var expect = require('chai').expect;
var should = require('chai').should;
describe('CSV_validator', () => {
    it('Should exist', () => {
        var v = require('../javascript/csv_validator.js').CSV_Validator;
        expect(v).to.not.be.undefined;
    });
});