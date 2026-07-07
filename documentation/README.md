# Dr. Feelgood - Clinic Management System v2.0

Modern, clean, and easy-to-use clinic management system for doctors and medical professionals.

## Features

✅ **Patient Management**
- Complete patient database with full medical history
- Quick search by name or contact
- Detailed patient profiles with assessment data
- Progress report tracking

✅ **Modern Interface**
- Clean, responsive design
- Works on desktop and mobile
- Easy navigation for doctors
- Real-time patient search

✅ **Data Preservation**
- All existing patient data preserved
- Complete treatment history (605K+ reports)
- Full backward compatibility
- Safe data migration

✅ **Authentication**
- Secure login system
- Session management
- Password hashing with bcrypt
- Automatic timeout protection

## System Requirements

- **PHP:** 8.3+ (tested with PHP 8.3.30)
- **Database:** MySQL 5.7+ or MariaDB
- **Web Server:** Apache with mod_rewrite enabled
- **Browser:** Modern browser (Chrome, Firefox, Safari, Edge)

## Installation

### Step 1: Upload Files to VPS

```bash
# Navigate to the hosting directory
cd /home/silverwebbuzz_in/public_html/drfeelgoods.in/

# Ensure app folder exists
mkdir -p app
cd app

# Copy all files from the app folder to this directory
```

### Step 2: Database Setup

The application uses the existing database structure. No migration needed.

**Database Credentials:**
```
Host: localhost
Database: silverwebbuzz_in_drfeelgoodsapp
User: silverwebbuzz_in_drfeelgoodsapp
Password: Drfeel@app123
```

### Step 3: File Permissions

```bash
# Set proper permissions
chmod 755 public/
chmod 755 storage/
chmod 644 public/index.php
chmod 644 public/.htaccess
```

### Step 4: Access the Application

```
URL: https://app.drfeelgoods.in/app/
Login: (use existing doctor credentials)
```

## Project Structure

```
app/
├── config/                 # Configuration files
│   ├── database.php       # Database connection settings
│   └── constants.php      # Application constants
├── src/                   # Application source code
│   ├── Models/            # Data models
│   │   ├── Database.php      # Base model class
│   │   ├── Patient.php       # Patient model
│   │   ├── ProgressReport.php # Report model
│   │   ├── User.php          # User/Doctor model
│   │   └── AdditionalInfo.php # Medical assessment model
│   └── Controllers/       # Business logic
│       ├── AuthController.php    # Login/Logout
│       └── PatientController.php # Patient operations
├── views/                 # HTML templates
│   ├── layout.php        # Main layout template
│   ├── dashboard.php     # Dashboard page
│   ├── auth/             # Authentication views
│   │   └── login.php
│   ├── patient/          # Patient views
│   │   ├── list.php
│   │   ├── detail.php
│   │   └── create.php
│   └── error/            # Error pages
│       └── 404.php
├── public/               # Web-accessible files
│   ├── index.php        # Main entry point
│   ├── .htaccess        # URL rewriting rules
│   └── assets/          # CSS, JS, images
├── storage/             # Uploads, logs
└── README.md           # This file
```

## Usage

### Login

1. Navigate to `https://app.drfeelgoods.in/app/`
2. Enter your doctor username and password
3. Click "Login"

### Dashboard

After login, you'll see:
- Quick stats (Total Patients, Reports, etc.)
- Recent patient list
- Quick action buttons

### Patient List

Click **Patients** in the sidebar to:
- View all patients with pagination
- Search patients by name or contact
- View detailed patient profiles

### Patient Profile

Click **View** on any patient to see:
- Basic information (name, DOB, contact, etc.)
- Health assessment data
- Physical examination details
- Complete progress report history
- Add new progress reports

### Add Patient

Click **Add Patient** to create a new patient record:
- Enter basic information
- Set chief complaint
- Medical history will be filled in later

## API Endpoints

The application includes REST API endpoints for advanced features:

```
GET    /api/patient/search?q=name    # Search patients
POST   /api/patient/{id}/report      # Add progress report
```

## Development

### Adding New Features

1. Create a new Model class in `src/Models/`
2. Create a new Controller in `src/Controllers/`
3. Create view files in `views/`
4. Add routes in `public/index.php`

### Database Changes

For new features requiring database changes:

1. Create SQL migration files in `migrations/`
2. Document the change
3. Test on replica database first
4. Deploy to production

## Troubleshooting

### Blank Page / 500 Error

1. Check PHP error logs
2. Verify database credentials in `config/database.php`
3. Ensure database user has SELECT/INSERT/UPDATE permissions
4. Check file permissions

### Database Connection Failed

1. Verify credentials:
   ```bash
   mysql -h localhost -u silverwebbuzz_in_drfeelgoodsapp -p
   ```
2. Check if database exists: `silverwebbuzz_in_drfeelgoodsapp`
3. Verify user can access the database

### URLs Not Working / 404 Errors

1. Verify `.htaccess` is in place: `public/.htaccess`
2. Check Apache `mod_rewrite` is enabled
3. Verify `RewriteBase` in `.htaccess` matches your installation path

## Database Schema

### Existing Tables

**patient**
- id: Primary key
- fname, lname: Name
- contact_no: Phone
- dob: Date of birth
- age: Age
- gender: M/F
- chief: Chief complaint
- And more...

**additional_info**
- id: Primary key
- p_id: Foreign key to patient
- 150+ fields for medical assessment
- Health history, family history, etc.

**progress_report**
- id: Primary key
- p_id: Foreign key to patient
- date: Report date
- medicins: Medicines prescribed
- amt: Amount

**user**
- id: Primary key
- fname, mname, lname: Name
- username: Login username
- password: Hashed password
- email, contact_no, address, etc.

## Security

✅ **Features**
- Password hashing with bcrypt
- Session-based authentication
- SQL injection prevention (prepared statements)
- XSS protection (output encoding)
- CSRF token support ready
- Automatic session timeout

## Performance

- Optimized database queries
- Pagination for large datasets
- CSS/JS minification
- Clean architecture for scalability

## Future Enhancements

Planned features for Phase 2:
- Appointment scheduling
- Prescription management
- Billing/invoice system
- Email notifications
- Advanced reporting
- Mobile app integration
- Multi-language support

## Support & Documentation

For issues or questions:
1. Check the troubleshooting section above
2. Review application logs
3. Contact system administrator

## Version History

**v2.0.0** (2026-04-08)
- Complete modernization of legacy system
- New PHP 8.3 architecture
- Modern UI with Bootstrap 5
- All existing data preserved

**v1.0.0** (Legacy)
- Original PHP system
- jQuery-based interface
- Functional but outdated

## License

Private - Dr. Feelgood Clinic

## Credits

Built with:
- PHP 8.3
- Bootstrap 5
- MySQL Database
- Font Awesome Icons

---

**Last Updated:** 2026-04-08
**Maintained By:** Development Team
**Contact:** support@drfeelgoods.in
