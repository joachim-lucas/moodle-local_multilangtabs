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
 * Unit tests for the language resolution of the multi-language tabs plugin.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_multilangtabs\languages
 */
final class languages_test extends \advanced_testcase {
    /**
     * Test that the selected language codes are read from the comma separated setting.
     *
     * @covers ::get_codes
     */
    public function test_get_codes_from_settings(): void {
        set_config('languages', 'fr,en', 'local_multilangtabs');

        $this->assertSame(['fr', 'en'], languages::get_codes());
    }

    /**
     * Test that surrounding spaces and empty items are removed from the setting.
     *
     * @covers ::get_codes
     */
    public function test_get_codes_ignores_spaces_and_empty_items(): void {
        set_config('languages', ' fr , , en ,', 'local_multilangtabs');

        $this->assertSame(['fr', 'en'], languages::get_codes());
    }

    /**
     * Test that the installed language packs are proposed when the setting is empty.
     *
     * @covers ::get_codes
     */
    public function test_get_codes_falls_back_on_installed_translations(): void {
        set_config('languages', '', 'local_multilangtabs');

        $this->assertSame(array_keys(get_string_manager()->get_list_of_translations()), languages::get_codes());
    }

    /**
     * Test that the tabs are labelled with the uppercased language code.
     *
     * @covers ::get_tabs
     */
    public function test_get_tabs(): void {
        set_config('languages', 'fr,en', 'local_multilangtabs');

        $this->assertSame([
            ['code' => 'fr', 'label' => 'FR'],
            ['code' => 'en', 'label' => 'EN'],
        ], languages::get_tabs());
    }

    /**
     * Test that the tabs open on the current user language.
     *
     * @covers ::get_default_code
     */
    public function test_get_default_code_uses_the_current_language(): void {
        $this->assertSame('en', languages::get_default_code(['fr', 'en']));
    }

    /**
     * Test that an unknown current language falls back on the first proposed language.
     *
     * @covers ::get_default_code
     */
    public function test_get_default_code_falls_back_on_the_first_code(): void {
        $this->assertSame('fr', languages::get_default_code(['fr', 'en']));
    }

    /**
     * Test that no default language is returned when no language is proposed.
     *
     * @covers ::get_default_code
     */
    public function test_get_default_code_without_any_code(): void {
        $this->assertSame('', languages::get_default_code([]));
    }

    /**
     * Test that no format is returned when no multilanguage filter is available.
     *
     * @covers ::get_format
     */
    public function test_get_format_without_any_filter(): void {
        $this->resetAfterTest();
        $this->disable_all_filters();

        $this->assertNull(languages::get_format());
    }

    /**
     * Test that the syntax of the single block filter is selected when only it is available.
     *
     * @covers ::get_format
     */
    public function test_get_format_with_the_multilang_filter(): void {
        $this->resetAfterTest();
        $this->disable_all_filters();
        filter_set_global_state(languages::FILTER_MLANG, TEXTFILTER_ON);

        $this->assertSame('span', languages::get_format());
    }

    /**
     * Test that the syntax of the multi block filter is preferred when both filters are available.
     *
     * @covers ::get_format
     */
    public function test_get_format_prefers_the_multilang2_filter(): void {
        $this->resetAfterTest();
        $this->disable_all_filters();
        filter_set_global_state(languages::FILTER_MLANG, TEXTFILTER_ON);
        filter_set_global_state(languages::FILTER_MLANG2, TEXTFILTER_ON);

        $this->assertSame('mlang2', languages::get_format());
    }

    /**
     * Disable every text filter of the site and forget the enabled filters cache.
     *
     * The state of the filters is read from the database, but the list of the globally enabled
     * filters is kept in a request cache, which has to be dropped for the change to be visible.
     */
    protected function disable_all_filters(): void {
        foreach (array_keys(filter_get_global_states()) as $filter) {
            filter_set_global_state($filter, TEXTFILTER_DISABLED);
        }
        \cache_helper::reset_caches();
    }
}
