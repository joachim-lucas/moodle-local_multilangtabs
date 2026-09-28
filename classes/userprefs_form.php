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
 * Form letting a user exclude fields from their own language tabs, or include the ones the
 * general rule does not cover.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userprefs_form extends \moodleform {
    /**
     * Define the form, which only holds the two lists of exceptions of the current user.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('userprefs', 'local_multilangtabs'));

        $mform->addElement(
            'textarea',
            'includedfields',
            get_string('userincludedfields', 'local_multilangtabs'),
            get_string('userincludedfields_help', 'local_multilangtabs'),
            ['rows' => 6, 'cols' => 50]
        );
        $mform->setType('includedfields', PARAM_RAW);

        $mform->addElement(
            'textarea',
            'excludedfields',
            get_string('userexcludedfields', 'local_multilangtabs'),
            get_string('userexcludedfields_help', 'local_multilangtabs'),
            ['rows' => 6, 'cols' => 50]
        );
        $mform->setType('excludedfields', PARAM_RAW);

        $this->add_action_buttons();
    }

    /**
     * Validate the two lists, which hold element names and nothing else.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Errors, empty when the form is valid.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        foreach (['includedfields', 'excludedfields'] as $name) {
            foreach (fields::parse_list($data[$name] ?? '') as $field) {
                if (!preg_match('/^[A-Za-z0-9_\[\]]+$/', $field)) {
                    $errors[$name] = get_string('userexceptions_invalid', 'local_multilangtabs', $field);
                    break;
                }
            }
        }

        return $errors;
    }
}
