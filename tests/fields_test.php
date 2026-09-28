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
     * Test that a user with no exception set excludes nothing, which keeps the general rule.
     *
     * @covers ::get_excluded_field_names
     * @covers ::get_included_field_names
     */
    public function test_no_exception_by_default(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->assertSame([], fields::get_excluded_field_names());
        $this->assertSame([], fields::get_included_field_names());
    }

    /**
     * Test that the lists of a user are read from their preferences.
     *
     * @covers ::get_excluded_field_names
     * @covers ::get_included_field_names
     */
    public function test_user_exceptions(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        set_user_preference(fields::USER_EXCLUDED_FIELDS_PREFERENCE, 'idnumber, idnumber2', $user->id);
        set_user_preference(fields::USER_INCLUDED_FIELDS_PREFERENCE, 'notes', $user->id);

        $this->assertSame(['idnumber', 'idnumber2'], fields::get_excluded_field_names());
        $this->assertSame(['notes'], fields::get_included_field_names());
    }

    /**
     * Test that the exceptions of a user are their own, and do not leak to the other users.
     *
     * @covers ::get_excluded_field_names
     */
    public function test_user_exceptions_are_not_shared(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $first = $generator->create_user();
        $second = $generator->create_user();

        set_user_preference(fields::USER_EXCLUDED_FIELDS_PREFERENCE, 'idnumber', $first->id);

        $this->assertSame(['idnumber'], fields::get_excluded_field_names($first->id));
        $this->assertSame([], fields::get_excluded_field_names($second->id));
    }

    /**
     * Test that a field both included and excluded by a user is left out, an exclusion winning.
     *
     * @covers ::get_excluded_field_names
     * @covers ::get_included_field_names
     */
    public function test_exclusion_and_inclusion_are_both_returned(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        set_user_preference(fields::USER_INCLUDED_FIELDS_PREFERENCE, 'notes', $user->id);
        set_user_preference(fields::USER_EXCLUDED_FIELDS_PREFERENCE, 'notes', $user->id);

        // Both lists are reported as they are entered, the precedence between them being
        // applied by the AMD module which is the only place the fields are known.
        $this->assertSame(['notes'], fields::get_included_field_names());
        $this->assertSame(['notes'], fields::get_excluded_field_names());
    }

    /**
     * Test that the site wide exceptions and the ones of the current user are merged.
     *
     * The site wide lists are empty by default, so the merge of the two sources is checked
     * against a user preference only: that is the shape a site wide list has to keep, a list
     * of names entered in the same constants.
     *
     * @covers ::get_excluded_field_names
     * @covers ::get_included_field_names
     */
    public function test_site_and_user_exceptions_are_merged(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        set_user_preference(fields::USER_EXCLUDED_FIELDS_PREFERENCE, 'idnumber', $user->id);
        set_user_preference(fields::USER_INCLUDED_FIELDS_PREFERENCE, 'notes', $user->id);

        $this->assertSame(
            array_merge(fields::EXCLUDED_FIELDS, ['idnumber']),
            fields::get_excluded_field_names()
        );
        $this->assertSame(
            array_merge(fields::INCLUDED_FIELDS, ['notes']),
            fields::get_included_field_names()
        );
    }
}
