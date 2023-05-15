<?php
/* Copyright (C) 2004-2012      Laurent Destailleur  <eldy@users.sourceforge.net>
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
* along with this program. If not, see <http://www.gnu.org/licenses/>.
* or see http://www.gnu.org/
*/

/**
 *   \file       htdocs/cabinetmed/patients_of_contact.php
 *   \brief      Tab for patients for contact
 *   \ingroup    cabinetmed
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

include_once DOL_DOCUMENT_ROOT."/core/lib/company.lib.php";
require_once DOL_DOCUMENT_ROOT."/contact/class/contact.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/contact.lib.php";
include_once "./lib/cabinetmed.lib.php";
include_once "./class/patient.class.php";
include_once "./class/cabinetmedcons.class.php";

$action = GETPOST("action");
$id=GETPOST('id', 'int');  // Id consultation

$langs->load("companies");
$langs->load("bills");
$langs->load("banks");
$langs->load("cabinetmed@cabinetmed");

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'societe', $socid);

if (!$user->rights->cabinetmed->read) accessforbidden();

$mesgarray=array();

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";

// Security check
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe');
$object = new Contact($db);

$now=dol_now();


/*
 * Actions
*/

// Delete consultation
if (GETPOST("action") == 'confirm_delete' && GETPOST("confirm") == 'yes' && $user->rights->societe->supprimer) {
	$consult->fetch($id);
	$result = $consult->delete($user);
	if ($result >= 0) {
		header("Location: ".$_SERVER["PHP_SELF"].'?socid='.$socid);
		exit;
	} else {
		$langs->load("errors");
		$mesg=$langs->trans($consult->error);
		$action='';
	}
}


/*
 *	View
*/


/*
 *	View
*/

$now=dol_now();

llxHeader('', $langs->trans("PatientsOfContact"), '');

$form = new Form($db);

$object->fetch($id, $user);

$head = contact_prepare_head($object);

dol_fiche_head($head, 'tabpatient', $langs->trans("ContactsAddresses"), ((float) DOL_VERSION < 7 ? 0 : -1), 'contact');


if ($id > 0) {
	$linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref='<div class="refidno">';
	if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
		$objsoc=new Societe($db);
		$objsoc->fetch($object->socid);
		// Thirdparty
		$morehtmlref.=$langs->trans('ThirdParty') . ' : ';
		if ($objsoc->id > 0) $morehtmlref.=$objsoc->getNomUrl(1);
		else $morehtmlref.=$langs->trans("ContactNotLinkedToCompany");
	}
	$morehtmlref.='</div>';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

	$cssclass='titlefield';
	//if ($action == 'editnote_public') $cssclass='titlefieldcreate';
	//if ($action == 'editnote_private') $cssclass='titlefieldcreate';

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

	// Civility
	print '<tr><td class="'.$cssclass.'">'.$langs->trans("UserTitle").'</td><td>';
	print $object->getCivilityLabel();
	print '</td></tr>';

	// Role
	print '<tr><td>'.$langs->trans("PostOrFunction").'</td><td colspan="3">'.$object->poste.'</td></tr>';

	print "</table>";

	print '</div>';
}

dol_fiche_end();


print_fiche_titre($langs->trans("ListOfPatients"), '', '');

$param='&id='.$id;

print "\n";

print '<div class="div-table-responsive">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('Name'), $_SERVER['PHP_SELF'], 's.nom', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('CustomerCode'), $_SERVER['PHP_SELF'], 's.code_client', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('Zip'), $_SERVER['PHP_SELF'], 's.zip', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('Town'), $_SERVER['PHP_SELF'], 's.town', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans(((float) DOL_VERSION < 13) ? 'DateToBirth' : 'DateOfBirth'), $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('');
print '</tr>';


// List of patients
$sql = "SELECT";
$sql.= " s.rowid,";
$sql.= " s.nom as name,";
$sql.= " s.code_client as customer_code,";
$sql.= " s.zip as zip,";
$sql.= " s.town as town,";
$sql.= " se.birthdate,";
$sql.= " tc.code, tc.libelle as label_type";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as se on se.fk_object = s.rowid,";
$sql.= " ".MAIN_DB_PREFIX."element_contact as ec,";
$sql.= " ".MAIN_DB_PREFIX."c_type_contact as tc";
$sql.= " WHERE ec.fk_socpeople = ".$id;
$sql.= " AND ec.element_id = s.rowid";
$sql.= " AND ec.fk_c_type_contact = tc.rowid";
$sql.= " AND tc.element = 'societe'";
$sql.= " ORDER BY ".$sortfield." ".$sortorder.", s.rowid DESC";

//print $sql;
$resql=$db->query($sql);
if ($resql) {
	$i = 0 ;
	$num = $db->num_rows($resql);

	$societestatic=new Societe($db);

	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		$societestatic->id = $obj->rowid;
		$societestatic->name = $obj->name;

		print '<tr class="oddeven">';

		print '<td>';
		print $societestatic->getNomUrl(1);
		print '</td>';
		print '<td>';
		print $obj->customer_code;
		print '</td>';
		print '<td>';
		print $obj->zip;
		print '</td>';
		print '<td>';
		print $obj->town;
		print '</td>';
		print '<td>';
		print $obj->birthdate;
		print '</td>';
		print '<td>';
		print $obj->label_type;
		print '</td>';

		print '</tr>';
		$i++;
	}

	if (!$num) {
		print '<tr class="oddeven"><td colspan="6">';
		print '<span class="opacitymedium">'.$langs->trans("None").'</span>';
		print '</td></tr>';
	}
} else {
	dol_print_error($db);
}

print '</table>';
print '</div>';

print '<br>';

llxFooter();

$db->close();
