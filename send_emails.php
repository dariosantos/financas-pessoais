<?php

ini_set('max_execution_time', 600); // default is 30 seconds - Send emails can take a while

require_once 'resources/init.php';

DB::removeExpiredEmails();

if (USE_EMAIL) {

    $valid_token = hash_hmac('sha256', date("l"), SEND_MAIL_SECRET_KEY);
    $valid_token_yesterday = hash_hmac('sha256', date("l", mktime(date("H"), date("i") - 5)), SEND_MAIL_SECRET_KEY); // When it passes midnight by 5 min

    if (isset($_POST['token']) && ($_POST['token'] == $valid_token || $_POST['token'] == $valid_token_yesterday)) {
        // Get emails to send from database
        $resource = DB::execute('SELECT * FROM _pending_emails WHERE valid_until >= NOW() AND sent_successfully = FALSE ORDER BY id_pending_email DESC');
        if (is_resource($resource) && mysql_num_rows($resource) > 0) {

            $email_sender = Email::createInstance();
            if ($email_sender) {
                while ($row = mysql_fetch_assoc($resource)) {
                    $id_pending_email = $row['id_pending_email'];

                    $subject = $row['email_subject'];
                    $from = $row['email_from'];
                    $to = $row['email_to'];
                    $body = $row['email_body'];
                    $cc = $row['email_cc'];
                    $bcc = $row['email_bcc'];
                    $replyTo = $row['email_replyTo'];
                    $contentType = $row['contentType'];
                    $charset = $row['charset'];

                    $dataToUpdate = array();

                    if ($email_sender->send($subject, $body, $from, $to, $cc, $bcc, $replyTo, $contentType, $charset)) {
                        // Email sent
                        $dataToUpdate['sent_successfully'] = true;
                    } else {
                        Log::newEntry("send_emails - Could not send email. id_pending_email: $id_pending_email", Email::$last_error_message, $subject, $body, $from, $to, $cc, $bcc, $replyTo, $contentType, $charset);
                    }
                    if (!DB::update("_pending_emails", $dataToUpdate, "id_pending_email = '$id_pending_email'")) {
                        Log::newEntry("send_emails - Could not update database. id_pending_email: $id_pending_email", DB::$last_error_message, $dataToUpdate);
                    }
                }
            }
        }
    }
}
?>