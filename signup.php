<?php
require_once 'resources/init.php';

User::logout();

$form_success = false;
$form_message = 'Todos os campos são obrigatórios';

if (isset($_POST) && isset($_POST['submit_register'])) {
    // Register user
    $username = (isset($_POST['username']) ? $_POST['username'] : null);
    $password = (isset($_POST['password']) ? $_POST['password'] : null);
    $repeat_password = (isset($_POST['repeat_password']) ? $_POST['repeat_password'] : null);
    $name = (isset($_POST['name']) ? $_POST['name'] : null);
    $email = (isset($_POST['email']) ? $_POST['email'] : null);

    // Check if the username or the email already exists
    $query = "SELECT * FROM _user WHERE username = '$username' OR email = '$email'";
    $resource = DB::execute($query);
    $num_rows = mysql_num_rows($resource);

    if (trim($username) == '' || trim($password) == '' || trim($repeat_password) == '' || trim($name) == '' || trim($email) == '') {
        // Invalid: some data missing
        $form_message = 'Todos os campos são obrigatórios';
    } else if ($num_rows > 0) {
        // Invalid: username or email already exists
        $form_message = 'ERRO: Esse username ou email já está registado';
    } else if ($password != $repeat_password) {
        // Invalid: Passwords don't match
        $form_message = 'ERRO: As passwords têm que ser idênticas';
    } else {
        // Valid: Save data

        DB::startTransaction();

        $dataToInsert = array(
            'username' => $username,
            'password' => mysql_real_escape_string(hash('sha256', $password)),
            'name' => $name,
            'email' => $email
        );
        if (!DB::insert('_user', $dataToInsert)) {
            $form_message = 'ERROR: Não foi possível registá-lo. Tente novamente mais tarde';
            DB::rollbackTransaction();
        } else {
            $id_user = DB::$last_inserted_id;

            // Create email validation token
            list(, $token) = User::generateToken();
            $dataToInsert = array(
                'id_user' => $id_user,
                'objective' => 'validate_user',
                'token' => mysql_real_escape_string($token),
                'valid_until' => date('Y-m-d H:i:s', mktime(date('H') + 48, date('i')))
            );
            DB::delete('_token', "objective = 'validate_user' AND id_user = '$id_user'");
            if (!DB::insert('_token', $dataToInsert)) {
                $form_message = 'ERROR: Ocorreu um erro ao criar um token de validação para o seu email';
                DB::rollbackTransaction();
            } else {

                // Send validation email
                $validation_link = "http://simpleweb.pt/validate?u=" . urlencode($id_user) . "&token=" . urlencode($token);
                $body = <<<EOT
<p>Agradecemos o seu registo no site Finanças Pessoais</p>
<p>O seu username é <strong>$username</strong>.</p>
<p>Lembre-se que este serviço encontra-se em estado BETA, por isso algumas funcionalidades poderão não estar completas</p>
<p>Para validar o seu registo, clique em <a href="{$validation_link}">{$validation_link}</a></p>
<p>Cumprimentos,<br>A equipa Finanças Pessoais</p>
EOT;
                $email_sender = Email::createInstance();
                if (!$email_sender->send('Finanças Pessoais - Validação registo', $body, 'noreply@simpleweb.pt', $email)) {
                    $form_message = 'ERROR: Ocorreu um erro ao enviar o email de validação';
                    var_dump(Email::$last_error_message);
                    DB::rollbackTransaction();
                } else {
                    // Create user's account
                    $dataToInsert = array();
                    if (!DB::insert('account', $dataToInsert)) {
                        $form_message = 'ERROR: Não foi possível criar a sua conta de acesso';
                        DB::rollbackTransaction();
                    } else {
                        $id_account = DB::$last_inserted_id;

                        // Associate user with account
                        $dataToInsert = array(
                            'id_account' => $id_account,
                            'id_user' => $id_user,
                            'role' => 'owner'
                        );
                        if (!DB::insert('account_user', $dataToInsert)) {
                            $form_message = 'ERROR: Não foi possível configurar a sua conta de acesso';
                            DB::rollbackTransaction();
                        } else {
                            // Finnaly: Data saved
                            DB::commitTransaction();
                            $form_message = 'Registado com successo. Deverá receber um email com um link de validação';
                            $form_success = true;
                        }
                    }
                }
            }
        }
    }
}

require 'template/header.inc.php';

if ($form_success) {
    ?>
    <p><?php echo $form_message; ?></p>
    <a href="members-area">Voltar</a>
    <?php
} else {
    ?>
    <form id="form_register_user" action="signup" method="POST">

        <p id="form_message"><?php echo $form_message; ?></p>

        <label>
            Username: 
            <input type="text" name="username">
        </label>
        <br>
        <label>
            Password: 
            <input type="password" name="password">
        </label>
        <br>
        <label>
            Repetir Password: 
            <input type="password" name="repeat_password">
        </label>
        <br>
        <label>
            Nome: 
            <input type="text" name="name">
        </label>
        <br>
        <label>
            Email: 
            <input type="text" name="email">
        </label>
        <br>
        <input type="submit" name="submit_register" value="Registar">
    </form>

    <script>
        $(document).ready(function(){
            $('#form_register_user').submit(function(){
                // Validate form data
                var form = $(this);

                if (!validate($(form).find('input[name="username"]').val())) {
                    showMessage('Insira um username válido');
                    return false;
                }

                if (!validate($(form).find('input[name="name"]').val())) {
                    showMessage('Insira um nome válido');
                    return false;
                }

                if (!validateEmail($(form).find('input[name="email"]').val())) {
                    showMessage('Insira um email válido');
                    return false;
                }

                var password = $(form).find('input[name="password"]').val();
                var repeat_password = $(form).find('input[name="repeat_password"]').val();
                if (!validate(password) || password != repeat_password) {
                    showMessage('As passwords não correspondem');
                    return false;
                }
            });
        });

        function validate(value) {
            if ($.trim(value) == '') {
                return false;
            }
            return true;
        }
                                                                                                                                                                                                            
        function showMessage(message) {
            $('#form_message').text('ERRO: ' + message);
        }
                                                                                                                                                                                                            
        function validateEmail(email) { 
            var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(email);
        }
    </script>

    <?php
}

require 'template/footer.inc.php';
?>