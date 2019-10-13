// import style from "./main.css";
class ManyMailerPlus_mod {
    constructor(apiAvailable) {
        'use strict';
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
        this.service_list = $('h2:contains("Services")').next('ul'); // sidebar list of services
        this.active_services = $('#active_services'); //hidden input for active services
        this.sel_csv_entry = $('select[name=recipient_entry]');
        this.recipient = $('input[name=recipient]'); // input box for email(s)
        this.csv_recipient = $('#csv_recipient'); // textarea for the content of the csv file
        this.file_recipient = $("input[name='file_recipient']"); // file input
        this.btn_reset = $('#reset'); // reset button for csv data
        this.tmp_selections = $('input[name="selection[]"');
        this.con_placeholder = $('#csv_placeholder'); // container for placeholders
        this.con_errors = $('#csv_errors'); // container for error messages
        this.loader = $('.loader'); // css loading visuals

        // modules
        this.Stepper = new Stepper($('.form-section'));
        $.fn.extend({
            val_with_linenum: function(v) {
                return this.each(() => {
                    $(this).val(v).trigger('input');
                });
            }
        });
        return this;
    }

    init() {
        this.toggle_loading(this.initializePage.bind(this));
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

    set csvObj(results) {
        this.tableData = { headers: results.headers, data: results.data };
        this.theCSV_obj = results;
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
    get on_compose_page() {
        return window.location.href.split('/').slice(-1)[0].indexOf('compose') > -1;
    }
    get linenum_offset() {
        var data = this.get_csv_recip(true);
        var errLines = data.filter((val) => val === val.toUpperCase()).length;
        return errLines > 0 ? ++errLines : 1;
    }
    toggle_loading(fn, ...args) {
            // this.loader.addClass('is-active');
            fn(...args);
            // this.loader.removeClass('is-active');
        }
        // SweetAlert2 messenger
    show_message(config) {
        if (Swal !== undefined) {
            Swal.fire(config);
        } else {
            alert(JSON.stringify(config));
        }
        return this;
    }

    sweetAlertbyID(id) {
            var html = $(id).html();
            var title = $($.parseHTML(html)).find('h1').text();
            var info = $($.parseHTML(html)).find('.txt-wrap').html();
            this.show_message({ title: title, html: info, type: 'info' });
        }
        /**
         * set all events for dom elements
         */
    initializePage() {
        this.toggleInitState(false).init_service_list().init_dom_events();
        if (this.b_isApiAvailable) {
            this.useApi().init_placeholder_funcs();
        }
        // for debugging purposes only
        // this.test_funcs();
    }

    val_with_linenum(str) {
        this.csv_recipient.val(str).trigger('input');
        return this;
    }

    useApi() {
        this.file_recipient.change((evt) => {
            this.resetRecipients();
            var fileType = /csv.*/;
            var file = evt.target.files[0];
            if (file) {
                if (file.type.match(fileType) || file.name.slice(-3) === 'csv') {
                    var reader = new FileReader();
                    reader.onload = function(e) {
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
                    this.resetRecipients(true);
                }
            }
        });
        return this;
    }

    init_placeholder_funcs() {
        (function($) {
            // Behind the scenes method deals with browser
            // idiosyncrasies and such
            $.caretTo = function(el, index) {
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
            $.fn.caretTo = function(index, offset) {
                return this.queue(function(next) {
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
            $.fn.caretToStart = function() {
                return this.caretTo(0);
            };

            // Set caret to the end of an element
            $.fn.caretToEnd = function() {
                return this.queue(function(next) {
                    $.caretTo(this, $(this).val().length);
                    next();
                });
            };
        })(jQuery);
        return this;
    }

    init_dom_events() {
            this.doc_body
                .on('click', '*[data-conditional-modal]', function(e) {
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
                                this.show_message({
                                    title: 'Deleted!',
                                    html: 'Your file has been deleted.',
                                    type: 'success'
                                });
                            }
                        });
                    $('.app-overlay').removeClass('app-overlay---open');
                    return;
                })
                .on('click', '#mc-edits legend', function() {
                    $(this).nextAll('div').fadeToggle('slow');
                })
                .bind(this);
            // hijacks default 'view email' button for SweetAlert2 action!
            $('a.m-link')
                .bind('click', (e) => {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    var rel = e.target.rel;
                    this.sweetAlertbyID(`.${rel}`);
                })
                .bind(this);

            if (this.on_compose_page) {
                this.mail_type[0].addEventListener(
                    'change',
                    function(e) {
                        this.change_mail_type(e);
                    }.bind(this),
                    false
                );

                this.sel_csv_entry[0].addEventListener(
                    'change',
                    function(e) {
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
                    function(e) {
                        this.toggle_loading(this.evt_load_csv_file.bind(this), e);
                    }.bind(this),
                    false
                );
                this.recipient[0].addEventListener(
                    'change',
                    function(e) {
                        this.countEmails();
                    }.bind(this),
                    false
                );
                this.recipient[0].addEventListener(
                    'click',
                    function(e) {
                        this.show_message({
                            title: 'Invalid!',
                            html: 'Please enter emails using csv entry (file upload/paste).',
                            type: 'error'
                        });
                    }.bind(this),
                    false
                );
                $.each($('input[name^=use_templates]'), (idx, val) => {
                    val.addEventListener(
                        'change',
                        function(e) {
                            this.evt_toggle_templates(e);
                        }.bind(this),
                        false
                    );
                });
                $('[name$=linenum], #reset')[0].addEventListener(
                    'click',
                    function(e) {
                        this.resetRecipients(true);
                    }.bind(this),
                    false
                );

                $('button[name=convert_csv]')[0].addEventListener(
                    'click',
                    function(e) {
                        this.toggle_loading(this.evt_convert_csv.bind(this), e);
                    }.bind(this),
                    false
                );
                this.tmp_selections.bind('interact', (e) => {
                    this.evt_select_template(e);
                });

                this.tmp_selections[0].addEventListener(
                    'change',
                    function(e) {
                        this.evt_select_template(e);
                    }.bind(this),
                    false
                );
            }
            return this;
        }
        // BEGIN EVENT FUNCTIONS
    evt_load_csv_file(evt) {
        this.resetRecipients();
        var file = evt.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                TLN.remove_line_numbers('csv_recipient');
                this.val_with_linenum(reader.result);
                this.show_csv_recipient_fieldset(true);
            }.bind(this);
            reader.readAsText(file);
        }
    }
    evt_toggle_templates(el) {
        let current_base_url = 'http://' + window.location.hostname;
        let url = new URL('/admin.php?/cp/addons/settings/manymailerplus/email/get_template_view', current_base_url);
        $.get(url, {}, function(data, textStatus, jqXHR) {
            console.log(url.href);
            console.dir(jqXHR);
            console.warn(textStatus);
        });
        // var toggle = el.value === 'y' ? 'slow' : false;
        // this.con_embed_tmps.fadeToggle(toggle);
        // this.con_tmp_name.fadeToggle(toggle);
    }
    evt_convert_csv(e) {
        this.convertCSV();
        this.sel_csv_entry.val('file_recipient').trigger('change');
    }
    evt_change_mail_type() {
        this.plaintext.toggle(this.val() === 'html');
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
                this.tmp_selections.not(this).attr('checked', false).parents('tr').removeClass('selected');
                message = choice.innerHTML;
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
                                        edit_section: attribute.value,
                                        content: element.innerHTML
                                    });
                                }
                                console.log(attribute.name + '(' + element.nodeType + ')', '=>', attribute.value);
                            }
                        }
                    }
                }
                this.create_editable_content(sections);
            }
            $('legend').trigger('click');
        }
        this.tmp_name.val(name);
        $('input[name=subject]').val(subject);
    }

    evt_placeholder_btn(btn) {
        var plain = $("textarea[name='plaintext_alt']");
        var msg = $("textarea[name='message']");
        var message = $("textarea[name='plaintext_alt']").is(':visible') ? plain : msg;

        // Insert text into textarea at cursor position and replace selected text
        var cursorPosStart = message.prop('selectionStart');
        var cursorPosEnd = message.prop('selectionEnd');
        var insertedText = $(btn).val() + ' ';
        var strMsg = message.val();
        var textBefore = strMsg.substring(0, cursorPosStart);
        var textAfter = strMsg.substring(cursorPosEnd, strMsg.length);
        message.val(textBefore + insertedText + textAfter);
        $('textarea[name=message]').caretTo(insertedText, true);
    }

    /// End EVENT FUNCTIONS

    prep_data_for_parse() {
        // remove validation errors
        var arr_current_csv = this.get_csv_recip(true);
        if (arr_current_csv.length < 1) {
            this.show_message({
                title: 'No CSV Data Provided',
                type: 'error'
            });
            return;
        }
        return arr_current_csv.join('\n');
    }
    toggleInitState(show) {
        if (this.on_compose_page) {
            this.btn_reset.toggle(show);
            this.show_csv_recipient_fieldset(show);
            this.con_recip_review.toggle(show);
            this.con_embed_tmps.toggle(show);
            this.con_tmp_name.toggle(show);
            this.con_errors.toggle(show);
            this.recipient.prop('readonly', true);
        }

        return this;
    }
    parse_csv_data(str_data) {
        str_data = this.prep_data_for_parse(str_data);
        Papa.parse(str_data, {
            header: true,
            quoteChar: '',
            skipEmptyLines: 'greedy',
            error: (e, file) => {
                this.showPapaErrors(data.errors);
                return;
            },
            complete: (results, file) => {
                this._parsed = results;
            }
        });
        return this._parsed;
    }

    showPapaErrors(arr_errors) {
        var errMsgs = this.reduce_errorMsgs(arr_errors);
        var container = $('<div class="errCode" />');
        // consotype_containerdate error array
        Object.keys(errMsgs).forEach((type) => {
            var type_container = $('<details/>')
                .append($('<summary />', { text: type }))
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
        this.show_message({
            title: 'Errors',
            type: 'error',
            html: Object.keys(errMsgs).join('<br> ')
        });
        return this;
    }

    reduce_errorMsgs(errors) {
        var errMsgs = {};
        var testMsg = function(val, testVal) {
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

    resetRecipients(all) {
        if (all) {
            this.val_with_linenum('');
            this.show_csv_recipient_fieldset($('select[name=recipient_entry]').val() === 'csv_recipient');
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

    resetFileInput() {
        // reset upload
        this.file_recipient.replaceWith(this.file_recipient.val('').clone(true));
        return this;
    }

    show_csv_recipient_fieldset(show) {
        this.con_csv_recipient.toggle(show);
        return this;
    }

    create_editable_content(sections) {
        var email_body = [
            'main',
            'content'
        ];
        var found = sections.find(function(el) {
            return $.inArray(el.edit_section, email_body) !== -1;
        });
        var suggested = found ? `(suggested: <b>'${found.edit_section}')</b>` : '';
        var fs = $('<fieldset id="mc-edits" />').append('<legend class="btn">Editable Content</legend>');

        sections.forEach((el_obj) => {
            var id = el_obj.edit_section;
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
                    $(`<label for="mc-check_${id}">(Body?)</label>`)
                    .css('text-align', 'right')
                    .css('display', 'inline-block')
                ),
                $('<div>')
                .addClass('field-control')
                .append($(`<textarea value="${id}" name="mc-edit[${id}]" rows="10" cols="50">${val}</textarea>`))
            );

            $('input[name^="mc-check"').change(function() {
                var chk = this.checked;
                $('input[name^="mc-check"').not(this).each(function(el) {
                    if (chk) {
                        $(this).attr('checked', false).hide();
                        $(`label[for=${this.name}]`).hide();
                    } else {
                        $(`label[for=${this.name}]`).show();
                        $(this).show();
                    }
                });
                var name = this.name.substr('mc-check_'.length);
                console.log(name);
            });
        });
    }

    show_active_services() {
        var val = this.active_services.val();
        $.each(this.service_list.children(), function() {
            var list_item = $(this).text().toLowerCase();
            if (val && val.indexOf(list_item) > -1) {
                $(this).addClass('enabled-service');
            } else {
                $(this).addClass('disabled-service');
            }
            $(this).attr('data-service', list_item);
        });
    }

    showPlaceholders(headers) {
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
            function(e) {
                this.evt_placeholder_btn(e.currentTarget);
            }.bind(this)
        );
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
        this.resetRecipients();
        let obj_parsed = this.parse_csv_data();
        if (this.validate(obj_parsed)) {
            this.init_datatable().setFormValues().show_csv_recipient_fieldset(false).resetFileInput();
        } else {
            this.con_errors.toggle(true);
        }
        return this;
    }

    setFormValues() {
        $('#reset').show();
        this.showPlaceholders().recipient.val(this.csvObj.emails.join(', '));
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
                this.showPapaErrors(validation_result.errors, 'warning');
            }
        } else {
            this.showPapaErrors(obj_parsed.errors);
        }
        return validation_result.is_valid || false;
    }

    init_datatable() {
        // this.val_with_linenum('');
        $('#csv_content').addClass('fixed_header display').DataTable({
            defaultContent: '',
            dom: '<"top"i>rt<"bottom"flp><"clear">',
            initComplete: function() {
                var api = this.api();
                api.$('td').click(function() {
                    api.search(this.innerHTML).draw();
                });
            },
            columns: this.tableData.columns,
            data: this.tableData.data,
            paging: false,
            ordering: false
        });
        return this;
    }

    dumpHiddenVals() {
        var msg = $('<table/>');
        $('input[type="hidden"]').each(function() {
            var val = $(this).val();
            val = val.length > 100 ? val.substring(0, 100) + '...' : val;
            console.log($(this).attr('name') + ': ' + $(this).val());
            msg.append('<tr><td>' + $(this).attr('name') + '</td><td>' + val + '</td></tr>');
        });
        this.show_message({
            title: 'HIDDEN VALS',
            type: 'info',
            html: msg,
            width: '80%'
        });
    }

    dumpFormVals() {
        var msg = $('<table/>');
        $('form :input').each(function() {
            var val = this.value;
            val = val.length > 100 ? val.substring(0, 100) + '...' : val;
            val = val === 'on' || val === 'off' ? this.checked : val;
            console.log(`${this.name}: ${this.value}`);
            msg.append(`<tr><td>${this.name}</td><td>${val}</td></tr>`);
        });
        var frmStr = JSON.stringify($('form').serialize());
        this.show_message({
            title: 'Form VALS',
            type: 'info',
            html: msg,
            width: '80%'
        });
    }
    init_service_list() {
        this.service_list
            .attr('action-url', 'admin.php?/cp/addons/settings/manymailerplus/services/')
            .addClass('service-list');
        if (this.active_services.length > 0) {
            this.show_active_services();
        } else {
            this.service_list.hide();
        }
        return this;
    }

    // init_sortable(){
    //  $('.service-list').sortable({
    //     axis: 'y',
    //     opacity: 0.5,
    //     update: function() {
    //         var serviceOrder = [];
    //         var url = document.getElementsByClassName('service-list')[0].getAttribute('action-url');
    //         $('.service-list li').each(function() {
    //             serviceOrder.push($(this).data('service'));
    //         });
    //         $.post(url, {
    //                 service_order: serviceOrder.toString(),
    //                 CSRF_TOKEN: EE.CSRF_TOKEN,
    //                 XID: EE.XID
    //             })
    //             .success(function(data) {
    //                 data = procReq(data);
    //                 $('.service-list').data('order', data);
    //                 if (data.indexOf('console') === 0){
    //                     logs = data.split(');');
    //                     logs.forEach(element => {
    //                         element = element.trim() + ');';
    //                         if (element.indexOf('console') === 0){
    //                             eval(element);
    //                         }
    //                     });
    //                 }else{
    //                     console.dir(data);
    //                 }
    //             })
    //             .fail(function(err){
    //                 data = procReq(this.data, true);
    //                 console.log(data);
    //             });
    //     }
    // });
    // }
    // isJson(item) {
    //     item = typeof item !== 'string' ? JSON.stringify(item) : item;
    //     try {
    //         item = JSON.parse(item);
    //     } catch (e) {
    //         return false;
    //     }

    //     if (typeof item === 'object' && item !== null) {
    //         return true;
    //     }
    //     console.log(item);
    //     return false;
    // }

    // qs2json(data) {
    //     var pairs = data.split('&');
    //     var retVals = decodeURIComponent(pairs[0]).replace('=', ':');
    //     return JSON.parse(JSON.stringify('{' + retVals + '}'));
    // }

    //     procReq(data, query = false) {
    //         if (query) {
    //             return this.qs2json(data);
    //         }
    //         // console.log(data);
    //         logs = data.substring(0, data.lastIndexOf('</script>') + 9);
    //         // console.log(logs);
    //         var d1 = document.getElementsByTagName('head')[0];
    //         d1.insertAdjacentHTML('beforeend', logs);
    //         data = data.substring(logs.length);
    //         // console.log(data);
    //         return data === '' ? logs.replace(/<\/?[^>]+(>|$)/g, '') : isJson(data) ? JSON.parse(data) : data;
    //     }
    //     test_funcs() {
    //         $('#btnData').on('click', function (e) {
    //             var url = document.getElementsByClassName('service-list')[0].getAttribute('action-url');
    //             Swal.fire({
    //                 title: 'Select Fuction',
    //                 input: 'select',
    //                 inputOptions: {
    //                     update_service_order: 'Update SO',
    //                     get_settings: 'Get Settings',
    //                     get_service_order: 'Get SO',
    //                     get_active_services: 'Active',
    //                     get_initial_service: 'Priority Service'
    //                 },
    //                 inputPlaceholder: 'Select Function',
    //                 showCancelButton: true,
    //                 allowOutsideClick: () => !Swal.isLoading(),
    //                 preConfirm: (value) => {
    //                     return $.post(url + value).always(function (jqXHR) {
    //                         // debugger
    //                         var data;
    //                         if (jqXHR.hasOwnProperty('responseText')) {
    //                             data = jqXHR.responseText;
    //                         } else {
    //                             data = jqXHR;
    //                         }
    //                         if (isJson(data)) {
    //                             data = jqXHR;
    //                         } else {
    //                             data = procReq(data);
    //                         }
    //                         console.dir(data);
    //                         data = JSON.stringify(data, null, 4);

    //                         Swal.fire({
    //                             type: 'question',
    //                             html: data
    //                         });
    //                     });
    //                 }
    //             });
    //         });
    //     }
}

// for running test only
(function testable() {
    if (typeof module !== 'undefined' && typeof module.exports !== 'undefined') {
        module.exports = ManyMailerPlus_mod;
    }
})();

$(document).ready(function() {
    'use strict';
    window.LogRocket && window.LogRocket.init('jj0vrm/manymailerplus');
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