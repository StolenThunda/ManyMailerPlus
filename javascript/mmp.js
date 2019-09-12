const TLN = {
    eventList: {},
    update_line_numbers: function (ta, el) {
        'use strict';
        let lines = ta.value.split('\n').length;
        let child_count = el.children.length;
        let difference = lines - child_count;

        if (difference > 0) {
            let frag = document.createDocumentFragment();
            while (difference > 0) {
                let line_number = document.createElement('span');
                line_number.className = 'tln-line';
                frag.appendChild(line_number);
                difference--;
            }
            el.appendChild(frag);
        }
        while (difference < 0) {
            el.removeChild(el.firstChild);
            difference++;
        }
    },
    append_line_numbers: function (id) {
        let ta = document.getElementById(id);
        if (ta === null) {
            return console.error("[tln.js] Couldn't find textarea of id '" + id + "'");
        }
        if (ta.className.indexOf('tln-active') !== -1) {
            return;
            // return console.log("[tln.js] textarea of id '" + id + "' is already numbered");
        }
        ta.classList.add('tln-active');
        ta.style = {};

        let el = document.createElement('div');
        ta.parentNode.insertBefore(el, ta);
        el.className = 'tln-wrapper';
        TLN.update_line_numbers(ta, el);
        TLN.eventList[id] = [];

        const __change_evts = [
            'propertychange',
            'input',
            'keydown',
            'keyup'
        ];
        const __change_hdlr = (function (ta, el) {
            return function (e) {
                if (
                    (+ta.scrollLeft === 10 &&
                        (e.keyCode === 37 || e.which === 37 || e.code === 'ArrowLeft' || e.key === 'ArrowLeft')) ||
                    e.keyCode === 36 ||
                    e.which === 36 ||
                    e.code === 'Home' ||
                    e.key === 'Home' ||
                    e.keyCode === 13 ||
                    e.which === 13 ||
                    e.code === 'Enter' ||
                    e.key === 'Enter' ||
                    e.code === 'NumpadEnter'
                ) {
                    ta.scrollLeft = 0;
                }
                TLN.update_line_numbers(ta, el);
            };
        })(ta, el);
        for (let i = __change_evts.length - 1; i >= 0; i--) {
            ta.addEventListener(__change_evts[i], __change_hdlr);
            TLN.eventList[id].push({
                evt: __change_evts[i],
                hdlr: __change_hdlr
            });
        }

        const __scroll_evts = [
            'change',
            'mousewheel',
            'scroll'
        ];
        const __scroll_hdlr = (function (ta, el) {
            return function () {
                el.scrollTop = ta.scrollTop;
            };
        })(ta, el);
        for (let i = __scroll_evts.length - 1; i >= 0; i--) {
            ta.addEventListener(__scroll_evts[i], __scroll_hdlr);
            TLN.eventList[id].push({
                evt: __scroll_evts[i],
                hdlr: __scroll_hdlr
            });
        }
    },
    remove_line_numbers: function (id) {
        let ta = document.getElementById(id);
        if (ta === null) {
            return console.error("[tln.js] Couldn't find textarea of id '" + id + "'");
        }
        if (ta.className.indexOf('tln-active') === -1) {
            return;
            // return console.log("[tln.js] textarea of id '" + id + "' isn't numbered");
        }
        ta.classList.remove('tln-active');

        ta.previousSibling.remove();

        if (!TLN.eventList[id]) {
            return;
        }
        for (let i = TLN.eventList[id].length - 1; i >= 0; i--) {
            const evt = TLN.eventList[id][i];
            ta.removeEventListener(evt.evt, evt.hdlr);
        }
        delete TLN.eventList[id];
    }
};
const con_csv_recipient = $('#csv_recipient').parents('fieldset'); // csv input container
const con_embed_tmps = $('#embed_templates'); //container for template list
const con_tmp_name = $('#template_name').parents('fieldset');
const tmp_name = $('#template_name');
const tmp_editables = $('fieldset#mc-edits');
const con_file_recipient = $("input[name='file_recipient']").parents('fieldset'); // file input container
const con_recip_review = $('fieldset[data-control=recipient_review'); // recipient review container
const service_list = $('h2:contains("Services")').next('ul'); // sidebar list of services
const active_services = $('#active_services'); //hidden input for active services
const sel_csv_entry = $('select[name=recipient_entry]');
const recipient = $('input[name=recipient]'); // input box for email(s)
const csv_recipient = $('#csv_recipient'); // textarea for the content of the csv file
const file_recipient = $("input[name='file_recipient']"); // file input
const btn_reset = $('#reset'); // reset button for csv data
const tmp_selections = $('input[name="selection[]"');
const con_placeholder = $('#placeholder'); // container for placeholders
const con_errors = $('#csv_errors'); // container for error messages
class ManyMailerPlus_mod {
    constructor(apiAvailable) {
        'use strict';
        this.b_isApiAvailable = apiAvailable || false;
        this.b_swalLoaded = Swal !== undefined;
        this.b_inEmailFunctions = function () {
            return window.location.href.split('/').slice(-2)[0] === 'email';
        };
        this.doc_body = $('body');
        // orig EE spec
        this.mail_type = $("select[name='mailtype']"); // markdown,html,plain
        this.plaintext = $("textarea[name='plaintext_alt']").parents('fieldset').eq(0);
        // MMP spec

        // modules
        this.csvValidator = new CSV_Validator();
        this.Stepper = new Stepper($('.form-section'));
    }
    /**
     * @returns {any}
     */
    static get con_csv_recipient() { return con_csv_recipient; }
    static get con_embed_tmps() { return con_embed_tmps; }
    static get con_tmp_name() { return con_tmp_name; }
    static get tmp_name() { return tmp_name; }
    static get tmp_editables() { return tmp_editables; }
    static get con_file_recipient() { return con_file_recipient; }
    static get con_recip_review() { return con_recip_review; }
    static get service_list() { return service_list; }
    static get active_services() { return active_services; }
    static get sel_csv_entry() { return sel_csv_entry; }
    static get recipient() { return recipient; }
    static get csv_recipient() { return csv_recipient; }
    static get file_recipient() { return file_recipient; }
    static get btn_reset() { return btn_reset; }
    static get tmp_selections() { return tmp_selections; }
    static get con_placeholder() { return con_placeholder; }
    static get con_errors() { return con_errorss; }


    init() {
        this.initializePage();
    }

    // SweetAlert2 messenger
    static show_message(config) {
        if (Swal !== undefined) {
            Swal.fire(config);
        } else {
            alert(JSON.stringify(config));
        }
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
        this.extend_jq();
        this.init_body_events();
        this.init_dom_events();
        this.init_service_list();
        this.init_sweet();
        if (this.b_isApiAvailable) {
            this.useApi();
        }
        // for debugging purposes only
        this.test_funcs();
    }

    extend_jq() {
        if (this.b_inEmailFunctions) {
            this.init_placeholder_funcs();
            $.fn.extend({
                val_with_linenum: function (v) {
                    return this.each(() => {
                        $(this).val(v).trigger('input');
                    });
                }
            });
        }
    }

    useApi() {
        ManyMailerPlus_mod.file_recipient.change((evt) => {
            resetRecipients();
            var fileType = /csv.*/;
            var file = evt.target.files[0];
            if (file) {
                if (file.type.match(fileType) || file.name.slice(-3) === 'csv') {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        $('#csv_recipient').val_with_linenum(reader.result).parents('fieldset').show();
                    };
                    reader.readAsText(file);
                } else {
                    var extension = file.type !== '' ? file.type : file.name.slice(file.name.indexOf('.'));
                    $('#csv_recipient').val_with_linenum('');
                    Swal.fire({
                        title: 'Invalid File',
                        type: 'error',
                        html: `File type( <span style='color:red'>${extension} </span>): not suppored!`
                    }).then(resetRecipients(true));
                }
            }
        });
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
    }

    init_body_events() {
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
            .on('click', '#mc-edits legend', function () {
                $(this).nextAll('div').fadeToggle('slow');
            });
    }
    init_dom_events() {
        if (this.b_inEmailFunctions) {
            // hijacks default 'view email' button for SweetAlert2 action!
            $('a.m-link').bind('click', (e) => {
                e.preventDefault();
                e.stopImmediatePropagation();
                var rel = e.target.rel;
                sweetAlertbyID(`.${rel}`);
            });
            this.mail_type.change(function () {
                this.plaintext.toggle(this.val() === 'html');
            });
            /** TODO: instantiate toggleInitState(array(elements)) */
            ManyMailerPlus_mod.btn_reset.hide();
            this.show_csv_recipient_fieldset(false);
            ManyMailerPlus_mod.con_recip_review.toggle(false);
            con_embed_tmps.toggle(false);
            ManyMailerPlus_mod.con_tmp_name.toggle(false);
            ManyMailerPlus_mod.recipient.prop('readonly', true).change(this.countEmails()).click(function () {
                this.show_message({
                    title: 'Invalid!',
                    html: 'Please enter emails using csv entry (file upload/paste).',
                    type: 'error'
                });
            });
            ManyMailerPlus_mod.sel_csv_entry.change(function () {
                let showTextEntry = this.value === 'csv_recipient';
                ManyMailerPlus_mod.con_file_recipient.toggle(!showTextEntry);
                ManyMailerPlus_mod.con_csv_recipient.toggle(showTextEntry);
            });
            ManyMailerPlus_mod.csv_recipient
                .bind('interact', (e) => {
                    if (e.currentTarget.value === '') {
                        TLN.remove_line_numbers('csv_recipient');
                    } else {
                        TLN.append_line_numbers('csv_recipient');
                    }
                })
                .wrap('<div id="csv_recipient_wrapper" ></div>');
            ManyMailerPlus_mod.file_recipient.change((evt) => {
                this.resetRecipients();
                var fileType = /csv.*/;
                var file = evt.target.files[0];
                if (file) {
                    if (file.type.match(fileType) || file.name.slice(-3) === 'csv') {
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            csv_recipient.val_with_linenum(reader.result).parents('fieldset').show();
                        };
                        reader.readAsText(file);
                    } else {
                        var extension = file.type !== '' ? file.type : file.name.slice(file.name.indexOf('.'));
                        ManyMailerPlus_mod.csv_recipient.val_with_linenum('');
                        this.show_message({
                            title: 'Invalid File',
                            type: 'error',
                            html: `File type( <span style='color:red'>${extension} </span>): not suppored!`
                        }).then(resetRecipients(true));
                    }
                }
            });
            $('input[name=use_templates]').change(function () {
                var toggle = this.value === 'y' ? 'slow' : false;
                ManyMailerPlus_mod.con_embed_tmps.fadeToggle(toggle);
                ManyMailerPlus_mod.con_tmp_name.fadeToggle(toggle);
            });
            $('[name$=linenum], #reset').bind('click', (e) => {
                this.resetRecipients(true);
            });

            $('button[name=convert_csv]').bind('click', (e) => {
                this.convertCSV();
                ManyMailerPlus_mod.sel_csv_entry.val('file_recipient').trigger('change');
            });

            ManyMailerPlus_mod.tmp_selections.change(function () {
                var name,
                    subject,
                    message = '';
                var details = $('fieldset#mc-edits');
                if (details.length > 0) {
                    details.remove();
                }
                if (this.checked) {
                    var sections = [];
                    var element, attributes, attribute;
                    name = this.value;
                    subject = this.dataset.confirm;
                    var choice = document.getElementById(name + '-code');
                    if (choice !== null) {
                        $('input[name="selection[]"]')
                            .not(this)
                            .attr('checked', false)
                            .parents('tr')
                            .removeClass('selected');
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
                                        console.log(
                                            attribute.name + '(' + element.nodeType + ')',
                                            '=>',
                                            attribute.value
                                        );
                                    }
                                }
                            }
                        }
                        ManyMailerPlus_mod.create_editable_content(sections);
                    }
                    $('legend').trigger('click');
                }
                ManyMailerPlus_mod.tmp_name.val(name);
                $('input[name=subject]').val(subject);
            });
        }
    }

    prep_data_for_parse(data) {
        // remove validation errors
        var current_csv = this.get_csv_recip();
        if (current_csv[0] === current_csv[0].toUpperCase()) {
            current_csv.shift();
            ManyMailerPlus_mod.csv_recipient.val_with_linenum(current_csv.join('\n'));
            str = this.get_csv_recip();
        }
        if (str === '') {
            this.show_message({
                title: 'No CSV Data Provided',
                type: 'error'
            });
            return;
        }
        return str;
    }

    parseData(str_data) {
        str_data = this.prep_data_for_parse(str_data);
        // config
        let Papa_config = {
            header: true,
            skipEmptyLines: 'greedy',
            error: (e, file) => {
                this.showPapaErrors(data.errors);
                return;
            },
            complete: (results, file) => {
                var data = {};
                data.headers = results.meta.fields;
                data.errors = results.errors;
                data.data = results.data;

                if (data.data.length === 0) {
                    this.showPapaErrors(data.errors);
                    return;
                }
                data.dtCols = [];
                data.dtData = [];

                data.headers.forEach((col) => {
                    data.dtCols.push({
                        title: col
                    });
                });

                data.data.forEach((itm) => {
                    var itmVals = Object.values(itm);
                    if (itmVals.length !== data.dtCols.length) {
                        var diff = data.dtCols.length - itmVals.length;
                        do {
                            itmVals.push('');
                            diff--;
                        } while (diff > 0);
                    }
                    data.dtData.push(itmVals);
                });

                data.string = Papa.unparse({
                    fields: data.headers,
                    data: data.dtData
                });

                if (this.validate(data)) {
                    initTable(data);
                }
                console.log('Parsing: Processing ', results, file);
                console.log('Parsing Complete:', data);
            }
        };
        return Papa.parse(str_data, Papa_config);
    }

    showPapaErrors(arr_errors) {
        var errMsgs = {};
        var ul = $('<ul class="errCode" />');
        // consolidate error array
        arr_errors.forEach((err) => {
            if (!Object.keys(errMsgs).includes(err.type)) {
                errMsgs[err.type] = {};
            }
            if (!Object.keys(errMsgs[err.type]).includes(err.code)) {
                errMsgs[err.type][err.code] = {
                    affected: []
                };
            }
            errMsgs[err.type][err.code].message = err.message;
            var linenum_offset = 2;
            if (!errMsgs[err.type][err.code].affected.includes(err.row + linenum_offset)) {
                errMsgs[err.type][err.code].affected.push(err.row + linenum_offset);
            }
        });
        Object.keys(errMsgs).forEach((type) => {
            var li = $('<li />', {
                text: type
            });
            var sub_ul = $('<ul />');
            Object.keys(errMsgs[type]).forEach((code) => {
                $('<li />')
                    .text(errMsgs[type][code].message)
                    .append(
                        $('<p />', {
                            text: 'Affected Row(s): ' + errMsgs[type][code].affected.join(', ')
                        })
                    )
                    .appendTo(sub_ul);
            });
            li.append(sub_ul).appendTo(ul);
        });

        ul.appendTo(con_errors);
        this.show_message({
            title: 'Errors',
            type: 'error',
            html: Object.keys(errMsgs).join(', ')
        });
        return true;
    }

    resetRecipients(all) {
        if (all) {
            ManyMailerPlus_mod.csv_recipient
                .val_with_linenum('')
                .parents('fieldset')
                .toggle($('select[name=recipient_entry]').val() === 'csv_recipient');
            // reset upload
            ManyMailerPlus_mod.file_recipient.wrap('<form>').closest('form').get(0).reset().unwrap();
        }
        // reset emails and errors
        ManyMailerPlus_mod.recipient.val('');
        con_errors.html('');

        // reset recipient label
        this.countEmails();

        $('#placeholder').parent().remove();
        // reset table

        var parent = $('#csv_content_wrapper').parent();
        parent.empty();
        var table = $("<table id='csv_content' class='fixed_header'></table>");
        parent.wrapInner(table);

        ManyMailerPlus_mod.btn_reset.hide();
    }

    show_csv_recipient_fieldset(show) {
        ManyMailerPlus_mod.con_placeholder.toggle(show);
    }

    static create_editable_content(sections) {
        var email_body = [
            'main',
            'content'
        ];
        var found = sections.find(function (el) {
            return $.inArray(el.edit_section, email_body) !== -1;
        });
        var suggested = found ? `(suggested: <b>'${found.edit_section}')</b>` : '';
        var fs = $('<fieldset id="mc-edits" />').append('<legend class="btn">Editable Content</legend>');

        sections.forEach((el_obj) => {
            var id = el_obj.edit_section;
            var val = el_obj.content;
            var parent = ManyMailerPlus_mod.con_tmp_name.eq(0);
            if (ManyMailerPlus_mod.tmp_editables.length === 0) {
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

            $('input[name^="mc-check"').change(function () {
                var chk = this.checked;
                $('input[name^="mc-check"').not(this).each(function (el) {
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

    init_service_list() {
        ManyMailerPlus_mod.service_list
            .attr('action-url', 'admin.php?/cp/addons/settings/manymailerplus/services/')
            .addClass('service-list');
        if (ManyMailerPlus_mod.active_services) {
            ManyMailerPlus_mod.show_active_services();
        } else {
            ManyMailerPlus_mod.service_list.hide();
        }

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
        //                         debugger
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
    }

    static show_active_services() {
        $.each(service_list.children(), function () {
            var list_item = $(this).text().toLowerCase();
            var val = ManyMailerPlus_mod.active_services.val();
            if (val && val.indexOf(list_item) > -1) {
                $(this).addClass('enabled-service');
            } else {
                $(this).addClass('disabled-service');
            }
            $(this).attr('data-service', list_item);
        });
    }

    init_sweet() {
        $('a.m-link').bind('click', (e) => {
            e.preventDefault();
            e.stopImmediatePropagation();
            var rel = e.target.rel;
            this.sweetAlertbyID(`.${rel}`);
        });
    }

    showPlaceholders(headers) {
        var el_exists = document.getElementById('placeholder');
        if (ManyMailerPlus_mod.con_placeholder) {
            ManyMailerPlus_mod.con_placeholder.remove();
        }
        $('<div />', {
            id: 'stick-here',
            class: 'stick-here',
            height: $('div.col.w-12').height()
        })
            .append("<table id='placeholder'><caption>Placeholders</caption></table>")
            .appendTo('.sidebar');

        headers.forEach((el) => {
            var test = $('<button/>', {
                class: 'btn placeholder',
                text: el,
                click: function () {
                    var plain = $("textarea[name='plaintext_alt']");
                    var msg = $("textarea[name='message']");
                    var message = $("textarea[name='plaintext_alt']").is(':visible') ? plain : msg;

                    // Insert text into textarea at cursor position and replace selected text
                    var cursorPosStart = message.prop('selectionStart');
                    var cursorPosEnd = message.prop('selectionEnd');
                    var insertedText = $(this).text() + ' ';
                    var v = message.val();
                    var textBefore = v.substring(0, cursorPosStart);
                    var textAfter = v.substring(cursorPosEnd, v.length);
                    message.val(textBefore + insertedText + textAfter);
                    $('textarea[name=message]').caretTo(insertedText, true);
                }
            })
                .wrap('<tr><td align="center"></td></tr>')
                .closest('tr');

            ManyMailerPlus_mod.con_placeholder.append(test);
        });
    }

    displayCSVErrors(errs, errDetail) {
        var title = '';
        var msg = $("<ul style='color:red' />");
        var detail = $("<ul style='white-space: pre-wrap; color:red' />");
        errs.forEach((element) => {
            title += element + '<br />';
            var itm = $('<li />', {
                text: element
            });
            msg.append(itm);
        });
        errDetail.forEach((element) => {
            var dt = $('<dt/>', {
                text: element
            });
            detail.append(dt);
            msg.append(detail);
        });
        con_errors.prepend(msg);
        this.show_message({
            title: title,
            type: 'error',
            html: detail
        });
    }

    countEmails() {
        var emails = recipient.val().split(',');
        var count = emails[0] === '' ? 0 : emails.length;

        var label = ManyMailerPlus_mod.csv_recipient.parent().prev().find('label');
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
    }

    get_csv_recip() {
        return ManyMailerPlus_mod.csv_recipient.val().split(/\n+/g).trim();
    }

    convertCSV() {
        this.resetRecipients();
        this.parseData(this.get_csv_recip());
        if (this.csvObj) {
            this.setFormValues();
            this.countEmails();
            console.dir(this.csvObj);
            return true;
        }
    }

    setFormValues() {
        this.showPlaceholders(this.csvObj.headers);
        $('input[name="csv_object"]').val(this.csvObj.data_string);
        $("input[name='recipient_count']").val(this.csvObj.recipient_count);
        $("input[name='mailKey']").val(this.csvObj.mailKey);
        $('input[name=recipient]').val(this.csvObj.email_list);
    }

    getEmails(data) {
        this.csvObj = this.validate(data);
        $('#reset').show();
        if (csvObj) {
            $('input[name="csv_object"]').val(csvObj.data_string);
            $("input[name='recipient_count']").val(csvObj.recipient_count);
            $("input[name='mailKey']").val(csvObj.mailKey);
            ManyMailerPlus_mod.recipient.val(csvObj.email_list);
            showPlaceholders(csvObj.headers);
            countEmails();
            console.dir(csvObj);
            return true;
        }
        return false;
    }

    validate(data) {
        return this.csvValidator.validate_csv(data) !== undefined;
    }

    procReq(data, query = false) {
        if (query) {
            return this.qs2json(data);
        }
        // console.log(data);
        logs = data.substring(0, data.lastIndexOf('</script>') + 9);
        // console.log(logs);
        var d1 = document.getElementsByTagName('head')[0];
        d1.insertAdjacentHTML('beforeend', logs);
        data = data.substring(logs.length);
        // console.log(data);
        return data === '' ? logs.replace(/<\/?[^>]+(>|$)/g, '') : isJson(data) ? JSON.parse(data) : data;
    }

    qs2json(data) {
        var pairs = data.split('&');
        var retVals = decodeURIComponent(pairs[0]).replace('=', ':');
        return JSON.parse(JSON.stringify('{' + retVals + '}'));
    }

    initTable(data) {
        ManyMailerPlus_mod.csv_recipient.val_with_linenum('');
        return $('#csv_content').addClass('fixed_header display').DataTable({
            defaultContent: '',
            dom: '<"top"i>rt<"bottom"flp><"clear">',
            initComplete: function () {
                var api = this.api();
                api.$('td').click(function () {
                    api.search(this.innerHTML).draw();
                });
            },
            columns: data.dtCols,
            data: data.dtData,
            paging: false,
            ordering: false
        });
    }

    static dumpHiddenVals() {
        var msg = $('<table/>');
        $('input[type="hidden"]').each(function () {
            var val = $(this).val();
            val = val.length > 100 ? val.substring(0, 100) + '...' : val;
            console.log($(this).attr('name') + ': ' + $(this).val());
            msg.append('<tr><td>' + $(this).attr('name') + '</td><td>' + val + '</td></tr>');
        });
        ManyMailerPlus_mod.show_message({
            title: 'HIDDEN VALS',
            type: 'info',
            html: msg,
            width: '80%'
        });
    }

    static dumpFormVals() {
        var msg = $('<table/>');
        $('form :input').each(function () {
            var val = this.value;
            val = val.length > 100 ? val.substring(0, 100) + '...' : val;
            val = val === 'on' || val === 'off' ? this.checked : val;
            console.log(`${this.name}: ${this.value}`);
            msg.append(`<tr><td>${this.name}</td><td>${val}</td></tr>`);
        });
        var frmStr = JSON.stringify($('form').serialize());
        ManyMailerPlus_mod.show_message({
            title: 'Form VALS',
            type: 'info',
            html: msg,
            width: '80%'
        });
    }

    isJson(item) {
        item = typeof item !== 'string' ? JSON.stringify(item) : item;
        try {
            item = JSON.parse(item);
        } catch (e) {
            return false;
        }

        if (typeof item === 'object' && item !== null) {
            return true;
        }
        console.log(item);
        return false;
    }

    test_funcs() {
        $('#btnData').on('click', function (e) {
            var url = document.getElementsByClassName('service-list')[0].getAttribute('action-url');
            Swal.fire({
                title: 'Select Fuction',
                input: 'select',
                inputOptions: {
                    update_service_order: 'Update SO',
                    get_settings: 'Get Settings',
                    get_service_order: 'Get SO',
                    get_active_services: 'Active',
                    get_initial_service: 'Priority Service'
                },
                inputPlaceholder: 'Select Function',
                showCancelButton: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: (value) => {
                    return $.post(url + value).always(function (jqXHR) {
                        // debugger
                        var data;
                        if (jqXHR.hasOwnProperty('responseText')) {
                            data = jqXHR.responseText;
                        } else {
                            data = jqXHR;
                        }
                        if (isJson(data)) {
                            data = jqXHR;
                        } else {
                            data = procReq(data);
                        }
                        console.dir(data);
                        data = JSON.stringify(data, null, 4);

                        Swal.fire({
                            type: 'question',
                            html: data
                        });
                    });
                }
            });
        });
    }
}

$(document).ready(function () {
    'use strict';

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
    // debugger;
    MMP.init();
});