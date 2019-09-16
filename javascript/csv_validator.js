class CSV_Validator {
    constructor() {}

    validate_csv(data) {
        var errObj = { errors: [], detail: [] };
        if (data.data.length > 0) {
            var first_row_values = Object.values(data.data[0]).filter((val) => !Array.isArray(val));

            // email column in file?
            var file_contains_emails =
                first_row_values.filter((word) => {
                    return null !== this.isValidEmailAddress(word);
                }).length > 0;

            // validate required columns
            var required_columns = this.validateRequiredKeys(data.headers);
            required_columns.errors.forEach((err) => {
                this.pushError(data, {
                    code: 'MMP_MissingRequiredKeys',
                    message: err,
                    row: 0,
                    type: 'Missing Required Keys'
                });
            });

            var email_column = required_columns.email_column;

            // generate email list and generate converted data to obj with tokenized keys
            var emails = [];
            var tokenized_obj = [];
            if (required_columns.validHeader && email_column) {
                data.data.forEach((row) => {
                    var newRow = {};
                    var token_key;
                    var current_email = this.isValidEmailAddress(row[email_column.original]);
                    if (email_column) {
                        if (current_email) {
                            emails.push(current_email);
                            row[email_column.original] = current_email;
                        } else {
                            var tmp = 'Column (' + email_column.original + '): does not contain email data';
                            if (!errObj.detail.includes(tmp)) {
                                errObj.detail.unshift(tmp);
                                this.pushError(data, {
                                    code: 'MMP_MissingColumn',
                                    message: tmp,
                                    row: 0,
                                    type: 'MissingEmailColumn'
                                });
                            }
                        }
                    }
                    for (var itm in row) {
                        token_key = required_columns.headerKeyMap[itm];
                        newRow[token_key] = row[itm];
                    }
                    tokenized_obj.push(newRow);
                });
            }

            if (!required_columns.validHeader) {
                errObj.errors.unshift('Invalid Header');
                this.pushError(data, {
                    code: 'MMP_InvalidFile',
                    message: 'Header Does Not Conform to Standard (email, first, last, etc...)',
                    row: 0,
                    type: 'Invalid Header'
                });
            }
            if (!email_column || !file_contains_emails) {
                errObj.detail.unshift('No Valid Email Column Header Found');
                this.pushError(data, {
                    code: 'MMP_MissingColumn',
                    message: 'Invalid Email Column Header (see Missing Required Keys) for details',
                    row: 0,
                    type: 'Missing Email Column'
                });
            }
            if (file_contains_emails && !email_column) {
                errObj.detail.unshift('Email column is mislabeled');
                this.pushError(data, {
                    code: 'MMP_InvalidColumn',
                    message: 'Emails FOUND But Email Column is Mislabeled',
                    row: 0,
                    type: 'Invalid Email Column'
                });
            }
            if (!required_columns) {
                errObj.detail.unshift('Required Columns: email, first_name, last_name');
                this.pushError(data, {
                    code: 'MMP_MissingColumn',
                    message: 'Required Columns: email, first_name, last_name',
                    row: 0,
                    type: 'Missing Required Column'
                });
            }
            return Object.assign({
                    csv_valid: errObj.errors.length === 0,
                    mailKey: required_columns.email_column.val,
                    headers: Object.values(required_columns.headerKeyMap),
                    data_string: JSON.stringify(tokenized_obj),
                    data: tokenized_obj,
                    rawdata: data.data,
                    recipient_count: emails.length,
                    validated_data: required_columns,
                    email_list: emails.join(',')
                },
                data
            );
        } else if (data.errors.length > 0) {
            return data;
        }
        return errObj;
    }

    pushError(origData, errObj) {
        origData.has_MMP_ERR = true;
        origData.errors.push(errObj);
        return this;
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
        var regexify = (arr) => {
            return new RegExp(arr.join('|'), 'i');
        };
        var header_has_no_email_data =
            data.filter((word) => {
                return false === this.isValidEmailAddress(word);
            }).length === data.length;
        var email_column = this.intersection(data, regexify(this.validHeaders.email_column));
        var first = this.intersection(data, regexify(this.validHeaders.first));
        var last = this.intersection(data, regexify(this.validHeaders.last));
        var reqKeys = {
            email_column: email_column.length > 0 ? email_column[0] : false,
            first: first.length > 0 ? first[0] : '',
            last: last.length > 0 ? last[0] : '',
            headerKeyMap: header_has_no_email_data ? this.genKeyMap(data) : data,
            errors: []
        };
        Object.keys(reqKeys).filter((k) => {
            if (reqKeys[k] === '' || reqKeys[k] === false) {
                reqKeys.errors.push(`Acceptable Values for ${k}: ${this.validHeaders[k]}`);
            }
        });
        reqKeys.validHeader = reqKeys.errors.length === 0 && header_has_no_email_data;
        return reqKeys;
    }

    get validHeaders() {
        return {
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
    }
    genKeyMap(data) {
        return this.tokenizeKeys(data);
    }

    isValidEmailAddress(emailAddress) {
        var result = false;
        if (emailAddress) {
            //test email pattern
            var pattern = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            var str_test = emailAddress;
            // remove surrounding quotes and trim value for testing
            str_test = str_test.replace(/['"]+/g, '').trim();
            result = pattern.exec(str_test); //str_test.match(pattern);
        }
        return Array.isArray(result) ? result[0] : false;
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