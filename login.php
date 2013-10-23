<?php
require_once 'resources/init.php';

$message = (isset($_GET['message']) ? urldecode($_GET['message']) : 'Login to continue');
$redirect_url = (isset($_GET['redirect']) ? urldecode($_GET['redirect']) : 'data-transaction');

// Logout
if (isset($_GET['logout'])) {
    if ($_GET['logout'] == 'clean') {
        User::logout(true); // Also destroy session
    } else {
        User::logout();
    }
    
    $redirect_url = (isset($_GET['redirect']) ? urldecode($_GET['redirect']) : 'home');
    
    header('Location: ' . $redirect_url);
    exit;
}

if (User::isSessionValid()) {
    header('Location: ' . $redirect_url);
    exit;
}


// Login
$try_username = '';
if (isset($_POST['login'])) {
    
    if (isset($_POST['username']) && isset($_POST['password'])) {

        $try_username = htmlentities($_POST['username'], ENT_COMPAT, 'UTF-8');
        $try_password = htmlentities($_POST['password'], ENT_COMPAT, 'UTF-8');
        $remember_me = (isset($_POST['remember_me']) && $_POST['remember_me'] == 'true' ? true : false);

        $result = User::login($try_username, $try_password, $remember_me);
        if ($result === TRUE) {
            header('Location: ' . $redirect_url);
            exit;
        } else {
            $message = $result;
        }
    }
}
require 'template/header.inc.php';
?>

<header>
    Área Reservada
</header>
<div role="main">

    <div id="login">
        <h3 align="center">Login</h3>
        <form name="login_form" action="login?redirect=<?php echo urlencode($redirect_url); ?>" method="post">
            <div align="center">
                <p>
                    Username:&nbsp;
                    <input type="text" name="username" value="<?php echo $try_username; ?>" />
                    <br />
                    Password:&nbsp;
                    <input type="password" name="password" value="" />
                    <br />
                    Remember me on this computer:&nbsp;
                    <input id="remember_me" type="checkbox" name="remember_me" value="true" />
                    <br />
                    <span id="form_message"><?php echo $message; ?></span>
                    <br />
                    <input type="submit" name="login" value="Login" />
                </p>
                <a href="account-recovery">Recuperar dados de acesso</a>
                <br />
                <a href="signup">Registar novo utilizador</a>
            </div>
        </form>
    </div>

</div>

<?php
require 'template/footer.inc.php';
?>