$(document).ready(function(){

    $('select[name="budget_table[]"]').live("autocompleteselect", onTableTypeChange);
    $('select[name="budget_table[]"]').live("autocompletechange", onTableTypeChange);
    function onTableTypeChange(event, oldValue){

        var target = $(event.target);

        if (event.target.value == GLOBAL_TYPE_CATEGORY) {
            updateCategories(target);
        } else if (event.target.value == GLOBAL_TYPE_ENTITY) {
            updateEntities(target);
        } else {
            // Remove options
            removeOptions(target);
        }
    }


    $( "select.autocomplete_select" ).live("autocompleteselect", onAutocompleteChange);
    $( "select.autocomplete_select" ).live("autocompletechange", onAutocompleteChange);
    function onAutocompleteChange(event, oldValue) {

        // Check if is the last row to create a new one
        if($(this).parents(".budget_line").is(':last-child')) {
            clone_last_budget_line();
        }
    }


    // Auto add product row
    $('.budget_line:last select[name="id_budget[]"], .budget_line:last input[name="budget_table[]"], .budget_line:last input[name="budget_id_field[]"], .budget_line:last input[name="budget_amount[]"], .budget_line:last input[name="budget_timespan[]"]').live('change', function(){
        // Clone last line
        clone_last_budget_line();
    });

});

function clone_last_budget_line() {
    var newRow = $('.budget_line:last').clone(false);
    newRow.find('span.ui-combobox').remove();
    newRow.find('select[name="id_budget[]"]').val('none').show().combobox();
    newRow.find('select[name="budget_table[]"]').val('none').show().combobox();
    newRow.find('select[name="budget_id_field[]"]').val('none').show().combobox();
    newRow.find('input[name="budget_amount[]"]').val('0.000');
    newRow.find('select[name="budget_timespan[]"]').val('none').show().combobox();

    newRow.find('input[name="calculo_actual[]"]').val('N/A');
    newRow.find('input[name="calculo_passado[]"]').val('N/A');
    newRow.find('input[name="calculo_media_global[]"]').val('N/A');

    $('.budget_line:last').parent().append(newRow);
    
    // Remove options
    removeOptions(newRow.find('select[name="budget_table[]"]'));
}

function updateCategories(target) {

    // Unselect the categories
    var budget_id_field = $(target).nextAll('select[name="budget_id_field[]"]');
    console.log(budget_id_field.val("none").nextAll('.ui-combobox:first').children('input').val(''));

    $.ajax({
        cache: false,
        type: 'GET',
        dataType: 'json',
        url: "ajax_functions?action=categories",
        success: function(data, textStatus, jqXHR) {
            if (data != false) {

                var categories = data;

                budget_id_field.find('option:not([value="none"])').remove();
                for (var index in categories) {
                    var option = $('<option title="' + categories[index]['remarks'] + '" value="' + categories[index]['id_category'] + '">' + categories[index]['name'] + '</option>');
                    budget_id_field.append(option);
                }
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log("An error occured while loading categories from server", "textStatus: " + textStatus, "errorThrown: " + errorThrown, "responseText: " + jqXHR.responseText);
            alert("An error occured while loading categories from server");
        }
    });
}

function updateEntities(target) {

    // Unselect the entities
    var budget_id_field = $(target).nextAll('select[name="budget_id_field[]"]');
    console.log(budget_id_field.val("none").nextAll('.ui-combobox:first').children('input').val(''));

    $.ajax({
        cache: false,
        type: 'GET',
        dataType: 'json',
        url: "ajax_functions?action=entities",
        success: function(data, textStatus, jqXHR) {
            if (data != false) {

                var entities = data;

                budget_id_field.find('option:not([value="none"])').remove();
                for (var index in entities) {
                    var option = $('<option title="' + entities[index]['remarks'] + '" value="' + entities[index]['id_entity'] + '">' + entities[index]['name'] + '</option>');
                    budget_id_field.append(option);
                }
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log("An error occured while loading entities from server", "textStatus: " + textStatus, "errorThrown: " + errorThrown, "responseText: " + jqXHR.responseText);
            alert("An error occured while loading entities from server");
        }
    });
}

function removeOptions(target) {

    // Unselect the entities
    var budget_id_field = $(target).nextAll('select[name="budget_id_field[]"]');
    console.log(budget_id_field.val("none").nextAll('.ui-combobox:first').children('input').val(''));

    budget_id_field.find('option:not([value="none"])').remove();
}
