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
 * @package     local_musi
 * @copyright   2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Bernhard Fischer
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_musi;

use cache_helper;

/**
 * Event observer for local_musi.
 */
class observer {

    /**
     * Observer for the payment_added event
     */
    public static function payment_added() {
        cache_helper::purge_by_event('setbackcachedpaymenttable');
    }

    /**
     * Observer for the payment_completed event
     */
    public static function payment_completed() {
        cache_helper::purge_by_event('setbackcachedpaymenttable');
    }

    /**
     * Observer for the payment_successful event
     */
    public static function payment_successful() {
        cache_helper::purge_by_event('setbackcachedpaymenttable');
    }
}
