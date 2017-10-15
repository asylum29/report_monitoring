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

require_once('../../config.php');
require_once($CFG->dirroot.'/report/monitoring/locallib.php');
require_once($CFG->dirroot.'/report/monitoring/forms.php');
require_once($CFG->libdir.'/coursecatlib.php');

$id         = required_param('id', PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$courseid   = optional_param('courseid', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
if ($courseid > 0) {
    $categoryid = $DB->get_field('course', 'category', array('id' => $courseid), MUST_EXIST);
}
$contextcoursecat = $categoryid ? context_coursecat::instance($categoryid) : null;
$baseurl = new moodle_url('/report/monitoring/index.php', array('id' => $id));
$PAGE->set_url($baseurl);

require_login($course);
$contextcourse = context_course::instance($id);
require_capability('report/monitoring:view', $contextcourse);
if ($contextcoursecat) {
    require_capability('report/monitoring:catview', $contextcoursecat);
}
if ($courseid > 0) {
    require_capability('report/monitoring:catadmin', $contextcoursecat);
}

$strmonitoring = get_string('pluginname', 'report_monitoring');
$PAGE->set_title("$course->shortname: $strmonitoring");
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('report');

if ($courseid > 0) {
    
    $baseurl->param('courseid', $courseid);
    $commentheader = get_string('key26', 'report_monitoring');
    $PAGE->navbar->add($commentheader, $baseurl);

    $params = array('id' => $id, 'categoryid' => $categoryid);
    $redirecturl = new moodle_url('/report/monitoring/index.php', $params, 'report_monitoring_' . $courseid);
    $coursedata = report_monitoring_get_course_data($courseid);
    $formdata = array('id' => $id, 'categoryid' => $categoryid, 'coursedata' => $coursedata);
    $commentform = new report_moniroting_comment_form(null, $formdata);
    if ($commentform->is_cancelled()) {
        redirect($redirecturl);
    } else if ($data = $commentform->get_data()) {
        report_monitoring_set_comment($courseid, $data->comment, $data->ready);
        $SESSION->report_monitoring_last_course = $courseid;
        $event = \report_monitoring\event\comment_created::create(array('context' => $contextcourse, 'objectid' => $courseid));
        $event->trigger();
        redirect($redirecturl);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading($commentheader);

    if ($result = $coursedata->monitoring) {
        $commentform->set_data(array(
            'comment' => $result->comment,
            'ready'   => $result->ready,
        ));
    }
    $commentform->display();

    echo $OUTPUT->footer();
    
} else {
    
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
        $details = isset($SESSION->report_monitoring_details) ? $SESSION->report_monitoring_details : 1;

        if ($contextcoursecat) { // если категория выбрана, существует и есть право ее просмотра
            $params = array('id' => $id, 'categoryid' => $categoryid);
            $exporturl = new moodle_url($CFG->wwwroot . '/report/monitoring/export.php', $params);
            echo $output->single_button($exporturl, get_string('key25', 'report_monitoring'), 'get');
            
            if ($details == 1) {
                $params['enable'] = 0;
                $label = get_string('key30', 'report_monitoring');
            } else {
                $params['enable'] = 1;
                $label = get_string('key29', 'report_monitoring');
            }
            $moreurl = new moodle_url($CFG->wwwroot . '/report/monitoring/details.php', $params);
            echo $output->single_button($moreurl, $label, 'post');
        }

        $label = $output->container(get_string('categories') . ':', 'report_monitoring_coursecat_label');
        $select = $output->single_select($baseurl, 'categoryid', $categories, $categoryid);
        echo $output->container($label . $select, 'report_monitoring_coursecat_select');

        if ($contextcoursecat) { // если категория выбрана, существует и есть право ее просмотра
            $coursesdata = array();
            $courses = coursecat::get($categoryid)->get_courses(array('recursive' => true));
            if ($details == 1) {
                foreach ($courses as $course) {
                    if (!$course->visible) continue;
                    $coursesdata[] = report_monitoring_get_course_data($course->id);
                }
                echo $output->display_report($id, $coursesdata, has_capability('report/monitoring:catadmin', $contextcoursecat));
            } else {
                foreach ($courses as $course) {
                    if (!$course->visible) continue;
                    $coursesdata[] = report_monitoring_get_course_comment($course->id);
                }
                echo $output->display_simple_report($id, $coursesdata, has_capability('report/monitoring:catadmin', $contextcoursecat));
            }
        }
    }

    echo $output->container_end();
    echo $output->footer();
    
}
