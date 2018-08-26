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
 * Displays help via AJAX call or in a new page
 *
 * Use {@link core_renderer::help_icon()} or {@link addHelpButton()} to display
 * the help icon.
 *
 * @copyright  2017 Dan Marsden
 * @package    mod_attendance
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use Endroid\QrCode\QrCode;
require_once(dirname(__FILE__).'/../../config.php');


$session = required_param('session', PARAM_INT);
$session = $DB->get_record('attendance_sessions', array('id' => $session), '*', MUST_EXIST);

$cm = get_coursemodule_from_instance('attendance', $session->attendanceid);
require_login($cm->course, $cm);

$context = context_module::instance($cm->id);
$capabilities = array('mod/attendance:manageattendances', 'mod/attendance:takeattendances', 'mod/attendance:changeattendances');
if (!has_any_capability($capabilities, $context)) {
    exit;
}

$PAGE->set_url('/mod/attendance/password.php');
$PAGE->set_pagelayout('popup');

$PAGE->set_context(context_system::instance());

$PAGE->set_title(get_string('password', 'attendance'));

echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('passwordgrp', 'attendance'));
echo html_writer::span($session->studentpassword, 'student-password');
echo html_writer::tag('h3', $plugininfos['qrlinks']);

if (isset($session->includeqrcode) && $session->includeqrcode == 1) {
    $qrcodeurl = $CFG->wwwroot . '/mod/attendance/attendance.php?studentpassword=' . $session->studentpassword . '&sessid=' . $session->id;
    echo html_writer::tag('h3', get_string('qrcode', 'attendance'));

    // If the local_qrlinks plugin is installed, use it to create the QR code.
    $plugininfos = core_plugin_manager::instance()->get_plugins_of_type('local');
    if (isset($plugininfos['qrlinks'])) {
        require_once(dirname(__FILE__).'/../../local/qrlinks/thirdparty/QrCode/src/QrCode.php');
        $code = new QrCode($qrcodeurl);
        $code->setSize(500);
        echo html_writer::img('data:image/png;base64,' . base64_encode($code->get()));
    } else {
        // Otherwise try using an external API service to create the QR code instead.
        try {
            $qrcode = file_get_contents('https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=' . urlencode($qrcodeurl));
            if ($qrcode === false) {
                echo html_writer::tag('p', get_string('qrcodemissing', 'attendance'));
            } else {
                echo html_writer::img('data:image/png;base64,' . base64_encode($qrcode));
                echo html_writer::tag('p', get_string('qrcodewarning', 'attendance'));
            }
        } catch (Exception $e) {
                echo html_writer::tag('p', get_string('qrcodemissing', 'attendance'));
        }
    }
}
echo $OUTPUT->footer();
