<?php

define('SUPER_ADMIN_USER', 'my_username');
define('SUPER_ADMIN_PASSWD', 'my_password');
define('SUPER_ADMIN_EMAIL', 'my_email');

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/Email.php';
require_once __DIR__ . '/File.php';
require_once __DIR__ . '/LangDB.php';
require_once __DIR__ . '/LangXML.php';
require_once __DIR__ . '/Log.php';
require_once __DIR__ . '/URL.php';
require_once __DIR__ . '/User.php';


session_start();

date_default_timezone_set('Europe/Lisbon');

// Check if the site is online or offline
if (in_array($_SERVER['SERVER_ADDR'], array("127.0.0.1", "::1")) || preg_match('/^192\.168\..+\..+/', $_SERVER['SERVER_ADDR'])) { // ::1 -> IPv6
    define('OFFLINE_MODE', true);
} else {
    define('OFFLINE_MODE', false);
}

define('USE_DATABASE', true);

// Init Database
if (!OFFLINE_MODE) {
    error_reporting(E_ALL);
    $host = 'localhost';
    $user = 'financaspessoais';
    $pass = 'financaspessoais';
    $db = 'financaspessoais';
} else {
    error_reporting(E_ALL);
    $host = '127.0.0.1';
    $user = 'root';
    $pass = '';
    $db = 'financaspessoais';
}
$charset = 'utf8';
DB::init($host, $user, $pass, $db, $charset);


// Configure email credentials
define('USE_EMAIL', TRUE);
define('SEND_MAIL_SECRET_KEY', 'some_random_key');
define('CONTACT_EMAIL_ADDRESS', SUPER_ADMIN_EMAIL);
define('EMAIL_SENDER', 'email@email.com'); // Example: user@server.com (GMail: your_email_address)
define('EMAIL_HOST', ''); // Example: mail.server.com (GMail: smtp.gmail.com)
define('EMAIL_PORT', 465); // Default: 25 (GMail: 465)
define('EMAIL_USE_CREDENTIALS', true); // Use the EMAIL_USERNAME and EMAIL_PASSWORD in the host
define('EMAIL_USERNAME', ''); // Example: user@server.com (GMail: your_email_address)
define('EMAIL_PASSWORD', ''); // Your password in plain text
define('EMAIL_SECURITY', 'ssl'); // Encryption type can be null, 'tls' or 'ssl' (GMail: 'ssl')
define('EMAIL_CONTENT_TYPE', 'text/html'); // HTML: 'text/html' Text: 'text/plain'
define('EMAIL_CHARSET', 'utf-8'); // Example: 'utf-8'



LangBase::updateCurrentLang();



// Check if the user is in a members-only area
// Members-only pages: 
//       + starting with data-
//       + starting with account (except account-recovery)
//       + starting with profile
//       + starting with member
$members_only_area = (preg_match('/^data-|^account|^profile|^member/', basename($_SERVER['PHP_SELF'])) && !in_array(basename($_SERVER['PHP_SELF']), array('account-recovery.php')));

if ($members_only_area) {
    $is_session_valid = User::isSessionValid();
    if (!$is_session_valid &&
            empty($_POST) &&
            basename($_SERVER['PHP_SELF']) != 'ajax_functions.php' &&
            basename($_SERVER['PHP_SELF']) != 'login.php' &&
            basename($_SERVER['PHP_SELF']) != 'signup.php' &&
            basename($_SERVER['PHP_SELF']) != 'validate.php' &&
            basename($_SERVER['PHP_SELF']) != 'account-recovery.php') {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']) . '&message=' . urlencode(User::$last_error_message));
        exit;
    } else if ($is_session_valid) {
        User::renewSession();
    }
}
?>