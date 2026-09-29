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
 * Unit tests for the hook registration of the multi-language tabs plugin.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_multilangtabs\hook_callbacks
 */
final class hook_callbacks_test extends \advanced_testcase {
    /**
     * Test that the callback injecting the AMD module is registered for the output hook.
     *
     * Moodle only dispatches the output hooks to the callbacks declared in
     * db/hooks.php: without that file the callback class is silently ignored
     * and the plugin does nothing, without any error being displayed.
     */
    public function test_callback_is_registered_for_the_output_hook(): void {
        $callbacks = \core\hook\manager::get_instance()->get_callbacks_for_hook(
            \core\hook\output\before_footer_html_generation::class
        );

        $this->assertContains(
            hook_callbacks::class . '::before_footer_html_generation',
            array_column($callbacks, 'callback')
        );
    }
}
