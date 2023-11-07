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
use mod_booking\output\page_allteachers;
use moodle_url;
use renderer_base;
use renderable;
use stdClass;
use templatable;
use context_system;
use core\output\dynamic_tabs\base;

/**
 * This class prepares data for displaying a booking option instance
 *
 * @package local_musi
 * @copyright 2021 Georg Maißer {@link http://www.wunderbyte.at}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class teacher extends base {

    /**
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        //error_log(print_r( $this->get_teachers(),true));
        return $this->get_teachers();
    }

    /**
     * The label to be displayed on the tab
     *
     * @return string
     */
    public function get_tab_label(): string {
        return get_string('viewteachers', 'local_musi');
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
        return 'local_musi/page_allteachers';
    }

    private function get_teachers(){
        global $DB,$PAGE;

        $returnarray = [];

        $context = context_system::instance();

        if (has_capability('local/musi:canedit', $context)) {
            // Only add, if true - false won't work in template.
            $returnarray['canedit'] = true;
        }

        if (!isset($PAGE->context)) {
            $PAGE->set_context($context);
        }

        $teacherids = [];

// Now get all teachers that we're interested in.
        $sqlteachers =
            "SELECT DISTINCT bt.userid, u.firstname, u.lastname, u.email
    FROM {booking_teachers} bt
    LEFT JOIN {user} u
    ON u.id = bt.userid
    ORDER BY u.lastname ASC";

        if ($teacherrecords = $DB->get_records_sql($sqlteachers)) {
            foreach ($teacherrecords as $teacherrecord) {
                $teacherids[] = $teacherrecord->userid;
            }
        }

// Now prepare the data for all teachers.
        $data = new page_allteachers($teacherids);

        $output = $PAGE->get_renderer('local_musi');

// And return the rendered page showing all teachers.
       return $data->export_for_template($output);
    }
}
