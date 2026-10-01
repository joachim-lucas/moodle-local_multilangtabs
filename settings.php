<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Administration settings of the multi-language tabs plugin.
 *
 * Contrary to mod and block plugins, Moodle does not pre-create $settings for local plugins: this
 * file has to build its own settings page and add it explicitly to the administration tree.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_multilangtabs\admin_setting_filterstatus;

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_multilangtabs',
        get_string('pluginname', 'local_multilangtabs')
    );

    if ($ADMIN->fulltree) {
        // Warn the administrator when no multilanguage filter can interpret the marks on the site.
        $settings->add(new admin_setting_filterstatus());

        // The choices are built from the language packs really installed on the
        // platform (for instance 'fr' => 'French (fr)').
        $choices = get_string_manager()->get_list_of_translations();

        // No language is ticked by default, which is what languages::get_codes() falls back
        // on: every installed language pack. A default listing a few codes would be
        // misleading rather than convenient, because Moodle never pre-ticks a multicheckbox
        // from its default: it would only show up in the "Default:" hint of the setting,
        // while the languages actually proposed stayed the installed ones.
        $settings->add(new admin_setting_configmulticheckbox(
            'local_multilangtabs/languages',
            get_string('languages', 'local_multilangtabs'),
            get_string('languages_desc', 'local_multilangtabs'),
            [],
            $choices
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_multilangtabs/includedfields',
            get_string('includedfields', 'local_multilangtabs'),
            get_string('includedfields_desc', 'local_multilangtabs'),
            ''
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_multilangtabs/excludedfields',
            get_string('excludedfields', 'local_multilangtabs'),
            get_string('excludedfields_desc', 'local_multilangtabs'),
            ''
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_multilangtabs/inplaceincluded',
            get_string('inplaceincluded', 'local_multilangtabs'),
            get_string('inplaceincluded_desc', 'local_multilangtabs'),
            ''
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_multilangtabs/inplaceexcluded',
            get_string('inplaceexcluded', 'local_multilangtabs'),
            get_string('inplaceexcluded_desc', 'local_multilangtabs'),
            ''
        ));

        // Fold the four advanced settings into a closed disclosure, to tell the administrator
        // that there is nothing to fill in unless they hit an unusual case. The module is inert
        // on the pages which do not render those rows, and leaves the rows in the form when it
        // moves them, so their values are still submitted normally.
        $PAGE->requires->js_call_amd('local_multilangtabs/settingsfold', 'init');
    }

    $ADMIN->add('localplugins', $settings);

    // Stops Moodle from adding a second, generic settings page for this plugin,
    // which is the default behaviour for local plugins without a settings.php.
    $settings = null;
}
