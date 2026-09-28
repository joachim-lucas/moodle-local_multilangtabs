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
 * Page letting each user exclude fields from their own language tabs, or include the ones the
 * general rule does not cover.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

use local_multilangtabs\fields;
use local_multilangtabs\userprefs_form;

$PAGE->set_url('/local/multilangtabs/userprefs.php');
$PAGE->set_context(\context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('userprefs', 'local_multilangtabs'));
$PAGE->set_heading(get_string('userprefs', 'local_multilangtabs'));

// Every user manages their own exceptions, so no capability is required beyond being logged in.
require_login();

$form = new userprefs_form();
$form->set_data([
    'includedfields' => get_user_preferences(
        fields::USER_INCLUDED_FIELDS_PREFERENCE,
        '',
        $USER->id
    ),
    'excludedfields' => get_user_preferences(
        fields::USER_EXCLUDED_FIELDS_PREFERENCE,
        '',
        $USER->id
    ),
]);

if ($data = $form->get_data()) {
    require_sesskey();

    // The lists are stored as entered, commas and line breaks included, so that the form
    // gives back what the user typed the next time they come to it.
    set_user_preference(fields::USER_INCLUDED_FIELDS_PREFERENCE, trim($data->includedfields), $USER->id);
    set_user_preference(fields::USER_EXCLUDED_FIELDS_PREFERENCE, trim($data->excludedfields), $USER->id);

    $notification = get_string('userprefs_saved', 'local_multilangtabs');
    $url = new moodle_url('/local/multilangtabs/userprefs.php');
    redirect($url, $notification, null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('userprefs', 'local_multilangtabs'));

// Explain that the exceptions set on the site are not shown here, as those always win.
echo $OUTPUT->box(
    get_string('userprefs_intro', 'local_multilangtabs'),
    'generalbox'
);

$form->display();
echo $OUTPUT->footer();
