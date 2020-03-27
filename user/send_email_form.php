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
 * A form for sending emails.
 *
 * @copyright 2020 Université Rennes 2 {@link https://www.univ-rennes2.fr}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   core_user
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Send email form class
 *
 * @copyright 2020 Université Rennes 2 {@link https://www.univ-rennes2.fr}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   core_user
 */
class send_email_form extends moodleform {

    /**
     * Definition of the form
     */
    public function definition () {
        $mform =& $this->_form;

        // Subject field.
        $mform->addElement('text', 'subject', get_string('subject', 'message'));
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');
        $mform->setType('subject', PARAM_TEXT);

        // Carbon copy field.
        $mform->addElement('checkbox', 'carboncopy', get_string('carboncopy', 'message'));
        $mform->addHelpButton('carboncopy', 'carboncopy', 'message');

        // Message field.
        if (isset($this->_customdata['editoroptions'])) {
            $editoroptions = $this->_customdata['editoroptions'];
            $mform->addElement('editor', 'message', get_string('message', 'message'), null, $editoroptions);
        } else {
            $mform->addElement('editor', 'message', get_string('message', 'message'));
        }
        $mform->addRule('message', '', 'required', null, 'server');
        $mform->setType('message', PARAM_RAW);

        // Submit buttons.
        $this->add_action_buttons($cancel = true, $submitlabel = get_string('send', 'message'));
    }
}
