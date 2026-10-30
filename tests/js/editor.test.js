/**
 * @jest-environment jsdom
 */
const $ = require('jquery');
global.$ = $;
global.jQuery = $;

let factory;
global.define = function (deps, cb) {
    factory = cb;
};

const log = {debug: jest.fn()};
const parser = require('../../amd/src/parser.js');

require('../../amd/src/editor.js');

const mod = factory($, log, parser);

describe('findFormFields', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        mod.init({
            languages: [{code: 'en', label: 'EN'}],
            format: 'span',
            defaultLang: 'en',
            includedFields: ['notes'],
            excludedFields: [],
            inplaceIncluded: [],
            inplaceExcluded: []
        });
    });

    test('includes textareas named in the inclusion list', () => {
        document.body.innerHTML = `
            <form>
                <div class="felement" data-fieldtype="textarea">
                    <textarea name="notes">x</textarea>
                </div>
                <div class="felement" data-fieldtype="textarea">
                    <textarea name="other">y</textarea>
                </div>
            </form>
        `;
        const $fields = mod.findFormFields();
        const names = $fields.map(function () { return this.getAttribute('name'); }).get();
        expect(names).toContain('notes');
        expect(names).not.toContain('other');
    });

    test('includes rich text editors by default', () => {
        document.body.innerHTML = `
            <form>
                <div class="felement" data-fieldtype="editor">
                    <textarea name="intro">x</textarea>
                </div>
            </form>
        `;
        const $fields = mod.findFormFields();
        const names = $fields.map(function () { return this.getAttribute('name'); }).get();
        expect(names).toContain('intro');
    });

    test('excludes fields named in the exclusion list', () => {
        document.body.innerHTML = `
            <form>
                <div class="felement" data-fieldtype="text">
                    <input type="text" name="idnumber" />
                </div>
            </form>
        `;
        mod.init({
            languages: [{code: 'en', label: 'EN'}],
            format: 'span',
            defaultLang: 'en',
            includedFields: [],
            excludedFields: ['idnumber'],
            inplaceIncluded: [],
            inplaceExcluded: []
        });
        const $fields = mod.findFormFields();
        const names = $fields.map(function () { return this.getAttribute('name'); }).get();
        expect(names).not.toContain('idnumber');
    });
});