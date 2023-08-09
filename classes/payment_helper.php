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

/**
 * Helper functions for payment stuff.
 *
 * @package local_musi
 * @copyright 2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Bernhard Fischer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payment_helper {

    /**
     * Helper function to get name of the orderid column for each gateway table.
     * We currently do not need it here, but we'll need it later, so keep it!
     *
     * @param string $gwname the name of the gateway
     * @return string the name of the orderid column
     */
    public static function get_name_of_orderid_column(string $gwname): string {
        switch ($gwname) {
            case 'paypal':
                return 'pp_orderid';
            case 'mpay24':
                return 'mpay24_orderid';
            case 'payunity':
                return 'pu_orderid';
        }
    }

}
