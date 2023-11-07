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
use mod_booking\output\view;
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
class overview extends base {


    /**
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
//        $content = (object)[];
//        $report = system_report_factory::create(config_changes::class, context_system::instance());
//        $content->content = $report->output();
//        return $content;
        // We transform the data object to an array where we can read key & value.
        $data = $this->return_content();
        foreach ($data as $key => $value) {

            $item = [
                'key' => get_string($key, 'local_musi')
            ];

            // We only have value & link at the time as types, but might have more at one point.
            foreach ($value as $type => $name) {
                $item[$type] = $name;
            }

            $returnarray['item'][] = $item;
        }

        $returnarray['coursesavailable'] = $data->coursesavailable;
        $returnarray['coursesbooked'] = $data->coursesbooked;
        $returnarray['locations'] = $data->locations;
        $returnarray['courses'] = $data->courses;

        return $returnarray;
    }

    private function return_content() {
        global $DB, $PAGE;

        $data = new stdClass();

        $url = new moodle_url('/local/shopping_cart/cashier.php');
        $data->cachier = ['link' => $url->out(false), 'icon' => 'fa-money'];


        $cmid = get_config('local_musi', 'shortcodessetinstance');

        if ($cmid) {
            $url = new moodle_url('/mod/booking/view.php', ['id' => $cmid, 'whichview' => 'showall']);
            $data->editbookings = ['link' => $url->out(false), 'icon' => 'fa-list'];
        } else {
            $url = new moodle_url('/admin/category.php?category=local_musi');
            $data->addbookinginstance = ['link' => $url->out(false)];
        }

        $url = new moodle_url('/local/musi/teachers.php');
        $data->viewteachers = ['link' => $url->out(false), 'icon' => 'fa-user'];

        global $DB;

        if ($activeinstance = get_config('local_musi', 'shortcodessetinstance')) {
            $sql = "SELECT COUNT(*)
                FROM {booking_options} bo
                JOIN {course_modules} cm ON bo.bookingid=cm.instance
                JOIN {modules} m ON cm.module=m.id
                WHERE m.name='booking'
                AND cm.id=:cmid";
            $coursesavailable = $DB->count_records_sql($sql, ['cmid' => $activeinstance]);
        } else {
            $coursesavailable = 0;
        }

        $coursesbooked = $DB->count_records('booking_answers', ['waitinglist' => MUSI_STATUSPARAM_BOOKED]);
        $locations = $DB->count_records('local_entities');

        $data->coursesavailable = $coursesavailable;
        $data->coursesbooked = $coursesbooked;
        $data->locations = $locations;

        $_POST['id'] = $cmid;
        $courseview = new view($cmid, 'showall', 0);
        $output = $PAGE->get_renderer('mod_booking');
        $data->courses = $output->render_view($courseview);


        return $data;
    }

    /**
     * The label to be displayed on the tab
     *
     * @return string
     */
    public function get_tab_label(): string {
        return get_string('numberofcourses', 'local_musi');
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
}
