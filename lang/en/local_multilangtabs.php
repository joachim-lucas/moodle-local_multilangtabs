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
$string['textfields'] = 'Plain text fields';
$string['textfields_desc'] = 'Names of the plain text form elements to decorate with language tabs, separated
    by commas, for instance "name, pagetitle". Every rich text editor is always decorated. Leave the field
    empty to decorate all the plain text fields, which is the default.';
