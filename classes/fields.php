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
 * Resolves which form fields are decorated with language tabs.
 *
 * Moodle keeps no registry of the fields displaying their value through a multilanguage
 * filter, so the fields cannot be detected in PHP: the tabs are placed on the fields of a
 * form built by the core or by any other plugin. What PHP does provide is the policy, which
 * is read from the settings below and sent to the AMD module, which then relies on the
 * data-fieldtype attribute Moodle puts on every form element.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fields {
    /**
     * Inplace editable targets decorated when the setting is left empty, as component-itemtype pairs.
     *
     * @var string[]
     */
    public const DEFAULT_INPLACE_TARGETS = [
        'format_topics-sectionname',
        'format_topics-sectionnamenl',
        'format_weeks-sectionname',
        'format_weeks-sectionnamenl',
    ];

    /**
     * Return the inplace editable targets to decorate, as selected by the administrator.
     *
     * A textarea setting is stored as a raw string, so the value is split on commas, spaces and
     * line breaks rather than unserialized. Moodle does not store the default value of a setting,
     * so the fallback below keeps the feature enabled on a site where the setting has never been
     * saved. get_config() returns false when the setting is absent, and an empty string when the
     * administrator saved it empty, which disables the inplace editable support altogether.
     *
     * @return string[]
     */
    public static function get_inplace_targets(): array {
        $value = get_config('local_multilangtabs', 'inplacetargets');
        if ($value === false) {
            return self::DEFAULT_INPLACE_TARGETS;
        }

        return self::parse_list($value);
    }

    /**
     * Return the plain text form elements to decorate, as selected by the administrator.
     *
     * Only the plain text fields are concerned, the editors being always decorated. An empty
     * setting, which is the default, means every plain text field of the page.
     *
     * @return string[] Element names, e.g. ['name', 'pagetitle'].
     */
    public static function get_textfield_names(): array {
        $value = get_config('local_multilangtabs', 'textfields');
        if ($value === false) {
            return [];
        }

        return self::parse_list($value);
    }

    /**
     * Split a setting holding a list of values separated by commas, spaces or line breaks.
     *
     * @param mixed $value Raw value of the setting.
     * @return string[] Non empty items only, in the order they were entered.
     */
    private static function parse_list($value): array {
        $items = preg_split('/[\s,]+/', trim((string)$value), -1, PREG_SPLIT_NO_EMPTY);

        return array_values($items);
    }
}
