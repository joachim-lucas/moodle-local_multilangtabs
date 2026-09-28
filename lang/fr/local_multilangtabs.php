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

$string['inplacetargets'] = 'Champs modifiables sur place';
$string['inplacetargets_desc'] = 'Une cible par ligne, sous la forme « composant-type », par exemple
    format_topics-sectionname. Laissez le champ vide pour désactiver les onglets sur les champs modifiables sur place.';
$string['languages'] = 'Langues disponibles';
$string['languages_desc'] = 'Cochez les langues Ã  proposer sous forme dâ€™onglets. Si aucune langue nâ€™est cochÃ©e, tous les packs de langue installÃ©s sont proposÃ©s.';
$string['pluginname'] = 'Onglets multilingues';
$string['userexceptions_invalid'] = '{$a} n’est pas un nom de champ valide. Utilisez les noms des éléments de formulaire, comme name ou pagetitle.';
$string['userexcludedfields'] = 'Champs à exclure';
$string['userexcludedfields_help'] = 'Noms des éléments de formulaire sur lesquels vous ne voulez pas les onglets,
    séparés par des virgules, par exemple « idnumber, idnumber2 ». Laissez le champ vide pour n’exclure
    aucun champ. Une exclusion l’emporte toujours, quelles que soient les listes d’inclusion.';
$string['userincludedfields'] = 'Champs à inclure';
$string['userincludedfields_help'] = 'Noms des éléments de formulaire sur lesquels vous voulez les onglets même
    s’ils ne sont ni des éditeurs de texte enrichi ni des champs texte simples, séparés par des virgules,
    par exemple « notes ». Laissez le champ vide pour n’inclure aucun champ au-delà des champs par défaut.';
$string['userprefs'] = 'Onglets multilingues';
$string['userprefs_intro'] = 'Tous les éditeurs de texte enrichi et tous les champs texte simples des pages que vous
    modifiez sont équipés d’onglets. Les deux listes ci-dessous servent aux champs qui doivent échapper à
    cette règle : les pages de votre propre environnement, un formulaire d’un plugin utilisé pour un usage
    précis, ou simplement un formulaire parmi tous les autres. Les listes définies sur le site, par
    l’administrateur, ne sont pas rappelées ici et l’emportent toujours sur les vôtres.';
$string['userprefs_saved'] = 'Vos exceptions ont été enregistrées.';
