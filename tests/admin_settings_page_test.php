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
 * Tests for the settings page folding the advanced settings.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_multilangtabs\admin_settings_page
 */
final class admin_settings_page_test extends \advanced_testcase {
    /** @var string[] Names of the four settings the plugin folds away, as settings.php declares them. */
    const FOLDED = [
        'includedfields',
        'excludedfields',
        'inplaceincluded',
        'inplaceexcluded',
    ];

    /**
     * Build the real administration tree and return the settings page of the plugin.
     *
     * The tree is built the way the site does it, so the test also proves that settings.php still
     * registers the page under the category the site expects.
     *
     * @return \local_multilangtabs\admin_settings_page The settings page.
     */
    protected function get_plugin_page(): \local_multilangtabs\admin_settings_page {
        $this->resetAfterTest();
        $this->setAdminUser();

        $page = admin_get_root(true)->locate('local_multilangtabs');
        $this->assertInstanceOf(\local_multilangtabs\admin_settings_page::class, $page);

        return $page;
    }

    /**
     * The form name of a setting, which is what the form actually posts.
     *
     * @param string $name Name of a setting of the plugin.
     * @return string The name of its form field.
     */
    protected function formname(string $name): string {
        return 's_local_multilangtabs_' . $name;
    }

    /**
     * The four advanced settings are folded into a closed disclosure.
     */
    public function test_advanced_settings_are_folded(): void {
        $html = $this->get_plugin_page()->output_html();

        $this->assertStringContainsString('<details class="mlt-fold">', $html);
        // Left closed on purpose: the markup must not carry an open attribute.
        $this->assertDoesNotMatchRegularExpression('/<details[^>]*\bopen\b/', $html);
        $this->assertStringContainsString('<summary>', $html);
    }

    /**
     * The disclosure is a single element holding the four lists, the others stay visible.
     */
    public function test_only_the_advanced_settings_are_folded(): void {
        $html = $this->get_plugin_page()->output_html();

        $this->assertSame(1, preg_match_all('/<details/', $html));
        $this->assertSame(1, preg_match_all('#</details>#', $html));
        $this->assertSame(1, preg_match_all('#</fieldset>#', $html));

        $start = strpos($html, '<details');
        $end = strpos($html, '</details>');
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $folded = substr($html, $start, $end - $start);

        foreach (self::FOLDED as $name) {
            $this->assertStringContainsString(
                $this->formname($name),
                $folded,
                "{$name} should be inside the disclosure"
            );
        }

        // The languages and the filter status are the everyday settings, they stay visible.
        $this->assertStringNotContainsString(
            $this->formname('languages'),
            $folded,
            'the languages setting should stay outside the disclosure'
        );
        $this->assertStringNotContainsString(
            'mlt-filterstatus',
            $folded,
            'the filter status should stay outside the disclosure'
        );
    }

    /**
     * The disclosure tells the administrator what is inside and why to leave it alone.
     */
    public function test_fold_carries_its_explanation(): void {
        $html = $this->get_plugin_page()->output_html();

        $this->assertStringContainsString('mlt-folddesc', $html);
        $this->assertStringContainsString(
            get_string('advancedsettings', 'local_multilangtabs'),
            $html
        );
    }

    /**
     * The folded settings keep their input in the form, so saving the page cannot drop them.
     *
     * This is the behaviour the details element is chosen for: the fields stay submitted even
     * while the disclosure is closed, so a "Save changes" click meant for another setting cannot
     * wipe the four lists.
     */
    public function test_folded_settings_are_still_submitted(): void {
        set_config('inplaceexcluded', 'core_tag-tagname', 'local_multilangtabs');
        set_config('inplaceincluded', 'mod_forum-digestoptions', 'local_multilangtabs');

        $html = $this->get_plugin_page()->output_html();

        foreach (self::FOLDED as $name) {
            $this->assertStringContainsString($this->formname($name), $html);
        }

        // The saved value is what the field carries, hidden or not.
        $this->assertStringContainsString('core_tag-tagname', $html);
        $this->assertStringContainsString('mod_forum-digestoptions', $html);
    }

    /**
     * Saving the form keeps the folded settings untouched.
     */
    public function test_writing_the_form_preserves_the_folded_settings(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('inplaceexcluded', 'core_tag-tagname', 'local_multilangtabs');
        set_config('inplaceincluded', 'mod_forum-digestoptions', 'local_multilangtabs');
        set_config('languages', 'en', 'local_multilangtabs');

        // admin_get_root() is what admin_write_settings() looks the settings up in.
        admin_get_root(true);

        // Exactly what the form posts: only the languages were touched by the administrator, the
        // four textareas of the closed disclosure still carry their value.
        $formdata = [
            $this->formname('languages') => ['en' => 1],
            $this->formname('includedfields') => '',
            $this->formname('excludedfields') => '',
            $this->formname('inplaceincluded') => 'mod_forum-digestoptions',
            $this->formname('inplaceexcluded') => 'core_tag-tagname',
        ];

        $this->assertNotEmpty(admin_write_settings($formdata));

        $this->assertSame('en', get_config('local_multilangtabs', 'languages'));
        $this->assertSame('core_tag-tagname', get_config('local_multilangtabs', 'inplaceexcluded'));
        $this->assertSame('mod_forum-digestoptions', get_config('local_multilangtabs', 'inplaceincluded'));
    }

    /**
     * A page without any folded setting renders exactly like the core one.
     */
    public function test_page_without_folded_settings(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $adminroot = new \admin_root(true);
        $adminroot->add('root', new \admin_category('root', 'Root'));
        $GLOBALS['ADMIN'] = $adminroot;

        $page = new \local_multilangtabs\admin_settings_page('testpage', 'Test page');
        $page->add(new \admin_setting_configtext(
            'local_multilangtabs/includedfields',
            'Included',
            'Included fields',
            ''
        ));
        $adminroot->add('root', $page);

        $html = $page->output_html();

        $this->assertStringNotContainsString('<details', $html);
        $this->assertStringContainsString($this->formname('includedfields'), $html);
    }

    /**
     * A folded run followed by another setting is closed before that setting, not after it.
     */
    public function test_a_setting_after_the_run_stays_outside(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $adminroot = new \admin_root(true);
        $adminroot->add('root', new \admin_category('root', 'Root'));
        $GLOBALS['ADMIN'] = $adminroot;

        $page = new \local_multilangtabs\admin_settings_page(
            'testpage',
            'Test page',
            ['testplugin/foldedone', 'testplugin/foldedtwo'],
            'Folded'
        );
        $page->add(new \admin_setting_configtext('testplugin/foldedone', 'One', '', ''));
        $page->add(new \admin_setting_configtext('testplugin/foldedtwo', 'Two', '', ''));
        $page->add(new \admin_setting_configtext('testplugin/after', 'After', '', ''));
        $adminroot->add('root', $page);

        $html = $page->output_html();

        $this->assertSame(1, preg_match_all('/<details/', $html));
        $this->assertSame(1, preg_match_all('#</details>#', $html));

        $end = strpos($html, '</details>');
        $this->assertNotFalse($end);
        $this->assertStringContainsString('s_testplugin_foldedone', substr($html, 0, $end));
        $this->assertStringContainsString('s_testplugin_foldedtwo', substr($html, 0, $end));
        $this->assertStringNotContainsString('s_testplugin_after', substr($html, 0, $end));
        $this->assertStringContainsString('s_testplugin_after', $html);
    }

    /**
     * A name which is not on the page folds nothing instead of breaking the page.
     */
    public function test_an_unknown_folded_name_is_ignored(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $adminroot = new \admin_root(true);
        $adminroot->add('root', new \admin_category('root', 'Root'));
        $GLOBALS['ADMIN'] = $adminroot;

        $page = new \local_multilangtabs\admin_settings_page(
            'testpage',
            'Test page',
            ['testplugin/nowhere']
        );
        $page->add(new \admin_setting_configtext('testplugin/one', 'One', '', ''));
        $adminroot->add('root', $page);

        $html = $page->output_html();

        $this->assertStringNotContainsString('<details', $html);
        $this->assertStringContainsString('s_testplugin_one', $html);
    }

    /**
     * The names declared in settings.php are turned into the form names of the core.
     */
    public function test_fullname_translates_the_setting_names(): void {
        $fullname = [\local_multilangtabs\admin_settings_page::class, 'fullname'];

        $this->assertSame('s_local_multilangtabs_includedfields', call_user_func($fullname, 'local_multilangtabs/includedfields'));
        $this->assertSame('s_mysetting', call_user_func($fullname, 'mysetting'));
    }
}
