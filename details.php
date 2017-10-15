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

$courseid   = required_param('id', PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$details    = optional_param('enable', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$contextcoursecat = $categoryid ? context_coursecat::instance($categoryid) : null;

$baseurl = new moodle_url('/report/monitoring/details.php', array('id' => $courseid, 'enable' => $details));
$PAGE->set_url($baseurl);

require_login($course);

$contextcourse = context_course::instance($courseid);
require_capability('report/monitoring:view', $contextcourse);

if (data_submitted() && confirm_sesskey()) {
    $SESSION->report_monitoring_details = $details == 0 ? 0 : 1;
}

$returnurl = new moodle_url('/report/monitoring/index.php');
$returnurl->param('id', $courseid);
$returnurl->param('categoryid', $categoryid);
redirect($returnurl);
