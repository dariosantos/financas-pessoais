<?php
require_once 'resources/init.php';

$id_account = User::getAccountInUse();

require 'template/header.inc.php';
?>

<header>
    Estatísticas
</header>
<div role="main">
    
    <?php
    // Gastos por categoria ao longo do ano
    $query = "
                SELECT name as category, ROUND( SUM(AMOUNT), 2) as total, CONCAT(YEAR(transaction_date), '-', LPAD(MONTH(transaction_date), 2, '0') ) AS date 
                FROM transaction t INNER JOIN category c ON t.id_category = c.id_category 
                        INNER JOIN account a ON a.id_account = c.id_account 
                WHERE a.id_account = '$id_account' AND type = 2 
                GROUP BY t.id_category, date
                ORDER BY t.id_category, date";
    $resource = DB::execute($query);
    if (is_resource($resource)) {

        echo '<h2>Gastos por categoria ao longo do ano: </h2>';
        ?>
        <div id="graph_gasto_categoria_ao_longo_ano">
            <div class="value_tooltip"></div>
            <div class="graph_holder"></div>
            <div class="choices_holder"></div>
        </div>
        <script>
    <?php
    $categories = array();
//    $months = array();
//    $data = "";
//    $current_category = '';
//    $max_value = -99999999;
//    $min_value = 99999999;
//    $total_value = 0;
//    $number_values = 0;
//    $max_month = -99;
//    $min_month = 99;
    $collected_data = array();
    while ($row = mysql_fetch_assoc($resource)) {

        // Init arrays if necessary
        if (!in_array($row['category'], $categories)) {
            $categories[] = $row['category'];
        }
        $category_index = array_search($row['category'], $categories);

        $collected_data[$category_index] = (isset($collected_data[$category_index]) && is_array($collected_data[$category_index]) ? $collected_data[$category_index] : array());
        $collected_data[$category_index]['label'] = (isset($collected_data[$category_index]['label']) && is_array($collected_data[$category_index]['label']) ? $collected_data[$category_index]['label'] : array());
        $collected_data[$category_index]['data'] = (isset($collected_data[$category_index]['data']) && is_array($collected_data[$category_index]['data']) ? $collected_data[$category_index]['data'] : array());

        // Put data in
        $collected_data[$category_index]['label'] = $row['category'];
        $collected_data[$category_index]['data'][] = array((strtotime($row['date']) * 1000), floatval($row['total']));
    }
    usort($collected_data, function($a, $b) {
                return strcmp($a['label'], $b['label']);
            }
    );
    echo 'graph_gasto_categoria_ao_longo_ano = ' . json_encode($collected_data) . ';';
    ?>
        </script>
        <?php
    } else {
        var_dump(DB::$last_error_message);
    }
    ?>

    <br>
    <br>

    <?php
    // Gastos por categoria (mês actual)
    $query = "
                SELECT name as category, ROUND( SUM(AMOUNT), 2) as total, CONCAT(YEAR(transaction_date), '-', LPAD(MONTH(transaction_date), 2, '0') ) AS date 
                FROM transaction t INNER JOIN category c ON t.id_category = c.id_category 
                    INNER JOIN account a ON a.id_account = c.id_account 
                WHERE a.id_account = '$id_account' AND type = 2 AND CONCAT(YEAR(transaction_date), '-', LPAD(MONTH(transaction_date), 2, '0') )='" . date('Y') . '-' . (date('m')) . "' 
                GROUP BY t.id_category";
    $resource = DB::execute($query);
    if (is_resource($resource)) {

        echo '<h2>Gastos por categoria (mês actual): </h2>';
        ?>
        <div class="graph_gasto_diario mes_actual"></div>
        <script>
    <?php
    $data = "";
    while ($row = mysql_fetch_assoc($resource)) {
        $data .= "{ label: '{$row['category']}', data: {$row['total']} },";
    }
    ?>
                                                                                                                                        
        graph_gasto_diario_data__mes_actual = [<?php echo substr($data, 0, count($data) - 2); ?>];
        </script>
        <?php
    } else {
        var_dump(DB::$last_error_message);
    }
    ?>

    <br>
    <br>

    <?php
    // Gastos por categoria (mês passado)
    $query = "
                SELECT name as category, ROUND( SUM(AMOUNT), 2) as total, CONCAT(YEAR(transaction_date), '-', LPAD(MONTH(transaction_date), 2, '0') ) AS date 
                FROM transaction t INNER JOIN category c ON t.id_category = c.id_category 
                    INNER JOIN account a ON a.id_account = c.id_account 
                WHERE a.id_account = '$id_account' AND type = 2 AND CONCAT(YEAR(transaction_date), '-', LPAD(MONTH(transaction_date), 2, '0') )='" . date('Y') . '-' . (date('m', strtotime("-1 month"))) . "' 
                GROUP BY t.id_category";
    $resource = DB::execute($query);
    if (is_resource($resource)) {

        echo '<h2>Gastos por categoria (mês passado): </h2>';
        ?>
        <div class="graph_gasto_diario mes_anterior"></div>
        <script>
    <?php
    $data = "";
    while ($row = mysql_fetch_assoc($resource)) {
        $data .= "{ label: '{$row['category']}', data: {$row['total']} },";
    }
    ?>
                                                                                                                                        
        graph_gasto_diario_data__mes_anterior = [<?php echo substr($data, 0, count($data) - 2); ?>];
        </script>
        <?php
    } else {
        var_dump(DB::$last_error_message);
    }
    ?>

    <br>

    <?php
    // Mostra soma de gastos por mes e por categoria
    $query = "
                SELECT name as category, ROUND( SUM(AMOUNT), 2) as total, CONCAT(YEAR(transaction_date), '-', LPAD(MONTH(transaction_date), 2, '0') ) AS date 
                FROM transaction t LEFT JOIN category c ON t.id_category = c.id_category 
                    INNER JOIN account a ON a.id_account = c.id_account 
                WHERE a.id_account = '$id_account' AND type = 2 GROUP BY t.ID_CATEGORY, date 
                ORDER BY date DESC, category ASC, total DESC";
    $resource = DB::execute($query);
    if (is_resource($resource)) {

        $totals = array();

        echo '<h2>Soma de gastos por mes e por categoria: </h2>';
        ?>
        <table border="1">
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th>Total</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = mysql_fetch_assoc($resource)) {
                    if (!isset($totals[$row['date']])) {
                        $totals[$row['date']] = 0;

                        if (isset($last_date)) {
                            ?>
                            <tr>
                                <td colspan="3"><?php echo $totals[$last_date]; ?> &euro;</td>
                            </tr>
                            <tr>
                                <td colspan="3">&nbsp;</td>
                            </tr>
                            <?php
                        }

                        $last_date = $row['date'];
                    }
                    $totals[$row['date']] += $row['total'];
                    ?>
                    <tr>
                        <td><?php echo $row['category']; ?></td>
                        <td><?php echo $row['total']; ?></td>
                        <td><?php echo $row['date']; ?></td>
                    </tr>
                    <?php
                }

                if (isset($last_date)) {
                    ?>
                    <tr>
                        <td colspan="3"><?php echo $totals[$last_date]; ?> &euro;</td>
                    </tr>
                    <tr>
                        <td colspan="3">&nbsp;</td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>

        <?php
    } else {
        var_dump(DB::$last_error_message);
    }
    ?>

</div>

<?php
require 'template/footer.inc.php';
?>