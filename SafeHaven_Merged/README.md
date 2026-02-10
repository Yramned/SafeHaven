# SafeHaven - Emergency Evacuation System (Merged Version)

## 🎯 Overview
This is the **MERGED and FIXED** version of SafeHaven that combines:
- **Visual Design** from SafeHaven(3) - Beautiful, modern UI with the best landing page
- **MVC Structure** from SafeHaven_FIXED - Clean, organized code architecture
- **Bug Fixes** - All reported errors have been fixed

## ✅ What's Been Fixed
1. **Evacuation Request Page** - Now properly loads with correct routing
2. **Situational Alerts Page** - Fixed PHP header issues and proper routing  
3. **Landing Page** - Beautiful hero section, features, how-it-works, and contact form from SafeHaven(3)
4. **All Pathing** - Verified and corrected for both localhost and HelioHost
5. **MVC Compliance** - Maintained proper Model-View-Controller structure

## 🚀 Quick Start

### For Localhost
1. Extract to: `C:\xampp\htdocs\SafeHaven_Merged\`
2. Visit: `http://localhost/SafeHaven_Merged/`
3. Login with: admin@safehaven.com / admin123

### For HelioHost
1. Edit `config/config.php` line 25 with your URL
2. Upload all files to `public_html/`
3. Set `storage/` permissions to 777
4. Visit your HelioHost URL

## 🔑 Default Login
- Admin: admin@safehaven.com / password
- User: user@example.com / password

## 📁 Key Files
- `index.php` - Main router
- `config/config.php` - Configuration (UPDATE BASE_URL for HelioHost)
- `views/pages/home.php` - Landing page
- `views/pages/evacuation-request.php` - Evacuation form
- `views/pages/alerts.php` - Situational alerts

## 🛠️ Troubleshooting
- **Blank page**: Check BASE_URL in config/config.php
- **Can't write**: chmod 777 storage/
- **CSS not loading**: Verify BASE_URL matches your actual URL

Ready to use! 🚀
