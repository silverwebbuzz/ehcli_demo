# VPS Deployment Guide - Dr. Feelgood

## Quick Summary

This guide covers deploying the Dr. Feelgood application to your VPS at:
- **Domain:** https://app.drfeelgoods.in/
- **VPS Path:** /home/silverwebbuzz_in/public_html/drfeelgoods.in/
- **Database:** silverwebbuzz_in_drfeelgoodsapp

---

## Prerequisites

✅ VPS hosting with cPanel/WHM or SSH access  
✅ PHP 8.3+ installed  
✅ MySQL/MariaDB running  
✅ Apache with mod_rewrite enabled  
✅ SSL certificate for https://  

---

## Step-by-Step Deployment

### Step 1: Upload Files to VPS (20 minutes)

#### Option A: Using cPanel File Manager
1. Log in to cPanel
2. Navigate to: **File Manager** → `/home/silverwebbuzz_in/public_html/drfeelgoods.in/`
3. Upload the application files:
   - Upload all folders: `config/`, `src/`, `public/`, `views/`, `documentation/`
   - Upload `.htaccess` at root
   - Upload `.gitignore`

#### Option B: Using SFTP
```bash
# From your local machine
sftp silverwebbuzz_in@your-vps-ip

# Navigate to the directory
cd public_html/drfeelgoods.in

# Upload all files (recursively)
put -r config
put -r src
put -r public
put -r views
put -r documentation
put .htaccess
put .gitignore
```

#### Option C: Using Git (Recommended)
```bash
# SSH into VPS
ssh root@your-vps-ip

# Navigate to the directory
cd /home/silverwebbuzz_in/public_html/drfeelgoods.in

# Clone the repository
git clone https://github.com/yourusername/drfeelgoodapp.git .
```

---

### Step 2: Set File Permissions (5 minutes)

```bash
# SSH into VPS
ssh root@your-vps-ip

# Navigate to the directory
cd /home/silverwebbuzz_in/public_html/drfeelgoods.in

# Set proper permissions
chmod 755 .
chmod 755 config/
chmod 755 src/
chmod 755 public/
chmod 755 views/
chmod 755 documentation/

chmod 644 .htaccess
chmod 644 public/.htaccess
chmod 644 .gitignore

chmod 644 config/database.php
chmod 644 config/constants.php
chmod 644 public/index.php

# Make storage directory writable (if created)
mkdir -p storage
chmod 755 storage
```

---

### Step 3: Verify Apache Configuration (5 minutes)

#### Check mod_rewrite is enabled:
```bash
# SSH into VPS
ssh root@your-vps-ip

# Check if mod_rewrite is enabled
apache2ctl -M | grep rewrite

# If not enabled, enable it:
a2enmod rewrite

# Restart Apache
systemctl restart apache2
```

#### Verify virtual host allows .htaccess overrides:
```bash
# Check virtual host config
# Usually at: /etc/apache2/sites-available/ or /etc/httpd/conf.d/

# Look for the drfeelgoods.in virtual host
# Make sure it has: AllowOverride All
```

---

### Step 4: Configure Virtual Host (if needed)

If the virtual host doesn't exist, create it:

```bash
# SSH into VPS
ssh root@your-vps-ip

# Create virtual host config file
nano /etc/apache2/sites-available/app-drfeelgoods.conf
```

Paste this configuration:
```apache
<VirtualHost *:80>
    ServerName app.drfeelgoods.in
    ServerAdmin admin@drfeelgoods.in
    DocumentRoot /home/silverwebbuzz_in/public_html/drfeelgoods.in

    <Directory /home/silverwebbuzz_in/public_html/drfeelgoods.in>
        AllowOverride All
        Require all granted
    </Directory>

    # Enable mod_rewrite
    <IfModule mod_rewrite.c>
        RewriteEngine On
    </IfModule>

    ErrorLog ${APACHE_LOG_DIR}/app-drfeelgoods_error.log
    CustomLog ${APACHE_LOG_DIR}/app-drfeelgoods_access.log combined
</VirtualHost>

# HTTPS configuration (after SSL setup)
<VirtualHost *:443>
    ServerName app.drfeelgoods.in
    ServerAdmin admin@drfeelgoods.in
    DocumentRoot /home/silverwebbuzz_in/public_html/drfeelgoods.in

    <Directory /home/silverwebbuzz_in/public_html/drfeelgoods.in>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key

    ErrorLog ${APACHE_LOG_DIR}/app-drfeelgoods_error.log
    CustomLog ${APACHE_LOG_DIR}/app-drfeelgoods_access.log combined
</VirtualHost>
```

Enable the site:
```bash
a2ensite app-drfeelgoods.conf
systemctl reload apache2
```

---

### Step 5: Verify Database Connection (5 minutes)

Test the database credentials:
```bash
# SSH into VPS
ssh root@your-vps-ip

# Test MySQL connection
mysql -h localhost -u silverwebbuzz_in_drfeelgoodsapp -p

# When prompted, enter: Drfeel@app123

# If connected, check the database exists:
mysql> SHOW DATABASES;
mysql> exit;
```

---

### Step 6: Test the Application (10 minutes)

#### Test 1: Check if application loads
```
Visit: https://app.drfeelgoods.in/
Expected: Login page should load
```

#### Test 2: Test login
```
Enter doctor credentials
Expected: Dashboard should load
```

#### Test 3: Test patient list
```
Click "Patients" in sidebar
Expected: Patient list with pagination should show
```

#### Test 4: Test patient search
```
Type patient name in search box
Expected: Results should appear instantly
```

#### Test 5: Test patient profile
```
Click "View" on a patient
Expected: Patient profile with complete history should load
```

---

## Troubleshooting

### Issue: 404 Error on Root URL

**Symptoms:** Visiting https://app.drfeelgoods.in/ shows 404 error

**Solutions:**
1. Verify `.htaccess` exists at root:
   ```bash
   ls -la /home/silverwebbuzz_in/public_html/drfeelgoods.in/.htaccess
   ```

2. Check .htaccess has correct permissions:
   ```bash
   chmod 644 /home/silverwebbuzz_in/public_html/drfeelgoods.in/.htaccess
   ```

3. Verify Apache mod_rewrite is enabled:
   ```bash
   apache2ctl -M | grep rewrite
   ```

4. Check Apache error logs:
   ```bash
   tail -f /var/log/apache2/error.log
   ```

5. Restart Apache:
   ```bash
   systemctl restart apache2
   ```

### Issue: Blank Page / 500 Error

**Symptoms:** Page loads but shows blank or 500 error

**Solutions:**
1. Check PHP error logs:
   ```bash
   tail -f /var/log/apache2/error.log
   ```

2. Enable PHP error reporting in `config/database.php`

3. Verify database connection:
   ```bash
   mysql -h localhost -u silverwebbuzz_in_drfeelgoodsapp -p"Drfeel@app123"
   ```

4. Check file permissions are correct (755 for dirs, 644 for files)

5. Verify `public/index.php` exists and is readable:
   ```bash
   ls -la /home/silverwebbuzz_in/public_html/drfeelgoods.in/public/index.php
   ```

### Issue: Database Connection Failed

**Symptoms:** Error message about database connection

**Solutions:**
1. Verify credentials in `config/database.php`:
   ```
   Host: localhost
   Database: silverwebbuzz_in_drfeelgoodsapp
   User: silverwebbuzz_in_drfeelgoodsapp
   Password: Drfeel@app123
   ```

2. Test MySQL connection directly:
   ```bash
   mysql -h localhost -u silverwebbuzz_in_drfeelgoodsapp -p"Drfeel@app123" -e "SELECT COUNT(*) FROM silverwebbuzz_in_drfeelgoodsapp.patient;"
   ```

3. Check user has proper permissions:
   ```bash
   mysql -u root -p
   mysql> GRANT ALL ON silverwebbuzz_in_drfeelgoodsapp.* TO 'silverwebbuzz_in_drfeelgoodsapp'@'localhost';
   mysql> FLUSH PRIVILEGES;
   mysql> exit;
   ```

### Issue: CSS/JS/Images Not Loading

**Symptoms:** Page loads but styling/scripts don't work

**Solutions:**
1. Check file permissions:
   ```bash
   chmod 644 /home/silverwebbuzz_in/public_html/drfeelgoods.in/public/*
   ```

2. Check Apache access logs:
   ```bash
   tail -f /var/log/apache2/access.log
   ```

3. Verify rewrite rules aren't blocking static files

4. Check MIME types are set correctly in `.htaccess`

---

## Post-Deployment Checklist

- [ ] Application loads at https://app.drfeelgoods.in/
- [ ] Login page displays correctly
- [ ] Can login with doctor credentials
- [ ] Dashboard shows stats and recent patients
- [ ] Patient list loads with pagination
- [ ] Patient search works
- [ ] Patient profile displays complete history
- [ ] CSS and styling renders properly
- [ ] JavaScript functionality works
- [ ] Mobile view is responsive
- [ ] No errors in browser console
- [ ] No errors in Apache error logs
- [ ] Database connection is stable

---

## Performance Optimization (Optional)

### Enable Gzip Compression
Add to `.htaccess`:
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

### Enable Browser Caching
Add to `.htaccess`:
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css A2592000
    ExpiresByType application/javascript A2592000
    ExpiresByType image/jpeg A2592000
    ExpiresByType image/png A2592000
    ExpiresByType image/gif A2592000
</IfModule>
```

---

## Security Recommendations

✅ Use HTTPS (SSL certificate)  
✅ Disable directory listing (already in .htaccess)  
✅ Protect sensitive files (.env, config)  
✅ Regular backups of database  
✅ Keep PHP and dependencies updated  
✅ Monitor error logs regularly  
✅ Use strong passwords for database  
✅ Keep database credentials secure  

---

## Support

For issues not covered here:

1. Check Apache error logs: `/var/log/apache2/error.log`
2. Check access logs: `/var/log/apache2/access.log`
3. Check PHP error logs: `php -i | grep error_log`
4. Contact your hosting provider for VPS-specific issues
5. Refer to documentation in `/documentation/` folder

---

**Deployment completed successfully!** 🚀

Next steps:
1. Doctor tests the application
2. Get approval on functionality
3. Plan Phase 2 features
