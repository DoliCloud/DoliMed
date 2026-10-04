<?php
/* Copyright (C) 2004-2014      Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   \file       htdocs/cabinetmed/documents.php
 *   \brief      Tab for courriers (documents) of a patient
 *   \ingroup    cabinetmed
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include str_replace("..", "", $_SERVER["CONTEXT_DOCUMENT_ROOT"])."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;  // @phan-suppress-current-line DolibarrForbiddenFunctionPlugin
$j = strlen($tmp2) - 1;  // @phan-suppress-current-line DolibarrForbiddenFunctionPlugin
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}
/**
 * The main.inc.php has been included so the following variable are now defined:
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT."/core/lib/company.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";
include_once "./lib/cabinetmed.lib.php";
include_once "./class/patient.class.php";
include_once "./class/html.formfilecabinetmed.class.php";

// Load translation files required by the page
$langs->loadLangs(array("companies", "bills", "banks", "other", "mails", "cabinetmed@cabinetmed"));

// Get parameters
$action  = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm');
$id      = (GETPOSTINT('socid') ? GETPOSTINT('socid') : GETPOSTINT('id'));
$ref     = GETPOST('ref', 'alpha');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "date";
}

// Initialize technical objects
$object = new Patient($db);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('thirdpartycard', 'documentcabinetmed'));

// Load object
$upload_dir = null;
if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);

	$upload_dir = $conf->societe->multidir_output[empty($object->entity) ? $conf->entity : $object->entity]."/".$object->id;
}

// Permissions
$permissiontoadd = $user->hasRight('societe', 'creer'); // Used by the include of actions_linkedfiles.inc.php and actions_builddoc.inc.php

// Security check
$socid = $id;
if ($user->socid > 0) {
	// External user
	unset($action);
	$socid = $user->socid;
}
$result = restrictedArea($user, 'societe', $object->id, '&societe');
if (!isModEnabled('cabinetmed')) {
	accessforbidden();
}
if (!$user->hasRight('cabinetmed', 'read')) {
	accessforbidden();
}
if (empty($object->id) || $upload_dir === null) {
	accessforbidden();
}


/*
 * Actions
 */

$parameters = array('id' => $object->id > 0 ? $object->id : $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Actions to upload, link, delete or rename files
	include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

	// Actions to build or delete documents generated from a model
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}

// Actions to send emails
$triggersendname = 'COMPANY_SENTBYMAIL';
$paramname = 'socid';
$mode = 'emailfromthirdparty';
$trackid = 'thi'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

// Header
$title = $langs->trans("Courriers");
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-cabinetmed page-card_document');

// Show tabs
$head = societe_prepare_head($object);

print dol_get_fiche_head($head, 'tabdocument', $langs->trans("Patient"), -1, 'user-injured', 0, '', '', 0, '', 1);

// Build file list
$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\\.meta|_preview.*\\.png)$', $sortfield, (dol_strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
$totalsize = 0;
foreach ($filearray as $key => $file) {
	$totalsize += $file['size'];
}

// Object card
$linkback = '<a href="'.dol_buildpath('/cabinetmed/patients.php', 1).'?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

// Prefix
if (getDolGlobalString("SOCIETE_USEPREFIX")) {  // Old not used prefix field
	print '<tr><td class="titlefield">'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
}

if ($object->client) {
	print '<tr><td class="titlefield">';
	print $langs->trans('CustomerCode').'</td><td colspan="3">';
	print $object->code_client;
	$tmpcheck = $object->check_codeclient();
	if ($tmpcheck != 0 && $tmpcheck != -5) {
		print ' <span class="error">('.$langs->trans("WrongCustomerCode").')</span>';
	}
	print '</td></tr>';
}

if ($object->fournisseur) {
	print '<tr><td class="titlefield">';
	print $langs->trans('SupplierCode').'</td><td colspan="3">';
	print $object->code_fournisseur;
	$tmpcheck = $object->check_codefournisseur();
	if ($tmpcheck != 0 && $tmpcheck != -5) {
		print ' <span class="error">('.$langs->trans("WrongSupplierCode").')</span>';
	}
	print '</td></tr>';
}

// Number of files
print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

// Total size
print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';

print '</table>';

print '</div>';

print dol_get_fiche_end();

$modulepart = 'societe';
$param = '&socid='.$object->id;

// Confirm form to delete a file or a link
// (the delete buttons of the list of documents use the old action names 'delete' and 'deletelink')
if ($action == 'delete' || $action == 'deletelink') {
	$langs->load("companies"); // Need for string DeleteFile+ConfirmDeleteFiles
	print $form->formconfirm(
		$_SERVER["PHP_SELF"].'?id='.$object->id.'&urlfile='.urlencode(GETPOST("urlfile")).'&linkid='.GETPOSTINT('linkid'),
		$langs->trans('DeleteFile'),
		$langs->trans('ConfirmDeleteFile'),
		'confirm_deletefile',
		'',
		'',
		1
	);
}

// Form to upload a new file or add a link
$formfile->form_attach_new_file(
	$_SERVER["PHP_SELF"].'?socid='.$object->id,
	'',
	0,
	0,
	$permissiontoadd,
	$conf->browser->layout == 'phone' ? 40 : 60,
	$object,
	'',
	1,
	'',
	1
);

print '<a name="builddoc"></a>'; // ancre

/*
 * Documents generated from a model (courriers)
 */
$urlsource = $_SERVER["PHP_SELF"]."?socid=".$object->id;
$genallowed = $permissiontoadd;
$delallowed = $user->hasRight('societe', 'supprimer');

$title = img_picto('', 'filenew').' '.$langs->trans("GenerateADocument");
$tooltipmessage = $langs->trans("EditOrAddTemplateFromSetupOfThirdPartyModule", $langs->trans("Module1Name"), $langs->trans("Home"), $langs->trans("Setup"), $langs->trans("Modules"));

print $formfile->showdocuments('company', '', '', $urlsource, $genallowed, $delallowed, $object->model_pdf, 0, 0, 0, 0, 0, '', $title, '', $object->default_lang, '', $object, 0, 'remove_file', $tooltipmessage);

// List of documents (use the list of the module to be able to send a file by email)
print '<br><br>';
$disablemove = 0;

$formfilecabinetmed = new FormFileCabinetmed($db);
$formfilecabinetmed->list_of_documents_cabinetmed(
	$filearray,
	$object,
	$modulepart,
	$param,
	0,
	'',		// relative path with no file
	$permissiontoadd,
	0,
	'',
	0,
	'',
	'',
	0,
	$permissiontoadd,
	$upload_dir,
	$sortfield,
	$sortorder,
	$disablemove
);

print "<br>";

// List of links
$formfile->listOfLinks($object, $permissiontoadd, $action, GETPOSTINT('linkid'), $param);

// Presend form (to send a file by email)
if ($action == 'presend') {
	// Anchor used by the "send by email" links of the list of documents
	print '<div id="sendform" name="formmailbeforetitle"></div>';
	print '<br>';
}

$modelmail = 'thirdparty';
$defaulttopic = 'SendConsultationRef';
$diroutput = $conf->societe->multidir_output[empty($object->entity) ? $conf->entity : $object->entity];
$trackid = 'thi'.$object->id;

include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';

// End of page
llxFooter();

$db->close();
