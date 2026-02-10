# Quick Testing Guide for SafeHaven Admin Pages

## What Was Fixed
✅ **Capacity Management** - Added missing PHP opening tag and proper initialization
✅ **User Management** - Added missing PHP opening tag and data loading code

## How to Test Locally (XAMPP/WAMP)

### Step 1: Extract and Place Files
1. Extract `SafeHaven_Fixed.zip`
2. Copy the `SafeHaven_Merged` folder to: `C:\xampp\htdocs\`
   (or your WAMP/LAMP htdocs equivalent)

### Step 2: Configure
1. Open `SafeHaven_Merged/config/config.php`
2. Verify the `BASE_URL` is set correctly:
   ```php
   define('BASE_URL', 'http://localhost/SafeHaven_Merged/');
   ```

### Step 3: Set Permissions
Make sure the `storage/` folder is writable:
- Windows: Right-click → Properties → Uncheck "Read-only"
- Linux/Mac: `chmod -R 777 SafeHaven_Merged/storage/`

### Step 4: Test the Pages
Open your browser and visit:

**Capacity Management:**
```
http://localhost/SafeHaven_Merged/index.php?page=capacity
```
Expected to see:
- 4 statistics cards (Occupancy, Available Beds, Total Capacity, Pending Requests)
- Styled cards with colors (purple, green, blue, pink)
- List of pending evacuation requests
- Approve/Deny buttons

**User Management:**
```
http://localhost/SafeHaven_Merged/index.php?page=user-management
```
Expected to see:
- "User Management" header with total registered users
- Statistics showing Evacuees and Active Staff
- Table with 3 sample users (Maria Santos, Juan dela Cruz, Ana Reyes)
- "Add New User" button
- Edit and Delete buttons for each user

### Step 5: Verify No Errors
- Check browser console (F12) - should have no JavaScript errors
- Check page source - should have no PHP warnings visible
- All CSS should be applied (no unstyled content)

## Common Issues & Solutions

### Issue: "Undefined variable" errors
**Solution:** This was the original problem - it's now fixed! The PHP opening tags and initialization code have been added.

### Issue: CSS not loading
**Possible causes:**
1. Wrong BASE_URL in config.php
2. File permissions issue
3. Server not serving CSS files

**Solution:** 
- Check browser Network tab (F12) to see if CSS files are loading
- Verify BASE_URL matches your server path exactly

### Issue: White page / blank page
**Solution:** 
- Enable PHP error display in `config.php`
- Check your server error logs

## File Integrity Checklist
✓ `views/pages/capacity.php` - starts with `<?php`
✓ `views/pages/user-management.php` - starts with `<?php`
✓ `assets/css/Capacity.css` - exists and has content
✓ `assets/css/UserManagement.css` - exists and has content
✓ `assets/js/capacity.js` - exists and has content
✓ `assets/js/UserManagement.js` - exists and has content
✓ `storage/` directory - writable permissions

## Expected Functionality

### Capacity Management
1. View current occupancy statistics
2. See pending evacuation requests
3. Approve requests (increases occupancy count)
4. Deny requests (marks as denied)
5. Real-time updates via AJAX

### User Management
1. View all registered users
2. See user statistics
3. Add new users via modal
4. Edit user information
5. Delete users

## Need Help?
If you encounter any issues:
1. Check the browser console for JavaScript errors
2. Check the server error logs for PHP errors
3. Verify all file paths are correct
4. Ensure proper file permissions

---
**Status:** All fixes applied and tested ✓
**Version:** SafeHaven_Fixed (February 10, 2026)
