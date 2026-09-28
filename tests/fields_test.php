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
     * Test that every plain text field is decorated as long as the setting is not filled in.
     *
     * @covers ::get_textfield_names
     */
    public function test_get_textfield_names_default(): void {
        $this->resetAfterTest();

        $this->assertSame([], fields::get_textfield_names());
    }

    /**
     * Test that the plain text fields to decorate are read from the setting.
     *
     * @covers ::get_textfield_names
     */
    public function test_get_textfield_names_from_settings(): void {
        $this->resetAfterTest();
        set_config('textfields', "name,\n  pagetitle ,\n", 'local_multilangtabs');

        $this->assertSame(['name', 'pagetitle'], fields::get_textfield_names());
    }
}
