<?php
defined('MOODLE_INTERNAL') || die();

// Contrairement aux plugins de type mod/block, $settings n'est PAS
// pré-créé par Moodle pour les plugins de type "local" : c'est à ce
// fichier de créer sa propre page de réglages et de l'ajouter
// explicitement à l'arbre d'administration.
if ($hassiteconfig) {

    $settings = new admin_settingpage(
        'local_multilangtabs',
        get_string('pluginname', 'local_multilangtabs')
    );

    if ($ADMIN->fulltree) {

        // Construit la liste des choix à partir des packs de langue
        // réellement installés sur la plateforme (ex: 'fr' => 'Français (fr)').
        $choices = get_string_manager()->get_list_of_translations();

        // Langues cochées par défaut si l'admin n'a encore rien choisi.
        $defaults = [];
        foreach (['fr', 'en'] as $code) {
            if (isset($choices[$code])) {
                $defaults[$code] = 1;
            }
        }

        $settings->add(new admin_setting_configmulticheckbox(
            'local_multilangtabs/languages',
            get_string('languages', 'local_multilangtabs'),
            get_string('languages_desc', 'local_multilangtabs'),
            $defaults,
            $choices
        ));
    }

    $ADMIN->add('localplugins', $settings);

    // Empêche Moodle de tenter d'ajouter une seconde fois une page de
    // réglages générique pour ce plugin (comportement par défaut pour
    // les plugins locaux sans settings.php).
    $settings = null;
}