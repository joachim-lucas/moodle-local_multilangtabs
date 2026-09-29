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
 * French language strings for the multi-language tabs plugin.
 *
 * @package    local_multilangtabs
 * @copyright  2026 Multi-language tabs contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['excludedfields'] = 'Champs à exclure';
$string['excludedfields_desc'] = 'Noms des éléments de formulaire qui ne doivent pas recevoir d’onglets,
    séparés par des virgules, par exemple « idnumber ». Laissez le champ vide pour n’exclure aucun champ
    ici. Cette liste s’ajoute à celle définie dans le code du plugin, classes/fields.php, qui
    s’applique toujours.';
$string['includedfields'] = 'Champs à inclure';
$string['includedfields_desc'] = 'Noms des éléments de formulaire qui doivent recevoir des onglets même
    s’ils ne sont ni des éditeurs de texte enrichi ni des champs texte simples, séparés par des virgules,
    par exemple « notes ». Laissez le champ vide pour n’inclure aucun champ ici. Cette liste s’ajoute à
    celle définie dans le code du plugin, classes/fields.php, qui s’applique toujours.';
$string['inplacetargets'] = 'Champs modifiables sur place';
$string['inplacetargets_desc'] = 'Une cible par ligne, sous la forme « composant-type », par exemple
    format_topics-sectionname. Laissez le champ vide pour désactiver les onglets sur les champs modifiables sur place.';
$string['languages'] = 'Langues disponibles';
$string['languages_desc'] = 'Cochez les langues à proposer sous forme d’onglets. Si aucune langue n’est cochée, tous les packs de langue installés sont proposés.';
$string['multilangfilter_content'] = 'Le filtre {$a->filter} ne s’applique qu’aux contenus, pas aux titres ni aux
    autres textes courts. Passez-le sur « Contenus et titres » dans la page {$a->filterspage}, sinon des titres comme
    le nom du cours ou celui des activités peuvent continuer d’afficher les marques multilingues brutes.';
$string['multilangfilter_off'] = 'Aucun filtre multilingue n’est actif sur ce site. Sans le filtre « multilang » ni
    le filtre « multilang2 », les marques multilingues ne sont pas interprétées et les onglets ne fonctionnent pas.
    Activez l’un d’eux dans la page {$a->filterspage}.';
$string['multilangfilter_ok'] = 'Tout est en ordre : un filtre multilingue est actif et s’applique aux contenus et
    aux titres, les onglets fonctionnent donc comme prévu.';
$string['pluginname'] = 'Onglets multilingues';
$string['privacy:metadata'] = 'Le plugin Onglets multilingues ne stocke aucune donnée personnelle.';
