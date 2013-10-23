<?php

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/DBBase.php';

class User {

    const SESSION_TIMEOUT = 1800; // The login last for 30 minutes (30 * 60) between calls
    const SESSION_KEY = 'finpe_loggedin_user';
    const COOKIE_KEY = 'finpe_remember_me';

    static public $last_error_message = '';

    public static function isSuperAdmin() {
        return (
                isset($_SESSION[self::SESSION_KEY]) &&
                isset($_SESSION[self::SESSION_KEY]['user']) &&
                isset($_SESSION[self::SESSION_KEY]['user']['super_admin']) &&
                $_SESSION[self::SESSION_KEY]['user']['super_admin']
                );
    }

    public static function isSessionValid($verifiy_session_timeout = true) {
        self::$last_error_message = 'Área de acesso reservado a membros';
        $is_valid = true;

        if (!isset($_SESSION[self::SESSION_KEY]) || empty($_SESSION[self::SESSION_KEY])) {
            $is_valid = false;
        }

        if ($is_valid && $verifiy_session_timeout) {
            // Check if the SESSION_TIMEOUT was reached
            if ((strtotime(date('Y-m-d H:i:s')) - strtotime($_SESSION[self::SESSION_KEY]['timer'])) < self::SESSION_TIMEOUT) {
                self::renewSession();
            } else {
                self::$last_error_message = 'A sessão expirou';
                $is_valid = false;
            }
        }

        if (!$is_valid) {
            // Not logged in. Check if user has remember me on
            if (isset($_COOKIE[self::COOKIE_KEY])) {
                $is_valid = self::loginWithHash($_COOKIE[self::COOKIE_KEY]);
            }
        }

        if ($is_valid && $_SESSION[self::SESSION_KEY]['user']['hidden']) {
            self::$last_error_message = 'Este utilizador não está activo';
            $is_valid = false;
        }

        if ($is_valid && $_SESSION[self::SESSION_KEY]['user']['validated'] == null) {
            self::$last_error_message = 'Ainda não validou o seu email. Clique em recuperar conta para receber um novo link de validação';
            $is_valid = false;
        }

        return $is_valid;
    }

    public static function renewSession() {
        if (self::isSessionValid(false)) {
            $_SESSION[self::SESSION_KEY]['timer'] = date('Y-m-d H:i:s'); // Reset the coutdown to the next login
            return true;
        }
        return false;
    }

    public static function getAccountInUse() {
        $user_info = User::getLoggedInUser();
        if ($user_info && isset($user_info['id_user']) && trim($user_info['id_user']) != '') {

            $id_user = $user_info['id_user'];

            // NOTE: Enquanto não arranjar outra forma de escolher a conta, usa a primeira cujo o role seja owner
            $query = "
                SELECT a.id_account 
                FROM account a 
                    INNER JOIN account_user au ON a.id_account = au.id_account 
                WHERE au.id_user = '$id_user' AND au.role = 'owner'";
            $resource = DB::execute($query);
            $account_info = DB::toArray($resource);

            if ($account_info && isset($account_info['id_account']) && trim($account_info['id_account']) != '') {
                return $account_info['id_account'];
            }
        }
        return false;
    }

    public static function getLoggedInUser() {

        if (self::isSessionValid()) {

            if (self::isSuperAdmin()) {
                // Logged in user is a super_admin
                $user_info = $_SESSION[self::SESSION_KEY]['user'];
            } else {
                // Normal user. Get info from DB
                $user_info = DB::getInfoUser($_SESSION[self::SESSION_KEY]['user']['id_user']);
            }

            // Only accepts login from users that are NOT hidden
            if (!$user_info['hidden']) {
                $_SESSION[self::SESSION_KEY]['user'] = $user_info;

                // Check if the SESSION_TIMEOUT was reached
                if ((strtotime(date('Y-m-d H:i:s')) - strtotime($_SESSION[self::SESSION_KEY]['timer'])) < self::SESSION_TIMEOUT) {
                    self::renewSession();

                    // Success
                    return $_SESSION[self::SESSION_KEY]['user'];
                }
            }
        }

        return false;
    }

    public static function checkUserPassword($check_password, $in_plain_text = true) {

        if (self::isSessionValid()) {

            if (self::isSuperAdmin()) {
                // Logged in user is a super_admin
                if ($in_plain_text) {
                    return ($check_password === SUPER_ADMIN_PASSWD);
                } else {
                    return ($check_password === hash('sha256', SUPER_ADMIN_PASSWD));
                }
            } else {
                // Normal user. Get info from DB
                $user_info = DB::getInfoUser($_SESSION[self::SESSION_KEY]['user']['id_user']);
                if ($user_info && $user_info['password']) {
                    if ($in_plain_text) {
                        $check_password = hash('sha256', $check_password);
                    }
                    return ($check_password === $user_info['password']);
                }
            }
        }

        return false;
    }

    public static function login($try_username, $try_password, $remember_me, $hash_pass = true) {

        // Checks if is the SUPER ADMIN
        if ($try_username == htmlentities(SUPER_ADMIN_USER, ENT_COMPAT, 'UTF-8') &&
                ($try_password == htmlentities(SUPER_ADMIN_PASSWD, ENT_COMPAT, 'UTF-8') || (!$hash_pass && $try_password == hash('sha256', SUPER_ADMIN_PASSWD)))) {

            $user_info = array(
                'super_admin' => true,
                'id_user' => null,
                'username' => SUPER_ADMIN_USER,
                'password' => hash('sha256', SUPER_ADMIN_PASSWD),
                'force_change_password' => 0,
                'validated' => 1,
                'profile_image' => null,
                'name' => 'Administrator',
                'email' => SUPER_ADMIN_EMAIL,
                'hidden' => 0,
                'last_update' => null,
                'insert_date' => null
            );
            $_SESSION[self::SESSION_KEY] = array(
                'user' => $user_info,
                'timer' => ''
            );

            // Remove remember me when is SUPER_ADMIN
            setcookie(self::COOKIE_KEY, '', time() - 3600); // Cookie expired

            self::renewSession();
            return true;
        }

        $login = DB::getInfoUserByUsername($try_username);
        $hashed_password = $try_password;
        if ($hash_pass) {
            $hashed_password = hash('sha256', $try_password);
        }

        if ($login && strtolower($login['username']) === strtolower($try_username) && $login['password'] === $hashed_password) {

            if ($login['hidden']) {
                self::logout();
                return 'Este utilizador não está activo';
            }

            if ($login['validated'] == null) {
                self::logout();
                return 'Ainda não validou o seu email. Clique em recuperar conta para receber um novo link de validação';
            }

            $_SESSION[self::SESSION_KEY] = array(
                'user' => $login,
                'timer' => ''
            );

            // Remember login in this computer
            if ($remember_me) {
                $remember_me_hash = self::generateHash_RememberMe($login['id_user']);
                setcookie(self::COOKIE_KEY, $remember_me_hash, time() + 7 * 24 * 60 * 60); // valid for 7 days
            }

            self::renewSession();
            return true;
        }

        self::logout();
        return 'Invalid username or password';
    }

    public static function loginWithHash($login_hash) {
        // Split the user from the hash
        $pos = strpos($login_hash, '&');
        if ($pos !== FALSE && $login_hash[$pos] == '&') {
            $id_user = substr($login_hash, 0, $pos);
            $token = substr($login_hash, $pos + 1);

            if (DB::validateRememberMeToken($id_user, $token)) {
                $user_info = DB::getInfoUser($id_user);
                if ($user_info) {
                    // Login valid
                    return (self::login($user_info['username'], $user_info['password'], false, false) === TRUE);
                }
            }
        }

        return false;
    }

    public static function logout($destroy_session = true) {
        $_SESSION[self::SESSION_KEY] = false;
        setcookie(self::COOKIE_KEY, '', time() - 60000); // Cancel remember me
        unset($_SESSION[self::SESSION_KEY]);
        if (session_id() != '' && $destroy_session) {
            session_destroy();
        }
    }

    protected static function generateHash_RememberMe($id_user) {
        list(, $token) = User::generateToken();
        $hash_value = $id_user . '&' . $token;

        DB::insertRememberMeToken($id_user, $token);

        return $hash_value;
    }

    public static function changePassword($old_password, $new_password, $repeat_password, $force_new_different_old = true, $hash_pass = true) {

        if (self::isSuperAdmin()) {
            return "Can't change password for superadmin";
        }


        if ($force_new_different_old && $old_password == $new_password) {
            return 'The new password must be different from the old one';
        }
        if (trim($new_password) == '' || trim($repeat_password) == '') {
            return 'The new password cannot be empty';
        }
        if ($new_password !== $repeat_password) {
            return 'The new password is different from the repeated one';
        }

        if (self::isSessionValid()) {

            $hashed_password_actual = $old_password;
            if ($hash_pass) {
                $hashed_password_actual = hash('sha256', $old_password);
            }

            $user_info = DB::getInfoUser($_SESSION[self::SESSION_KEY]['user']['id_user']);

            if (!$user_info['hidden']) {
                if ($user_info['password'] != $hashed_password_actual) {
                    return 'The old password is incorrect';
                }

                $hashed_password_nova = hash('sha256', $new_password);
                $user_info = array(
                    'password' => $hashed_password_nova,
                    'force_change_password' => 0
                );

                if (DB::saveUser($user_info, $_SESSION[self::SESSION_KEY]['user']['id_user']) !== FALSE) {
                    return true;
                } else {
                    return 'An error has occured while changing your password. ERROR: ' . DB::$last_error_message;
                }
            }
        }

        return 'Unkown error';
    }

    public static function createAccountRecoveryToken($user_email) {
        list(, $token) = User::generateToken();

        if (DB::insertAccountRecoveryToken($user_email, $token)) {
            return true;
        }

        return false;
    }

    public static function validateAccountRecoveryToken($user_email, $token) {

        return DB::validateAccountRecoveryToken($user_email, $token);
    }

    public static function removeAccountRecoveryToken($id_user) {

        return DB::removeAccountRecoveryToken($id_user);
    }

    public static function generateToken() {
        $number = rand();
        $token = hash('sha256', $number);

        return array($number, $token);
    }

}

?>