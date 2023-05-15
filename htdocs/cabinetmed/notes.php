<?php
/* Copyright (C) 2001-2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012      Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
 *   \file       htdocs/cabinetmed/notes.php
 *   \brief      Tab for notes on third party
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

$langs->load("companies");

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('thirdpartycard','globalcard'));


// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'societe', $socid, '&societe');

$object = new Patient($db);
if ($socid > 0) $object->fetch($socid);


/*
 * Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if ($action == 'add' && ! GETPOST('cancel', 'alpha')) {
		$error=0;

		$db->begin();

		$result=$object->update_note(dol_html_entity_decode(dol_htmlcleanlastbr(GETPOST('note_private', 'none')?GETPOST('note_private', 'none'):GETPOST('note', 'none')), ENT_QUOTES), '_private');
		if ($result < 0) {
			$error++;
			$errors[]=$object->errors;
		}

		$alert_note = (GETPOST("alert_note") ? '1' : '0');
		$result=addAlert($db, 'alert_note', $socid, $alert_note);

		if ($result == '') {
			 $object->alert_note = $alert_note;
			 setEventMessages($langs->trans("RecordModifiedSuccessfully"), null);
		} else {
			$error++;
			setEventMessages($result, null, 'errors');
		}

		if (! $error) $db->commit();
		else $db->rollback();
	}
}


/*
 *	View
 */

if (getDolGlobalString('MAIN_DIRECTEDITMODE') && $user->rights->societe->creer) {
	$action='edit';
}

$form = new Form($db);

$help_url = '';

llxHeader('', $langs->trans("Patient").' - '.$langs->trans("Notes"), $help_url);

if ($socid > 0) {
	/*
	 * Affichage onglets
	 */
	if (isModEnabled("notification")) $langs->load("mails");

	$head = societe_prepare_head($object);
	if ((float) DOL_VERSION < 7) dol_fiche_head($head, 'tabnotes', $langs->trans("Patient"), 0, 'patient@cabinetmed');
	elseif ((float) DOL_VERSION < 15) dol_fiche_head($head, 'tabnotes', $langs->trans("Patient"), -1, 'patient@cabinetmed');
	else dol_fiche_head($head, 'tabnotes', $langs->trans("Patient"), -1, 'user-injured');


	print '<script type="text/javascript">
        var changed=false;
        jQuery(function() {
            jQuery(window).bind(\'beforeunload\', function(){
                /* alert(changed); */
                console.log(changed);
                if (changed) return \''.dol_escape_js($langs->transnoentitiesnoconv("WarningExitPageWithoutSaving")).'\';
            });
            jQuery(".flat").keydown(function (e) {
                    console.log("aa");
    			changed=true;
            });
            jQuery("#alert_note").change(function () {
                    console.log("bb");
			    changed=true;
            });
            jQuery(".ignorechange").click(function () {
                changed=false;
            });
         });
        </script>';

	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	$linkback = '<a href="'.dol_buildpath('/cabinetmed/patients.php', 1).'">'.$langs->trans("BackToList").'</a>';
	dol_banner_tab($object, 'socid', $linkback, ($user->socid?0:1), 'rowid', 'nom');

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield" width="100%">';

	if (! empty($conf->global->SOCIETE_USEPREFIX)) {  // Old not used prefix field
		print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
	}

	if ($object->client) {
		print '<tr><td class="titlefield">';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $object->code_client;
		if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
	}

	if ($object->fournisseur) {
		print '<tr><td class="titlefield">';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $object->code_fournisseur;
		if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
	}

	print '<tr><td class="tdtop titlefield">'.$langs->trans("NotePrivate");
	print '<br><input type="checkbox" id="alert_note" name="alert_note"'.((isset($_POST['alert_note'])?GETPOST('alert_note'):$object->alert_note)?' checked="checked"':'').'"> <label for="alert_note">'.$langs->trans("Alert").'</label>';
	print '</td>';
	print '<td class="tdtop">';
	$note=($object->note_private?$object->note_private:$object->note);
	if ($user->rights->societe->creer) {
		print '<input type="hidden" name="action" value="add" />';
		print '<input type="hidden" name="socid" value="'.$object->id.'" />';

		// Editeur wysiwyg
		require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";
		$doleditor=new DolEditor('note', $note, '', 360, 'dolibarr_notes', 'In', true, false, getDolGlobalInt('FCKEDITOR_ENABLE_SOCIETE') ? true : false, 20, '90%');
		$doleditor->Create(0, '.on( \'key\', function(e) { console.log("changed"); changed=true; }) ');  // Add on to detect changes with key pressed
		print '<br>';
	} else {
		print dol_textishtml($note)?$note:dol_nl2br($note, 1, true);
	}
	print "</td></tr>";

	print "</table>";

	if ($user->rights->societe->creer) {
		print '<center><br>';
		print '<input type="submit" class="button ignorechange" name="save" value="'.$langs->trans("Save").'">';
		print '</center>';
	}

	print '</form>';

	dol_fiche_end();
}


llxFooter();

$db->close();
