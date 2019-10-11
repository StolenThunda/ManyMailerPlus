/* #region OBJ literals for testing */
const valid_pp_parsed = {
    data: [{
            Email: 'tonym415@gmail.com',
            'First Name ': 'Antonio',
            'Last name': 'Moses',
            renew_date: '2/13/19',
            ready: 'x'
        },
        {
            Email: 'alexissimmons041712@gmail.com',
            'First Name ': 'Alexis',
            'Last name': 'Moses',
            renew_date: '3/15/19'
        },
        {
            Email: 'tonymoses@texasbluesalley.com',
            'First Name ': 'Tony',
            'Last name': 'Moses',
            renew_date: '8/1/19',
            ready: 'x'
        },
        {
            Email: 'antonio.moses79@gmail.com',
            'First Name ': 'Antonio',
            'Last name': 'Moses',
            renew_date: '7/19/19'
        },
        {
            Email: '"tonym415+test@gmail.com"',
            'First Name ': '"Tony"',
            'Last name': '"Test1"',
            renew_date: '"1/2/2019"'
        },
        {
            Email: '"tonym415+test2@gmail.com"',
            'First Name ': '"Tony"',
            'Last name': '"Test2"',
            renew_date: '"1/3/2019"',
            ready: 'x'
        },
        {
            Email: '"helocheck@abuseat.org"',
            'First Name ': '"Tester"',
            'Last name': '"McTesterson"',
            renew_date: '"1/4/2020"'
        }
    ],
    errors: [{
            type: 'FieldMismatch',
            code: 'TooFewFields',
            message: 'Too few fields: expected 5 fields but parsed 4',
            row: 1
        },
        {
            type: 'FieldMismatch',
            code: 'TooFewFields',
            message: 'Too few fields: expected 5 fields but parsed 4',
            row: 3
        },
        {
            type: 'FieldMismatch',
            code: 'TooFewFields',
            message: 'Too few fields: expected 5 fields but parsed 4',
            row: 4
        },
        {
            type: 'FieldMismatch',
            code: 'TooFewFields',
            message: 'Too few fields: expected 5 fields but parsed 4',
            row: 6
        }
    ],
    meta: {
        delimiter: ',',
        linebreak: '\n',
        aborted: false,
        truncated: false,
        cursor: 401,
        fields: [
            'Email',
            'First Name ',
            'Last name',
            'renew_date',
            'ready'
        ]
    },
    headers: [
        'Email',
        'First Name ',
        'Last name',
        'renew_date',
        'ready'
    ],
    dtCols: [{
            title: 'Email'
        },
        {
            title: 'First Name '
        },
        {
            title: 'Last name'
        },
        {
            title: 'renew_date'
        },
        {
            title: 'ready'
        }
    ],
    dtData: [
        [
            'tonym415@gmail.com',
            'Antonio',
            'Moses',
            '2/13/19',
            'x'
        ],
        [
            'alexissimmons041712@gmail.com',
            'Alexis',
            'Moses',
            '3/15/19',
            ''
        ],
        [
            'tonymoses@texasbluesalley.com',
            'Tony',
            'Moses',
            '8/1/19',
            'x'
        ],
        [
            'antonio.moses79@gmail.com',
            'Antonio',
            'Moses',
            '7/19/19',
            ''
        ],
        [
            '"tonym415+test@gmail.com"',
            '"Tony"',
            '"Test1"',
            '"1/2/2019"',
            ''
        ],
        [
            '"tonym415+test2@gmail.com"',
            '"Tony"',
            '"Test2"',
            '"1/3/2019"',
            'x'
        ],
        [
            '"helocheck@abuseat.org"',
            '"Tester"',
            '"McTesterson"',
            '"1/4/2020"',
            ''
        ]
    ],
    string: 'Email,"First Name ",Last name,renew_date,ready\r\ntonym415@gmail.com,Antonio,Moses,2/13/19,x\r\nalexissimmons041712@gmail.com,Alexis,Moses,3/15/19,\r\ntonymoses@texasbluesalley.com,Tony,Moses,8/1/19,x\r\nantonio.moses79@gmail.com,Antonio,Moses,7/19/19,\r\n"""tonym415+test@gmail.com""","""Tony""","""Test1""","""1/2/2019""",\r\n"""tonym415+test2@gmail.com""","""Tony""","""Test2""","""1/3/2019""",x\r\n"""helocheck@abuseat.org""","""Tester""","""McTesterson""","""1/4/2020""",'
};
var sample_emails = [
    'tonym415@gmail.com',
    'tonym'
];
var csv_string = [
    'Email,First Name ,Last name,renew_date,ready',
    'tonym415@gmail.com,Antonio,Moses,2/13/19,x',
    'alexissimmons041712@gmail.com,Alexis,Moses,3/15/19',
    'tonymoses@texasbluesalley.com,Tony,Moses,8/1/19,x',
    'antonio.moses79@gmail.com,Antonio,Moses,7/19/19',
    '"tonym415+test@gmail.com","Tony","Test1","1/2/2019"',
    '"tonym415+test2@gmail.com","Tony","Test2","1/3/2019",x',
    '"helocheck@abuseat.org","Tester","McTesterson","1/4/2020"'
].join('');
/* #endregion */
if (typeof module !== 'undefined' && typeof module.exports !== 'undefined') {
    module.exports = {
        valid_pp_parsed: valid_pp_parsed,
        csv_string: csv_string
    };
}