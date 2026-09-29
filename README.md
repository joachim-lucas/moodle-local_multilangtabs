# Multi-language tabs (local_multilangtabs)

[![Moodle Plugin CI](https://github.com/joachim-lucas/moodle-local_multilangtabs/actions/workflows/moodle-ci.yml/badge.svg?branch=develop)](https://github.com/joachim-lucas/moodle-local_multilangtabs/actions/workflows/moodle-ci.yml)

A Moodle local plugin that turns any multilanguage field into a set of language tabs, so that
course sections, activities and page titles can be written once per language from a single form.

## Description

Multilingual content in Moodle is normally written either with a multilanguage filter
(`{mlang en}...{mlang}` or `<span lang="en" class="multilang">...`) or with the language
configuration of the activity itself. Both are impractical as soon as a single course page has to
carry several titles: the administrator has to edit the same field over and over again, and the
multilanguage syntax is far from obvious in a plain textarea.

This plugin adds a row of language tabs above the supported fields. One tab per configured
language, the content of the active tab only, and the complete multilingual value is rebuilt and
written back to the field when the form is submitted. Existing content is read in both multilanguage
syntaxes, so nothing has to be migrated.

Supported fields:

* single line and textarea form fields (course section names, page titles, activity names, ...),
* TinyMCE and plain text editors (course introductions, page content, ...),
* core inplace editable fields (section and activity names in the course topics/weeks format).

## Requirements

* Moodle 5.0 or later.
* A multilanguage filter must be installed and enabled on the site, otherwise the plugin stays
  inactive by design and loads no JavaScript at all:
  * [multilang](https://moodle.org/plugins/?q=multilang) (filter_multilang), or
  * [tiny_multilang2](https://github.com/Communautonon/tiny_multilang2) (filter_multilang2), which
    is recommended because it copes with several blocks per language.

The markup produced by the plugin is adapted to the filter which is available on the site:
`filter_multilang2` receives `{mlang xx}...{mlang}` markup, `filter_multilang` receives
`<span lang="xx" class="multilang">` markup.

## Installation

* Download the latest release, or clone this repository.
* Copy the `multilangtabs` directory into the `local` directory of your Moodle installation, so that
  the path ends with `local/multilangtabs`.
* Log in as an administrator and visit *Site administration > Plugins > Admin tools > Overview* to
  install the plugin.

Alternatively, use [Moodle CLI](https://moodle.org/):

```bash
php admin/cli/upgrade.php
```

> [!IMPORTANT]
> The tabs are injected by an output hook, and Moodle only knows about hook callbacks which are
> declared in `db/hooks.php`. The list of those callbacks is cached, so **purge the caches** after
> copying or updating the files, otherwise the plugin silently loads nothing: no tab, no error in the
> browser console. The version bump of the plugin prompts the upgrade, which purges the caches for
> you.

## Configuration

Go to *Site administration > Plugins > Local plugins > Multi-language tabs* and tick the languages
to propose as tabs. When nothing is ticked, every installed language pack is proposed. Tabs open on
the current user language, or on the first ticked language when the user language is not part of the
selection. The page also holds the *Fields to include* / *Fields to exclude* lists, described below,
and the *In-place editable fields* list.

### Fields decorated with tabs

Moodle keeps no registry of the fields displaying their value through a multilanguage filter, and the
plugin builds no form itself: the fields are found on the rendered page, from the `data-fieldtype`
attribute Moodle puts on every form element, which is the contract the core itself relies on to tell
the types of fields apart.

The general rule is that **every rich text editor and every plain text field of the page** is
decorated, whatever the form it belongs to. The fields which have to escape that rule are the
exceptions, and there are two levels of them.

The first level is set in the code rather than in the settings, in `classes/fields.php`, for the
exceptions which are not a matter of administration but of development:

```php
// Never decorated, whatever the general rule and the inclusion lists say. The course
// identification number is the kind of field which has to be left alone site wide.
public const EXCLUDED_FIELDS = ['idnumber'];

// Decorated even though the general rule does not cover them, which is the list to use for the
// form elements of another type, a plain textarea for instance.
public const INCLUDED_FIELDS = ['notes'];
```

The second level is the two textareas at the bottom of the plugin settings, *Fields to include* and
*Fields to exclude*, with one name per line or separated by commas, `idnumber`, `notes`. What is set
there is **added to the lists of the code, which always apply**: the settings cannot lift an
exclusion written in `classes/fields.php`.

A field is decorated when the general rule covers it, or when an inclusion list names it, and when no
exclusion list names it: **an exclusion always wins**, whatever the level it comes from.

The names are those of the form elements, the ones the browser shows in the `name` attribute of the
field, a composite value being named after its element, `intro` for the `intro[text]` editor.

### In-place editable fields

The fields edited in place, without opening a form, are set from the plugin settings, as
`component-itemtype` targets, one per line, for instance `format_topics-sectionname`. The setting
comes with the section and activity names of the `format_topics` and `format_weeks` course formats.
Clearing it and saving the settings disables that support altogether.

## Known limitations

* The language tabs of core inplace editable fields cover the section and activity names of the
  `format_topics` and `format_weeks` course formats by default. Other inplace editable fields are
  ignored unless they are added to the setting above.
* The filter is detected on the site as a whole, not per context: enabling it for a single course
  only is not enough for the tabs to appear.
* Only rich text editors, plain text fields and the plain textareas named in an inclusion list are
  decorated. The other types of form elements, selects for instance, are left alone.

## Uninstallation

* Go to *Site administration > Plugins > Local plugins > Multi-language tabs* and click
  *Remove plugins*.
* Run `php admin/cli/upgrade.php` if the plugin is not uninstalled automatically.

## Repository

* [Source code and issue tracker](https://github.com/joachim-lucas/moodle-local_multilangtabs)
* Continuous integration: [Moodle Plugin CI](https://moodlehq.github.io/moodle-plugin-ci/)

## Development

This plugin follows the Moodle coding guidelines. Run the standard checks with a checkout of
[moodle-plugin-ci](https://moodlehq.github.io/moodle-plugin-ci/) or with the GitHub Actions workflow
provided in `.github/workflows/moodle-ci.yml`.

To rebuild the AMD JavaScript modules, from a Moodle checkout:

```sh
npm install
npx grunt amd --root=local/multilangtabs
```

The generated files `amd/build/editor.min.js` and its sourcemap `amd/build/editor.min.js.map` are
committed: Moodle does not build them at runtime, so they have to stay in sync with `amd/src/editor.js`.

Unit tests live in `tests/` and cover the language resolution and the fields decorated. Run them with:

```sh
vendor/bin/phpunit --testsuite local_multilangtabs_testsuite
```

## License

GNU General Public License version 3 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-3.0.html).
