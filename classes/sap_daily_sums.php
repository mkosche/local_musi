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
 * Functions for SAP text files (daily SAP sums for M:USI).
 *
 * @package local_musi
 * @since Moodle 4.0
 * @copyright 2022 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Bernhard Fischer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_musi;

use context_system;
use core_payment\account;
use moodle_exception;
use stored_file;

/**
 * Deals with local_shortcodes regarding booking.
 */
class sap_daily_sums {
    /**
     * Helper function to create SAP text files for M:USI.
     *
     * @param string $day formatted day to generate the SAP text file for
     * @return array [$content, $errorcontent] file content and content of the error file
     */
    public static function generate_sap_text_file_for_date(string $day): array {
        global $DB;

        $startofday = strtotime($day . ' 00:00');
        $endofday = strtotime($day . ' 24:00');

        // Get payment account from settings.
        $accountid = get_config('local_shopping_cart', 'accountid');
        $account = null;
        if (!empty($accountid)) {
            $account = new account($accountid);
        }

        // Create selects for each payment gateway.
        $colselects = [];
        // Create an array of table names for the payment gateways.
        if (!empty($account)) {
            foreach ($account->get_gateways() as $gateway) {
                $gwname = $gateway->get('gateway');
                if ($gateway->get('enabled')) {
                    $tablename = "paygw_" . $gwname;

                    $cols = $DB->get_columns($tablename);
                    // Only use tables with exactly 5 columns: id, paymentid, <pgw>_orderid, paymentbrand, pboriginal.
                    if (count($cols) <> 5) {
                        continue;
                    }

                    // Generate a select for each table.
                    // Only do this, if an orderid exists.
                    foreach ($cols as $key => $value) {
                        if (strpos($key, 'orderid') !== false) {
                            $colselects[] =
                                "SELECT
                                    $gwname.paymentid,
                                    $gwname.$key AS orderid,
                                    $gwname.paymentbrand
                                FROM {paygw_$gwname} $gwname";
                        }
                    }
                }
            }
        }

        $selectorderidpart = "";
        if (!empty($colselects)) {
            $selectorderidpart = ", pgw.orderid, pgw.paymentbrand";
            $colselectsstring = implode(' UNION ', $colselects);
            $gatewayspart = "JOIN ($colselectsstring) pgw ON p.id = pgw.paymentid";
        }

        $sql = "SELECT
                    p.id, p.itemid AS identifier, p.amount AS price, p.currency,
                    p.userid, u.lastname, u.firstname, u.email,
                    p.timecreated, p.timemodified,
                    p.gateway$selectorderidpart
                FROM {payments} p
                LEFT JOIN {user} u
                ON u.id = p.userid
                $gatewayspart
                WHERE p.timecreated BETWEEN :startofday AND :endofday
                AND p.component = 'local_shopping_cart'
                AND p.itemid <> 0";

        $params = [
            'startofday' => $startofday,
            'endofday' => $endofday
        ];

        $content = '';
        $errorcontent = '';
        if ($records = $DB->get_records_sql($sql, $params)) {
            foreach ($records as $record) {

                $currentline = '';

                // If the SAP line has errors, we need to add it to a separate file.
                $linehaserrors = false;

                // Kostenstelle.
                $sqlkostenstelle = "SELECT s1.kst
                FROM {payments} p
                JOIN {local_shopping_cart_history} h
                ON h.identifier = p.itemid
                JOIN (
                    SELECT d.instanceid AS optionid, d.value AS kst
                    FROM {customfield_field} f
                    JOIN {customfield_category} c
                    ON c.id = f.categoryid AND c.component = 'mod_booking' AND f.shortname = 'kst'
                    JOIN {customfield_data} d
                    ON d.fieldid = f.id
                ) s1
                ON s1.optionid = h.itemid
                WHERE p.itemid = :identifier AND s1.kst <> '' AND s1.kst IS NOT NULL
                LIMIT 1";
                $paramskostenstelle = ['identifier' => $record->identifier];
                $kostenstelle = $DB->get_field_sql($sqlkostenstelle, $paramskostenstelle);
                // Wenn die Kostenstelle fehlt, wird die Zeile zum Error-File hinzugefügt.
                if (empty($kostenstelle)) {
                    $linehaserrors = true;
                }
                if (empty($record->lastname)) {
                    $linehaserrors = true;
                }

                /*
                 * Mandant - 3 Stellen alphanumerisch - immer "101"
                 * Buchungskreis - 4 Stellen alphanumerisch - immer "VIE1"
                 * Währung - 3 Stellen alphanumerisch - immer "EUR"
                 * Belegart - 2 Stellen alphanumerisch - Immer "DR"
                 */
                $currentline .= "101#VIE1#EUR#DR#";
                // Referenzbelegnummer - 16 Stellen alphanumerisch.
                $currentline .= str_pad($record->identifier, 16, " ", STR_PAD_LEFT) . '#';
                // Buchungsdatum - 10 Stellen.
                $currentline .= date('d.m.Y', $record->timemodified) . '#';
                // Belegdatum - 10 Stellen.
                $currentline .= date('d.m.Y', $record->timecreated) . '#';
                // Belegkopftext - 25 Stellen alphanumerisch - in unserem Fall immer "US".
                $currentline .= str_pad('US', 25, " ", STR_PAD_LEFT) . '#';
                // Betrag - 14 Stellen alphanumerisch - Netto-Betrag.
                $renderedprice = (string) $record->price;
                $renderedprice = str_replace('.', ',', $renderedprice);
                $currentline .= str_pad($renderedprice, 14, " ", STR_PAD_LEFT) . '#';
                // Steuer rechnen - 1 Stelle alphanumerisch - immer "X".
                $currentline .= 'X#';
                /* Steuerbetrag - 14 Stellen alphanumerisch - derzeit sind keine Geschäftsfälle mit Umsatzsteuer vorgesehen,
                daher bleibt das Feld immer leer. */
                $currentline .= str_pad('', 14, " ") . '#';
                /* Werbeabgabe - 14 Stellen alphanumerisch - kein bekannter Geschäftsfall im Moment,
                daher bleibt das Feld immer leer. */
                $currentline .= str_pad('', 14, " ") . '#';
                // Buchungsschlüssel - 2 Stellen alphanumerisch - 50 - bei Rechnungen, 40 - bei Gutschriften
                // Derzeit können nur Rechnungen geloggt werden, daher immer 50.
                $currentline .= '50#';
                // Geschäftsfall-Code - 3 Stellen alphanumerisch - immer "US0".
                $currentline .= 'US0#';
                // Zahlungscode - 3 Stellen alphanumerisch.
                if (!empty($record->paymentbrand)) {
                    $currentline .= str_pad($record->paymentbrand, 3, " ", STR_PAD_LEFT) . '#';
                } else {
                    $currentline .= str_pad('', 3, " ", STR_PAD_LEFT) . '#';
                }
                // Buchungstext - 50 Stellen alphanumerisch.
                $lastname = 'UNKNOWN';
                if (!empty($record->lastname)) {
                    $lastname = self::clean_string_for_sap($record->lastname);
                }
                $buchungstext = " US $record->userid " . $lastname;
                if (strlen($buchungstext) > 50) {
                    $buchungstext = substr($buchungstext, 0, 50);
                }
                $currentline .= str_pad($buchungstext, 50, " ", STR_PAD_LEFT) . '#';
                // Zuordnung - analog "Referenzbelegnummer" - 18 Stellen alphanumerisch.
                $currentline .= str_pad($record->identifier, 18, " ", STR_PAD_LEFT) . '#';

                // Kostenstelle - 10 Stellen - leer.
                $currentline .= str_pad('', 10, " ", STR_PAD_LEFT) . '#';

                // Innenauftrag - 12 Stellen - USI Wien immer "ET592002".
                /* MUSI-350: Dort, wo jetzt immer "ET592002" steht muss der Wert
                des optionalen Feldes Statistik-Kostenstelle geschrieben werden. */
                // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                /* $currentline .= str_pad('ET592002', 12, " ", STR_PAD_LEFT) . '#'; */
                $currentline .= str_pad($kostenstelle ?? '', 12, " ", STR_PAD_LEFT) . '#';

                // Anrede - 15 Stellen.
                $currentline .= str_pad('', 15, " ", STR_PAD_LEFT) . '#';
                // Name 1 - 35 Stellen.
                $currentline .= str_pad('', 35, " ", STR_PAD_LEFT) . '#';
                // Name 2 - 35 Stellen.
                $currentline .= str_pad('', 35, " ", STR_PAD_LEFT) . '#';
                // Name 3 - 35 Stellen.
                $currentline .= str_pad('', 35, " ", STR_PAD_LEFT) . '#';
                // Name 4 - 35 Stellen.
                $currentline .= str_pad('', 35, " ", STR_PAD_LEFT) . '#';
                // Straße / Hausnummer - 35 Stellen.
                $currentline .= str_pad('', 35, " ", STR_PAD_LEFT) . '#';
                // Ort - 35 Stellen.
                $currentline .= str_pad('', 35, " ", STR_PAD_LEFT) . '#';
                // Postleitzahl - 10 Stellen.
                $currentline .= str_pad('', 10, " ", STR_PAD_LEFT) . '#';
                // Land - 2 Stellen.
                $currentline .= str_pad('', 2, " ", STR_PAD_LEFT) . '#';
                // Zahlungsbedingung - 4 Stellen.
                $currentline .= str_pad('', 4, " ", STR_PAD_LEFT) . '#';
                // ENDE: Zeilenumbruch.
                $currentline .= "\n"; // Needs to be LF, not CRLF, so we use \n.

                if (!$linehaserrors) {
                    $content .= $currentline;
                } else {
                    $errorcontent .= $currentline;
                }
            }
        }

        return [$content, $errorcontent];
    }

    /**
     * Helper function to replace special characters like "ä" with "ae" etc.
     * And convert to uppercase letters.
     *
     * @param string $stringwithspecialchars
     * @return string the cleaned output string
     */
    public static function clean_string_for_sap(string $stringwithspecialchars): string {

        // At first replace special chars.
        $umlaute = [
            "/ß/" ,
            "/ä/", "/à/", "/á/", "/â/", "/æ/", "/ã/", "/å/", "/ā/",
            "/Ä/", "/À/", "/Á/", "/Â/", "/Æ/", "/Ã/", "/Å/", "/Ā/",
            "/é/", "/è/", "/ê/", "/ë/", "/ė/",
            "/É/", "/È/", "/Ê/", "/Ë/", "/Ė/",
            "/î/", "/ï/", "/í/", "/ī/", "/ì/",
            "/Î/", "/Ï/", "/Í/", "/Ī/", "/Ì/",
            "/ö/", "/ô/", "/ò/", "/ó/", "/õ/", "/œ/", "/ø/", "/ō/",
            "/Ö/", "/Ô/", "/Ò/", "/Ó/", "/Õ/", "/Œ/", "/Ø/", "/Ō/",
            "/ü/", "/û/", "/ù/", "/ú/", "/ū/",
            "/Ü/", "/Û/", "/Ù/", "/Ú/", "/Ū/",
            "/ç/", "/ć/", "/č/",
            "/Ç/", "/Ć/", "/Č/",
        ];

        $replace = [
            "ss" ,
            "ae", "a", "a", "a", "ae", "a", "a", "a",
            "Ae", "A", "A", "A", "Ae", "A", "A", "A",
            "e", "e", "e", "e", "e",
            "E", "E", "E", "E", "E",
            "i", "i", "i", "i", "i",
            "I", "I", "I", "I", "I",
            "oe", "o", "o", "o", "o", "o", "o", "o",
            "Oe", "O", "O", "O", "O", "Oe", "O", "O",
            "ue", "u", "u", "u", "u",
            "Ue", "U", "U", "U", "U",
            "c", "c", "c",
            "C", "C", "C",
        ];

        $string = preg_replace($umlaute, $replace, $stringwithspecialchars);

        // Now remove any remaining special chars.
        $string = preg_replace("/[^A-Za-z0-9 ]/", '', $string);

        // Now make sure, it's encoded as UTF-8.
        // Note: utf8_encode() has been deprecated since php 8.2 .
        $string = mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');

        // At last, make it UPPERCASE.
        return strtoupper($string);
    }

    /**
     * Collect all files without errors in a single directory.
     *
     * @param stored_file $file
     * @param string $filename
     * @return void
     */
    public static function copy_file_to_dir(stored_file $file, string $filename) {
        global $CFG;
        // Copy the file to a directory in moodleroot, create dir if it does not.
        try {
            $dataroot = $CFG->dataroot;
            $datadir = $dataroot . '/sapfiles';
            $filepath = $datadir . "/" . $filename;
            if (!is_dir($datadir)) {
                // Create the directory if it doesn't exist.
                if (!make_upload_directory('sapfiles')) {
                    // Handle directory creation error (e.g., display an error message).
                    throw new moodle_exception('errorcreatingdirectory', 'local_musi');
                }
            }
            // Copy the file to the sapfiles directory.
            if (file_exists($filepath)) {
                return;
            }
            if (!$file->copy_content_to($filepath)) {
                throw new moodle_exception('errorcopyingfiles', 'local_musi');
            }
        } catch (moodle_exception $e) {
            // Exception handling.
            $message = $e->getMessage();
            $code = $e->getCode();
            // Get detailed information about $file.
            $fileinfo = array(
                    'filename' => $file->get_filename(),
                    'filepath' => $file->get_filepath(),
                    'filesize' => $file->get_filesize(),
                    'filearea' => $file->get_filearea(),
                    'timecreated' => $file->get_timecreated(),

            );
            debugging("Moodle Exception: $message (Code: $code). File Info: " . var_dump($fileinfo, true));
        }
    }

    /**
     * @param string $starttimestamp
     * @return void
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     * @throws moodle_exception
     */
    public static function create_sap_files_from_date(int $starttimestamp): void {
        $now = time();
        if (!$context = context_system::instance()) {
            throw new moodle_exception('badcontext');
        }
        $yesterday = strtotime('-1 day', $now);
        $fs = get_file_storage();
        $contextid = $context->id;
        $component = 'local_musi';
        $filearea = 'musi_sap_dailysums';
        $itemid = 0;
        $filepath = '/';
        while ($starttimestamp <= $yesterday) {
            $filename = 'SAP_USI_' . date('Ymd', $starttimestamp);
            // Retrieve the file from the Files API.
            $file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
            if (!$file) {
                $fileinfo = array(
                        'contextid' => $contextid,
                        'component' => $component,
                        'filearea' => $filearea,
                        'itemid' => $itemid,
                        'filepath' => $filepath,
                        'filename' => $filename
                );

                list($content, $errorcontent) = self::generate_sap_text_file_for_date(date('Y-m-d', $starttimestamp));
                $file = $fs->create_file_from_string($fileinfo, $content);

                // If we have error content, we create an error file.
                if (!empty($errorcontent)) {
                    $errorfilename = 'SAP_USI_' . date('Ymd', $starttimestamp) . '_errors';
                    $errorfile = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $errorfilename);
                    if (!$errorfile) {
                        $errorfileinfo = array(
                                'contextid' => $contextid,
                                'component' => $component,
                                'filearea' => $filearea,
                                'itemid' => $itemid,
                                'filepath' => $filepath,
                                'filename' => $errorfilename
                        );
                        $fs->create_file_from_string($errorfileinfo, $errorcontent);
                    }

                }
            }
            $starttimestamp = strtotime('+1 day', $starttimestamp);
            // Collect all files in single directory.
            self::copy_file_to_dir($file, $filename);
        }
    }
}
