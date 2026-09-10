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
     * Extrait le contenu par langue depuis un texte contenant soit des
     * balises <span lang="xx" class="multilang">, soit des balises
     * {mlang xx}...{mlang}. Les deux formats sont toujours reconnus en
     * lecture, quel que soit outputFormat, pour rester rétrocompatible
     * avec du contenu déjà enregistré dans l'autre format.
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
     * Reconstruit le contenu multilingue complet dans le format
     * effectivement attendu par le filtre actif côté serveur.
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
     * Récupère le contenu depuis TinyMCE si l'éditeur est initialisé pour
     * cet élément, sinon depuis le champ brut (textarea/input).
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
     * Définit le contenu dans TinyMCE si disponible, sinon dans le champ brut.
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
     * Attend que TinyMCE soit initialisé pour cet élément, avec une
     * limite de tentatives : évite une boucle de setTimeout infinie
     * sur les champs qui n'utilisent finalement pas TinyMCE (éditeur
     * texte brut, Atto, etc.).
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
     * Met en place la barre d'onglets de langue pour un champ donné.
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
                }, 400);
            });
        }
    };
});