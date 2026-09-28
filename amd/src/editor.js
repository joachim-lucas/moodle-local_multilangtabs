define(['jquery', 'core/log'], function($, log) {

    // Langues et format de repli, écrasés par init(params) si fourni par lib.php.
    let languages = [
        {code: 'fr', label: 'Français'},
        {code: 'en', label: 'English'}
    ];
    let defaultLang = 'fr';

    // 'span'  -> compatible filter_multilang (legacy)
    // 'mlang2' -> compatible filter_multilang2, recommandé pour du contenu
    //             multi-paragraphes.
    let outputFormat = 'span';

    /*
     * Registre global de tous les champs multilingues de la page.
     * Indispensable pour que le handler de soumission du formulaire
     * (déclaré une seule fois, en dehors de setupTabs) puisse accéder
     * à l'état de CHAQUE champ au moment de l'envoi.
     */
    let fields = {};

    // Empêche une double soumission native lors de l'interception du submit.
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

        // Aucune syntaxe multilingue détectée : on considère que tout le
        // contenu existant est dans la langue par défaut, plutôt que de
        // le perdre silencieusement (cas d'un champ déjà rempli avant
        // l'activation du plugin).
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

        // ~5 secondes maximum (50 tentatives x 100ms).
        if (attempt < 50) {
            setTimeout(function() {
                waitForEditor(elementId, onReady, attempt + 1);
            }, 100);
        } else {
            log.debug('multilangtabs: TinyMCE non détecté pour ' + elementId +
                ', poursuite en mode champ brut.');
        }
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

        // Affiche la langue par défaut dès que TinyMCE (s'il est utilisé)
        // est prêt ; le second appel couvre le cas où l'éditeur n'est pas
        // utilisé du tout (champ texte brut), pour un affichage immédiat.
        waitForEditor(elementId, function() {
            setContent($element, elementId, field.langData[field.currentLang]);
        });
        setContent($element, elementId, field.langData[field.currentLang]);

        // Changement d'onglet de langue.
        $tabBar.on('click', 'button', function(e) {
            e.preventDefault();
            const newLang = $(this).attr('data-lang');
            if (newLang === field.currentLang) {
                return;
            }

            // Sauvegarder le contenu de la langue qu'on quitte AVANT de
            // changer currentLang, sinon on écrase le mauvais slot.
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
     * Intercepte la soumission du formulaire pour reconstituer, pour
     * CHAQUE champ multilingue enregistré, le contenu complet (toutes
     * langues confondues) avant l'envoi natif. Sans ce handler, seul
     * le contenu de l'onglet actif au moment du clic serait envoyé et
     * les autres langues saisies seraient perdues.
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

                // On a besoin de lire le contenu courant de TinyMCE
                // (getContent) pour chaque champ avant de reconstruire
                // la valeur finale : on bloque le submit natif le temps
                // de faire ce travail, puis on relance nous-mêmes la
                // soumission.
                e.preventDefault();

                Object.keys(fields).forEach(function(elementId) {
                    const field = fields[elementId];
                    if (!field) {
                        return;
                    }

                    // Capture le contenu actuellement affiché (langue active).
                    field.langData[field.currentLang] =
                        getContent(field.$element, elementId);

                    // Reconstruit le contenu multilingue complet et l'écrit
                    // dans le champ, pour que Moodle le lise à la soumission.
                    const fullContent = buildMultilang(field.langData);
                    field.$element.val(fullContent);
                });

                submitting = true;
                HTMLFormElement.prototype.submit.call(form);
            });
        });
    }

    /*
     * Edit in place (core/inplace_editable) : noms de section, d'activité,
     * etc. Ces champs n'existent pas dans le DOM au chargement : ils sont
     * créés dynamiquement au clic sur le lien d'édition, puis détruits
     * après enregistrement. On ne peut pas les cibler par sélecteur
     * statique comme les textarea classiques : on observe le DOM.
     */

    // Liste des "component-itemtype" pour lesquels on active les onglets.
    // Fournie par lib.php via init() ; vide = fonctionnalité désactivée.
    let inplaceTargets = [];

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

        // Moodle pré-remplit l'input avec la valeur brute complète
        // (toutes langues confondues) : on la remplace avant que
        // l'utilisateur ne la voie.
        $input.val(state.langData[state.currentLang] || '');

        const $tabBar = $('<span class="multilangtabs-inplace-bar"></span>');
        languages.forEach(function(lang) {
            $tabBar.append(
                $('<button type="button"></button>')
                    .text(lang.code.toUpperCase())
                    .attr('data-lang', lang.code)
                    .toggleClass('active', lang.code === state.currentLang)
            );
        });
        $input.after($tabBar);

        // Empêche le clic sur un onglet de faire perdre le focus à l'input
        // (un blur pourrait annuler ou enregistrer prématurément l'édition
        // selon la version de Moodle).
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
            $tabBar.find('button').removeClass('active');
            $(this).addClass('active');
        });

        // Reconstruit la valeur multilingue complète juste avant que le
        // core ne la lise pour l'enregistrement. Branché directement sur
        // l'input (pas délégué sur "body"), donc exécuté AVANT le handler
        // natif de core/inplace_editable qui, lui, écoute au niveau body
        // et appelle stopImmediatePropagation() sur le clic du lien
        // d'édition (mais pas sur ce keydown, qui remonte normalement).
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

        // Le nœud ajouté par la mutation peut être :
        // - l'élément [data-inplaceeditable] lui-même,
        // - un descendant qui en contient un (rare),
        // - OU (le cas le plus fréquent en pratique) un descendant DE
        //   l'élément [data-inplaceeditable], puisque Moodle réécrit le
        //   contenu interne de cet élément existant plutôt que de le
        //   remplacer entièrement. Il faut donc aussi chercher vers le
        //   haut via closest(), sans quoi ce cas est systématiquement raté.
        let $mainelements = $node.filter('[data-inplaceeditable]')
            .add($node.find('[data-inplaceeditable]'))
            .add($node.closest('[data-inplaceeditable]'));

        $mainelements.each(function() {
            const $mainelement = $(this);

            if ($mainelement.attr('data-type') !== 'text') {
                return; // On ignore select/toggle : pas de sens en multilingue.
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
            log.debug('multilangtabs: MutationObserver indisponible.');
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
            // Ce paramètre n'était pas lu auparavant : inplaceTargets
            // restait vide en permanence et bloquait toute la
            // fonctionnalité d'édition en place, quel que soit le
            // contenu envoyé par lib.php.
            if (params && params.inplaceTargets) {
                inplaceTargets = params.inplaceTargets;
            }

            $(document).ready(function() {
                const selector = [
                    'textarea[id*="intro"]',
                    'textarea[id="id_summary_editor"]',
                    'textarea[name*="intro"]',
                    'textarea[name="page"]',
                    'textarea[name^="page["]',
                    'textarea.editor',
                    'input[type="text"][id="id_name"]',
                    'input[type="text"][id="id_pagetitle"]',
                    'input[type="text"][id*="id_"]',
                    'input[type="text"][name="name"]'
                ].join(', ');

                // Petit délai pour laisser Moodle instancier ses éléments DOM
                // avant qu'on cherche à les décorer.
                setTimeout(function() {
                    $(selector).each(function() {
                        setupTabs($(this));
                    });
                    setupFormSubmit();

                    // Cet appel manquait également : sans lui, aucun
                    // observateur n'est jamais créé et le clic sur un
                    // crayon d'édition en place ne déclenche rien côté
                    // multilangtabs.
                    if (inplaceTargets.length) {
                        setupInplaceObserver();
                    }
                }, 400);
            });
        }
    };
});