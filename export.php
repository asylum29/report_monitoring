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
require_once($CFG->libdir.'/coursecatlib.php');
require_once($CFG->libdir.'/excellib.class.php');

$courseid   = required_param('id', PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$contextcoursecat = $categoryid ? context_coursecat::instance($categoryid) : null;

$baseurl = new moodle_url('/report/monitoring/export.php', array('id' => $courseid));
$PAGE->set_url($baseurl);

require_login($course);
$contextcourse = context_course::instance($courseid);
require_capability('report/monitoring:view', $contextcourse);

if ($contextcoursecat) { // если категория выбрана и существует
    require_capability('report/monitoring:catview', $contextcoursecat);

    $strmonitoring = get_string('pluginname', 'report_monitoring');
    $downloadfilename = clean_filename("$strmonitoring.xls");
    $workbook = new MoodleExcelWorkbook("-");
    $workbook->send($downloadfilename);
    $myxls = $workbook->add_worksheet($strmonitoring);

    // Формирование заголовка таблицы
    $myxls->write_string(0, 0, get_string('key1', 'report_monitoring'));
    $myxls->write_string(0, 1, get_string('key2', 'report_monitoring'));
    $myxls->write_string(0, 2, get_string('key12', 'report_monitoring'));
    $myxls->write_string(0, 3, get_string('key13', 'report_monitoring'));
    $myxls->write_string(0, 4, get_string('key14', 'report_monitoring'));
    $myxls->write_string(0, 5, get_string('key17', 'report_monitoring'));
    $myxls->write_string(0, 6, get_string('key18', 'report_monitoring'));
    $myxls->write_string(0, 7, get_string('key3', 'report_monitoring'));

    // Формирование данных таблицы
    $index = 1;
    $courses = coursecat::get($categoryid)->get_courses(array('recursive' => true));
    foreach ($courses as $course) {
        if (!$course->visible) continue;
        $coursedata = report_monitoring_get_course_data($course->id);

        $myxls->write_string($index, 0, $coursedata->fullname);

        $graders = array();
        if (count($coursedata->graders) > 0) {
            foreach ($coursedata->graders as $grader) {
                $content = $grader->fullname;
                $content .= $grader->lastaccess ? ' (' . format_time(time() - $grader->lastaccess) . ')' : ' (' . get_string('never') . ')';
                $graders[] = $content;
            }
        } else $graders[] = get_string('key4', 'report_monitoring');
        $myxls->write_string($index, 1, implode('; ', $graders));

        $total = $submitted = $graded = 0;
        if (count($coursedata->assigns) > 0) {
            foreach ($coursedata->assigns as $assign) {
                if (!$assign->teamsubmission && !$assign->nograde) {
                    $total += $assign->participants;
                }

                if (!$assign->teamsubmission && !$assign->nograde) {
                    $submitted += $assign->submitted;
                }

                if (!$assign->teamsubmission && !$assign->nograde) {
                    $graded += $assign->submitted - $assign->need_grading;
                }
            }
        }
        $myxls->write_number($index, 2, $total);
        $myxls->write_number($index, 3, $submitted);
        $myxls->write_number($index, 4, $graded);
        $need_grading = $submitted - $graded;

        $total = $graded = 0;
        if (count($coursedata->quiz) > 0) {
            foreach ($coursedata->quiz as $quiz) {
                $total += $quiz->countusers;
                $graded += $quiz->countgrades;
            }
        }
        $myxls->write_number($index, 5, $total);
        $myxls->write_number($index, 6, $graded);

        $notices = array();
        if (count($coursedata->graders) == 0) {
            $notices[] = get_string('key4', 'report_monitoring');
        }
        if (count($coursedata->graders) == $coursedata->participants) {
            $notices[] = get_string('key5', 'report_monitoring');
        }
        if (count($coursedata->assigns) == 0 && count($coursedata->quiz) == 0) {
            $notices[] = get_string('key6', 'report_monitoring');
        }
        if ($coursedata->files == 0) {
            $notices[] = get_string('key7', 'report_monitoring');
        }
        if ($need_grading > 0) {
            $notices[] = get_string('key9', 'report_monitoring') . ' (' . $need_grading . ')';
        }
        if (count($notices) == 0) {
            $notices[] = get_string('key8', 'report_monitoring');
        }
        $myxls->write_string($index, 7, implode('; ', $notices));

        $myxls->write_string($index, 8, "$CFG->wwwroot/course/view.php?id=$coursedata->id");

        $index++;
    }

    $workbook->close();
    
    exit;
}

// Если категория не была выбрана, вернуться на страницу отчета
$returnurl = new moodle_url('/report/monitoring/index.php');
$returnurl->param('id', $courseid);
redirect($returnurl);
