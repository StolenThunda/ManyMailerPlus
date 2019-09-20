class CSV_Validator {
    constructor(PapaParsed_data) {
        Object.assign(this, PapaParsed_data);
        this.error_count = 0;
        this.emails = [];
        this.csv_valid = false;
        this.b_file_contains_emails = false;
        this.required_columns = {};
        this.rawdata = this.data;
        this.validate_csv();
    }
    /* #region Getters/setters */
    set is_valid(b_var) {
        this.csv_valid = b_var;
    }
    set contains_emails(b_var) {
        this.b_file_contains_emails = b_var;
    }
    set required_columns(obj) {
        this.required_columns = obj;
    }
    set headers(h) {
        this._headers = h;
    }
    set email_column(col) {
        this.email_column = col;
    }
    set finalHeaders(v) {
        this._headers = v;
    }
    get is_valid() {
        return this.error_count === 0;
    }
    get contains_emails() {
        return this.b_file_contains_emails;
    }
    get required_columns() {
        return this.required_columns;
    }
    get email_list() {
        return this.emails.join(',');
    }

    get extracted_first_row() {
        return Object.values(this.data[0]).filter((val) => !Array.isArray(val));
    }
    get check_headers_for_email() {
        return (
            this.headers.filter((word) => {
                return false === this.isValidEmailAddress(word);
            }).length === this.headers.length
        );
    }
    get email_column() {
        var matches = this.intersection(this.headers, this.regexify(this.validHeaders.email_column));
        return matches.length > 0 ? matches[0] : false;
    }
    get first_name_col() {
        var first = this.intersection(this.headers, this.regexify(this.validHeaders.first));
        return first.length > 0 ? first[0] : '';
    }
    get last_name_col() {
        var last = this.intersection(this.headers, this.regexify(this.validHeaders.last));
        return last.length > 0 ? last[0] : '';
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
    get genKeyMap() {
        return this.tokenizeKeys(this.headers);
    }
    get headers() {
        return this.headers;
    }
    get finalHeaders() {
        return this._headers;
    }
    get validateReqHeaders() {
        var header_has_no_email_data = this.check_headers_for_email;
        var reqKeys = {
            email_column: this.email_column,
            first: this.first_name_col,
            last: this.last_name_col,
            headerKeyMap: header_has_no_email_data ? this.genKeyMap : this.headers,
            errors: []
        };
        Object.keys(reqKeys).filter((k) => {
            if (reqKeys[k] === '' || reqKeys[k] === false) {
                reqKeys.errors.push(`Acceptable Values for ${k}: ${this.validHeaders[k]}`);
            }
        });
        this.validated_data = reqKeys;
        this.mailKey = reqKeys.email_column.val;
        this.is_valid = reqKeys.errors.length === 0 && header_has_no_email_data;
        this.finalHeaders = Object.values(reqKeys.headerKeyMap);
        return this;
    }
    /* #endregion */

    validate_csv() {
        if (this.data.length > 0) {
            this.tokenized_obj = first_row_values = [];
            first_row_values = this.extracted_first_row;
            // email column in file?
            this.contains_email = this.scan_for_emails(first_row_values);
            // validate required columns
            this.validatedReqHeaders.scan_for_errors();
            //generate converted this.data to obj with tokenized keys
            if (this.required_columns.is_valid && this.email_column) {
                this.data.forEach((row) => {
                    var newRow = {};
                    var token_key;
                    var current_email = this.isValidEmailAddress(row[email_column.original]);
                    if (email_column) {
                        if (current_email) {
                            this.emails.push(current_email);
                            row[email_column.original] = current_email;
                        } else {
                            this.pushError({
                                code: 'MMP_MissingColumn',
                                message: `Column (${email_column.original}): does not contain email data`,
                                row: 0,
                                type: 'MissingEmailColumn'
                            });
                        }
                    }
                    for (var itm in row) {
                        token_key = this.required_columns.headerKeyMap[itm];
                        newRow[token_key] = row[itm];
                    }
                    tokenized_obj.push(newRow);
                });
            }
            return Object.assign({
                data_string: JSON.stringify(tokenized_obj),
                data: tokenized_obj,
                recipient_count: this.emails.length,
                email_list: this.email_list,
                headers: this.finalHeaders
            },
                this.pp_data_obj
            );
        } else if (this.errors.length > 0) {
            return this.pp_data_obj;
        }
    }
    scan_for_errors() {
        this.required_columns.errors.forEach((err) => {
            this.pushError({
                code: 'MMP_MissingRequiredKeys',
                message: err,
                row: 0,
                type: 'Missing Required Keys'
            });
        });
        if (!this.required_columns.validHeader) {
            this.pushError({
                code: 'MMP_InvalidFile',
                message: 'Header Does Not Conform to Standard (email, first, last, etc...)',
                row: 0,
                type: 'Invalid Header'
            });
        }
        if (!this.required_columns.email_column || !this.contains_email) {
            this.pushError({
                code: 'MMP_MissingColumn',
                message: 'Invalid Email Column Header (see Missing Required Keys) for details',
                row: 0,
                type: 'Missing Email Column'
            });
        }
        if (this.contains_email && !this.required_columns.email_column) {
            this.pushError({
                code: 'MMP_InvalidColumn',
                message: 'Emails FOUND But Email Column is Mislabeled',
                row: 0,
                type: 'Invalid Email Column'
            });
        }
        if (!this.required_columns) {
            this.pushError({
                code: 'MMP_MissingColumn',
                message: 'Required Columns: email, first_name, last_name',
                row: 0,
                type: 'Missing Required Column'
            });
        }
        // generate email list and
    }
    scan_for_emails(first_row) {
        return (
            first_row.filter((word) => {
                return null !== this.isValidEmailAddress(word);
            }).length > 0
        );
    }
    pushError(errObj) {
        this.has_MMP_ERR = true;
        this.errors.push(errObj);
        ++this.error_count;
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
    regexify(arr) {
        return new RegExp(arr.join('|'), 'i');
    }
}
if (module !== 'undefined' && module.exports !== 'undefined') module.exports = CSV_Validator;