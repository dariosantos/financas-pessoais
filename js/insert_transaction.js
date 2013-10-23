products_price = [];

$(document).ready(function(){

    // Update the Products when an entity is already defined
    var id_entity = $('select[name="id_entity"]').val();
    if ($.trim(id_entity) != '') {
        updateProducts(id_entity, true);
    }

    // Auto add product row
    $('.transaction_product:last select[name="id_product[]"], .transaction_product:last input[name="price[]"], .transaction_product:last input[name="quantity[]"]').live('change', function(){
        // Clone last line
        clone_last_transaction_product();
    });


    $('#form_insert_transaction input[name="amount"]').live('change', function(){
        checkTotalPrice();
    });

    // Update total amount fields
    calculate_prices();
    $('.transaction_product input[name="price[]"]').add('.transaction_product input[name="quantity[]"]').live('change', function(){
        calculate_prices();
    });

    ignoreInputLetters($('.transaction_product input[name="price[]"]'), '0.000');
    ignoreInputLetters($('.transaction_product input[name="quantity[]"]'), '1.000');
    $('#sortable_transaction_product').sortable({
        handle: ".sortable_handler"
    });

    $( "select.autocomplete_select" ).live("autocompleteselect", onAutocompleteChange);
    $( "select.autocomplete_select" ).live("autocompletechange", onAutocompleteChange);
    function onAutocompleteChange(event, oldValue) {

        // Check if is the last row to create a new one
        if($(this).parents(".transaction_product").is(':last-child')) {
            clone_last_transaction_product();
        }

        calculate_prices();
    }


    $('select[name="id_entity"]').bind("autocompleteselect", onEntityChange);
    $('select[name="id_entity"]').bind("autocompletechange", onEntityChange);
    function onEntityChange(event, oldValue){
        var id_entity = (event.target.value ? event.target.value : -1);
        if (id_entity != oldValue) {
            updateProducts(id_entity);
        }
    }

    $('select[name="id_product[]"]').live("autocompleteselect", onProductChange);
    $('select[name="id_product[]"]').live("autocompletechange", onProductChange);
    function onProductChange(event, oldValue) {
        var id_product = (event.target.value ? event.target.value : -1);
        var last_price = 0.000;
        if (id_product != -1 && typeof products_price[id_product] != "undefined") {
            last_price = parseFloat(products_price[id_product].replace(",", "."));
        }
        if (isNaN(last_price)) {
            last_price = 0.000;
        }

        var price_input = $(this).nextAll('input[name="price[]"]');
        if (isNaN(parseFloat(price_input.val().replace(",", "."))) || parseFloat(price_input.val().replace(",", ".")) == 0) {
            price_input.val(last_price.toFixed(3));
        }

        if (price_input.is(':focus')) {
            price_input.select();
        }

        calculate_prices();
    }

    $('input[name="price[]"]').live("change", function(event, oldValue) {
        var current_price = parseFloat($(this).val().replace(",", "."));
        if (!isNaN(current_price)) {

            // Update products_price
            var id_product = $(this).prevAll('select[name="id_product[]"]').val();
            if ($.trim(id_product) != "" && !isNaN(parseFloat(id_product))) {
                if (id_product >= 1 && typeof products_price[id_product] != "undefined") {
                    products_price[id_product] = "" + current_price;
                }
            }
        }
    });

    // Calendario
    //http://jqueryui.com/demos/datepicker/#option-firstDay
    $(".datepicker").datetimepicker({
        ampm: false,
        timeFormat: 'hh:mm',
        separator: ' ',
        dateFormat: "yy-mm-dd",
        addSliderAccess: true,
        sliderAccessArgs: {
            touchonly: false
        },
        onSelect: function(dateText, inst) {
        //            // Coloca as horas na data seleccionada
        //            var lastHour = inst.lastVal.toString().split(' ', 2);
        //            if (lastHour.length >= 2 && $.trim(lastHour[1]) != '') {
        //                lastHour = lastHour[1];
        //            } else {
        //                lastHour = (new Date()).getHours() + ":" + (new Date()).getMinutes()
        //            }
        //            dateText = dateText + " " + lastHour;
        //            $(this).val(dateText);
        },
        beforeShow: function(input, inst) {
        //            $(input).blur()
        },
        firstDay: 1, // Monday
        showOtherMonths: true,
        selectOtherMonths: true,
        showOn: "button",
        buttonImage: "img/calendar.gif",
        buttonImageOnly: true
    });
});

function calculate_prices() {
    // Sum the prices
    var total_amount = 0;
    $('.transaction_product .sub_total').each(function(){
        var id_product = $(this).siblings('select[name="id_product[]"]').val();
        if (id_product == "none") {
            $(this).val('0.00');
        } else {
            var price = parseFloat($(this).siblings('input[name="price[]"]').val().replace(",", "."));
            price = (!isNaN(price) ? price : 0);
            var quantity = parseFloat($(this).siblings('input[name="quantity[]"]').val().replace(",", "."));
            quantity = (!isNaN(quantity) ? quantity : 1);
            var total = Number(price * quantity).round(2); // Number to make sure uses the custom function Number.prototype.round
            $(this).val(total.toFixed(2));
            total_amount += total;
        }
    });
    $('#total_amount').val(total_amount.toFixed(2));

    checkTotalPrice();
}

function checkTotalPrice() {
    // Highlights the total amount field when it's value is different from the total calculated
    var input = $('#form_insert_transaction input[name="amount"]');
    
    if (input.length > 0){
        input.removeClass('paid_more').removeClass('paid_less');

        var total_amount = parseFloat(input.val().replace(",", "."));
        var total_calculated = parseFloat($('#total_amount').val().replace(",", "."));

        if (total_amount > total_calculated) {
            input.addClass('paid_more');
        } else if (total_amount < total_calculated) {
            input.addClass('paid_less');
        }
    }
}

function clone_last_transaction_product() {
    var newRow = $('.transaction_product:last').clone(false);
    newRow.find('span.ui-combobox').remove();
    newRow.find('select[name="id_product[]"]').val('none').show().combobox();
    newRow.find('input[name="price[]"]').val('0.000');
    newRow.find('input[name="quantity[]"]').val('1.000');
    $('.transaction_product:last').parent().append(newRow);

    ignoreInputLetters($('input[name="price[]"]', newRow), '0.000');
    ignoreInputLetters($('input[name="quantity[]"]', newRow), '1.000');
    $('#sortable_transaction_product').sortable('refresh');
}

function validateForm_InsertTransaction() {
    var element;
    var value;

    // Validate data to submit from #form_insert_transaction

    var form_errors = false;
    var form_feedback = "";
    $('#form_insert_transaction *').removeClass("validation_failed");

    // Valida o tipo de transacção
    element = $('#form_insert_transaction select[name="type"]');
    if (!validateCombobox(element)) {
        form_errors = true;
        form_feedback += "<br>Escolha um tipo de transacção válido. (Crédito: receber / Débito: pagar)";
        $(element).prev('label').addClass("validation_failed");
    }

    // Valida a entidade da transacção
    element = $('#form_insert_transaction select[name="id_entity"]');
    if (!validateCombobox(element)) {
        form_errors = true;
        form_feedback += "<br>Escolha uma entidade válida. Representa de onde OU para onde foi o valor";
        $(element).prev('label').addClass("validation_failed");
    }

    // Valida a data da transacção
    element = $('#form_insert_transaction input[name="transaction_date"]');

    var temp = element.val().split(' ');
    if (temp.length < 2 || $.trim(temp[0]) == "" || $.trim(temp[1]) == "" || isNaN(Date.parse(temp[0])) || temp[1].toString().match(/\d{2}:\d{2}/) === null) {
        form_errors = true;
        form_feedback += "<br>Introduza uma data válida. Formato: yyyy-mm-dd hh:mm";
        $(element).prev('label').addClass("validation_failed");
    }

    // Valida o valor total da transacção
    element = $('#form_insert_transaction input[name="amount"]');
    value = element.val().replace(",", ".");
    if ($.trim(value) == "" || isNaN(parseFloat(value.replace(",", ".")))) {
        form_errors = true;
        form_feedback += "<br>Introduza o valor total da transacção. Ex: 14.50";
        $(element).prev('label').addClass("validation_failed");
    }


    // Valida cada transacção
    $(".transaction_product").each(function(){
        if (validateCombobox($(this).find('select[name="id_product[]"]'))) {

            var preco = $(this).find('input[name="price[]"]');
            if ($.trim(preco.val()) == "" || isNaN(parseFloat(preco.val().replace(",", ".")))) {
                form_errors = true;
                form_feedback += "<br>Introduza o valor parcial da transacção. Ex: 2.95";
                $(preco).prev('label').addClass("validation_failed");
            }

            var quantidade = $(this).find('input[name="quantity[]"]');
            if ($.trim(quantidade.val()) == "" || isNaN(parseFloat(quantidade.val().replace(",", ".")))) {
                form_errors = true;
                form_feedback += "<br>Introduza a quantidade da transacção. Ex: 0.125 ou 2";
                $(quantidade).prev('label').addClass("validation_failed");
            }
        }
    // Válido. Basta ignorar esta transacção
    });


    $('#form_insert_transaction #form_feedback').html(form_feedback);

    if (form_errors) {
        return false;
    }

    return true; // Faz submit
}

function validateCombobox(combobox) {
    // Combobox must have a value different from "none" and not empty
    var value = $(combobox).val();
    if (value == "none" || $.trim(value) == "") {
        return false;
    }
    return true;
}

function updateProducts(id_entity, pricesOnly) {

    pricesOnly = (typeof pricesOnly != 'undefined' ? pricesOnly : false ); // Default is false

    $.ajax({
        cache: false,
        type: 'GET',
        dataType: 'json',
        url: "ajax_functions?action=products&id=" + id_entity,
        success: function(data, textStatus, jqXHR) {
            if (data != false && data.length >= 2) {
                var products = data[0];
                var id_category = data[1];

                // Update prices
                products_price = [];
                for (var product in products) {
                    products_price[products[product]['id_product']] = products[product]['last_price'];
                }

                if (!pricesOnly) {

                    // Change category
                    if ($.trim($('select[name="id_category"]').val()) == ''){
                        $('select[name="id_category"]').val(id_category);
                        var category_name = $('select[name="id_category"] option:selected').text();
                        $('select[name="id_category"]').nextAll('.ui-combobox').find('input').val(category_name);
                    }

                    $('select[name="id_product[]"] option:not([value="none"])').remove();
                    for (var product in products) {
                        var option = $('<option title="' + products[product]['description'] + '" value="' + products[product]['id_product'] + '">' + products[product]['reference'] + '</option>');
                        $('select[name="id_product[]"]').append(option);
                    }

                    //                    $('input[name="price[]"]').val('0.000');
                    //                    $('input[name="quantity[]"]').val('1.000');

                    // Update products
                    $('.transaction_product .ui-combobox input').each(function(){
                        $(this).data("autocomplete")._trigger("change");
                    });
                    
                    calculate_prices();
                }
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            // If jqXHR.status is 0 (zero), ignores the error -> User is just going away from the page
            if (jqXHR.status != 0) {
                console.log("An error occured while loading products from server", "id_entity: " + id_entity, "textStatus: " + textStatus, "errorThrown: " + errorThrown, "responseText: " + jqXHR.responseText);
                alert("An error occured while loading products from server");
            }
        }
    });
}

function ignoreInputLetters(inputs, default_value) {

    $(inputs).each(function(){
        var current_input = $(this);

        current_input.live('blur', function(e){
            var value = $(this).val().replace(',', '.');
            $(this).val(value);
            if (isNaN(value) || $.trim(value) == '') {
                playSound('sound_invalid_input');
                if ($(this).data('last_valid_value') != 'undefined') {
                    $(this).val($(this).data('last_valid_value'));
                } else {
                    $(this).val(default_value);
                }
                calculate_prices();
            }
        });

        current_input.live('focus', function(e){
            if (!isNaN($(this).val()) && $.trim($(this).val()) != '') {
                $(this).data('last_valid_value', $(this).val());
            }
        });

        // Auto select next product when typing letters in this field
        current_input.live('input', function(e){
            var current_value = $(this).val().replace(",", ".");
            var letters = current_value.match(/[^0-9.\-\+]/g);
            if (current_value != '.' && isNaN(current_value) && letters != null) {

                playSound('sound_invalid_input');

                $(this).val(current_value.replace(/[^0-9.\-\+]/g, ''));

                // Focus the next line input (letters are not valid in this field)
                var nextLine = $(this).parent().nextAll('.transaction_product').filter(function(){
                    return ($(this).find('.ui-combobox input:text[value=""]').length > 0);
                }).first();
                var element;
                if (nextLine.length > 0) {
                    element = nextLine.find('.ui-combobox input:text[value=""]').val(letters);
                } else {
                    clone_last_transaction_product();
                    element = $('.transaction_product .ui-combobox input:text[value=""]').last().val(letters);
                }
                calculate_prices();
                
                moveCaretToEnd(element);
                setTimeout(function(){
                    moveCaretToEnd($(element));
                }, 30);
            }
        });
    });
}

function playSound(sound_id) {
    var sound_element = $('#' + sound_id);
    if (sound_element.length > 0 && typeof sound_element[0].play == 'function') {
        sound_element[0].pause();
        sound_element[0].currentTime = 0.1;
        sound_element[0].play();
    }
}

function moveCaretToEnd(element) {
    var last_value = $(element).focus().val();
    $(element).val('');
    $(element).val(last_value)
}