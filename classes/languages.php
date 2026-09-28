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
 * Resolves the languages and the markup format the editor tabs have to work with.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class languages {
    /**
     * Name of the filter providing the {mlang xx}...{mlang} syntax, handling several blocks.
     */
    public const FILTER_MLANG2 = 'multilang2';

    /**
     * Name of the filter providing the <span lang="xx"> syntax, handling a single block.
     */
    public const FILTER_MLANG = 'multilang';

    /**
     * Return the markup format expected by the multilanguage filter available on this site.
     *
     * @return string|null 'mlang2', 'span', or null when no multilanguage filter is available.
     */
    public static function get_format(): ?string {
        if (filter_is_enabled(self::FILTER_MLANG2)) {
            return 'mlang2';
        }
        if (filter_is_enabled(self::FILTER_MLANG)) {
            return 'span';
        }
        return null;
    }

    /**
     * Return the language codes proposed as tabs, as selected by the administrator.
     *
     * A multicheckbox setting is stored as a comma separated list of the selected keys, so the raw
     * value is split on commas rather than unserialized. When nothing is selected, the codes of all
     * installed language packs are proposed.
     *
     * @return string[]
     */
    public static function get_codes(): array {
        $selected = array_filter(array_map('trim', explode(',', (string)get_config('local_multilangtabs', 'languages'))));

        if (!$selected) {
            $selected = array_keys(get_string_manager()->get_list_of_translations());
        }

        return array_values($selected);
    }

    /**
     * Return the languages to propose as tabs, ready to be sent to the AMD module.
     *
     * @return array[] List of ['code' => string, 'label' => string] items.
     */
    public static function get_tabs(): array {
        return array_values(array_map(
            fn(string $code): array => ['code' => $code, 'label' => strtoupper($code)],
            self::get_codes()
        ));
    }

    /**
     * Return the code the tabs have to open on.
     *
     * The current user language may not belong to the proposed languages (a regional variant, or a
     * language which was not selected by the administrator), in which case the first proposed
     * language is used instead of sending an unusable value to the AMD module.
     *
     * @param string[] $codes
     * @return string Empty when no language is proposed.
     */
    public static function get_default_code(array $codes): string {
        if (!$codes) {
            return '';
        }

        $currentlanguage = current_language();

        if (in_array($currentlanguage, $codes, true)) {
            return $currentlanguage;
        }

        return reset($codes);
    }
}
