<?php
/**
 * Create Admin User - Run once to create admin account
 * 
 * USAGE:
 * 1. Save this file as: create_admin.php
 * 2. Place it in the root directory (same level as login.php)
 * 3. Open in browser: http://localhost/create_admin.php
 * 4. Follow the form to create admin user
 * 5. Delete this file after creating admin
 */

session_start();
require_once './config/db_config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm'] ?? '';

    // Validation
    if (!$firstname || !$lastname || !$email || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (strlen($password) > 25) {
        $error = 'Password must be less than 25 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strpos(strtolower($email), 'admin') === false) {
        $error = 'Email must contain "admin" to create admin account.';
    } else {
        // Check if email already exists
        $chk = $connect->prepare("SELECT id FROM users WHERE email = ?");
        $chk->bind_param("s", $email);
        $chk->execute();
        
        if ($chk->get_result()->num_rows > 0) {
            $error = 'This email already exists in the system.';
        } else {
            // Hash password
            $hashedPass = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert admin user (verified=1 so no OTP needed)
            $stmt = $connect->prepare(
                "INSERT INTO users (firstname, lastname, email, password, is_verified, phone, address, avatar, created_at)
                 VALUES (?, ?, ?, ?, 1, NULL, NULL, NULL, NOW())"
            );
            
            if (!$stmt) {
                $error = 'Database error: ' . $connect->error;
            } else {
                $stmt->bind_param("ssss", $firstname, $lastname, $email, $hashedPass);
                
                if ($stmt->execute()) {
                    $message = '✅ Admin user created successfully!<br>';
                    $message .= '<strong>Email:</strong> ' . htmlspecialchars($email) . '<br>';
                    $message .= '<strong>Name:</strong> ' . htmlspecialchars($firstname . ' ' . $lastname) . '<br><br>';
                    $message .= 'You can now login and access the admin dashboard at:<br>';
                    $message .= '<code>/admin/dashboard.php</code><br><br>';
                    $message .= '<em>Please delete this file (create_admin.php) after creating the admin account for security.</em>';
                } else {
                    $error = 'Error creating admin account: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
        $chk->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User - BoyCold Cafe</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Afacad', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 400px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
            font-size: 28px;
        }
        
        .subtitle {
            color: #999;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #555;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .password-requirements {
            background: #f5f5f5;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 13px;
            color: #666;
        }
        
        .requirement {
            margin: 5px 0;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        .security-note {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 12px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Create Admin User</h1>
        <p class="subtitle">Set up your admin account for BoyCold Cafe</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?= $message ?>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input 
                        type="text" 
                        id="firstname" 
                        name="firstname" 
                        placeholder="e.g., John"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input 
                        type="text" 
                        id="lastname" 
                        name="lastname" 
                        placeholder="e.g., Admin"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="email">Email (must contain "admin")</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="admin@boycold.com"
                        required
                    >
                    <small style="color: #999; margin-top: 5px; display: block;">
                        Example: admin@boycold.com or john_admin@boycold.com
                    </small>
                </div>
                
                <div class="password-requirements">
                    <strong>Password Requirements:</strong>
                    <div class="requirement">✓ At least 8 characters</div>
                    <div class="requirement">✓ One uppercase letter (A-Z)</div>
                    <div class="requirement">✓ One lowercase letter (a-z)</div>
                    <div class="requirement">✓ One number (0-9)</div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Min. 8 characters"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="confirm">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirm" 
                        name="confirm" 
                        placeholder="Re-enter password"
                        required
                    >
                </div>
                
                <button type="submit">Create Admin User</button>
            </form>
            
            <div class="security-note">
                ⚠️ <strong>Important:</strong> After creating the admin account, please delete this file (<code>create_admin.php</code>) for security.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
