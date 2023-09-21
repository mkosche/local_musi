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
use local_shopping_cart\event\payment_rebooked;
use stdClass;

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

    /**
     * Observer for the payment_rebooked event
     */
    public static function payment_rebooked(payment_rebooked $event) {
        global $DB;

        // Little hack to prevent posisble problems with the second observer function in shopping_cart.
        sleep(2);

        $identifier = $event->other['identifier'];
        $userid = $event->other['userid'];
        $annotation = $event->other['annotation'];
        $accountid = $DB->get_field('payment_gateways', 'accountid', ['gateway' => 'payunity', 'enabled' => '1']);

        // Let's use this flag to determine if it's a MUSI OrderID for which we might need special treatment.
        $ismusiorderid = false;
        $ispayunityorderid = false;
        $useidentifierfrommusiorderid = false;

        $annotation = trim($annotation);
        $orderidelements = explode(' ', $annotation);

        // The last word of the string might store the payment brand.
        // If we do not find it, we use UNKNOWN as default.
        $lastelement = end($orderidelements);
        switch ($lastelement) {
            case 'VC':
            case 'VISA':
                array_pop($orderidelements); // Remove the last element.
                $paymentbrand = 'VC';
                $pboriginal = 'VISA';
                break;
            case 'MC':
            case 'MASTER':
                array_pop($orderidelements); // Remove the last element.
                $paymentbrand = 'MC';
                $pboriginal = 'MASTER';
                break;
            case 'EP':
            case 'EPS':
                array_pop($orderidelements); // Remove the last element.
                $paymentbrand = 'EP';
                $pboriginal = 'EPS';
                break;
            case 'UK':
            case 'UNKNOWN':
                array_pop($orderidelements); // Remove the last element.
                $paymentbrand = 'UK';
                $pboriginal = 'UNKNOWN';
                break;
            default:
                // In this case we keep the last element of the array!
                $paymentbrand = 'UK';
                $pboriginal = 'UNKNOWN';
                break;
        }
        // Now we can restore the annotation string without the payment brand.
        $annotation = implode(" ", $orderidelements);

        // Find out if it's a MUSI OrderID or just some annotation.
        // Example MUSI OrderID: 1000105 16 K327 79.00 82.00 1686577767 VISA.
        // Note: VISA, MASTER, UNKNOWN or EPS can be appended at the end - they are actually not part of the OrderID.
        if (
            count($orderidelements) > 5 &&
            substr($orderidelements[2], 0, 1) == "K" &&
            is_number(substr($orderidelements[2], 1, 1)) &&
            is_number($orderidelements[0])
        ) {
            $ismusiorderid = true;
        } else if (preg_match("/[A-Z0-9]+\.[a-z0-9\-]+/", $annotation)) {
            $ispayunityorderid = true;
        }

        // Now we retrieve the amount from the MUSI order id.
        if ($ismusiorderid) {
            // Example order id: 1000105 16 K327 79.00 82.00 1686577767 - we already removed the payment brand with array_pop.
            // So the amount is at the second last element.
            $amount = (float) $orderidelements[count($orderidelements) - 2];

            // Do not do this! Let's work with the right identifier!
            // It might be necessary to change the identifier, so it corresponds with the MUSI order id.
            /* if (is_number($orderidelements[0])) {
                $musiidentifier = $orderidelements[0];
                // We have ot check if it already exists in ledger.
                if (!$DB->get_records('local_shopping_cart_ledger', ['identifier' => $musiidentifier])) {
                    $useidentifierfrommusiorderid = true;
                    /* If it does not exist in ledger, we can safely change the created identifier
                    to the one from the MUSI order id. */
                    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                    /* if ($ledgerrecords = $DB->get_records('local_shopping_cart_ledger', [
                        'payment' => PAYMENT_METHOD_CASHIER_MANUAL,
                        'identifier' => $identifier,
                        'userid' => $userid,
                    ])) {
                        foreach ($ledgerrecords as $ledgerrecord) {
                            $ledgerrecord->identifier = $musiidentifier;
                            // Usually, ledger should never be updated. But here we have to.
                            $DB->update_record('local_shopping_cart_ledger', $ledgerrecord);
                        }
                    }
                }
            } */

        } else if ($ispayunityorderid) {
            // Initialize with 0.
            $amount = 0.0;
            // In this case, we have to calculate the amount from ledger.
            // In any case, we store the annotation into the ledger table.
            if ($ledgerrecords = $DB->get_records('local_shopping_cart_ledger', [
                'payment' => PAYMENT_METHOD_CASHIER_MANUAL,
                'identifier' => $identifier,
                'userid' => $userid,
            ])) {
                foreach ($ledgerrecords as $ledgerrecord) {
                    $amount += $ledgerrecord->price;
                }
            }
        }
        // Else it's just an annotation, so we do not write into payment tables at all!
        if ($ismusiorderid || $ispayunityorderid) {

            // Let's work with the correct identifier for now!
            // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
            /* $identifier = $useidentifierfrommusiorderid ? $musiidentifier : $identifier; */

            if (!$existingpaymentrecord = $DB->get_record('payments', [
                'component' => 'local_shopping_cart',
                'itemid' => $identifier,
                'userid' => $userid
            ])) {
                $paymentrecord = new stdClass;
                $paymentrecord->component = 'local_shopping_cart';
                $paymentrecord->paymentarea = '';
                $paymentrecord->itemid = $identifier;
                $paymentrecord->userid = $userid;
                $paymentrecord->amount = $amount;
                $paymentrecord->currency = 'EUR';
                $paymentrecord->accountid = $accountid;
                $paymentrecord->gateway = 'payunity';
                $paymentrecord->timecreated = time();
                $paymentrecord->timemodified = time();
                $paymentid = $DB->insert_record('payments', $paymentrecord);
            } else {
                // If a payment record for the identifier already exists...
                // ... then we have to use the paymentid of this record!
                $paymentid = $existingpaymentrecord->id;
            }

            if (!$DB->get_records('paygw_payunity', [
                'paymentid' => $paymentid
            ])) {
                $payunityrecord = new stdClass;
                $payunityrecord->paymentid = $paymentid;
                $payunityrecord->pu_orderid = $annotation;
                $payunityrecord->paymentbrand = $paymentbrand;
                $payunityrecord->pboriginal = $pboriginal;
                $DB->insert_record('paygw_payunity', $payunityrecord);
            }

            if (!$DB->get_records('paygw_payunity_openorders', [
                'itemid' => $identifier
            ])) {
                $ordersrecord = new stdClass;
                $ordersrecord->tid = $annotation;
                $ordersrecord->itemid = $identifier;
                $ordersrecord->userid = $userid;
                $ordersrecord->price = $amount;
                $ordersrecord->status = 3; // Successful transaction.
                $DB->insert_record('paygw_payunity_openorders', $ordersrecord);
            }
        }
    }
}
