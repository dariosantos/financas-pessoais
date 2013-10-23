<?php
require_once 'resources/init.php';

$id_account = User::getAccountInUse();

define('MIN_ROWS_BUDGETS', 3);

define('TYPE_CATEGORY', 1);
define('TYPE_ENTITY', 2);

define('TIMESPAN_DAY', 1);
define('TIMESPAN_WEEK', 2);
define('TIMESPAN_MONTH', 3);
define('TIMESPAN_YEAR', 4);

$error_message = '';

// Handle form submits
if (!empty($_POST)) {

//    var_dump($_POST);

    try {
        // Validate data
        if (trim($error_message) != '') {
            throw new Exception("Dados inválidos: $error_message");
        }

        $general_counter = 1;

        // For each form row
        for ($index = 0; $index < count($_POST['budget_table']); $index++) {

            $id_budget = htmlentities($_POST['id_budget'][$index], ENT_COMPAT, 'UTF-8');
            $budget_table = htmlentities($_POST['budget_table'][$index], ENT_COMPAT, 'UTF-8');
            $budget_id_field = htmlentities($_POST['budget_id_field'][$index], ENT_COMPAT, 'UTF-8');
            $budget_amount = htmlentities($_POST['budget_amount'][$index], ENT_COMPAT, 'UTF-8');
            $budget_timespan = htmlentities($_POST['budget_timespan'][$index], ENT_COMPAT, 'UTF-8');

            if ($budget_table != 'none' && $budget_id_field != 'none' && is_numeric($budget_amount) && $budget_timespan != 'none') {

                // Add this budget
                $dataToSave = array(
                    'id_account' => $id_account,
                    'table_type' => $budget_table,
                    'id_field' => $budget_id_field,
                    'amount' => $budget_amount,
                    'timespan' => $budget_timespan,
                    'sort' => $general_counter
                );

                if (trim($id_budget) != '' && is_numeric($id_budget)) {
                    // Update
                    if (!DB::update('budget', $dataToSave, " id_budget = '$id_budget' ")) {
                        throw new Exception("ERROR on update budget ($id_budget, $id_account, $budget_table, $budget_id_field, $budget_amount, $budget_timespan, $general_counter)");
                    } else {
                        $general_counter++;
                    }
                } else {
                    // insert
                    if (!DB::insert('budget', $dataToSave)) {
                        throw new Exception("ERROR on insert budget ($id_account, $budget_table, $budget_id_field, $budget_amount, $budget_timespan, $general_counter)");
                    } else {
                        $general_counter++;
                    }
                }
            }
        }
    } catch (Exception $e) {
        DB::rollbackTransaction();
        $error_message = "ERROR: {$e->getMessage()}.<p>MYSQL: " . DB::$last_error_message . "</p>";
    }
}

function getTimespanMysqlFunction($timespan) {

    // Get the timespan mysql function
    $timespan_mysql_function = '';
    switch ($timespan) {
        case TIMESPAN_DAY:
            $timespan_mysql_function = 'DAYOFYEAR';
            break;
        case TIMESPAN_WEEK:
            $timespan_mysql_function = 'WEEKOFYEAR';
            break;
        case TIMESPAN_MONTH:
            $timespan_mysql_function = 'MONTH';
            break;
        case TIMESPAN_YEAR:
            $timespan_mysql_function = 'YEAR';
            break;

        default:
            return FALSE;
            break;
    }

    return $timespan_mysql_function;
}

function getDefaultTableType() {

    $id_account = User::getAccountInUse();

    $query = "
            SELECT COUNT(b.table_type) AS counter, b.table_type AS table_type
            FROM budget b 
                INNER JOIN account a ON a.id_account = b.id_account 
            WHERE a.id_account = '$id_account'
            GROUP BY table_type
            ORDER BY counter DESC
            LIMIT 0,1
    ";
    $resource = DB::execute($query);
    if (is_resource($resource) && ($row = mysql_fetch_array($resource)) && isset($row['table_type'])) {
        return $row['table_type'];
    }

    return false;
}

function getDefaultTimespan() {

    $id_account = User::getAccountInUse();

    $query = "
            SELECT COUNT(timespan) AS counter, timespan
            FROM budget b 
                INNER JOIN account a ON a.id_account = b.id_account 
            WHERE a.id_account = '$id_account'
            GROUP BY timespan
            ORDER BY counter DESC
            LIMIT 0,1
    ";
    $resource = DB::execute($query);
    if (is_resource($resource) && ($row = mysql_fetch_array($resource)) && isset($row['timespan'])) {
        return $row['timespan'];
    }

    return false;
}

function getBudgetFields($table_type) {

    // Gets the correct table
    $table_name = '';
    $id_field = '';
    $option_name = '';
    $option_description = '';

    switch ($table_type) {
        case TYPE_CATEGORY:
            $table_name = 'category';
            $id_field = 'id_category';
            $option_name = 'name';
            $option_description = 'remarks';
            break;

        case TYPE_ENTITY:
            $table_name = 'entity';
            $id_field = 'id_entity';
            $option_name = 'name';
            $option_description = 'remarks';
            break;

        default:
            return FALSE;
            break;
    }

    return array($table_name, $id_field, $option_name, $option_description);
}

function getBudgetTableOptions($table_type) {

    $id_account = User::getAccountInUse();

    $budget_fields = getBudgetFields($table_type);
    if ($budget_fields === FALSE) {
        return false;
    }
    list($table_name, $id_field, $option_name, $option_description) = $budget_fields;

    // Gets the rows from the $table_name
    $query = "
            SELECT $id_field AS id_field, $option_name AS option_name, $option_description AS option_description 
            FROM $table_name AS table_name 
                INNER JOIN account a ON a.id_account = table_name.id_account 
            WHERE a.id_account = '$id_account' 
            ORDER BY option_name ASC";
    $resource = DB::execute($query);

    return $resource;
}

function getBudgetCurrentValues($table_type, $table_id_value, $timespan) {

    $id_account = User::getAccountInUse();

    $budget_fields = getBudgetFields($table_type);
    if ($budget_fields === FALSE) {
        return array('', '', '');
    }
    list($table_name, $id_field,, ) = $budget_fields;


    $timespan_mysql_function = getTimespanMysqlFunction($timespan);
    if ($timespan_mysql_function === FALSE) {
        return array('', '', '');
    }

    $query = "
            SELECT ROUND( SUM(t.amount), 2) AS total, $timespan_mysql_function( t.transaction_date ) AS time_span, $timespan_mysql_function( CURRENT_TIMESTAMP() ) AS now 
            FROM $table_name AS table_name 
                INNER JOIN transaction t ON t.$id_field = table_name.$id_field 
                INNER JOIN account a ON a.id_account = table_name.id_account 
            WHERE a.id_account = '$id_account' AND table_name.$id_field = '$table_id_value' 
            GROUP BY time_span
            ORDER BY time_span DESC
    ";
//    var_dump($query);
    $resource = DB::execute($query);

    $actual = '';
    $passado = '';
    $media = '';
    $total = 0;
    $contador = 0;
    if (is_resource($resource)) {
        while ($row = mysql_fetch_array($resource)) {
            $contador++;

            $now = $row['now'];
            $time_span = $row['time_span'];
            $amount = $row['total'];

            if ($time_span == $now) {
                $actual = $amount;
            } else if ($time_span == ($now - 1)) {
                $passado = $amount;
            }

            $total += $amount;
        }
    } else {
        return array('', '', '');
    }

    if ($contador > 0) {
        $media = $total / $contador;
    }

    return array($actual, $passado, $media);
}

require 'template/header.inc.php';
?>

<header>
    Orçamento
</header>
<div role="main">
    
    <form id="form_budget" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">

        <p>&nbsp;</p>

        <div id="form_feedback"><?php echo (trim($error_message) != '' ? $error_message : '&nbsp;'); ?></div>

        <div>

            <?php
            $query = "SELECT * FROM budget WHERE id_account = '$id_account' ORDER BY sort ASC";
            $resource = DB::execute($query);

            $default_table_type = getDefaultTableType();
            $default_table_type = ($default_table_type !== FALSE ? $default_table_type : TYPE_CATEGORY);

            $default_timespan = getDefaultTimespan();
            $default_timespan = ($default_timespan !== FALSE ? $default_timespan : TIMESPAN_MONTH);

            $budgetInfo = false;
            $num_rows = (is_resource($resource) ? mysql_num_rows($resource) : 0);
            $min_rows = ($num_rows > (MIN_ROWS_BUDGETS - 1) ? $num_rows + 1 : MIN_ROWS_BUDGETS);
            for ($index = 0; (is_resource($resource) && ($budgetInfo = mysql_fetch_array($resource))) || ($index < $min_rows); $index++) {
                if (!$budgetInfo) {
                    $budgetInfo = array(
                        'id_budget' => null,
                        'table_type' => $default_table_type,
                        'id_field' => null,
                        'amount' => '0.000',
                        'timespan' => $default_timespan
                    );
                }
                list($actual, $passado, $media) = getBudgetCurrentValues($budgetInfo['table_type'], $budgetInfo['id_field'], $budgetInfo['timespan']);
                $actual = (trim($actual) != '' ? number_format($actual, 3, '.', '') : 'N/A');
                $passado = (trim($passado) != '' ? number_format($passado, 3, '.', '') : 'N/A');
                $media = (trim($media) != '' ? number_format($media, 3, '.', '') : 'N/A');
                ?>
                <div class="budget_line">

                    <input type="hidden" name="id_budget[]" value="<?php echo $budgetInfo['id_budget']; ?>">

                    <br><label>Tipo: </label><select class="autocomplete_select" name="budget_table[]">
                        <option value="none"></option>
                        <option value="<?php echo TYPE_CATEGORY; ?>" <?php echo (intval($budgetInfo['table_type']) == TYPE_CATEGORY ? ' selected ' : '' ); ?>>Categoria</option>
                        <option value="<?php echo TYPE_ENTITY; ?>" <?php echo (intval($budgetInfo['table_type']) == TYPE_ENTITY ? ' selected ' : '' ); ?>>Entidade</option>
                    </select>

                    <label>Alvo: </label><select class="autocomplete_select" name="budget_id_field[]" <?php echo ($budgetInfo['table_type'] == null ? 'disabled' : ''); ?>>
                        <option value="none"></option>
                        <?php
                        // Tabela seleccionada. Vamos buscar as escolhas possíveis à BD
                        $resource_budget = getBudgetTableOptions($budgetInfo['table_type']);
                        if (is_resource($resource_budget)) {
                            while ($row_budget = mysql_fetch_array($resource_budget)) {
                                echo '<option title="' . $row_budget['option_description'] . '" value="' . $row_budget['id_field'] . '" ' . ($budgetInfo['id_field'] == $row_budget['id_field'] ? ' selected ' : '' ) . '>' . $row_budget['option_name'] . '</option>';
                            }
                        }
                        ?>
                    </select>

                    <label>Disponível: </label><input type="text" name="budget_amount[]" size="6" value="<?php echo number_format($budgetInfo['amount'], 3, '.', ''); ?>">

                    <label>Cada: </label><select class="autocomplete_select" name="budget_timespan[]">
                        <option value="none"></option>
                        <option value="<?php echo TIMESPAN_DAY; ?>" <?php echo (intval($budgetInfo['timespan']) == TIMESPAN_DAY ? ' selected ' : '' ); ?>>Dia</option>
                        <option value="<?php echo TIMESPAN_WEEK; ?>" <?php echo (intval($budgetInfo['timespan']) == TIMESPAN_WEEK ? ' selected ' : '' ); ?>>Semana</option>
                        <option value="<?php echo TIMESPAN_MONTH; ?>" <?php echo (intval($budgetInfo['timespan']) == TIMESPAN_MONTH ? ' selected ' : '' ); ?>>Mês</option>
                        <option value="<?php echo TIMESPAN_YEAR; ?>" <?php echo (intval($budgetInfo['timespan']) == TIMESPAN_YEAR ? ' selected ' : '' ); ?>>Ano</option>
                    </select>

                    <br>
                    <label>Actual: </label><input type="text" disabled="disabled" name="calculo_actual[]" size="6" value="<?php echo $actual; ?>">
                    <label>Passado: </label><input type="text" disabled="disabled" name="calculo_passado[]" size="6" value="<?php echo $passado; ?>">
                    <label>Média global: </label><input type="text" disabled="disabled" name="calculo_media_global[]" size="6" value="<?php echo $media; ?>">

                    <br><br>
                </div>

                <?php
            }
            ?>

        </div>

        <p>&nbsp;</p>
        <input type="submit" name="submit_form" value="Guardar">
        <p>&nbsp;</p>
    </form>

</div>

        <script>
            GLOBAL_TYPE_CATEGORY = '<?php echo TYPE_CATEGORY; ?>';
            GLOBAL_TYPE_ENTITY = '<?php echo TYPE_ENTITY; ?>';
        </script>
<?php
require 'template/footer.inc.php';
?>