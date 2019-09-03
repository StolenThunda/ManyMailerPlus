// CSV Manipulation
class csvManager {
    csvObj: {};
    constructor() {
       

    }

    countEmails() {
        let email_string = $('input[name=recipient]').val();
        let emails = email_string.split(',');
        let count = emails[0] === '' ? 0 : emails.length;

        let label = $('input[name=recipient]').parent().prev().find('label');
        let origText = label.text();
        // preserve  original label just append count string
        if (origText.includes('Count')) {
            let idx = origText.indexOf(' (Count: ');
            if (idx > -1) {
                origText = origText.substr(0, idx);
            }
        }
        let countText = count > 0 ? ` (Count: ${count})` : '';
        label.text(origText + countText);
    }

    getEmails(data) {
        resetRecipients();
        if (typeof data === 'undefined') {
            data = $('#csv_recipient').val();
            if (data === '') {
                Swal.fire({
                    title: 'No Data',
                    type: 'error',
                    text: 'No csv data provided!'
                });
                return;
            } else {
                parseData(data);
            }
        }

        csvObj = validate_csv(data);
        $('#reset').show();
        if (csvObj) {
            $('input[name="csv_object"]').val(csvObj.data_string);
            $("input[name='recipient_count']").val(csvObj.recipient_count);
            $("input[name='mailKey']").val(csvObj.mailKey);
            $('input[name=recipient]').val(csvObj.email_list);
            showPlaceholders(csvObj.headers);
            countEmails();
            console.dir(csvObj);
            return true;
        }
        return false;
    }

    validate_csv(data) {
        let errs = [];
        let errDetail = [];
        let header_valid, email_column;
        let rowdata = Object.values(data.data[0]);
        let first_row_values = rowdata.filter((val) => !Array.isArray(val));

        // email column in file?
        let file_contains_emails = first_row_values.filter((word) => isValidEmailAddress(word)).length > 0;

        // validate required columns
        let required_columns = validateRequiredKeys(data.headers);
        if (required_columns.errors.length > 0) {
            errDetail = errDetail.concat(required_columns.errors);
        }
        header_valid = required_columns.validHeader;
        email_column = required_columns.email_column;

        // generate email list and generate converted data to obj with tokenized keys
        let emails = [];
        let tokenized_obj = [];
        data.data.forEach((row) => {
            let newRow = {};
            let token_key;
            let current_email = row[email_column.original];
            if (email_column) {
                if (current_email && isValidEmailAddress(current_email)) {
                    emails.push(current_email.trim());
                } else {
                    let tmp = 'Column (' + email_column.original + '): does not contain email data';
                    if (!errDetail.includes(tmp)) {
                        errDetail.unshift(tmp);
                    }
                }
            }
            for (let itm in row) {
                token_key = required_columns.headerKeyMap[itm];
                newRow[token_key] = row[itm];
            }
            tokenized_obj.push(newRow);
        });

        if (!header_valid) {
            errs.unshift('Invalid Header');
        }
        if (!email_column || !file_contains_emails) {
            errDetail.unshift('No Valid Email Column Header Found');
        }
        if (file_contains_emails && !email_column) {
            errDetail.unshift('Email column is mislabeled');
        }
        if (!required_columns) {
            errDetail.unshift('Required Columns: email, first_name, last_name');
        }
        if (errs.length === 0) {
            return {
                mailKey: required_columns.email_column.val,
                headers: Object.values(required_columns.headerKeyMap),
                data_string: JSON.stringify(tokenized_obj),
                data: tokenized_obj,
                rawdata: data.data,
                recipient_count: emails.length,
                validated_data: required_columns,
                email_list: emails.join(',')
            };
        } else {
            displayCSVErrors(errs, errDetail);
            let current_csv = $('#csv_recipient').val().split(/\n+/g);
            current_csv.unshift(errs[0].toUpperCase());
            let new_csv = current_csv.join('\n');
            $('#csv_recipient').val_with_linenum(new_csv);

            return null;
        }
    }

    intersection(d, regX) {
        let dataArray = d;
        let foundColumn = [];
        dataArray.forEach((csvColumn, idx) => {
            let testColumn = csvColumn.toLowerCase().trim();
            if (regX.test(testColumn)) {
                foundColumn.push({
                    original: csvColumn.trim(),
                    val: tokenizeKey(testColumn)
                });
            }
        });
        return foundColumn;
    }

    validateRequiredKeys(data) {
        let validHeaders = {
            email_column: [
                'mail',
                'email',
                'address',
                'e-mail'
            ],
            first: [
                'first',
                'given',
                'forename'
            ],
            last: [
                'last',
                'surname'
            ]
        };
        let regexify = (arr) => {
            return new RegExp(arr.join('|'), 'i');
        };
        let header_has_no_email_data = data.filter((word) => isValidEmailAddress(word)).length === 0;
        let email_column = intersection(data, regexify(validHeaders.email_column));
        let first = intersection(data, regexify(validHeaders.first));
        let last = intersection(data, regexify(validHeaders.last));
        let reqKeys = {
            email_column: email_column.length > 0 ? email_column[0] : false,
            first: first.length > 0 ? first[0] : '',
            last: last.length > 0 ? last[0] : '',
            headerKeyMap: header_has_no_email_data ? genKeyMap(data) : data,
            errors: []
        };
        let invalidColumns = Object.keys(reqKeys).filter((k) => {
            let isNotSet = reqKeys[k] === '' || reqKeys[k] === false;
            if (isNotSet) {
                reqKeys.errors.push(`Acceptable Values for ${k}: ${validHeaders[k]}`);
            }
        });
        reqKeys.validHeader = reqKeys.errors.length === 0 && header_has_no_email_data;
        return reqKeys;
    }

    genKeyMap(data) {
        let obj = {};
        Object.values(data).forEach((key) => {
            obj[key] = tokenizeKey(key);
        });
        return obj;
    }

    isValidEmailAddress(emailAddress) {
        let pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
        return pattern.test(emailAddress.trim());
    }

    tokenizeKeys(data) {
        let newData = [];
        data.forEach((key) => {
            newData.push(tokenizeKey(key));
        });
        return newData;
    }

    tokenizeKey(key) {
        return '{{' + key.trim().toLowerCase().replace(' ', '_') + '}}';
    }
    showPapaErrors(errorArray) {
        let errMsgs = {};
        let ul = $('<ul class="errCode" />');
        // consolidate error array
        errorArray.forEach((err) => {
            if (!Object.keys(errMsgs).includes(err.type)) {
                errMsgs[err.type] = {};
            }
            if (!Object.keys(errMsgs[err.type]).includes(err.code)) {
                errMsgs[err.type][err.code] = {
                    affected: []
                };
            }
            errMsgs[err.type][err.code].message = err.message;
            let linenum_offset = 2;
            if (!errMsgs[err.type][err.code].affected.includes(err.row + linenum_offset)) {
                errMsgs[err.type][err.code].affected.push(err.row + linenum_offset);
            }
        });
        Object.keys(errMsgs).forEach((type) => {
            let li = $('<li />', {
                text: type
            });
            let sub_ul = $('<ul />');
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

        ul.appendTo('#csv_errors');
        Swal.fire({
            title: 'Errors',
            type: 'error',
            html: Object.keys(errMsgs).join(', ')
        });
        return true;
    }

}

/*
    Utilities
*/
class Utilities {
    TLN: { eventList: {}; update_line_numbers: (ta: any, el: any) => void; append_line_numbers: (id: any) => void; remove_line_numbers: (id: any) => void; };
    constructor() {
        this.TLN = {
            eventList: {},
            update_line_numbers: function(ta, el) {
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
            append_line_numbers: function(id) {
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
                const __change_hdlr = (function(ta, el) {
                    return function(e) {
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
                const __scroll_hdlr = (function(ta, el) {
                    return function() {
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
            remove_line_numbers: function(id) {
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
    }
    sweetAlertbyID(id: any) {
        let html = $(id).html();
        let title = $($.parseHTML(html)).find('h1').text();
        let info = $($.parseHTML(html)).find('.txt-wrap').html();
        Swal.fire(title, info, 'info');
    }
    isJson(item: string) {
        item = typeof item !== "string" ? JSON.stringify(item) : item;
        try {
            item = JSON.parse(item);
        } catch (e) {
            return false;
        }

        if (typeof item === "object" && item !== null) {
            return true;
        }
        console.log(item);
        return false;
    }
}

sweetAlertbyID(id) {
    let html = $(id).html();
    let title = $($.parseHTML(html)).find('h1').text();
    let info = $($.parseHTML(html)).find('.txt-wrap').html();
    Swal.fire(title, info, 'info');
}

}
/*
 Page Specific
*/
class theEngine {
    service_list: any;
    active_services: any;
    csv_manager: csvManager;
    constructor() {
        $.fn.extend({
            val_with_linenum: function(v) {
                return this.each(() => {
                    $(this).val(v).trigger('input');
                });
            }
        });

        let this.service_list = $('h2:contains("Services")').next('ul');
        let this.active_services = $('#active_services').val();
        $('input[name=recipient]').prop('readonly', true);
        $('input[name=recipient]').change(countEmails);
        $("select[name='mailtype']").change(messageType);

        $('fieldset[data-control=recipient_review').toggle();

        $('select[name=recipient_entry]').change(function() {
            $('input[name=file_recipient]').parents('fieldset').toggle('slow');
            $('#csv_recipient').parents('fieldset').toggle('slow');
        });

        $('#embed_templates').toggle();
        $('#template_name').parents('fieldset').toggle();

        // Prepare sections by setting the `data-parsley-group` attribute to 'block-0', 'block-1', etc.
        $sections.each(function(index, section) {
            $(section).find(':input').attr('data-parsley-group', 'block-' + index);
        });
        $('body').on('click', '#mc-edits legend', function() {
            $(this).nextAll('div').fadeToggle('slow');
        });
        // hijacks default 'view email' button for SweetAlert2 action!
        $('a.m-link').bind('click', (e) => {
            e.preventDefault();
            e.stopImmediatePropagation();
            let rel = e.target.rel;
            sweetAlertbyID(`.${rel}`);
        });
        $('body').on('click', '*[data-conditional-modal]', function(e) {
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
                        Swal.fire('Deleted!', 'Your file has been deleted.', 'success');
                    }
                });
            $('.app-overlay').removeClass('app-overlay---open');
            return;
        });
        $('#btnData').on('click', function(e) {
            let url = document.getElementsByClassName('service-list')[0].getAttribute('action-url');
            Swal.fire({
                title: 'Select Fuction',
                input: 'select',
                inputOptions: {
                    'update_service_order': 'Update SO',
                    'get_settings': 'Get Settings',
                    'get_service_order': 'Get SO',
                    'get_active_services': 'Active',
                    'get_initial_service': 'Priority Service'
                },
                inputPlaceholder: "Select Function",
                showCancelButton: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: (value) => {
                    return $.post(url + value)
                        .always(function(jqXHR) {
                            // debugger
                            let data;
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
                                html: data,
                            });
                        });
                }
            });
        });
        // Page Events
        $('input[readonly]').click(function() {
            Swal.fire('Invalid!', 'Please enter emails using csv entry (file upload/paste).', 'error');
        });

    }

    (function() {
        // Check for the letious File API support.
        if (window.File && window.FileReader && window.FileList && window.Blob) {
            // Great success! All the File APIs are supported.
            console.log('Great success! All the File APIs are supported.\n CSV uploads enabled!');
            return true;
        } else {
            // source: File API availability - http://caniuse.com/#feat=fileapi
            // source: <output> availability - http://html5doctor.com/the-output-element/
            let title = 'CSV Upload feature is disabled';
            let warning = 'The HTML5 APIs used in this form are only available in the following browsers:<br />';
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
    })();


    function resetRecipients(all) {
        if (all) {
            $('#csv_recipient').val_with_linenum('');
            // reset upload
            let file_recip = $('input[name=file_recipient');
            file_recip.wrap('<form>').closest('form').get(0).reset();
            file_recip.unwrap();
        }
        // reset emails and errors
        $('input[name=recipient]').val('');
        $('#csv_errors').html('');

        // reset recipient label
        countEmails();

        $('#placeholder').parent().remove();
        // reset table

        let parent = $('#csv_content_wrapper').parent();
        parent.empty();
        let table = $("<table id='csv_content' class='fixed_header'></table>");
        parent.wrapInner(table);

        $('#reset').hide();
    }

    function messageType() {
        if ($("select[name='mailtype']").val() === 'html') {
            $("textarea[name='plaintext_alt']").parents('fieldset').eq(0).toggle('slow');
        } else {
            $("textarea[name='plaintext_alt']").parents('fieldset').eq(0).toggle('slow');
        }
    }
    // MMP functions
    // Behind the scenes method deals with browser
    // idiosyncrasies and such
    $.caretTo = function(el, index) {

        if (el.createTextRange) {
            let range = el.createTextRange();
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
                let i = $(this).val().indexOf(index);
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

showPlaceholders(headers) {
    let el_exists = document.getElementById('placeholder');
    if (el_exists) {
        $('#placeholder').remove();
    }
    $('<div />', {
            id: 'stick-here',
            class: 'stick-here',
            height: $('div.col.w-12').height()
        })
        .append("<table id='placeholder'><caption>Placeholders</caption></table>")
        .appendTo('.sidebar');

    headers.forEach((el) => {
        let test = $('<button/>', {
                class: 'btn placeholder',
                text: el,
                click: function() {
                    let plain = $("textarea[name='plaintext_alt']");
                    let msg = $("textarea[name='message']");
                    let message = $("textarea[name='plaintext_alt']").is(':visible') ? plain : msg;

                    // Insert text into textarea at cursor position and replace selected text
                    let cursorPosStart = message.prop('selectionStart');
                    let cursorPosEnd = message.prop('selectionEnd');
                    let insertedText = $(this).text() + " ";
                    let v = message.val();
                    let textBefore = v.substring(0, cursorPosStart);
                    let textAfter = v.substring(cursorPosEnd, v.length);
                    message.val(textBefore + insertedText + textAfter);
                    $('textarea[name=message]').caretTo(insertedText, true);
                }
            })
            .wrap('<tr><td align="center"></td></tr>')
            .closest('tr');

        $('#placeholder').append(test);
    });
}

displayCSVErrors(errs, errDetail) {
    let title = '';
    let msg = $("<ul style='color:red' />");
    let detail = $("<ul style='white-space: pre-wrap; color:red' />");
    errs.forEach((element) => {
        title += element + '<br />';
        let itm = $('<li />', {
            text: element
        });
        msg.append(itm);
    });
    errDetail.forEach((element) => {
        let dt = $('<dt/>', {
            text: element
        });
        detail.append(dt);
        msg.append(detail);
    });
    $('#csv_errors').prepend(msg);
    Swal.fire({
        title: title,
        type: 'error',
        html: detail
    });
}
initTable(data) {
    $('#csv_recipient').val_with_linenum('');
    // $('fieldset[data-control=recipient_review').toggle('slow');
    return $('#csv_content').addClass('fixed_header display').DataTable({
        defaultContent: '',
        dom: '<"top"i>rt<"bottom"flp><"clear">',
        initComplete: function() {
            let api = this.api();
            api.$('td').click(function() {
                api.search(this.innerHTML).draw();
            });
        },
        columns: data.dtCols,
        data: data.dtData,
        paging: false,
        ordering: false
    });
}

parseData(str) {
    // remove validation errors
    let current_csv = $('#csv_recipient').val().split(/\n+/g);
    if (current_csv[0] === current_csv[0].toUpperCase()) {
        current_csv.shift();
        $('#csv_recipient').val_with_linenum(current_csv.join('\n'));
        str = $('#csv_recipient').val();
    }
    if (str === '') {
        Swal.fire({
            title: 'No CSV Data Provided',
            type: 'error'
        });
        return;
    }
    return Papa.parse(str, {
        header: true,
        skipEmptyLines: 'greedy',
        error: (e, file) => {
            showPapaErrors(data.errors);
            return;
        },
        complete: (results, file) => {
            let data = {};
            data.headers = results.meta.fields;
            data.errors = results.errors;
            data.data = results.data;

            if (data.data.length === 0) {
                showPapaErrors(data.errors);
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
                let itmVals = Object.values(itm);
                if (itmVals.length !== data.dtCols.length) {
                    let diff = data.dtCols.length - itmVals.length;
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

            if (getEmails(data)) {
                initTable(data);
            }
            console.log('Parsing: Processing ', results, file);
            console.log('Parsing Complete:', data);
        }
    });
}
$('#csv_recipient')
    .bind('interact', (e) => {
        if (e.currentTarget.value === '') {
            TLN.remove_line_numbers('csv_recipient');
        } else {
            TLN.append_line_numbers('csv_recipient');
        }
    })
    .wrap('<div id="csv_recipient_wrapper" ></div>');
$('#reset').hide();

$('input[name="selection[]"').change(function() {
    let name,
        subject,
        message = '';
    let details = $('fieldset#mc-edits');
    if (details.length > 0) {
        details.remove();
    }
    if (this.checked) {
        let sections = [];
        let element, attributes, attribute;
        name = this.value;
        subject = this.dataset.confirm;
        let choice = document.getElementById(name + '-code');
        if (choice !== null) {
            $('input[name="selection[]"]').not(this)
                .attr('checked', false)
                .parents('tr')
                .removeClass('selected');
            message = choice.innerHTML;
            let test_element = document.createElement('div');
            test_element.innerHTML = message;
            let list = test_element.getElementsByTagName('*');
            for (let j = 0; j < list.length; j++) {
                element = list[j];
                attributes = element.attributes;
                if (element.attributes) {
                    for (let i = 0; i < attributes.length; i++) {
                        attribute = attributes[i];
                        if (attribute.name.startsWith('mc:')) {
                            if (attribute.value !== "") {
                                sections.push({
                                    'edit_section': attribute.value,
                                    'content': element.innerHTML
                                });
                            }
                            console.log(attribute.name + '(' + element.nodeType + ')', '=>', attribute.value);
                        }
                    }
                }
            }
            createEC(sections);
        }
        $('legend').trigger('click');
    }
    $('#template_name').val(name);
    $('input[name=subject]').val(subject);
});

function createEC(sections) {
    let email_body = ['main', 'content'];
    let found = sections.find(function(el) {
        return ($.inArray(el.edit_section, email_body) !== -1);
    });
    let suggested = (found) ? `(suggested: <b>'${found.edit_section}')</b>` : "";
    sections.forEach((el_obj) => {
        let id = el_obj.edit_section;
        let val = el_obj.content;
        let parent = $('#template_name').parents('fieldset').eq(0);
        let fs = $('fieldset#mc-edits');
        if (fs.length === 0) {
            fs = $('<fieldset id="mc-edits" />');
            let legend = $('<legend class="btn">Editable Content</legend>');
            fs.append(legend);
            parent.after(fs);
            fs.append(
                $('<div>')
                .addClass('field-instruct')
                .append($(`<label><em>Choose the section represented by the email body ${suggested} </em></label>`))
            );
        }

        fs.append(
            $('<div>')
            .addClass('field-instruct')
            .append($(`<label>${id}</label>`)
                .css('color', 'red')
                .css('font-size', '20px')
            )
            .append($(`<input type="checkbox" " name="mc-check_${id}" id="mc-check_${id}" />`, {
                'data-parsley-mincheck': "1",
                'data-parsley-multiple': "mc-check"
            }))
            .append($(`<label for="mc-check_${id}">(Body?)</label>`)
                .css('text-align', 'right')
                .css('display', 'inline-block')
            ),
            $('<div>')
            .addClass('field-control')
            .append($(`<textarea value="${id}" name="mc-edit[${id}]" rows="10" cols="50">${val}</textarea>`))
        );

        $('input[name^="mc-check"').change(function() {
            let chk = this.checked;
            $('input[name^="mc-check"').not(this).each(function(el) {
                if (chk) {
                    $(this)
                        .attr('checked', false)
                        .hide();
                    $(`label[for=${this.name}]`).hide();
                } else {
                    $(`label[for=${this.name}]`).show();
                    $(this).show();
                }

            });
            let name = this.name.substr('mc-check_'.length);
            console.log(name);
        });

    });

}
$('input[name=use_templates]').change(function() {
    let toggle = this.value === 'y' ? 'slow' : false;
    $('#embed_templates').fadeToggle(toggle);
    $('#template_name').parents('fieldset').fadeToggle(toggle);
});
$('[name$=linenum], #reset').bind('click', (e) => {
    resetRecipients(true);
});
$('button[name=convert_csv]').bind('click', (e) => {
    resetRecipients();
    let val = $('#csv_recipient').val();
    parseData(val.trim());
    $('select[name=recipient_entry]').val('file_recipient').trigger('change');
});

$('body').on('click', '*[data-conditional-modal]', function(e) {
    e.preventDefault();
});
dumpHiddenVals() {
    let msg = $('<table/>');
    $('input[type="hidden"]').each(function() {
        let val = $(this).val();
        val = val.length > 100 ? val.substring(0, 100) + '...' : val;
        console.log($(this).attr('name') + ': ' + $(this).val());
        msg.append('<tr><td>' + $(this).attr('name') + '</td><td>' + val + '</td></tr>');
    });
    swal.fire({
        title: 'HIDDEN VALS',
        type: 'info',
        html: msg,
        width: '80%'
    });
}

dumpFormVals() {
    let msg = $('<table/>');
    $('form :input').each(function() {
        let val = this.value;
        val = val.length > 100 ? val.substring(0, 100) + '...' : val;
        val = (val === 'on' || val === 'off') ? this.checked : val;
        console.log(`${this.name}: ${this.value}`);
        msg.append(`<tr><td>${this.name}</td><td>${val}</td></tr>`);
    });
    let frmStr = JSON.stringify($('form').serialize());
    swal.fire({
        title: 'Form VALS',
        type: 'info',
        html: msg,
        width: '80%'
    });
}
}

class stepUtilities {
    constructor() {
        // Previous button is easy, just go back
        $('.form-navigation .previous').click(function() {
            navigateTo(curIndex() - 1);
        });

        // Next button goes forward iff current block validates
        $('.form-navigation .next').click(function() {
            $('.demo-form')
                .parsley()
                .whenValidate({
                    group: 'block-' + curIndex()
                })
                .done(function() {
                    navigateTo(curIndex() + 1);
                });
        });
    }
    navigateTo(index) {
        // Mark the current section with the class 'current'
        $sections.removeClass('current').eq(index).addClass('current');
        // Show only the navigation buttons that make sense for the current section:
        $('.form-navigation .previous').toggle(index > 0);
        let atTheEnd = index >= $sections.length - 1;
        $('.form-navigation .next').toggle(!atTheEnd);

        $('.form-navigation input[type="submit"]').toggle(atTheEnd);
    }

    curIndex() {
        // Return the current index by looking at which section has the class 'current'
        return $sections.index($sections.filter('.current'));
    }



    getCurrentSlug() {
        return $sections.filter('.current').data('slug');
    }


    navigateTo(0); // Start at the beginning
}



$(document).ready(function() {
            // Set caret position easily in jQuery
            // Written by and Copyright of Luke Morton, 2011
            // Licensed under MIT
            (function($) {



                if (isAPIAvailable()) {
                    $("input[name='file_recipient']").change((evt) => {
                        // parseData(evt.target.files[0]);
                        resetRecipients();
                        let fileType = /csv.*/;
                        let file = evt.target.files[0];
                        if (file) {
                            if (file.type.match(fileType) || file.name.slice(-3) === 'csv') {
                                let reader = new FileReader();
                                reader.onload = function(e) {
                                    $('#csv_recipient').val_with_linenum(reader.result);
                                };
                                reader.readAsText(file);
                            } else {
                                let extension = file.type !== '' ? file.type : file.name.slice(file.name.indexOf('.'));
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

            });


        }