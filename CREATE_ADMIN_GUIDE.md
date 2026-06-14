# 🔐 Create Admin Account - Step by Step

## Quick Start (2 minutes)

### Step 1: Open the Admin Creator
Go to your browser and open:
```
http://localhost/create_admin.php
```

Or on live server:
```
http://yoursite.com/create_admin.php
```

---

## Step 2: Fill in the Form

| Field | Example | Notes |
|-------|---------|-------|
| **First Name** | John | Any name |
| **Last Name** | Admin | Any name |
| **Email** | admin@boycold.com | **MUST contain "admin"** |
| **Password** | SecurePass123 | See requirements below |
| **Confirm Password** | SecurePass123 | Must match password |

### Password Requirements:
- ✓ At least 8 characters
- ✓ At least 1 UPPERCASE letter (A-Z)
- ✓ At least 1 lowercase letter (a-z)
- ✓ At least 1 number (0-9)

**Examples of valid passwords:**
- `SecurePass123` ✓
- `Admin@2024Pass` ✓
- `MyAdmin456` ✓

**Examples of invalid passwords:**
- `password123` ✗ (no uppercase)
- `PASSWORD123` ✗ (no lowercase)
- `Password` ✗ (no number, too short)
- `pass123` ✗ (too short, no uppercase)

---

## Step 3: Click "Create Admin User"

Once you click the button:
- ✅ Admin account will be created
- ✅ You'll see a success message
- ✅ Email will be confirmed (no OTP needed for admin)

---

## Step 4: Delete the Create Admin File

**For Security**, delete the `create_admin.php` file after creating the admin account:

**Option 1: Delete via File Explorer**
```
C:\laragon\www\boycoldv2\create_admin.php → Delete
```

**Option 2: Delete via Command**
```powershell
cd C:\laragon\www\boycoldv2
Remove-Item create_admin.php
```

**Option 3: Delete via FTP/SSH**
- Connect to your server
- Delete `create_admin.php`

---

## Step 5: Login as Admin

1. Go to the **Login page**: `http://localhost/login.php`
2. Enter your **admin email** and **password**
3. Click **Login**

---

## Step 6: Access Admin Dashboard

After logging in, go to:
```
http://localhost/admin/dashboard.php
```

You should see:
- ✅ List of all orders
- ✅ Payment status for each order
- ✅ Ability to update order status
- ✅ Statistics dashboard

---

## ✨ Example Admin Account

Here's a recommended admin account setup:

| Field | Value |
|-------|-------|
| First Name | BoyCold |
| Last Name | Admin |
| Email | **admin@boycold.com** |
| Password | **ColdAdmin@2024** |

**After creating:**
1. Delete `create_admin.php`
2. Login with `admin@boycold.com` / `ColdAdmin@2024`
3. Access `/admin/dashboard.php`

---

## 🔒 Security Best Practices

✅ **Do:**
- Use a strong password (mix of uppercase, lowercase, numbers)
- Keep the password private
- Delete `create_admin.php` after creating admin
- Use unique email with "admin" in it

❌ **Don't:**
- Share the admin password
- Leave `create_admin.php` on the server
- Use simple passwords like "admin123"
- Use same password as your email account

---

## ⚠️ Troubleshooting

### "Email must contain 'admin'"
- **Problem**: Your email doesn't have "admin" in it
- **Solution**: Use email like `admin@boycold.com` or `john_admin@boycold.com`

### "Password does not meet requirements"
- **Problem**: Password too short or missing uppercase/lowercase/number
- **Solution**: Use at least 8 characters with uppercase, lowercase, and number
- **Example**: `ColdAdmin@2024` ✓

### "This email already exists"
- **Problem**: Email is already registered
- **Solution**: Use a different email address

### Can't access admin dashboard after login
- **Problem**: Not logged in as admin user
- **Solution**: 
  1. Check that your email contains "admin"
  2. Clear browser cache and login again

### "Access Denied" in admin dashboard
- **Problem**: Your user email doesn't contain "admin"
- **Solution**: Update your email via SQL:
```sql
UPDATE users SET email = 'admin@boycold.com' WHERE id = [your_user_id];
```

---

## 🎯 After Admin Account Created

You can now:

1. **View all orders** in the admin dashboard
2. **Update order status** (pending → preparing → ready → delivered → completed)
3. **Track payment status** (unpaid/paid)
4. **See statistics** on pending and completed orders
5. **Manage COD payments** (auto-settles when order completed)

---

## 🚀 Multi-Admin Setup (Optional)

If you want multiple admins, run `create_admin.php` multiple times with different emails:
- `admin1@boycold.com`
- `manager_admin@boycold.com`
- `owner_admin@boycold.com`

All will have access to the admin dashboard.

---

## 📝 Remember

| Task | When | Command/Location |
|------|------|---|
| Create admin | First time | `http://localhost/create_admin.php` |
| Delete create_admin.php | After creating admin | Delete the file |
| Login | Every session | `http://localhost/login.php` |
| Access dashboard | Managing orders | `http://localhost/admin/dashboard.php` |

---

**✅ That's it! You now have a secure admin account with dedicated access.**
