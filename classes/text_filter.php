<?php

namespace filter_multilangtabs;

defined('MOODLE_INTERNAL') || die();

class text_filter extends \core_filters\text_filter {

    /**
     * Filter the text before Moodle cleans the HTML.
     *
     * @param string $text
     * @param array $options
     * @return string
     */
    public function filter_stage_pre_clean(
        string $text,
        array $options
    ): string {

        /*
         * Do nothing if this content does not use Multilang Tabs.
         */
        if (strpos($text, 'data-mltabs-lang') === false) {
            return $text;
        }

        $currentlanguage = current_language();

        /*
         * Match language blocks.
         *
         * Example:
         *
         * <div data-mltabs-lang="fr">
         *     <p>Bonjour</p>
         * </div>
         *
         * <div data-mltabs-lang="en">
         *     <p>Hello</p>
         * </div>
         */
        $pattern = '/
            <div
                \s+
                [^>]*?
                data-mltabs-lang
                \s*=\s*
                ["\']' . preg_quote($currentlanguage, '/') . '["\']
                [^>]*?
            >
                (.*?)
            <\/div>
        /isx';

        if (preg_match($pattern, $text, $matches)) {
            return $matches[1];
        }

        /*
         * No content exists for the current language.
         *
         * Return an empty string.
         */
        return '';
    }
}