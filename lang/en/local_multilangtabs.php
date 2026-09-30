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
 * English language strings for the multi-language tabs plugin.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['advancedsettings'] = 'Advanced settings';
$string['advancedsettings_desc'] = 'Nothing to fill in here for a normal use of the plugin, which already gives
    language tabs to every text field. The four lists below are only useful in an unusual case: to add the tabs
    to a field which does not hold a text, or to remove them from a field which would normally get them. Leaving
    all four lists empty is the expected configuration.';
$string['excludedfields'] = 'Fields to exclude';
$string['excludedfields_desc'] = 'Names of the form elements which must not get language tabs, separated by
    commas, for instance "idnumber". Leave the field empty to exclude nothing here. This list is added
    to the one set in the code of the plugin, classes/fields.php, which always applies.';
$string['includedfields'] = 'Fields to include';
$string['includedfields_desc'] = 'Names of the form elements which must get language tabs even though they are
    neither rich text editors nor plain text fields, separated by commas, for instance "notes". Leave
    the field empty to include nothing here. This list is added to the one set in the code of the
    plugin, classes/fields.php, which always applies.';
$string['inplaceexcluded'] = 'In-place editable fields to exclude';
$string['inplaceexcluded_desc'] = 'Names of the fields edited in place which must not get language tabs, separated by
    commas, as a "component-itemtype" pair, for instance "core_tag-tagname". Leave the field empty to exclude
    nothing here. This list is added to the one set in the code of the plugin, classes/fields.php, which always
    applies.';
$string['inplaceincluded'] = 'In-place editable fields to include';
$string['inplaceincluded_desc'] = 'Names of the fields edited in place which must get language tabs even though they do
    not hold a text, separated by commas, as a "component-itemtype" pair, for instance
    "mod_forum-digestoptions". Leave the field empty to include nothing here. This list is added to the one set in
    the code of the plugin, classes/fields.php, which always applies.';
$string['languages'] = 'Available languages';
$string['languages_desc'] = 'Tick the languages to propose as tabs. When nothing is ticked, all installed language packs are proposed.';
$string['multilangfilter_content'] = 'The {$a->filter} filter applies to the content only, not to the headings or
    the other short strings. Set it on "Content and headings" on the {$a->filterspage} page if you want the plugin to
    apply to the headings too (otherwise titles such as the course name and the activity names may keep showing the
    raw language marks).';
$string['multilangfilter_off'] = 'No multilanguage filter is active on this site. Without either the "multilang" or
    the "multilang2" filter the language marks are not interpreted, so the tabs do not work. Activate one of them on
    the {$a->filterspage} page.';
$string['multilangfilter_ok'] = 'Everything is in order: a multilanguage filter is active and applies to the content
    and the headings, so the plugin\'s language tabs will work as expected.';
$string['pluginname'] = 'Multi-language tabs';
$string['privacy:metadata'] = 'The multi-language tabs plugin does not store any personal data.';
