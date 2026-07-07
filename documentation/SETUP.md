# Quick Setup Guide

## Pre-Deployment Checklist

- [x] PHP 8.3 confirmed available
- [x] Database credentials verified
- [x] VPS hosting confirmed
- [x] Domain: app.drfeelgoods.in
- [x] Replica database ready

## Files Created

### Configuration Files
- `config/database.php` - Database connection with VPS credentials
- `config/constants.php` - Application constants and settings

### Models (Database Layer)
- `src/Models/Database.php` - Base model class with common operations
- `src/Models/Patient.php` - Patient data operations
- `src/Models/ProgressReport.php` - Treatment history operations
- `src/Models/User.php` - User authentication
- `src/Models/AdditionalInfo.php` - Medical assessment data

### Controllers (Business Logic)
- `src/Controllers/AuthController.php` - Login, logout, session management
- `src/Controllers/PatientController.php` - Patient CRUD operations

### Views (User Interface)
- `views/layout.php` - Main page layout with sidebar navigation
- `views/auth/login.php` - Clean, modern login page
- `views/dashboard.php` - Dashboard with stats and quick actions
- `views/patient/list.php` - Patient list with search
- `views/patient/detail.php` - Patient profile with complete history
- `views/patient/create.php` - Add new patient form
- `views/error/404.php` - 404 error page

### Entry Point & Configuration
- `public/index.php` - Single entry point with routing
- `public/.htaccess` - URL rewriting for clean URLs
- `.gitignore` - Git ignore file (optional)

### Documentation
- `README.md` - Complete documentation
- `SETUP.md` - This file

## Deployment Steps

### Step 1: Upload to VPS

```bash
# SSH into VPS
ssh root@your-vps-ip

# Navigate to hosting directory
cd /home/silverwebbuzz_in/public_html/drfeelgoods.in/

# If app folder doesn't exist, create it
mkdir -p app
cd app

# Upload files here (use SFTP or cp from source directory)
```

### Step 2: Set File Permissions

```bash
chmod 755 public/
chmod 755 storage/
chmod 644 public/index.php
chmod 644 public/.htaccess
```

### Step 3: Create Storage Directory

```bash
mkdir -p storage
chmod 755 storage
```

### Step 4: Verify Apache Configuration

```bash
# Check if mod_rewrite is enabled
a2enmod rewrite

# Restart Apache
systemctl restart apache2
```

### Step 5: Test Application

```
Browser: https://app.drfeelgoods.in/app/
```

## Default Login Credentials

Use your existing doctor credentials from the old system:
- Username: (existing doctor username)
- Password: (existing doctor password)

## Database Connection Test

The application will test the database connection on first access. If you see an error:

1. Check credentials in `config/database.php`
2. Verify MySQL is running:
   ```bash
   mysql -u silverwebbuzz_in_drfeelgoodsapp -p
   ```
3. Verify the database exists:
   ```
   SHOW DATABASES;
   ```

## Post-Deployment Testing

### Test Checklist

- [ ] **Login Page Loads** - Navigate to `/` and see login form
- [ ] **Database Connection** - Login successfully with doctor credentials
- [ ] **Dashboard Loads** - See dashboard with stats
- [ ] **Patient List Works** - View patient list with pagination
- [ ] **Patient Search** - Search for a patient by name
- [ ] **Patient Profile** - Click on a patient and view details
- [ ] **Logout Works** - Click logout and redirected to login
- [ ] **Session Timeout** - Verify auto-logout after 1 hour

### Test Patient Search

Try searching for an existing patient:
1. Go to Patients page
2. Type a patient name in search box
3. Should see results appear
4. Click a result to view profile

### Test Patient Profile

1. From patient list, click "View" on any patient
2. Verify all information loads:
   - Basic info (name, age, gender)
   - Contact details (address, phone)
   - Chief complaint
   - Health assessment
   - Progress reports (should have 605K+ records total)

## Troubleshooting

### Issue: Blank Page on Login

**Solution:**
1. Check Apache error logs:
   ```bash
   tail -f /var/log/apache2/error.log
   ```
2. Check PHP error logs
3. Verify database connection

### Issue: Patient Data Not Showing

**Solution:**
1. Verify database credentials
2. Test MySQL connection:
   ```bash
   mysql -h localhost -u silverwebbuzz_in_drfeelgoodsapp -p"Drfeel@app123" -e "SELECT COUNT(*) FROM patient;"
   ```
3. Check database permissions

### Issue: URLs Not Working (404 errors)

**Solution:**
1. Verify `.htaccess` exists in `public/`
2. Check mod_rewrite is enabled: `a2enmod rewrite`
3. Verify RewriteBase in `.htaccess` matches installation path
4. Restart Apache: `systemctl restart apache2`

### Issue: Search Not Working

**Solution:**
1. Check browser console for JavaScript errors
2. Verify API endpoint: `/api/patient/search`
3. Test with longer search term (minimum 2 characters)

## Next Steps After Deployment

1. **Test with Real Data** - Use actual patient names to verify search works
2. **Verify All Patient Records** - Ensure 8,312+ patients are accessible
3. **Check Progress Reports** - Click on a patient and verify treatment history loads
4. **Performance Test** - Check if pagination works smoothly
5. **Get Approval** - Have doctor test and approve functionality

## Once Verified Working

1. Make note of any issues encountered
2. Discuss with team about UI improvements
3. Plan first feature to implement (after Phase 1)
4. Schedule Phase 2 improvements

## Important Notes

⚠️ **DO NOT:**
- Modify old `/drFeelGood` folder yet
- Delete any database tables
- Change database structure
- Modify database credentials

✅ **DO:**
- Test thoroughly on VPS
- Keep old system running as backup
- Document any issues found
- Plan next features based on testing

## Support

If you encounter any issues:
1. Check the troubleshooting section above
2. Review the README.md for detailed documentation
3. Check Apache and PHP error logs
4. Verify all file permissions are correct

---

**App Ready for VPS Deployment!** 🚀
