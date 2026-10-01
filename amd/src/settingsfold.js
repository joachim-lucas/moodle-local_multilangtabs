// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AMD module folding the advanced settings of the plugin settings page into a disclosure.
 *
 * The four lists at the bottom of the page are only useful in an unusual case, and Moodle
 * renders them as four plain setting rows. This module moves those rows inside a closed
 * details element, so the administrator sees that there is nothing to fill in unless they
 * need it. The disclosure is deliberately left closed: the point is to say that the expected
 * configuration is the empty one.
 *
 * The strings are fetched with core/str rather than inlined, so they go through the string
 * API and stay translatable.
 *
 * @module     local_multilangtabs/settingsfold
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/str'], function(str) {

    // Ids of the four setting rows the disclosure wraps. core/admin renders an
    // admin_setting_configtextarea as a row carrying the 'admin-<settingname>' id.
    const ROW_IDS = [
        'admin-includedfields',
        'admin-excludedfields',
        'admin-inplaceincluded',
        'admin-inplaceexcluded'
    ];

    // Class of the disclosure, styled in styles.css. Doubles as the marker telling init()
    // that the page has already been folded.
    const FOLD_CLASS = 'mlt-fold';

    /**
     * Return the setting rows the disclosure has to wrap, in page order.
     *
     * Rows which are not on the page are dropped, rather than being reported as missing: the
     * settings page is built by core, and an admin theme may render the rows differently.
     *
     * @returns {Element[]} The rows found on the page.
     */
    function findRows() {
        return ROW_IDS
            .map(function(id) {
                return document.getElementById(id);
            })
            .filter(function(row) {
                return row !== null;
            });
    }

    /**
     * Move the advanced setting rows inside a closed disclosure.
     *
     * The rows stay in the form they were rendered in, only their parent element changes, so
     * the form still submits their values normally.
     *
     * @returns {Promise} Resolved once the disclosure is in the page.
     */
    function fold() {
        const rows = findRows();

        // Nothing to fold: either the page does not hold the advanced settings, or it has
        // already been folded.
        if (!rows.length || document.querySelector('.' + FOLD_CLASS)) {
            return Promise.resolve();
        }

        return Promise.all([
            str.get_string('advancedsettings', 'local_multilangtabs'),
            str.get_string('advancedsettings_desc', 'local_multilangtabs')
        ]).then(function(strings) {
            const details = document.createElement('details');
            details.className = FOLD_CLASS;

            const summary = document.createElement('summary');
            summary.textContent = strings[0];
            details.appendChild(summary);

            const description = document.createElement('div');
            description.className = 'mlt-folddesc';
            description.textContent = strings[1];
            details.appendChild(description);

            // The disclosure takes the place of the first row, which then moves inside it.
            rows[0].parentNode.insertBefore(details, rows[0]);
            rows.forEach(function(row) {
                details.appendChild(row);
            });

            return details;
        });
    }

    return {
        init: function() {
            // An AMD module is loaded at the end of the body, so the setting rows are already
            // in the page by the time init() runs. The strings still arrive asynchronously,
            // the disclosure is built once they are there.
            return fold();
        }
    };
});
