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
 * Unit tests for the resolution of the fields decorated with language tabs.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_multilangtabs\fields
 */
final class fields_test extends \advanced_testcase {
    /**
     * Test that a list is split on commas, spaces and line breaks.
     *
     * @covers ::parse_list
     * @dataProvider parse_list_provider
     * @param mixed $value Raw list.
     * @param string[] $expected Expected items.
     */
    public function test_parse_list($value, array $expected): void {
        $this->assertSame($expected, fields::parse_list($value));
    }

    /**
     * Data provider for test_parse_list.
     *
     * @return array[]
     */
    public static function parse_list_provider(): array {
        return [
            'empty string' => ['', []],
            'commas' => ['name,pagetitle', ['name', 'pagetitle']],
            'spaces and commas' => [' name , pagetitle ', ['name', 'pagetitle']],
            'line breaks' => ["name,\n  pagetitle ,\n", ['name', 'pagetitle']],
            'duplicates kept' => ['name,name', ['name', 'name']],
        ];
    }

    /**
     * Test that the default inplace targets are used as long as the setting is not saved.
     *
     * @covers ::get_inplace_targets
     */
    public function test_get_inplace_targets_default(): void {
        $this->resetAfterTest();

        $this->assertSame(fields::DEFAULT_INPLACE_TARGETS, fields::get_inplace_targets());
    }

    /**
     * Test that the inplace targets are read from the setting.
     *
     * @covers ::get_inplace_targets
     */
    public function test_get_inplace_targets_from_settings(): void {
        $this->resetAfterTest();
        set_config('inplacetargets', 'format_weeks-sectionname, mod_page-sectionname', 'local_multilangtabs');

        $this->assertSame(
            ['format_weeks-sectionname', 'mod_page-sectionname'],
            fields::get_inplace_targets()
        );
    }

    /**
     * Test that the inplace editable support can be disabled with an empty setting.
     *
     * @covers ::get_inplace_targets
     */
    public function test_get_inplace_targets_can_be_disabled(): void {
        $this->resetAfterTest();
        set_config('inplacetargets', '', 'local_multilangtabs');

        $this->assertSame([], fields::get_inplace_targets());
    }

    /**
     * Test that no exception is set by default, which keeps the general rule.
     *
     * The lists of the constants are empty, and the settings not saved: get_config() returns
     * false, which leaves the lists of the code untouched rather than replacing them.
     *
     * @covers ::get_excluded_field_names
     * @covers ::get_included_field_names
     */
    public function test_no_exception_by_default(): void {
        $this->resetAfterTest();

        $this->assertSame([], fields::get_excluded_field_names());
        $this->assertSame([], fields::get_included_field_names());
    }

    /**
     * Test that the exclusions are read from the setting.
     *
     * @covers ::get_excluded_field_names
     */
    public function test_exclusions_from_setting(): void {
        $this->resetAfterTest();
        set_config('excludedfields', 'idnumber, idnumber2', 'local_multilangtabs');

        $this->assertSame(['idnumber', 'idnumber2'], fields::get_excluded_field_names());
    }

    /**
     * Test that the inclusions are read from the setting.
     *
     * @covers ::get_included_field_names
     */
    public function test_inclusions_from_setting(): void {
        $this->resetAfterTest();
        set_config('includedfields', 'notes', 'local_multilangtabs');

        $this->assertSame(['notes'], fields::get_included_field_names());
    }

    /**
     * Test that a setting saved empty excludes nothing.
     *
     * @covers ::get_excluded_field_names
     */
    public function test_an_empty_setting_excludes_nothing(): void {
        $this->resetAfterTest();
        set_config('excludedfields', '', 'local_multilangtabs');

        $this->assertSame([], fields::get_excluded_field_names());
    }

    /**
     * Test that the names set in the settings are added to the ones set in the code.
     *
     * The lists set in the constants always apply, whatever the setting holds, so the merge
     * of the two sources keeps the coded names first even though they are empty here.
     *
     * @covers ::get_excluded_field_names
     * @covers ::get_included_field_names
     */
    public function test_the_setting_is_merged_with_the_code(): void {
        $this->resetAfterTest();
        set_config('excludedfields', 'idnumber, notes', 'local_multilangtabs');
        set_config('includedfields', 'notes', 'local_multilangtabs');

        $this->assertSame(
            array_merge(fields::EXCLUDED_FIELDS, ['idnumber', 'notes']),
            fields::get_excluded_field_names()
        );
        $this->assertSame(
            array_merge(fields::INCLUDED_FIELDS, ['notes']),
            fields::get_included_field_names()
        );
    }

    /**
     * Test that a field both included and excluded is left out, an exclusion winning.
     *
     * @covers ::get_excluded_field_names
     * @covers ::get_included_field_names
     */
    public function test_exclusion_and_inclusion_are_both_returned(): void {
        $this->resetAfterTest();
        set_config('includedfields', 'notes', 'local_multilangtabs');
        set_config('excludedfields', 'notes', 'local_multilangtabs');

        // Both lists are reported as they are entered, the precedence between them being
        // applied by the AMD module which is the only place the fields are known.
        $this->assertSame(['notes'], fields::get_included_field_names());
        $this->assertSame(['notes'], fields::get_excluded_field_names());
    }
}
