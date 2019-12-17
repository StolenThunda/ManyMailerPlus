class ManyMailerPlus_mod {
    constructor(apiAvailable) {
        'use strict';
        this.base_url = "admin.php?/cp/addons/settings/manymailerplus/";
        this.b_isApiAvailable = apiAvailable || false;
        this.b_swalLoaded = Swal !== undefined;
        this.b_inEmailFunctions = false;
        this.theCSV_obj = {};
        this.doc_body = $('body');
        // orig EE spec
        this.mail_type = $("select[name='mailtype']"); // markdown,html,plain
        this.plaintext = $("textarea[name='plaintext_alt']").parents('fieldset').eq(0);
        // MMP spec
        this.con_csv_recipient = $('#csv_recipient').parents('fieldset'); // csv input container
        this.con_embed_tmps = $('#embed_templates'); //container for template list
        this.con_tmp_name = $('#template_name').parents('fieldset');
        this.tmp_name = $('#template_name');
        this.tmp_editables = $('fieldset#mc-edits');
        this.con_file_recipient = $("input[name='file_recipient']").parents('fieldset'); // file input container
        this.con_recip_review = $('fieldset[data-control=recipient_review'); // recipient review container
        this.composer_list = $('h2:contains("Email Functions")').next('ul'); // sidebar list of services
        this.service_list = $('h2:contains("Services")').next('ul'); // sidebar list of services
        this.active_services = $('#active_services'); //hidden input for active services
        this.sel_csv_entry = $('select[name=recipient_entry]');
        this.recipient = $('input[name=recipient]'); // input box for email(s)
        this.csv_recipient = $('#csv_recipient'); // textarea for the content of the csv file
        this.file_recipient = $("input[name='file_recipient']"); // file input
        this.btn_reset = $('#reset'); // reset button for csv data
        this.chosen_tmp = $('[name=chosen_template_html]'); // the HTML of the chosen template
        this.con_placeholder = $('#csv_placeholder'); // container for placeholders
        this.con_errors = $('#csv_errors'); // container for error messages
        this.loader = $('.loader'); // css loading visuals
       
        // modules
        this.DOMParser = new DOMParser();
        this.Stepper = new Stepper($('.form-section'));
        $.fn.extend({
            val_with_linenum: function (v) {
                return this.each(() => {
                    $(this).val(v).trigger('input');
                });
            }
        });
        return this;
    }

    //#region Page Init
    init() {
        this.toggle_loading();
        this.initializePage();
        this.toggle_loading();
    }
    
    initializePage() {
        this.toggleInitState(false);
        if (this.b_isApiAvailable) {
            this.useApi().init_placeholder_funcs();
        }
    }

    init_placeholder_funcs() {
        (function ($) {
            // Behind the scenes method deals with browser
            // idiosyncrasies and such
            $.caretTo = function (el, index) {
                if (el.createTextRange) {
                    var range = el.createTextRange();
                    range.move('character', index);
                    range.select();
                } else if (el.selectionStart !== null) {
                    el.focus();
                    el.setSelectionRange(index, index);
                }
            };

            // The following methods are queued under fx for more
            // flexibility when combining with $.fn.delay() and
            // jQuery effects.

            // Set caret to a particular index
            $.fn.caretTo = function (index, offset) {
                return this.queue(function (next) {
                    if (isNaN(index)) {
                        var i = $(this).val().indexOf(index);
                        if (i === -1) {
                            i = $(this).text().indexOf(index);
                        }
                        if (offset === true) {
                            i += index.length;
                        } else if (offset) {
                            i += offset;
                        }

                        $.caretTo(this, i);
                    } else {
                        $.caretTo(this, index);
                    }

                    next();
                });
            };

            // Set caret to beginning of an element
            $.fn.caretToStart = function () {
                return this.caretTo(0);
            };

            // Set caret to the end of an element
            $.fn.caretToEnd = function () {
                return this.queue(function (next) {
                    $.caretTo(this, $(this).val().length);
                    next();
                });
            };
        })(jQuery);
        return this;
    }

    generateProgressButtons(){
        $('.form-btns:visible').each(function(){
            var btn = $('<button />', {
                text: 'View Mailer Progress',
            })
            .click((e) => { 
                e.preventDefault();
                e.stopImmediatePropagation();
                mail_progress_poll();
                if (!$('.swal2-show progress').is(':visible')) {
                    $('a.m-link[rel=mail_progress]').trigger('click');
                }
            })
            .addClass('btn btn-progress')
            .appendTo(this);

            btn.hide();
        });
    }
    init_dom_events() {

        $('form').on('submit',function() {
                if (this.on_compose_page) $('.btn-progress').toggle('slide');
            }.bind(this)
        );
        $('[name=btnProgress]').on('click', function(){ 
            // debugger
            
      
            // var progressbar = $('progress'),
            //     max = progressbar.attr('max'),
            //     time = (1000/max)*5,
            //     value = progressbar.val();

            // var loading = function() {
            //     value += 1;
            //     let addValue = progressbar.val(value);

            //     $('.progress-value').html(value + '%');

            //     if (value == max) {
            //         clearInterval(animate);
            //     }
            // };

            // var animate = setInterval(function() {
            //     loading();
            // }, time);
        });
        this.doc_body
            .on('click', '*[data-conditional-modal]', function (e) {
                e.preventDefault();
                $('.modal-confirm-remove').hide();
                swal
                    .fire({
                        type: 'warning',
                        html: $('.modal-confirm-remove').find('.form-standard'),
                        showCloseButton: true,
                        showCancelButton: false,
                        showConfirmButton: false
                    })
                    .then((result) => {
                        if (result.value) {
                            this.display_message({
                                title: 'Deleted!',
                                html: 'Your file has been deleted.',
                                type: 'success'
                            });
                        }
                    });
                $('.app-overlay').removeClass('app-overlay---open');
                return;
            })
            .on('click', '#mc-edits legend', function () {
                $(this).nextAll('div').fadeToggle('slow');
            })
            .bind(this);
        // hijacks default 'modal  windows' for SweetAlert2 action!
        $('a.m-link')
            .bind('click', (e) => {
                e.preventDefault();
                e.stopImmediatePropagation();
                var rel = e.target.rel;
                this.display_message_by_id(`.${rel}`);
            })
            .bind(this);
            this.generateProgressButtons();
       
        if (this.on_compose_page) {
            this.mail_type[0].addEventListener(
                'change',
                function (e) {
                    this.evt_change_mail_type(e);
                }.bind(this),
                false
            );

            this.sel_csv_entry[0].addEventListener(
                'change',
                function (e) {
                    this.evt_toggle_csv_entry(e);
                }.bind(this),
                false
            );

            this.csv_recipient
                .bind('interact', (e) => {
                    if (e.currentTarget.value === '') {
                        TLN.remove_line_numbers('csv_recipient');
                    } else {
                        TLN.append_line_numbers('csv_recipient');
                    }
                })
                .wrap('<div id="csv_recipient_wrapper" ></div>');
            this.file_recipient[0].addEventListener(
                'change',
                function (e) {
                    this.evt_load_csv_file(e);
                }.bind(this),
                false
            );
            this.recipient[0].addEventListener(
                'change',
                function () {
                    this.countEmails();
                }.bind(this),
                false
            );
            this.recipient[0].addEventListener(
                'click',
                function () {
                    this.display_message({
                        title: 'Invalid!',
                        html: 'Please enter emails using csv entry (file upload/paste).',
                        type: 'error'
                    });
                }.bind(this),
                false
            );
            var useTemp = $('input[name=use_templates]');
            if (useTemp.length > 0) {
                $.each(useTemp, (idx, val) => {
                    val.addEventListener(
                        'change',
                        function (e) {
                            this.evt_toggle_templates(e);
                        }.bind(this),
                        false
                    );
                });
            }

            $('[name$=linenum], #reset')[0].addEventListener(
                'click',
                function () {
                    this.reset_form(true);
                }.bind(this),
                false
            );

            $('button[name=convert_csv]')[0].addEventListener(
                'click',
                function (e) {
                    this.evt_convert_csv(e);
                }.bind(this),
                false
            );
            var dumpBtns = $('button[name^=btnDump]');
            if (dumpBtns.length > 0) {
                $.each(dumpBtns, (idx, element) => {
                    element.addEventListener('click', function (e) {
                        this.evt_dump_data(e);
                    }.bind(this), false);
                });
            }
        }
        
        return this;
    }

    init_service_list() {
        this.db_service_order; // get db service order and assign data to element
        this.service_list
            .attr('action-url', this.base_url + 'services/update_service_order')
            .addClass('service-list');
        if (window.location.href.split("/").includes('services')) {
            this.init_sortable();
        } else {
            this.service_list.hide();
        }
        this.toggle_active_services();
        return this;
    }

    init_datatable() {
        $('#csv_content').addClass('fixed_header display').DataTable({
            destroy: true,
            defaultContent: '',
            dom: '<"top"i>rt<"bottom"flp><"clear">',
            initComplete: function () {
                var api = this.api();
                api.$('td').click(function () {
                    api.search(this.innerHTML).draw();
                });
            },
            columns: this.tableData.columns,
            data: this.tableData.data,
            // paging: false,
            // ordering: false
        });
        return this;
    }

    init_sortable(){
        var that = this;
     $('ul.service-list').sortable({
        axis: 'y',
        opacity: 0.5,
        update: function(data){that.send_sortable_update(data);}        
    });
    return this;
    }

    toggleInitState(show) {
        if (this.on_compose_page) {
            this.btn_reset.toggle(show);
            this.toggle_fs_csv_recipient(show);
            this.con_recip_review.toggle(show);
            this.con_embed_tmps.toggle(show);
            this.con_tmp_name.toggle(show);
            this.con_errors.toggle(show);
            this.recipient.prop('readonly', true);
        }
        this
            .toggle_composer_list()
            .init_service_list()
            .init_dom_events();
        return this;
    }
    //#endregion Page Init

    //#region Page funcs
    mail_progress_callback(el, response){
    
        // $.get('admin.php?/cp/addons/settings/manymailerplus/email/mail_progress', function(response){
        //     let status = !(response === '--');
        //     if (status) $('#percent').html(response);
        //     return status;
        //  });       
    }
    compare_service_order(arr1, arr2) {
        return JSON.stringify(arr1) === JSON.stringify(arr2);
    }

    get service_list_url(){
        return document.getElementsByClassName('service-list')[0].getAttribute('action-url');
    }

    get DOMserviceOrder(){
        var dataset, DOM_service_order = [];

        if (Array.isArray(this.service_list.data('order'))){
            dataset = this.service_list.data('order');
        }
        // debugger;
        this.service_list.children().each(function() {
            DOM_service_order.push($(this).data('service'));
        });
        return this.compare_service_order(DOM_service_order, dataset) ? dataset : DOM_service_order;
    }
    
    get db_service_order(){
        var that = this;
        $.get(this.base_url + 'services/get_service_order')
        .success(function(data){
            return data; 
        })
        .fail(function (data) {
            return that.parseDbgFromJson(data); 
        })
        .always(function (data) {
            console.dir(data);
            that.service_list.attr('data-order', that.parseDbgFromJson(data));
        });
    }

    resetFileInput() {
        // reset upload
        this.file_recipient.replaceWith(this.file_recipient.val('').clone(true));
        return this;
    }

    toggle_fs_csv_recipient(show) {
        this.con_csv_recipient.toggle(show);
        return this;
    }

    toggle_active_services() {
        var val = this.active_services.val();
        $.each(this.service_list.children(), function () {
            var list_item = $(this).text().toLowerCase();
            if (val && val.indexOf(list_item) > -1) {
                $(this).addClass('enabled-service');
            } else {
                $(this).addClass('disabled-service');
            }
            $(this).attr('data-service', list_item);
        });
        return this;
    }

    toggle_placeholders() {
        $('#stick-here').remove();
        $('<div />', {
            id: 'stick-here',
            class: 'stick-here',
            height: $('div.col.w-12').height()
        })
            .append(
                $('<table />', {
                    id: 'csv_placeholder',
                    class: 'placeholder'
                }).append(
                    $('<caption />', {
                        text: 'Placeholders'
                    })
                )
            )
            .appendTo('.sidebar');
        // reinitialize placeholder var because it is dynamically generated
        this.con_placeholder = $('#csv_placeholder'); // container for placeholders
        Object.entries(this.csvObj.headerKeyMap).forEach(([key, value]) => {
            var btn = $('<button/>', {
                class: 'btn placeholder',
                value: value,
                text: key
            })
                .wrap('<tr><td align="center"></td></tr>')
                .closest('tr');
            this.con_placeholder.append(btn);
        });
        $('#csv_placeholder').on(
            'click',
            'button',
            function (e) {
                this.evt_placeholder_btn(e.currentTarget);
            }.bind(this)
        );
        return this;
    }
    
    toggle_loading() {
        this.loader.toggleClass('is-active');
        return this;
    }
    
    display_message(config) {
        // SweetAlert2 messenger
        if (Swal !== undefined) {
            Swal.fire(config);
        } else {
            alert(JSON.stringify(config));
        }
        return this;
    }

     display_message_by_id(id) {
        var html = $(id).html();
        var title = $($.parseHTML(html)).find('h1').text();
        var info = $($.parseHTML(html)).find('.txt-wrap').html();
        this.display_message({
            title: title,
            html: info,
            type: 'info'
        });
    }
   
    get on_compose_page() {
        var onCompose = window.location.href.split('/').slice(-1)[0].indexOf('compose') > -1;
        if (!onCompose) {
            var onServices = window.location.href.split('/').slice(-1)[0].indexOf('services') > -1;
            if (onServices) this.update_sortable(this.DOMserviceOrder);
        }
        var tables = $("table:has(thead ~ tbody>tr:not('.no-results'))"); // non-empty tables
        tables.DataTable({
            "aoColumns" : null,
            "retrieve" : true,
            "pagingType": "full_numbers",
            "scrollY":        "400px",
            "info":           true,
            "autoWidth":      true,
            "paging":         true
        });
        if (tables.forEach){
            tables.forEach(el => {
                el.columns.adjust().draw();
            });
        }        
        return onCompose;
    }

    parseDbgFromJson(data){
        var htmlDoc = this.DOMParser.parseFromString(data.responseText, 'text/html');
        if (Array.isArray(data)){
            return data;
        } else if (htmlDoc.body.innerText === 'undefined') {
            let script = data
                    .replace(/<script async='true' defer='true'>/g, '')
                    .replace(/<\/?script>/g,'');
            eval(script);
        } else {
            //scrape debug info if any
            let scripts = htmlDoc.getElementsByTagName('script');
            if (scripts && scripts.length){
                for (let script of scripts) {
                    eval(script.outerText);
                } 
            }    
        }
            
        return JSON.parse(htmlDoc.body.innerText) || "";
    }

    send_sortable_update(){
        let that = this;
        $.post(this.service_list_url, {
            service_order: this.DOMserviceOrder,
            CSRF_TOKEN: EE.CSRF_TOKEN,
            XID: EE.XID
        })
        .fail(function (data) {
            data = that.parseDbgFromJson(data);
            that.update_sortable(data); 
        })
        .always(function (data) {
            data = that.parseDbgFromJson(data);
            if (data !== "") that.update_sortable(data); 
            
        });
        return that;
    }

    update_sortable(data){ 
        var dom_order = this.DOMserviceOrder;
        if (!dom_order || !Array.isArray(data) || !data.length) return;
        // 
        if (JSON.stringify(dom_order) !== JSON.stringify(data)){
            this.reorder(data);
        }
        
        console.dir(dom_order);
    }

    reorder(orderedArray) {
        
        // return;
        var el, pre,
            p = this.service_list[0];
        orderedArray.forEach(function (a, b, c) {
            if (b > 0) {
                el = $('[data-service=' + a + ']')[0];
                pre =  $('[data-service=' + c[b - 1] + ']')[0];
                console.dir(el);
                console.dir(pre);
                p.insertBefore(el, pre.nextSibling);
            }
        });
        this.service_list.data("order", orderedArray);
    }

    dumpHiddenVals() {
        var msg = $('<table/>');
        $('input[type="hidden"]').each(function () {
            var val = $(this).val();
            val = val.length > 100 ? val.substring(0, 100) + '...' : val;
            console.log($(this).attr('name') + ': ' + $(this).val());
            msg.append('<tr><td>' + $(this).attr('name') + '</td><td>' + val + '</td></tr>');
        });
        this.display_message({
            title: 'HIDDEN VALS',
            type: 'info',
            html: msg,
            width: '80%'
        });
    }

    dumpFormVals() {
        var msg = $('<table/>');
        $('form :input').each(function () {
            var val = this.value;
            val = val.length > 100 ? val.substring(0, 100) + '...' : val;
            val = val === 'on' || val === 'off' ? this.checked : val;
            console.log(`${this.name}: ${this.value}`);
            msg.append(`<tr><td>${this.name}</td><td>${val}</td></tr>`);
        });
        this.display_message({
            title: 'Form VALS',
            type: 'info',
            html: msg,
            width: '80%'
        });
    }

    toggle_composer_list() {
        if (window.location.href.split("/").includes('email')) {
            this.composer_list.show();
        } else {
            this.composer_list.hide();
        }
        return this;
    }

    get linenum_offset() {
        var data = this.get_csv_recip(true);
        var errLines = data.filter((val) => val === val.toUpperCase()).length;
        return errLines > 0 ? ++errLines : 1;
    }

    val_with_linenum(str) {
        this.csv_recipient.val(str).trigger('input');
        return this;
    }

    useApi() {
        this.file_recipient.change((evt) => {
            this.reset_form();
            var fileType = /csv.*/;
            var file = evt.target.files[0];
            if (file) {
                if (file.type.match(fileType) || file.name.slice(-3) === 'csv') {
                    var reader = new FileReader();
                    reader.onload = function () {
                        this.val_with_linenum(reader.result);
                        this.con_csv_recipient.show();
                    }.bind(this);
                    reader.readAsText(file);
                } else {
                    var extension = file.type !== '' ? file.type : file.name.slice(file.name.indexOf('.'));
                    this.val_with_linenum('');
                    Swal.fire({
                        title: 'Invalid File',
                        type: 'error',
                        html: `File type( <span style='color:red'>${extension} </span>): not suppored!`
                    });
                    this.reset_form(true);
                }
            }
        });
        return this;
    }
    //#endregion Page Functions

    //#region Events
    evt_load_csv_file(evt) {
        this.reset_form();
        var file = evt.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function () {
                TLN.remove_line_numbers('csv_recipient');
                this.val_with_linenum(reader.result);
                this.toggle_fs_csv_recipient(true);
            }.bind(this);
            reader.readAsText(file);
        }
    }

    evt_toggle_templates(e) {
        console.dir(e);
        this.con_templates = $("input[name^='use_template'] ~ div");
        var toggle = e.currentTarget && e.currentTarget.value === 'y' ? 'slow' : false;
        if (toggle) {
            // 
            let current_base_url = 'http://' + window.location.hostname;
            let url = new URL(this.base_url + 'email/getTemplateView', current_base_url);
            this.toggle_loading();
            $.get(url, {}, function (data) {
                var htmlDoc = this.DOMParser.parseFromString(data, 'text/html');
                if (htmlDoc.getElementsByClassName('no-results').length === 0)
                {
                    this.con_templates
                        .append(htmlDoc.getElementById('embed_templates'))
                        .append(htmlDoc.getElementsByClassName('modal-wrap'))
                        .show();
                    this.tmp_selections = $('input[name="selection[]"');
                    $.each(this.tmp_selections, (idx, val) => {
                        val.addEventListener(
                            'change',
                            function (e) {
                                this.evt_select_template(e);
                            }.bind(this),
                            false
                        );
                    });
                }else{
                    let order = $('li.enabled-service').data('service');
                    // let order = this.service_list.data('order').split(',')[0];
                    order = order.charAt(0).toUpperCase() + order.slice(1);
                    this.display_message({
                        title: 'Information',
                        html: `You have no saved templates for your ${order} (highest ranked service.)`,
                        type: 'info'
                    });
                    $("input[name^='use_template']").filter('[value="n"]').prop('checked', true).trigger('change');
                }
                
                
                this.toggle_loading().on_compose_page;
                return this;
            }.bind(this));
        } else {
            console.log("Hiding Templates");
            this.con_templates.empty().toggle(toggle);
            this.con_tmp_name.toggle(toggle);
        }
        return this;
    }

    evt_dump_data(evt) {
        if (evt.currentTarget.name === 'btnDump') {
            this.dumpHiddenVals();
        } else {
            this.dumpFormVals();
        }
    }

    evt_convert_csv() {
        this.convertCSV();
        this.sel_csv_entry
            .val('file_recipient')
            .trigger('change');
    }

    evt_change_mail_type(e) {
        this.plaintext.toggle(e.currentTarget.value === 'html');
    }

    evt_toggle_csv_entry(e) {
        let showTextEntry = e.target.value === 'csv_recipient';
        this.con_file_recipient.toggle(!showTextEntry);
        this.con_csv_recipient.toggle(showTextEntry);
    }

    evt_select_template(e) {
        var name,
            subject,
            message = '';
        var details = $('fieldset#mc-edits');
        if (details.length > 0) {
            details.remove();
        }
        if (e.target.checked) {
            var sections = [];
            var element, attributes, attribute;
            name = e.target.value;
            subject = e.target.dataset.confirm;
            var choice = document.getElementById(name + '-code');
            if (choice !== null) {
                this.chosen_tmp.val(choice);
                this.con_tmp_name.fadeToggle('slow');
                this.tmp_selections.not(e.currentTarget).attr('checked', false).parents('tr').removeClass('selected');
                message = choice.innerHTML;
                this.chosen_tmp.val(message);
                var test_element = document.createElement('div');
                test_element.innerHTML = message;
                var list = test_element.getElementsByTagName('*');
                for (var j = 0; j < list.length; j++) {
                    element = list[j];
                    attributes = element.attributes;
                    if (element.attributes) {
                        for (var i = 0; i < attributes.length; i++) {
                            attribute = attributes[i];
                            if (attribute.name.startsWith('mc:')) {
                                if (attribute.value !== '') {
                                    sections.push({
                                        region: attribute.value,
                                        content: element.innerHTML
                                    });
                                }
                                console.log(attribute.name + '(' + element.nodeType + ')', '=>', attribute.value);
                            }
                        }
                    }
                }
                console.log(sections);
                this.create_editable_content(name, sections);
            }
            // $('legend').trigger('click');
        }
        this.tmp_name.val(name);
        $('input[name=subject]').val(subject);
    }

    evt_placeholder_btn(btn) {
        var plain = $("textarea[name='plaintext_alt']");
        var msg = $("textarea[name='message']");
        var message = (this.template_editables && this.template_editables.template_body) ? this.template_editables.template_body : $("textarea[name='plaintext_alt']").is(':visible') ? plain : msg;

        // Insert text into textarea at cursor position and replace selected text
        var cursorPosStart = message.prop('selectionStart');
        var cursorPosEnd = message.prop('selectionEnd');
        var insertedText = $(btn).val() + ' ';
        var strMsg = message.val();
        var textBefore = strMsg.substring(0, cursorPosStart);
        var textAfter = strMsg.substring(cursorPosEnd, strMsg.length);
        message
            .val(textBefore + insertedText + textAfter)
            .caretTo(insertedText, true)
            .trigger('change')
            .focus();

    }
    //#endregion EVENT FUNCTIONS

    //#region Parse Functionality
 
    set csvObj(results) {
        this.tableData = {
            headers: results.headers,
            data: results.data
        };
        this.theCSV_obj = results;
    }

    set tableData(info) {
        let dtCols = [];
        let dtData = [];
        let headers = info.headers;
        let data = info.data;

        headers.forEach((col) => {
            dtCols.push({
                title: col
            });
        });

        data.forEach((itm) => {
            var itmVals = Object.values(itm);
            if (itmVals.length !== dtCols.length) {
                var diff = dtCols.length - itmVals.length;
                do {
                    itmVals.push('');
                    diff--;
                } while (diff > 0);
            }
            dtData.push(itmVals);
        });
        this._dtCols = dtCols;
        this._dtData = dtData;
    }

    get csvObj() {
        return this.theCSV_obj;
    }

    get tableData() {
        return {
            columns: this._dtCols,
            data: this._dtData
        };
    }

   prep_data_for_parse() {
        // remove validation errors
        var arr_current_csv = this.get_csv_recip(true);
        if (arr_current_csv.length < 1) {
            this.display_message({
                title: 'No CSV Data Provided',
                type: 'error'
            });
            return;
        }
        return arr_current_csv.join('\n');
    }

    parse_csv_data(str_data) {
        str_data = this.prep_data_for_parse(str_data);
        Papa.parse(str_data, {
            header: true,
            quoteChar: '',
            skipEmptyLines: 'greedy',
            error: () => {
                this.display_parse_errors(data.errors);
                return;
            },
            complete: (results) => {
                this._parsed = results;
            }
        });
        return this._parsed;
    }

    display_parse_errors(arr_errors) {
        var errMsgs = this.reduce_errorMsgs(arr_errors);
        var container = $('<div class="errCode" />');
        // consotype_containerdate error array
        Object.keys(errMsgs).forEach((type) => {
            var type_container = $('<details/>')
                .append($('<summary />', {
                    text: type
                }))
                .addClass(type.indexOf(' ') > -1 ? 'validation_error' : 'validation_warning');
            var typ_sub_container = $('<dl />');
            Object.keys(errMsgs[type]).forEach((code) => {
                $('<dt />')
                    .html(errMsgs[type][code].message)
                    .append(
                        $('<dd/>', {
                            text: 'Affected Row(s): ' + errMsgs[type][code].affected.join(', ')
                        })
                    )
                    .addClass(code.substring(0, 4) !== 'MMP_' ? 'validation_warning' : 'validation_error')
                    .appendTo(typ_sub_container);
            });
            type_container.append(typ_sub_container).appendTo(container);
        });

        container.appendTo(this.con_errors);
        this.display_message({
            title: 'Errors',
            type: 'error',
            html: Object.keys(errMsgs).join('<br> ')
        });
        return this;
    }

    reduce_errorMsgs(errors) {
        var errMsgs = {};
        var testMsg = function (val, testVal) {
            return testVal !== undefined && val.indexOf(testVal) < 0;
        };
        errors.forEach((err) => {
            if (!Object.keys(errMsgs).includes(err.type)) {
                errMsgs[err.type] = {};
            }
            if (!Object.keys(errMsgs[err.type]).includes(err.code)) {
                errMsgs[err.type][err.code] = {
                    message: '',
                    affected: []
                };
            }
            errMsgs[err.type][err.code].message += testMsg(errMsgs[err.type][err.code].message, err.message) ?
                `${err.message}<br />` :
                '';
            var offset = this.linenum_offset;
            if (!errMsgs[err.type][err.code].affected.includes(err.row + offset)) {
                errMsgs[err.type][err.code].affected.push(err.row + offset);
            }
        });
        return errMsgs;
    }

    reset_form(all) {
        if (all) {
            this.val_with_linenum('');
            this.toggle_fs_csv_recipient($('select[name=recipient_entry]').val() === 'csv_recipient');
            this.resetFileInput();
        }
        // reset emails and errors
        this.recipient.val('');
        this.con_errors.html('').toggle(false);

        // reset recipient label
        this.countEmails();

        this.con_placeholder.empty();
        // reset table

        var parent = $('#csv_content_wrapper').parent();
        parent.empty();
        var table = $("<table id='csv_content' class='fixed_header'></table>");
        parent.wrapInner(table);

        this.btn_reset.toggle(false);
        return this;
    }

    countEmails() {
        var count = this.csvObj.emails ? this.csvObj.emails.length : 0;
        var label = this.csv_recipient.parent().prev().find('label');
        var origText = label.text();
        // preserve  original label just append count string
        if (origText.includes('Count')) {
            var idx = origText.indexOf(' (Count: ');
            if (idx > -1) {
                origText = origText.substr(0, idx);
            }
        }
        var countText = count > 0 ? ` (Count: ${count})` : '';
        label.text(origText + countText);
        return this;
    }

    get_csv_recip(asArray) {
        let val = this.csv_recipient.val().trim();
        return asArray ? val.split(/\n+/g) : val;
    }

    convertCSV() {
        this.reset_form();
        let obj_parsed = this.parse_csv_data();
        if (this.validate(obj_parsed)) {
            this.init_datatable().setFormValues().toggle_fs_csv_recipient(false).resetFileInput();
        } else {
            this.con_errors.toggle(true);
        }
        return this;
    }

    setFormValues() {
        $('#reset').show();
        this.toggle_placeholders().recipient.val(this.csvObj.emails.join(', '));
        this.countEmails();
        // hidden form vals set
        $('input[name="csv_object"]').val(JSON.stringify(this.csvObj.data));
        $("input[name='recipient_count']").val(this.csvObj.emails.length);
        $("input[name='mailKey']").val(this.csvObj.mailKey);
        return this;
    }

    validate(obj_parsed) {
        let validation_result = null;
        if (!obj_parsed.meta.aborted) {
            validation_result = new CSV_Validator(obj_parsed);
            if (validation_result.is_valid) {
                this.csvObj = validation_result;
            } else {
                this.display_parse_errors(validation_result.errors, 'warning');
            }
        } else {
            this.display_parse_errors(obj_parsed.errors);
        }
        return validation_result.is_valid || false;
    }

    //#endregion Parse Functionality

    //#region Template functionality    
    set template_editables(editObj) {
        this._tmp_edits = (!editObj) ? null : {
            template_body: $(`#${editObj.name}`).bind('change input blur', () => {
                this.mirror_body_content();
            }),
            regions: editObj.regions
        };
    }

    get template_editables() {
        return this._tmp_edits;
    }
    
    create_editable_content(name, sections) {
        var email_body = [
            'main',
            'content'
        ];
        var found = sections.find(function (el) {
            return $.inArray(el.region, email_body) !== -1;
        });
        var suggested = found ? `(suggested: <b>'${found.region}')</b>` : '';
        var fs = $('<fieldset id="mc-edits" />').append(`<legend class="btn">Editable Content for <span>${name}</span></legend>`);
        var that = this;
        sections.forEach((el_obj) => {
            var id = el_obj.region;
            var val = el_obj.content;
            var parent = this.con_tmp_name.eq(0);
            if (this.tmp_editables.length === 0) {
                parent.after(fs);
                fs.append(
                    $('<div>')
                        .addClass('field-instruct')
                        .append(
                            $(`<label><em>Choose the section represented by the email body ${suggested} </em></label>`)
                        )
                );
            }

            fs.append(
                $('<div>')
                    .addClass('field-instruct')
                    .append($(`<label>${id}</label>`).css('color', 'red').css('font-size', '20px'))
                    .append(
                        $(`<input type="checkbox" " name="mc-check_${id}" id="mc-check_${id}" />`, {
                            'data-parsley-mincheck': '1',
                            'data-parsley-multiple': 'mc-check'
                        })
                    )
                    .append(
                        $(`<label for="mc-check_${id}">(Message Body?)</label>`)
                            .css('text-align', 'right')
                            .css('display', 'inline-block')
                    ),
                $('<div>')
                    .addClass('field-control')
                    .append($(`<textarea id="${id}" name="mc-edit[${id}]" rows="10" cols="50">${val}</textarea>`))
            );

            $('input[name^="mc-check"').change(function () {
                var chk = this.checked;
                $('input[name^="mc-check"').not(this).each(function () {
                    if (chk) {
                        $(this).attr('checked', false).hide();
                        $(`label[for=${this.name}]`).hide();
                    } else {
                        $(`label[for=${this.name}]`).show();
                        $(this).show();
                    }
                });
                var name = this.name.substr('mc-check_'.length);
                if (chk) {
                    that.template_editables = { name: name, regions: sections };
                    that.toggle_message_div(chk);
                }
                console.log(name);
            });
        });
    }

    sync_template_body_content() {
        var html = this.chosen_tmp.val();
        if (html !== "") {
            var htmlDoc = this.DOMParser.parseFromString(html, 'text/html');
            this.template_editables.regions.forEach(element => {

                var current = htmlDoc.querySelectorAll(`div[mc\\:edit=${element.region}]`);
                var newHTML = $(`#${element.region}`).val();
                current[0].innerHTML = newHTML;
            });
            this.chosen_tmp.val(htmlDoc.documentElement.outerHTML);
        }
        return this;
    }

    toggle_message_div(toggle) {
        if (toggle) {
            var message = `Edit the value of '${this.template_editables.template_body.attr('id')}' under "Editable Content"`;
            $('[name=message')
                .on('click', () => {
                    this.display_message({
                        title: 'Error! Template Body in use',
                        html: message,
                        type: 'error'
                    });
                    this.template_editables.template_body.focus();
                })
                .prop('readonly', true)
                .val(this.template_editables.template_body.val());
        } else {
            $('[name=message')
                .prop('onclick', null)
                .off('click')
                .prop('readonly', false);
        }
        return this;
    }

    mirror_body_content() {
        this
            .toggle_message_div(this.template_editables.template_body);
        // .sync_template_body_content();
    }    
    //#endregion Templates
}
function mail_progress_poll(){
//    debugger;
    const url = 'admin.php?/cp/addons/settings/manymailerplus/email/mail_progress';    
    $.ajax({
        type: "POST",
        url: url,
        dataType: "json",
        success: function(status){
            debugger;
            var p = status.progress;
            $('.swal2-show .progress-value').html(p + '%');
            $('.swal2-show #current').html(status.current);
            $('.swal2-show #total').html(status.total);
            $('.swal2-show #time').html(status.time);
            $('.swal2-show .pBar').val(p);
            $('.swal2-show #result')
                .val(status.messages)
                .scrollTop($('.swal2-show #result')[0].scrollHeight);
            if (p === '--' || parseInt(p) < 100 || parseInt(p) === NaN) setTimeout(mail_progress_poll,500);
        },
        error: function(jqXHR, status, e) {
            debugger; 
            var response = jqXHR.responseText;
            if (jqXHR.hasOwnProperty('responseJson')){                
                Swal.fire({title: status, type: 'error', html: response});    
                var result = JSON.parse(response);
                if (result.current !== result.total && result.total !== 0) setTimeout(mail_progress_poll,500);}
            }       
    });
}

// for running test only
(function testable() {
    if (typeof module !== 'undefined' && typeof module.exports !== 'undefined') {
        module.exports = ManyMailerPlus_mod;
    }
})();

$(document).ready(function () {
    'use strict';
    // window.LogRocket && window.LogRocket.init('jj0vrm/manymailerplus');
    function isAPIAvailable() {
        // Check for the various File API support.
        if (window.File && window.FileReader && window.FileList && window.Blob) {
            // Great success! All the File APIs are supported.
            console.log('Great success! All the File APIs are supported.\n CSV uploads enabled!');
            return true;
        } else {
            // source: File API availability - http://caniuse.com/#feat=fileapi
            // source: <output> availability - http://html5doctor.com/the-output-element/
            var title = 'CSV Upload feature is disabled';
            var warning = 'The HTML5 APIs used in this form are only available in the following browsers:<br />';
            warning += ' - Google Chrome: 13.0 or later<br />'; // 6.0 File API & 13.0 <output>
            warning += ' - Mozilla Firefox: 6.0 or later<br />'; // 3.6 File API & 6.0 <output>
            warning += ' - Internet Explorer: Not supported (partial support expected in 10.0)<br />'; // 10.0 File API & 10.0 <output>
            warning += ' - Safari: Not supported<br />'; // ? File API & 5.1 <output>
            warning += ' - Opera: Not supported'; // ? File API & 9.2 <output>
            Swal.fire({
                title: title,
                type: 'warning',
                html: warning
            });

            return false;
        }
    }

    
    var MMP = new ManyMailerPlus_mod(isAPIAvailable());
    MMP.init();

});