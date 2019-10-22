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
 *  Page for resetting users that are locked out from resetting password
 *
 * @package    tool_securityquestion
 * @copyright  2019 Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__.'/locallib.php');

defined('MOODLE_INTERNAL') || die();

admin_externalpage_setup('tool_securityquestions_reset_lockout');
$resetid = optional_param('reset', 0, PARAM_INT);
$prevurl = ($CFG->wwwroot.'/admin/category.php?category=securityquestions');

$notifyresetsuccess = false;
$notifyclearsuccess = false;

if ($resetid != 0) {
    //Execute reset of user before regenerating page
    $exists = $DB->record_exists('user', array('id' => $resetid));
    if ($exists) {
        $user = $DB->get_record('user', array('id' => $resetid));
        tool_securityquestions_reset_lockout_counter($user);
        tool_securityquestions_unlock_user($user);
        $notifyresetsuccess = true;
    }
}

$form = new \tool_securityquestions\form\reset_lockout();
if ($form->is_cancelled()) {

    redirect($prevurl);

} else if ($fromform = $form->get_data()) {

    $foundusers = $DB->get_records('user', array('username' => ($fromform->clearresponses)));
    // User is guaranteed to exist here due to validation
    if (!empty($foundusers)) {
        // Get first matching username record
        $user = reset($foundusers);
    } else {
        $foundusers = $DB->get_records('user', array('email' => ($fromform->clearresponses)));
        if (!empty($foundusers)) {
            // Get first matching email record (should be unique)
            $user = reset($foundusers);
        }
    }

    tool_securityquestions_clear_user_responses($user);
    $notifyclearsuccess = true;
}

// Build the page output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('resetuserpagename', 'tool_securityquestions'));
echo '<br>';
$form->display();

if ($notifyresetsuccess == true) {
    $notifyresetsuccess == false;
    echo $OUTPUT->notification(get_string('formuserunlocked', 'tool_securityquestions'), 'notifysuccess');
}

if ($notifyclearsuccess == true) {
    $notifyclearsuccess == false;
    echo $OUTPUT->notification(get_string('formuserresponsescleared', 'tool_securityquestions'), 'notifysuccess');
}

echo '<br>';
echo '<h3>Locked Out Users</h3>';
generate_table();
echo $OUTPUT->footer();

// =======================================DISPLAY AND VALIDATION FUNCTIONS ====================================

function generate_table() {
    // Render table
    global $DB;
    // Get records from database for populating table
    $lockedusers = $DB->get_records('tool_securityquestions_loc', array('locked' => 1));

    $table = new html_table();
    $table->head = array('User ID', 'Username', 'Email', 'Full Name', 'Unlock User');
    $table->colclasses = array('centeralign', 'centeralign', 'centeralign', 'centeralign', 'centeralign');

    foreach ($lockedusers as $userrecord) {
        $user = $DB->get_record('user', array('id' => $userrecord->userid));
        $reset = new moodle_url('/admin/tool/securityquestions/reset_lockout.php', array('reset' => $userrecord->id));
        
        $table->data[] = array(
            $user->id,
            $user->username,
            $user->email,
            fullname($user),
            html_writer::link($reset, get_string('formresetlockout', 'tool_securityquestions')),
        );
    }
    if (count($lockedusers) != 0) {
        echo html_writer::table($table);
    } else {
        $string = get_string('formnolockedusers', 'tool_securityquestions');
        echo "<h4>$string</h4>";
    }

}