<?php

class DBBase {

    const CREATE_BASE_QUERY = '
CREATE TABLE IF NOT EXISTS `_user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `super_admin` tinyint(1) NOT NULL DEFAULT \'0\',
  `username` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `force_change_password` tinyint(1) NOT NULL DEFAULT \'0\',
  `validated` datetime DEFAULT NULL,
  `profile_image` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT \'0\',
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `_lang_copy` (
  `lang` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `copy` text COLLATE utf8_unicode_ci NOT NULL,
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`lang`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `_pending_emails` (
  `id_pending_email` int(11) NOT NULL AUTO_INCREMENT,
  `email_subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email_from` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email_to` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email_body` text COLLATE utf8_unicode_ci NOT NULL,
  `email_cc` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_bcc` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_replyTo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contentType` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'text/html\',
  `charset` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'utf-8\',
  `valid_until` datetime DEFAULT NULL,
  `sent_successfully` bit(1) NOT NULL DEFAULT b\'0\',
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pending_email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `_token` (
  `id_user` int(11) NOT NULL,
  `objective` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `token` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `valid_until` datetime DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`,`objective`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `_logs` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `log_data` text COLLATE utf8_unicode_ci NOT NULL,
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `_statistics` (
  `id_statistics` int(11) NOT NULL AUTO_INCREMENT,
  `hashed_columns` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `ip_address` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `request_url` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `statistics_data` text COLLATE utf8_unicode_ci NOT NULL,
  `num_pageviews` int(11) NOT NULL DEFAULT \'1\',
  `last_update` timestamp NULL DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_statistics`),
  UNIQUE KEY `hashed_columns` (`hashed_columns`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

';

    static protected $connection = null;
    static public $last_inserted_id = null;
    static public $last_error_message = '';

    public function __construct() {
        
    }

    static public function turnOnAutoCommit() {
        self::execute('SET AUTOCOMMIT=1');
    }

    static public function turnOffAutoCommit() {
        self::execute('SET AUTOCOMMIT=0');
    }

    static public function startTransaction($handleAutoCommit = true) {
        if ($handleAutoCommit) {
            self::turnOffAutoCommit();
        }

        self::execute('START TRANSACTION');
    }

    static public function commitTransaction($handleAutoCommit = true) {
        self::execute('COMMIT');

        if ($handleAutoCommit) {
            self::turnOnAutoCommit();
        }
    }

    static public function rollbackTransaction($handleAutoCommit = true) {
        self::execute('ROLLBACK');

        if ($handleAutoCommit) {
            self::turnOnAutoCommit();
        }
    }

    static public function createBaseTables() {

        $errors = 0;
        $counter = 0;
        $results = array();
        $queries = explode(';', self::CREATE_BASE_QUERY);

        foreach ($queries as $query) {
            self::$last_error_message = '';
            if (trim($query) != '') {
                $result = self::execute($query);
                if ($result === FALSE) {
                    $errors++;
                }
                $counter++;
                $results[] = array(
                    'query' => $query,
                    'result' => $result,
                    'last_error' => self::$last_error_message
                );
            }
        }

        $results[] = array(
            'counter' => $counter,
            'errors' => $errors
        );

        return $results;
    }

    static protected function getConnection() {

        if (self::isOpen()) {
            return self::$connection;
        } else if (USE_DATABASE) {
            self::$last_error_message = 'Database not initialized:  Need to call init() once before call getConnection()';
            return false;
        }
    }

    static public function init($host, $user, $pass, $db, $charset) {

        if (self::isOpen()) {
            self::close();
        }


        $connection = mysql_connect($host, $user, $pass);
        if (!$connection) {
            self::$last_error_message = 'Could not connect: ' . mysql_error();
            return false;
        }

        $db_selected = mysql_select_db($db);
        if (!$db_selected) {
            self::$last_error_message = 'Database access error : ' . mysql_error();
            return false;
        }

        if (!mysql_set_charset($charset, $connection)) {
            self::$last_error_message = 'Error setting charset "' . $charset . '" (' . mysql_error() . ') ';
            return false;
        }

        self::$connection = $connection;

        return true;
    }

    static public function isOpen() {
        return (isset(self::$connection) && self::$connection != null);
    }

    static public function close() {
        if (self::isOpen()) {
            /* close connection */
            mysql_close(self::$connection);
            self::$connection = null;
        }
    }

    static public function execute($query) {
        if (USE_DATABASE) {
            if (!self::isOpen()) {
                self::$last_error_message = 'Database not initialized:  Need to call init() once before call execute()';
                return false;
            }

            // Perform Query
            $resource = mysql_query($query, self::$connection);
            self::$last_inserted_id = mysql_insert_id();

            // Check result
            if (!$resource) {
                self::$last_error_message = '<p><strong>Invalid query:</strong> "' . mysql_error() . '"</p><br /><p><strong>Whole query:</strong> "' . $query . '"</p>';
                return false;
            }

            return $resource;
        }

        return false;
    }

    static public function insert($tablename, $dataToInsert) {

        if (USE_DATABASE) {
            if (!self::isOpen()) {
                self::$last_error_message = 'Database not initialized:  Need to call init() once before call insert()';
                return false;
            }

            if (!self::isTableCreated($tablename)) {
                self::$last_error_message = 'First parameter (' . $tablename . ') is not a table in the database in insert()';
                return false;
            }

            if (!is_array($dataToInsert)) {
                self::$last_error_message = 'Second parameter (' . $dataToInsert . ') must be a array in insert()';
                return false;
            }

            $fields = '';
            $values = '';
            foreach ($dataToInsert as $key => $value) {
                $fields .= $key . ",";
                if ($value === NULL) {
                    $values .= "NULL,";
                } else if ($value === TRUE) {
                    $values .= "TRUE,";
                } else if ($value === FALSE) {
                    $values .= "FALSE,";
                } else {
                    $values .= "'" . mysql_real_escape_string($value) . "',";
                }
            }
            $fields = rtrim($fields, ',');
            $values = rtrim($values, ',');

            $query = "INSERT INTO " . $tablename . " (" . $fields . ") VALUES (" . $values . ");";

            $resource = self::execute($query);

            return $resource;
        }

        return false;
    }

    static public function update($tablename, $dataToUpdate, $whereClause) {

        if (USE_DATABASE) {
            if (!self::isOpen()) {
                self::$last_error_message = 'Database not initialized:  Need to call init() once before call update()';
                return false;
            }

            if (!self::isTableCreated($tablename)) {
                self::$last_error_message = 'First parameter (' . $tablename . ') is not a table in the database in update()';
                return false;
            }

            if (!is_array($dataToUpdate)) {

                self::$last_error_message = 'Second parameter (' . $dataToUpdate . ') must be a array in update()';
                return false;
            }

            if (!is_string($whereClause)) {

                self::$last_error_message = 'Third parameter (' . $whereClause . ') must be a string in update()';
                return false;
            }


            $fields = '';
            foreach ($dataToUpdate as $key => $value) {
                if ($value === NULL) {
                    $fields .= $key . " = NULL,";
                } else if ($value === TRUE) {
                    $fields .= $key . " = TRUE,";
                } else if ($value === FALSE) {
                    $fields .= $key . " = FALSE,";
                } else {
                    $fields .= $key . " = '" . mysql_real_escape_string($value) . "',";
                }
            }

            $fields .= 'last_update = CURRENT_TIMESTAMP';

            $query = "UPDATE " . $tablename . " SET " . $fields . " WHERE " . $whereClause;

            $resource = self::execute($query);

            return $resource;
        }

        return false;
    }

    static public function delete($tablename, $whereClause) {

        if (USE_DATABASE) {
            if (!self::isOpen()) {
                self::$last_error_message = 'Database not initialized:  Need to call init() once before call delete()';
                return false;
            }

            if (!self::isTableCreated($tablename)) {
                self::$last_error_message = 'First parameter (' . $tablename . ') is not a table in the database in delete()';
                return false;
            }

            if (!is_string($whereClause)) {

                self::$last_error_message = 'Third parameter (' . $whereClause . ') must be a string in delete()';
                return false;
            }

            $query = "DELETE FROM $tablename WHERE $whereClause ";

            $resource = self::execute($query);

            return !($resource === false);
        }

        return false;
    }

    static public function free($resource) {
        // Free the resources associated with the result set
        // This is done automatically at the end of the script
        if (is_resource($resource)) {
            mysql_free_result($resource);
        }
    }

    static public function toArray($resource) {

        if (is_resource($resource)) {
            $result = array();

            // Use result
            // One of the mysql result functions must be used
            // See also mysql_result(), mysql_fetch_array(), mysql_fetch_row(), etc.

            $num_rows = mysql_num_rows($resource);
            if ($num_rows == 1) {
                // Returns an uni-dimensional array
                $row = mysql_fetch_assoc($resource);
                $result = array();
                foreach ($row as $key => $value) {
                    $result[$key] = $value;
                }
            } else if ($num_rows > 1) {
                // Returns a multi-dimensional
                while ($row = mysql_fetch_assoc($resource)) {
                    $result[] = array();
                    foreach ($row as $key => $value) {
                        $result[][$key] = $value;
                    }
                }
            } else {
                // Returns false
                $result = false;
            }

            self::free($resource);

            return $result;
        }

        return false;
    }

    static public function getLastId() {

        if (USE_DATABASE) {
            if (!self::isOpen()) {
                self::$last_error_message = 'Database not initialized:  Need to call init() once before call getLastId()';
                return false;
            }

            $resource = mysql_query("SELECT LAST_INSERT_ID()");
            $lastId = mysql_result($resource, 0);
            self::free($resource);

            return $lastId;
        }

        return false;
    }

    static public function isTableCreated($tablename) {

        if (USE_DATABASE) {
            if (!self::isOpen()) {
                self::$last_error_message = 'Database not initialized:  Need to call init() once before call isTableCreated()';
                return false;
            }

            $resource = mysql_query("SELECT DATABASE()");
            $database = mysql_result($resource, 0);

            $resource = mysql_query("
	    SELECT COUNT(*) AS count 
	    FROM information_schema.tables 
	    WHERE table_schema = '$database' 
	    AND table_name = '$tablename'
	");

            return mysql_result($resource, 0) != 0;
        }

        return false;
    }

    static public function isDatabaseCreated($database, $host = null, $user = null, $pass = null) {

        if (USE_DATABASE) {
            if (!self::isOpen()) {
                $connection = mysql_connect($host, $user, $pass);
                if (!$connection) {
                    self::$last_error_message = 'Could not connect: ' . mysql_error();
                    return false;
                }
            }

            $resource = mysql_query("
	    SELECT COUNT(*) AS count 
	    FROM information_schema.schemata 
	    WHERE schema_name = '$database'
	");

            return mysql_result($resource, 0) != 0;
        }

        return false;
    }

    static public function insertLog($log_data) {

        if (USE_DATABASE) {
            if (!self::isOpen()) {
                self::$last_error_message = 'Database not initialized:  Need to call init() once before call insertLog()';
                return false;
            }

            if (self::isTableCreated('_logs') === FALSE) {
                self::createBaseTables();
            }

            $data = array(
                'log_data' => mysql_real_escape_string($log_data)
            );

            return self::insert('_logs', $data);
        }

        return false;
    }

    static public function insertStatistics($user_agent, $ip_address, $request_url, $statistics_data) {

        if (USE_DATABASE) {
            if (!self::isOpen()) {
                self::$last_error_message = 'Database not initialized:  Need to call init() once before call insertStatistics()';
                return false;
            }

            if (self::isTableCreated('_statistics') === FALSE) {
                self::createBaseTables();
            }

            $hashed_columns = hash('sha256', $ip_address . $user_agent);
            $data = array(
                'hashed_columns' => $hashed_columns,
                'ip_address' => mysql_real_escape_string($ip_address),
                'user_agent' => mysql_real_escape_string($user_agent),
                'request_url' => mysql_real_escape_string($request_url),
                'statistics_data' => mysql_real_escape_string($statistics_data)
            );
            $result = false;
            if (!($result = self::execute(" UPDATE _statistics SET num_pageviews = num_pageviews + 1, last_update = CURRENT_TIMESTAMP WHERE hashed_columns = '$hashed_columns' ")) || mysql_affected_rows() < 1) {
                if (($result = self::insert('_statistics', $data)) === FALSE) {
                    Log::newEntry('ERROR inserting pageview in Database. mysql error: ' . self::$last_error_message);
                }
            }

            return $result;
        }

        return false;
    }

    static public function getInfoUser($id_user, $showHidden = false) {
        if (self::isTableCreated('_user') === FALSE) {
            self::createBaseTables();
        }

        if ($id_user == null || !is_numeric($id_user)) {
            self::$last_error_message = 'First parameter (' . $id_user . ') must be an number';
            return false;
        }

        if (!is_bool($showHidden)) {
            self::$last_error_message = 'Second parameter (' . $showHidden . ') must be a boolean';
            return false;
        }

        $query = 'SELECT * FROM _user WHERE id_user = \'' . $id_user . '\' ';

        if ($showHidden == false) {
            $query .= ' AND IFNULL(hidden, 0) <> 1 ';
        }

        $resource = self::execute($query);

        $userInfo = self::toArray($resource);

        self::free($resource);

        return $userInfo;
    }

    static public function getInfoUserByUsername($username, $showHidden = false) {
        if (self::isTableCreated('_user') === FALSE) {
            self::createBaseTables();
        }

        if (!is_string($username) || empty($username)) {
            self::$last_error_message = 'First parameter (' . $username . ') must be an not empty string';
            return false;
        }

        if (!is_bool($showHidden)) {
            self::$last_error_message = 'Second parameter (' . $showHidden . ') must be a boolean';
            return false;
        }

        $query = 'SELECT * FROM _user WHERE UPPER(username) = UPPER(\'' . $username . '\') ';

        if ($showHidden == false) {
            $query .= ' AND IFNULL(hidden, 0) <> 1 ';
        }

        $resource = self::execute($query);

        $userInfo = self::toArray($resource);

        self::free($resource);

        return $userInfo;
    }

    static public function getInfoUserByEmail($email, $showHidden = false) {
        if (self::isTableCreated('_user') === FALSE) {
            self::createBaseTables();
        }

        if (!is_string($email) || empty($email)) {
            self::$last_error_message = 'First parameter (' . $email . ') must be an not empty string';
            return false;
        }

        if (!is_bool($showHidden)) {
            self::$last_error_message = 'Second parameter (' . $showHidden . ') must be a boolean';
            return false;
        }

        $query = 'SELECT * FROM _user WHERE UPPER(email) = UPPER(\'' . $email . '\') ';

        if ($showHidden == false) {
            $query .= ' AND IFNULL(hidden, 0) <> 1 ';
        }

        $resource = self::execute($query);

        $userInfo = self::toArray($resource);

        self::free($resource);

        return $userInfo;
    }

    static public function getListUsers($orderBy = 'insert_date', $orderType = 'DESC', $showHidden = false) {
        if (self::isTableCreated('_user') === FALSE) {
            self::createBaseTables();
        }

        if (!is_bool($showHidden)) {
            self::$last_error_message = 'First parameter (' . $showHidden . ') must be a boolean';
            return false;
        }

        $query = 'SELECT * FROM _user WHERE 1 ';

        if ($showHidden == false) {
            $query .= ' AND IFNULL(hidden, 0) <> 1 ';
        }

        $query .= " ORDER BY $orderBy $orderType ";

        $resource = self::execute($query);

        return $resource;
    }

    static public function insertAccountRecoveryToken($user_email, $token) {
        if (self::isTableCreated('_token') === FALSE) {
            self::createBaseTables();
        }

        if (!is_string($user_email) || empty($user_email)) {
            self::$last_error_message = 'First parameter (' . $user_email . ') must be an not empty string';
            return false;
        }

        $user_info = DBBase::getInfoUserByEmail($user_email);
        if ($user_info) {
            $id_user = $user_info['id_user'];
            $username = $user_info['username'];

            // Create email validation token
            $dataToInsert = array(
                'id_user' => $id_user,
                'objective' => 'recover_account',
                'token' => mysql_real_escape_string($token),
                'valid_until' => date('Y-m-d H:i:s', mktime(date('H') + 48, date('i')))
            );
            DBBase::removeAccountRecoveryToken($id_user);
            if (!DB::insert('_token', $dataToInsert)) {
                return false;
            } else {
                $validation_link = URL::baseURL() . "/account-recovery?email=" . urlencode($user_email) . "&token=" . urlencode($token);
                $email_body = <<<EOT
        <p>Recebemos um pedido para recuperar a sua conta em Finanças Pessoais</p>
        <p>O seu username é <strong>$username</strong>.</p>
        <p>Para redefinir a sua password clique em <a href="$validation_link">$validation_link</a></p>
        <p>Cumprimentos,<br>A equipa Finanças Pessoais</p>
EOT;

                $dataToInsert = array(
                    'email_subject' => 'Finanças Pessoais - Recuperação de conta',
                    'email_from' => 'email@email.com',
                    'email_to' => $user_email,
                    'email_body' => $email_body,
                    'valid_until' => date('Y-m-d H:i:s', mktime(date('H') + 48, date('i')))
                );

                // Insert the email data in the database to be sent ASAP
                if (DB::insert('_pending_emails', $dataToInsert)) {
                    return TRUE;
                }
            }
        }

        return false;
    }

    static public function validateAccountRecoveryToken($user_email, $token) {
        if (self::isTableCreated('_token') === FALSE) {
            self::createBaseTables();
        }

        if (!is_string($user_email) || empty($user_email)) {
            self::$last_error_message = 'First parameter (' . $user_email . ') must be an not empty string';
            return false;
        }

        if (!is_string($token) || empty($token)) {
            self::$last_error_message = 'Second parameter (' . $token . ') must be an not empty string';
            return false;
        }

        $valid = false;
        $user_info = DBBase::getInfoUserByEmail($user_email);
        if ($user_info !== FALSE) {
            $id_user = $user_info['id_user'];


            $query = 'SELECT * FROM _token WHERE objective = \'recover_account\' AND id_user = \'' . $id_user . '\' AND token = \'' . $token . '\' AND valid_until >= NOW() ';
            $resource = self::execute($query);

            if (mysql_num_rows($resource) > 0) {
                $valid = true;
            }

            DBBase::removeExpiredTokens();
        }

        return $valid;
    }

    static public function removeAccountRecoveryToken($id_user) {
        if (self::isTableCreated('_token') === FALSE) {
            self::createBaseTables();
        }

        if ($id_user == null || !is_numeric($id_user)) {
            self::$last_error_message = 'First parameter (' . $id_user . ') must be an number';
            return false;
        }

        DBBase::removeExpiredTokens();

        // Delete this token
        return self::delete('_token', " objective = 'recover_account' AND id_user = '$id_user' ");
    }

    static public function insertRememberMeToken($id_user, $token) {
        if (self::isTableCreated('_token') === FALSE) {
            self::createBaseTables();
        }

        if ($id_user == null || !is_numeric($id_user)) {
            self::$last_error_message = 'First parameter (' . $id_user . ') must be an number';
            return false;
        }

        if (!is_string($token) || empty($token)) {
            self::$last_error_message = 'Second parameter (' . $token . ') must be an not empty string';
            return false;
        }

        $user_info = DBBase::getInfoUser($id_user);
        if ($user_info) {

            $id_user = $user_info['id_user'];

            $dataToInsert = array(
                'id_user' => $id_user,
                'objective' => 'remember_me',
                'token' => mysql_real_escape_string($token),
                'valid_until' => date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('n'), date('j') + 7, date('Y'))) // valid for 7 days
            );
            DBBase::removeRememberMeToken($id_user);
            if (DB::insert('_token', $dataToInsert)) {
                return true;
            }
        }

        return false;
    }

    static public function validateRememberMeToken($id_user, $token) {
        if (self::isTableCreated('_token') === FALSE) {
            self::createBaseTables();
        }

        if ($id_user == null || !is_numeric($id_user)) {
            self::$last_error_message = 'First parameter (' . $id_user . ') must be an number';
            return false;
        }

        if (!is_string($token) || empty($token)) {
            self::$last_error_message = 'Second parameter (' . $token . ') must be an not empty string';
            return false;
        }


        $valid = false;
        $user_info = DBBase::getInfoUser($id_user);
        if ($user_info) {
            $id_user = $user_info['id_user'];


            $query = 'SELECT * FROM _token WHERE objective = \'remember_me\' AND id_user = \'' . $id_user . '\' AND token = \'' . $token . '\' AND valid_until >= NOW() ';
            $resource = self::execute($query);

            if (mysql_num_rows($resource) > 0) {
                $valid = true;
            }

            DBBase::removeExpiredTokens();
        }

        return $valid;
    }

    static public function removeRememberMeToken($id_user) {
        if (self::isTableCreated('_token') === FALSE) {
            self::createBaseTables();
        }

        if ($id_user == null || !is_numeric($id_user)) {
            self::$last_error_message = 'First parameter (' . $id_user . ') must be an number';
            return false;
        }

        DBBase::removeExpiredTokens();

        // Delete this token
        return self::delete('_token', " objective = 'remember_me' AND id_user = '$id_user' ");
    }

    static public function removeExpiredTokens() {
        DB::delete('_token', "NOW() > valid_until");
    }

    static public function removeExpiredEmails() {
        DB::delete('_pending_emails', "NOW() > valid_until");
    }

    static public function saveUser($userInfo, $userId = null) {
        if (self::isTableCreated('_user') === FALSE) {
            self::createBaseTables();
        }

        if ($userId == null) {
            if (self::insert('_user', $userInfo)) {
                return self::$last_inserted_id;
            }
            return false;
        } else {
            if (self::update('_user', $userInfo, " id_user = '$userId' ")) {
                return $userId;
            }
            return false;
        }
    }

    static public function hideUser($userId) {
        if (self::isTableCreated('_user') === FALSE) {
            self::createBaseTables();
        }

        $dataToUpdate = array(
            'hidden' => 1
        );
        return self::update('_user', $dataToUpdate, " id_user = '$userId' ");
    }

    static public function removeUser($userId) {
        if (self::isTableCreated('_user') === FALSE) {
            self::createBaseTables();
        }

        return self::delete('_user', " id_user = '$userId' ");
    }

    static public function getLangCopy($lang, $copy_name) {
        if (self::isTableCreated('_lang_copy') === FALSE) {
            self::createBaseTables();
        }

        if (!is_string($lang) || empty($lang)) {
            self::$last_error_message = 'The lang (' . $lang . ') must be an not empty string';
            return false;
        }

        if (!is_string($copy_name) || empty($copy_name)) {
            self::$last_error_message = 'The copy_name (' . $copy_name . ') must be an not empty string';
            return false;
        }

        $query = 'SELECT * FROM _lang_copy WHERE UPPER(lang) = UPPER(\'' . $lang . '\') AND UPPER(name) = UPPER(\'' . $copy_name . '\') ';

        $resource = self::execute($query);

        $copyInfo = self::toArray($resource);

        self::free($resource);

        return $copyInfo;
    }

}

?>