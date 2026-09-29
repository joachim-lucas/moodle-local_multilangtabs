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
 * - the exceptions below, which are set in this file and always apply, as they are a matter
 *   of development rather than of administration;
 * - the exceptions the administrator adds from the plugin settings, which apply to the whole
 *   site as well and are added to the ones below rather than replacing them.
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
     * Exclusions set in the code, which always apply whatever the settings hold. The names
     * below are the ones the core forms have nothing to translate: identification numbers,
     * account and secret values, addresses of the network and plain numbers, whose value is
     * never written as human language. A name goes here only when that is true of every form
     * using it, as the names are matched on every form of the site, whatever the plugin.
     *
     * The fields holding actual content, the ones whose value is shown to the users, must NOT
     * be listed: name, fullname, shortname when it is a friendly name, location, description,
     * intro, summary, and so on, which keep their language tabs.
     *
     * @var string[]
     */
    public const EXCLUDED_FIELDS = [
        // Identification numbers, of the course, of an activity, of a badge, of an enrolment.
        'idnumber',
        'cmidnumber',
        'platformid',
        'clientid',
        'deploymentid',
        'claimid',
        'targetcode',
        // Accounts and secrets, of the user, of an LTI tool, of the MoodleNet backpack.
        'username',
        'email',
        'resourcekey',
        'secret',
        'issuercontact',
        'backpackemail',
        'backpackemailcanvas',
        // URLs and network addresses, an LTI launch URL, a SCORM package, an ICS feed.
        'toolurl',
        'securetoolurl',
        'icon',
        'secureicon',
        'packageurl',
        'targeturl',
        'issuerurl',
        'backpackweburl',
        'backpackapiurl',
        'authenticationrequesturl',
        'jwksurl',
        'accesstokenurl',
        'subnet',
        'url',
        // Numbers and thresholds: sizes, positions, minutes, passing grades.
        'popupwidth',
        'popupheight',
        'width',
        'height',
        'navpositionleft',
        'navpositiontop',
        'blockafter',
        'warnafter',
        'entbypage',
        'timespent',
        'gradebetterthan',
        'gradepass',
        'submissiongradepass',
        'gradinggradepass',
        'repeats',
        'maxenrolled',
        'cost',
        'param1',
        'param2',
        // Identification fields of the user profile, which Moodle itself never translates.
        'shortname',
        'city',
        'institution',
        'department',
        'address',
        'phone1',
        'phone2',
        'version',
    ];

    /**
     * Inclusions set in the code, which always apply whatever the settings hold. The general
     * rule covers the rich text editors and the plain text fields, so this is the list to use
     * for the form elements of another type, a plain textarea for instance:
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
     * The list of the code comes first, then the one of the settings, so that the settings
     * cannot be mistaken for a way to lift an exclusion written in the code.
     *
     * @return string[] Element names, e.g. ['idnumber'].
     */
    public static function get_excluded_field_names(): array {
        return self::merge_with_setting(self::EXCLUDED_FIELDS, 'excludedfields');
    }

    /**
     * Return the names of the fields to decorate even though the general rule does not cover them.
     *
     * @return string[] Element names, e.g. ['notes'].
     */
    public static function get_included_field_names(): array {
        return self::merge_with_setting(self::INCLUDED_FIELDS, 'includedfields');
    }

    /**
     * Add the names entered by the administrator to a list of names set in the code.
     *
     * The setting is a textarea, so its value is split the same way, and an absent setting,
     * which get_config() reports as false, leaves the list of the code untouched.
     *
     * @param string[] $codednames Names set in the constants of this class.
     * @param string $setting Name of the setting holding the names to add.
     * @return string[] Names, in order, without duplicates.
     */
    private static function merge_with_setting(array $codednames, string $setting): array {
        $value = get_config('local_multilangtabs', $setting);
        if ($value === false) {
            return array_values(array_unique($codednames));
        }

        return array_values(array_unique(array_merge($codednames, self::parse_list($value))));
    }

    /**
     * Split a raw list of values separated by commas, spaces or line breaks.
     *
     * @param mixed $value Raw list, as stored in a setting.
     * @return string[] Non empty items only, in the order they were entered.
     */
    public static function parse_list($value): array {
        $items = preg_split('/[\s,]+/', trim((string)$value), -1, PREG_SPLIT_NO_EMPTY);

        return array_values($items);
    }
}
