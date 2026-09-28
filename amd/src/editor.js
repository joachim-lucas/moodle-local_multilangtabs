define(['jquery', 'core/log'], function($, log) {

    // Fallback languages and format, overridden by init(params) when the hook provides them.
    let languages = [
        {code: 'fr', label: 'Français'},
        {code: 'en', label: 'English'}
    ];
    let defaultLang = 'fr';

    // 'span'  -> compatible with filter_multilang (legacy).
    // 'mlang2' -> compatible with filter_multilang2, recommended for content
    //             made of several paragraphs.
    let outputFormat = 'span';

    /*
     * Global registry of all the multilanguage fields of the page.
     * Required so that the form submit handler, which is declared only once
     * outside of setupTabs, can reach the state of EVERY field when the form
     * is submitted.
     */
    let fields = {};

    // Prevents a double native submission when the submit event is intercepted.
    let submitting = false;


    /**
     * Extract the content of each language from a text containing either <span lang="xx"
     * class="multilang"> tags or {mlang xx}...{mlang} tags. Both formats are always recognised when
     * reading, whatever the output format is, to stay backward compatible with content which was
     * already saved in the other format.
     *
     * @param {String} text Raw content of the field.
     * @returns {Object} Content of each language, indexed by language code.
     */
    function parseMultilang(text) {
        const result = {};
        languages.forEach(function(lang) {
            result[lang.code] = '';
        });

        if (!text) {
            return result;
        }

        let foundAny = false;

        const spanRegex =
            /<span\b[^>]*\blang\s*=\s*["']([a-z0-9_-]+)["'][^>]*>([\s\S]*?)<\/span>/gi;

        let match;
        while ((match = spanRegex.exec(text)) !== null) {
            const langCode = match[1].toLowerCase();
            if (result.hasOwnProperty(langCode)) {
                result[langCode] = match[2];
                foundAny = true;
            }
        }

        if (!foundAny) {
            const mlangRegex = /\{mlang\s+([a-z0-9_-]+)\}([\s\S]*?)\{mlang\}/gi;
            while ((match = mlangRegex.exec(text)) !== null) {
                const langCode = match[1].toLowerCase();
                if (result.hasOwnProperty(langCode)) {
                    result[langCode] = match[2];
                    foundAny = true;
                }
            }
        }

        // No multilanguage syntax found: assume that the whole existing
        // content belongs to the default language, rather than silently
        // losing it, which would happen with a field filled in before the
        // plugin was enabled.
        if (!foundAny && text.trim() !== '') {
            result[defaultLang] = text;
        }

        return result;
    }


    /**
     * Rebuild the complete multilanguage content in the format actually expected by the filter
     * which is enabled on the server.
     *
     * @param {Object} data Content of each language, indexed by language code.
     * @returns {String} The value to store in the field.
     */
    function buildMultilang(data) {
        let output = '';

        languages.forEach(function(lang) {
            const val = data[lang.code] ? data[lang.code].trim() : '';
            if (val === '') {
                return;
            }

            if (outputFormat === 'mlang2') {
                output += '\n{mlang ' + lang.code + '}\n' + val + '\n{mlang}\n';
            } else {
                output += '<span lang="' + lang.code + '" class="multilang">' +
                    val +
                    '</span>';
            }
        });

        return output;
    }


    /**
     * Read the content from TinyMCE when the editor is initialised for this element, from the raw
     * field otherwise.
     *
     * @param {JQuery} $element Field to read.
     * @param {String} elementId Id of the field.
     * @returns {String} Current content of the field.
     */
    function getContent($element, elementId) {
        if (window.tinymce) {
            const editor = window.tinymce.get(elementId);
            if (editor) {
                return editor.getContent();
            }
        }
        return $element.val();
    }


    /**
     * Write the content to TinyMCE when it is available, to the raw field otherwise.
     *
     * @param {JQuery} $element Field to write.
     * @param {String} elementId Id of the field.
     * @param {String} val Content to write.
     */
    function setContent($element, elementId, val) {
        if (window.tinymce) {
            const editor = window.tinymce.get(elementId);
            if (editor) {
                editor.setContent(val || '');
                return;
            }
        }
        $element.val(val || '').trigger('change');
    }


    /**
     * Wait for TinyMCE to be initialised for the given field, with a limited number of attempts, to
     * avoid an endless setTimeout loop on the fields which do not use TinyMCE at all (plain text
     * editor, Atto editor, and so on).
     *
     * @param {String} elementId Id of the field.
     * @param {Function} onReady Called with the editor once it is available.
     * @param {Number} attempt Current number of attempts.
     */
    function waitForEditor(elementId, onReady, attempt) {
        attempt = attempt || 0;

        const editor = window.tinymce ? window.tinymce.get(elementId) : null;

        if (editor) {
            onReady(editor);
            return;
        }

        // About 5 seconds at most (50 attempts x 100ms).
        if (attempt < 50) {
            setTimeout(function() {
                waitForEditor(elementId, onReady, attempt + 1);
            }, 100);
        } else {
            log.debug('multilangtabs: TinyMCE not found for ' + elementId +
                ', falling back to plain text mode.');
        }
    }


    /**
     * Return the name of the form element a field belongs to.
     *
     * Moodle appends the subfield to the name of the fields holding a composite value, as in
     * "intro[text]" for an editor, so it has to be removed to get the name of the element, "intro"
     * here, which is the name typed in the settings.
     *
     * @param {JQuery} $element Field of the form.
     * @returns {String} The name of the element, empty when it cannot be determined.
     */
    function elementName($element) {
        const name = $element.attr('name') || '';

        return name.replace(/\[[^\]]*\]$/, '');
    }

    /**
     * Tell whether a field is part of the interface of the editor rather than a field of the form.
     *
     * @param {JQuery} $element Field of the form.
     * @returns {Boolean} True when the field belongs to TinyMCE.
     */
    function isEditorInternal($element) {
        return !!$element.closest('.tox').length;
    }

    /**
     * Tell whether a field is excluded from the tabs by one of the exclusion lists.
     *
     * An exclusion always wins over an inclusion, whichever list it comes from.
     *
     * @param {JQuery} $element Field of the form.
     * @returns {Boolean} True when the field must be left alone.
     */
    function isExcluded($element) {
        return excludedFieldNames.indexOf(elementName($element)) !== -1;
    }

    /**
     * Collect the fields of the page which have to be decorated with language tabs.
     *
     * Moodle describes every form element it renders with a data-fieldtype attribute, both on the
     * rich text editors and on the plain text fields, and the core relies on that attribute itself
     * to tell the types of fields apart. Selecting the fields on that attribute rather than on a
     * list of ids and names maintained here covers every form of every plugin, and keeps working
     * when a form is renamed or a new one is added upstream.
     *
     * The general rule is that every editor and every plain text field is decorated. The fields
     * of any other type, a plain textarea for instance, are only decorated when an inclusion list
     * names them. No field is decorated when an exclusion list names it.
     *
     * @returns {JQuery} The fields to decorate.
     */
    function findFormFields() {
        const $fields = $('.felement[data-fieldtype="editor"] textarea, ' +
            '.felement[data-fieldtype="text"] input[type="text"]')
            .filter(function() {
                return !isEditorInternal($(this)) && !isExcluded($(this));
            });

        if (includedFieldNames.length) {
            $fields.add($('.felement[data-fieldtype="textarea"] textarea')
                .filter(function() {
                    const $element = $(this);

                    return !isEditorInternal($element) &&
                        includedFieldNames.indexOf(elementName($element)) !== -1 &&
                        !isExcluded($element);
                }));
        }

        return $fields;
    }


    /**
     * Set the language tab bar up for a single field.
     *
     * @param {JQuery} $element Field to decorate.
     */
    function setupTabs($element) {
        const elementId = $element.attr('id');
        if (!elementId || $element.data('multilangtabs-ready')) {
            return;
        }
        $element.data('multilangtabs-ready', true);

        const rawContent = $element.val();

        const field = {
            $element: $element,
            elementId: elementId,
            langData: parseMultilang(rawContent),
            currentLang: defaultLang
        };
        fields[elementId] = field;

        const $tabBar = $('<div class="multilangtabs-bar btn-group" role="group"></div>');

        languages.forEach(function(lang) {
            const activeClass = (lang.code === field.currentLang) ?
                'btn-primary' : 'btn-outline-primary';
            const $btn = $('<button type="button" class="btn multilangtabs-btn ' +
                activeClass + '"></button>')
                .text(lang.label)
                .attr('data-lang', lang.code);
            $tabBar.append($btn);
        });

        const $container = $('<div class="multilangtabs-container"></div>');
        $container.append($tabBar);

        const $targetWrapper = $element.closest('.fitem, .felement').length ?
            $element.closest('.fitem, .felement').find('.tox.tox-tinymce').parent() :
            null;

        if ($targetWrapper && $targetWrapper.length) {
            $targetWrapper.before($container);
        } else {
            $element.before($container);
        }

        // Display the default language as soon as TinyMCE, when it is used, is
        // ready; the second call covers the case where no editor is used at all
        // (plain text field), to display it right away.
        waitForEditor(elementId, function() {
            setContent($element, elementId, field.langData[field.currentLang]);
        });
        setContent($element, elementId, field.langData[field.currentLang]);

        // Language tab change.
        $tabBar.on('click', 'button', function(e) {
            e.preventDefault();
            const newLang = $(this).attr('data-lang');
            if (newLang === field.currentLang) {
                return;
            }

            // Save the content of the language being left BEFORE changing
            // currentLang, otherwise the wrong slot gets overwritten.
            field.langData[field.currentLang] = getContent($element, elementId);

            field.currentLang = newLang;

            $tabBar.find('button')
                .removeClass('btn-primary')
                .addClass('btn-outline-primary');
            $(this)
                .removeClass('btn-outline-primary')
                .addClass('btn-primary');

            setContent($element, elementId, field.langData[newLang]);
        });
    }


    /**
     * Intercept the form submission to rebuild, for EVERY registered
     * multilanguage field, the full content (all languages at once) before
     * the native submission happens. Without this handler, only the content of
     * the tab active at click time would be sent, and every other language
     * typed in would be lost.
     */
    function setupFormSubmit() {
        $('form').each(function() {
            const form = this;

            if ($(form).data('multilangtabs-submit-ready')) {
                return;
            }
            $(form).data('multilangtabs-submit-ready', true);

            $(form).on('submit.multilangtabs', function(e) {
                if (submitting) {
                    return;
                }

                // The current TinyMCE content (getContent) has to be read for
                // every field before the final value is rebuilt: the native
                // submit is blocked while doing so, then the form is submitted
                // again by this module.
                e.preventDefault();

                Object.keys(fields).forEach(function(elementId) {
                    const field = fields[elementId];
                    if (!field) {
                        return;
                    }

                    // Capture the content currently displayed (active language).
                    field.langData[field.currentLang] =
                        getContent(field.$element, elementId);

                    // Rebuild the full multilanguage content and write it into
                    // the field, so that Moodle reads it on submission.
                    const fullContent = buildMultilang(field.langData);
                    field.$element.val(fullContent);
                });

                submitting = true;
                HTMLFormElement.prototype.submit.call(form);
            });
        });
    }

    /*
     * Edit in place (core/inplace_editable): section names, activity names,
     * etc. Those fields are not in the DOM on load: they are created
     * dynamically when the edit link is clicked, then destroyed once saved.
     * They cannot be targeted by a static selector like the regular textareas,
     * so the DOM has to be observed instead.
     */

    // List of the component-itemtype pairs the tabs are enabled for. It is
    // provided by the hook callback through init(); empty = feature disabled.
    let inplaceTargets = [];

    // Names of the form elements decorated even though the general rule does not
    // cover them. It is provided by the hook callback through init().
    let includedFieldNames = [];

    // Names of the form elements never decorated, which win over the inclusions. It
    // is provided by the hook callback through init().
    let excludedFieldNames = [];

    /**
     * Build the key identifying an inplace editable target.
     *
     * @param {String} component Component of the target.
     * @param {String} itemtype Item type of the target.
     * @returns {String} The key of the target.
     */
    function inplaceKey(component, itemtype) {
        return component + '-' + itemtype;
    }

    /**
     * Set the language tab bar up for a single inplace editable field.
     *
     * @param {JQuery} $input Input holding the value being edited.
     * @param {JQuery} $mainelement Inplace editable element being decorated.
     */
    function setupInplaceField($input, $mainelement) {
        if ($input.data('multilangtabs-inplace-ready')) {
            return;
        }
        $input.data('multilangtabs-inplace-ready', true);

        const state = {
            langData: parseMultilang($mainelement.attr('data-value')),
            currentLang: defaultLang
        };

        // Moodle pre-fills the input with the full raw value, all languages at
        // once: replace it before the user gets a chance to see it.
        $input.val(state.langData[state.currentLang] || '');

        const $tabBar = $('<span class="multilangtabs-inplace-bar"></span>');
        languages.forEach(function(lang) {
            const activeClass = (lang.code === state.currentLang) ?
                'btn-primary' : 'btn-outline-primary';
            $tabBar.append(
                $('<button type="button" class="btn multilangtabs-btn ' +
                    activeClass + '"></button>')
                    .text(lang.code.toUpperCase())
                    .attr('data-lang', lang.code)
            );
        });
        $input.after($tabBar);

        // Prevent a click on a tab from taking the focus away from the input: a
        // blur could cancel or save the edition too early, depending on the
        // Moodle version.
        $tabBar.on('mousedown', 'button', function(e) {
            e.preventDefault();
        });

        $tabBar.on('click', 'button', function(e) {
            e.preventDefault();
            const newLang = $(this).attr('data-lang');
            if (newLang === state.currentLang) {
                return;
            }
            state.langData[state.currentLang] = $input.val();
            state.currentLang = newLang;
            $input.val(state.langData[newLang] || '');
            $tabBar.find('button')
                .removeClass('btn-primary')
                .addClass('btn-outline-primary');
            $(this)
                .removeClass('btn-outline-primary')
                .addClass('btn-primary');
        });

        // Rebuild the full multilanguage value just before core reads it to
        // save it. Bound directly on the input, not delegated on "body", so it
        // runs BEFORE the native handler of core/inplace_editable, which
        // listens on body and calls stopImmediatePropagation() on the click
        // of the edit link, but not on this keydown, which bubbles up
        // normally.
        $input.on('keydown', function(e) {
            if (e.key !== 'Enter' && e.keyCode !== 13) {
                return;
            }
            state.langData[state.currentLang] = $input.val();
            $input.val(buildMultilang(state.langData));
        });
    }

    /**
     * Decorate the supported inplace editables found in a freshly added node.
     *
     * @param {Node} node Node which was just added to the page.
     */
    function scanInplaceEditables(node) {
        const $node = $(node);

        // The node added by the mutation can be:
        // - the [data-inplaceeditable] element itself,
        // - a descendant containing one (rare),
        // - OR (by far the most common case in practice) a descendant OF the
        //   [data-inplaceeditable] element, since Moodle rewrites the inner
        //   content of that existing element instead of replacing it entirely.
        // closest() is therefore needed as well, otherwise this case is
        // systematically missed.
        let $mainelements = $node.filter('[data-inplaceeditable]')
            .add($node.find('[data-inplaceeditable]'))
            .add($node.closest('[data-inplaceeditable]'));

        $mainelements.each(function() {
            const $mainelement = $(this);

            if ($mainelement.attr('data-type') !== 'text') {
                return; // Ignore select/toggle: it makes no sense for multilanguage content.
            }

            const key = inplaceKey(
                $mainelement.attr('data-component'),
                $mainelement.attr('data-itemtype')
            );
            if (inplaceTargets.indexOf(key) === -1) {
                return;
            }

            const $input = $mainelement.find('input');
            if ($input.length) {
                setupInplaceField($input, $mainelement);
            }
        });
    }

    /**
     * Watch the page for the inplace editable elements created on the fly by core.
     */
    function setupInplaceObserver() {
        if (!window.MutationObserver) {
            log.debug('multilangtabs: MutationObserver is not available.');
            return;
        }

        new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        scanInplaceEditables(node);
                    }
                });
            });
        }).observe(document.body, {childList: true, subtree: true});
    }


    return {
        init: function(params) {
            if (params && params.languages) {
                languages = params.languages;
            }
            if (params && params.format) {
                outputFormat = params.format;
            }
            if (params && params.defaultLang) {
                defaultLang = params.defaultLang.toLowerCase();
                if (!languages.some(function(l) {
                    return l.code === defaultLang;
                }) && languages.length > 0) {
                    defaultLang = languages[0].code;
                }
            }
            // Without this list of targets, the edit in place support stays
            // disabled for the whole page.
            if (params && params.inplaceTargets) {
                inplaceTargets = params.inplaceTargets;
            }
            // Both lists hold the exceptions of the site and the ones of the current
            // user, already merged and deduplicated by the hook callback.
            if (params && params.includedFields) {
                includedFieldNames = params.includedFields;
            }
            if (params && params.excludedFields) {
                excludedFieldNames = params.excludedFields;
            }

            $(document).ready(function() {
                // Short delay to let Moodle instantiate its DOM elements
                // before looking for them in order to decorate them.
                setTimeout(function() {
                    findFormFields().each(function() {
                        setupTabs($(this));
                    });
                    setupFormSubmit();

                    // The observer is only worth setting up when this site
                    // supports at least one inplace editable target.
                    if (inplaceTargets.length) {
                        setupInplaceObserver();
                    }
                }, 400);
            });
        }
    };
});
