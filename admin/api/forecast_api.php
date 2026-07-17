<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/db_config.php';

// Get parameters
$branchId = isset($_GET['branch_id']) ? $_GET['branch_id'] : 'all';
$forecastDays = isset($_GET['forecast_days']) ? intval($_GET['forecast_days']) : 14;
$historicalDays = isset($_GET['historical_days']) ? intval($_GET['historical_days']) : 28;

// Branch filter
$branchCondition = '';
$params = [];
$types = '';
if ($branchId !== 'all') {
    $branchCondition = 'AND o.branch_id = ?';
    $params[] = $branchId;
    $types .= 'i';
}

// ==========================================
// 1. DAILY SALES HISTORY (last N days)
// ==========================================
$dailySalesQuery = "SELECT 
    DATE(o.created_at) as sale_date,
    COALESCE(SUM(o.total), 0) as total_sales,
    COUNT(o.id) as total_orders
FROM orders o
WHERE o.status NOT IN ('cancelled')
    AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    $branchCondition
GROUP BY DATE(o.created_at)
ORDER BY sale_date ASC";

$stmt = $connect->prepare($dailySalesQuery);
$histDaysParam = $historicalDays;
if ($branchId !== 'all') {
    $stmt->bind_param('i' . $types, $histDaysParam, ...$params);
} else {
    $stmt->bind_param('i', $histDaysParam);
}
$stmt->execute();
$result = $stmt->get_result();

$historicalSales = [];
$historicalDates = [];
while ($row = $result->fetch_assoc()) {
    $historicalSales[] = [
        'date' => $row['sale_date'],
        'sales' => floatval($row['total_sales']),
        'orders' => intval($row['total_orders'])
    ];
    $historicalDates[] = $row['sale_date'];
}
$stmt->close();

// ==========================================
// 2. FORECAST SALES (next N days)
// Uses: Linear Regression + Seasonal Adjustment
// ==========================================
function computeForecast($historicalSales, $forecastDays) {
    $n = count($historicalSales);
    if ($n < 3) {
        // Not enough data, return simple average
        $avg = $n > 0 ? array_sum(array_column($historicalSales, 'sales')) / $n : 0;
        $forecast = [];
        for ($i = 1; $i <= $forecastDays; $i++) {
            $forecast[] = round($avg + (rand(-5, 5) / 100) * $avg, 2);
        }
        return $forecast;
    }
    
    // Linear regression on sales
    $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
    foreach ($historicalSales as $i => $day) {
        $x = $i + 1;
        $y = $day['sales'];
        $sumX += $x;
        $sumY += $y;
        $sumXY += $x * $y;
        $sumX2 += $x * $x;
    }
    
    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    $intercept = ($sumY - $slope * $sumX) / $n;
    
    // Extract day-of-week patterns for seasonality (if we have enough data)
    $dayOfWeekPattern = [0,0,0,0,0,0,0]; // Sun=0, Mon=1, ...
    $dayOfWeekCount = [0,0,0,0,0,0,0];
    
    foreach ($historicalSales as $day) {
        $dow = date('w', strtotime($day['date']));
        $dayOfWeekPattern[$dow] += $day['sales'];
        $dayOfWeekCount[$dow]++;
    }
    
    $overallAvg = $sumY / $n;
    for ($i = 0; $i < 7; $i++) {
        if ($dayOfWeekCount[$i] > 0) {
            $dayOfWeekPattern[$i] = ($dayOfWeekPattern[$i] / $dayOfWeekCount[$i]) / $overallAvg;
        } else {
            $dayOfWeekPattern[$i] = 1.0;
        }
    }
    
    // Generate forecasts
    $forecast = [];
    $lastDate = new DateTime(end($historicalSales)['date']);
    
    for ($i = 1; $i <= $forecastDays; $i++) {
        $x = $n + $i;
        $trendValue = $intercept + $slope * $x;
        if ($trendValue < 0) $trendValue = 0;
        
        // Apply day-of-week seasonality
        $forecastDate = clone $lastDate;
        $forecastDate->modify("+{$i} days");
        $dow = intval($forecastDate->format('w'));
        $seasonalFactor = $dayOfWeekPattern[$dow];
        
        $forecastedValue = $trendValue * $seasonalFactor;
        $forecast[] = round(max($forecastedValue, 0), 2);
    }
    
    return $forecast;
}

$forecastedSales = computeForecast($historicalSales, $forecastDays);

// ==========================================
// 3. DEMAND FORECAST (Top Menu Items)
// ==========================================
$demandQuery = "SELECT 
    oi.product_name,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.line_total) as total_revenue,
    COUNT(DISTINCT DATE(o.created_at)) as days_sold
FROM order_items oi
INNER JOIN orders o ON oi.order_id = o.id
WHERE o.status NOT IN ('cancelled')
    AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    $branchCondition
GROUP BY oi.product_name
ORDER BY total_quantity DESC
LIMIT 10";

$stmt = $connect->prepare($demandQuery);
if ($branchId !== 'all') {
    $stmt->bind_param('i' . $types, $histDaysParam, ...$params);
} else {
    $stmt->bind_param('i', $histDaysParam);
}
$stmt->execute();
$result = $stmt->get_result();

$demandItems = [];
$maxQuantity = 0;
while ($row = $result->fetch_assoc()) {
    $qty = intval($row['total_quantity']);
    if ($qty > $maxQuantity) $maxQuantity = $qty;
    $demandItems[] = [
        'product_name' => $row['product_name'],
        'total_quantity' => $qty,
        'total_revenue' => floatval($row['total_revenue']),
        'days_sold' => intval($row['days_sold'])
    ];
}
$stmt->close();

// Forecast demand for each top item using proportion of total
$totalHistoricalSales = array_sum(array_column($historicalSales, 'sales'));
$totalForecastedSales = array_sum($forecastedSales);

if ($totalHistoricalSales > 0) {
    $salesGrowthFactor = $totalForecastedSales / $totalHistoricalSales;
} else {
    $salesGrowthFactor = 1;
}

$demandForecast = [];
foreach ($demandItems as $item) {
    $avgDailyQty = $item['days_sold'] > 0 ? $item['total_quantity'] / min($item['days_sold'], $historicalDays) : 0;
    $forecastedQty = round($avgDailyQty * $forecastDays * $salesGrowthFactor);
    
    // Determine trend
    $trend = 'stable';
    $trendIcon = 'minus';
    $trendPercent = 0;
    
    // Check if this item was in the last 7 days vs the 7 days before that
    $trendQuery = "SELECT 
        SUM(CASE WHEN o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN oi.quantity ELSE 0 END) as recent,
        SUM(CASE WHEN o.created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND o.created_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN oi.quantity ELSE 0 END) as previous
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    WHERE o.status NOT IN ('cancelled')
        AND oi.product_name = ?
        AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
        $branchCondition";
    
    $stmt = $connect->prepare(str_replace('$branchCondition', $branchCondition, $trendQuery));
    $itemParams = array_merge([$item['product_name']], $params);
    $itemTypes = 's' . $types;
    $stmt->bind_param($itemTypes, ...$itemParams);
    $stmt->execute();
    $trendResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $recent = intval($trendResult['recent']);
    $previous = intval($trendResult['previous']);
    
    if ($previous > 0) {
        $trendPercent = round((($recent - $previous) / $previous) * 100, 1);
        if ($trendPercent > 5) {
            $trend = 'increasing';
            $trendIcon = 'arrow-up';
        } elseif ($trendPercent < -5) {
            $trend = 'decreasing';
            $trendIcon = 'arrow-down';
        }
    }
    
    $item['forecasted_quantity'] = $forecastedQty;
    $item['trend'] = $trend;
    $item['trend_icon'] = $trendIcon;
    $item['trend_percent'] = $trendPercent;
    $item['progress'] = $maxQuantity > 0 ? round(($qty / $maxQuantity) * 100) : 0;
    
    $demandForecast[] = $item;
}

// ==========================================
// 4. PEAK HOURS FORECAST
// ==========================================
$peakHoursQuery = "SELECT 
    HOUR(o.created_at) as hour,
    COUNT(*) as order_count,
    COUNT(DISTINCT DATE(o.created_at)) as days_active
FROM orders o
WHERE o.status NOT IN ('cancelled')
    AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    $branchCondition
GROUP BY HOUR(o.created_at)
ORDER BY order_count DESC";

$stmt = $connect->prepare($peakHoursQuery);
if ($branchId !== 'all') {
    $stmt->bind_param('i' . $types, $histDaysParam, ...$params);
} else {
    $stmt->bind_param('i', $histDaysParam);
}
$stmt->execute();
$result = $stmt->get_result();

$peakHours = [];
$maxOrders = 0;
$hourlyData = [];
while ($row = $result->fetch_assoc()) {
    $orders = intval($row['order_count']);
    $hourlyData[intval($row['hour'])] = $orders;
    if ($orders > $maxOrders) $maxOrders = $orders;
}

// Generate all 24 hours with predictions where missing
for ($h = 0; $h < 24; $h++) {
    $orders = isset($hourlyData[$h]) ? $hourlyData[$h] : 0;
    $daysActive = $historicalDays;
    
    // Predict future peak using average orders per day
    $avgPerDay = $daysActive > 0 ? $orders / $daysActive : 0;
    
    // For hours with no data, estimate based on nearby hours
    if ($avgPerDay == 0) {
        // Check adjacent hours
        $neighbors = [];
        for ($offset = -2; $offset <= 2; $offset++) {
            $nh = ($h + $offset + 24) % 24;
            if (isset($hourlyData[$nh]) && $hourlyData[$nh] > 0) {
                $neighbors[] = $hourlyData[$nh];
            }
        }
        if (count($neighbors) > 0) {
            $avgPerDay = array_sum($neighbors) / count($neighbors) / $daysActive * 0.5;
        }
    }
    
    $predictedOrders = round($avgPerDay * $forecastDays);
    
    // Traffic level labels
    if ($maxOrders > 0) {
        $ratio = $orders / $maxOrders;
        if ($ratio >= 0.8) {
            $traffic = 'Very High';
            $color = '#E5383B';
        } elseif ($ratio >= 0.5) {
            $traffic = 'High';
            $color = '#EB7B45';
        } elseif ($ratio >= 0.3) {
            $traffic = 'Medium';
            $color = '#F2994A';
        } elseif ($ratio > 0) {
            $traffic = 'Low';
            $color = '#F5D76E';
        } else {
            $traffic = 'None';
            $color = '#E0E0E0';
        }
    } else {
        $traffic = 'None';
        $color = '#E0E0E0';
    }
    
    $peakHours[] = [
        'hour' => $h,
        'label' => sprintf('%02d:00 - %02d:00', $h, ($h + 1) % 24),
        'orders' => $orders,
        'predicted_orders' => $predictedOrders,
        'traffic' => $traffic,
        'color' => $color,
        'progress' => $maxOrders > 0 ? round(($orders / $maxOrders) * 100) : 0
    ];
}

// Sort peak hours by actual orders descending, keep top hours
usort($peakHours, function($a, $b) {
    return $b['orders'] - $a['orders'];
});
$peakHours = array_slice($peakHours, 0, 8);

// Sort back by hour for display
usort($peakHours, function($a, $b) {
    return $a['hour'] - $b['hour'];
});

// ==========================================
// 5. TRENDING DRINKS PREDICTION
// ==========================================
$trendingQuery = "SELECT 
    oi.product_name,
    SUM(CASE WHEN o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN oi.quantity ELSE 0 END) as recent_7,
    SUM(CASE WHEN o.created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND o.created_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN oi.quantity ELSE 0 END) as prev_7
FROM order_items oi
INNER JOIN orders o ON oi.order_id = o.id
WHERE o.status NOT IN ('cancelled')
    AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    $branchCondition
GROUP BY oi.product_name
HAVING recent_7 > 0 OR prev_7 > 0
ORDER BY recent_7 DESC
LIMIT 20";

$stmt = $connect->prepare($trendingQuery);
if ($branchId !== 'all') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$trendingItems = [];
while ($row = $result->fetch_assoc()) {
    $recent = intval($row['recent_7']);
    $previous = intval($row['prev_7']);
    
    if ($previous > 0) {
        $changePercent = round((($recent - $previous) / $previous) * 100, 1);
    } elseif ($recent > 0) {
        $changePercent = 100; // New item, big increase
    } else {
        $changePercent = -100; // Dropped off
    }
    
    $trendingItems[] = [
        'product_name' => $row['product_name'],
        'recent_7' => $recent,
        'prev_7' => $previous,
        'change_percent' => $changePercent,
        'is_up' => $changePercent >= 0
    ];
}

// Sort by absolute change percentage to find trending
usort($trendingItems, function($a, $b) {
    return abs($b['change_percent']) - abs($a['change_percent']);
});
$trendingItems = array_slice($trendingItems, 0, 6);

// ==========================================
// 6. INGREDIENT RESTOCK PREDICTIONS
// ==========================================
$restockQuery = "SELECT 
    i.name,
    i.stock,
    i.unit,
    COALESCE(SUM(iud.amount_used), 0) as total_used,
    COUNT(DISTINCT iud.usage_date) as days_used
FROM ingredients i
LEFT JOIN ingredient_usage_daily iud ON i.id = iud.ingredient_id
    AND iud.usage_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
WHERE i.branch_id = ? OR ? = 'all'
GROUP BY i.id, i.name, i.stock, i.unit
ORDER BY i.name";

$restockBranchId = ($branchId !== 'all') ? intval($branchId) : 1;
$stmt = $connect->prepare($restockQuery);
$stmt->bind_param('iis', $histDaysParam, $restockBranchId, $branchId);
$stmt->execute();
$result = $stmt->get_result();

$restockItems = [];
$criticalCount = 0;
$soonCount = 0;
while ($row = $result->fetch_assoc()) {
    $stock = floatval($row['stock']);
    $totalUsed = floatval($row['total_used']);
    $daysUsed = intval($row['days_used']);
    
    $dailyUsage = $daysUsed > 0 ? $totalUsed / $daysUsed : 0;
    $daysRemaining = $dailyUsage > 0 ? $stock / $dailyUsage : 999;
    
    $status = 'ok';
    if ($daysRemaining <= 3) {
        $status = 'critical';
        $criticalCount++;
    } elseif ($daysRemaining <= 7) {
        $status = 'soon';
        $soonCount++;
    }
    
    $restockItems[] = [
        'name' => $row['name'],
        'stock' => $stock,
        'unit' => $row['unit'],
        'daily_usage' => round($dailyUsage, 2),
        'days_remaining' => round($daysRemaining, 1),
        'status' => $status
    ];
}

// ==========================================
// 7. OVERALL STATS & INSIGHTS
// ==========================================

// Current week vs previous week comparison
$weekComparisonQuery = "SELECT 
    SUM(CASE WHEN o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN o.total ELSE 0 END) as this_week,
    SUM(CASE WHEN o.created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND o.created_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN o.total ELSE 0 END) as last_week,
    COUNT(CASE WHEN o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as this_week_orders,
    COUNT(CASE WHEN o.created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND o.created_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as last_week_orders
FROM orders o
WHERE o.status NOT IN ('cancelled')
    $branchCondition
    AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)";

$stmt = $connect->prepare($weekComparisonQuery);
if ($branchId !== 'all') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$weekData = $stmt->get_result()->fetch_assoc();
$stmt->close();

$thisWeekSales = floatval($weekData['this_week']);
$lastWeekSales = floatval($weekData['last_week']);
$salesChangePercent = $lastWeekSales > 0 ? round((($thisWeekSales - $lastWeekSales) / $lastWeekSales) * 100, 1) : 0;

// Predicted sales for next 14 days
$predictedSales14 = round(array_sum($forecastedSales), 2);

// Highest demand item
$highestDemandItem = !empty($demandForecast) ? $demandForecast[0]['product_name'] : 'N/A';
$highestDemandQty = !empty($demandForecast) ? $demandForecast[0]['forecasted_quantity'] : 0;

// ==========================================
// BUILD RESPONSE
// ==========================================
$response = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'forecast_date' => date('Y-m-d'),
    'stats' => [
        'predicted_sales_next_14' => $predictedSales14,
        'sales_change_percent' => $salesChangePercent,
        'this_week_sales' => $thisWeekSales,
        'last_week_sales' => $lastWeekSales,
        'this_week_orders' => intval($weekData['this_week_orders']),
        'last_week_orders' => intval($weekData['last_week_orders']),
        'critical_restocks' => $criticalCount,
        'soon_restocks' => $soonCount,
        'highest_demand_item' => $highestDemandItem,
        'highest_demand_qty' => $highestDemandQty,
        'total_ingredients' => count($restockItems)
    ],
    'historical_sales' => $historicalSales,
    'forecasted_sales' => $forecastedSales,
    'historical_dates' => $historicalDates,
    'demand_forecast' => $demandForecast,
    'peak_hours' => $peakHours,
    'trending_items' => $trendingItems,
    'restock_items' => $restockItems,
    'insights' => [
        [
            'type' => $salesChangePercent >= 0 ? 'positive' : 'negative',
            'icon' => $salesChangePercent >= 0 ? 'arrow-trend-up' : 'arrow-trend-down',
            'heading' => 'Sales ' . ($salesChangePercent >= 0 ? 'increase' : 'decrease') . ' by ' . abs($salesChangePercent) . '%',
            'desc' => $salesChangePercent >= 0 ? 'Great job! Your sales are higher than last week.' : 'Sales are lower than last week. Consider promotions.'
        ],
        [
            'type' => 'info',
            'icon' => 'star',
            'heading' => $highestDemandItem,
            'desc' => 'Top selling item with ~' . $highestDemandQty . ' orders forecasted.'
        ],
        [
            'type' => 'warning',
            'icon' => 'clock',
            'heading' => 'Peak hours at ' . (!empty($peakHours) ? $peakHours[0]['label'] : 'N/A'),
            'desc' => 'Prepare your team and stocks before the rush.'
        ]
    ]
];

echo json_encode($response);