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

    <div id="table_edit_inline_filters">
        Filter:&nbsp;<input type="text" name="filter" value="" /> <button>Filter</button>
    </div>

    <script>
        String.prototype.stripAccents = function() {
            var translate_re = /[àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ]/g;
            var translate = 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY';
            return (this.replace(translate_re, function(match){
                return translate.substr(translate_re.source.indexOf(match)-1, 1);
            }));
        };
        jQuery.expr[':'].contains_i = function(a, i, m) {
            return jQuery(a).text().stripAccents().toUpperCase()
            .indexOf(m[3].stripAccents().toUpperCase()) >= 0;
        };
        
        timer_update_filter_results = null;
        function update_filter_results() {
            var filter_value = $('#table_edit_inline_filters input[type="text"]').val();
            
            if ($.trim(filter_value) != '') {
                $('#table_edit_inline tbody td').parent().hide();
                $('#table_edit_inline tbody td:contains_i(' + filter_value + ')').parent().show();
            } else {
                $('#table_edit_inline tbody td').parent().show();
            }
        }
        
        $(document).ready(function(){
            $('#table_edit_inline_filters button').click(update_filter_results);
            $('#table_edit_inline_filters input[type="text"]').keyup(function(){
                clearInterval(timer_update_filter_results);
                timer_update_filter_results = setTimeout(update_filter_results, 600);
            });
        });
    </script>

    <table id="table_edit_inline" data-type="products">
        <thead>
            <tr>
                <th>Entity Name</th>
                <th>Reference</th>
<!--                <th>Description</th>-->
                <th>Edit</th>
<!--                <th>Remove</th>-->
            </tr>
        </thead>

        <tbody>
            <?php
            $query = "
                SELECT id_product, reference, description, e.name AS entity_name 
                FROM product p 
                    INNER JOIN entity e ON e.id_entity = p.id_entity 
                    INNER JOIN account a ON a.id_account = e.id_account 
                WHERE a.id_account = '$id_account' 
                ORDER BY e.name ASC, p.reference ASC";
            $resource = DB::execute($query);
            if (is_resource($resource)) {
                $found_selected = mysql_num_rows($resource);
                while ($row = mysql_fetch_array($resource)) {
                    echo '<tr>';
                    echo '<td>' . $row['entity_name'] . '</td>';
                    echo '<td><div id="id_' . $row['id_product'] . '" class="editable">' . $row['reference'] . '</div></td>';
//                    echo '<td><div id="id_' . $row['id_product'] . '" class="editable">' . $row['description'] . '</div></td>';
                    echo '<td><a href="#id_' . $row['id_product'] . '" class="link_edit">Edit</a></td>';
//                    echo '<td><a href="#id_' . $row['id_product'] . '" class="link_remove">Remove</a></td>';
                    echo '</tr>';
                }
                if ($found_selected <= 0) {
                    echo '<tr><td colspan="5">Nenhum produto encontrado</td></tr>';
                }
            }
            ?>
        </tbody>
    </table>

</div>

<?php
require 'template/footer.inc.php';
?>