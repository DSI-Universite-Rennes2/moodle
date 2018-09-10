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
 * Tests core_locale class.
 *
 * @package   core
 * @copyright 2018 Université Rennes 2 {@link https://www.univ-rennes2.fr}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core;

use coding_exception;

defined('MOODLE_INTERNAL') || die();

// Include class to test.
require_once('lib/classes/locale.php');

// Create a global variable that contains next expected result.
$MOCK = array();

/**
 * Function to emulate native function setlocale().
 *
 * @param int $category
 * @param string $locale
 * @return string|false Returns the new current locale, or FALSE on failure.
 */
function setlocale($category = 0, $locale = '') {
    global $MOCK;

    return array_shift($MOCK);
}

/**
 * Tests core_date class.
 *
 * @package   core
 * @copyright 2018 Université Rennes 2 {@link https://www.univ-rennes2.fr}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_locale_testcase extends \advanced_testcase {
    /**
     * Test that moodle_check_locale_availability() works as expected.
     *
     */
    public function test_check_locale_availability() {
        global $MOCK;

        // Test what happen when locale is available on system.
        $MOCK = array(); // Emulate responses from PHP native functions called in core_locale::check_locale_availability().
        $MOCK[] = 'en'; // Return a value for the first setlocale() call, when we backup current locale.
        $MOCK[] = 'es'; // Return a value for the second setlocale() call, when we set new locale.
        $MOCK[] = 'en'; // Return a value for the third setlocale() call, when we restore initial locale.

        $result = core_locale::check_locale_availability('en');
        $this->assertTrue($result);

        // Test what happen when locale is not available on system.
        $MOCK = array(); // Emulate responses from PHP native functions called in core_locale::check_locale_availability().
        $MOCK[] = 'en'; // Return a value for the first setlocale() call, when we backup current locale.
        $MOCK[] = false; // Return a value for the second setlocale() call, when we set new locale.
        $MOCK[] = 'en'; // Return a value for the third setlocale() call, when we restore initial locale.

        $result = core_locale::check_locale_availability('en');
        $this->assertFalse($result);

        // Test some invalid parameters.
        foreach (array(1, true, '') as $invalidparameter) {
            try {
                $result = core_locale::check_locale_availability($invalidparameter);
                $this->fail('A coding_exception exception was expected');
            } catch (coding_exception $exception) {
                $this->assertInstanceOf('coding_exception', $exception);
            }
        }
    }
}
