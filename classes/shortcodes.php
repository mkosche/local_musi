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
 * Shortcodes for local_musi
 *
 * @package local_musi
 * @subpackage db
 * @since Moodle 3.11
 * @copyright 2022 Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_musi;

use Closure;
use context_system;
use mod_booking\output\page_allteachers;
use local_musi\output\userinformation;
use local_musi\table\musi_table;
use local_shopping_cart\shopping_cart;
use local_shopping_cart\shopping_cart_credits;
use mod_booking\booking;
use mod_booking\singleton_service;
use moodle_url;
use stdClass;

/**
 * Deals with local_shortcodes regarding booking.
 */
class shortcodes {

    /**
     * Prints out list of bookingoptions.
     * Arguments can be 'category' or 'perpage'.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return void
     */
    public static function showallsports($shortcode, $args = [], $content, $env, $next) {

        global $DB, $OUTPUT, $USER;

        self::fix_args($args);

        // Get the ID of the course containing the sports categories.
        $courseid = sports::return_courseid();

        $sections = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC');

        $pages = sports::return_list_of_pages();

        $data['categories'] = [];

        $caneditsubstitutionspool = has_capability('local/musi:editsubstitutionspool', context_system::instance());
        $canviewsubstitutionspool = has_capability('local/musi:viewsubstitutionspool', context_system::instance());

        // Iterate through sport categories.
        foreach ($sections as $section) {

            if (empty($section->name)) {
                continue;
            }

            $cmids = explode(',', $section->sequence);

            $category = [
                'name' => $section->name,
                'categoryid' => $section->id,
                'summary' => $section->summary,
                'sports' => []
            ];

            // For performance.
            // Get all sport records.
            $sportrecords = $DB->get_records_sql("SELECT sport, teachers FROM {local_musi_substitutions}");
            // Get all teacher records.
            $teachersarr = [];
            foreach ($sportrecords as $sportrecord) {
                $teacherids = explode(',', $sportrecord->teachers);
                foreach ($teacherids as $teacherid) {
                    $teachersarr[$teacherid] = $teacherid;
                }
            }
            if(!empty($teachersarr)) {
                list($inorequal, $params) = $DB->get_in_or_equal($teachersarr);
                $sql = "SELECT id, firstname, lastname, email, phone1, phone2 FROM {user} WHERE id $inorequal";
                $teacherrecords = $DB->get_records_sql($sql, $params);
            }

            // Sports.
            foreach ($cmids as $cmid) {
                if (isset($pages[$cmid])) {

                    // If the page is hidden, we do not want to add it.
                    list($course, $cm) = get_course_and_cm_from_cmid($cmid);
                    if (empty($cm->visible)) {
                        continue;
                    }

                    $sport = $pages[$cmid]->name;

                    $description = null;
                    // We do not add descriptions, if they contain one of the "[allekurse..." shorcodes.
                    if (strpos($pages[$cmid]->intro, "[allekurse") == false) {
                        $description = $pages[$cmid]->intro;
                    }

                    $editsubstitutionspool = null;
                    if ($caneditsubstitutionspool) {
                        $editsubstitutionspool = true;
                    }

                    $viewsubstitutionspool = null;
                    $substitutionteachers = [];
                    if ($canviewsubstitutionspool) {
                        $viewsubstitutionspool = true;
                        // Retrieve the list of teachers who can substitute.
                        if (!empty($sportrecords[$sport])) {
                            $record = $sportrecords[$sport];
                            if (!empty($record->teachers)) {
                                $teacherids = explode(',', $record->teachers);
                                foreach ($teacherids as $teacherid) {
                                    $fullteacher = $teacherrecords[$teacherid] ?? null;
                                    if (!empty($fullteacher)) {
                                        $teacher['id'] = $fullteacher->id;
                                        $teacher['firstname'] = $fullteacher->firstname;
                                        $teacher['lastname'] = $fullteacher->lastname;
                                        $teacher['email'] = $fullteacher->email;
                                        $teacher['phone1'] = $fullteacher->phone1;
                                        $teacher['phone2'] = $fullteacher->phone2;
                                        $substitutionteachers[] = $teacher;
                                    }
                                }
                                // Now sort the teachers by last name.
                                usort($substitutionteachers, function($a, $b) {
                                    return $a['lastname'] <=> $b['lastname'];
                                });
                            }
                        }
                        // Generate mailto-Link.
                        $emailstring = '';
                        if (!empty($substitutionteachers)) {
                            foreach ($substitutionteachers as $teacher) {
                                if (!empty($teacher['email']) && ($teacher['email'] != $USER->email)) {
                                    $emailstring .= $teacher['email'] . ";";
                                }
                            }
                            if (!empty($emailstring)) {
                                $emailstring = trim($emailstring, ';');
                                $loggedinuseremail = $USER->email;
                                $mailtolink = str_replace(' ', '%20', htmlspecialchars("mailto:$loggedinuseremail?bcc=$emailstring",
                                    ENT_QUOTES));
                            }
                        }
                    }

                    $category['sports'][] = [
                        'name' => $sport,
                        'editsubstitutionspool' => $editsubstitutionspool,
                        'viewsubstitutionspool' => $viewsubstitutionspool,
                        'substitutionteachers' => $substitutionteachers,
                        'mailtolink' => $mailtolink ?? null,
                        'emailstring' => $emailstring ?? null,
                        'description' => $description,
                        'id' => $cmid,
                        'table' => format_text('[allekurseliste sort=1 search=1 lazy=1 category="' . $sport . '"]'),
                    ];
                }
            }
            $data['categories'][] = $category;
        }
        return $OUTPUT->render_from_template('local_musi/shortcodes_rendersportcategories', $data);
    }

    /**
     * Prints out list of bookingoptions.
     * Arguments can be 'category' or 'perpage'.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return void
     */
    public static function userinformation($shortcode, $args = [], $content, $env, $next) {

        global $USER, $PAGE;

        self::fix_args($args);

        $userid = $args['userid'] ?? 0;
        // If the id argument was not passed on, we have a fallback in the connfig.
        $context = context_system::instance();
        if (empty($userid) && has_capability('local/shopping_cart:cashier', $context)) {
            $userid = shopping_cart::return_buy_for_userid();
        } else if (!has_capability('local/shopping_cart:cashier', $context)) {
            $userid = $USER->id;
        }

        if (!isset($args['fields'])) {

            $args['fields'] = '';
        }

        $data = new userinformation($userid, $args['fields']);
        $output = $PAGE->get_renderer('local_musi');
        return $output->render_userinformation($data);
    }

    /**
     * Prints out list of bookingoptions.
     * Arguments can be 'category' or 'perpage'.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return void
     */
    public static function allcourseslist($shortcode, $args = [], $content, $env, $next) {

        self::fix_args($args);

        $booking = self::get_booking($args);

        if (!isset($args['category']) || !$category = ($args['category'])) {
            $category = '';
        }

        if (!isset($args['image']) || !$showimage = ($args['image'])) {
            $showimage = false;
        }

        if (empty($args['countlabel'])) {
            $args['countlabel'] = false;
        }

        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* if (!isset($args['infinitescrollpage']) || !$infinitescrollpage = ($args['infinitescrollpage'])) {
            $infinitescrollpage = 20;
        } */

        if (
            !isset($args['perpage'])
            || !is_int((int)$args['perpage'])
            || !$perpage = ($args['perpage'])
        ) {
            $perpage = 1000;
        }

        $table = self::inittableforcourses($booking);

        $table->showcountlabel = $args['countlabel'];
        $wherearray = ['bookingid' => (int)$booking->id];

        if (!empty($category)) {
            $wherearray['sport'] = $category;
        };

        // If we want to find only the teacher relevant options, we chose different sql.
        if (isset($args['teacherid']) && (is_int((int)$args['teacherid']))) {
            $wherearray['teacherobjects'] = '%"id":' . $args['teacherid'] . ',%';
            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray);
        } else {

            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray);
        }

        $table->set_filter_sql($fields, $from, $where, $filter, $params);

        $table->use_pages = false;

        if ($showimage !== false) {
            $table->set_tableclass('cardimageclass', 'pr-0 pl-1');

            $table->add_subcolumns('cardimage', ['image']);
        }

        self::set_table_options_from_arguments($table, $args);
        self::generate_table_for_list($table, $args);

        $table->cardsort = true;

        // This allows us to use infinite scrolling, No pages will be used.
        $table->infinitescroll = 60;

        $table->tabletemplate = 'local_musi/table_list';

        // If we find "nolazy='1'", we return the table directly, without lazy loading.
        if (!empty($args['lazy'])) {

            list($idstring, $encodedtable, $out) = $table->lazyouthtml($perpage, true);

            return $out;
        }

        $out = $table->outhtml($perpage, true);

        return $out;
    }

    /**
     * Prints out grid of bookingoptions.
     * Arguments can be 'category' or 'perpage'.
     * Templates table_grid...
     * Styles Tablegrid
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return void
     */
    public static function allcoursesgrid($shortcode, $args = [], $content, $env, $next) {

        self::fix_args($args);

        $booking = self::get_booking($args);

        if (!isset($args['category']) || !$category = ($args['category'])) {
            $category = '';
        }

        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* if (!isset($args['infinitescrollpage']) || !$infinitescrollpage = ($args['infinitescrollpage'])) {
            $infinitescrollpage = 20;
        } */

        if (
            !isset($args['perpage'])
            || !is_int((int)$args['perpage'])
            || !$perpage = ($args['perpage'])
        ) {
            $perpage = 1000;
        }

        $table = self::inittableforcourses($booking);

        $wherearray = ['bookingid' => (int)$booking->id];

        if (!empty($category)) {
            $wherearray['sport'] = $category;
        };

        // If we want to find only the teacher relevant options, we chose different sql.
        if (isset($args['teacherid']) && (is_int((int)$args['teacherid']))) {
            $wherearray['teacherobjects'] = '%"id":' . $args['teacherid'] . ',%';
            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray);
        } else {

            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray);
        }

        $table->set_filter_sql($fields, $from, $where, $filter, $params);

        $table->use_pages = false;

        $table->define_cache('mod_booking', 'bookingoptionstable');

        $table->add_subcolumns('entrybody', ['text', 'dayofweektime', 'sport', 'teacher', 'location', 'bookings', 'minanswers',
            'price', 'action']);

        // This avoids showing all keys in list view.
        $table->add_classes_to_subcolumns('entrybody', ['columnkeyclass' => 'd-md-none']);

        $table->add_classes_to_subcolumns('entrybody', ['columnclass' => 'grid-area-text'], ['text']);

        $table->add_classes_to_subcolumns('entrybody', ['columnclass' => 'grid-area-dayofweektime'], ['dayofweektime']);
        $table->add_classes_to_subcolumns('entrybody', ['columniclassbefore' => 'fa fa-clock-o'], ['dayofweektime']);

        $table->add_classes_to_subcolumns('entrybody', ['columnclass' => 'grid-area-sport'], ['sport']);
        $table->add_classes_to_subcolumns('entrybody', ['columnvalueclass' => 'sport-badge bg-info text-light'], ['sport']);

        $table->add_classes_to_subcolumns('entrybody', ['columnclass' => 'grid-area-teacher'], ['teacher']);

        $table->add_classes_to_subcolumns('entrybody', ['columnclass' => 'grid-area-location'], ['location']);
        $table->add_classes_to_subcolumns('entrybody', ['columniclassbefore' => 'fa fa-map-marker'], ['location']);

        $table->add_classes_to_subcolumns('entrybody', ['columnclass' => 'grid-area-booking'], ['bookings']);

        $table->add_classes_to_subcolumns('entrybody', ['columnclass' => 'grid-area-minanswers'], ['minanswers']);

        $table->add_classes_to_subcolumns('entrybody', ['columnclass' => 'grid-area-price'], ['price']);

        // Override naming for columns. one could use getstring for localisation here.
        $table->add_classes_to_subcolumns(
            'cardbody',
            ['keystring' => get_string('tableheader_text', 'booking')],
            ['text']
        );
        $table->add_classes_to_subcolumns(
            'cardbody',
            ['keystring' => get_string('tableheader_teacher', 'booking')],
            ['teacher']
        );
        $table->add_classes_to_subcolumns(
            'cardbody',
            ['keystring' => get_string('tableheader_maxanswers', 'booking')],
            ['maxanswers']
        );
        $table->add_classes_to_subcolumns(
            'cardbody',
            ['keystring' => get_string('tableheader_maxoverbooking', 'booking')],
            ['maxoverbooking']
        );
        $table->add_classes_to_subcolumns(
            'cardbody',
            ['keystring' => get_string('tableheader_coursestarttime', 'booking')],
            ['coursestarttime']
        );
        $table->add_classes_to_subcolumns(
            'cardbody',
            ['keystring' => get_string('tableheader_courseendtime', 'booking')],
            ['courseendtime']
        );

        $table->is_downloading('', 'List of booking options');

        self::set_table_options_from_arguments($table, $args);

        // This allows us to use infinite scrolling, No pages will be used.
        $table->infinitescroll = 100;

        $table->tabletemplate = 'local_musi/table_grid_list';

        // If we find "nolazy='1'", we return the table directly, without lazy loading.
        if (!empty($args['lazy'])) {

            list($idstring, $encodedtable, $out) = $table->lazyouthtml($perpage, true);

            return $out;
        }

        $out = $table->outhtml($perpage, true);

        return $out;
    }

    /**
     * Prints out list of bookingoptions.
     * Arguments can be 'category' or 'perpage'.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return void
     */
    public static function allcoursescards($shortcode, $args = [], $content, $env, $next) {

        // TODO: Define capality.
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* if (!has_capability('moodle/site:config', $env->context)) {
            return '';
        } */
        self::fix_args($args);
        $booking = self::get_booking($args);

        if (!isset($args['category']) || !$category = ($args['category'])) {
            $category = '';
        }

        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* if (!isset($args['infinitescrollpage']) || !$infinitescrollpage = ($args['infinitescrollpage'])) {
            $infinitescrollpage = 20;
        } */

        if (
            !isset($args['perpage'])
            || !is_int((int)$args['perpage'])
            || !$perpage = ($args['perpage'])
        ) {
            $perpage = 1000;
        }

        $table = self::inittableforcourses($booking);

        $wherearray = ['bookingid' => (int)$booking->id];

        if (!empty($category)) {
            $wherearray['sport'] = $category;
        };

        // If we want to find only the teacher relevant options, we chose different sql.
        if (isset($args['teacherid']) && (is_int((int)$args['teacherid']))) {
            $wherearray['teacherobjects'] = '%"id":' . $args['teacherid'] . ',%';
            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray);
        } else {

            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray);
        }

        $table->set_filter_sql($fields, $from, $where, $filter, $params);

        $table->use_pages = false;

        self::generate_table_for_cards($table, $args);

        self::set_table_options_from_arguments($table, $args);

        // This allows us to use infinite scrolling, No pages will be used.
        $table->infinitescroll = 30;

        $table->tabletemplate = 'local_musi/table_card';

        // If we find "nolazy='1'", we return the table directly, without lazy loading.
        if (!empty($args['lazy'])) {

            list($idstring, $encodedtable, $out) = $table->lazyouthtml($perpage, true);

            return $out;
        }

        $out = $table->outhtml($perpage, true);

        return $out;
    }


    /**
     * Prints out list of bookingoptions.
     * Arguments can be 'id', 'category' or 'perpage'.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return void
     */
    public static function mycoursescards($shortcode, $args = [], $content, $env, $next) {

        global $USER;
        self::fix_args($args);
        $booking = self::get_booking($args);

        if (!isset($args['category']) || !$category = ($args['category'])) {
            $category = '';
        }

        if (
            !isset($args['perpage'])
            || !is_int((int)$args['perpage'])
            || !$perpage = ($args['perpage'])
        ) {
            $perpage = 1000;
        }

        $table = self::inittableforcourses($booking);

        $wherearray = ['bookingid' => (int)$booking->id];

        if (!empty($category)) {
            $wherearray['sport'] = $category;
        };

        // If we want to find only the teacher relevant options, we chose different sql.
        if (isset($args['teacherid']) && (is_int((int)$args['teacherid']))) {
            $wherearray['teacherobjects'] = '%"id":' . $args['teacherid'] . ',%';
            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray, $USER->id);
        } else {

            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray, $USER->id);
        }

        $table->set_filter_sql($fields, $from, $where, $filter, $params);

        $table->use_pages = false;

        self::generate_table_for_cards($table, $args);

        self::set_table_options_from_arguments($table, $args);

        $table->cardsort = true;

        // This allows us to use infinite scrolling, No pages will be used.
        $table->infinitescroll = 30;

        $table->tabletemplate = 'local_musi/table_card';

        // If we find "nolazy='1'", we return the table directly, without lazy loading.
        if (!empty($args['lazy'])) {

            list($idstring, $encodedtable, $out) = $table->lazyouthtml($perpage, true);

            return $out;
        }

        $out = $table->outhtml($perpage, true);
        return $out;
    }

    /**
     * Prints out list of bookingoptions where the current user is a trainer.
     * Arguments can be 'id', 'category' or 'perpage'.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return void
     */
    public static function myteachedcoursescards($shortcode, $args = [], $content, $env, $next) {

        global $USER;
        self::fix_args($args);
        $booking = self::get_booking($args);

        if (
            !isset($args['perpage'])
            || !is_int((int)$args['perpage'])
            || !$perpage = ($args['perpage'])
        ) {
            $perpage = 1000;
        }

        $table = self::inittableforcourses($booking);

        // We want to check for the currently logged in user...
        // ... if (s)he is teaching courses.
        $teacherid = $USER->id;

        // This is the important part: We only filter for booking options where the current user is a teacher!
        // Also we only want to show courses for the currently set booking instance (semester instance).
        list($fields, $from, $where, $params, $filter) =
            booking::get_all_options_of_teacher_sql($teacherid, (int)$booking->id);

        $table->set_filter_sql($fields, $from, $where, $filter, $params);

        $table->use_pages = false;

        self::generate_table_for_cards($table, $args);

        self::set_table_options_from_arguments($table, $args);

        $table->cardsort = true;

        // This allows us to use infinite scrolling, No pages will be used.
        $table->infinitescroll = 30;

        $table->tabletemplate = 'local_musi/table_card';

        // If we find "nolazy='1'", we return the table directly, without lazy loading.
        if (!empty($args['lazy'])) {

            list($idstring, $encodedtable, $out) = $table->lazyouthtml($perpage, true);

            return $out;
        }

        $out = $table->outhtml($perpage, true);
        return $out;
    }

    /**
     * Prints out list of my booked bookingoptions.
     * Arguments can be 'category' or 'perpage'.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return void
     */
    public static function mycourseslist($shortcode, $args = [], $content, $env, $next) {

        global $USER;
        self::fix_args($args);
        $booking = self::get_booking($args);

        if (!isset($args['category']) || !$category = ($args['category'])) {
            $category = '';
        }

        if (
            !isset($args['perpage'])
            || !is_int((int)$args['perpage'])
            || !$perpage = ($args['perpage'])
        ) {
            $perpage = 1000;
        }

        if (empty($args['countlabel'])) {
            $args['countlabel'] = false;
        }

        $table = self::inittableforcourses($booking);

        $table->showcountlabel = $args['countlabel'];
        $wherearray = ['bookingid' => (int)$booking->id];

        if (!empty($category)) {
            $wherearray['sport'] = $category;
        };

        // If we want to find only the teacher relevant options, we chose different sql.
        if (isset($args['teacherid']) && (is_int((int)$args['teacherid']))) {
            $wherearray['teacherobjects'] = '%"id":' . $args['teacherid'] . ',%';
            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray, $USER->id);
        } else {

            list($fields, $from, $where, $params, $filter) =
                booking::get_options_filter_sql(0, 0, '', null, $booking->context, [], $wherearray, $USER->id);
        }

        $table->set_filter_sql($fields, $from, $where, $filter, $params);

        $table->use_pages = false;

        self::generate_table_for_list($table, $args);;

        self::set_table_options_from_arguments($table, $args);

        $table->cardsort = true;

        // This allows us to use infinite scrolling, No pages will be used.
        $table->infinitescroll = 60;

        $table->tabletemplate = 'local_musi/table_list';

        // If we find "nolazy='1'", we return the table directly, without lazy loading.
        if (!empty($args['lazy'])) {

            list($idstring, $encodedtable, $out) = $table->lazyouthtml($perpage, true);

            return $out;
        }

        $out = $table->outhtml($perpage, true);
        return $out;
    }

    /**
     * Prints out user dashboard overview as cards.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return void
     */
    public static function userdashboardcards($shortcode, $args = [], $content, $env, $next) {
        global $DB, $PAGE, $USER;
        self::fix_args($args);
        // If the id argument was not passed on, we have a fallback in the connfig.
        if (!isset($args['id'])) {
            $args['id'] = get_config('local_musi', 'shortcodessetinstance');
        }

        // To prevent misconfiguration, id has to be there and int.
        if (!(isset($args['id']) && $args['id'] && is_int((int)$args['id']))) {
            return 'Set id of booking instance';
        }

        if (!$booking = singleton_service::get_instance_of_booking_by_cmid($args['id'])) {
            return 'Couldn\'t find right booking instance ' . $args['id'];
        }

        $user = $USER;

        $booked = $booking->get_user_booking_count($USER);
        $asteacher = $DB->get_fieldset_select('booking_teachers', 'optionid',
            "userid = {$USER->id} AND bookingid = $booking->id ");
        $credits = shopping_cart_credits::get_balance($USER->id);

        $data['booked'] = $booked;
        $data['teacher'] = count($asteacher);
        $data['credits'] = $credits[0];

        $output = $PAGE->get_renderer('local_musi');
        return $output->render_user_dashboard_overview($data);

    }

    /**
     * Prints out all teachers as cards.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return void
     */
    public static function allteacherscards($shortcode, $args = [], $content, $env, $next) {
        global $DB, $PAGE;
        self::fix_args($args);
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
        return $output->render_allteacherspage($data);
    }

    /**
     * Undocumented function
     *
     * @param [type] $shortcode
     * @param [type] $args
     * @param [type] $content
     * @param [type] $env
     * @param [type] $next
     * @return array
     */
    private static function return_base_table($shortcode, $args = [], $content, $env, $next) {

        // TODO: Define capality.
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* if (!has_capability('moodle/site:config', $env->context)) {
            return '';
        } */
        self::fix_args($args);
        $booking = self::get_booking($args);

        if (!isset($args['category']) || !$category = ($args['category'])) {
            $category = '';
        }

        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* if (!isset($args['infinitescrollpage']) || !$infinitescrollpage = ($args['infinitescrollpage'])) {
            $infinitescrollpage = 20;
        } */

        if (
            !isset($args['perpage'])
            || !is_int((int)$args['perpage'])
            || !$perpage = ($args['perpage'])
        ) {
            $perpage = 1000;
        }

        $table = self::inittableforcourses($booking);

        $wherearray = ['bookingid' => (int)$booking->id];

        if (!empty($category)) {
            $wherearray['sport'] = $category;
        };

        $table->use_pages = false;

        $table->define_cache('mod_booking', 'bookingoptionstable');

        $table->add_subcolumns('itemcategory', ['sport']);
        $table->add_subcolumns('itemday', ['dayofweektime']);
        $table->add_subcolumns('cardimage', ['image']);
        $table->add_subcolumns('optioninvisible', ['invisibleoption']);

        $table->add_subcolumns('cardbody', ['invisibleoption', 'sport', 'text', 'teacher']);
        $table->add_classes_to_subcolumns('cardbody', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns(
            'cardbody',
            ['columnvalueclass' => 'shortcodes_option_info_invisible'],
            ['invisibleoption']
        );
        $table->add_classes_to_subcolumns('cardbody', ['columnvalueclass' => 'h6'], ['sports']);
        $table->add_classes_to_subcolumns('cardbody', ['columnvalueclass' => 'm-0 mb-1 h5'], ['text']);

        $table->add_subcolumns('cardlist', ['dayofweektime', 'location', 'bookings', 'minanswers', 'botags']);
        $table->add_classes_to_subcolumns('cardlist', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-fw fa-clock-o mr-1'], ['dayofweektime']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-fw fa-map-marker mr-1'], ['location']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-fw fa-users mr-1'], ['bookings']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-fw fa-arrow-up mr-1'], ['minanswers']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-fw fa-tag mr-1'], ['botags']);

        $table->add_subcolumns('cardfooter', ['price']);
        $table->add_classes_to_subcolumns('cardfooter', ['columnkeyclass' => 'd-none']);

        $table->set_tableclass('cardimageclass', 'w-100');

        $table->is_downloading('', 'List of booking options');

        self::set_table_options_from_arguments($table, $args);

        // This allows us to use infinite scrolling, No pages will be used.
        $table->infinitescroll = 100;

        $table->tabletemplate = 'local_musi/nolazytable';

        return [$table, $booking, $category];
    }

    private static function inittableforcourses($booking) {

        global $PAGE, $USER;

        $tablename = bin2hex(random_bytes(12));

        // It's important to have the baseurl defined, we use it as a return url at one point.
        $baseurl = $PAGE->url ?? new moodle_url('');

        // On the cashier page, we want to buy for different users...
        // ...else we always want to buy for ourselves.
        if (strpos($baseurl->out(), "cashier.php") !== false) {
            $buyforuserid = null;
        } else {
            $buyforuserid = $USER->id;
        }

        $table = new musi_table($tablename, $booking, $buyforuserid);

        $table->define_baseurl($baseurl->out());
        $table->cardsort = true;
        // Without defining sorting won't work!
        $table->define_columns(['titleprefix']);
        return $table;
    }

    private static function define_filtercolumns(&$table) {
        $table->define_filtercolumns([
            'id', 'sport' => [
                'localizedname' => get_string('sport', 'local_musi')
            ], 'dayofweek' => [
                'localizedname' => get_string('dayofweek', 'local_musi'),
                'monday' => get_string('monday', 'mod_booking'),
                'tuesday' => get_string('tuesday', 'mod_booking'),
                'wednesday' => get_string('wednesday', 'mod_booking'),
                'thursday' => get_string('thursday', 'mod_booking'),
                'friday' => get_string('friday', 'mod_booking'),
                'saturday' => get_string('saturday', 'mod_booking'),
                'sunday' => get_string('sunday', 'mod_booking')
            ],  'location' => [
                'localizedname' => get_string('location', 'mod_booking')
            ],  'botags' => [
                'localizedname' => get_string('tags', 'core')
            ]
        ]);
    }

    private static function get_booking($args) {
        self::fix_args($args);
        // If the id argument was not passed on, we have a fallback in the connfig.
        if (!isset($args['id'])) {
            $args['id'] = get_config('local_musi', 'shortcodessetinstance');
        }

        // To prevent misconfiguration, id has to be there and int.
        if (!(isset($args['id']) && $args['id'] && is_int((int)$args['id']))) {
            return 'Set id of booking instance';
        }

        if (!$booking = singleton_service::get_instance_of_booking_by_cmid($args['id'])) {
            return 'Couldn\'t find right booking instance ' . $args['id'];
        }

        return $booking;
    }

    private static function set_table_options_from_arguments(&$table, $args) {
        self::fix_args($args);

        /** @var musi_table $table */
        $table->set_display_options($args);

        if (!empty($args['filter'])) {
            self::define_filtercolumns($table);
        }

        if (!empty($args['search'])) {
            $table->define_fulltextsearchcolumns([
                'titleprefix', 'text', 'sport', 'description', 'location',
                'teacherobjects', 'botags']);
        }

        if (!empty($args['sort'])) {
            $table->define_sortablecolumns([
                'titleprefix' => get_string('titleprefix', 'local_musi'),
                'text' => get_string('coursename', 'local_musi'),
                'sport' => get_string('sport', 'local_musi'),
                'location' => get_string('location', 'local_musi'),
            ]);
        }

        if (!empty($args['sortby'])) {
            $defaultorder = SORT_ASC; // Default.
            if (!empty($args['sortorder'])) {
                if (strtolower($args['sortorder']) === "desc") {
                    $defaultorder = SORT_DESC;
                }
            }
            $table->sortable(true, $args['sortby'], $defaultorder);
        } else {
            $table->sortable(true, 'text', SORT_ASC);
        }
    }

    private static function generate_table_for_cards(&$table, $args) {
        self::fix_args($args);
        $table->define_cache('mod_booking', 'bookingoptionstable');

        // We define it here so we can pass it with the mustache template.
        $table->add_subcolumns('optionid', ['id']);

        $table->add_subcolumns('itemcategory', ['sport']);
        $table->add_subcolumns('cardimage', ['image']);
        $table->add_subcolumns('optioninvisible', ['invisibleoption']);

        $table->add_subcolumns('cardbody', ['action', 'invisibleoption', 'sport', 'text', 'botags']);
        $table->add_classes_to_subcolumns('cardbody', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('cardbody', ['columnvalueclass' => 'float-right m-1'], ['action']);
        $table->add_classes_to_subcolumns('cardbody', ['columnvalueclass' => 'font-size-sm'], ['botags']);
        $table->add_classes_to_subcolumns(
            'cardbody',
            ['columnvalueclass' => 'text-center shortcodes_option_info_invisible'],
            ['invisibleoption']
        );
        $table->add_classes_to_subcolumns('cardbody', ['columnvalueclass' => 'text-secondary'], ['sport']);
        $table->add_classes_to_subcolumns('cardbody', ['columnvalueclass' => 'm-0 mt-1 mb-1 h5'], ['text']);

        $subcolumns = ['teacher', 'dayofweektime', 'location', 'institution', 'bookings'];
        if (!empty($args['showminanswers'])) {
            $subcolumns[] = 'minanswers';
        }

        $table->add_subcolumns('cardlist', $subcolumns);
        $table->add_classes_to_subcolumns('cardlist', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('cardlist', ['columnvalueclass' => 'text-secondary']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'text-secondary fa-fw']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-map-marker'], ['location']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-building-o'], ['institution']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-clock-o'], ['dayofweektime']);
        $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-users'], ['bookings']);
        if (!empty($args['showminanswers'])) {
            $table->add_classes_to_subcolumns('cardlist', ['columniclassbefore' => 'fa fa-arrow-up'], ['minanswers']);
        }

        // Set additional descriptions.
        $table->add_classes_to_subcolumns('cardlist', ['columnalt' => get_string('teacheralt', 'local_musi')], ['teacher']);
        $table->add_classes_to_subcolumns('cardlist', ['columnalt' => get_string('locationalt', 'local_musi')], ['location']);
        $table->add_classes_to_subcolumns('cardlist', ['columnalt' => get_string('dayofweekalt', 'local_musi')], ['dayofweektime']);
        $table->add_classes_to_subcolumns('cardlist', ['columnalt' => get_string('bookingsalt', 'local_musi')], ['bookings']);
        $table->add_classes_to_subcolumns('cardimage', ['cardimagealt' => get_string('imagealt', 'local_musi')], ['image']);

        $table->add_subcolumns('cardfooter', ['course', 'price']);
        $table->add_classes_to_subcolumns('cardfooter', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('cardfooter', ['columnclass' => 'theme-text-color bold '], ['price']);
        $table->set_tableclass('cardimageclass', 'w-100');

        $table->is_downloading('', 'List of booking options');
    }

    private static function generate_table_for_list(&$table, $args) {
        self::fix_args($args);
        $subcolumnsleftside = ['text'];
        $subcolumnsinfo = ['teacher', 'dayofweektime', 'location', 'institution', 'bookings'];

        // Check if we should add the description.
        if (get_config('local_musi', 'shortcodelists_showdescriptions')) {
            $subcolumnsleftside[] = 'description';
        }

        if (!empty($args['showminanswers'])) {
            $subcolumnsinfo[] = 'minanswers';
        }

        $table->define_cache('mod_booking', 'bookingoptionstable');

        // We define it here so we can pass it with the mustache template.
        $table->add_subcolumns('optionid', ['id']);

        $table->add_subcolumns('top', ['sport', 'action']);
        $table->add_subcolumns('leftside', $subcolumnsleftside);
        $table->add_subcolumns('info', $subcolumnsinfo);
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* $table->add_subcolumns('footer', ['botags']); */

        $table->add_subcolumns('info', $subcolumnsinfo);
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* $table->add_subcolumns('footer', ['botags']); */
        $table->add_subcolumns('rightside', ['botags', 'invisibleoption', 'course', 'price']);

        $table->add_classes_to_subcolumns('top', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('top', ['columnclass' => 'text-left col-md-8'], ['sport']);
        $table->add_classes_to_subcolumns('top', ['columnvalueclass' =>
            'sport-badge rounded-sm text-gray-800 mt-2'], ['sport']);
        $table->add_classes_to_subcolumns('top', ['columnclass' => 'text-right col-md-2 position-relative pr-0'], ['action']);

        $table->add_classes_to_subcolumns('leftside', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('leftside', ['columnclass' => 'text-left mt-1 mb-1 h3 col-md-auto'], ['text']);
        if (get_config('local_musi', 'shortcodelists_showdescriptions')) {
            $table->add_classes_to_subcolumns('leftside', ['columnclass' => 'text-left mt-1 mb-3 col-md-auto'], ['description']);
        }
        $table->add_classes_to_subcolumns('info', ['columnkeyclass' => 'd-none']);
        $table->add_classes_to_subcolumns('info', ['columnclass' => 'text-left text-secondary font-size-sm pr-2']);
        $table->add_classes_to_subcolumns('info', ['columnvalueclass' => 'd-flex'], ['teacher']);
        $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-clock-o'], ['dayofweektime']);
        $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-map-marker'], ['location']);
        $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-building-o'], ['institution']);
        $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-ticket'], ['bookings']);
        if (!empty($args['showminanswers'])) {
            $table->add_classes_to_subcolumns('info', ['columniclassbefore' => 'fa fa-arrow-up'], ['minanswers']);
        }

        // Set additional descriptions.
        $table->add_classes_to_subcolumns('info', ['columnalt' => get_string('teacheralt', 'local_musi')], ['teacher']);
        $table->add_classes_to_subcolumns('info', ['columnalt' => get_string('dayofweekalt', 'local_musi')], ['dayofweektime']);
        $table->add_classes_to_subcolumns('info', ['columnalt' => get_string('locationalt', 'local_musi')], ['location']);
        $table->add_classes_to_subcolumns('info', ['columnalt' => get_string('bookingsalt', 'local_musi')], ['bookings']);

        $table->add_classes_to_subcolumns('rightside',
            ['columnvalueclass' => 'text-right mb-auto align-self-end shortcodes_option_info_invisible '],
            ['invisibleoption']);
        $table->add_classes_to_subcolumns('rightside', ['columnclass' => 'text-right mb-auto align-self-end '], ['botags']);
        $table->add_classes_to_subcolumns('rightside', ['columnclass' =>
            'text-right mt-auto w-100 align-self-end theme-text-color bold '], ['price']);

        // Override naming for columns. one could use getstring for localisation here.
        $table->add_classes_to_subcolumns(
            'top',
            ['keystring' => get_string('tableheader_text', 'booking')],
            ['sport']
        );
        $table->add_classes_to_subcolumns(
            'leftside',
            ['keystring' => get_string('tableheader_text', 'booking')],
            ['text']
        );
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*
        $table->add_classes_to_subcolumns(
            'leftside',
            ['keystring' => get_string('tableheader_teacher', 'booking')],
            ['teacher']
        );
        */
        $table->add_classes_to_subcolumns(
            'info',
            ['keystring' => get_string('tableheader_maxanswers', 'booking')],
            ['maxanswers']
        );
        $table->add_classes_to_subcolumns(
            'info',
            ['keystring' => get_string('tableheader_maxoverbooking', 'booking')],
            ['maxoverbooking']
        );
        $table->add_classes_to_subcolumns(
            'info',
            ['keystring' => get_string('tableheader_coursestarttime', 'booking')],
            ['coursestarttime']
        );
        $table->add_classes_to_subcolumns(
            'info',
            ['keystring' => get_string('tableheader_courseendtime', 'booking')],
            ['courseendtime']
        );

        $table->is_downloading('', 'List of booking options');
    }

    /**
     * Helper function to remove quotation marks from args.
     * @param array &$args reference to arguments array
     */
    private static function fix_args( &$args) {
        if(!empty($args)) {
            foreach ($args as $key => &$value) {
                // Get rid of quotation marks.
                $value = str_replace('"', '', $value);
                $value = str_replace("'", "", $value);
            }
        }
    }
}
