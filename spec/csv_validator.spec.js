var expect = require('chai').expect;
var should = require('chai').should;
describe('CSV_validator', () => {
    beforeEach(() => {

    });
    it('Should exist', () => {
        var v = require('../javascript/csv_validator.js').CSV_Validator;
        expect(v).to.be.undefined;
    });
    it('Should Validate emails', () => {
        var v = require('../javascript/csv_validator.js').CSV_Validator;
        var email = "tonym@boo.comx";
        expect(v.isValidEmailAddress(email)).to.be.false;
    })
});