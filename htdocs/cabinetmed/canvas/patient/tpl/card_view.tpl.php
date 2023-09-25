<?php
/* Copyright (C) 2010-2011 Regis Houssin       <regis@dolibarr.fr>
 * Copyright (C) 2011      Laurent Destailleur <eldy@users.sourceforge.net>
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

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

if (!isset($socid)) {
	$socid = GETPOST('socid', 'int');
}
if (empty($error)) {
	$error = 0;
}
if (empty($errors)) {
	$errors = array();
}

$object = $GLOBALS['object'];

global $db,$conf,$mysoc,$langs,$user,$hookmanager,$extrafields;

require_once DOL_DOCUMENT_ROOT ."/core/class/html.formcompany.class.php";
require_once DOL_DOCUMENT_ROOT ."/core/class/html.formfile.class.php";
require_once DOL_DOCUMENT_ROOT ."/core/lib/date.lib.php";
if (isModEnabled("adherent")) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
dol_include_once("/cabinetmed/lib/cabinetmed.lib.php");

$form=new Form($GLOBALS['db']);
$formcompany=new FormCompany($GLOBALS['db']);
$formadmin=new FormAdmin($GLOBALS['db']);
$formfile=new FormFile($GLOBALS['db']);
?>

<!-- BEGIN PHP TEMPLATE CARD_VIEW.TPL.PHP PATIENT -->

<?php

$head = societe_prepare_head($object);
$now=dol_now();

/*foreach($head as $key => $val)
{
	var_dump($val);
}*/

if ((float) DOL_VERSION < 7) dol_fiche_head($head, 'card', $langs->trans("Patient"), 0, 'patient@cabinetmed');
elseif ((float) DOL_VERSION < 15) dol_fiche_head($head, 'card', $langs->trans("Patient"), -1, 'patient@cabinetmed');
else dol_fiche_head($head, 'card', $langs->trans("Patient"), -1, 'user-injured');


dol_htmloutput_errors($error, $errors);


// Confirm delete third party
if ($action == 'delete' || ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile))) {
	print $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$object->id, $langs->trans("DeleteACompany"), $langs->trans("ConfirmDeleteCompany"), "confirm_delete", '', 0, "action-delete");
}


$linkback = '<a href="'.dol_buildpath('/cabinetmed/patients.php', 1).'">'.$langs->trans("BackToList").'</a>';
dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

print '<div class="fichecenter">';
print '<div class="fichehalfleft">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border tableforfield centpercent">';

// Prefix
if (! empty($conf->global->SOCIETE_USEPREFIX)) {  // Old not used prefix field
	print '<tr><td>'.$langs->trans('Prefix').'</td><td>'.dol_escape_htmltag($object->prefix_comm).'</td></tr>';
}

//if ($object->client)
//{
	print '<tr><td class="titlefield">';
	print $langs->trans('CustomerCode').'</td><td>';
	print $object->code_client;
	if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongPatientCode").')</font>';
	print '</td></tr>';
//}

// Barcode
if (getDolGlobalString('MAIN_MODULE_BARCODE')) {
	print '<tr><td>'.$langs->trans('Gencod').'</td><td>'.$object->barcode.'</td></tr>';
}

// Prof ids
$i=1; $j=0;
while ($i <= 6) {
	$key='CABINETMED_SHOW_PROFID'.$i;
	if (!getDolGlobalString($key)) {
		$i++;
		continue;
	}

	$idprof=$langs->transcountry('ProfId'.$i, $object->country_code);
	if ($idprof!='-') {
		print '<tr>';
		print '<td>'.$idprof.'</td><td>';
		$key='idprof'.$i;
		print dol_print_profids($object->$key, 'ProfId'.$i, $object->country_code, 1);
		if ($object->$key) {
			if ($object->id_prof_check($i, $object) > 0) {
				if (!empty($object->id_prof_url($i, $object))) {
					print ' &nbsp; '.$object->id_prof_url($i, $object);
				}
			} else {
				print ' <span class="error">('.$langs->trans("ErrorWrongValue").')</span>';
			}
		}
		print '</td>';
		print '</tr>';
		$j++;
	}
	$i++;
}
//if ($j % 2 == 1)  print '<td colspan="2"></td></tr>';

// Num secu
print '<tr>';
print '<td class="nowrap">'.$langs->trans('PatientVATIntra').'</td><td>';
if ($object->tva_intra) {
	$s='';
	$s.=$object->tva_intra;
	$s.='<input type="hidden" id="tva_intra" name="tva_intra" maxlength="20" value="'.$object->tva_intra.'">';

	if (empty($conf->global->MAIN_DISABLEVATCHECK)) {
		$s.=' &nbsp; ';

		if ($conf->use_javascript_ajax) {
			print "\n";
			print '<script type="text/javascript">';
			print "function CheckVAT(a) {\n";
			print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?vatNumber='+a,'".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."',500,285);\n";
			print "}\n";
			print '</script>';
			print "\n";
			$s.='<a href="#" class="hideonsmartphone" onclick="CheckVAT( $(\'#tva_intra\').val() );">'.$langs->trans("VATIntraCheck").'</a>';
			$s = $form->textwithpicto($s, $langs->trans("VATIntraCheckDesc", $langs->transnoentitiesnoconv("VATIntraCheck")), 1);
		} else {
			$s.='<a href="'.$langs->transcountry("VATIntraCheckURL", $object->country_id).'" class="hideonsmartphone" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"), 'help').'</a>';
		}
	}
	print $s;
} else {
	print '&nbsp;';
}
print '</td>';
print '</tr>';

// Type + Staff => Genre
$arr = $formcompany->typent_array(1);
$typent_label = (empty($arr[$object->typent_code]) ? '' : $arr[$object->typent_code]);
print '<tr><td>'.$langs->trans("Gender").'</td><td>';
print $typent_label;
print '</td>';
//print '<td>'.$langs->trans("Staff").'</td><td>'.$object->effectif.'</td>';
print '</tr>';

// Juridical status => Secteur activité
print '<tr><td>'.$langs->trans('ActivityBranch').'</td><td>'.$object->forme_juridique.'</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<div class="fichehalfright">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border tableforfield centpercent">';

// Tags / categories
if (isModEnabled("categorie") && ! empty($user->rights->categorie->lire)) {
	// Customer
	if ($object->prospect || $object->client) {
		print '<tr><td>' . $langs->trans("CustomersCategoriesShort") . '</td>';
		print '<td>';
		print $form->showCategories($object->id, 'customer', 1);
		print "</td></tr>";
	}

	// Supplier
	if ($object->fournisseur) {
		print '<tr><td>' . $langs->trans("SuppliersCategoriesShort") . '</td>';
		print '<td>';
		print $form->showCategories($object->id, 'supplier', 1);
		print "</td></tr>";
	}
}

// Default language
if (getDolGlobalString('MAIN_MULTILANGS')) {
	require_once DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php";
	print '<tr><td>'.$langs->trans("DefaultLang").'</td><td>';
	//$s=picto_from_langcode($object->default_lang);
	//print ($s?$s.' ':'');
	$langs->load("languages");
	$labellang = ($object->default_lang?$langs->trans('Language_'.$object->default_lang):'');
	print $labellang;
	print '</td></tr>';
}

// Other attributes
$parameters = array('socid'=>$socid);
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

// Inject age if a date is defined

if (! empty($object->array_options['options_birthdate'])) {
	//$birthdate=dol_mktime(0,0,0,$birthdatearray['mon']+1,($birthdatearray['mday']),($birthdatearray['year']+1900),true);
	$birthdate = $object->array_options['options_birthdate'];
	//var_dump($birthdatearray);
	$ageyear=convertSecondToTime($now-$birthdate, 'year') - 1970;
	$agemonth=convertSecondToTime($now-$birthdate, 'month');
	if ($ageyear >= 2) $agetoshow = '('.$ageyear.' '.$langs->trans("DurationYears").')';
	elseif ($agemonth >= 2) $agetoshow = '('.(($ageyear * 12) + $agemonth).' '.$langs->trans("DurationMonths").')';
	else $agetoshow = '('.(($ageyear * 12) + $agemonth).' '.$langs->trans("DurationMonth").')';
	$agetoshow = dol_print_date($object->array_options['options_birthdate'], 'day').' <span class="opacitymedium">'.$agetoshow.'</span>';
	print '<script type="text/javascript" language="javascript">';
	print "
		jQuery(document).ready(function() {
        	jQuery(\".societe_extras_birthdate\").html('".dol_escape_js($agetoshow)."');
		});";
	print '</script>'."\n";
}

// Ban
if (empty($conf->global->SOCIETE_DISABLE_BANKACCOUNT)) {
	print '<tr><td>';
	print '<table class="centpercent nobordernopadding"><tr><td>';
	print $langs->trans('RIB');
	print '<td><td align="right">';
	if ($user->rights->societe->creer) {
		if ((float) DOL_VERSION < 8.0) {
			print '<a class="editfielda" href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$object->id.'">'.img_edit().'</a>';
		} else {
			print '<a class="editfielda" href="'.DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.$object->id.'">'.img_edit().'</a>';
		}
	} else {
		print '&nbsp;';
	}
	print '</td></tr></table>';
	print '</td>';
	print '<td>';
	print $object->display_rib();
	print '</td></tr>';
}

// Parent company
if (empty($conf->global->SOCIETE_DISABLE_PARENTCOMPANY)) {
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('ParentPatient');
	print '</td>';
	if ($action != 'editparentcompany') print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editparentcompany&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'editparentcompany') {
		$form->form_thirdparty($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->parent, 'editparentcompany', 's.rowid <> '.$object->id, 1);
	} else {
		$form->form_thirdparty($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->parent, 'none', 's.rowid <> '.$object->id, 1);
	}
	print '</td>';
	print '</tr>';
}

// Sales representative
include DOL_DOCUMENT_ROOT.'/societe/tpl/linesalesrepresentative.tpl.php';

// Module Adherent
if (isModEnabled("adherent")) {
	$langs->load("members");
	print '<tr><td>'.$langs->trans("LinkedToDolibarrMember").'</td>';
	print '<td>';
	$adh=new Adherent($db);
	$result=$adh->fetch('', '', $object->id);
	if ($result > 0) {
		$adh->ref=$adh->getFullName($langs);
		print $adh->getNomUrl(1);
	} else {
		print '<span class="opacitymedium">'.$langs->trans("ThirdpartyNotLinkedToMember").'</span>';
	}
	print '</td>';
	print "</tr>\n";
}

	// Webservices url/key
if (isModEnabled("syncsupplierwebservices")) {
	print '<tr><td>'.$langs->trans("WebServiceURL").'</td><td>'.dol_print_url($object->webservices_url).'</td>';
	print '<td class="nowrap">'.$langs->trans('WebServiceKey').'</td><td>'.$object->webservices_key.'</td></tr>';
}

print '</table>';
print '</div>';

print '</div>';
print '<div class="clearboth"></div>';

dol_fiche_end();


/*
 *  Actions
 */

print '<div class="tabsAction">'."\n";

$parameters=array();
$reshook=$hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	if (! empty($object->email)) {
		$langs->load("mails");
		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'&action=presend&token='.newToken().'&mode=init">'.$langs->trans('SendMail').'</a></div>';
	} else {
		$langs->load("mails");
		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans('SendMail').'</a></div>';
	}

	if ($user->rights->societe->creer) {
		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a></div>'."\n";
	}

	if ($user->rights->societe->supprimer) {
		if ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)) {	// We can't use preloaded confirm form with jmobile
			print '<div class="inline-block divButAction"><span id="action-delete" class="butActionDelete">'.$langs->trans('Delete').'</span></div>'."\n";
		} else {
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans('Delete').'</a></div>'."\n";
		}
	}
}

print '</div>'."\n";


//Select mail models is same action as presend
if (GETPOST('modelselected')) {
	$action = 'presend';
}

if ((float) DOL_VERSION >= 7.0) {
	// Presend form
	$modelmail='thirdparty';
	$defaulttopic='Information';
	$diroutput = $conf->societe->dir_output;
	$trackid = 'thi'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
} elseif ($action == 'presend') {
	// By default if $action=='presend'
	$titreform='SendMail';
	$topicmail='';
	$action='send';
	$modelmail='thirdparty';

	print '<br>';
	print '<div class="titre">'.$langs->trans($titreform).'</div>';

	// Define output language
	$outputlangs = $langs;
	$newlang = '';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
		$newlang = GETPOST('lang_id', 'aZ09');
	}
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
		$newlang = $object->client->default_lang;
	}

	// Cree l'objet formulaire mail
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	$formmail = new FormMail($db);
	$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
	$formmail->fromtype = 'user';
	$formmail->fromid   = $user->id;
	$formmail->fromname = $user->getFullName($langs);
	$formmail->frommail = $user->email;
	$formmail->trackid='thi'.$object->id;
	if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2)) {	// If bit 2 is set
		include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'inv'.$object->id);
	}
	$formmail->withfrom=1;
	$formmail->withtopic=1;
	$liste=array();
	foreach ($object->thirdparty_and_contact_email_array(1) as $key=>$value) $liste[$key]=$value;
	$formmail->withto=GETPOST('sendto')?GETPOST('sendto'):$liste;
	$formmail->withtofree=0;
	$formmail->withtocc=$liste;
	$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
	$formmail->withfile=2;
	$formmail->withbody=1;
	$formmail->withdeliveryreceipt=1;
	$formmail->withcancel=1;
	// Tableau des substitutions
	$formmail->substit['__SIGNATURE__']=$user->signature;
	$formmail->substit['__USER_SIGNATURE__']=$user->signature;
	$formmail->substit['__PERSONALIZED__']='';		// deprecated
	$formmail->substit['__CONTACTCIVNAME__']='';


	// Tableau des parametres complementaires du post
	$formmail->param['action']=$action;
	$formmail->param['models']=$modelmail;
	$formmail->param['socid']=$object->id;
	$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?socid='.$object->id;

	// Init list of files
	if (GETPOST("mode")=='init') {
		$formmail->clear_attached_files();
		$formmail->add_attached_files($file, basename($file), dol_mimetype($file));
	}

	print $formmail->get_form();

	print '<br>';
}


if ($action != 'presend') {
	print '<br>';


	/*
	print '<table width="100%"><tr><td valign="top" width="50%">';
	print '<a name="builddoc"></a>'; // ancre

	$filedir=$conf->societe->dir_output.'/'.$object->id;
	$urlsource=$_SERVER["PHP_SELF"]."?socid=".$object->id;
	$genallowed=$user->rights->societe->creer;
	$delallowed=$user->rights->societe->supprimer;

	$var=true;

	print $formfile->showdocuments('company',$object->id,$filedir,$urlsource,$genallowed,$delallowed,'',0,0,0,28,0,'',0,'',$object->default_lang);

	print '</td>';
	print '<td>';
	print '</td>';
	print '</tr>';
	print '</table>';

	print '<br>';
	*/

	// Subsidiaries list
	$result=show_subsidiaries($conf, $langs, $db, $object);
}

?>

<!-- END PHP TEMPLATE -->
