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

namespace local_multilangtabs;

/**
 * Display the state of the multilanguage filters on the plugin settings page.
 *
 * This is a display only setting: it renders a notification explaining whether the tabs can work
 * on the site, based on the state of the filters, without saving anything.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_filterstatus extends \admin_setting_heading {
    /**
     * Build the filter status setting.
     */
    public function __construct() {
        parent::__construct('local_multilangtabs/filterstatus', '', '');
    }

    /**
     * Render the notification matching the current state of the multilanguage filters.
     *
     * @param string $data Unused.
     * @param string $query Unused.
     * @return string The notification, as an HTML string.
     */
    public function output_html($data, $query = '') {
        global $OUTPUT;

        $filterspage = \html_writer::link(new \moodle_url('/admin/filters.php'), get_string('filtersettings', 'admin'));
        $state = filter_status::get_state();

        if ($state === filter_status::FILTER_STATUS_OFF) {
            $message = get_string('multilangfilter_off', 'local_multilangtabs', [
                'filterspage' => $filterspage,
            ]);
            $level = \core\output\notification::NOTIFY_WARNING;
        } else if ($state === filter_status::FILTER_STATUS_CONTENT_ONLY) {
            $message = get_string('multilangfilter_content', 'local_multilangtabs', [
                'filter' => filter_status::get_active_filter(),
                'filterspage' => $filterspage,
            ]);
            $level = \core\output\notification::NOTIFY_WARNING;
        } else {
            $message = get_string('multilangfilter_ok', 'local_multilangtabs');
            $level = \core\output\notification::NOTIFY_SUCCESS;
        }

        // Render the notification inline, right above the settings below, instead of adding it to
        // the page notification stack (\core\notification::warning/success echo to the page header).
        $notification = new \core\output\notification($message, $level);
        return \html_writer::div($OUTPUT->render($notification), 'mlt-filterstatus');
    }
}
