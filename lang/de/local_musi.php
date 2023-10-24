<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     local_musi
 * @category    string
 * @copyright   2022 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'M:USI Dashboard';

$string['dashboard'] = 'Dashboard';
$string['messageprovider:sendmessages'] = 'Verschicke Nachrichten';
$string['musi:cansendmessages'] = 'Kann Nachrichten schicken.';
$string['musi:editavailability'] = 'Kann die Verfügbarkeit von Buchungsoptionen ändern und Vorreservierungen anlegen';
$string['musi:editsubstitutionspool'] = 'Kann den Vertretungspool für einzelne Sportarten bearbeiten';
$string['musi:viewsubstitutionspool'] = 'Kann den Vertretungspool für einzelne Sportarten sehen und E-Mails an den Vertretungspool senden';

// Caches.
$string['cachedef_cachedpaymenttable'] = 'Zahlungstransaktionen (Cache)';

// Shortcodes.
$string['shortcodelists'] = 'Shortcode-Listen';
$string['shortcodelists_desc'] = 'Hier können Sie Listen konfigurieren, die durch Shortcodes (z.B. [allekurseliste]) generiert werden.';
$string['shortcodelists_showdescriptions'] = 'Beschreibungen von Buchungsoptionen anzeigen';
$string['shortcodeslistofbookingoptions'] = 'Alle Kurse als Liste';
$string['shortcodeslistofbookingoptionsascards'] = 'Alle Kurse als Karten';
$string['shortcodeslistofmybookingoptionsascards'] = 'Meine Kurse als Karten';
$string['shortcodeslistofmybookingoptionsaslist'] = 'Meine Kurse als Liste';
$string['shortcodeslistofteachersascards'] = 'Liste aller Trainer als Karten';
$string['shortcodeslistofmyteachedbookingoptionsascards'] = 'Kurse, die ich unterrichte, als Karten';
$string['shortcodesshowallsports'] = "Liste aller sportarten";

// General strings.
$string['campaigns'] = 'Kampagnen';
$string['dayofweek'] = 'Wochentag';
$string['editavailabilityanddescription'] = 'Verfügbarkeit & Beschreibung bearbeiten';
$string['editavailability'] = 'Verfügbarkeit bearbeiten';
$string['editdescription'] = 'Beschreibung bearbeiten';
$string['substitutionspool'] = 'Vertretungspool für {$a}';
$string['editsubstitutionspool'] = 'Vertretungspool bearbeiten';
$string['viewsubstitutionspool'] = 'Vertretungspool ansehen';
$string['mailtosubstitutionspool'] = 'E-Mail an Vertretungspool senden';
$string['substitutionspool:infotext'] = 'Trainer:innen, die <b>{$a}</b> vertreten dürfen:';
$string['substitutionspool:mailproblems'] = 'Hier klicken, wenn Sie Probleme beim Versenden der E-Mails haben...';
$string['substitutionspool:copypastemails'] = 'Kopieren Sie die folgenden E-Mail-Adressen in das BCC-Feld Ihres E-Mail-Programms:';
$string['gateway'] = 'Gateway';
$string['invisibleoption'] = '<i class="fa fa-eye-slash" aria-hidden="true"></i>';
$string['sportsdivisions'] = 'Sparten';
$string['titleprefix'] = 'Kursnummer';

// Errors.
$string['error:starttime'] = 'Start muss vor dem Ende sein.';
$string['error:endtime'] = 'Ende muss nach dem Start sein.';

// List of all courses.
$string['allcourses'] = 'Alle Kurse';

// Cards.
$string['listofsports'] = 'Sportarten';
$string['listofsports_desc'] = 'Zeige und editiere die Liste der Sportarten auf diesem System.';

$string['numberofcourses'] = 'Kurse';
$string['numberofcourses_desc'] = 'Informationen über die Kurse und Buchungen auf der Plattform.';

$string['numberofentities'] = 'Anzahl der Sportstätten';
$string['numberofentities_desc'] = 'Informationen über die Sportstätten auf der Plattform.';

$string['coursesavailable'] = "Buchbare <br> Kurse";
$string['coursesbooked'] = 'Gebuchte <br> Kurse';
$string['coursesincart'] = 'Im Warenkorb';
$string['coursesdeleted'] = 'Gelöschte Kurse';
$string['coursesboughtcard'] = 'Gekaufte Kurse (Online)';
$string['coursespending'] = 'Noch unbestätigte Kurse';
$string['coursesboughtcashier'] = 'Gekaufte Kurse (Kassa)';
$string['paymentsaborted'] = 'Abgebrochene Zahlungen';
$string['bookinganswersdeleted'] = "Gelöschte Buchungen";

$string['quicklinks'] = 'Quicklinks';
$string['quicklinks_desc'] = 'Die wichtigsten Links auf einen Blick';
$string['settingsandreports'] = 'Einstellungen & Berichte';
$string['settingsandreports_desc'] = 'Verschiedene Einstellungen und Berichte, die für M:USI relevant sind.';
$string['editentities'] = 'Sportstätten bearbeiten';
$string['editentitiescategories'] = 'Kategorien der Sportstätten bearbeiten';
$string['importentities'] = 'Sportstätten importieren';
$string['entitiesavailable'] = 'Verfügbare <br> Sportstätten';
$string['editbookinginstance'] = 'Semester-Instanz bearbeiten';
$string['editbookings'] = 'Kurs-Übersicht';
$string['viewteachers'] = 'Trainer:innen-Übersicht';
$string['teachersinstancereport'] = 'Trainer:innen-Gesamtbericht (Kurse, Fehlstunden, Vertretungen)';
$string['sapdailysums'] = 'SAP-Buchungsdateien';

$string['addbookinginstance'] = '<span class="bg-danger font-weight-bold">Keine Semester-Instanz! Hier klicken, um eine einzustellen.</span>';
$string['editpricecategories'] = 'Preiskategorien bearbeiten';
$string['editsemesters'] = 'Semester bearbeiten';
$string['changebookinginstance'] = 'Standard-Semester-Instanz setzen';
$string['editbotags'] = 'Tags verwalten';
$string['createbotag'] = 'Neuen Tag anlegen...';
$string['createbotag:helptext'] = '<p>
<a data-toggle="collapse" href="#collapseTagsHelptext" role="button" aria-expanded="false" aria-controls="collapseTagsHelptext">
  <i class="fa fa-question-circle" aria-hidden="true"></i><span>&nbsp;Hilfe: So können Sie Tags konfigurieren...</span>
</a>
</p>
<div class="collapse" id="collapseTagsHelptext">
<div class="card card-body">
  <p>Damit Sie Tags verwenden können, müssen Sie ein Benutzerdefiniertes Buchungsoptionsfeld vom Typ "Dynamic Dropdown menu" mit folgenden Einstellungen anlegen:</p>
  <ul>
  <li><strong>Kategorie: </strong>Tags</li>
  <li><strong>Name: </strong>Tags</li>
  <li><strong>Kurzname: </strong>botags</li>
  <li><strong>SQL query: </strong><code>SELECT botag as id, botag as data FROM {local_musi_botags}</code></li>
  <li><strong>Auto-complete: </strong><span class="text-success">aktiviert</span></li>
  <li><strong>Multi select: </strong><span class="text-success">aktiviert</span></li>
  </ul>
  <p>Nun können Sie die hier angelegten Tags den Buchungsoptionen zuordnen.<br>Sie müssen hier mindestens einen Tag angelegt haben, damit Sie Tagging verwenden können.</p>
</div>
</div>';

// Edit sports.
$string['editsports'] = 'Sportarten bearbeiten';
$string['youneedcustomfieldsport'] = 'Das benutzerdefinierte Feld mit dem Shortname "sport" ist bei dieser Buchungsoption nicht gesetzt.';

// Shortcodes.
$string['shortcodeslistofbookingoptions'] = 'Liste der buchbaren Kurse';
$string['shortcodeslistofbookingoptionsascards'] = 'Liste der buchbaren Kurse als Karten';
$string['shortcodeslistofmybookingoptionsascards'] = 'Liste meiner gebuchte Kurse als Karten';
$string['shortcodessetdefaultinstance'] = 'Setze eine Standard-Instanz für Shortcodes';
$string['shortcodessetdefaultinstancedesc'] = 'Damit kann eine Standard-Buchungsinstanz definiert werden, die dann verwendet wird,
wenn keine ID definiert wurde. Dies erlaubt den schnellen Wechsel (zum Beispiel von einem Semster zum nächsten), wenn es Überblicks-
Seiten für unterschiedliche Kategorien gibt.';
$string['shortcodessetinstance'] = 'Definiere die Buchungsinstanz, die standardmäßig verwendet werden soll.';
$string['shortcodessetinstancedesc'] = 'Wenn Du hier einen Wert setzt, kann der Shortcode so verwendet werden: [allekurseliste category="philosophy"]
Es ist also nicht mehr nötig, eine ID zu übergeben.';
$string['shortcodesnobookinginstance'] = '<div class="text-danger font-weight-bold">Noch keine Buchungsinstanz erstellt!</div>';
$string['shortcodesnobookinginstancedesc'] = 'Sie müssen mindestens eine Buchungsinstanz in einem Moodle-Kurs erstellen, bevor Sie hier eine auswählen können.';
$string['shortcodesuserinformation'] = 'Zeige Informationen von NutzerInnen';
$string['shortcodesarchivecmids'] = 'Liste von IDs für das "Meine Kurse"-Archiv';
$string['shortcodesarchivecmids_desc'] = 'Geben Sie eine Komma-getrennte Liste von Kursmodul-IDs (cmids) der Semester-Instanzen (Buchungsinstanzen) an,
die im "Meine Kurse"-Archiv aufscheinen sollen.';

$string['archive'] = '<i class="fa fa-archive" aria-hidden="true"></i> Archiv';
$string['mycourses'] = 'Meine Kurse';
$string['coursesibooked'] = '<i class="fa fa-ticket" aria-hidden="true"></i> Kurse, die ich im aktuellen Semester gebucht habe:';
$string['coursesibookedarchive'] = 'Kurse, die ich in vergangenen Semestern gebucht habe:';
$string['coursesiteach'] = '<i class="fa fa-graduation-cap" aria-hidden="true"></i> Kurse, die ich im aktuellen Semester unterrichte:';
$string['coursesiteacharchive'] = 'Kurse, die ich in vergangenen Semestern unterrichtet habe:';

// Access.php.
$string['musi:canedit'] = 'Nutzer:in darf verwalten';

// Filter.
$string['sport'] = 'Sportart';
$string['location'] = 'Ort';

// Nav.
$string['musi'] = 'MUSI';
$string['cashier'] = 'Kassa';
$string['entities'] = 'Sportstätten';
$string['coursename'] = 'Kursname';

// Contract management.
$string['contractmanagementsettings'] = 'Vertragsmanagement-Einstellungen';
$string['contractmanagementsettings_desc'] = 'Konfigurieren Sie hier, wie sich Verträge auf Abrechnungen
 auswirken und welche Sonderfälle es gibt.';
$string['contractformula'] = 'Vertragsformel';
$string['contractformula_desc'] = 'Hier können Sie eine JSON-Formel angeben, die festlegt, wie sich Verträge auf Abrechnungen
 auswirken und welche Sonderfälle es gibt.';
$string['contractformulatest'] = 'Vertragsformel testen';
$string['editcontractformula'] = 'Vertragsformel bearbeiten';

// Userinformation.mustache.
$string['userinformation'] = 'Benutzer-Information';

// My Courses List.
$string['tocoursecontent'] = 'Zu den Kursmaterialien';

// Shortlist section information.
$string['dayofweekalt'] = 'Wochentag und Termin, an dem eine Kurseinheit stattfindet';
$string['locationalt'] = 'Abhaltungsort des Kurses';
$string['bookingsalt'] = 'Anzahl der freien und maximal verfügbaren Kursplätze';
$string['teacheralt'] = 'Leiter des Kurses';
$string['imagealt'] = 'Titelbild des Kurses';


// Transactions List.
$string['status'] = 'Status';
$string['openorder'] = 'Offen';
$string['bookedorder'] = 'Bezahlt';
$string['transactionslist'] = 'Zahlungstransaktionen';
$string['checkstatus'] = 'Überprüfe Status';
$string['statuschanged'] = 'Status geändert';
$string['statusnotchanged'] = 'Status nicht geändert';

$string['id'] = 'Eintrag';
$string['transactionid'] = 'Interne ID';
$string['itemid'] = 'ItemID';
$string['username'] = 'Nutzer';
$string['price'] = 'Betrag';
$string['names'] = 'Buchungen';
$string['action'] = 'Aktion';

// Easy availability feature.
$string['easyavailability:overbook'] = 'Sogar dann, wenn der Kurs <b>ausgebucht</b> ist';
$string['easyavailability:previouslybooked'] = 'Nutzer:innen, die bereits einen bestimmten USI-Kurs gebucht haben, dürfen immer buchen';
$string['easyavailability:selectusers'] = 'Ausgewählte Nutzer:innen dürfen außerhalb der Buchungszeiten buchen';
$string['easyavailability:formincompatible'] = '<div class="alert alert-warning">Diese Buchungsoption verwendet Einschränkungen,
 die mit diesem Formular nicht kompatibel sind. Bitte wenden Sie sich an einen M:USI-Admin.</div>';
$string['easyavailability:openingtime'] = 'Kann gebucht werden ab';
$string['easyavailability:closingtime'] = 'Kann gebucht werden bis';
$string['easyavailability:heading'] = '<div class="alert alert-info">Sie bearbeiten die Verfügbarkeit von "<b>{$a}</b>"</div>';

// Task.
$string['create_sap_files'] = 'Die täglichen SAP Dateien erstellen.';
