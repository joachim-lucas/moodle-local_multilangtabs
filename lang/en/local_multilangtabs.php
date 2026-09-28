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

$string['inplacetargets'] = 'In-place editable fields';
$string['inplacetargets_desc'] = 'One target per line, as a "component-itemtype" pair, for instance
    format_topics-sectionname. Leave the field empty to disable the language tabs on the fields edited in place.';
$string['languages'] = 'Available languages';
$string['languages_desc'] = 'Tick the languages to propose as tabs. When nothing is ticked, all installed language packs are proposed.';
$string['pluginname'] = 'Multi-language tabs';
$string['userexceptions_invalid'] = '{$a} is not a valid field name. Use the names of the form elements, such as name or pagetitle.';
$string['userexcludedfields'] = 'Fields to exclude';
$string['userexcludedfields_help'] = 'Names of the form elements you do not want the language tabs on, separated
    by commas, for instance "idnumber, idnumber2". Leave the field empty to exclude nothing. An exclusion
    always wins, whatever the inclusion lists say.';
$string['userincludedfields'] = 'Fields to include';
$string['userincludedfields_help'] = 'Names of the form elements you want the language tabs on even though they are
    not rich text editors nor plain text fields, separated by commas, for instance "notes". Leave the field
    empty to include nothing beyond the default fields.';
$string['userprefs'] = 'Multi-language tabs';
$string['userprefs_intro'] = 'Every rich text editor and every plain text field of the pages you edit is decorated with
    language tabs. The two lists below are for the fields which have to escape that rule, in the pages of
    your own environment, in a form of a plugin you use for a specific purpose, or simply in one form out
    of all the others. The lists set on the site, by the administrator, are not listed here, and always
    win over yours.';
$string['userprefs_saved'] = 'Your exceptions have been saved.';
