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
 * Adds instance form
 *
 * @package    enrol
 * @subpackage cohort
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class duplicate_form extends moodleform {
    function definition() {
        global $CFG, $DB, $USER;

        $mform  = $this->_form;
        $customdata = $this->_customdata;

        $myCourses = enrol_get_my_courses('id, fullname');
        $courses = array();

        foreach($myCourses as $myCourse){
            $context = context_course::instance($myCourse->id);
            if($context !== FALSE){
                if(has_capability('moodle/course:manageactivities', $context)){
                    $courses[$myCourse->id] = $myCourse->fullname;
                }
            }
        }

        $mform->addElement('select', 'targetcourse', get_string('course'), $courses);
        $mform->setDefault('targetcourse', $customdata['course']);
        $mform->addRule('targetcourse', get_string('required'), 'required', null, 'client');

        $mform->addElement('hidden', 'cmid', $customdata['cmid']);
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'course', $customdata['course']);
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'sr', $customdata['sr']);
        $mform->setType('sr', PARAM_INT);

        $this->add_action_buttons(true);
    }
}
