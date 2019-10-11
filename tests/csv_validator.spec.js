// csv_validator.spec.js
'use strict;';
var expect = require('chai').expect;
const test_objects = require('./test_obj');

describe('PapaParse CSV Validator', function() {
    // const newLocal = v.validate_csv();
    it('It should exist', () => {
        var Validator = require('../javascript/csv_validator.js');
        expect(Validator).to.not.be.undefined;
    });

    describe('validate_csv()', () => {
        it('...take a csv string and return a list of emails', () => {
            var Validator = require('../javascript/csv_validator.js');
            const v = new Validator(test_objects.valid_pp_parsed);
            expected = [
                'tonym415@gmail.com',
                'alexissimmons041712@gmail.com',
                'tonymoses@texasbluesalley.com',
                'antonio.moses79@gmail.com',
                'tonym415+test@gmail.com',
                'tonym415+test2@gmail.com',
                'helocheck@abuseat.org'
            ].join(', ');
            expect(v.emails.join(', ').replace("'", '')).eql(expected);
        });
    });
});