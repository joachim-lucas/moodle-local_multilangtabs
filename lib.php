<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Hook exécuté avant le rendu du footer sur chaque page.
 *
 * Moodle appelle automatiquement une fonction nommée
 * {component}_before_footer() si elle est définie dans le lib.php
 * d'un plugin local, sans configuration supplémentaire.
 */
function local_multilangtabs_before_footer() {
    global $PAGE;

    // Déterminer quel filtre multilingue est actif, et donc quel format
    // de balisage le JS doit produire à l'enregistrement.
    // - filter_multilang2 utilise la syntaxe {mlang xx}...{mlang}
    //   et gère correctement le contenu multi-blocs (plusieurs <p> par langue).
    // - filter_multilang (legacy) utilise <span lang="xx" class="multilang">
    //   et ne garantit un comportement correct que pour un contenu simple
    //   (un seul bloc), voir la documentation du plugin tiny_multilang2.
    $format = null;
    if (filter_is_enabled('multilang2')) {
        $format = 'mlang2';
    } else if (filter_is_enabled('multilang')) {
        $format = 'span';
    }

    if ($format === null) {
        // Aucun filtre multilingue actif : le contenu généré ne serait
        // interprété par personne, inutile de charger le JS.
        return;
    }

    // Récupérer la liste des langues cochées dans les réglages du plugin.
    // admin_setting_configmulticheckbox stocke un tableau sérialisé de la
    // forme ['fr' => 1, 'en' => 1, 'es' => 0, ...] : seules les clés dont
    // la valeur est "1" correspondent à une case cochée.
    $raw_config = get_config('local_multilangtabs', 'languages');
    $checked = @unserialize($raw_config);

    if (is_array($checked)) {
        $lang_codes = array_keys(array_filter($checked, function($val) {
            return (string)$val === '1';
        }));
    } else {
        $lang_codes = [];
    }

    if (empty($lang_codes)) {
        // Aucune case cochée (ou réglage jamais enregistré) : repli sur
        // la détection automatique des packs de langue installés.
        $installed = get_string_manager()->get_list_of_translations();
        $lang_codes = array_keys($installed);
    }

    if (empty($lang_codes)) {
        // Sécurité : si malgré tout la liste est vide, on ne charge rien
        // plutôt que d'afficher une barre d'onglets sans langue.
        return;
    }

    $default_lang = current_language();

    // La langue courante de l'utilisateur peut ne pas faire partie des
    // langues configurées pour le multilinguisme (ex: variante régionale,
    // ou langue non prévue dans ce champ) : on retombe alors sur la
    // première langue configurée plutôt que d'envoyer une valeur invalide.
    if (!in_array($default_lang, $lang_codes, true)) {
        $default_lang = reset($lang_codes);
    }

    $languages_config = [];
    foreach ($lang_codes as $code) {
        $languages_config[] = [
            'code' => $code,
            'label' => strtoupper($code)
        ];
    }

    $params = [
        'languages' => $languages_config,
        'defaultLang' => $default_lang,
        'format' => $format,
    ];

    // Injection du script AMD uniquement si un filtre multilingue est actif.
    $PAGE->requires->js_call_amd('local_multilangtabs/editor', 'init', [$params]);
}