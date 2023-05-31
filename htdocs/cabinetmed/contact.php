<?php
/* Copyright (C) 2005      Patrick Rouillon     <patrick@rouillon.net>
 * Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *       \file       htdocs/cabinetmed/contact.php
 *       \ingroup    cabinetmed
 *       \brief      Tab for links between doctors and patient
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
include_once DOL_DOCUMENT_ROOT."/core/lib/ajax.lib.php";
include_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/contact/class/contact.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
include_once "./class/patient.class.php";
include_once "./class/cabinetmedcons.class.php";

$langs->load("cabinetmed@cabinetmed");
$langs->load("orders");
$langs->load("sendings");
$langs->load("companies");

$action = GETPOST('action');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('thirdpartycard','globalcard'));

$id = GETPOST('socid', 'int');
$ref= GETPOST('ref');

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'societe', $socid);

$object = new Patient($db);


/*
 * Add new contact
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if ($action == 'addcontact' && $user->rights->societe->creer) {
		if (GETPOST("contactid", "int") && GETPOST("type")) {
			$result = 0;
			$societe = new Societe($db);
			$result = $societe->fetch($socid);

			if ($result > 0 && $socid > 0) {
				$result = $societe->add_contact(GETPOST("contactid", 'int'), GETPOST("type"), GETPOST("source"));
			}

			if ($result >= 0) {
				Header("Location: contact.php?socid=".$societe->id);
				exit;
			} else {
				if ($societe->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					$langs->load("errors");
					$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
				} else {
					$mesg = '<div class="error">'.$societe->error.'</div>';
				}
			}
		}
	}

	// bascule du statut d'un contact
	if ($action == 'swapstatut' && $user->rights->societe->creer) {
		$object = new Societe($db);
		if ($object->fetch(GETPOST('facid', 'int'))) {
			$result=$object->swapContactStatus(GETPOST('ligne', 'int'));
		} else {
			dol_print_error($db);
		}
	}

	// Efface un contact
	if ($action == 'deleteline' && $user->rights->societe->creer) {
		$societe = new Societe($db);
		$societe->fetch($socid);
		$result = $societe->delete_contact(GETPOST("lineid", 'int'));

		if ($result >= 0) {
			Header("Location: contact.php?socid=".$societe->id);
			exit;
		} else {
			dol_print_error($db);
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);

llxHeader('', $langs->trans('Contacts'), '');

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
if (isset($mesg)) print $mesg;

if ($id > 0 || ! empty($ref)) {
	$societe = new Patient($db);
	$societe->fetch($id);

	$object = $societe;		// Use on test by module tabs declaration


	$head = societe_prepare_head($societe);
	if ((float) DOL_VERSION < 7) dol_fiche_head($head, 'tabpatientcontacts', $langs->trans("Patient"), 0, 'patient@cabinetmed');
	elseif ((float) DOL_VERSION < 15) dol_fiche_head($head, 'tabpatientcontacts', $langs->trans("Patient"), -1, 'patient@cabinetmed');
	else dol_fiche_head($head, 'tabpatientcontacts', $langs->trans("Patient"), -1, 'user-injured');

	$width=300;
	print '
            <style>
            .ui-autocomplete-input { width: '.$width.'px; }
            </style>
            ';

	print ajax_combobox('contactid');

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	$linkback = '<a href="'.dol_buildpath('/cabinetmed/patients.php', 1).'">'.$langs->trans("BackToList").'</a>';
	dol_banner_tab($object, 'socid', $linkback, ($user->socid?0:1), 'rowid', 'nom');

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield" width="100%">';

	//if ($societe->client)
	//{
		print '<tr><td class="titlefield">';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $societe->code_client;
		if ($societe->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongPatientCode").')</font>';
		print '</td></tr>';
	//}

	if ($societe->fournisseur) {
		print '<tr><td class="titlefield">';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $societe->code_fournisseur;
		if ($societe->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
	}

	print "</table>";
	print '</div>';

	print '</form>';

	dol_fiche_end();

	/*
	* Lines of contacts
	*/
	print '<form action="contact.php?socid='.$socid.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="addcontact">';
	print '<input type="hidden" name="source" value="external">';
	print '<input type="hidden" name="socid" value="'.$socid.'">';

	print '<br>';

	print '<table class="noborder" width="100%">';

	/*
	* Ajouter une ligne de contact
	* Non affiche en mode modification de ligne
	*/
	if ($action != 'editline') {
		print '<thead><tr class="liste_titre">';
		//print '<td>'.$langs->trans("Source").'</td>';
		print '<td>'.$langs->trans("Contacts").'</td>';
		print '<td>'.$langs->trans("ContactType").'</td>';
		print '<td colspan="3">&nbsp;</td>';
		print "</tr></thead>\n";

		// Line to add contacts
		print '<tr class="oddeven">';

		print '<td>';
		// $contactAlreadySelected = $commande->getListContactId('external');	// On ne doit pas desactiver un contact deja selectionner car on doit pouvoir le seclectionner une deuxieme fois pour un autre type
		print $form->selectcontacts(0, '', 'contactid', 1, '', '', 1);
		$nbofcontacts = $form->num;
		//if ($nbofcontacts == 0) print $langs->trans("NoContactDefined");
		if (versioncompare(versiondolibarrarray(), array(3,7,-3)) >= 0) {
			print ' <a href="'.DOL_URL_ROOT.'/contact/card.php?leftmenu=contacts&action=create&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?socid='.$socid.'">'.$langs->trans("Add").'</a>';
		} else {
			print ' <a href="'.DOL_URL_ROOT.'/contact/card.php?leftmenu=contacts&action=create&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?socid='.$socid.'">'.$langs->trans("Add").'</a>';
		}
		print '</td>';
		print '<td>';
		$formcompany->selectTypeContact($societe, '', 'type', 'external', 'libelle', 1);
		//if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
		print '</td>';
		print '<td align="center" colspan="3" ><input type="submit" class="button small" value="'.$langs->trans("AddLink").'"';
		if (! $nbofcontacts) print ' disabled="disabled"';
		print '></td>';
		print '</tr>';

		print "</form>";
	}


	// List of linked contacts
	print '<tr class="liste_titre">';
	//print '<td>'.$langs->trans("Source").'</td>';
	print '<td>'.$langs->trans("Contacts").'</td>';
	print '<td>'.$langs->trans("ContactType").'</td>';
	print '<td class="center">'.$langs->trans("Status").'</td>';
	print '<td colspan="2">&nbsp;</td>';
	print "</tr>\n";

	$companystatic=new Societe($db);

	foreach (array('external') as $source) {
		$tab = $societe->liste_contact(-1, $source);
		$num=count($tab);

		$i = 0;
		while ($i < $num) {
			print '<tr>';

			// Source
			/*print '<td align="left">';
			if ($tab[$i]['source']=='internal') print $langs->trans("User");
			if ($tab[$i]['source']=='external') print $langs->trans("ThirdPartyContact");
			print '</td>';
			*/

			// Societe
			/*print '<td align="left">';
			if ($tab[$i]['socid'] > 0)
			{
				$companystatic->fetch($tab[$i]['socid']);
				print $companystatic->getNomUrl(1);
			}
			if ($tab[$i]['socid'] < 0)
			{
				print $conf->global->MAIN_INFO_SOCIETE_NOM;
			}
			if (! $tab[$i]['socid'])
			{
				print '&nbsp;';
			}
			print '</td>';
			*/

			// Contact
			print '<td>';
			if ($tab[$i]['source']=='internal') {
				$userstatic->id=$tab[$i]['id'];
				$userstatic->lastname=$tab[$i]['lastname'];
				$userstatic->firstname=$tab[$i]['firstname'];
				print $userstatic->getNomUrl(1);
			}
			if ($tab[$i]['source']=='external') {
				$contactstatic->id=$tab[$i]['id'];
				$contactstatic->lastname=$tab[$i]['lastname'];
				$contactstatic->firstname=$tab[$i]['firstname'];
				print $contactstatic->getNomUrl(1);
			}
			print '</td>';

			// Type de contact
			print '<td>'.$tab[$i]['libelle'].'</td>';

			// Statut
			print '<td class="center">';
			// Activation desativation du contact
			if ($societe->statut >= 0)	print '<a href="contact.php?socid='.$societe->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'">';
			print $contactstatic->LibStatut($tab[$i]['status'], 3);
			if ($societe->statut >= 0)	print '</a>';
			print '</td>';

			// Icon update et delete
			print '<td align="center" nowrap>';
			if ($societe->statut < 5 && $user->rights->societe->creer) {
				print '&nbsp;';
				print '<a href="contact.php?socid='.$societe->id.'&action=deleteline&lineid='.$tab[$i]['rowid'].'&token='.newToken().'">';
				print img_delete();
				print '</a>';
			}
			print '</td>';

			print "</tr>\n";

			$i ++;
		}
	}
	print "</table>";
}

// End of page
llxFooter();
$db->close();
