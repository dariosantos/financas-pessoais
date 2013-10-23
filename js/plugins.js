// usage: log('inside coolFunc', this, arguments);
// paulirish.com/2009/log-a-lightweight-wrapper-for-consolelog/
window.log = function f(){
    log.history = log.history || [];
    log.history.push(arguments);
    if(this.console) {
        var args = arguments, newarr;
        args.callee = args.callee.caller;
        newarr = [].slice.call(args);
        if (typeof console.log === 'object') log.apply.call(console.log, console, newarr); else console.log.apply(console, newarr);
    }
};

// make it safe to use console.log always
(function(a){
    function b(){}
    for(var c="assert,count,debug,dir,dirxml,error,exception,group,groupCollapsed,groupEnd,info,log,markTimeline,profile,profileEnd,time,timeEnd,trace,warn".split(","),d;!!(d=c.pop());){
        a[d]=a[d]||b;
    }
})
(function(){
    try{
        console.log();
        return window.console;
    }catch(a){
        return (window.console={});
    }
}());


// place any jQuery/helper plugins in here, instead of separate, slower script files.

(function( $ ) {
    $.widget( "ui.combobox", {
        _create: function() {
            var input,
            self = this,
            select = this.element.hide(),
            selected = select.children( ":selected" ),
            value = selected.val() ? selected.text() : "",
            new_select_option = (value != '' && $(selected).hasClass('new_select_option') ? ' new_select_option ' : ''),
            wrapper = this.wrapper = $( "<span>" )
            .addClass( "ui-combobox")
            .insertAfter( select );
            
            input = $( "<input>" )
            .appendTo( wrapper )
            .val( value )
            .addClass( "ui-state-default ui-combobox-input" + new_select_option )
            .autocomplete({
                delay: 0,
                minLength: 0,
                source: function( request, response ) {
                    var matcher = new RegExp( stripVowelAccent($.ui.autocomplete.escapeRegex(request.term)), "i" );
                    response( select.children( "option" ).map(function() {
                        var text = $( this ).text();
                        if ( this.value && ( !request.term || matcher.test(text) || matcher.test(stripVowelAccent(text)) ) )
                            return {
                                label: text.replace(
                                    new RegExp(
                                        "(?![^&;]+;)(?!<[^<>]*)(" +
                                        $.ui.autocomplete.escapeRegex(request.term) +
                                        ")(?![^<>]*>)(?![^&;]+;)", "gi"
                                        ), "<strong>$1</strong>" ),
                                value: text,
                                option: this
                            };
                    }) );
                },
                select: function( event, ui ) {
                    var oldValue = select.val();
                    
                    ui.item.option.selected = true;
                    self._trigger( "selected", event, {
                        item: ui.item.option
                    });
                    
                    // Mostra se o valor é novo ou não
                    if ($(ui.item.option).hasClass("new_select_option")) {
                        $( this ).addClass("new_select_option");
                    } else {
                        $( this ).removeClass("new_select_option");
                    }
                    
                    select.trigger("autocompleteselect", oldValue);
                },
                change: function( event, ui ) {
                    var oldValue = select.val();
                    var input_element = this;
                    
                    if ( !ui.item ) {
                        $( input_element ).removeClass("new_select_option");
                        
                        var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( $(this).val() ) + "$", "i" ),
                        valid = false;
                        select.children( "option" ).each(function() {
                            if ( $( this ).text().match( matcher ) ) {
                                this.selected = valid = true;
                                
                                // Mostra se o valor é novo ou não
                                if ($( this ).hasClass("new_select_option")) {
                                    $( input_element ).addClass("new_select_option");
                                }
                                
                                select.trigger("autocompletechange", oldValue);
                                return false;
                            }
                        });
                        if ( !valid ) {
                            //
                            //
                            //  --------------------------------------------------
                            // NOTE: Alterado aqui. Caso o input não exista no select, isto cria-o automaticamente para todos os selects que tenham o mesmo name
                            // --------------------------------------------------
                            if (select.attr("class").search('auto_add') != -1) {
                                // can add new value to select
                                select.add('[name="' + select.attr('name') + '"]').append('<option class="new_select_option" value="' + $(this).val() + '">' + $(this).val() + '</option>');
                                this.selected = valid = true;
                                input.data( "autocomplete" ).term = $(this).val();
                                
                                // Mostra que o valor é novo
                                $( input_element ).addClass("new_select_option");
                                
                                select.val( $(this).val() );
                                
                                select.trigger("autocompletechange", oldValue);
                                return false;
                            } else {
                                // --------------------------------------------------
                                // 
                                // 
                                // remove invalid value, as it didn't match anything
                                $( this ).val( "" );
                                select.val( "" );
                                input.data( "autocomplete" ).term = "";
                                
                                select.trigger("autocompletechange", oldValue);
                                return false;
                            }
                        }
                    }
                }
            })
            .addClass( "ui-widget ui-widget-content ui-corner-left" );
            
            input.data( "autocomplete" )._renderItem = function( ul, item ) {
                
                // Mostra se o valor é novo ou não
                var optionClass = '';
                if ($(item.option).hasClass("new_select_option")) {
                    optionClass = 'class="new_select_option"';
                }
                
                return $( "<li></li>" )
                .data( "item.autocomplete", item )
                .append( "<a " + optionClass + ">" + item.label + "</a>" )
                .appendTo( ul );
            };
            
            $( "<a>" )
            .attr( "tabIndex", -1 )
            .attr( "title", "Show All Items" )
            .appendTo( wrapper )
            .button({
                icons: {
                    primary: "ui-icon-triangle-1-s"
                },
                text: false
            })
            .removeClass( "ui-corner-all" )
            .addClass( "ui-corner-right ui-combobox-toggle" )
            .click(function() {
                // close if already visible
                if ( input.autocomplete( "widget" ).is( ":visible" ) ) {
                    input.autocomplete( "close" );
                    return;
                }
                
                // work around a bug (likely same cause as #5265)
                $( this ).blur();
                
                // pass empty string as value to search for, displaying all results
                input.autocomplete( "search", "" );
                input.focus(); // NOTE: Colocar focus no input mostra o teclado nos telemoveis (ocupa muito espaço)
            });
        },
        
        destroy: function() {
            this.wrapper.remove();
            this.element.show();
            $.Widget.prototype.destroy.call( this );
        }
    });
})( jQuery );


function stripVowelAccent(str) {
    var rExps=[
    {
        re:/[\xC0-\xC6]/g, 
        ch:'A'
    },
    
    {
        re:/[\xE0-\xE6]/g, 
        ch:'a'
    },
    
    {
        re:/[\xC8-\xCB]/g, 
        ch:'E'
    },
    
    {
        re:/[\xE8-\xEB]/g, 
        ch:'e'
    },
    
    {
        re:/[\xCC-\xCF]/g, 
        ch:'I'
    },
    
    {
        re:/[\xEC-\xEF]/g, 
        ch:'i'
    },
    
    {
        re:/[\xD2-\xD6]/g, 
        ch:'O'
    },
    
    {
        re:/[\xF2-\xF6]/g, 
        ch:'o'
    },
    
    {
        re:/[\xD9-\xDC]/g, 
        ch:'U'
    },
    
    {
        re:/[\xF9-\xFC]/g, 
        ch:'u'
    },
    
    {
        re:/[\xD1]/g, 
        ch:'N'
    },
    
    {
        re:/[\xF1]/g, 
        ch:'n'
    } ];
    
    for(var i=0, len=rExps.length; i<len; i++)
        str=str.replace(rExps[i].re, rExps[i].ch);
    
    return str;
}