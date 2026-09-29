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
 * Unit tests for the filter status resolution of the multi-language tabs plugin.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_multilangtabs\filter_status
 */
final class filter_status_test extends \advanced_testcase {
    /**
     * Test that no filter is reported when none of them is actually active.
     *
     * @covers ::get_active_filter
     * @covers ::get_state
     */
    public function test_tabs_are_off_without_any_active_filter(): void {
        $this->resetAfterTest();
        $this->disable_all_filters();

        $this->assertNull(filter_status::get_active_filter());
        $this->assertSame(filter_status::FILTER_STATUS_OFF, filter_status::get_state());
    }

    /**
     * Test that the "Off but available" state is not enough for the tabs to run.
     *
     * @covers ::get_active_filter
     * @covers ::get_state
     */
    public function test_tabs_are_off_when_the_filter_is_off_but_available(): void {
        $this->resetAfterTest();
        $this->disable_all_filters();
        filter_set_global_state(languages::FILTER_MLANG, TEXTFILTER_OFF);
        filter_set_applies_to_strings(languages::FILTER_MLANG, true);

        $this->assertNull(filter_status::get_active_filter());
        $this->assertSame(filter_status::FILTER_STATUS_OFF, filter_status::get_state());
    }

    /**
     * Test that the core filter applied to the content and the headings is reported as in order.
     *
     * @covers ::get_active_filter
     * @covers ::get_state
     */
    public function test_tabs_are_ok_with_the_multilang_filter_on_content_and_headings(): void {
        $this->resetAfterTest();
        $this->disable_all_filters();
        filter_set_global_state(languages::FILTER_MLANG, TEXTFILTER_ON);
        filter_set_applies_to_strings(languages::FILTER_MLANG, true);

        $this->assertSame(languages::FILTER_MLANG, filter_status::get_active_filter());
        $this->assertSame(filter_status::FILTER_STATUS_OK, filter_status::get_state());
    }

    /**
     * Test that the core filter applied to the content only is reported.
     *
     * @covers ::get_active_filter
     * @covers ::get_state
     */
    public function test_tabs_are_content_only_with_the_multilang_filter(): void {
        $this->resetAfterTest();
        $this->disable_all_filters();
        filter_set_global_state(languages::FILTER_MLANG, TEXTFILTER_ON);

        $this->assertSame(languages::FILTER_MLANG, filter_status::get_active_filter());
        $this->assertSame(filter_status::FILTER_STATUS_CONTENT_ONLY, filter_status::get_state());
    }

    /**
     * Test that the multilang2 filter is preferred over the core one, and reported as in order.
     *
     * @covers ::get_active_filter
     * @covers ::get_state
     */
    public function test_tabs_are_ok_with_the_multilang2_filter_on_content_and_headings(): void {
        $this->resetAfterTest();
        $this->disable_all_filters();
        filter_set_global_state(languages::FILTER_MLANG2, TEXTFILTER_ON);
        filter_set_global_state(languages::FILTER_MLANG, TEXTFILTER_ON);
        $this->set_string_filters(languages::FILTER_MLANG2);

        $this->assertSame(languages::FILTER_MLANG2, filter_status::get_active_filter());
        $this->assertSame(filter_status::FILTER_STATUS_OK, filter_status::get_state());
    }

    /**
     * Test that the multilang2 filter applied to the content only is reported.
     *
     * @covers ::get_active_filter
     * @covers ::get_state
     */
    public function test_tabs_are_content_only_with_the_multilang2_filter(): void {
        $this->resetAfterTest();
        $this->disable_all_filters();
        filter_set_global_state(languages::FILTER_MLANG2, TEXTFILTER_ON);

        $this->assertSame(languages::FILTER_MLANG2, filter_status::get_active_filter());
        $this->assertSame(filter_status::FILTER_STATUS_CONTENT_ONLY, filter_status::get_state());
    }

    /**
     * Disable every text filter of the site.
     *
     * The state of the filters is read from the database by the resolved helpers, so no cache has
     * to be dropped.
     */
    protected function disable_all_filters(): void {
        foreach (array_keys(filter_get_global_states()) as $filter) {
            filter_set_global_state($filter, TEXTFILTER_DISABLED);
        }
    }

    /**
     * Ask the given filter to apply to the short strings too, as the core "apply to strings" toggle does.
     *
     * The core filter_set_applies_to_strings() helper removes the filters which are not installed
     * as plugins, so the multilang2 filter has to be configured with its raw config settings.
     *
     * @param string $filter Filter name, for instance "multilang2".
     */
    protected function set_string_filters(string $filter): void {
        set_config('stringfilters', $filter);
        set_config('filterall', 1);
    }
}
