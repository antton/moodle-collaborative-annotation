<?php
// This file is part of mod_annotation
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
 * Loads the annotations from the server for the image currently being viewed.
 * POST request with the window.location.url (cmid)
 *
 * @package   mod_annotation
 * @copyright 2015 Jamie McGowan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!empty($_POST['url'])) {
    require_once(__DIR__ . "../../../../config.php");
    require_once("$CFG->dirroot/mod/annotation/locallib.php");
    require_login();

    global $CFG, $DB, $USER;

    $userid = $USER->id; // Gets the current users id.
    $url = $_POST['url'];
    $cmid = $url; // Used by initialize.php.
    require_once("../initialize.php");

    $cm = get_coursemodule_from_id('annotation', $url, 0, false, MUST_EXIST);

    // Determine if user is student or teacher.
    $context = context_course::instance($cm->course);
    $teacher = has_capability('mod/annotation:manage', $context);

    if ($groupannotation && $teacher) {
        // The user is a teacher, load all annotations [from every group].
        $sql = "SELECT * FROM mdl_annotation_annotation WHERE url = ?";
        $rs = $DB->get_recordset_sql($sql, array($url));
    } else if ($groupannotation && ! $groupannotationsvisible) {
        // Load only annotations for this group and teachers/admins.
        $sql = "SELECT * FROM mdl_annotation_annotation WHERE url = ? AND (group_id = ? OR group_id = -1)";
        $rs = $DB->get_recordset_sql($sql, array($url, $group));
    } else {
        $sql = "SELECT * FROM mdl_annotation_annotation WHERE url = ?";
        $rs = $DB->get_recordset_sql($sql, array($url));
    }

    $response = array();

    $response[] = $editable; // Testing block annotations if they are disabled; Could be interrupted?.

    // Loop through results.
    foreach ($rs as $record) {
        // Get username of annotation creator.
        $user = $DB->get_record('user', array("id" => $record->userid));
        $record->username = $user->firstname . " " . $user->lastname;

        // Determine group name from the annotations group id if groups enabled.
        if ($groupannotation) {
                $record->groupname = groups_get_group_name($record->group_id);
        }

        // Don't need to return the user's id or the url.
        unset($record->userid);
        unset($record->url);

        if ($record->tags == "") {
            $record->tags = null;
        }

        $response[] = $record;
    }
    $rs->close(); // Close the record set.
    echo json_encode($response);
} else {
    http_response_code(400);
}
