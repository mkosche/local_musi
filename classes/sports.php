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

namespace local_musi;

use context_system;

/**
 * Helper functions for payment stuff.
 *
 * @package local_musi
 * @copyright 2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sports {

    /**
     * Generate a list of all Sports.
     *
     * @return array
     */
    public static function return_list_of_pages() {
        global $DB;

        $sql = "SELECT cm.id, p.name, p.intro
                FROM {page} p
                LEFT JOIN {course_modules} cm
                ON cm.instance = p.id
                JOIN {modules} m
                ON m.id = cm.module
                WHERE m.name = 'page'
                AND (p.content LIKE '%allekurse%category%'
                OR p.intro LIKE '%allekurse%category%') ";

        return $DB->get_records_sql($sql);
    }

    /**
     * Generate a list of all Sports.
     *
     * @return array
     */
    public static function return_courseid() {
        global $DB;

        $courseid = $DB->get_field_sql(
            "SELECT s1.course FROM (
                SELECT DISTINCT cm.course, count(cm.course)
                FROM {page} p
                JOIN {course_modules} cm
                ON cm.instance = p.id
                JOIN {modules} m
                ON m.id = cm.module
                WHERE (p.content LIKE '%allekurse%category%' OR p.intro LIKE '%allekurse%category%')
                AND m.name = 'page'
                GROUP BY cm.course
                ORDER BY count(cm.course) DESC
            ) s1 LIMIT 1");

        return $courseid;
    }
}
