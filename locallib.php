<?php

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot.'/mod/assign/locallib.php');

function report_monitoring_get_course_data($courseid) {
    $result = new stdCLass();
    
    $modinfo = get_fast_modinfo($courseid);
    $context = context_course::instance($courseid);
    
    $course = $modinfo->get_course();
    $result->id = $courseid;
    $result->fullname = $course->fullname;
    $result->visible = has_capability('moodle/course:view', $context);
    $result->files = report_monitoring_get_count_course_files($modinfo, true);
    $result->participants = report_monitoring_get_count_course_participants($courseid);
    
    $result->graders = array();
    $graders = report_monitoring_get_course_graders($courseid);
    foreach ($graders as $grader) {
        $graderinfo = new stdClass();
        $graderinfo->id = $grader->id;
        $graderinfo->fullname = fullname($grader);
        $graderinfo->lastaccess = report_monitoring_get_last_access_to_course($courseid, $grader->id);
        $result->graders[] = $graderinfo;
    }
    
    $result->assigns = report_monitoring_get_assign_grades_data($modinfo, 0, true); 
    $result->quiz = report_monitoring_get_quiz_grades_data($modinfo, 0, true);
    
    return $result;
}

function report_monitoring_get_last_access_to_course($courseid, $userid) {
    global $DB;
	
    return $DB->get_field('user_lastaccess', 'timeaccess', array('courseid' => $courseid, 'userid' => $userid));
}

function report_monitoring_get_course_graders($courseid, $fields = 'u.*') {
    $context = context_course::instance($courseid);
    $graders = get_enrolled_users($context, 'mod/assign:grade', null, $fields, null, null, null, true);
    foreach ($graders as $grader) {
        if (!is_enrolled($context, $grader, 'mod/quiz:grade', true))
            unset($graders[$grader->id]);
    }
    return $graders;
}

function report_monitoring_get_count_course_participants($courseid) {
    $context = context_course::instance($courseid);
    return count_enrolled_users($context);
}

function report_monitoring_get_assign_grades_data($modinfo, $activitygroup, $onlyvisible = false) {
    global $DB, $CFG;

    $report_plugins = core_component::get_plugin_list('report');
    $report_activity_installed = isset($report_plugins['activity']);
    if ($report_activity_installed) {
        require_once($CFG->dirroot.'/report/activity/locallib.php');
    }

    $modules = $modinfo->get_instances_of('assign');
    $course = $modinfo->get_course();

    $result = array();

    foreach ($modules as $module) {

        $visible = $module->visible;
        if ($report_activity_installed) $visible = report_activity_get_modvisible($module);
        if ($onlyvisible && !$visible) continue;
        $cm = context_module::instance($module->id);
        $assign = new assign($cm, $module, $course);
        $instance = $assign->get_instance();
        $moddata = new stdClass();
        
        $moddata->name = $module->name;
        $moddata->teamsubmission = $instance->teamsubmission;
        $moddata->nograde = $instance->grade == 0;
        $moddata->modvisible = $module->visible;
        $moddata->visible = has_capability('mod/assign:view', $cm);
        
        if ($instance->teamsubmission) { // расчет по правилам Moodle
            $moddata->participants = $assign->count_teams($activitygroup);
            $moddata->submitted = $assign->count_submissions_with_status(ASSIGN_SUBMISSION_STATUS_DRAFT) +
                                  $assign->count_submissions_with_status(ASSIGN_SUBMISSION_STATUS_SUBMITTED);
            $moddata->need_grading = $assign->count_submissions_need_grading();
        } else { // расчет по собственным правилам
            list($esql, $uparams) = get_enrolled_sql($cm, 'mod/assign:submit', $activitygroup, 'u.*', null, null, null, true);
            $info = new \core_availability\info_module($module);
            list($fsql, $fparams) = $info->get_user_list_sql(true);
            if ($fsql) $uparams = array_merge($uparams, $fparams);
            $psql = "SELECT COUNT(*) FROM {user} u JOIN ($esql) e ON u.id = e.id " . ($fsql ? "JOIN ($fsql) f ON u.id = f.id" : "");
            $moddata->participants = $DB->count_records_sql($psql, $uparams);
            
            $select = "SELECT COUNT(DISTINCT(s.userid)) ";
            $table = "FROM {assign_submission} s ";
            $ujoin = "JOIN ($esql) e ON s.userid = e.id " . ($fsql ? "JOIN ($fsql) f ON s.userid = f.id " : "");
            $where = "WHERE s.assignment = :assign AND s.timemodified IS NOT NULL AND (s.status = :stat1 OR s.status = :stat2) ";
            $sparams = array(
                'assign' => $module->instance,
                'stat1'  => ASSIGN_SUBMISSION_STATUS_SUBMITTED,
                'stat2'  => ASSIGN_SUBMISSION_STATUS_DRAFT
            );
            $sparams = array_merge($sparams, $uparams);
            $moddata->submitted = $DB->count_records_sql($select . $table . $ujoin . $where, $sparams);
            
            $select = "SELECT COUNT(s.userid) ";
            $gjoin = "LEFT JOIN {assign_grades} g ON s.assignment = g.assignment AND s.userid = g.userid AND g.attemptnumber = s.attemptnumber ";
            $where .= "AND s.latest = 1 AND (s.timemodified >= g.timemodified OR g.timemodified IS NULL OR g.grade IS NULL)";
            $moddata->need_grading = $DB->count_records_sql($select . $table . $ujoin . $gjoin . $where, $sparams);
        }
        
        $result[$module->id] = $moddata;
        
    }

    return $result;
}

function report_monitoring_get_quiz_grades_data($modinfo, $activitygroup, $onlyvisible = false) {
    global $DB, $CFG;

    $report_plugins = core_component::get_plugin_list('report');
    $report_activity_installed = isset($report_plugins['activity']);
    if ($report_activity_installed) {
        require_once($CFG->dirroot.'/report/activity/locallib.php');
    }
    
    $modules = $modinfo->get_instances_of('quiz');

    $result = array();

    foreach ($modules as $module) {

        $visible = $module->visible;
        if ($report_activity_installed) $visible = report_activity_get_modvisible($module);
        if ($onlyvisible && !$visible) continue;
        $cm = context_module::instance($module->id);
        $moddata = new stdClass();
        
        $moddata->name = $module->name;
        $moddata->modvisible = $module->visible;
        $moddata->visible = has_capability('mod/quiz:view', $cm);

        list($esql, $uparams) = get_enrolled_sql($cm, 'mod/quiz:attempt', $activitygroup, 'u.*', null, null, null, true);
        $info = new \core_availability\info_module($module);
        list($fsql, $fparams) = $info->get_user_list_sql(true);
        if ($fsql) $uparams = array_merge($uparams, $fparams);
        $psql = "SELECT COUNT(*) FROM {user} u JOIN ($esql) e ON u.id = e.id " . ($fsql ? "JOIN ($fsql) f ON u.id = f.id" : "");
        $moddata->countusers = $DB->count_records_sql($psql, $uparams);
        
        $select = "SELECT COUNT(qg.id) ";
        $table = "FROM {quiz_grades} qg ";
        $ujoin = "JOIN ($esql) e ON qg.userid = e.id " . ($fsql ? "JOIN ($fsql) f ON qg.userid = f.id " : "");
        $where = "WHERE qg.quiz = :quiz";
        $qparams = array_merge(array('quiz' => $module->instance), $uparams);
        $moddata->countgrades = $DB->count_records_sql($select . $table . $ujoin . $where, $qparams);

        $result[$module->id] = $moddata;
        
    }

    return $result;
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
