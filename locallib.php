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
 * monitoring report
 *
 * @package    report_monitoring
 * @copyright  2017 Aleksandr Raetskiy <ksenon3@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot.'/report/activity/locallib.php');

function report_monitoring_get_course_data($courseid) {
    $result = new stdClass();
    
    $modinfo = get_fast_modinfo($courseid);
    $context = context_course::instance($courseid);
    
    $course = $modinfo->get_course();
    $result->id = $courseid;
    $result->fullname = $course->fullname;
    $result->visible = has_capability('moodle/course:view', $context);
    $result->files = report_monitoring_get_count_course_files($modinfo, true);
    $result->participants = report_monitoring_get_count_course_participants($courseid);
    $result->monitoring = report_monitoring_get_comment($courseid);
    
    $result->graders = array();
    $graders = report_activity_get_course_graders($courseid);
    foreach ($graders as $grader) {
        $graderinfo = new stdClass();
        $graderinfo->id = $grader->id;
        $graderinfo->fullname = fullname($grader);
        $graderinfo->lastaccess = report_activity_get_last_access_to_course($courseid, $grader->id);
        $result->graders[] = $graderinfo;
    }
    
    $result->assigns = report_activity_get_assign_grades_data($modinfo, 0, true); 
    $result->quiz = report_activity_get_quiz_grades_data($modinfo, 0, true);
    
    return $result;
}

function report_monitoring_get_count_course_participants($courseid) {
    $context = context_course::instance($courseid);
    return count_enrolled_users($context);
}

function report_monitoring_get_count_course_files($modinfo, $onlyvisible = false) {
    $result = 0;
    
    $modules = $modinfo->get_instances_of('resource');
    foreach ($modules as $module) {
        if ($onlyvisible && !$module->visible) continue;
        $result++;
    }  
    $fs = get_file_storage();
    $modules = $modinfo->get_instances_of('folder');
    foreach ($modules as $module) {
        if ($onlyvisible && !$module->visible) continue;
        $cm = context_module::instance($module->id);
        $files = $fs->get_area_files($cm->id, 'mod_folder', 'content', 0, null, false);
        $result += count($files);
    }
    
    return $result;
}

function report_monitoring_set_comment($courseid, $comment, $ready) {
    global $DB;

    $new = array(
        'courseid'     => $courseid,
        'comment'      => trim($comment),
        'ready'        => $ready ? 1 : 0,
        'timemodified' => time()
    );
    
    $count = $DB->count_records('report_monitoring_results', array('courseid' => $courseid));
    if ($count > 0) {
        $DB->execute('UPDATE {report_monitoring_results}
                         SET comment = :comment, ready = :ready, timemodified = :timemodified
                       WHERE courseid = :courseid', $new);
    } else {
        $DB->insert_record('report_monitoring_results', $new);
    }
}

function report_monitoring_get_comment($courseid) {
    global $DB;

    return $DB->get_record('report_monitoring_results', array('courseid' => $courseid));
}
