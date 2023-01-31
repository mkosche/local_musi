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
 * This file contains the definition for the renderable classes for the booking instance
 *
 * @package   local_musi
 * @copyright 2021 Georg Maißer {@link http://www.wunderbyte.at}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_musi\output\tabs;

use html_writer;
use mod_booking\singleton_service;
use moodle_url;
use renderer_base;
use renderable;
use stdClass;
use templatable;
use context_system;
use core\output\dynamic_tabs\base;
use core_reportbuilder\system_report_factory;
use report_configlog\local\systemreports\config_changes;

/**
 * This class prepares data for displaying a booking option instance
 *
 * @package local_musi
 * @copyright 2021 Georg Maißer {@link http://www.wunderbyte.at}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sports extends base {


    /**
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
       return $this->get_sports();
    }

    /**
     * The label to be displayed on the tab
     *
     * @return string
     */
    public function get_tab_label(): string {
        return get_string('listofsports', 'local_musi');
    }

    /**
     * Check permission of the current user to access this tab
     *
     * @return bool
     */
    public function is_available(): bool {
        // Define the correct permissions here.
        return true;
    }

    /**
     * Template to use to display tab contents
     *
     * @return string
     */
    public function get_template(): string {
        return 'local_musi/dashboard_card_quicklinks';
    }

    private function get_sports() {
        global $DB;

        $data = new stdClass();

        $sportspages = $DB->get_records_sql(
            "SELECT cm.id, p.name
            FROM {page} p
            JOIN {course_modules} cm
            ON cm.instance = p.id
            JOIN {modules} m
            ON m.id = cm.module
            WHERE m.name = 'page'
            AND (p.content LIKE '%allekurse%category%'
            OR p.intro LIKE '%allekurse%category%')");

        foreach ($sportspages as $sportspage) {
            $url = new moodle_url('/mod/page/view.php', ['id' => $sportspage->id]);
            $data->{$sportspage->name} = ['link' => $url->out(false)];
        }

        $returnarray = [];
        $returnarray['item'] = [];

        // We transform the data object to an array where we can read key & value.
        foreach ($data as $key => $value) {

            $item = [
                'key' => $key
            ];

            // We only have value & link at the time as types, but might have more at one point.
            foreach ($value as $type => $name) {
                $item[$type] = $name;
            }

            $returnarray['item'][] = $item;
        }

        return $returnarray;
    }
}