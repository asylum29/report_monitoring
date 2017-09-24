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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class report_moniroting_comment_form extends moodleform {

    public function definition() {
        global $CFG;

        $mform = & $this->_form;
        $id = $this->_customdata['id'];
        $categoryid = $this->_customdata['categoryid'];
        $coursedata = $this->_customdata['coursedata'];

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'categoryid', $categoryid);
        $mform->setType('categoryid', PARAM_INT);
        
        $mform->addElement('hidden', 'courseid', $coursedata->id);
        $mform->setType('courseid', PARAM_INT);

        $courseurl = "$CFG->wwwroot/course/view.php?id=$coursedata->id";
        $content = html_writer::link($courseurl, $coursedata->fullname, array('target' => '_blank'));
        $mform->addElement('static', 'coursename', get_string('key9', 'block_course_manager'), $content);

        $need_grading = 0;
        foreach ($coursedata->assigns as $assign) {
            if (!$assign->teamsubmission && !$assign->nograde) {
                $need_grading += $assign->need_grading;
            }
        }

        $notices = array();
        if (count($coursedata->graders) == 0) {
            $notices[] = get_string('key4', 'report_monitoring') . ';';
        }
        if (count($coursedata->graders) == $coursedata->participants) {
            $notices[] = get_string('key5', 'report_monitoring') . ';';
        }
        if (count($coursedata->assigns) == 0 && count($coursedata->quiz) == 0) {
            $notices[] = get_string('key6', 'report_monitoring') . ';';
        }
        if ($coursedata->files == 0) {
            $notices[] = get_string('key7', 'report_monitoring') . ';';
        }
        if ($need_grading > 0) {
            $notices[] = get_string('key9', 'report_monitoring') . '&nbsp;(' . $need_grading . ')' . ';';
        }
        if (count($notices) == 0) {
            $notices[] = get_string('key8', 'report_monitoring');
        }
        $content = html_writer::alist($notices);
        $mform->addElement('static', 'problems', get_string('key3', 'report_monitoring'), $content);

        $mform->addElement('textarea', 'comment', '', 'wrap="virtual" rows="5" cols="50"');

        $mform->addElement('advcheckbox', 'ready', get_string('key27', 'report_monitoring'));

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

}
