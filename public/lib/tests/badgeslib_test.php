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

namespace core;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/badgeslib.php');

/**
 * Unit tests for /lib/badgeslib.php.
 *
 * @package   core
 * @category  test
 * @copyright 2026 Université Rennes 2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class badgeslib_test extends \advanced_testcase {
    /**
     * Tests the function badges_external_delete_mapping().
     *
     * @covers \badges_external_delete_mapping()
     */
    public function test_badges_external_delete_mapping(): void {
        global $DB;

        $this->resetAfterTest();

        // Add a external mapping information for a backpack.
        $record = ['sitebackpackid' => 1, 'internalid' => 2, 'externalid' => 3, 'type' => 4];
        $DB->insert_record('badge_external_identifier', $record);

        // Confirm external mapping information was created.
        $this->assertEquals(1, $DB->count_records('badge_external_identifier'));

        badges_external_delete_mapping($record['sitebackpackid'], $record['type'], $record['internalid']);

        // Confirm external mapping information was deleted.
        $this->assertEquals(0, $DB->count_records('badge_external_identifier'));
    }
}
