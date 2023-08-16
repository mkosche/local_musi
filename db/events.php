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
 * Event observers.
 *
 * @package local_musi
 * @category event
 * @copyright 2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Bernhard Fischer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\paygw_payunity\event\payment_added',
        'callback' => '\local_musi\observer::payment_added',
    ],
    [
        'eventname' => '\paygw_payunity\event\payment_completed',
        'callback' => '\local_musi\observer::payment_completed',
    ],
    [
        'eventname' => '\paygw_payunity\event\payment_successful',
        'callback' => '\local_musi\observer::payment_successful',
    ],
    [
        'eventname' => '\paygw_mpay24\event\payment_added',
        'callback' => '\local_musi\observer::payment_added',
    ],
    [
        'eventname' => '\paygw_mpay24\event\payment_completed',
        'callback' => '\local_musi\observer::payment_completed',
    ],
    [
        'eventname' => '\paygw_mpay24\event\payment_successful',
        'callback' => '\local_musi\observer::payment_successful',
    ],
    [
        'eventname' => '\local_shopping_cart\event\payment_rebooked',
        'callback' => '\local_musi\observer::payment_rebooked',
    ],
];
