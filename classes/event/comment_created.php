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

namespace report_monitoring\event;
defined('MOODLE_INTERNAL') || die();

class comment_created extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'report_monitoring_results';
    }

    public static function get_name() {
        return get_string('key28', 'report_monitoring');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' has left the comment for the course with id '{$this->data['objectid']}'.";
    }

    public function get_url() {
        return new \moodle_url('/report/monitoring/index.php', array('id' => $this->courseid, 'courseid' => $this->data['objectid']));
    }

}
