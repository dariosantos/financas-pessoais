<?php
require_once 'resources/init.php';

User::logout();

$send_email = false;
$show_email_form = true;
$show_password_form = false;
$recovery_message = 'Introduza o seu email';

if (isset($_GET['email']) && isset($_GET['token'])) {
    // Changing password
    $show_email_form = false;
    $show_password_form = true;
    $recovery_message = 'Introduza a nova password';

    $user_email = htmlentities(urldecode($_GET['email']), ENT_COMPAT, 'UTF-8');
    $token = htmlentities(urldecode($_GET['token']), ENT_COMPAT, 'UTF-8');

    if (!User::validateAccountRecoveryToken($user_email, $token)) {
        // Token invalid. Check if user is already logged in (changed password)
        if (User::isSessionValid()) {
            // User is logged in. Redirecting
            header('Location: members-area');
            exit;
        } else {
            $show_email_form = false;
            $show_password_form = false;
            $recovery_message = 'O token não é válido. Verifique se o endereço no browser é igual ao link indicado no email enviado';
        }
    } else {
        // Token valid
        if (isset($_POST['new_password']) && isset($_POST['repeat_password'])) {
            // Change the password

            $user_info = DB::getInfoUserByEmail($user_email);
            $id_user = $user_info['id_user'];
            $username = $user_info['username'];
            $password = $user_info['password'];

            $new_password = htmlentities($_POST['new_password'], ENT_COMPAT, 'UTF-8');
            $repeat_password = htmlentities($_POST['repeat_password'], ENT_COMPAT, 'UTF-8');

            $result = User::login($username, $password, false, false);
            if ($result === TRUE) {

                if ($user_info['validated'] == null) {
                    $dataToUpdate = array(
                        'validated' => date('Y-m-d H:i:s')
                    );
                    DB::update('_user', $dataToUpdate, "id_user = '$id_user'");
                }

                $result = User::changePassword($password, $new_password, $repeat_password, true, false);
                if ($result === TRUE) {

                    User::removeAccountRecoveryToken($id_user);
                    $show_email_form = false;
                    $show_password_form = false;
                    $recovery_message = 'Password alterada com sucesso.<br><a href="members-area">Continuar</a>';
                } else {
                    User::logout();
                    $recovery_message = $result;
                }
            } else {
                User::logout();
                $recovery_message = $result;
            }
        }
    }
} else if (isset($_POST['recover']) && isset($_POST['email']) && trim($_POST['email']) != '') {
    // Posting email
    $show_email_form = true;
    $show_password_form = false;
    $recovery_message = 'Caso este email esteja registado na base de dados, é-lhe enviado um email com um link para redefinir a sua password';

    $user_email = htmlentities($_POST['email'], ENT_COMPAT, 'UTF-8');

    if (User::createAccountRecoveryToken($user_email)) {
        $send_email = true; // Send email after the render this page
    }
}

// Used to send an email asynchronously
// http://stackoverflow.com/questions/962915/how-do-i-make-an-asynchronous-get-request-in-php
function curl_post_async($url, $params) {
    foreach ($params as $key => &$val) {
        if (is_array($val))
            $val = implode(',', $val);
        $post_params[] = $key . '=' . urlencode($val);
    }
    $post_string = implode('&', $post_params);

    $parts = parse_url(str_replace('https://', 'http://', $url));

//    $port = (PROTOCOL == 'https://' ? 443 : 80 );
    $port = 80;

    $fp = fsockopen($parts['host'], $port, $errno, $errstr, 30);

    if ($fp !== FALSE) {
        $out = "POST " . $parts['path'] . " HTTP/1.1\r\n";
        $out.= "Host: " . $parts['host'] . "\r\n";
        $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out.= "Content-Length: " . strlen($post_string) . "\r\n";
        $out.= "Connection: Close\r\n\r\n";
        if (isset($post_string))
            $out.= $post_string;

        fwrite($fp, $out);
        fclose($fp);
    } else {
        Log::newEntry("Could not connect to '$url' (using fsockopen in curl_post_async)");
    }
}

require 'template/header.inc.php';

if ($show_email_form) {
    ?>
    <br>
    <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
        <label>Email: <input type="text" name="email" value=""></label>
        <input type="submit" name="recover" value="Recuperar">
    </form>
    <?php
}
if ($show_password_form) {
    ?>
    <br>
    <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
        <label>Nova password: <input type="password" name="new_password" value=""></label>
        <br>
        <label>Confirmação: <input type="password" name="repeat_password" value=""></label>
        <br>
        <input type="submit" name="recover" value="Alterar">
    </form>
    <?php
}

echo $recovery_message;

require 'template/footer.inc.php';


// Check if is suppose to send the email
if ($send_email) {

    $valid_token = hash_hmac('sha256', date("l"), SEND_MAIL_SECRET_KEY);
    $parameters = array(
        'token' => $valid_token
    );

    $url = URL::baseURL() . '/send_emails';

    curl_post_async($url, $parameters);
}
?>