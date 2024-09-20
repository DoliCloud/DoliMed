<?php
/* Copyright (C) 2011 Laurent Destailleur <eldy@users.sourceforge.net>
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
	exit(1);
}


$object=$GLOBALS['object'];

global $db,$conf,$mysoc,$langs,$user,$hookmanager,$extrafields;


$socialnetworks = getArrayOfSocialNetworks();


require_once DOL_DOCUMENT_ROOT ."/core/class/html.formcompany.class.php";
require_once DOL_DOCUMENT_ROOT ."/core/class/html.formfile.class.php";
require_once DOL_DOCUMENT_ROOT ."/core/lib/company.lib.php";

$langs->loadLangs(array("cabinetmed@cabinetmed"));

$form=new Form($GLOBALS['db']);
$formcompany=new FormCompany($GLOBALS['db']);
$formadmin=new FormAdmin($GLOBALS['db']);
$formfile=new FormFile($GLOBALS['db']);


// Load object modCodeTiers
$module = getDolGlobalString('SOCIETE_CODECLIENT_ADDON');
if (! $module) dolibarr_error('', $langs->trans("ErrorModuleThirdPartyCodeInCompanyModuleNotDefined"));
if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php') {
	$module = substr($module, 0, dol_strlen($module)-4);
}
// Load object modCodeClient
$dirsociete=array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
foreach ($dirsociete as $dirroot) {
	$res=dol_include_once($dirroot.$module.".php");
	if ($res) break;
}
$modCodeClient = new $module($db);
// We verified if the tag prefix is used
if ($modCodeClient->code_auto) {
	$prefixCustomerIsUsed = $modCodeClient->verif_prefixIsUsed();
}
$modCodeFournisseur = new $module($db);
// On verifie si la balise prefix est utilisee
if ($modCodeFournisseur->code_auto) {
	$prefixSupplierIsUsed = $modCodeFournisseur->verif_prefixIsUsed();
}


if (GETPOST("name")) {
	$object->client = 1;

	$object->lastname=GETPOST("name");
	$object->firstname=GETPOST("firstname");
	$object->particulier=0;
	$object->prefix_comm=GETPOST("prefix_comm");
	$object->client=GETPOST("client")?GETPOST("client"):$object->client;
	$object->code_client=GETPOST("code_client") ? GETPOST("code_client") : GETPOST("customer_code");
	$object->fournisseur=GETPOST("fournisseur")?GETPOST("fournisseur"):$object->fournisseur;
	$object->code_fournisseur=GETPOST("code_fournisseur") ? GETPOST("code_fournisseur") : GETPOST("supplier_code");
	$object->adresse=GETPOST("address"); // TODO obsolete
	$object->address=GETPOST("address");
	$object->zip=GETPOST("zipcode");
	$object->town=GETPOST("town");
	$object->state_id=GETPOST("departement_id");
	$object->parent = GETPOSTINT('parent_company_id');

	$object->socialnetworks = array();
	if (isModEnabled('socialnetworks')) {
		foreach ($socialnetworks as $key => $value) {
			if (GETPOSTISSET($key) && GETPOST($key, 'alphanohtml') != '') {
				$object->socialnetworks[$key] = GETPOST($key, 'alphanohtml');
			}
		}
	}

	$object->phone					= GETPOST('phone', 'alpha');
	$object->phone_mobile			= (string) GETPOST('phone_mobile', 'alpha');
	$object->fax					= GETPOST('fax', 'alpha');
	$object->email					= GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
	$object->no_email				= GETPOSTINT("no_email");
	$object->url					= GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
	$object->capital				= GETPOST('capital', 'alphanohtml');
	$object->idprof1				= GETPOST('idprof1', 'alphanohtml');
	$object->idprof2				= GETPOST('idprof2', 'alphanohtml');
	$object->idprof3				= GETPOST('idprof3', 'alphanohtml');
	$object->idprof4				= GETPOST('idprof4', 'alphanohtml');
	$object->idprof5				= GETPOST('idprof5', 'alphanohtml');
	$object->idprof6				= GETPOST('idprof6', 'alphanohtml');
	$object->typent_id = GETPOSTINT('typent_id');
	$object->effectif_id = GETPOSTINT('effectif_id');
	$object->barcode				= GETPOST('barcode', 'alphanohtml');
	$object->forme_juridique_code = GETPOSTINT('forme_juridique_code');
	$object->default_lang = GETPOST('default_lang', 'alpha');

	$object->tva_assuj				= GETPOSTINT('assujtva_value');
	$object->vat_reverse_charge		= GETPOST('vat_reverse_charge') == 'on' ? 1 : 0;
	$object->tva_intra				= GETPOST('tva_intra', 'alphanohtml');
	$object->status =				GETPOSTINT('status');

	//Local Taxes
	$object->localtax1_assuj		= GETPOST('localtax1assuj_value');
	$object->localtax2_assuj		= GETPOST('localtax2assuj_value');

	$object->localtax1_value		= GETPOST('lt1');
	$object->localtax2_value		= GETPOST('lt2');

	// We set country_id, and country_code label of the chosen country
	$object->country_id = GETPOST("country_id")?GETPOST("country_id"):$mysoc->country_id;
	if ($object->country_id > 0) {
		$tmparray = getCountry($object->country_id, 'all');
		$object->country_code = $tmparray['code'];
		$object->country = $tmparray['label'];
	}

	// We set multicurrency_code if enabled
	if (isModEnabled("multicurrency")) {
		$object->multicurrency_code = GETPOST('multicurrency_code') ? GETPOST('multicurrency_code') : $object->multicurrency_code;
	}

	$object->forme_juridique_code = GETPOST('forme_juridique_code');
}

?>

<!-- BEGIN PHP TEMPLATE CARD_EDIT.TPL.PHP PATIENT -->

<?php
print_fiche_titre($langs->trans("EditPatient"));

print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'" method="post" name="formsoc">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="socid" value="'.$object->id.'">';
print '<input type="hidden" name="entity" value="'.$object->entity.'">';
print '<input type="hidden" name="private" value="0">';
print '<input type="hidden" name="status" value="'.$object->status.'">';
print '<input type="hidden" name="client" value="'.$object->client.'">';
if ($modCodeClient->code_auto || $modCodeFournisseur->code_auto) print '<input type="hidden" name="code_auto" value="1">';


dol_fiche_head('');

print '<table class="border centpercent">';

// Name
print '<tr><td class="titlefield"><span class="fieldrequired">'.$langs->trans('PatientName').'</span></td><td colspan="3"><input type="text" size="40" maxlength="60" name="name" value="'.$object->name.'"></td>';


// Prospect/Customer
print '<tr><td>'.fieldLabel('ProspectCustomer', 'customerprospect', 1).'</td>';
print '<td class="maxwidthonsmartphone">';
$nothingvalue=0;
$prospectonly=2;
if (! empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) {
	print '<input type="hidden" name="client" value="3">';
	print $langs->trans("Patient");
} else {
	if (! empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) $nothingvalue=1;  // if feature to disable customer is on, nothing will keep value 1 in database.
	if (! empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) $prospectonly=3;  // if feature to disable customer is on, nothing will keep value 3 in database.
	print '<select class="flat" name="client" id="customerprospect">';
	if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print '<option value="'.$prospectonly.'"'.($object->client==$prospectonly?' selected':'').'>'.$langs->trans('Prospect').'</option>';
	if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print '<option value="3"'.($object->client==3?' selected':'').'>'.$langs->trans('ProspectCustomer').'</option>';
	if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print '<option value="1"'.($object->client==1?' selected':'').'>'.$langs->trans('Customer').'</option>';
	print '<option value="'.$nothingvalue.'"'.($object->client==$nothingvalue?' selected':'').'>'.$langs->trans('NorProspectNorCustomer').'</option>';
	print '</select>';
}
print '</td>';
print '<td width="25%">'.fieldLabel('CustomerCode', 'customer_code').'</td><td width="25%">';

print '<table class="nobordernopadding"><tr><td>';
if ((!$object->code_client || $object->code_client == -1) && $modCodeClient->code_auto) {
	$tmpcode=$object->code_client;
	if (empty($tmpcode) && ! empty($object->oldcopy->code_client)) $tmpcode=$object->oldcopy->code_client; // When there is an error to update a thirdparty, the number for supplier and customer code is kept to old value.
	if (empty($tmpcode) && ! empty($modCodeClient->code_auto)) $tmpcode=$modCodeClient->getNextValue($object, 0);
	print '<input type="text" name="code_client" id="customer_code" size="16" value="'.dol_escape_htmltag($tmpcode).'" maxlength="24">';
} elseif ($object->codeclient_modifiable()) {
	print '<input type="text" name="code_client" id="customer_code" size="16" value="'.$object->code_client.'" maxlength="24">';
} else {
	print $object->code_client;
	print '<input type="hidden" name="code_client" value="'.$object->code_client.'">';
}
print '</td><td>';
$s=$modCodeClient->getToolTip($langs, $object, 0);
print $form->textwithpicto('', $s, 1);
print '</td></tr></table>';

print '</td></tr>';

// Supplier
if (((isModEnabled("fournisseur") && $user->hasRight('fournisseur', 'lire') && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) || (isModEnabled("supplier_order") && $user->hasRight('supplier_order', 'lire')) || (isModEnabled("supplier_invoice") && $user->hasRight('supplier_invoice', 'lire')))
|| (isModEnabled('supplier_proposal') && $user->hasRight('supplier_proposal', 'lire'))) {
	print '<tr>';
	print '<td>'.$form->editfieldkey('Supplier', 'fournisseur', '', $object, 0, 'string', '', 1).'</td>';
	print '<td class="maxwidthonsmartphone">';
	print $form->selectyesno("fournisseur", $object->fournisseur, 1, false, 0, 1);
	print '</td>';
	if ($conf->browser->layout == 'phone') {
		print '</tr><tr>';
	}
	print '<td>';
	if ((isModEnabled("fournisseur") && $user->hasRight('fournisseur', 'lire') && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) || (isModEnabled("supplier_order") && $user->hasRight('supplier_order', 'lire')) || (isModEnabled("supplier_invoice") && $user->hasRight('supplier_invoice', 'lire'))) {
		print $form->editfieldkey('SupplierCode', 'supplier_code', '', $object, 0);
	}
	print '</td>';
	print '<td>';
	print '<table class="nobordernopadding"><tr><td>';
	if ((!$object->code_fournisseur || $object->code_fournisseur == -1) && $modCodeFournisseur->code_auto) {
		$tmpcode = $object->code_fournisseur;
		if (empty($tmpcode) && !empty($object->oldcopy->code_fournisseur)) {
			$tmpcode = $object->oldcopy->code_fournisseur; // When there is an error to update a thirdparty, the number for supplier and customer code is kept to old value.
		}
		if (empty($tmpcode) && !empty($modCodeFournisseur->code_auto)) {
			$tmpcode = $modCodeFournisseur->getNextValue($object, 1);
		}
		print '<input type="text" name="supplier_code" id="supplier_code" size="16" value="'.dol_escape_htmltag($tmpcode).'" maxlength="24">';
	} elseif ($object->codefournisseur_modifiable()) {
		print '<input type="text" name="supplier_code" id="supplier_code" size="16" value="'.dol_escape_htmltag($object->code_fournisseur).'" maxlength="24">';
	} else {
		print $object->code_fournisseur;
		print '<input type="hidden" name="supplier_code" value="'.$object->code_fournisseur.'">';
	}
	print '</td><td>';
	$s = $modCodeFournisseur->getToolTip($langs, $object, 1);
	print $form->textwithpicto('', $s, 1);
	print '</td></tr></table>';
	print '</td></tr>';
}

// Barcode
if (isModEnabled('barcode')) {
	print '<tr><td class="tdtop">'.$form->editfieldkey('Gencod', 'barcode', '', $object, 0).'</td>';
	print '<td colspan="3">';
	print img_picto('', 'barcode', 'class="pictofixedwidth"');
	print '<input type="text" name="barcode" id="barcode" value="'.dol_escape_htmltag($object->barcode).'">';
	print '</td></tr>';
}

// Status
print '<tr><td>'.fieldLabel('Status', 'status').'</td><td colspan="3">';
print $form->selectarray('status', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')), 1);
print '</td></tr>';

// Address
print '<tr><td class="tdtop">'.$langs->trans('Address').'</td><td colspan="3"><textarea name="address" class="quatrevingtpercent" rows="3" wrap="soft">';
print $object->address;
print '</textarea></td></tr>';

// Zip / Town
print '<tr><td>'.$langs->trans('Zip').'</td><td>';
print $formcompany->select_ziptown($object->zip, 'zipcode', array('town','selectcountry_id','departement_id'), 6);
print '</td><td>'.$langs->trans('Town').'</td><td>';
print $formcompany->select_ziptown($object->town, 'town', array('zipcode','selectcountry_id','departement_id'));
print '</td></tr>';

// Country
print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
print $form->select_country($object->country_id, 'country_id');
if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
print '</td></tr>';

// State
if (!getDolGlobalString('SOCIETE_DISABLE_STATE')) {
	if ((getDolGlobalInt('MAIN_SHOW_REGION_IN_STATE_SELECT') == 1 || getDolGlobalInt('MAIN_SHOW_REGION_IN_STATE_SELECT') == 2)) {
		print '<tr><td>'.$form->editfieldkey('Region-State', 'state_id', '', $object, 0).'</td><td colspan="3">';
	} else {
		print '<tr><td>'.$form->editfieldkey('State', 'state_id', '', $object, 0).'</td><td colspan="3">';
	}

	print img_picto('', 'state', 'class="pictofixedwidth"');
	print $formcompany->select_state($object->state_id, $object->country_code);
	print '</td></tr>';
}

// Phone / Fax
print '<tr><td>'.$form->editfieldkey('Phone', 'phone', GETPOST('phone', 'alpha'), $object, 0).'</td>';
print '<td'.($conf->browser->layout == 'phone' ? ' colspan="3"' : '').'>'.img_picto('', 'object_phoning', 'class="pictofixedwidth"').' <input type="text" name="phone" id="phone" class="maxwidth200 widthcentpercentminusx" value="'.(GETPOSTISSET('phone') ? GETPOST('phone', 'alpha') : $object->phone).'"></td>';
if ($conf->browser->layout == 'phone') {
	print '</tr><tr>';
}
print '<td>'.$form->editfieldkey('PhoneMobile', 'phone_mobile', GETPOST('phone_mobile', 'alpha'), $object, 0).'</td>';
print '<td'.($conf->browser->layout == 'phone' ? ' colspan="3"' : '').'>'.img_picto('', 'object_phoning_mobile', 'class="pictofixedwidth"').' <input type="text" name="phone_mobile" id="phone_mobile" class="maxwidth200 widthcentpercentminusx" value="'.(GETPOSTISSET('phone_mobile') ? GETPOST('phone_mobile', 'alpha') : $object->phone_mobile).'"></td></tr>';

print '<td>'.$form->editfieldkey('Fax', 'fax', GETPOST('fax', 'alpha'), $object, 0).'</td>';
print '<td'.($conf->browser->layout == 'phone' ? ' colspan="3"' : '').'>'.img_picto('', 'object_phoning_fax', 'class="pictofixedwidth"').' <input type="text" name="fax" id="fax" class="maxwidth200 widthcentpercentminusx" value="'.(GETPOSTISSET('fax') ? GETPOST('fax', 'alpha') : $object->fax).'"></td>';
print '</tr>';

// Web
print '<tr><td>'.$form->editfieldkey('Web', 'url', GETPOST('url', 'alpha'), $object, 0).'</td>';
print '<td colspan="3">'.img_picto('', 'globe', 'class="pictofixedwidth"').' <input type="text" name="url" id="url" class="maxwidth200onsmartphone maxwidth300 widthcentpercentminusx " value="'.(GETPOSTISSET('url') ? GETPOST('url', 'alpha') : $object->url).'"></td></tr>';

// EMail
print '<tr><td>'.$form->editfieldkey('EMail', 'email', GETPOST('email', 'alpha'), $object, 0, 'string', '', (getDolGlobalString('SOCIETE_EMAIL_MANDATORY'))).'</td>';
print '<td colspan="3">';
print img_picto('', 'object_email', 'class="pictofixedwidth"');
print '<input type="text" name="email" id="email" class="maxwidth500 widthcentpercentminusx" value="'.(GETPOSTISSET('email') ? GETPOST('email', 'alpha') : $object->email).'">';
print '</td></tr>';

// Unsubscribe
if (isModEnabled('mailing')) {
	if ($conf->use_javascript_ajax && getDolGlobalInt('MAILING_CONTACT_DEFAULT_BULK_STATUS') == 2) {
		print "\n".'<script type="text/javascript">'."\n";

		print '
						jQuery(document).ready(function () {
							function init_check_no_email(input) {
								if (input.val()!="") {
									$(".noemail").addClass("fieldrequired");
								} else {
									$(".noemail").removeClass("fieldrequired");
								}
							}
							$("#email").keyup(function() {
								init_check_no_email($(this));
							});
							init_check_no_email($("#email"));
						})'."\n";
		print '</script>'."\n";
	}
	if (!GETPOSTISSET("no_email") && !empty($object->email)) {
		$result = $object->getNoEmail();
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	print '<tr>';
	print '<td class="noemail"><label for="no_email">'.$langs->trans("No_Email").'</label></td>';
	print '<td>';
	$useempty = (getDolGlobalInt('MAILING_CONTACT_DEFAULT_BULK_STATUS') == 2);
	print $form->selectyesno('no_email', (GETPOSTISSET("no_email") ? GETPOSTINT("no_email") : $object->no_email), 1, false, $useempty);
	print '</td>';
	print '</tr>';
}

// Social network
if (isModEnabled('socialnetworks')) {
	$object->showSocialNetwork($socialnetworks, ($conf->browser->layout == 'phone' ? 2 : 4));
}

// Prof ids
$i = 1;
$j = 0;
$NBCOLS = ($conf->browser->layout == 'phone' ? 1 : 2);
$NBPROFIDMIN = getDolGlobalInt('THIRDPARTY_MIN_NB_PROF_ID', 2);
$NBPROFIDMAX = getDolGlobalInt('THIRDPARTY_MAX_NB_PROF_ID', 6);
while ($i <= $NBPROFIDMAX) {
	$key='CABINETMED_SHOW_PROFID'.$i;
	if (empty($conf->global->$key)) { $i++; continue; }

	$idprof = $langs->transcountry('ProfId'.$i, $object->country_code);
	if ($idprof != '-' && ($i <= $NBPROFIDMIN || !empty($langs->tab_translate['ProfId'.$i.$object->country_code]))) {
		$key = 'idprof'.$i;

		if (($j % $NBCOLS) == 0) {
			print '<tr>';
		}

		$idprof_mandatory = 'SOCIETE_IDPROF'.($i).'_MANDATORY';
		print '<td>'.$form->editfieldkey($idprof, $key, '', $object, 0, 'string', '', !(empty($conf->global->$idprof_mandatory) || !$object->isACompany())).'</td><td>';
		print $formcompany->get_input_id_prof($i, $key, $object->$key, $object->country_code);
		print '</td>';
		if (($j % $NBCOLS) == ($NBCOLS - 1)) {
			print '</tr>';
		}
		$j++;
	}
	$i++;
}
if ($NBCOLS > 0 && $j % 2 == 1) {
	print '<td colspan="2"></td></tr>';
}

/*
print '<tr>';
// Height
$idprof=$langs->trans('HeightPeople');
print '<td>'.$idprof.'</td><td>';
print '<input type="text" name="idprof1" size="6" maxlength="6" value="'.$object->idprof1.'">';
print '</td>';
// Weight
$idprof=$langs->trans('Weight');
print '<td>'.$idprof.'</td><td>';
print '<input type="text" name="idprof2" size="6" maxlength="6" value="'.$object->idprof2.'">';
print '</td>';
print '</tr>';
print '<tr>';
// Date ot birth
$idprof=$langs->trans(((float) DOL_VERSION < 13) ? 'DateToBirth' : 'DateOfBirth'));
print '<td>'.$idprof.'</td><td colspan="3">';
print '<input type="text" name="idprof3" size="18" maxlength="32" value="'.$object->idprof3.'"> ('.$conf->format_date_short_java.')';
print '</td>';
print '</tr>';
*/

// VAT reverse charge by default
if (getDolGlobalString('ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE')) {
	print '<tr><td>' . $form->editfieldkey('VATReverseChargeByDefault', 'vat_reverse_charge', '', $object, 0) . '</td><td colspan="3">';
	print '<input type="checkbox" name="vat_reverse_charge" '.($object->vat_reverse_charge == '1' ? ' checked' : '').'>';
	print '</td></tr>';
}

// Num secu
print '<tr>';
print '<td class="nowrap">'.$langs->trans('PatientVATIntra').'</td>';
print '<td class="nowrap" colspan="3">';
$s ='<input type="text" class="flat" name="tva_intra" size="18" maxlength="20" value="'.$object->tva_intra.'">';
print $s;
print '</td></tr>';

// Sexe
print '<tr><td>'.$langs->trans("Gender").'</td><td colspan="3">';
print $form->selectarray("typent_id", $formcompany->typent_array(0, "AND code in ('TE_UNKNOWN', 'TE_HOMME', 'TE_FEMME')"), $object->typent_id);
if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
print '</td>';
print '</tr>';

// Juridical status
print '<tr><td>'.$langs->trans('ActivityBranch').'</td><td colspna="3">';
print $formcompany->select_juridicalstatus($object->forme_juridique_code, $object->country_code, "AND (f.module = 'cabinetmed' OR f.code > '100000')");
print '</td>';
print '</tr>';

// Default language
if (getDolGlobalInt('MAIN_MULTILANGS')) {
	print '<tr><td>'.$form->editfieldkey('DefaultLang', 'default_lang', '', $object, 0).'</td><td colspan="3">'."\n";
	print img_picto('', 'language', 'class="pictofixedwidth"').$formadmin->select_language($object->default_lang, 'default_lang', 0, null, '1', 0, 0, 'maxwidth300 widthcentpercentminusx');
	print '</td>';
	print '</tr>';
}

// Incoterms
/*if (isModEnabled('incoterm')) {
	print '<tr>';
	print '<td>'.$form->editfieldkey('IncotermLabel', 'incoterm_id', '', $object, 0).'</td>';
	print '<td colspan="3" class="maxwidthonsmartphone">';
	print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''));
	print '</td></tr>';
}*/

// Categories
if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
	// Customer
	print '<tr class="visibleifcustomer"><td>'.$form->editfieldkey('CustomersCategoriesShort', 'custcats', '', $object, 0).'</td>';
	print '<td colspan="3">';
	$cate_arbo = $form->select_all_categories(Categorie::TYPE_CUSTOMER, '', '', 64, 0, 3);
	$c = new Categorie($db);
	$cats = $c->containing($object->id, Categorie::TYPE_CUSTOMER);
	$arrayselected = array();
	foreach ($cats as $cat) {
		$arrayselected[] = $cat->id;
	}
	print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('custcats', $cate_arbo, $arrayselected, 0, 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
	print "</td></tr>";

	// Supplier
	if ((isModEnabled("fournisseur") && $user->hasRight('fournisseur', 'lire') && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) || (isModEnabled("supplier_order") && $user->hasRight('supplier_order', 'lire')) || (isModEnabled("supplier_invoice") && $user->hasRight('supplier_invoice', 'lire'))) {
		print '<tr class="visibleifsupplier"><td>'.$form->editfieldkey('SuppliersCategoriesShort', 'suppcats', '', $object, 0).'</td>';
		print '<td colspan="3">';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_SUPPLIER, '', '', 64, 0, 3);
		$c = new Categorie($db);
		$cats = $c->containing($object->id, Categorie::TYPE_SUPPLIER);
		$arrayselected = array();
		foreach ($cats as $cat) {
			$arrayselected[] = $cat->id;
		}
		print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('suppcats', $cate_arbo, $arrayselected, 0, 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
		print "</td></tr>";
	}
}

// Other attributes
$parameters = array('socid'=>$object->id, 'colspan' => ' colspan="3"', 'colspanvalue' => '3');
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

// Webservices url/key
if (isModEnabled('webservicesclient')) {
	print '<tr><td>'.$form->editfieldkey('WebServiceURL', 'webservices_url', '', $object, 0).'</td>';
	print '<td><input type="text" name="webservices_url" id="webservices_url" value="'.$object->webservices_url.'"></td>';
	print '<td>'.$form->editfieldkey('WebServiceKey', 'webservices_key', '', $object, 0).'</td>';
	print '<td><input type="text" name="webservices_key" id="webservices_key" value="'.$object->webservices_key.'"></td></tr>';
}

// Logo
print '<tr class="hideonsmartphone">';
print '<td>'.fieldLabel('Logo', 'photoinput').'</td>';
print '<td colspan="3">';
if ($object->logo) print $form->showphoto('societe', $object);
$caneditfield=1;
if ($caneditfield) {
	if ($object->logo) print "<br>\n";
	print '<table class="nobordernopadding">';
	if ($object->logo) print '<tr><td><input type="checkbox" class="flat" name="deletephoto" id="photodelete"> '.$langs->trans("Delete").'<br><br></td></tr>';
	//print '<tr><td>'.$langs->trans("PhotoFile").'</td></tr>';
	print '<tr><td><input type="file" class="flat" name="photo" id="photoinput"></td></tr>';
	print '</table>';
}
print '</td>';
print '</tr>';

// Assign sale representative
print '<tr>';
print '<td>'.$form->editfieldkey('AllocateCommercial', 'commercial_id', '', $object, 0).'</td>';
print '<td colspan="3" class="maxwidthonsmartphone">';
$userlist = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
$arrayselected = GETPOST('commercial', 'array');
if (empty($arrayselected)) {
	$arrayselected = $object->getSalesRepresentatives($user, 1);
}
print img_picto('', 'user', 'class="pictofixedwidth"').$form->multiselectarray('commercial', $userlist, $arrayselected, 0, 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0, '', '', '', 1);
print '</td></tr>';

print '</table>';

dol_fiche_end();

print $form->buttonsSaveCancel();

print '</form>';
?>

<!-- END PHP TEMPLATE -->
