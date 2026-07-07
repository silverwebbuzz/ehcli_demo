# Deployment Checklist - Dr. Feelgood App

## Ready for VPS Deployment ✅

All files are committed to GitHub and ready to deploy to production.

---

## Step 1: Deploy Files to VPS

Copy all files from `/Users/apple/Silverwebbuzz/drfeelgoodapp` to `/home/silverwebbuzz_in/public_html/drfeelgoods.in/app/` on your VPS.

**Using Git (Recommended):**
```bash
cd /home/silverwebbuzz_in/public_html/drfeelgoods.in/app
git clone https://github.com/silverwebbuzz/drfeelgoodapp.git .
```

Or pull latest changes if already cloned:
```bash
cd /home/silverwebbuzz_in/public_html/drfeelgoods.in/app
git pull origin main
```

---

## Step 2: Verify Deployment

### Check Files Exist
```bash
ls -la /home/silverwebbuzz_in/public_html/drfeelgoods.in/app/
```

Should show:
- `index.php` ✅
- `config/` directory
- `src/` directory
- `views/` directory
- `documentation/` directory
- `debug.php` (for testing only)

### Check File Permissions
```bash
chmod 755 /home/silverwebbuzz_in/public_html/drfeelgoods.in/app
chmod 644 /home/silverwebbuzz_in/public_html/drfeelgoods.in/app/index.php
```

---

## Step 3: Test Login

1. **Open Browser:** https://app.drfeelgoods.in/
2. **Expected:** Login page displays
3. **Enter Credentials:**
   - Username: `mitesh`
   - Password: `feelgood`
4. **Expected:** Dashboard loads with stats and recent patients

---

## Step 4: Test Core Features

After successful login:

- [ ] **Dashboard loads** - Shows stats and recent patients
- [ ] **Patient List** - Click "Patients", see paginated list (10 per page)
- [ ] **Patient Search** - Type patient name in search box, results appear instantly
- [ ] **Patient Profile** - Click "View" on a patient, see full profile
- [ ] **Logout** - Click logout, redirected to login page

---

## Step 5: Cleanup (After Testing)

Once everything works, remove the debug script:
```bash
rm /home/silverwebbuzz_in/public_html/drfeelgoods.in/app/debug.php
```

---

## If Login Fails

Check Apache error logs:
```bash
tail -50 /var/log/apache2/error.log
```

Check if index.php is being accessed:
```bash
curl https://app.drfeelgoods.in/ -I
```

Test database connection directly:
```bash
mysql -h localhost -u silverwebbuzz_in_drfeelgoodsapp -p"Drfeel@app123" -e "SELECT COUNT(*) FROM silverwebbuzz_in_drfeelgoodsapp.user;"
```

---

## Key Notes

- **DocumentRoot:** `/home/silverwebbuzz_in/public_html/drfeelgoods.in/app/`
- **Entry Point:** `index.php` at root level (not in public folder)
- **Database:** Uses existing tables (backward compatible)
- **Passwords:** Supports both plain text (legacy) and bcrypt hashed passwords
- **Session Timeout:** 1 hour of inactivity
- **No .htaccess needed:** Apache finds index.php automatically

---

**Status:** Ready for production deployment 🚀
