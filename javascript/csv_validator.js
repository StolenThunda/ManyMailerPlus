class CSV_Validator {
    constructor() { }

    validate_csv(data) {
        var errs = [];
        var errDetail = [];
        var header_valid, email_column;
        var rowdata = Object.values(data.data[0]);
        var first_row_values = rowdata.filter((val) => !Array.isArray(val));

        // email column in file?
        var file_contains_emails = first_row_values.filter((word) => this.isValidEmailAddress(word)).length > 0;

        // validate required columns
        var required_columns = this.validateRequiredKeys(data.headers);
        if (required_columns.errors.length > 0) {
            errDetail = errDetail.concat(required_columns.errors);
        }
        header_valid = required_columns.validHeader;
        email_column = required_columns.email_column;

        // generate email list and generate converted data to obj with tokenized keys
        var emails = [];
        var tokenized_obj = [];
        data.data.forEach((row) => {
            var newRow = {};
            var token_key;
            var current_email = row[email_column.original];
            if (email_column) {
                if (current_email && this.isValidEmailAddress(current_email)) {
                    emails.push(current_email.trim());
                } else {
                    var tmp = 'Column (' + email_column.original + '): does not contain email data';
                    if (!errDetail.includes(tmp)) {
                        errDetail.unshift(tmp);
                    }
                }
            }
            for (var itm in row) {
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
            this.displayCSVErrors(errs, errDetail);
            var current_csv = $('#csv_recipient').val().split(/\n+/g);
            current_csv.unshift(errs[0].toUpperCase());
            let new_csv = current_csv.join('\n');
            $('#csv_recipient').val_with_linenum(new_csv);

            return { errors: errs, detail: errDetail, };
        }
    }

    intersection(d, regX) {
        var dataArray = d;
        var foundColumn = [];
        dataArray.forEach((csvColumn, idx) => {
            var testColumn = csvColumn.toLowerCase().trim();
            if (regX.test(testColumn)) {
                foundColumn.push({
                    original: csvColumn.trim(),
                    val: this.tokenizeKey(testColumn)
                });
            }
        });
        return foundColumn;
    }

    validateRequiredKeys(data) {
        var validHeaders = {
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
        var regexify = (arr) => {
            return new RegExp(arr.join('|'), 'i');
        };
        var header_has_no_email_data = data.filter((word) => this.isValidEmailAddress(word)).length === 0;
        var email_column = this.intersection(data, regexify(validHeaders.email_column));
        var first = this.intersection(data, regexify(validHeaders.first));
        var last = this.intersection(data, regexify(validHeaders.last));
        var reqKeys = {
            email_column: email_column.length > 0 ? email_column[0] : false,
            first: first.length > 0 ? first[0] : '',
            last: last.length > 0 ? last[0] : '',
            headerKeyMap: header_has_no_email_data ? this.genKeyMap(data) : data,
            errors: []
        };
        var invalidColumns = Object.keys(reqKeys).filter((k) => {
            var isNotSet = reqKeys[k] === '' || reqKeys[k] === false;
            if (isNotSet) {
                reqKeys.errors.push(`Acceptable Values for ${k}: ${validHeaders[k]}`);
            }
        });
        reqKeys.validHeader = reqKeys.errors.length === 0 && header_has_no_email_data;
        return reqKeys;
    }

    genKeyMap(data) {
        return this.tokenizeKeys(data);
    }

    isValidEmailAddress(emailAddress) {
        var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
        return pattern.test(emailAddress.trim());
    }

    tokenizeKeys(data) {
        var newData = {};
        data.forEach((key) => {
            newData[key] = this.tokenizeKey(key);
        });
        return newData;
    }

    tokenizeKey(key) {
        return '{{' + key.trim().toLowerCase().replace(' ', '_') + '}}';
    }
}