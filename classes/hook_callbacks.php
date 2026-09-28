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

use core\hook\output\before_footer_html_generation;

/**
 * Hook callbacks for the multi-language tabs plugin.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Load the AMD module turning multilanguage fields into a set of language tabs.
     *
     * Nothing is loaded when no multilanguage filter is available on the site, or when no language
     * is available, as the generated markup would not be interpreted by anybody.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        $format = languages::get_format();
        $codes = languages::get_codes();
        if ($format === null || !$codes) {
            return;
        }

        $params = [
            'languages' => languages::get_tabs(),
            'defaultLang' => languages::get_default_code($codes),
            'format' => $format,
            'textFields' => fields::get_textfield_names(),
            'inplaceTargets' => fields::get_inplace_targets(),
        ];

        $hook->renderer->get_page()->requires->js_call_amd('local_multilangtabs/editor', 'init', [$params]);
    }
}
