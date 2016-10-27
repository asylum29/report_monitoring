<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/report/monitoring/locallib.php');
require_once($CFG->libdir.'/coursecatlib.php');

$courseid   = required_param('id', PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$contextcoursecat = $categoryid ? context_coursecat::instance($categoryid) : null;

$baseurl = new moodle_url('/report/monitoring/index.php', array('id' => $courseid));
$PAGE->set_url($baseurl);

require_login($course);
$contextcourse = context_course::instance($courseid);
require_capability('report/monitoring:view', $contextcourse);
if ($contextcoursecat) {
    require_capability('report/monitoring:catview', $contextcoursecat);
}

$strmonitoring = get_string('pluginname', 'report_monitoring');
$PAGE->set_title("$course->shortname: $strmonitoring");
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('report');
$PAGE->requires->css('/report/monitoring/css/styles-plugin.css');
$PAGE->requires->js_call_amd('report_monitoring/reporttable', 'init');

$event = \report_monitoring\event\report_viewed::create(array('context' => $contextcourse, 'objectid' => $categoryid));
$event->trigger();

$output = $PAGE->get_renderer('report_monitoring');

echo $output->header();
echo $output->heading($strmonitoring);
echo $output->container_start('', 'report_monitoring');

$categories = coursecat::make_categories_list('report/monitoring:catview');
if (count($categories) > 0) { // если есть категории, которые можно выбрать
    echo $output->container(get_string('categories') . ':', 'report_monitoring_coursecat_select');
    echo $output->single_select($baseurl, 'categoryid', $categories, $categoryid);   
}

if ($contextcoursecat) { // если категория выбрана, существует и есть право ее просмотра
    $coursesdata = array();
    $courses = coursecat::get($categoryid)->get_courses(array('recursive' => true));
    foreach ($courses as $course) {
        if (!$course->visible) continue;
        $coursesdata[] = report_monitoring_get_course_data($course->id);
    }
    echo $output->display_report($coursesdata);
}

echo $output->container_end();
echo $output->footer();
