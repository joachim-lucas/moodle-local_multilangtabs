const {parseMultilang, buildMultilang} = require('../../amd/src/parser.js');

const langs = [{code: 'en'}, {code: 'fr'}];
const defaultLang = 'en';

describe('parseMultilang / buildMultilang', () => {
    test('round trips a simple two-language value (span)', () => {
        const text = '<span lang="en" class="multilang">Hello</span><span lang="fr" class="multilang">Bonjour</span>';
        const parsed = parseMultilang(text, langs, defaultLang);
        expect(parsed).not.toBeNull();
        expect(parsed.langData.en).toBe('Hello');
        expect(parsed.langData.fr).toBe('Bonjour');
        const rebuilt = buildMultilang(parsed.segments, parsed.langData, langs, 'span');
        expect(rebuilt).toBe(text);
    });

    test('round trips mlang2 syntax', () => {
        const text = '{mlang en}Hello{mlang}{mlang fr}Bonjour{mlang}';
        const parsed = parseMultilang(text, langs, defaultLang);
        expect(parsed).not.toBeNull();
        const rebuilt = buildMultilang(parsed.segments, parsed.langData, langs, 'mlang2');
        // conversion to mlang2 format wraps with newlines
        expect(rebuilt).toContain('{mlang en}');
        expect(rebuilt).toContain('Hello');
    });

    test('preserves common text outside language blocks', () => {
        const text = 'Common intro <span lang="en" class="multilang">Hello</span> common middle <span lang="fr" class="multilang">Bonjour</span> common outro';
        const parsed = parseMultilang(text, langs, defaultLang);
        expect(parsed).not.toBeNull();
        const rebuilt = buildMultilang(parsed.segments, parsed.langData, langs, 'span');
        expect(rebuilt).toBe(text);
    });

    test('preserves {mlang other} blocks', () => {
        const text = '{mlang en}Hello{mlang}{mlang de}Hallo{mlang}';
        const langs2 = [{code: 'en'}];
        const parsed = parseMultilang(text, langs2, 'en');
        expect(parsed).not.toBeNull();
        const rebuilt = buildMultilang(parsed.segments, parsed.langData, langs2, 'span');
        expect(rebuilt).toContain('{mlang de}Hallo{mlang}');
    });

    test('does not match spans without multilang class', () => {
        const text = '<span lang="en">accessibility mark</span> <span lang="en" class="multilang">Hello</span>';
        const parsed = parseMultilang(text, langs, defaultLang);
        expect(parsed).not.toBeNull();
        const rebuilt = buildMultilang(parsed.segments, parsed.langData, langs, 'span');
        expect(rebuilt).toContain('<span lang="en">accessibility mark</span>');
        expect(rebuilt).toContain('Hello');
    });

    test('handles nested spans inside a multilang block', () => {
        const text = '<span lang="en" class="multilang">Hello <span style="color:red">world</span></span>';
        const parsed = parseMultilang(text, langs, defaultLang);
        expect(parsed).not.toBeNull();
        expect(parsed.langData.en).toBe('Hello <span style="color:red">world</span>');
        const rebuilt = buildMultilang(parsed.segments, parsed.langData, langs, 'span');
        expect(rebuilt).toBe(text);
    });

    test('leaves field undecorated when a configured language appears twice', () => {
        const text = '<span lang="en" class="multilang">A</span><span lang="en" class="multilang">B</span>';
        const parsed = parseMultilang(text, langs, defaultLang);
        expect(parsed).toBeNull();
    });

    test('assigns whole content to default language when no marks', () => {
        const text = 'Just plain text';
        const parsed = parseMultilang(text, langs, defaultLang);
        expect(parsed).not.toBeNull();
        expect(parsed.langData.en).toBe(text);
    });

    test('writes the plain value when a single language is filled and original had no markup', () => {
        const text = 'Just plain text';
        const parsed = parseMultilang(text, langs, defaultLang);
        parsed.langData.en = 'Changed';
        const rebuilt = buildMultilang(parsed.segments, parsed.langData, langs, 'span');
        expect(rebuilt).toBe('Changed');
    });

    test('keeps markup when the original had no markup but two languages are filled', () => {
        const text = 'Just plain text';
        const parsed = parseMultilang(text, langs, defaultLang);
        parsed.langData.en = 'Hello';
        parsed.langData.fr = 'Bonjour';
        const rebuilt = buildMultilang(parsed.segments, parsed.langData, langs, 'span');
        expect(rebuilt).toContain('<span lang="en" class="multilang">Hello</span>');
        expect(rebuilt).toContain('<span lang="fr" class="multilang">Bonjour</span>');
    });

    test('editing a language updates only that block', () => {
        const text = 'Common <span lang="en" class="multilang">Hello</span> <span lang="fr" class="multilang">Bonjour</span>';
        const parsed = parseMultilang(text, langs, defaultLang);
        parsed.langData.en = 'Hi';
        const rebuilt = buildMultilang(parsed.segments, parsed.langData, langs, 'span');
        expect(rebuilt).toBe('Common <span lang="en" class="multilang">Hi</span> <span lang="fr" class="multilang">Bonjour</span>');
    });
});