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

/**
 * Hook callbacks for the multi-language tabs plugin.
 *
 * The output hooks are only dispatched to the callbacks declared here, so this
 * file is what actually makes the plugin inject its AMD module in the pages and
 * its page in the user menu.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\output\before_footer_html_generation::class,
        'callback' => \local_multilangtabs\hook_callbacks::class . '::before_footer_html_generation',
    ],
    [
        'hook' => \core_user\hook\extend_user_menu::class,
        'callback' => \local_multilangtabs\hook_callbacks::class . '::extend_user_menu',
    ],
];
