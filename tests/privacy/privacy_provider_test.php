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

namespace local_multilangtabs\privacy;

/**
 * Unit tests for the privacy provider of the multi-language tabs plugin.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_multilangtabs\privacy\provider
 */
final class privacy_provider_test extends \advanced_testcase {
    /**
     * The plugin stores no personal data, so its provider only has to state so.
     */
    public function test_provider_is_a_null_provider(): void {
        $this->assertTrue(
            is_subclass_of(provider::class, \core_privacy\local\metadata\null_provider::class)
        );
    }

    /**
     * The reason must point to an existing language string.
     */
    public function test_get_reason_returns_a_language_string_id(): void {
        $reason = provider::get_reason();

        $this->assertSame('privacy:metadata', $reason);
        $this->assertNotEmpty(get_string($reason, 'local_multilangtabs'));
    }
}
