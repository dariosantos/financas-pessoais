<?php
require_once 'resources/init.php';

function updateTransactionInfo(&$id_transaction, &$transactionInfo) {

    $query = "SELECT * FROM transaction WHERE id_transaction = '$id_transaction'";
    $resource = DB::execute($query);
    $transactionInfo = DB::toArray($resource);

    if (!is_array($transactionInfo)) {
        // Transaction is new
        $id_transaction = null;

        $transactionInfo = array(
            'id_transaction' => null,
            'type' => 2,
            'id_entity' => null,
            'id_category' => null,
            'amount' => '0.00',
            'transaction_date' => date('Y-m-d H:i'),
            'remarks' => '',
            'hidden' => null,
            'last_update' => null,
            'insert_date' => null
        );
    }

    // If there is a $_POST, data is not saved, so use it
    $transactionInfo['type'] = (isset($_POST['type']) ? $_POST['type'] : $transactionInfo['type']);
    $transactionInfo['id_entity'] = (isset($_POST['id_entity']) ? $_POST['id_entity'] : $transactionInfo['id_entity']);
    $transactionInfo['id_category'] = (isset($_POST['id_category']) ? $_POST['id_category'] : $transactionInfo['id_category']);
    $transactionInfo['amount'] = (isset($_POST['amount']) ? $_POST['amount'] : $transactionInfo['amount']);
    $transactionInfo['transaction_date'] = (isset($_POST['transaction_date']) ? $_POST['transaction_date'] : $transactionInfo['transaction_date']);
    $transactionInfo['remarks'] = (isset($_POST['remarks']) ? $_POST['remarks'] : $transactionInfo['remarks']);
}

$id_account = User::getAccountInUse();

define('MIN_ROWS_PRODUCTS', 7);
define('TYPE_CREDIT', 1);
define('TYPE_DEBIT', 2);

$error_message = '';

$id_transaction = null;
$transactionInfo = null;
if (isset($_GET['id'])) {
    $id_transaction = mysql_real_escape_string($_GET['id']);
}
updateTransactionInfo($id_transaction, $transactionInfo);


if (!empty($_POST)) {
//    var_dump($_POST);
}
if (!User::isSessionValid()) {
    $error_message = 'Session expired. Login to continue';
} else {
// Handle form submits
    if (!empty($_POST)) {
        try {
            // Insert data in database
            DB::startTransaction();

            // Validate data
            if (htmlentities($_POST['id_entity'], ENT_COMPAT, 'UTF-8') == 'none') {
                $error_message .= '<br>Introduza a Entidade';
            }
            if (htmlentities($_POST['type'], ENT_COMPAT, 'UTF-8') == 'none') {
                $error_message .= '<br>Introduza o tipo de transacção';
            }
            if (htmlentities($_POST['amount'], ENT_COMPAT, 'UTF-8') == 'none') {
                $error_message .= '<br>Introduza o valor total da transacção';
            }
            if (htmlentities($_POST['transaction_date'], ENT_COMPAT, 'UTF-8') == 'none') {
                $error_message .= '<br>Introduza a data da transacção';
            }
            if (count($_POST['id_product']) != count($_POST['price'])) {
                $error_message .= '<br>O número de produtos tem que ser igual ao número de preços de produto';
            }

            foreach ($_POST['id_product'] as $index => $id_product) {
                if ($id_product != 'none' && htmlentities($_POST['price'][$index], ENT_COMPAT, 'UTF-8') == 'none') {
                    $error_message .= "<br>O produto ($id_product) está definido mas o preço equivalente (index: $index) não";
                }
            }

            if (trim($error_message) != '') {
                throw new Exception("Dados inválidos: $error_message");
            }

            // Insert Entity
            $id_entity = htmlentities($_POST['id_entity'], ENT_COMPAT, 'UTF-8');
            $query = "SELECT * FROM entity WHERE id_entity = '$id_entity'";
            $resource = DB::execute($query);
            $info = DB::toArray($resource);
            if (!is_array($info) || $info['id_entity'] != $id_entity) {
                // Entity is new
                $name_entity = $id_entity;
                $dataToInsert = array(
                    'id_account' => $id_account,
                    'name' => $name_entity
                );
                if (!DB::insert('entity', $dataToInsert)) {
                    throw new Exception("ERROR inserting entity ($name_entity)");
                }
                $id_entity = DB::$last_inserted_id;
            }



            // Insert Category
            $id_category = htmlentities($_POST['id_category'], ENT_COMPAT, 'UTF-8');
            // NOTE: Category is optional
            if ($id_category != 'none') {
                $query = "SELECT * FROM category WHERE id_category = '$id_category'";
                $resource = DB::execute($query);
                $info = DB::toArray($resource);
                if (!is_array($info) || $info['id_category'] != $id_category) {
                    // Category is new
                    $name_category = $id_category;
                    $dataToInsert = array(
                        'id_account' => $id_account,
                        'name' => $name_category
                    );
                    if (!DB::insert('category', $dataToInsert)) {
                        throw new Exception("ERROR inserting category ($name_category)");
                    }
                    $id_category = DB::$last_inserted_id;
                }
            }



            // Insert Transaction
            $type = htmlentities($_POST['type'], ENT_COMPAT, 'UTF-8');
            $amount = abs(doubleval(str_replace(',', '.', htmlentities($_POST['amount'], ENT_COMPAT, 'UTF-8'))));
            $transaction_date = htmlentities($_POST['transaction_date'], ENT_COMPAT, 'UTF-8');
            $remarks = htmlentities($_POST['remarks'], ENT_COMPAT, 'UTF-8');

            $dataToSave = array(
                'id_entity' => $id_entity,
                'id_category' => $id_category,
                'type' => $type,
                'amount' => $amount,
                'transaction_date' => $transaction_date,
                'remarks' => $remarks
            );

            $new_transaction = false;
            if (!empty($id_transaction)) {
                if (!DB::update('transaction', $dataToSave, "id_transaction = '$id_transaction'")) {
                    throw new Exception("ERROR on update transaction ($id_transaction)");
                }
            } else {
                if (!DB::insert('transaction', $dataToSave)) {
                    throw new Exception("ERROR on insert transaction");
                }
                $id_transaction = DB::$last_inserted_id;
                $new_transaction = true;
            }

            // Remove old products
            if (!DB::delete('transaction_product', "id_transaction = '$id_transaction'")) {
                throw new Exception("ERROR on delete old products from transaction_product - id_transaction ($id_transaction)");
            }

            $general_counter = 1;

            // Insert Products
            foreach ($_POST['id_product'] as $index => $id_product) {

                if ($id_product != 'none') {

                    $id_product = htmlentities($id_product, ENT_COMPAT, 'UTF-8');

                    $query = "SELECT * FROM product WHERE id_product = '$id_product'";
                    $resource = DB::execute($query);
                    $info = DB::toArray($resource);
                    if (!is_array($info) || $info['id_product'] != $id_product) {
                        // Check if this new product was already inserted
                        $query = "SELECT * FROM product WHERE reference = '$id_product' AND id_entity = '$id_entity'";
                        $resource = DB::execute($query);
                        $info = DB::toArray($resource);
                        if (!is_array($info) || $info['reference'] != $id_product || $info['id_entity'] != $id_entity) {
                            // Product wasn't inserted yet
                            $reference_product = $id_product;
                            $dataToInsert = array(
                                'id_entity' => $id_entity,
                                'reference' => $reference_product
                            );
                            if (!DB::insert('product', $dataToInsert)) {
                                throw new Exception("ERROR inserting product ($reference_product)");
                            }
                            $id_product = DB::$last_inserted_id;
                        } else {
                            $id_product = $info['id_product'];
                        }
                    }

                    $price = doubleval(str_replace(',', '.', htmlentities($_POST['price'][$index], ENT_COMPAT, 'UTF-8')));

                    $quantity = doubleval(str_replace(',', '.', htmlentities($_POST['quantity'][$index], ENT_COMPAT, 'UTF-8')));
                    if (!is_numeric($quantity)) {
                        $quantity = 1;
                    }


                    // Get the first available counter in transaction_product
                    $same_product_counter = 0;
                    do {
                        $same_product_counter++;
                        $query = "SELECT * FROM transaction_product WHERE id_transaction = '$id_transaction' AND id_product = '$id_product' AND counter = '$same_product_counter'";
                        $resource_counter = DB::execute($query);
                    } while (is_resource($resource_counter) && mysql_num_rows($resource_counter) >= 1);


                    // Add this product to this transaction
                    $dataToInsert = array(
                        'id_transaction' => $id_transaction,
                        'id_product' => $id_product,
                        'counter' => $same_product_counter,
                        'price' => $price,
                        'quantity' => $quantity,
                        'sort' => $general_counter
                    );
                    if (!DB::insert('transaction_product', $dataToInsert)) {
                        throw new Exception("ERROR on insert transaction_product ($id_transaction, $id_product, $same_product_counter, $price, $quantity)");
                    } else {
                        $general_counter++;
                    }
                }
            }

            DB::commitTransaction();

            $_POST = array(); // All data saved. $_POST is no longer necessary

            if ($new_transaction) {
                header('Location: data-transaction?id=' . $id_transaction);
                exit;
            }

            updateTransactionInfo($id_transaction, $transactionInfo);
        } catch (Exception $e) {
            DB::rollbackTransaction();
            $error_message = "ERROR: {$e->getMessage()}.<p>MYSQL: " . DB::$last_error_message . "</p>";
        }
    }
}

require 'template/header.inc.php';
?>

<header>
    Nova Transacção
</header>
<div role="main">

    <audio id="sound_invalid_input">
        <source src="audio/sound_invalid_input.ogg" type="audio/ogg">
        <source src="audio/sound_invalid_input.mp3" type="audio/mpeg">
    </audio>

    <?php
    $id_previous = null;
    $id_next = null;

    $query_link = "
            SELECT t.id_transaction 
            FROM transaction t 
                INNER JOIN entity e ON e.id_entity = t.id_entity 
                INNER JOIN account a ON a.id_account = e.id_account 
            WHERE t.transaction_date < '{$transactionInfo['transaction_date']}' AND a.id_account = '$id_account' 
            ORDER BY t.transaction_date DESC 
            LIMIT 0, 1";
    $resource_link = DB::execute($query_link);
    $info_link = DB::toArray($resource_link);
    if (is_array($info_link)) {
        $id_previous = $info_link['id_transaction'];
    }

    $query_link = "
            SELECT t.id_transaction 
            FROM transaction t 
                INNER JOIN entity e ON e.id_entity = t.id_entity 
                INNER JOIN account a ON a.id_account = e.id_account 
            WHERE t.transaction_date > '{$transactionInfo['transaction_date']}' 
            ORDER BY t.transaction_date ASC 
            LIMIT 0, 1";
    $resource_link = DB::execute($query_link);
    $info_link = DB::toArray($resource_link);
    if (is_array($info_link)) {
        $id_next = $info_link['id_transaction'];
    }
    ?>

    <?php
    $welcome_msg = 'Seja bem-vindo';
    $user_info = User::getLoggedInUser();
    if ($user_info && isset($user_info['name']) && trim($user_info['name']) != '') {
        $welcome_msg .= ', <strong>' . $user_info['name'] . '</strong>';
    }
    echo "<p>$welcome_msg</p>";
    ?>

    <form id="form_insert_transaction" action="<?php echo $_SERVER['PHP_SELF'] . (trim($id_transaction) != '' ? "?id=$id_transaction" : ''); ?>" method="POST">

        <p>&nbsp;</p>

        <div id="form_feedback"><?php echo (trim($error_message) != '' ? $error_message : '&nbsp;'); ?></div>

        <br><label>Tipo: </label><select class="autocomplete_select" name="type">
            <option value="none"></option>
            <option value="<?php echo TYPE_CREDIT; ?>" <?php echo (intval($transactionInfo['type']) == TYPE_CREDIT ? ' selected ' : '' ); ?>>Crédito</option>
            <option value="<?php echo TYPE_DEBIT; ?>" <?php echo (intval($transactionInfo['type']) == TYPE_DEBIT ? ' selected ' : '' ); ?>>Débito</option>
        </select>

        <br><label>Entidade: </label><select class="autocomplete_select auto_add" name="id_entity">
            <option value="none"></option>
            <?php
            $query = "
                        SELECT id_entity, name 
                        FROM entity e 
                            INNER JOIN account a ON a.id_account = e.id_account 
                        WHERE a.id_account = '$id_account'
                        ORDER BY name ASC";
            $resource = DB::execute($query);
            if (is_resource($resource)) {
                $found_selected = false;
                while ($row = mysql_fetch_array($resource)) {
                    $is_selected = ($transactionInfo['id_entity'] == $row['id_entity'] ? ' selected ' : '' );
                    echo '<option value="' . $row['id_entity'] . '" ' . $is_selected . '>' . $row['name'] . '</option>';
                    if ($is_selected != '') {
                        // Found the selected option
                        $found_selected = true;
                    }
                }
                if (!$found_selected && $transactionInfo['id_entity'] != 'none') {
                    // Didn't found selected. Create a new option
                    echo '<option class="new_select_option" value="' . $transactionInfo['id_entity'] . '" selected >' . $transactionInfo['id_entity'] . '</option>';
                }
            }
            ?>
        </select>

        <br><label>Data: </label><input class="datepicker" type="text" name="transaction_date" value="<?php echo date('Y-m-d H:i', strtotime($transactionInfo['transaction_date'])); ?>">

        <br><label>Categoria: </label><select class="autocomplete_select auto_add" name="id_category">
            <option value="none"></option>
            <?php
            $query = "
                        SELECT id_category, name 
                        FROM category c 
                            INNER JOIN account a ON a.id_account = c.id_account 
                        WHERE a.id_account = '$id_account'
                        ORDER BY name ASC";
            $resource = DB::execute($query);
            if (is_resource($resource)) {
                $found_selected = false;
                while ($row = mysql_fetch_array($resource)) {
                    $is_selected = ($transactionInfo['id_category'] == $row['id_category'] ? ' selected ' : '' );
                    echo '<option value="' . $row['id_category'] . '" ' . $is_selected . '>' . $row['name'] . '</option>';
                    if ($is_selected != '') {
                        // Found the selected option
                        $found_selected = true;
                    }
                }
                if (!$found_selected && $transactionInfo['id_category'] != 'none') {
                    // Didn't found selected. Create a new option
                    echo '<option class="new_select_option" value="' . $transactionInfo['id_category'] . '" selected >' . $transactionInfo['id_category'] . '</option>';
                }
            }
            ?>
        </select>

        <br><label>Observações: </label><textarea name="remarks" cols="30" rows="4"><?php echo $transactionInfo['remarks']; ?></textarea>

        <p>&nbsp;</p>

        <div id="transaction">

            <br><label>Total calculado: </label><input id="total_amount" disabled="disabled" size="6" type="text" value="0.00">
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <label>Total: </label><input type="text" name="amount" size="6" value="<?php echo number_format($transactionInfo['amount'], 2, '.', ''); ?>">

            <div id="sortable_transaction_product">
                <?php
                $resource = false;
                if ($id_transaction !== null) {
                    $query = "SELECT * FROM transaction_product WHERE id_transaction = '$id_transaction' ORDER BY sort ASC";
                    $resource = DB::execute($query);
                }

                $itemInfo = false;
                $num_rows = (isset($_POST['id_product']) && count($_POST['id_product']) > 0 ? count($_POST['id_product']) : 0);
                $num_rows = (is_resource($resource) && mysql_num_rows($resource) > $num_rows ? mysql_num_rows($resource) : $num_rows);
                $min_rows = ($num_rows > (MIN_ROWS_PRODUCTS - 1) ? $num_rows + 1 : MIN_ROWS_PRODUCTS);
                for ($index = 0; (is_resource($resource) && ($itemInfo = mysql_fetch_array($resource))) || ($index < $min_rows); $index++) {
                    if (!$itemInfo) {
                        $itemInfo = array(
                            'id_transaction' => $id_transaction,
                            'id_product' => null,
                            'price' => '0.000',
                            'quantity' => '1'
                        );
                    }
                    // If there is a $_POST, data is not saved, so use it
                    $itemInfo['id_product'] = (isset($_POST['id_product'][$index]) ? $_POST['id_product'][$index] : $itemInfo['id_product']);
                    $itemInfo['price'] = (isset($_POST['price'][$index]) ? $_POST['price'][$index] : $itemInfo['price']);
                    $itemInfo['quantity'] = (isset($_POST['quantity'][$index]) ? $_POST['quantity'][$index] : $itemInfo['quantity']);

                    $itemInfo['price'] = (is_numeric($itemInfo['price']) ? $itemInfo['price'] : 0);
                    $itemInfo['quantity'] = (is_numeric($itemInfo['quantity']) ? $itemInfo['quantity'] : 0);

                    if ($itemInfo['id_product'] != 'none' || $index == $min_rows - 1) {
                        ?>
                        <div class="transaction_product">
                            <br><label>Produto: </label><select class="autocomplete_select auto_add" name="id_product[]">
                                <option value="none"></option>
                                <?php
                                $query_product = "
                                    SELECT id_product, reference, description 
                                    FROM product p 
                                        INNER JOIN entity e ON e.id_entity = p.id_entity 
                                        INNER JOIN account a ON a.id_account = e.id_account 
                                    WHERE p.id_entity = '" . $transactionInfo['id_entity'] . "' AND a.id_account = '$id_account' 
                                    ORDER BY p.reference ASC";
                                $resource_product = DB::execute($query_product);
                                if (is_resource($resource_product)) {
                                    $found_selected = false;
                                    while ($row_product = mysql_fetch_array($resource_product)) {
                                        $is_selected = ($itemInfo['id_product'] == $row_product['id_product'] ? ' selected ' : '' );
                                        echo '<option title="' . $row_product['description'] . '" value="' . $row_product['id_product'] . '" ' . $is_selected . '>' . $row_product['reference'] . '</option>';
                                        if ($is_selected != '') {
                                            // Found the selected option
                                            $found_selected = true;
                                        }
                                    }
                                    if (!$found_selected && $itemInfo['id_product'] != 'none') {
                                        // Didn't found selected. Create a new option
                                        echo '<option class="new_select_option" value="' . $itemInfo['id_product'] . '" selected >' . $itemInfo['id_product'] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <label>Preço: </label><input type="text" name="price[]" size="6" value="<?php echo number_format($itemInfo['price'], 3, '.', ''); ?>">
                            <label>Qtd: </label><input type="text" size="3" name="quantity[]" value="<?php echo number_format($itemInfo['quantity'], 3, '.', ''); ?>">
                            <label>Sub-total: </label><input class="sub_total" disabled="disabled" size="6" type="text" value="0.00">
                            <span class="sortable_handler ui-icon ui-icon-arrowthick-2-n-s"></span>
                            <br>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>

        <p>&nbsp;</p>
        <input type="submit" name="submit_form" value="Guardar">
        <p>&nbsp;</p>
    </form>

</div>

<?php
require 'template/footer.inc.php';
?>