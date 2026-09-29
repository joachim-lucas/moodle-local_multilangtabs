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
 * Describe the state of the site regarding the multilanguage filters.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_status {
    /**
     * A multilanguage filter is active and applies to the content and the headings.
     */
    public const FILTER_STATUS_OK = 0;

    /**
     * No multilanguage filter is actually active on the site.
     */
    public const FILTER_STATUS_OFF = 1;

    /**
     * A multilanguage filter is active, but it applies to the content only.
     */
    public const FILTER_STATUS_CONTENT_ONLY = 2;

    /**
     * Return the multilanguage filter which is actually running on the site.
     *
     * A filter is considered active only when its global state is TEXTFILTER_ON: the "Off but
     * available" state does not process anything. As in languages::get_format(), the multilang2
     * filter is preferred over the core multilang filter when both are enabled.
     *
     * @return string|null 'multilang2', 'multilang', or null when no multilanguage filter is active.
     */
    public static function get_active_filter(): ?string {
        $states = filter_get_global_states();

        foreach ([languages::FILTER_MLANG2, languages::FILTER_MLANG] as $filter) {
            // The active column comes back as a string from the database, hence the cast.
            if (isset($states[$filter]) && (int) $states[$filter]->active === TEXTFILTER_ON) {
                return $filter;
            }
        }

        return null;
    }

    /**
     * Describe the state of the site regarding the multilanguage filters.
     *
     * The tabs are expected to work only when the active filter also applies to the short strings
     * (the headings), as this is what the headings such as the course name are formatted with.
     *
     * @return int One of the FILTER_STATUS_* constants of this class.
     */
    public static function get_state(): int {
        $active = self::get_active_filter();
        if ($active === null) {
            return self::FILTER_STATUS_OFF;
        }

        if (isset(filter_get_string_filters()[$active])) {
            return self::FILTER_STATUS_OK;
        }

        return self::FILTER_STATUS_CONTENT_ONLY;
    }
}
