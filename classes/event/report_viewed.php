<?php

namespace report_monitoring\event;
defined('MOODLE_INTERNAL') || die();

class report_viewed extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'course_categories';
    }

    public static function get_name() {
        return get_string('key21', 'report_monitoring');
    }

    public function get_description() {
        return "The user with id '$this->userid' viewed the monitoring report.";
    }

    public function get_url() {
        return new \moodle_url('/report/monitoring/index.php', array('id' => $this->courseid, 'categoryid' => $this->data['objectid']));
    }

}
