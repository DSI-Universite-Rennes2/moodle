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

namespace core_question;

use question_bank;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/questiontypebase.php');


/**
 * Tests for some of ../questionbase.php
 *
 * @package    core_question
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(question_type::class)]
final class question_type_test extends \advanced_testcase {
    public function test_save_question_name(): void {
        $this->resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category(array());

        $saq = $questiongenerator->create_question('shortanswer', null,
                array('category' => $cat->id, 'name' => 'Test question'));
        $actual = question_bank::load_question_data($saq->id);

        $this->assertSame('Test question', $actual->name);
    }

    public function test_save_question_zero_name(): void {
        $this->resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category(array());

        $saq = $questiongenerator->create_question('shortanswer', null,
                array('category' => $cat->id, 'name' => '0'));
        $actual = question_bank::load_question_data($saq->id);

        $this->assertSame('0', $actual->name);
    }


    /**
     * Test fraction validation.
     */
    #[TestDox('@covers ::validate_fraction')]
    public function test_validate_fraction(): void {
        $this->resetAfterTest();

        // Setup environment.
        $matchgrades = 'error';
        $gradeoptionsfull = question_bank::fraction_options_full();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category([]);

        $question = $questiongenerator->create_question('shortanswer', null, ['category' => $category->id, 'name' => '0']);
        $qtype = question_bank::get_qtype($question->qtype);

        // Test empty fraction case.
        $question->fraction = null;
        $this->assertSame(null, $qtype::validate_fraction($question, $gradeoptionsfull, $matchgrades));

        // Test non-array fraction value.
        $question->fraction = 'invalid value';
        $this->assertSame(null, $qtype::validate_fraction($question, $gradeoptionsfull, $matchgrades));

        // Test invalid fraction value.
        try {
            $question->fraction = [1, 0.333, 0.123];
            $qtype::validate_fraction($question, $gradeoptionsfull, $matchgrades);

            $this->fail('0.333 and 0.123 fractions should throw an exception.');
        } catch (\Exception $exception) {
            $expectedmessage = get_string(
                'invalidgradequestion',
                'question',
                ['grades' => '0.333, 0.123', 'question' => $question->name]
            );
            $this->assertSame($expectedmessage, $exception->getMessage());
        }

        // Test normal case.
        $question->fraction = [1, 0.50, 0.3333333];
        $this->assertSame(['1.0', '0.5', '0.3333333'], $qtype::validate_fraction($question, $gradeoptionsfull, $matchgrades));
    }
}
