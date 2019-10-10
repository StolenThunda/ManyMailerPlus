class CSV_Validator {
    constructor(PapaParsed_data) {
        Object.assign(this, PapaParsed_data);
        this.error_count = 0;
        this.emails = [];
        this.b_file_contains_emails = false;
        this.headers = this.meta.fields;
        this.validate_csv();
        return this;
    }
    /* #region Getters/setters */
    set is_valid(b_var) {
        this.csv_valid = b_var;
    }
    set contains_email(b_var) {
        this.b_file_contains_emails = b_var;
    }
    set required_columns(obj) {
        this._required_columns = obj;
    }
    set email_column(col) {
        this.required_columns.email_column = col;
    }
    set headers(v) {
        this._headers = v;
        this.meta.fields = this._headers;
    }
    get is_valid() {
        return this.error_count === 0;
    }
    get contains_email() {
        return this.b_file_contains_emails;
    }
    get required_columns() {
        return this._required_columns;
    }

    get extracted_first_row() {
        return Object.values(this.data[0]).filter((val) => !Array.isArray(val));
    }
    get check_headers_for_email() {
        return (
            this.headers.filter((word) => {
                return this.isValidEmailAddress(word);
            }).length > 0
        );
    }
    get email_column() {
        return this.required_columns.email_column;
    }
    get find_email_column() {
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
        return this._headers;
    }
    get validateReqHeaders() {
        var header_has_no_email_data = this.check_headers_for_email;
        var reqKeys = {
            email_column: this.find_email_column,
            first: this.first_name_col,
            last: this.last_name_col,
            headerKeyMap: header_has_no_email_data ? this.headers : this.genKeyMap,
            errors: []
        };
        Object.keys(reqKeys).filter((k) => {
            if (reqKeys[k] === '' || reqKeys[k] === false) {
                reqKeys.errors.push(`Acceptable Values for ${k}: ${this.validHeaders[k]}`);
            }
        });
        this.required_columns = reqKeys;
        this.mailKey = reqKeys.email_column.val;
        this.is_valid = reqKeys.errors.length === 0 && header_has_no_email_data;
        this.headers = Object.values(reqKeys.headerKeyMap);
        return this;
    }
    /* #endregion */

    validate_csv() {
        if (this.data.length > 0) {
            // email column in file?
            this.contains_email = this.scan_for_emails(this.extracted_first_row);
            // validate required columns
            this.validateReqHeaders.scan_for_errors();
            //generate converted this.data to obj with tokenized keys
            this.data = this.createTokenizedObj();
        }
        return this;
    }

    createTokenizedObj() {
        let token_obj = [];
        if (this.is_valid && this.required_columns.email_column) {
            this.data.forEach((row) => {
                var newRow = {};
                var token_key;
                var current_email = this.isValidEmailAddress(row[this.required_columns.email_column.original]);
                if (this.required_columns.email_column) {
                    if (current_email) {
                        this.emails.push(current_email);
                        row[this.required_columns.email_column.original] = current_email;
                    } else {
                        this.pushError({
                            code: 'MMP_MissingColumn',
                            message: `Column (${this.required_columns.email_column
                                .original}): does not contain email data`,
                            row: 0,
                            type: 'MissingEmailColumn'
                        });
                    }
                }
                for (var itm in row) {
                    token_key = this.required_columns.headerKeyMap[itm];
                    newRow[token_key] = row[itm];
                }
                token_obj.push(newRow);
            });
        }
        return token_obj;
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
        if (!this.is_valid) {
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
                return this.isValidEmailAddress(word);
            }).length > 0
        );
    }
    pushError(errObj) {
        this.has_MMP_ERRS = true;
        this.csv_valid = !this.has_MMP_ERRS;
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
        if (key) {
            return '{{' + key.trim().toLowerCase().replace(' ', '_') + '}}';
        }
    }
    regexify(arr) {
        return new RegExp(arr.join('|'), 'i');
    }
}
// for running test only
if (typeof module !== 'undefined' && typeof module.exports !== 'undefined') {
    module.exports = CSV_Validator;
}