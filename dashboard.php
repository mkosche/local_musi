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
 * Add dates to option.
 *
 * @package local_musi
 * @copyright 2022 Georg Maißer <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');

// No guest autologin.
require_login(0, false);

global $DB, $PAGE, $OUTPUT, $USER;

if (!$context = context_system::instance()) {
    throw new moodle_exception('badcontext');
}

// Check if optionid is valid.
$PAGE->set_context($context);
$PAGE->set_url('/local/musi/dashboard.php');

if ((has_capability('mod/booking:updatebooking', $context) || has_capability('mod/booking:addeditownoption', $context)) == false) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('accessdenied', 'mod_booking'), 4);
    echo get_string('nopermissiontoaccesspage', 'mod_booking');
    echo $OUTPUT->footer();
    die();
}

$title = get_string('pluginname', 'local_musi');
$PAGE->navbar->add($title);
$PAGE->set_title(format_string($title));
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');
$PAGE->add_body_class('local_musi-dashboard');

echo $OUTPUT->header();

$PAGE->requires->js_call_amd(
    'local_musi/botagsmodal',
    'init',
    [
        '[data-action=openbotagsmodal]',
        get_string('editbotags', 'local_musi')
    ]
);



// Add dynamic tabs to our page.
$main_tabs = new local_musi\output\main_tabs();
$tabsoutput = new core\output\dynamic_tabs($main_tabs->get_main_tabs());
$tabs = $tabsoutput->export_for_template($OUTPUT);

// Render the page content via mustache templates.
//$output = $PAGE->get_renderer('local_musi');
//$data = new dashboard();
echo $OUTPUT->render_from_template('local_musi/dynamic_sidebar_menu',$tabs);

echo $OUTPUT->footer();
