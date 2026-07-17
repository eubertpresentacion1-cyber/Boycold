<?php
$file = __DIR__ . '/status.php';
$content = file_get_contents($file);

// Find and replace the stepReached section
$pattern = '/\$stepReached = \[false, false, false, false, false, false\];\s*if \(\$hasOrder\) \{[^}]*\$status\s+= \$latestOrder\[.status.\];[^}]*\$paymentStatus = \$latestOrder\[.payment_status.\];[^}]*\$stepReached\[0\] = true;[^}]*\$stepReached\[1\] = in_array\(\$status, \[.confirmed., .preparing., .ready., .delivered., .completed.\]\);[^}]*\$stepReached\[2\] = \(\$paymentStatus === .paid.\);[^}]*\$stepReached\[3\] = in_array\(\$status, \[.preparing., .ready., .delivered., .completed.\]\);[^}]*\$stepReached\[4\] = in_array\(\$status, \[.ready., .delivered., .completed.\]\);[^}]*\$stepReached\[5\] = in_array\(\$status, \[.delivered., .completed.\]\);[^}]*\}/s';

$newCode = '// Define different status flows based on payment method
$stepReached = [false, false, false, false, false];
$stepLabels = [];
$stepIcons = [];
if ($hasOrder) {
    $status        = $latestOrder[\'status\'];
    $paymentStatus = $latestOrder[\'payment_status\'];
    $paymentMethodKey = strtolower($latestOrder[\'payment_method\'] ?? \'\');
    
    if ($paymentMethodKey === \'gcash\') {
        // GCash flow: Order Confirm → Payment Pending → Preparing → Out for Delivery → Delivered
        $stepReached[0] = true;
        $stepReached[1] = in_array($status, [\'confirmed\', \'preparing\', \'ready\', \'delivered\', \'completed\']);
        $stepReached[2] = ($paymentStatus === \'paid\');
        $stepReached[3] = in_array($status, [\'preparing\', \'ready\', \'delivered\', \'completed\']);
        $stepReached[4] = in_array($status, [\'ready\', \'delivered\', \'completed\']);
        $stepLabels = [
            $status === \'pending\' ? \'Order Pending\' : \'Order Confirmed\',
            $paymentStepLabel,
            \'Preparing\',
            \'Out for Delivery\',
            \'Delivered\'
        ];
        $stepIcons = [\'fa-clock\', \'fa-clipboard-check\', \'fa-credit-card\', \'fa-truck\', \'fa-house\'];
    } else {
        // COD flow: Order Confirm → Preparing → Out for Delivery → Payment Confirm → Delivered
        $stepReached[0] = true;
        $stepReached[1] = in_array($status, [\'preparing\', \'ready\', \'delivered\', \'completed\']);
        $stepReached[2] = in_array($status, [\'ready\', \'delivered\', \'completed\']);
        $stepReached[3] = ($paymentStatus === \'paid\');
        $stepReached[4] = in_array($status, [\'delivered\', \'completed\']);
        $stepLabels = [
            $status === \'pending\' ? \'Order Pending\' : \'Order Confirmed\',
            \'Preparing\',
            \'Out for Delivery\',
            \'Payment Confirm\',
            \'Delivered\'
        ];
        $stepIcons = [\'fa-clock\', \'fa-mug-hot\', \'fa-truck\', \'fa-credit-card\', \'fa-house\'];
    }
} else {
    $stepLabels = [\'Order Pending\', \'Payment Pending\', \'Preparing\', \'Out for Delivery\', \'Delivered\'];
    $stepIcons = [\'fa-clock\', \'fa-clipboard-check\', \'fa-credit-card\', \'fa-truck\', \'fa-house\'];
}';

$content = preg_replace($pattern, $newCode, $content);

// Now update the HTML section to use dynamic steps - simpler approach
// Find the start of progress-tracker and replace everything until the closing div
$startMarker = '<div class="progress-tracker">';
$endMarker = '</div>';
$endPos = strpos($content, $endMarker, strpos($content, $startMarker));
if ($endPos !== false) {
    $startPos = strpos($content, $startMarker);
    $fullEndPos = $endPos + strlen($endMarker);
    
    $htmlReplacement = '<div class="progress-tracker">
                <div class="progress-line-fill" style="width: <?= $fillPercent ?>%;"></div>
                <?php foreach ($stepLabels as $index => $label): ?>
                <div class="progress-step">
                    <div class="<?= step_class($stepReached[$index]) ?>">
                        <?php if (isset($stepIcons[$index]) && strpos($stepIcons[$index], \'fa-\') === 0): ?>
                            <i class="fa-solid <?= htmlspecialchars($stepIcons[$index]) ?>"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-clock"></i>
                        <?php endif; ?>
                    </div>
                    <div class="step-label"><?= htmlspecialchars($label) ?></div>
                </div>
                <?php endforeach; ?>
            </div>';
    
    $content = substr($content, 0, $startPos) . $htmlReplacement . substr($content, $fullEndPos);
}

file_put_contents($file, $content);
echo "Updated successfully";
?>
