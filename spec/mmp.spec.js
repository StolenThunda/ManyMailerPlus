// mmp.spec.js
'use strict;';
var expect = require('chai').expect;
const test_objects = require('../tests/test_obj');

describe('ManyMailerPlus_mod', () => {
    beforeEach(() => {
        $ = require('jquery');
        global.$ = $;
    });
    it('...should exist', () => {
        var mmp = new require('../javascript/mmp.js');
        expect(mmp).to.not.be.undefined;
    });
});