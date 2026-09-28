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
 * is sent to the AMD module, which then relies on the data-fieldtype attribute Moodle puts on
 * every form element.
 *
 * The policy is one general rule, every rich text editor and every plain text field of the
 * page, narrowed down by exceptions coming from two levels:
 *
 * - the site wide exceptions below, which are set in this file and are meant to be edited
 *   by whoever maintains the code, not from the settings;
 * - the exceptions of the user, which each user sets for themselves.
 *
 * A field is decorated when the general rule covers it, or when an inclusion list names it,
 * and when no exclusion list names it. An exclusion therefore always wins over an inclusion.
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
     * Site wide exclusions: fields which are never decorated, whatever the general rule and the
     * inclusions say. To keep a field untouched on every page of the site, add its name here,
     * for instance to leave an identification number out of the tabs:
     *
     *     public const EXCLUDED_FIELDS = ['idnumber'];
     *
     * The names are those of the form elements, the ones the browser shows in the name
     * attribute of the field. Empty by default, which excludes nothing.
     *
     * @var string[]
     */
    public const EXCLUDED_FIELDS = [];

    /**
     * Site wide inclusions: fields which are decorated even though the general rule does not
     * cover them. The general rule covers the rich text editors and the plain text fields, so
     * this is the list to use for the form elements of another type, a plain textarea for
     * instance, or for the fields of a particular form only:
     *
     *     public const INCLUDED_FIELDS = ['notes'];
     *
     * Names are matched the same way as in EXCLUDED_FIELDS. Empty by default, which includes
     * nothing beyond the general rule.
     *
     * @var string[]
     */
    public const INCLUDED_FIELDS = [];

    /**
     * Name of the user preference holding the fields the user excludes from the tabs.
     *
     * @var string
     */
    public const USER_EXCLUDED_FIELDS_PREFERENCE = 'local_multilangtabs_excludedfields';

    /**
     * Name of the user preference holding the fields the user includes in the tabs.
     *
     * @var string
     */
    public const USER_INCLUDED_FIELDS_PREFERENCE = 'local_multilangtabs_includedfields';

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
     * Return the names of the fields to exclude from the tabs on every page of the site.
     *
     * @param int|null $userid User the exclusions are read for, current user when null.
     * @return string[] Element names, e.g. ['idnumber'].
     */
    public static function get_excluded_field_names(?int $userid = null): array {
        return array_values(array_unique(array_merge(
            self::EXCLUDED_FIELDS,
            self::get_user_field_names(self::USER_EXCLUDED_FIELDS_PREFERENCE, $userid)
        )));
    }

    /**
     * Return the names of the fields to decorate even though the general rule does not cover them.
     *
     * @param int|null $userid User the inclusions are read for, current user when null.
     * @return string[] Element names, e.g. ['notes'].
     */
    public static function get_included_field_names(?int $userid = null): array {
        return array_values(array_unique(array_merge(
            self::INCLUDED_FIELDS,
            self::get_user_field_names(self::USER_INCLUDED_FIELDS_PREFERENCE, $userid)
        )));
    }

    /**
     * Return the list of field names a user entered in their own settings.
     *
     * The list is stored as a raw string, the way a textarea stores it. An unset preference
     * gives an empty list, which is also what an empty list means: no exception of that kind.
     *
     * @param string $preference Name of the user preference holding the list.
     * @param int|null $userid User the list is read for, current user when null.
     * @return string[] Element names, in the order they were entered.
     */
    private static function get_user_field_names(string $preference, ?int $userid = null): array {
        $value = get_user_preferences($preference, null, $userid);
        if (empty($value)) {
            return [];
        }

        return self::parse_list($value);
    }

    /**
     * Split a raw list of values separated by commas, spaces or line breaks.
     *
     * @param mixed $value Raw list, as stored in a setting or in a user preference.
     * @return string[] Non empty items only, in the order they were entered.
     */
    public static function parse_list($value): array {
        $items = preg_split('/[\s,]+/', trim((string)$value), -1, PREG_SPLIT_NO_EMPTY);

        return array_values($items);
    }
}
