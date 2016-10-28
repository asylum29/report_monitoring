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
 * @copyright  2016 Aleksandr Raetskiy <ksenon3@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2016102901;
$plugin->maturity  = MATURITY_STABLE;
$plugin->requires  = 2016052300;
$plugin->component = 'report_monitoring';

$plugin->dependencies = array(
    'mod_resource'    => 2016052300,
    'mod_folder'      => 2016052300,
    'report_activity' => 2016102102,
);
