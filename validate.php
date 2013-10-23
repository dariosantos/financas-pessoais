<?php

require_once 'resources/init.php';

User::logout();

$validation_message = '';

if (!isset($_GET['u']) || trim($_GET['u']) == '') {
    $validation_message = 'ERROR: User wasn\'t supplied';
} else if (!isset($_GET['token']) || trim($_GET['token']) == '') {
    $validation_message = 'ERROR: Token wasn\'t supplied';
} else {
    // Validating user
    $user = mysql_real_escape_string($_GET['u']);
    $token = mysql_real_escape_string($_GET['token']);

    // Check if this user is already validated
    $query = "SELECT * FROM _user WHERE id_user = '$user' AND validated IS NOT NULL";
    $resource = DB::execute($query);
    $num_rows = mysql_num_rows($resource);
    if ($num_rows > 0) {
        // User is already valid
        $validation_message = 'Este utilizador já foi validado<br><a href="members-area">Continuar</a>';
    } else {
        // Check if the token is valid
        $query = "SELECT * FROM _token WHERE objective = 'validate_user' AND id_user = '$user' AND token = '$token' AND valid_until >= NOW()";
        $resource = DB::execute($query);
        $num_rows = mysql_num_rows($resource);
        if ($num_rows <= 0) {
            // Not valid
            $validation_message = 'O token é inválido ou foi expirado. Faça login para pedir outro';
        } else {
            // Validates this user
            DB::startTransaction();

            $dataToUpdate = array(
                'validated' => date('Y-m-d H:i:s')
            );
            if (!DB::update('_user', $dataToUpdate, "id_user = '$user'")) {
                // ERROR when updating user
                $validation_message = 'Não foi possível actualizar o seu estado';
                DB::rollbackTransaction();
            } else if (!DB::delete('_token', "objective = 'validate_user' AND id_user = '$user'")) {
                // ERROR when removing token
                $validation_message = 'Erro ao remover o token';
                DB::rollbackTransaction();
            } else {
                // User validated
                DB::commitTransaction();
                $validation_message = 'Utilizador validado com sucesso. Faça login para continuar<br><a href="login">Login</a>';
            }
        }
    }
}
echo $validation_message;
?>