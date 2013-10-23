<?php
require_once 'resources/init.php';

$id_account = User::getAccountInUse();

require 'template/header.inc.php';
?>

<header>
    Finanças Pessoais
    <br><a href="login?logout=logout">Logout</a>
</header>
<div role="main">

    <br><a href="data-transaction">Nova transacção</a>
    <br><a href="data-list">Listagem</a>
    <br><a href="data-statistics">Estatísticas</a>
    <br><a href="data-budget">Orçamento</a>
    <hr>

    <?php
    $welcome_msg = 'Seja bem-vindo';
    $user_info = User::getLoggedInUser();
    if ($user_info && isset($user_info['name']) && trim($user_info['name']) != '') {
        $welcome_msg .= ', <strong>' . $user_info['name'] . '</strong>';
    }
    echo "<p>$welcome_msg</p>";
    ?>

    <table id="table_edit_inline" data-type="entities">
        <thead>
            <tr>
                <th>Name</th>
                <th>Edit</th>
<!--                <th>Remove</th>-->
            </tr>
        </thead>

        <tbody>
            <?php
            $query = "
                SELECT id_entity, name 
                FROM entity e 
                    INNER JOIN account a ON a.id_account = e.id_account 
                WHERE a.id_account = '$id_account'
                ORDER BY name ASC";
            $resource = DB::execute($query);
            if (is_resource($resource)) {
                $found_selected = mysql_num_rows($resource);
                while ($row = mysql_fetch_array($resource)) {
                    echo '<tr>';
                    echo '<td><div id="id_' . $row['id_entity'] . '" class="editable">' . $row['name'] . '</div></td>';
                    echo '<td><a href="#id_' . $row['id_entity'] . '" class="link_edit">Edit</a></td>';
//                    echo '<td><a href="#id_' . $row['id_entity'] . '" class="link_remove">Remove</a></td>';
                    echo '</tr>';
                }
                if ($found_selected <= 0) {
                    echo '<tr><td colspan="3">Nenhuma entidade encontrada</td></tr>';
                }
            }
            ?>
        </tbody>
    </table>

</div>

<?php
require 'template/footer.inc.php';
?>