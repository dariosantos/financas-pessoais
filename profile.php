<?php
// TODO: profile_image

require_once 'resources/init.php';

$form_message = '';

$user_info = User::getLoggedInUser();
if ($user_info && $user_info['id_user']) {
    $id_user = $user_info['id_user'];
    $user_info = DB::getInfoUser($id_user);
    if ($user_info) {
        $username = $user_info['username'];
//        $profile_image = $user_info['profile_image'];
        $name = $user_info['name'];
        $email = $user_info['email'];
    }

    // Check if user has posted data
    if (!empty($_POST)) {

//        $profile_image = $_POST['profile_image'];
        $name = $_POST['name'];
        $email = $_POST['email'];

        if (!isset($_POST['name']) || !trim($_POST['name'])) {
            $form_message = 'Insert your name';
        }

        if (!isset($_POST['email']) || !trim($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $form_message = 'Insert your email';
        }

        if (trim($form_message) == '') {
            // Check if current password is correct
            if (!User::checkUserPassword($_POST['current_password'])) {
                $form_message = 'Your current password is incorrect';
            } else {
                // Password correct. Lets save data
                $userInfo = array(
//                'profile_image' => $profile_image,
                    'name' => $name,
                    'email' => $email
                );

                // Check if is supposed to change password
                if (trim($_POST['new_password']) != '' || trim($_POST['repeat_password']) != '') {
                    if (($form_message = User::changePassword($_POST['current_password'], $_POST['new_password'], $_POST['repeat_password'])) === TRUE) {
                        $form_message = '';
                    }
                }

                if (trim($form_message) == '') {
                    if (DB::saveUser($userInfo, $id_user)) {
                        $form_message = 'Profile data saved';
                    } else {
                        $form_message = 'Could not save your profile data';
                    }
                }
            }
        }
    }
}

require_once 'template/header.inc.php';
?>

PROFILE
<br /><?php echo $form_message; ?>

<?php
if (!$user_info) {
    echo 'Ocorreu um erro ao aceder aos seus dados';
} else {
    ?>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
    <!--        <br />Photo: <input type="file" name="photo" value="" />-->
        <br />
        <br />Username: <input disabled="disabled" type="text" name="username" value="<?php echo $username; ?>" />
        <br />Name: <input type="text" name="name" value="<?php echo $name; ?>" />
        <br />E-mail: <input type="text" name="email" value="<?php echo $email; ?>" />
        <br />
        <br />If you want to change your password:
        <br />Change Password: <input type="password" name="new_password" value="" />
        <br />Confirm Password: <input type="password" name="repeat_password" value="" />
        <br />
        <br />Password: <input type="password" name="current_password" value="" />
        <input type="submit" name="submit" value="Save" />
        <br />Your current password is needed to change your profile
    </form>
    <?php
}
?>

<?php
require_once 'template/footer.inc.php';
?>