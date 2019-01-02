<?php
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
 * Utility lang import functions.
 *
 * @package    tool
 * @subpackage langimport
 * @copyright  2011 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_langimport;

use coding_exception;

defined('MOODLE_INTERNAL') || die;

/**
 * Called during upgrade, we need up-to-date lang pack
 * because it may be used during upgrade...
 *
 * @param string $lang
 * @return void
 */
function tool_langimport_preupgrade_update($lang) {
    global $CFG, $OUTPUT;
    require_once($CFG->libdir.'/componentlib.class.php');

    echo $OUTPUT->heading(get_string('langimport', 'tool_langimport').': '.$lang);

    @mkdir ($CFG->tempdir.'/');    //make it in case it's a fresh install, it might not be there
    @mkdir ($CFG->dataroot.'/lang/');

    $installer = new lang_installer($lang);
    $results = $installer->run();
    foreach ($results as $langcode => $langstatus) {
        switch ($langstatus) {
        case lang_installer::RESULT_DOWNLOADERROR:
            echo $OUTPUT->notification($langcode . '.zip');
            break;
        case lang_installer::RESULT_INSTALLED:
            echo $OUTPUT->notification(get_string('langpackinstalled', 'tool_langimport', $langcode), 'notifysuccess');
            break;
        case lang_installer::RESULT_UPTODATE:
            echo $OUTPUT->notification(get_string('langpackuptodate', 'tool_langimport', $langcode), 'notifysuccess');
            break;
        }
    }
}

/**
 * Helper class for the language import tool.
 *
 * @since      Moodle 3.6
 * @package    tool_langimport
 * @copyright  2018 Université Rennes 2 {@link https://www.univ-rennes2.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Checks availability of locale on current operating system.
     *
     * @param string $langpackcode (e.g.: en, es, fr, de)
     * @return bool true if the locale is available on OS.
     */
    public static function check_locale_availability($langpackcode) {
        global $CFG;

        if (is_string($langpackcode) === false || empty($langpackcode) === true) {
            throw new coding_exception('Invalid language pack code in \\'.__METHOD__.'() call, only non-empty string is allowed');
        }

        // Fetch the correct locale based on ostype.
        if ($CFG->ostype === 'WINDOWS') {
            $stringtofetch = 'localewin';
        } else {
            $stringtofetch = 'locale';
        }

        // Store current locale.
        $currentlocale = setlocale(LC_ALL, 0);
        $locale = get_string_manager()->get_string($stringtofetch, 'langconfig', $a = null, $langpackcode);

        // Try to set new locale.
        $return = setlocale(LC_ALL, $locale);

        // Restore current locale.
        setlocale(LC_ALL, $currentlocale);

        // If $return is not equal to false, it means that setlocale() succeed to change locale.
        return $return !== false;
    }
}
