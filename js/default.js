Number.prototype.round = function(precision) {
    precision = (typeof precision == 'number' ? precision : 0); // precision represents the number of decimal places
    
    /*
     * Este método tem um BUG em que adiciona lixo (casas décimais extra) ao valor do número
     *
    // http://www.javascriptkit.com/javatutors/round.shtml
    var multiplier = Math.pow(10, Math.abs(precision));
    return Math.round(this * multiplier) / multiplier;
    */
    
    return parseFloat(this.toFixed(precision));
}

$(document).ready(function(){
    
    // Check if the user changed something
    $('#sortable_transaction_product').live('sortstop', onFormChanged);
    $('#form_insert_transaction').find('input, textarea').live('change', onFormChanged);
    function onFormChanged(){
        $(window).on('beforeunload', function(){
            return 'Existem alterações não guardadas.\nTem a certeza que deseja sair sem guardar?';
        });
    }
    
    $('#form_insert_transaction').submit(function(){
        $(window).off('beforeunload');
    });

    
    $( ".autocomplete_select" ).combobox();

    $('#form_insert_transaction').submit(function(){
        $('input, textarea').blur(); // To force any combobox to update his value
        
        // NOTE: Faz delay ao submit do form para o caso de o autocomplete ainda não ter actualizado os valores das comboboxs
        if (typeof readyToSubmit != 'boolean' || readyToSubmit != true) {
            var form = this;
            setTimeout(function(){
                readyToSubmit = true;
                $(form).submit();
            }, 300);
            return false;
        }
    
        // Is ready to submit
        ;
        // Check if is valid
        // NOTE: Apenas valida depois de estar readyToSubmit porque o valor que falta actualizar numa combobox pode ser o causador da form not valid
        if (!validateForm_InsertTransaction()) {
            $('body').scrollTop(0);
            return false;
        }
    
        // Check if the total prices match
        var total_calculated = parseFloat($('#total_amount').val()).toFixed(2);
        var total_inserted = parseFloat($('#form_insert_transaction input[name="amount"]').val()).toFixed(2);
        if (total_inserted != total_calculated) {
            var confirmed = confirm('O total introduzido (' + total_inserted + ') é diferente do total calculado (' + total_calculated + ').\nTem a certeza que deseja guardar os dados?');
            return confirmed;
        }
    
        return true;
    });
    
    
    if ($('#table_edit_inline').length > 0) {
        
        var type = $('#table_edit_inline').data('type');
        var url = "ajax_functions?action=edit&type=" + type;
        
        $('#table_edit_inline div.editable').editable(url, { 
            type      : 'text',
            event     : 'click',
            submit    : 'OK',
            cancel    : 'Cancel',
            indicator : 'Saveing...',
            tooltip   : 'Click to edit...',
            select    : true,
            onblur    : 'cancel'
        });
        
        $('#table_edit_inline a.link_edit').click(function(e){
            // Edit link
            var id = $(this).attr('href');
            $(id).click();
            
            e.preventDefault();
            return false;
        });
        
        $('#table_edit_inline a.link_remove').click(function(e){
            // Remove link
            var id = $(this).attr('href').replace('#id_', '');
            
            // TODO: Remover itens da lista ??????? O que fazer caso existam outros itens que dependam destes ????
            
            e.preventDefault();
            return false;
        });
    }


    if (typeof graph_gasto_diario_data__mes_actual != 'undefined') {
        $.plot($(".graph_gasto_diario.mes_actual"), graph_gasto_diario_data__mes_actual, {
            series: {
                pie: { 
                    show: true
                }
            },
            legend: {
                show: false
            }
        });
    }


    if (typeof graph_gasto_diario_data__mes_anterior != 'undefined') {
        $.plot($(".graph_gasto_diario.mes_anterior"), graph_gasto_diario_data__mes_anterior, {
            series: {
                pie: { 
                    show: true
                }
            },
            legend: {
                show: false
            }
        });
    }
    
    
    if (typeof graph_gasto_categoria_ao_longo_ano != 'undefined') {
        // Example - http://people.iola.dk/olau/flot/examples/turning-series.html
        
        // hard-code color indices to prevent them from shifting as
        // countries are turned on/off
        var i = 0;
        $.each(graph_gasto_categoria_ao_longo_ano, function(key, val) {
            val.color = i;
            ++i;
        });
    
        // insert checkboxes 
        var choiceContainer = $("#graph_gasto_categoria_ao_longo_ano .choices_holder");
        $.each(graph_gasto_categoria_ao_longo_ano, function(key, val) {
            choiceContainer.append('<br/><label><input type="checkbox" checked="checked" name="' + key + '" id="id' + key + '">' + val.label + '</label>');
        });
        choiceContainer.find("input").click(plotAccordingToChoices);

    
        function plotAccordingToChoices() {
            var data = [];

            choiceContainer.find("input:checked").each(function () {
                var key = $(this).attr("name");
                if (key && graph_gasto_categoria_ao_longo_ano[key])
                    data.push(graph_gasto_categoria_ao_longo_ano[key]);
            });

            if (data.length > 0)
                $.plot($("#graph_gasto_categoria_ao_longo_ano .graph_holder"), data, {
                    lines: {
                        show: true
                    },
                    points: {
                        show: true
                    },
                    legend: {
                        noColumns: 4
                    },
                    grid: {
                        autoHighlight: true,
                        clickable: false,
                        hoverable: true
                    },
                    yaxis: {
                        min: 0
                    },
                    xaxis: {
                        mode: "time",
                        timeformat: "%y-%m"
                    }
                });
            $("#graph_gasto_categoria_ao_longo_ano .graph_holder").bind("plothover", function(event, pos, item){
                if (item && item.datapoint.length >= 2 && $('#graph_gasto_categoria_ao_longo_ano .value_tooltip').length) {
                    var date = new Date(item.datapoint[0]);
                    var text = item.series.label + " on " + (date.getFullYear() + "-" + date.getMonth()) + ": " + item.datapoint[1] + " &euro;";
                    $('#graph_gasto_categoria_ao_longo_ano .value_tooltip').html(text);
                //                    console.log(event);
                //                    console.log(item.series.label);
                //                    console.log(item.datapoint);
                }
            });
        }

        plotAccordingToChoices();
    }

});