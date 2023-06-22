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

namespace qformat_gift;

use advanced_testcase;
use context_course;
use core_question\local\bank\question_edit_contexts;
use qformat_gift;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/format/gift/format.php');

/**
 * Unit tests for export/import GIFT question format.
 *
 * @copyright  2023 Université Rennes 2 {@link https://www.univ-rennes2.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qformat_gift_import_export_test extends advanced_testcase {
    /**
     * Create object qformat_gift for test.
     *
     * @param string $filename with name for testing file.
     * @param stdClass $course
     *
     * @return qformat_gift GIFT question format object.
     */
    public function create_qformat($filename, $course) {
        $qformat = new qformat_gift();
        $qformat->setContexts((new question_edit_contexts(context_course::instance($course->id)))->all());
        $qformat->setCourse($course);
        $qformat->setFilename(__DIR__ . '/fixtures/' . $filename);
        $qformat->setRealfilename($filename);
        $qformat->setMatchgrades('error');
        $qformat->setCatfromfile(1);
        $qformat->setContextfromfile(1);
        $qformat->setStoponerror(1);
        $qformat->setCattofile(1);
        $qformat->setContexttofile(1);
        $qformat->set_display_progress(false);

        return $qformat;
    }

    /**
     * Check exception when importing questions with invalid fraction sum.
     *
     * @covers \qformat_default::importprocess
     */
    public function test_import_invalid_fraction_sum() {
        global $OUTPUT;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();
        $qformat = $this->create_qformat('error_invalid_fraction_sum.gift.txt', $course);

        ob_start();
        $imported = $qformat->importprocess();
        $output = ob_get_clean();

        // Expected message for question 1.
        $expectedoutput = $OUTPUT->notification(get_string('errfractionsaddwrong', 'qtype_multichoice', 200));

        // Expected message for question 2.
        $expectedoutput .= $OUTPUT->notification(get_string('errfractionsaddwrong', 'qtype_multichoice', 60));

        $this->assertFalse($imported);
        $this->assertEquals($expectedoutput, $output);
    }
}
