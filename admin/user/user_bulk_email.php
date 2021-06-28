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
 * Page for bulk user mailing.
 *
 * @copyright 2020 Université Rennes 2 {@link https://www.univ-rennes2.fr}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   core_user
 */

require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/send_email_form.php');

$subject    = optional_param('subject', '', PARAM_RAW);
$carboncopy = optional_param('carboncopy', false, PARAM_BOOL);
$text       = optional_param('text', '', PARAM_RAW);
$confirm    = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('userbulk');
require_capability('moodle/site:manageallmessaging', context_system::instance());

$return = $CFG->wwwroot.'/'.$CFG->admin.'/user/user_bulk.php';

if (empty($SESSION->bulk_users)) {
    redirect($return);
}

if ($confirm and !empty($subject) and !empty($text) and confirm_sesskey()) {
    require_once($CFG->dirroot . '/message/externallib.php');

    $message = array();
    $message['subject'] = $subject;
    $message['carboncopy'] = $carboncopy;
    $message['text'] = $text;
    $message['receivers'] = array_values($SESSION->bulk_users);
    $messages = array($message);

    $result = core_message_external::send_instant_emails($messages);

    $countmessages = count($result);
    if ($countmessages < 2) {
        $message = get_string('sendbulkemailsentsingle', 'message');
    } else {
        $message = get_string('sendbulkemailsent', 'message', $countmessages);
    }

    redirect($return, $message, 0, \core\output\notification::NOTIFY_SUCCESS);
}

$mform = new send_email_form();

if ($mform->is_cancelled()) {
    redirect($return);
} else if ($formdata = $mform->get_data()) {
    $options = new stdClass();
    $options->para     = false;
    $options->newlines = true;
    $options->smiley   = false;
    $options->trusted = trusttext_trusted(\context_system::instance());

    $carboncopy = isset($formdata->carboncopy);
    $text = format_text($formdata->message['text'], $formdata->message['format'], $options);

    $preview = html_writer::start_tag('dl');
    $preview .= html_writer::tag('dt', get_string('subject', 'message'));
    $preview .= html_writer::tag('dd', $formdata->subject);
    $preview .= html_writer::tag('dt', get_string('carboncopy', 'message'));
    if ($carboncopy) {
        $preview .= html_writer::tag('dd', get_string('yes'));
    } else {
        $preview .= html_writer::tag('dd', get_string('no'));
    }
    $preview .= html_writer::tag('dt', get_string('message', 'message'));
    $preview .= html_writer::tag('dd', $text);
    $preview .= html_writer::end_tag('dl');

    list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
    $userlist = $DB->get_records_select_menu('user', "id $in", $params, 'fullname', 'id,'.$DB->sql_fullname().' AS fullname');
    $usernames = implode(', ', $userlist);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('confirmation', 'admin'));
    echo $OUTPUT->box($preview, 'boxwidthnarrow boxaligncenter generalbox', 'preview');

    $post = array('confirm' => 1, 'subject' => $formdata->subject, 'carboncopy' => $carboncopy, 'text' => $text);
    $formcontinue = new single_button(new moodle_url('user_bulk_email.php', $post), get_string('yes'));
    $formcancel = new single_button(new moodle_url('user_bulk.php'), get_string('no'), 'get');
    echo $OUTPUT->confirm(get_string('confirmemail', 'bulkusers', $usernames), $formcontinue, $formcancel);
    echo $OUTPUT->footer();
    die;
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
