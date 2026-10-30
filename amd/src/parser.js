// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Pure helpers for parsing and rebuilding multilanguage field values.
 *
 * Kept free of any DOM or Moodle dependency so that they can be unit tested
 * directly. The editor AMD module wraps them with the site languages, the
 * default language and the output format.
 *
 * @module     local_multilangtabs/parser
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* global module */
/**
 * UMD wrapper.
 *
 * @param {Object} root Global object.
 * @param {Function} factory Factory returning the public API.
 * @returns {void}
 */
(function(root, factory) {
    'use strict';
    if (typeof define === 'function' && define.amd) {
        define(factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.multilangtabsParser = factory();
    }
}(this, function() {
    'use strict';

    /**
     * Tell whether a language code is one of the languages proposed as tabs.
     *
     * @param {String} code Language code.
     * @param {Array} languages Proposed languages.
     * @returns {Boolean}
     */
    function isConfiguredLanguage(code, languages) {
        return (languages || []).some(function(lang) {
            return lang.code === code;
        });
    }

    /**
     * Parse the opening tag of a multilang span.
     *
     * @param {String} text Full text being parsed.
     * @param {Number} index Position of the "<".
     * @returns {Object|null} {code, end} or null.
     */
    function matchMultilangSpanOpen(text, index) {
        const openMatch = /^<span\b([^>]*)>/i.exec(text.slice(index));
        if (!openMatch) {
            return null;
        }
        const attrs = openMatch[1];
        const classMatch = /\bclass\s*=\s*["']([^"']*)["']/i.exec(attrs);
        const langMatch = /\blang\s*=\s*["']([a-z0-9_-]+)["']/i.exec(attrs);
        if (!classMatch || !langMatch) {
            return null;
        }
        if (!/\bmultilang\b/i.test(classMatch[1])) {
            return null;
        }
        return {
            code: langMatch[1].toLowerCase(),
            end: index + openMatch[0].length
        };
    }

    /**
     * Find the matching closing span, taking nesting into account.
     *
     * @param {String} text Full text being parsed.
     * @param {Number} start Index just after the opening tag.
     * @returns {Number} Index of the matching "</span>", or -1.
     */
    function findMatchingSpanClose(text, start) {
        let depth = 1;
        const tagRegex = /<\/?span\b[^>]*>/gi;
        tagRegex.lastIndex = start;
        let m;
        while ((m = tagRegex.exec(text)) !== null) {
            if (m.index < start) {
                continue;
            }
            if (m[0].charAt(1) === '/') {
                depth--;
            } else {
                depth++;
            }
            if (depth === 0) {
                return m.index;
            }
            if (m.index === tagRegex.lastIndex) {
                tagRegex.lastIndex++;
            }
        }
        return -1;
    }

    /**
     * Parse a multilanguage value into segments.
     *
     * @param {String} text Raw content of the field.
     * @param {Array} languages Proposed languages.
     * @param {String} defaultLang Default language code.
     * @returns {Object|null} Parsed data or null when not editable.
     */
    /**
     * Scan the text into an ordered list of segments.
     *
     * @param {String} text Raw content.
     * @param {String} defaultLang Default language code.
     * @returns {Array}
     */
    function scanSegments(text, defaultLang) {
        const segments = [];
        let i = 0;
        const n = text.length;
        let last = 0;

        while (i < n) {
            const mlangOpen = /^\{mlang\s+([a-z0-9_-]+)\}/i.exec(text.slice(i));
            if (mlangOpen) {
                if (i > last) {
                    segments.push({type: 'text', value: text.slice(last, i)});
                }
                const code = mlangOpen[1].toLowerCase();
                const closeTag = '{mlang}';
                const closeIdx = text.indexOf(closeTag, i + mlangOpen[0].length);
                if (closeIdx === -1) {
                    segments.push({type: 'text', value: text.slice(last)});
                    last = n;
                    i = n;
                    break;
                }
                const content = text.slice(i + mlangOpen[0].length, closeIdx);
                const original = text.slice(i, closeIdx + closeTag.length);
                segments.push({type: 'lang', code: code, value: content, original: original});
                i = closeIdx + closeTag.length;
                last = i;
                continue;
            }

            if (text.charAt(i) === '<') {
                const spanOpen = matchMultilangSpanOpen(text, i);
                if (spanOpen) {
                    if (i > last) {
                        segments.push({type: 'text', value: text.slice(last, i)});
                    }
                    const closeIdx = findMatchingSpanClose(text, spanOpen.end);
                    if (closeIdx === -1) {
                        segments.push({type: 'text', value: text.slice(last)});
                        last = n;
                        i = n;
                        break;
                    }
                    const content = text.slice(spanOpen.end, closeIdx);
                    const original = text.slice(i, closeIdx + '</span>'.length);
                    segments.push({type: 'lang', code: spanOpen.code, value: content, original: original});
                    i = closeIdx + '</span>'.length;
                    last = i;
                    continue;
                }
            }

            i++;
        }

        if (last < n) {
            segments.push({type: 'text', value: text.slice(last)});
        }

        const hasLang = segments.some(function(s) {
            return s.type === 'lang';
        });
        if (!hasLang && defaultLang && text.trim() !== '') {
            segments.length = 0;
            segments.push({type: 'lang', code: defaultLang, value: text, original: null});
        }

        return segments;
    }

    /**
     * Finalise parsing: count blocks and build langData.
     *
     * @param {Array} segments Ordered segments.
     * @param {Array} languages Proposed languages.
     * @returns {Object|null}
     */
    function finalizeParse(segments, languages) {
        const blockCounts = {};
        (languages || []).forEach(function(lang) {
            blockCounts[lang.code] = 0;
        });
        segments.forEach(function(seg) {
            if (seg.type === 'lang' && Object.prototype.hasOwnProperty.call(blockCounts, seg.code)) {
                blockCounts[seg.code]++;
            }
        });

        let editable = true;
        Object.keys(blockCounts).forEach(function(code) {
            if (blockCounts[code] > 1) {
                editable = false;
            }
        });

        const langData = {};
        (languages || []).forEach(function(lang) {
            langData[lang.code] = '';
        });

        if (editable) {
            (languages || []).forEach(function(lang) {
                const code = lang.code;
                if (blockCounts[code] === 1) {
                    const seg = segments.find(function(s) {
                        return s.type === 'lang' && s.code === code;
                    });
                    langData[code] = seg ? seg.value : '';
                }
            });
        }

        if (!editable) {
            return null;
        }
        return {segments: segments, langData: langData, editable: true};
    }

    /**
     * Parse a multilanguage value into segments.
     *
     * @param {String} text Raw content of the field.
     * @param {Array} languages Proposed languages.
     * @param {String} defaultLang Default language code.
     * @returns {Object|null} Parsed data or null when not editable.
     */
    function parseMultilang(text, languages, defaultLang) {
        if (text === null || text === undefined) {
            text = '';
        }
        languages = languages || [];
        const segments = scanSegments(text, defaultLang);
        return finalizeParse(segments, languages);
    }
    /**
     * Rebuild the multilanguage content.
     *
     * @param {Array} segments Ordered segments.
     * @param {Object} langData Edited content per language.
     * @param {Array} languages Proposed languages.
     * @param {String} outputFormat 'mlang2' or 'span'.
     * @returns {String}
     */
    function buildMultilang(segments, langData, languages, outputFormat) {
        outputFormat = outputFormat || 'span';

        // When the original value carried no language markup and at most one
        // language ends up filled, write the plain value back instead of
        // wrapping it, so single-language content (often learner input) is not
        // polluted with markup.
        if (segments && segments.length === 1 && segments[0].type === 'lang' &&
                segments[0].original === null) {
            const filled = (languages || []).filter(function(lang) {
                return (langData && langData[lang.code]) ?
                    String(langData[lang.code]).trim() !== '' : false;
            });
            if (filled.length <= 1) {
                if (filled.length === 0) {
                    return '';
                }
                return String(langData[filled[0].code]).trim();
            }
        }

        let output = '';
        const emitted = {};

        (segments || []).forEach(function(seg) {
            if (seg.type === 'text') {
                output += seg.value;
                return;
            }

            const code = seg.code;
            const configured = isConfiguredLanguage(code, languages);
            if (!configured) {
                output += (seg.original !== null && seg.original !== undefined)
                    ? seg.original
                    : '';
                return;
            }

            const val = (langData && langData[code]) ? String(langData[code]).trim() : '';
            if (val === '') {
                return;
            }

            if (outputFormat === 'mlang2') {
                output += '\n{mlang ' + code + '}\n' + val + '\n{mlang}\n';
            } else {
                output += '<span lang="' + code + '" class="multilang">' +
                    val +
                    '</span>';
            }
            emitted[code] = true;
        });

        (languages || []).forEach(function(lang) {
            const code = lang.code;
            if (emitted[code]) {
                return;
            }
            const val = (langData && langData[code]) ? String(langData[code]).trim() : '';
            if (val === '') {
                return;
            }
            if (outputFormat === 'mlang2') {
                output += '\n{mlang ' + code + '}\n' + val + '\n{mlang}\n';
            } else {
                output += '<span lang="' + code + '" class="multilang">' +
                    val +
                    '</span>';
            }
        });

        return output;
    }

    // Expose helpers for stricter round-trip checks by callers.
    /**
     * Check whether parsing and rebuilding is lossless for the given format.
     *
     * @param {String} text Raw content.
     * @param {Array} languages Proposed languages.
     * @param {String} defaultLang Default language code.
     * @param {String} outputFormat 'mlang2' or 'span'.
     * @returns {Boolean}
     */
    function roundTrip(text, languages, defaultLang, outputFormat) {
        const parsed = parseMultilang(text, languages, defaultLang);
        if (!parsed) {
            return false;
        }
        const rebuilt = buildMultilang(parsed.segments, parsed.langData, languages, outputFormat);
        return rebuilt === text;
    }

    return {
        isConfiguredLanguage: isConfiguredLanguage,
        matchMultilangSpanOpen: matchMultilangSpanOpen,
        findMatchingSpanClose: findMatchingSpanClose,
        parseMultilang: parseMultilang,
        buildMultilang: buildMultilang,
        roundTrip: roundTrip
    };
}));
