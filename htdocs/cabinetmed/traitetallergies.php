<?php
/* Copyright (C) 2001-2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006      Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010           Juanjo Menent        <jmenent@2byte.es>
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
 *   \file       htdocs/cabinetmed/traitetallergies.php
 *   \brief      Tab for antecedants
 *   \ingroup    societe
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
include_once "./class/patient.class.php";
include_once "./lib/cabinetmed.lib.php";

$langs->load("companies");
$langs->load("cabinetmed@cabinetmed");

$action = GETPOST('action', 'aZ09');
if (empty($action)) $action='edit';

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'societe', $socid);

if (!$user->rights->cabinetmed->read) accessforbidden();


/*
 * Actions
 */

if ($action == 'addupdate') {
	$db->begin();

	$sql = "INSERT INTO ".MAIN_DB_PREFIX."cabinetmed_patient(rowid, note_traitclass, note_traitallergie, note_traitintol, note_traitspec)";
	$sql.= " VALUES('".$_POST["socid"]."','".addslashes($_POST["note_traitclass"])."','".addslashes($_POST["note_traitallergie"])."','".addslashes($_POST["note_traitintol"])."',";
	$sql.= " '".addslashes($_POST["note_traitspec"])."')";
	$result1 = $db->query($sql, 1);
	//if (! $result) dol_print_error($db);

	$sql = "UPDATE ".MAIN_DB_PREFIX."cabinetmed_patient SET";
	//$sql.= " note_traitclass='".addslashes($_POST["note_traitclass"])."',";
	//$sql.= " note_traitallergie='".addslashes($_POST["note_traitallergie"])."',";
	$sql.= " note_traitintol='".addslashes($_POST["note_traitintol"])."',";
	$sql.= " note_traitspec='".addslashes($_POST["note_traitspec"])."'";
	$sql.= " WHERE rowid=".$_POST["socid"];
	$result2 = $db->query($sql);

	$alert=($_POST["alert_traitspec"]?'1':'0');
	$result3=addAlert($db, 'alert_traitspec', $socid, $alert);
	if ($result3) {
		$error++; $mesg=$result3;
	}

	$alert=($_POST["alert_traitintol"]?'1':'0');
	$result4=addAlert($db, 'alert_traitintol', $socid, $alert);
	if ($result4) {
		$error++; $mesg=$result4;
	}

	if ((! $result2) || $result3 || $result4) {
		dol_print_error($db);
		$db->rollback();
	} else {
		$db->commit();
		$mesg=$langs->trans("RecordModifiedSuccessfully");
	}

	$action='edit';
}


/*
 *	View
 */

$form = new Form($db);

llxHeader('', $langs->trans('TraitEtAllergies'));


if ($socid > 0) {
	$object = new Patient($db);
	$res=$object->fetch($socid);
	if ($res < 0) {
		dol_print_error($db, $object->error);
	}
	$object->id=$socid;

	/*
	 * Affichage onglets
	 */
	if (isModEnabled("notification")) $langs->load("mails");

	$head = societe_prepare_head($object);

	dol_fiche_head($head, 'tabtraitetallergies', $langs->trans("ThirdParty"), 0, 'patient@cabinetmed');


	print '<script type="text/javascript">
    var changed=false;
    jQuery(function() {
        jQuery(window).bind(\'beforeunload\', function(){
            /* alert(changed); */
            if (changed) return \''.dol_escape_js($langs->transnoentitiesnoconv("WarningExitPageWithoutSaving")).'\';
        });
        jQuery(".flat").change(function () {
            changed=true;
        });
        jQuery(".ignorechange").click(function () {
            changed=false;
        });
     });
    </script>';

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="socid" value="'.$object->id.'">';
	print '<input type="hidden" name="action" value="addupdate">';

	print '<table class="border" width="100%">';

	print '<tr><td width="25%">'.$langs->trans('ThirdPartyName').'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object, 'socid', '', ($user->socid?0:1), 'rowid', 'nom');
	print '</td></tr>';

	if ($object->client) {
		print '<tr><td>';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $object->code_client;
		if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
	}

	if (isModEnabled("fournisseur") && $object->fournisseur) {
		print '<tr><td>';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $object->code_fournisseur;
		if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
	}


	$conf->fckeditor->enabled=false;
	unset($conf->modules['fckeditor']);

	$height=140;


	// Spec
	print '<tr height="80"><td class="tdtop">'.$langs->trans("SpecPharma");
	print '<br><input type="checkbox" name="alert_traitspec"'.((isset($_POST['alert_traitspec'])?GETPOST('alert_traitspec'):$object->alert_traitspec)?' checked="checked"':'').'"> '.$langs->trans("Alert");
	print '</td>';
	print '<td class="tdtop">';
	if ($action == 'edit' && $user->rights->societe->creer) {
		print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

		// Editeur wysiwyg
		require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";
		$doleditor=new DolEditor('note_traitspec', $object->note_traitspec, 0, $height, 'dolibarr_notes', 'In', false, false, isModEnabled("fckeditor") && $conf->global->FCKEDITOR_ENABLE_SOCIETE, 8, '90%');
		$doleditor->Create();
	} else {
		print nl2br($object->note_traitspec);
	}
	print "</td></tr>";


	// Classes
	/*
	print '<tr height="80"><td class="tdtop">'.$langs->trans("Classes").'</td>';
	print '<td class="tdtop">';
	if ($action == 'edit' && $user->rights->societe->creer)
	{
		print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

		// Editeur wysiwyg
		require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
		$doleditor=new DolEditor('note_traitclass',$object->note_traitclass,0,$height,'dolibarr_notes','In',false,false,isModEnabled("fckeditor") && $conf->global->FCKEDITOR_ENABLE_SOCIETE,6,'90%');
		$doleditor->Create();
	}
	else
	{
		print nl2br($object->note_traitclass);
	}
	print "</td></tr>";
	*/

	// Intolerances
	print '<tr height="80"><td class="tdtop">'.$langs->trans("Intolerances");
	print '<br><input type="checkbox" name="alert_traitintol"'.((isset($_POST['alert_traitintol'])?GETPOST('alert_traitintol'):$object->alert_traitintol)?' checked="true"':'').'"> '.$langs->trans("Alert");
	print '</td>';
	print '<td class="tdtop">';
	if ($action == 'edit' && $user->rights->societe->creer) {
		print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

		// Editeur wysiwyg
		require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";
		$doleditor=new DolEditor('note_traitintol', $object->note_traitintol, 0, $height, 'dolibarr_notes', 'In', false, false, isModEnabled("fckeditor") && $conf->global->FCKEDITOR_ENABLE_SOCIETE, 8, '90%');
		$doleditor->Create();
	} else {
		print nl2br($object->note_traitintol);
	}
	print "</td></tr>";

	print "</table>";


	if ($action == 'edit') {
		print '<center><br><input type="submit" class="button ignorechange" value="'.$langs->trans("Save").'"></center>';
	}

	print '</form>';
}

print '</div>';


/*
 * Boutons actions
 */
if ($action == '') {
	print '<div class="tabsAction">';

	if ($user->rights->societe->creer) {
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
	}

	print '</div>';
}


dol_htmloutput_mesg($mesg);


llxFooter();

$db->close();
