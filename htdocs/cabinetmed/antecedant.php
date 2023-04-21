<?php
/* Copyright (C) 2001-2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   \file       htdocs/cabinetmed/antecedant.php
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

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('thirdpartycard', 'antecedantcard', 'globalcard'));

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'societe', $socid);

if (!$user->rights->cabinetmed->read) accessforbidden();

$object = new Patient($db);


/*
 * Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if ($action == 'addupdate') {
		$error=0;

		$db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."cabinetmed_patient(rowid, note_antemed, note_antechirgen, note_antechirortho, note_anterhum, note_other, note_traitallergie, note_traitclass, note_traitintol, note_traitspec)";
		$sql.= " VALUES('".$_POST["socid"]."',";
		$sql.= " '".addslashes($_POST["note_antemed"])."','".addslashes($_POST["note_antechirgen"])."',";
		$sql.= " '".addslashes($_POST["note_antechirortho"])."','".addslashes($_POST["note_anterhum"])."','".addslashes($_POST["note_other"])."',";
		$sql.= " '".addslashes($_POST["note_traitallergie"])."','".addslashes($_POST["note_traitclass"])."','".addslashes($_POST["note_traitintol"])."','".addslashes($_POST["note_traitspec"])."'";
		$sql.= ")";
		$result1 = $db->query($sql, 1);
		//if (! $result) dol_print_error($db);

		$sql = "UPDATE ".MAIN_DB_PREFIX."cabinetmed_patient SET";
		$sql.= " note_antemed='".addslashes($_POST["note_antemed"])."',";
		$sql.= " note_antechirgen='".addslashes($_POST["note_antechirgen"])."',";
		$sql.= " note_antechirortho='".addslashes($_POST["note_antechirortho"])."',";
		$sql.= " note_anterhum='".addslashes($_POST["note_anterhum"])."',";
		//$sql.= " note_other='".addslashes($_POST["note_other"])."',";
		$sql.= " note_traitallergie='".addslashes($_POST["note_traitallergie"])."',";
		$sql.= " note_traitclass='".addslashes($_POST["note_traitclass"])."',";
		$sql.= " note_traitintol='".addslashes($_POST["note_traitintol"])."',";
		$sql.= " note_traitspec='".addslashes($_POST["note_traitspec"])."'";
		$sql.= " WHERE rowid=".$_POST["socid"];
		$result2 = $db->query($sql);

		$alert=($_POST["alert_antemed"]?'1':'0');
		$result3=addAlert($db, 'alert_antemed', $socid, $alert);
		if ($result3) {
			$error++; $mesg=$result3;
		}

		$alert=($_POST["alert_antechirgen"]?'1':'0');
		$result4=addAlert($db, 'alert_antechirgen', $socid, $alert);
		if ($result4) {
			$error++; $mesg=$result4;
		}

		$alert=($_POST["alert_antechirortho"]?'1':'0');
		$result5=addAlert($db, 'alert_antechirortho', $socid, $alert);
		if ($result5) {
			$error++; $mesg=$result5;
		}

		$alert=($_POST["alert_anterhum"]?'1':'0');
		$result6=addAlert($db, 'alert_anterhum', $socid, $alert);
		if ($result6) {
			$error++; $mesg=$result6;
		}

		$alert=($_POST["alert_traitallergie"]?'1':'0');
		$result7=addAlert($db, 'alert_traitallergie', $socid, $alert);
		if ($result7) {
			$error++; $mesg=$result7;
		}

		$alert=($_POST["alert_traitclass"]?'1':'0');
		$result8=addAlert($db, 'alert_traitclass', $socid, $alert);
		if ($result8) {
			$error++; $mesg=$result8;
		}

		$alert=($_POST["alert_traitintol"]?'1':'0');
		$result9=addAlert($db, 'alert_traitintol', $socid, $alert);
		if ($result9) {
			$error++; $mesg=$result9;
		}

		$alert=($_POST["alert_traitspec"]?'1':'0');
		$result10=addAlert($db, 'alert_traitspec', $socid, $alert);
		if ($result10) {
			$error++; $mesg=$result10;
		}

		if ((! $result2) || $result3 || $result4 || $result5 || $result6 || $result7 || $result8 || $result9 || $result10) {
			dol_print_error($db);
			$db->rollback();
		} else {
			$db->commit();
			$mesg=$langs->trans("RecordModifiedSuccessfully");
		}

		$action='edit';
	}
}


/*
 *	View
 */

$form = new Form($db);

llxHeader('', $langs->trans("ATCD"));

if ($socid > 0) {
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
	if ((float) DOL_VERSION < 7) dol_fiche_head($head, 'tabantecedents', $langs->trans("Patient"), 0, 'patient@cabinetmed');
	elseif ((float) DOL_VERSION < 15) dol_fiche_head($head, 'tabantecedents', $langs->trans("Patient"), -1, 'patient@cabinetmed');
	else dol_fiche_head($head, 'tabantecedents', $langs->trans("Patient"), -1, 'user-injured');


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

	$linkback = '<a href="'.dol_buildpath('/cabinetmed/patients.php', 1).'">'.$langs->trans("BackToList").'</a>';
	dol_banner_tab($object, 'socid', $linkback, ($user->socid?0:1), 'rowid', 'nom');

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield" width="100%">';

	//if ($object->client)
	//{
		print '<tr><td class="titlefield">';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $object->code_client;
		if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
	//}

	if (isModEnabled("fournisseur") && $object->fournisseur) {
		print '<tr><td class="titlefield">';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $object->code_fournisseur;
		if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
	}

	print '</table><br>';



	print '<div class="fichecenter"><div class="fichehalfleft">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border" width="100%" style="margin-bottom: 2px !important;">';

	// Force disable fckeditor
	if (! isset($conf->fckeditor)) $conf->fckeditor = new stdClass();
	$conf->fckeditor->enabled=false;
	unset($conf->modules['fckeditor']);

	$height=120;

	print '<tr height="80"><td class="tdtop titlefield">'.$langs->trans("AntecedentsMed");
	print '<br><input type="checkbox" id="alert_antemed" name="alert_antemed"'.((isset($_POST['alert_antemed'])?GETPOST('alert_antemed'):$object->alert_antemed)?' checked="checked"':'').'"> <label for="alert_antemed">'.$langs->trans("Alert").'</label>';
	print '</td>';
	print '<td class="tdtop">';
	if ($action == 'edit' && $user->rights->societe->creer) {
		print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

		// Editeur wysiwyg
		require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";
		$doleditor=new DolEditor('note_antemed', $object->note_antemed, 0, $height, 'dolibarr_notes', 'In', false, false, isModEnabled("fckeditor"), 8, '95%');
		$doleditor->Create();
	} else {
		print nl2br($object->note_antemed);
	}
	print "</td>";
	//print "</tr>";

	print '</tr></table>';
	print '</div><div class="fichehalfright">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border" width="100%" style="margin-bottom: 2px !important;"><tr height="80">';

	// Spec
	//print '<tr height="80">';
	print '<td class="tdtop titlefield">'.$langs->trans("SpecPharma");
	print '<br><input type="checkbox" id="alert_traitspec" name="alert_traitspec"'.((isset($_POST['alert_traitspec'])?GETPOST('alert_traitspec'):$object->alert_traitspec)?' checked="checked"':'').'">  <label for="alert_traitspec">'.$langs->trans("Alert").'</label>';
	print '</td>';
	print '<td class="tdtop">';
	if ($action == 'edit' && $user->rights->societe->creer) {
		print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

		// Editeur wysiwyg
		require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";
		$doleditor=new DolEditor('note_traitspec', $object->note_traitspec, 0, $height, 'dolibarr_notes', 'In', false, false, isModEnabled("fckeditor"), 8, '95%');
		$doleditor->Create();
	} else {
		print nl2br($object->note_traitspec);
	}
	print "</td></tr>";


	print '</table>';
	print '</div></div>';


	print '<div class="fichecenter"><div class="fichehalfleft">';
	print '<table class="border" width="100%" style="margin-bottom: 2px !important;">';

	print '<tr height="80"><td class="tdtop titlefield">'.$langs->trans("AntecedentsChirGene");
	print '<br><input type="checkbox" id="alert_antechirgen" name="alert_antechirgen"'.((isset($_POST['alert_antechirgen'])?GETPOST('alert_antechirgen'):$object->alert_antechirgen)?' checked="checked"':'').'">  <label for="alert_antechirgen">'.$langs->trans("Alert").'</label>';
	print '</td>';
	print '<td class="tdtop">';
	if ($action == 'edit' && $user->rights->societe->creer) {
		print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

		// Editeur wysiwyg
		require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";
		$doleditor=new DolEditor('note_antechirgen', $object->note_antechirgen, 0, $height, 'dolibarr_notes', 'In', false, false, isModEnabled("fckeditor"), 8, '95%');
		$doleditor->Create();
	} else {
		print nl2br($object->note_antechirgen);
	}
	print "</td>";
	//pritn "</tr>";

	print '</tr></table>';
	print '</div><div class="fichehalfright">';

	print '<table class="border" width="100%" style="margin-bottom: 2px !important;"><tr height="80">';

	// Intolerances
	//print '<tr height="80">';
	print '<td class="tdtop titlefield">'.$langs->trans("Intolerances");
	print '<br><input type="checkbox" id="alert_traitintol" name="alert_traitintol"'.((isset($_POST['alert_traitintol'])?GETPOST('alert_traitintol'):$object->alert_traitintol)?' checked="true"':'').'">  <label for="alert_traitintol">'.$langs->trans("Alert").'</label>';
	print '</td>';
	print '<td class="tdtop">';
	if ($action == 'edit' && $user->rights->societe->creer) {
		print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

		// Editeur wysiwyg
		require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";
		$doleditor=new DolEditor('note_traitintol', $object->note_traitintol, 0, $height, 'dolibarr_notes', 'In', false, false, isModEnabled("fckeditor"), 8, '95%');
		$doleditor->Create();
	} else {
		print nl2br($object->note_traitintol);
	}
	print "</td></tr>";

	print '</table>';
	print '</div></div>';


	print '<div class="fichecenter"><div class="fichehalfleft">';
	print '<table class="border" width="100%" style="margin-bottom: 2px !important;">';

	print '<tr height="80"><td class="tdtop titlefield">'.$langs->trans("AntecedentsChirOrtho");
	print '<br><input type="checkbox" id="alert_antechirortho" name="alert_antechirortho"'.((isset($_POST['alert_antechirortho'])?GETPOST('alert_antechirortho'):$object->alert_antechirortho)?' checked="checked"':'').'">  <label for="alert_antechirortho">'.$langs->trans("Alert").'</label>';
	print '</td>';
	print '<td class="tdtop">';
	if ($action == 'edit' && $user->rights->societe->creer) {
		print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

		// Editeur wysiwyg
		require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";
		$doleditor=new DolEditor('note_antechirortho', $object->note_antechirortho, 0, $height, 'dolibarr_notes', 'In', false, false, isModEnabled("fckeditor"), 6, '95%');
		$doleditor->Create();
	} else {
		print nl2br($object->note_antechirortho);
	}
	print "</td>";
	//print "</tr>";

	print '</tr></table>';
	print '</div><div class="fichehalfright">';
	print '<table class="border" width="100%" style="margin-bottom: 2px !important;"><tr height="80">';

	//print '<tr height="80">';
	print '<td class="tdtop titlefield">'.$langs->trans("Allergies");
	print '<br><input type="checkbox" id="alert_traitallergie" name="alert_traitallergie"'.((isset($_POST['alert_traitallergie'])?GETPOST('alert_traitallergie'):$object->alert_traitallergie)?' checked="checked"':'').'"">  <label for="alert_traitallergie">'.$langs->trans("Alert").'</label>';
	print '</td>';
	print '<td class="tdtop">';
	if ($action == 'edit' && $user->rights->societe->creer) {
		print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

		// Editeur wysiwyg
		require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";
		$doleditor=new DolEditor('note_traitallergie', $object->note_traitallergie, 0, $height, 'dolibarr_notes', 'In', false, false, isModEnabled("fckeditor"), 6, '95%');
		$doleditor->Create();
	} else {
		print nl2br($object->note_traitallergie);
	}
	print "</td></tr>";

	print '</table>';
	print '</div></div>';


	print '<div class="fichecenter"><div class="fichehalfleft">';
	print '<table class="border" width="100%" style="margin-bottom: 2px !important;">';

	print '<tr height="80"><td class="tdtop titlefield">'.$langs->trans("AntecedentsRhumato");
	print '<br><input type="checkbox" id="alert_anterhum" name="alert_anterhum"'.((isset($_POST['alert_anterhum'])?GETPOST('alert_anterhum'):$object->alert_anterhum)?' checked="checked"':'').'">  <label for="alert_anterhum">'.$langs->trans("Alert").'</label>';
	print '</td>';
	print '<td class="tdtop">';
	if ($action == 'edit' && $user->rights->societe->creer) {
		print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

		// Editeur wysiwyg
		require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";
		$doleditor=new DolEditor('note_anterhum', $object->note_anterhum, 0, $height, 'dolibarr_notes', 'In', false, false, isModEnabled("fckeditor"), 6, '95%');
		$doleditor->Create();
	} else {
		print nl2br($object->note_anterhum);
	}
	print "</td>";

	print '</tr></table>';
	print '</div><div class="fichehalfright">';
	/*print '<table class="border" width="100%"><tr height="100%">';

	print '<td colspan="2">&nbsp;</td>';
	print "</tr>";

	print '</table>';*/
	print '</div></div>';

	print '<div class="fichecenter"></div>';

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
