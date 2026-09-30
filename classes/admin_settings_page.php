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
 * Settings page folding a run of settings into a closed disclosure.
 *
 * Moodle has no collapsible group in its administration settings, and the "advanced" flag of
 * \admin_setting is a checkbox repeated on every single setting, which adds more clutter than it
 * removes. Folding a run of settings is therefore done here, on the page, where a single
 * disclosure can hide them all at once.
 *
 * The folding is done on the rendered HTML only. The folded settings keep their input in the
 * settings form, so saving the page never drops their value: a "Save changes" click meant for the
 * language selection alone cannot wipe the lists. That is the whole point of using a details
 * element rather than hiding the inputs from the form.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_settings_page extends \admin_settingpage {
    /** @var string[] Form names of the settings to fold, in the order they must appear. */
    protected array $folded = [];

    /** @var string Label of the disclosure. */
    protected string $foldsummary = '';

    /** @var string Text shown inside the disclosure, above the folded settings. */
    protected string $folddescription = '';

    /**
     * Build the settings page.
     *
     * @param string $name Name of the page.
     * @param string $visiblename Name shown in the navigation.
     * @param string[] $folded Names of the settings to fold, written exactly as they are passed to
     *      the setting constructors, for instance 'local_multilangtabs/includedfields'. They must be
     *      a contiguous run of the settings of the page, in the order they were added.
     * @param string|null $foldsummary Label of the disclosure, the page name if null.
     * @param string|null $folddescription Text shown inside the disclosure, empty if null.
     */
    public function __construct(
        string $name,
        string $visiblename,
        array $folded = [],
        ?string $foldsummary = null,
        ?string $folddescription = null
    ) {
        parent::__construct($name, $visiblename);
        $this->folded = array_map([self::class, 'fullname'], $folded);
        $this->foldsummary = $foldsummary ?? $visiblename;
        $this->folddescription = $folddescription ?? '';
    }

    /**
     * Convert a setting name into the form name the core gives it.
     *
     * \admin_setting::get_full_name() returns 's_' . plugin . '_' . name, and it is the only
     * public way to identify a setting of the page, so the names declared in settings.php are
     * translated here rather than written twice in another shape.
     *
     * @param string $name Name of a setting, 'plugin/name' or 'name'.
     * @return string The form name of the setting.
     */
    public static function fullname(string $name): string {
        $bits = explode('/', $name);
        if (count($bits) === 1) {
            return 's_' . $bits[0];
        }

        return 's_' . $bits[0] . '_' . $bits[1];
    }

    /**
     * Render the settings of the page, folding the requested run into a disclosure.
     *
     * The loop mirrors \admin_settingpage::output_html() of the core, which cannot be reused as it
     * already concatenates every setting into one string: the disclosure has to be inserted between
     * two settings, which is only possible while looping over them. If the core changes the way it
     * resolves the value of a setting, this loop has to follow the same change.
     *
     * @return string The HTML of the settings.
     */
    public function output_html(): string {
        $adminroot = admin_get_root();
        $folded = array_flip($this->folded);

        $return = '<fieldset>' . "\n" . '<div class="clearer"><!-- --></div>' . "\n";
        $isopen = false;
        $foldedalready = false;
        foreach ($this->settings as $setting) {
            $fullname = $setting->get_full_name();
            if (array_key_exists($fullname, $adminroot->errors)) {
                $data = $adminroot->errors[$fullname]->data;
            } else {
                $data = $setting->get_setting();
                // Do not use defaults if settings not available - upgrade settings handles the defaults!
            }
            // The disclosure opens on the first folded setting, and closes on the setting which
            // follows the run, so that the settings added after it stay outside. The two flags
            // together keep the run in a single disclosure.
            if (!$isopen && !$foldedalready && array_key_exists($fullname, $folded)) {
                $return .= $this->output_fold_start();
                $isopen = true;
            }
            if ($isopen && !array_key_exists($fullname, $folded)) {
                $return .= $this->output_fold_end();
                $isopen = false;
                $foldedalready = true;
            }
            $return .= $setting->output_html($data);
        }
        if ($isopen) {
            $return .= $this->output_fold_end();
        }
        $return .= '</fieldset>';

        return $return;
    }

    /**
     * Render the opening of the disclosure.
     *
     * @return string The HTML.
     */
    protected function output_fold_start(): string {
        $html = '<details class="mlt-fold">';
        $html .= \html_writer::tag('summary', $this->foldsummary);
        if ($this->folddescription !== '') {
            $html .= \html_writer::div(markdown_to_html($this->folddescription), 'mlt-folddesc');
        }

        return $html;
    }

    /**
     * Render the closing of the disclosure.
     *
     * @return string The HTML.
     */
    protected function output_fold_end(): string {
        return '</details>';
    }
}
