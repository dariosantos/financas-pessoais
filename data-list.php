<?php
define('TYPE_CREDIT', 1);
define('TYPE_DEBIT', 2);

require_once 'resources/init.php';

$id_account = User::getAccountInUse();

require 'template/header.inc.php';
?>

<header>
    Listagem
</header>
<div role="main">
    
    <?php
    $query = "
                SELECT 
                    (SELECT SUM(ROUND(price * quantity, 2)) FROM transaction_product p WHERE p.id_transaction = t.id_transaction) AS calculated, 
                    t.*, e.name AS entity, c.name AS category 
                FROM transaction t LEFT JOIN entity e ON t.id_entity = e.id_entity LEFT JOIN category c ON t.id_category = c.id_category 
                    INNER JOIN account a ON a.id_account = c.id_account 
                WHERE a.id_account = '$id_account'
                ORDER BY transaction_date DESC";
    $resource = DB::execute($query);
    if (is_resource($resource)) {
        ?>

        <table border="1">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Categoria</th>
                    <th>Entidade</th>
                    <th>Calculado</th>
                    <th>Total</th>
                    <th>Data</th>
                    <th>Options</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = mysql_fetch_array($resource)) {
                    $amount = number_format($row['amount'], 2, '.', '');
                    $calculated = number_format($row['calculated'], 2, '.', '');
                    ?>
                    <tr>
                        <td><?php echo $row['id_transaction']; ?></td>
                        <td><?php echo (intval($row['type']) == TYPE_CREDIT ? 'Crédito' : (intval($row['type']) == TYPE_DEBIT ? 'Débito' : '&lt;none&gt;')); ?></td>
                        <td><?php echo $row['category']; ?></td>
                        <td><?php echo $row['entity']; ?></td>
                        <td class="number"><?php echo $calculated; ?></td>
                        <td class="number<?php echo ($amount > $calculated ? ' paid_more' : ($amount < $calculated ? ' paid_less' : '')); ?>" ><?php echo $amount; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($row['transaction_date'])); ?></td>
                        <td><a href="data-transaction?id=<?php echo $row['id_transaction']; ?>">Edit</a></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>

        <?php
    }
    ?>

</div>

<?php
require 'template/footer.inc.php';
?>