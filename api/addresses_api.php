<?php
// ── addresses_api.php ─────────────────────────────────────────
// Manages a user's saved delivery addresses (address book), used
// by checkout.php's "DELIVER TO" dropdown + "Add new address" modal.
//
// Actions:
//   list → get all saved addresses for the logged-in user
//   add  → save a new address (optionally marked as default)
//
// user_name is ALWAYS taken from the session — never from the request.
// ─────────────────────────────────────────────────────────────
session_start();
require_once '../config/db_config.php';

header('Content-Type: application/json');

// ── Auth guard ─────────────────────────────────────────────────
if (!isset($_SESSION['user_name'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}
$userName = $_SESSION['user_name'];

$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = $body['action'] ?? ($_GET['action'] ?? '');

switch ($action) {

    // ── LIST all saved addresses for this user ────────────────
    case 'list':
        $stmt = $connect->prepare(
            "SELECT id, label, recipient_name, phone, street_address,
                    barangay, city, province, zip_code, is_default
             FROM   addresses
             WHERE  user_name = ?
             ORDER  BY is_default DESC, created_at DESC"
        );
        $stmt->bind_param("s", $userName);
        $stmt->execute();
        $addresses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'addresses' => $addresses]);
        break;

    // ── ADD a new address ──────────────────────────────────────
    // Expects JSON:
    // {
    //   "action": "add",
    //   "label": "Home",              (optional)
    //   "recipient_name": "...",
    //   "phone": "09XXXXXXXXX",
    //   "street_address": "...",
    //   "barangay": "...",
    //   "city": "...",
    //   "province": "...",
    //   "zip_code": "...",
    //   "is_default": true|false
    // }
    case 'add':
        $label     = substr(trim($body['label']         ?? ''), 0, 50);
        $recipient = substr(trim($body['recipient_name'] ?? ''), 0, 150);
        $phone     = array_key_exists('phone', $body)
            ? substr(trim($body['phone'] ?? ''), 0, 20)
            : null;
        $street    = substr(trim($body['street_address']  ?? ''), 0, 255);
        $barangay  = substr(trim($body['barangay']       ?? ''), 0, 100);
        $city      = substr(trim($body['city']           ?? ''), 0, 100);
        $province  = substr(trim($body['province']       ?? ''), 0, 100);
        $zip       = substr(trim($body['zip_code']       ?? ''), 0, 10);
        $isDefault = !empty($body['is_default']) ? 1 : 0;

        if (!$recipient || !$street || !$barangay || !$city || !$province || !$zip) {
            echo json_encode(['success' => false, 'error' => 'Please fill in all required fields.']);
            break;
        }

        // A user's very first address is always the default, regardless
        // of the checkbox, so there's always one address pre-selected.
        $countStmt = $connect->prepare("SELECT COUNT(*) FROM addresses WHERE user_name = ?");
        $countStmt->bind_param("s", $userName);
        $countStmt->execute();
        $countStmt->bind_result($existingCount);
        $countStmt->fetch();
        $countStmt->close();
        if ((int) $existingCount === 0) {
            $isDefault = 1;
        }

        // Only one default per user — clear any existing one first.
        if ($isDefault) {
            $clr = $connect->prepare("UPDATE addresses SET is_default = 0 WHERE user_name = ?");
            $clr->bind_param("s", $userName);
            $clr->execute();
        }

        $stmt = $connect->prepare(
            "INSERT INTO addresses
               (user_name, label, recipient_name, phone, street_address,
                barangay, city, province, zip_code, is_default)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssssssssi",
            $userName, $label, $recipient, $phone, $street,
            $barangay, $city, $province, $zip, $isDefault
        );

        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'error' => 'Failed to save address.']);
            break;
        }

        echo json_encode([
            'success' => true,
            'address' => [
                'id'             => $connect->insert_id,
                'label'          => $label,
                'recipient_name' => $recipient,
                'phone'          => $phone,
                'street_address' => $street,
                'barangay'       => $barangay,
                'city'           => $city,
                'province'       => $province,
                'zip_code'       => $zip,
                'is_default'     => $isDefault,
            ],
        ]);
        break;

    // ── EDIT an existing address ───────────────────────────────
    // Expects JSON:
    // {
    //   "action": "edit",
    //   "id": 12,
    //   "label": "Home",
    //   "recipient_name": "...",
    //   "phone": "09XXXXXXXXX",
    //   "street_address": "...",
    //   "barangay": "...",
    //   "city": "...",
    //   "province": "...",
    //   "zip_code": "...",
    //   "is_default": true|false
    // }
    case 'edit':
        $id = (int) ($body['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid address id.']);
            break;
        }

        // Ownership check — never let a user edit another user's address.
        $own = $connect->prepare("SELECT id FROM addresses WHERE id = ? AND user_name = ?");
        $own->bind_param("is", $id, $userName);
        $own->execute();
        if ($own->get_result()->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Address not found.']);
            break;
        }

        $label     = substr(trim($body['label']         ?? ''), 0, 50);
        $recipient = substr(trim($body['recipient_name'] ?? ''), 0, 150);
        $phone     = array_key_exists('phone', $body)
            ? substr(trim($body['phone'] ?? ''), 0, 20)
            : null;
        $street    = substr(trim($body['street_address']  ?? ''), 0, 255);
        $barangay  = substr(trim($body['barangay']       ?? ''), 0, 100);
        $city      = substr(trim($body['city']           ?? ''), 0, 100);
        $province  = substr(trim($body['province']       ?? ''), 0, 100);
        $zip       = substr(trim($body['zip_code']       ?? ''), 0, 10);
        $isDefault = !empty($body['is_default']) ? 1 : 0;

        if (!$recipient || !$street || !$barangay || !$city || !$province || !$zip) {
            echo json_encode(['success' => false, 'error' => 'Please fill in all required fields.']);
            break;
        }

        // Only one default per user — clear any existing one first.
        if ($isDefault) {
            $clr = $connect->prepare("UPDATE addresses SET is_default = 0 WHERE user_name = ?");
            $clr->bind_param("s", $userName);
            $clr->execute();
        }

        $stmt = $connect->prepare(
            "UPDATE addresses
             SET label = ?, recipient_name = ?, phone = ?, street_address = ?,
                 barangay = ?, city = ?, province = ?, zip_code = ?, is_default = ?
             WHERE id = ? AND user_name = ?"
        );
        $stmt->bind_param("ssssssssiis",
            $label, $recipient, $phone, $street,
            $barangay, $city, $province, $zip, $isDefault, $id, $userName
        );

        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'error' => 'Failed to update address.']);
            break;
        }

        // Guard against ending up with zero defaults (e.g. user unchecked
        // "default" on the only address that had it).
        if (!$isDefault) {
            $chk = $connect->prepare("SELECT COUNT(*) FROM addresses WHERE user_name = ? AND is_default = 1");
            $chk->bind_param("s", $userName);
            $chk->execute();
            $chk->bind_result($defaultCount);
            $chk->fetch();
            $chk->close();
            if ((int) $defaultCount === 0) {
                $isDefault = 1;
                $mk = $connect->prepare("UPDATE addresses SET is_default = 1 WHERE id = ?");
                $mk->bind_param("i", $id);
                $mk->execute();
            }
        }

        echo json_encode([
            'success' => true,
            'address' => [
                'id'             => $id,
                'label'          => $label,
                'recipient_name' => $recipient,
                'phone'          => $phone,
                'street_address' => $street,
                'barangay'       => $barangay,
                'city'           => $city,
                'province'       => $province,
                'zip_code'       => $zip,
                'is_default'     => $isDefault,
            ],
        ]);
        break;

    // ── DELETE a saved address ─────────────────────────────────
    // Expects JSON: { "action": "delete", "id": 12 }
    case 'delete':
        $id = (int) ($body['id'] ?? ($_GET['id'] ?? 0));
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid address id.']);
            break;
        }

        // Ownership check + remember if this was the default one.
        $own = $connect->prepare("SELECT is_default FROM addresses WHERE id = ? AND user_name = ?");
        $own->bind_param("is", $id, $userName);
        $own->execute();
        $row = $own->get_result()->fetch_assoc();
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Address not found.']);
            break;
        }
        $wasDefault = (int) $row['is_default'] === 1;

        $del = $connect->prepare("DELETE FROM addresses WHERE id = ? AND user_name = ?");
        $del->bind_param("is", $id, $userName);
        if (!$del->execute()) {
            echo json_encode(['success' => false, 'error' => 'Failed to delete address.']);
            break;
        }

        // If the deleted address was the default, promote the most
        // recently added remaining one so there's always a default on file.
        $newDefaultId = null;
        if ($wasDefault) {
            $next = $connect->prepare(
                "SELECT id FROM addresses WHERE user_name = ? ORDER BY created_at DESC LIMIT 1"
            );
            $next->bind_param("s", $userName);
            $next->execute();
            $nextRow = $next->get_result()->fetch_assoc();
            if ($nextRow) {
                $newDefaultId = (int) $nextRow['id'];
                $mk = $connect->prepare("UPDATE addresses SET is_default = 1 WHERE id = ?");
                $mk->bind_param("i", $newDefaultId);
                $mk->execute();
            }
        }

        echo json_encode([
            'success'        => true,
            'deleted_id'     => $id,
            'new_default_id' => $newDefaultId,
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}