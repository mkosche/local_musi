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
use local_musi\output\page_teacher;
use mod_booking\singleton_service;
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

        $sqlteachers =
            "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.picture, u.description, u.descriptionformat, u.email, u.maildisplay
            FROM {booking_teachers} bt
            LEFT JOIN {user} u
            ON u.id = bt.userid
            ORDER BY u.lastname ASC";

        $teacherrecords = $DB->get_records_sql($sqlteachers);
        // We transform the data object to an array where we can read key & value.
        foreach ($teacherrecords as $teacher) {
            if($teacher->id != null) {
                // Here we can load custom userprofile fields and add the to the array to render.
                // Right now, we just use a few standard pieces of information.

                $teacherarr = [
                    'teacherid' => $teacher->id,
                    'firstname' => $teacher->firstname,
                    'lastname' => $teacher->lastname,
                    'orderletter' => substr($teacher->lastname, 0, 1), // First letter of the teacher's last name.
                    'description' => format_text($teacher->description, $teacher->descriptionformat)
                ];

                if ($teacher->picture) {
                    $picture = new \user_picture($teacher);
                    $picture->size = 70;
                    $imageurl = $picture->get_url($PAGE);
                    $teacherarr['image'] = $imageurl;
                }

                // Add a link to the report of performed teaching units.
                // But only, if the user has the appropriate capability.
                if ((has_capability('mod/booking:updatebooking', $PAGE->context))) {
                    $url = new moodle_url('/mod/booking/teacher_performed_units_report.php', ['teacherid' => $teacher->id]);
                    $teacherarr['linktoperformedunitsreport'] = $url->out();
                }

                // If the user has set to hide e-mails, we won't show them.
                // However, a site admin will always see e-mail addresses.
                if (!empty($teacher->email) &&
                    ($teacher->maildisplay == 1 || has_capability('local/musi:canedit', $context))) {
                    $teacherarr['email'] = $teacher->email;
                }

                if (page_teacher::teacher_messaging_is_possible($teacher->id)) {
                    $teacherarr['messagingispossible'] = true;
                }

                $link = new moodle_url('/local/musi/teacher.php', ['teacherid' => $teacher->id]);
                $teacherarr['link'] = $link->out(false);

                $messagelink = new moodle_url('/message/index.php', ['id' => $teacher->id]);
                $teacherarr['messagelink'] = $messagelink->out(false);

                $returnarray['teachers'][] = $teacherarr;
            }
        }

        return $returnarray;
    }
}
