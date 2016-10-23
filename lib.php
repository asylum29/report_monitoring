<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/coursecatlib.php');

function report_monitoring_extend_navigation_course($reportnav, $course, $context) {
    if (has_capability('report/monitoring:view', $context) && count(coursecat::make_categories_list('report/monitoring:catview')) > 0) {
        $url = new moodle_url('/report/monitoring/index.php', array('id' => $course->id));
        $reportnav->add(get_string('pluginname', 'report_monitoring'), $url, null, navigation_node::TYPE_SETTING, null, new pix_icon('i/report', ''));
    }
}