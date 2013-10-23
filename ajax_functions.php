<?php

require_once 'resources/init.php';

User::renewSession();

$id_account = User::getAccountInUse();

if ($id_account === FALSE) {
    // Not logged in
    echo json_encode(FALSE);
    exit;
}

// Get action
$action = (isset($_GET['action']) ? $_GET['action'] : null);
switch ($action) {

    case 'categories':
        $query = "
            SELECT id_category, name, remarks
            FROM category c 
                INNER JOIN account a ON a.id_account = c.id_account 
            WHERE a.id_account = '$id_account' 
            ORDER BY c.name ASC";
        $resource = DB::execute($query);
        if (is_resource($resource)) {
            $categories = array();
            while ($row = mysql_fetch_array($resource)) {
                $categories[] = array(
                    'id_category' => $row['id_category'],
                    'name' => $row['name'],
                    'remarks' => $row['remarks']
                );
            }
            echo json_encode($categories);
            exit;
        }
        break;

    case 'entities':
        $query = "
            SELECT id_entity, name, remarks
            FROM entity e 
                INNER JOIN account a ON a.id_account = e.id_account 
            WHERE a.id_account = '$id_account' 
            ORDER BY e.name ASC";
        $resource = DB::execute($query);
        if (is_resource($resource)) {
            $entities = array();
            while ($row = mysql_fetch_array($resource)) {
                $entities[] = array(
                    'id_entity' => $row['id_entity'],
                    'name' => $row['name'],
                    'remarks' => $row['remarks']
                );
            }
            echo json_encode($entities);
            exit;
        }
        break;

    case 'products':

        $id_entity = null;
        if (isset($_GET['id'])) {
            $id_entity = mysql_real_escape_string($_GET['id']);

            $query = "
                SELECT id_category 
                FROM transaction t 
                    INNER JOIN entity e ON e.id_entity = t.id_entity 
                    INNER JOIN account a ON a.id_account = e.id_account 
                WHERE t.id_entity = '$id_entity' AND a.id_account = '$id_account' 
                ORDER BY t.transaction_date DESC, t.last_update DESC, t.insert_date DESC 
                LIMIT 0,1";
            $resource = DB::execute($query);
            $categoryInfo = DB::toArray($resource);
            if (is_array($categoryInfo)) {
                $id_category = $categoryInfo['id_category'];
            } else {
                $id_category = -1;
            }

            $query = "
                SELECT id_product, reference, description, 
                    (SELECT price FROM transaction_product p LEFT JOIN transaction t ON p.id_transaction = t.id_transaction WHERE id_product = product.id_product ORDER BY t.transaction_date DESC, t.last_update DESC, t.insert_date DESC LIMIT 0,1) AS last_price
                FROM product 
                    INNER JOIN entity e ON e.id_entity = product.id_entity 
                    INNER JOIN account a ON a.id_account = e.id_account 
                WHERE product.id_entity = '$id_entity' AND a.id_account = '$id_account' 
                ORDER BY product.reference ASC";
            $resource = DB::execute($query);
            if (is_resource($resource)) {
                $products = array();
                while ($row = mysql_fetch_array($resource)) {
                    $products[] = array(
                        'id_product' => $row['id_product'],
                        'reference' => $row['reference'],
                        'description' => $row['description'],
                        'last_price' => $row['last_price']
                    );
                }
                echo json_encode(array($products, $id_category));
                exit;
            }
        }
        break;

    case 'edit':

        $id = (isset($_POST['id']) ? $_POST['id'] : 'id_');
        $id = str_replace('id_', '', $id);

        $available_tables = array(
            'categories' => array(
                'table_name' => 'category',
                'value_field' => 'name',
                'where_clause' => " id_category = '$id' AND id_account = '$id_account' "
            ),
            'entities' => array(
                'table_name' => 'entity',
                'value_field' => 'name',
                'where_clause' => " id_entity = '$id' AND id_account = '$id_account' "
            ),
            'products' => array(
                'table_name' => 'product',
                'value_field' => 'reference',
                'where_clause' => " id_product = '$id' ",
                'confirm_query' => "SELECT COUNT(*) FROM entity e INNER JOIN product p ON p.id_entity = e.id_entity WHERE id_product = '$id' AND e.id_account = '$id_account' > 0"
            )
        );

        $type = (isset($_GET['type']) ? $_GET['type'] : null);
        $table_data = (array_key_exists($type, $available_tables) ? $available_tables[$type] : null);
        if ($table_data) {

            if (isset($table_data['confirm_query']) && ($resource = DB::execute($table_data['confirm_query'])) && is_resource($resource) && mysql_result($resource, 0, 0)) {

                $value = (isset($_POST['value']) ? $_POST['value'] : null);
                $value = htmlentities($value, ENT_COMPAT, 'UTF-8');

                // Save data if is setted
                if (trim($id) != '' && $value != null) {
                    $dataToUpdate = array(
                        $table_data['value_field'] => $value
                    );
                    DB::update($table_data['table_name'], $dataToUpdate, $table_data['where_clause']);
                    echo DB::$last_error_message;
                }

                // Get the current value in the database to return
                $resource = DB::execute("SELECT {$table_data['value_field']} FROM {$table_data['table_name']} WHERE {$table_data['where_clause']}");
                if (is_resource($resource) && mysql_num_rows($resource) > 0) {
                    echo mysql_result($resource, 0, 0);
                    exit;
                }
            }
        }
        break;

    default:
        break;
}

echo json_encode(FALSE);
exit;
?>