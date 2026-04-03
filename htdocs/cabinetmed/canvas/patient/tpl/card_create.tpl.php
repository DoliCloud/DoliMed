<?php
/* Copyright (C) 2011-2013 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * @var Conf $conf
 * @var DoliDB $db
 * @var CommonObject $object
 *
 * @var string $action
 * @var ?string $backtopage
 */
// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}


$object=$GLOBALS['object'];

global $db,$conf,$mysoc,$langs,$user,$hookmanager,$extrafields,$object,$permissiontoadd;


$socialnetworks = getArrayOfSocialNetworks();


// Load object modCodeTiers
$module = getDolGlobalString('SOCIETE_CODECLIENT_ADDON', 'mod_codeclient_leopard');
if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php') {
	$module = substr($module, 0, dol_strlen($module) - 4);
}
$dirsociete=array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
foreach ($dirsociete as $dirroot) {
	$res = dol_include_once($dirroot.$module.'.php');
	if ($res) {
		break;
	}
}
require_once DOL_DOCUMENT_ROOT ."/core/class/html.formcompany.class.php";
require_once DOL_DOCUMENT_ROOT ."/core/class/html.formadmin.class.php";
$modCodeClient = new $module($GLOBALS['db']);
'@phan-var-force ModeleThirdPartyCode $modCodeClient';

// Load object modCodeFournisseur
/*
$module = getDolGlobalString('SOCIETE_CODECLIENT_ADDON', 'mod_codeclient_leopard');
if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php') {
	$module = substr($module, 0, dol_strlen($module) - 4);
}
$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
foreach ($dirsociete as $dirroot) {
	$res = dol_include_once($dirroot.$module.'.php');
	if ($res) {
		break;
	}
}
$modCodeFournisseur = new $module($db);
'@phan-var-force ModeleThirdPartyCode $modCodeFournisseur';
*/

$form=new Form($GLOBALS['db']);
$formcompany=new FormCompany($GLOBALS['db']);
$formadmin=new FormAdmin($GLOBALS['db']);


// Prospect/Customer/Supplier
$selected = $object->client;
$selectedcustomer = ((getDolGlobalInt('THIRDPARTY_CUSTOMERTYPE_BY_DEFAULT')==1 && GETPOST("type", 'aZ') != 'p' && GETPOST("type", 'aZ') != 'f') || (getDolGlobalInt('THIRDPARTY_CUSTOMERTYPE_BY_DEFAULT')==3 && GETPOST("type", 'aZ') != 'p' && GETPOST("type", 'aZ') != 'f') ? 1 : 0);
$selectedprospect = ((getDolGlobalInt('THIRDPARTY_CUSTOMERTYPE_BY_DEFAULT')==2 && GETPOST("type", 'aZ') != 'c' && GETPOST("type", 'aZ') != 'f') || (getDolGlobalInt('THIRDPARTY_CUSTOMERTYPE_BY_DEFAULT')==3 && GETPOST("type", 'aZ') != 'c' && GETPOST("type", 'aZ') != 'f') ? 1 : 0);
switch ($selected) {
	case 1:
		$selectedcustomer = 1;
		break;
	case 2:
		$selectedprospect = 1;
		break;
	case 3:
		$selectedprospect = 1;
		$selectedcustomer = 1;
		break;
	default:
		break;
}

$selectedprospect = ((GETPOSTISSET('prospect') && $action == 'create') ? GETPOSTINT('prospect') : $selectedprospect);
$selectedcustomer = ((GETPOSTISSET('customer') && $action == 'create') ? GETPOSTINT('customer') : $selectedcustomer);
$selectedsupplier = ((GETPOSTISSET('supplier') && $action == 'create') ? GETPOSTINT('supplier') : $object->fournisseur);

$object->name = GETPOST("name");
$object->lastname = GETPOST("name");
$object->firstname = GETPOST("firstname");
$object->particulier = 0;
$object->prefix_comm = GETPOST("prefix_comm");
$object->client = GETPOSTISSET("client")?GETPOST("client"):$object->client;
$object->code_client = GETPOST("customer_code");
$object->fournisseur = GETPOSTISSET("fournisseur") ? GETPOST("fournisseur") : $object->fournisseur;
$object->code_fournisseur = GETPOSTISSET("code_fournisseur") ? GETPOST("code_fournisseur") : (GETPOSTISSET('supplier_code') ? GETPOST("supplier_code") : -1);

$object->address = GETPOST('address', 'alphanohtml');
$object->zip = GETPOST('zipcode', 'alphanohtml');
$object->town = GETPOST('town', 'alphanohtml');
$object->state_id = GETPOSTINT('state_id');

$object->socialnetworks = array();
if (isModEnabled('socialnetworks')) {
	foreach ($socialnetworks as $key => $value) {
		if (GETPOSTISSET($key) && GETPOST($key, 'alphanohtml') != '') {
			$object->socialnetworks[$key] = GETPOST($key, 'alphanohtml');
		}
	}
}

$object->phone				= GETPOST('phone', 'alpha');
$object->phone_mobile       = (string) GETPOST("phone_mobile", 'alpha');
$object->fax				= GETPOST('fax', 'alpha');
$object->email				= GETPOST('email', 'email');
if ((float) DOL_VERSION >= 23) {
	$object->url				= GETPOST('url', 'url');
} else {
	$object->url				= GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
}
$object->capital			= GETPOSTFLOAT('capital');
$paymentTermId = GETPOSTINT('cond_reglement_id'); // can be set by default values on create page and not already in get or post variables
if (empty($paymentTermId) && !GETPOSTISSET('cond_reglement_id')) {
	$paymentTermId = getDolGlobalString('MAIN_DEFAULT_PAYMENT_TERM_ID');
}
$object->cond_reglement_id	= $paymentTermId;
$paymentTypeId = GETPOSTINT('mode_reglement_id'); // can be set by default values on create page and not already in get or post variables
if (empty($paymentTypeId) && !GETPOSTISSET('mode_reglement_id')) {
	$paymentTypeId = getDolGlobalString('MAIN_DEFAULT_PAYMENT_TYPE_ID');
}
$object->mode_reglement_id 	= $paymentTypeId;
$object->barcode			= GETPOST('barcode', 'alphanohtml');
$object->idprof1			= GETPOST('idprof1', 'alphanohtml');
$object->idprof2			= GETPOST('idprof2', 'alphanohtml');
$object->idprof3			= GETPOST('idprof3', 'alphanohtml');
$object->idprof4			= GETPOST('idprof4', 'alphanohtml');
$object->idprof5			= GETPOST('idprof5', 'alphanohtml');
$object->idprof6			= GETPOST('idprof6', 'alphanohtml');
$object->typent_id = GETPOSTINT('typent_id');
$object->effectif_id		= GETPOSTINT('effectif_id');
$object->civility_id		= GETPOST('civility_id', 'alpha');

$object->tva_assuj = GETPOSTINT('assujtva_value');
$object->vat_reverse_charge	= GETPOST('vat_reverse_charge') == 'on' ? 1 : 0;
$object->status = GETPOSTINT('status');

//Local Taxes
$object->localtax1_assuj	= GETPOSTINT('localtax1assuj_value');
$object->localtax2_assuj	= GETPOSTINT('localtax2assuj_value');

$object->localtax1_value	= GETPOST('lt1', 'alpha');
$object->localtax2_value	= GETPOST('lt2', 'alpha');

$object->tva_intra = GETPOST('tva_intra', 'alphanohtml');

$object->commercial_id = GETPOSTINT('commercial_id');
$object->default_lang = GETPOST('default_lang');

if (GETPOSTISSET('accountancy_code_sell')) {
	$accountancy_code_sell  = GETPOST('accountancy_code_sell', 'alpha');

	if (empty($accountancy_code_sell) || $accountancy_code_sell == '-1') {
		$object->accountancy_code_sell = '';
	} else {
		$object->accountancy_code_sell = $accountancy_code_sell;
	}
}
if (GETPOSTISSET('accountancy_code_buy')) {
	$accountancy_code_buy   = GETPOST('accountancy_code_buy', 'alpha');

	if (empty($accountancy_code_buy) || $accountancy_code_buy == '-1') {
		$object->accountancy_code_buy = '';
	} else {
		$object->accountancy_code_buy = $accountancy_code_buy;
	}
}

$object->logo = (isset($_FILES['photo']) ? dol_sanitizeFileName($_FILES['photo']['name']) : '');

// Company logo management
$dir     = $conf->societe->multidir_output[$conf->entity]."/".$object->id."/logos";
$file_OK = (isset($_FILES['photo']) ? is_uploaded_file($_FILES['photo']['tmp_name']) : false);
if ($file_OK) {
	if (image_format_supported($_FILES['photo']['name'])) {
		dol_mkdir($dir);

		if (@is_dir($dir)) {
			$newfile = $dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
			$result = dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1);

			if (!($result > 0)) {
				$errors[] = "ErrorFailedToSaveFile";
			} else {
				// Create thumbs
				$object->addThumbs($newfile);
			}
		}
	}
}

// We set country_id, country_code and country for the selected country
$object->country_id = GETPOST('country_id') ? GETPOST('country_id') : $mysoc->country_id;
if ($object->country_id) {
	$tmparray = getCountry($object->country_id, 'all');
	$object->country_code = $tmparray['code'];
	$object->country = $tmparray['label'];
}
$object->forme_juridique_code = GETPOSTINT('forme_juridique_code');

// We set multicurrency_code if enabled
if (isModEnabled("multicurrency")) {
	$object->multicurrency_code = GETPOST('multicurrency_code') ? GETPOST('multicurrency_code') : $conf->currency;
}
?>

<!-- BEGIN PHP TEMPLATE -->
<?php
print_fiche_titre($langs->trans("NewPatient"), '', 'user-injured');

dol_htmloutput_errors($GLOBALS['error'], $GLOBALS['errors']);

?>

<script type="text/javascript">$(document).ready(function () {
	$("#selectcountry_id").change(function() {
		console.log("selectcountry_id change");
		document.formsoc.action.value="create";
		document.formsoc.submit();
	});
})
</script>

<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST" name="formsoc" enctype="multipart/form-data">

<input type="hidden" name="canvas" value="<?php echo $GLOBALS['canvas'] ?>">
<input type="hidden" name="action" value="add">
<input type="hidden" name="token" value="<?php echo newToken(); ?>">
<input type="hidden" name="backtopage" value="<?php echo empty($backtopage) ? '' : $backtopage; ?>">
<input type="hidden" name="private" value="0">
<input type="hidden" name="status" value="1">
<input type="hidden" name="client" value="<?php echo $object->client; ?>">
<?php
if ($modCodeClient->code_auto) {
	print '<input type="hidden" name="code_auto" value="1">';
}

print dol_get_fiche_head(array(), 'card', '', 0, '');

print '<table class="border centpercent">';

print '<tr class="tr-field-thirdparty-name">';
print '<td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans('PatientName').'</span></td>';
print '<td><input type="text" class="minwidth300" maxlength="128" name="name" id="name" value="'.dol_escape_htmltag($object->name).'" autofocus="autofocus">';
print $form->widgetForTranslation("name", $object, $permissiontoadd, 'string', 'alphanohtml', 'minwidth300');	// For some countries that need the company name in 2 languages
print '</td><td colspan="2"></td>';
print '</tr>';

// Customer code
print '<tr>';
print '<td>'.$form->editfieldkey('CustomerCode', 'customer_code', '', $object, 0).'</td><td>';
print '<table class="nobordernopadding"><tr><td>';
$tmpcode = $object->code_client ?? '';
if (empty($tmpcode) && !empty($modCodeClient->code_auto)) {
	$tmpcode = $modCodeClient->getNextValue($object, 0);
}
print '<input type="text" name="customer_code" id="customer_code" class="maxwidthonsmartphone" value="'.dol_escape_htmltag($tmpcode).'" maxlength="24">';
print '</td><td>';
$s = $modCodeClient->getToolTip($langs, $object, 0);
print $form->textwithpicto('', $s, 1);
print '</td></tr></table>';
print '</td>';

/*
if ((isModEnabled("fournisseur") && $user->hasRight('fournisseur', 'lire') && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) || (isModEnabled("supplier_order") && $user->hasRight('supplier_order', 'lire')) || (isModEnabled("supplier_invoice") && $user->hasRight('supplier_invoice', 'lire'))
	|| (isModEnabled('supplier_proposal') && $user->hasRight('supplier_proposal', 'lire'))) {
	if ($conf->browser->layout == 'phone') {
		print '<td colspan="2"></td>';
		print '</tr><tr>';
	}
	print '<td>'.$form->editfieldkey('SupplierCode', 'supplier_code', '', $object, 0).'</td><td>';
	print '<table class="nobordernopadding"><tr><td>';
	$tmpcode = $object->code_fournisseur ?? '';
	if (empty($tmpcode) && !empty($modCodeFournisseur->code_auto)) {
		$tmpcode = $modCodeFournisseur->getNextValue($object, 1);
	}
	print '<input type="text" name="supplier_code" id="supplier_code" class="maxwidthonsmartphone" value="'.dol_escape_htmltag($tmpcode).'" maxlength="24">';
	print '</td><td>';
	$s = $modCodeFournisseur->getToolTip($langs, $object, 1);
	print $form->textwithpicto('', $s, 1);
	print '</td></tr></table>';
	print '</td>';
} else {
	print '<td colspan="2"></td>';
}
*/
print '<td colspan="2"></td>';
print '</tr>';

print '<tr class="marginbottomlarge height50">';
if ($conf->browser->layout != 'phone') {
	print '<td class="titlefieldcreate">'.$form->editfieldkey('', 'customerprospect', '', $object, 0, 'string', '', 0).'</td>';
}
print '<td class="maxwidthonsmartphone"'.($conf->browser->layout != 'phone' ? 'colspan="3"' : 'colspan="2"').'>';

if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS')) {
	print '<span id="spannature1" class="spannature prospect-back paddinglarge marginrightonly"><label for="prospectinput" class="valignmiddle">'.$langs->trans("Prospect").'</label><input id="prospectinput" class="flat checkforselect marginleftonly valignmiddle" type="checkbox" name="prospect" value="2"'.($selectedprospect ? ' checked="checked"' : '').'></span>';
}

if (!getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS')) {
	print '<span id="spannature2" class="spannature customer-back paddinglarge marginrightonly"><label for="customerinput" class="valignmiddle">'.$langs->trans("Customer").'</label><input id="customerinput" class="flat checkforselect marginleftonly valignmiddle" type="checkbox" name="customer" value="1"'.($selectedcustomer ? ' checked="checked"' : '').'></span>';
}

if ((isModEnabled("fournisseur") && $user->hasRight('fournisseur', 'lire') && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) || (isModEnabled("supplier_order") && $user->hasRight('supplier_order', 'lire')) || (isModEnabled("supplier_invoice") && $user->hasRight('supplier_invoice', 'lire'))
	|| (isModEnabled('supplier_proposal') && $user->hasRight('supplier_proposal', 'lire'))) {
	// Supplier
	print '<span id="spannature3" class="spannature vendor-back paddinglarge marginrightonly"><label for="supplierinput" class="valignmiddle">'.$langs->trans("Vendor").'</label><input id="supplierinput" class="flat checkforselect marginleftonly valignmiddle" type="checkbox" name="supplier" value="1"'.($selectedsupplier ? ' checked="checked"' : '').'></span>';
}
// Add js to manage the background of nature
if ($conf->use_javascript_ajax) {
	print '<script>
	function refreshNatureCss() {
		jQuery(".spannature").each(function( index ) {
			id = $(this).attr("id").split("spannature")[1];
			console.log(jQuery("#spannature"+(id)+" .checkforselect").is(":checked"));
			if (jQuery("#spannature"+(id)+" .checkforselect").is(":checked")) {
				if (id == 1) {
					jQuery("#spannature"+(id)).addClass("prospect-back").removeClass("nonature-back");
				}
				if (id == 2) {
					jQuery("#spannature"+(id)).addClass("customer-back").removeClass("nonature-back");
				}
				if (id == 3) {
					jQuery("#spannature"+(id)).addClass("vendor-back").removeClass("nonature-back");
				}
			} else {
				jQuery("#spannature"+(id)).removeClass("prospect-back").removeClass("customer-back").removeClass("vendor-back").addClass("nonature-back");
			}
		});
	}

	function manageprospectcustomer(element) {
		console.log("We uncheck unwanted values on a nature");
		id = $(element).attr("id").split("spannature")[1];
		if ( id == 1){
			$("#spannature2 .checkforselect").prop("checked", false);
		}
		if ( id == 2){
			$("#spannature1 .checkforselect").prop("checked", false);
		}
	}

	jQuery(".spannature").click(function(){
		console.log("We click on a nature");
		'.(getDolGlobalString('SOCIETE_DISABLE_PROSPECTSCUSTOMERS') ? 'manageprospectcustomer($(this));' : '').'
		refreshNatureCss();
	});
	refreshNatureCss();
	</script>';
}
print '</td>';
print '</tr>';

// Barcode
if (isModEnabled('barcode')) {
	print '<tr><td>'.$form->editfieldkey('Gencod', 'barcode', '', $object, 0).'</td>';
	print '<td colspan="3">';
	print img_picto('', 'barcode', 'class="pictofixedwidth"');
	print '<input type="text" class="minwidth200 maxwidth300 widthcentpercentminusx" name="barcode" id="barcode" value="'.dol_escape_htmltag($object->barcode).'">';
	print '</td></tr>';
}

print '<tr><td colspan="4">&nbsp;</td></tr>';

// Address
print '<tr><td class="tdtop">';
print $form->editfieldkey('Address', 'address', '', $object, 0);
print '</td>';
print '<td colspan="3">';
print '<textarea name="address" id="address" class="quatrevingtpercent" rows="'.ROWS_2.'" wrap="soft">';
print dol_escape_htmltag($object->address, 0, 1);
print '</textarea>';
print $form->widgetForTranslation("address", $object, $permissiontoadd, 'textarea', 'alphanohtml', 'quatrevingtpercent');
print '</td></tr>';

// Zip / Town
print '<tr><td>'.$langs->trans('Zip').'</td><td>';
print $formcompany->select_ziptown($object->zip, 'zipcode', array('town','selectcountry_id','departement_id'), 0, 0, '', 'maxwidth100');
print '</td>';
if ($conf->browser->layout == 'phone') {
	print '</tr><tr>';
}
print '<td>'.$langs->trans('Town').'</td><td>';
print $formcompany->select_ziptown($object->town, 'town', array('zipcode','selectcountry_id','departement_id'), 0, 0, '', 'maxwidth150 quatrevingtpercent');
print '</td></tr>';

// Country
print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3" class="maxwidthonsmartphone">';
print img_picto('', 'country', 'class="pictofixedwidth"');
print $form->select_country((GETPOSTISSET('country_id') ? GETPOST('country_id') : $object->country_id), 'country_id', '', 0, 'minwidth200 maxwidth300 widthcentpercentminusx');
if ($user->admin) {
	print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
}
print '</td></tr>';

// State
if (!getDolGlobalString('SOCIETE_DISABLE_STATE')) {
	print '<tr><td>'.$langs->trans('State').'</td><td colspan="3" class="maxwidthonsmartphone">';

	if ($object->country_id) {
		print img_picto('', 'state', 'class="pictofixedwidth"');
		print $formcompany->select_state($object->state_id, $object->country_code, 'state_id', 'minwidth200 maxwidth300 widthcentpercentminusx');
	} else {
		print $countrynotdefined;
	}
	print '</td></tr>';
}

// Phone / Fax
print '<tr><td>'.$form->editfieldkey('Phone', 'phone', '', $object, 0).'</td>';
print '<td'.($conf->browser->layout == 'phone' ? ' colspan="3"' : '').'>'.img_picto('', 'object_phoning', 'class="pictofixedwidth"').' <input type="text" name="phone" id="phone" class="maxwidth200 widthcentpercentminusx" value="'.(GETPOSTISSET('phone') ? GETPOST('phone', 'alpha') : $object->phone).'"></td>';

if ($conf->browser->layout == 'phone') {
	print '</tr><tr>';
}

// Phone mobile
print '<td>'.$form->editfieldkey('PhoneMobile', 'phone_mobile', '', $object, 0).'</td>';
print '<td'.($conf->browser->layout == 'phone' ? ' colspan="3"' : '').'>'.img_picto('', 'object_phoning_mobile', 'class="pictofixedwidth"').' <input type="text" name="phone_mobile" id="phone_mobile" class="maxwidth200 widthcentpercentminusx" value="'.(GETPOSTISSET('phone_mobile') ? GETPOST('phone_mobile', 'alpha') : $object->phone_mobile).'"></td></tr>';

// Email
print '<tr><td>'.$form->editfieldkey('EMail', 'email', '', $object, 0, 'string', '', getDolGlobalInt('SOCIETE_EMAIL_MANDATORY')).'</td>';
print '<td'.(($conf->browser->layout == 'phone') || !isModEnabled('mailing') ? ' colspan="3"' : '').'>'.img_picto('', 'object_email', 'class="pictofixedwidth"').' <input type="text" class="maxwidth200 widthcentpercentminusx" name="email" id="email" value="'.$object->email.'"></td>';

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
*/

// Social networks
if (isModEnabled('socialnetworks')) {
	$colspan = ($conf->browser->layout == 'phone' ? 2 : 4);

	$object->showSocialNetwork($socialnetworks, $colspan);

	print '<tr><td'.($colspan ? ' colspan="'.$colspan.'"' : '').'><hr></td></tr>';
}

// Prof ids
$i=1;
$j=0;
$NBCOLS = ($conf->browser->layout == 'phone' ? 1 : 2);
$NBPROFIDMIN = getDolGlobalInt('THIRDPARTY_MIN_NB_PROF_ID', 2);
$NBPROFIDMAX = getDolGlobalInt('THIRDPARTY_MAX_NB_PROF_ID', 6);
while ($i <= $NBPROFIDMAX) {
	$key='CABINETMED_SHOW_PROFID'.$i;
	if (empty($conf->global->$key)) { $i++; continue; }

	$idprof=$langs->transcountry('ProfId'.$i, $object->country_code);
	if ($idprof!='-') {
		$key='idprof'.$i;

		if (($j % 2) == 0) print '<tr>';

		$idprof_mandatory ='SOCIETE_IDPROF'.($i).'_MANDATORY';
		if (empty($conf->global->$idprof_mandatory))
			print '<td><label for="'.$key.'">'.$idprof.'</label></td><td>';
		else print '<td><span class="fieldrequired"><label for="'.$key.'">'.$idprof.'</label></td><td>';

		print $formcompany->get_input_id_prof($i, $key, $object->$key, $object->country_code);
		print '</td>';
		if (($j % 2) == 1) print '</tr>';
		$j++;
	}
	$i++;
}
		if ($j % 2 == 1) print '<td colspan="2"></td></tr>';

		// Birthday
/*
		$idprof=$langs->trans(((float) DOL_VERSION < 13) ? 'DateToBirth' : 'DateOfBirth'));
		print '<td>'.$idprof.'</td><td colspan="3">';

		print '<input type="text" name="idprof3" size="18" maxlength="32" value="'.$object->idprof3.'"> ('.$conf->format_date_short_java.')';
		//$conf->global->MAIN_POPUP_CALENDAR='none';
		//print $form->selectDate(-1,'birthdate');
		print '</td>';
		print '</tr>';
*/
		print '<tr>';
		print '<td class="nowrap">'.$langs->trans('PatientVATIntra').'</td>';
		print '<td class="nowrap" colspan="3">';
		print '<input type="text" class="flat" name="tva_intra" size="18" maxlength="32" value="'.$object->tva_intra.'">';
		print '</td></tr>';

		// Genre
		print '<tr><td>'.$langs->trans("Gender").'</td><td colspan="3">'."\n";
		if ((float) DOL_VERSION <= 23) {
			print $form->selectarray("typent_id", $formcompany->typent_array(0, "AND code in ('TE_UNKNOWN', 'TE_HOMME', 'TE_FEMME')"), $object->typent_id);
		} else {
			print $form->selectarray("typent_id", $formcompany->typent_array(0, "(code:in:'TE_UNKNOWN','TE_HOMME','TE_FEMME')"), $object->typent_id);
		}
		if ($user->admin) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '</td></tr>';

		// Legal Form
		print '<tr><td>'.$langs->trans('ActivityBranch').'</td>';
		print '<td colspan="3">';
		if ($GLOBALS['mysoc']->country_id) {
			print $formcompany->select_juridicalstatus($object->forme_juridique_code, $GLOBALS['mysoc']->country_code, "AND (f.module = 'cabinetmed' OR f.code > '100000')");	// > 100000 is the only way i found to not see other entries
		} else {
			print $GLOBALS['countrynotdefined'];
		}
		print '</td>';
		print '</tr>';

		if (getDolGlobalInt('MAIN_MULTILANGS')) {
			print '<tr><td>'.$langs->trans("DefaultLang").'</td><td colspan="3">'."\n";
			print $formadmin->select_language(($object->default_lang ? $object->default_lang : getDolGlobalString('MAIN_LANG_DEFAULT')), 'default_lang', 0, 0, 1);
			print '</td>';
			print '</tr>';
		}

		// Categories
		if (isModEnabled('categorie') && $user->hasRight('categorie', 'lire')) {
			$langs->load('categories');

			// Customer
			if ($object->prospect || $object->client) {
				print '<tr><td class="toptd">' . fieldLabel('CustomersCategoriesShort', 'custcats') . '</td><td colspan="3">';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_CUSTOMER, null, 'parent', null, null, 1);
				print $form->multiselectarray('custcats', $cate_arbo, GETPOST('custcats', 'array'), null, null, null,
					null, "90%");
				print "</td></tr>";
			}

			// Supplier
			if ($object->fournisseur) {
				print '<tr><td class="toptd">' . fieldLabel('SuppliersCategoriesShort', 'suppcats') . '</td><td colspan="3">';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_SUPPLIER, null, 'parent', null, null, 1);
				print $form->multiselectarray('suppcats', $cate_arbo, GETPOST('suppcats', 'array'), null, null, null,
					null, "90%");
				print "</td></tr>";
			}
		}

		// Other attributes
		$parameters = array('socid'=>(empty($socid) ? 0 : $socid), 'colspan' => ' colspan="3"', 'colspanvalue' => '3');
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

		// Assign a sale representative
		print '<tr>';
		print '<td>'.$form->editfieldkey('AllocateCommercial', 'commercial_id', '', $object, 0).'</td>';
		print '<td colspan="3" class="maxwidthonsmartphone">';
		$userlist = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, '', 0, '', '', 0, 1);
		// Note: If user has no right to "see all thirdparties", we force selection of sale representative to him, so after creation he can see the record.
		$selected = (count(GETPOST('commercial', 'array')) > 0 ? GETPOST('commercial', 'array') : (GETPOST('commercial', 'int') > 0 ? array(GETPOST('commercial', 'int')) : (empty($user->rights->societe->client->voir)?array($user->id):array())));
		if (empty($selected)) $selected=array($user->id);
		print $form->multiselectarray('commercial', $userlist, $selected, null, null, null, null, "90%");
		print '</td></tr>';

		// Ajout du logo
		print '<tr class="hideonsmartphone">';
		print '<td>'.fieldLabel('Logo', 'photoinput').'</td>';
		print '<td colspan="3">';
		print '<input class="flat" type="file" name="photo" id="photoinput" />';
		print '</td>';
		print '</tr>';
?>
</table>

<?php

dol_fiche_end();

?>

<div align="center">
	<input type="submit" class="button" value="<?php echo $langs->trans('AddPatient'); ?>">
</div>

</form>

<!-- END PHP TEMPLATE -->
