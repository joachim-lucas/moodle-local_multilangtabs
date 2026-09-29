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

use local_multilangtabs\fields;

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_multilangtabs',
        get_string('pluginname', 'local_multilangtabs')
    );

    if ($ADMIN->fulltree) {
        // The choices are built from the language packs really installed on the
        // platform (for instance 'fr' => 'French (fr)').
        $choices = get_string_manager()->get_list_of_translations();

        // Languages checked by default when the administrator has not chosen any yet.
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

        $settings->add(new admin_setting_configtextarea(
            'local_multilangtabs/inplacetargets',
            get_string('inplacetargets', 'local_multilangtabs'),
            get_string('inplacetargets_desc', 'local_multilangtabs'),
            implode(PHP_EOL, fields::DEFAULT_INPLACE_TARGETS)
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
    }

    $ADMIN->add('localplugins', $settings);

    // Stops Moodle from adding a second, generic settings page for this plugin,
    // which is the default behaviour for local plugins without a settings.php.
    $settings = null;
}
