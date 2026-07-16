<?php
/**
 * Application Constants
 */

// Debug Mode — OFF by default so stack traces are never shown in production.
// Enable locally by setting the APP_DEBUG environment variable to 1/true/on.
define('DEBUG_MODE', in_array(strtolower((string)getenv('APP_DEBUG')), ['1', 'true', 'on'], true));

// Application Settings
define('SITE_NAME', 'Homeopathy clinic demo');
define('SITE_URL', 'https://demo.eclinicpro.com/');
define('APP_VERSION', '2.0.0');

// Directories
define('APP_PATH', dirname(dirname(__FILE__)));
define('PUBLIC_PATH', APP_PATH . '/public');
define('STORAGE_PATH', APP_PATH . '/storage');

// Session Settings
define('SESSION_TIMEOUT', 86400); // 24 hours (full day) in seconds

// Pagination
define('ITEMS_PER_PAGE', 10);

// Date Format
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');

// API Response Codes
define('SUCCESS', 200);
define('CREATED', 201);
define('BAD_REQUEST', 400);
define('UNAUTHORIZED', 401);
define('NOT_FOUND', 404);
define('SERVER_ERROR', 500);

// User Roles
define('ROLE_DOCTOR', 'doctor');
define('ROLE_ADMIN', 'admin');
define('ROLE_STAFF', 'staff');
