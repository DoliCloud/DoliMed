<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/cabinetmed/patients.php
 *	\ingroup    cabinetmed
 *	\brief      Page to show list of patients
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

require_once DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once "./lib/cabinetmed.lib.php";

$langs->loadLangs(array("companies", "commercial", "customers", "suppliers", "bills", "compta", "categories", "cashdesk", "other", "cabinetmed@cabinetmed"));

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'patientlist';
$optioncss=GETPOST('optioncss', 'alpha');
$mode=GETPOST("mode", 'alpha');

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'societe', $socid, '');

$search_all=trim(GETPOSTISSET('search_all')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$sall=$search_all;
$search_cti=preg_replace('/^0+/', '', preg_replace('/[^0-9]/', '', GETPOST('search_cti', 'alphanohtml')));	// Phone number without any special chars

$search_id=trim(GETPOST("search_id", "int"));
$search_nom = trim(GETPOST("search_nom", 'restricthtml'));
$search_alias = trim(GETPOST("search_alias", 'restricthtml'));

$search_code=GETPOST("search_code", "alpha");

$search_nom_only = trim(GETPOST("search_nom_only", 'restricthtml'));
$search_barcode = trim(GETPOST("search_barcode", 'alpha'));
$search_customer_code = trim(GETPOST('search_customer_code', 'alpha'));
$search_supplier_code = trim(GETPOST('search_supplier_code', 'alpha'));
$search_account_customer_code = trim(GETPOST('search_account_customer_code', 'alpha'));
$search_account_supplier_code = trim(GETPOST('search_account_supplier_code', 'alpha'));
$search_address = trim(GETPOST('search_address', 'alpha'));
$search_zip = trim(GETPOST("search_zip", 'alpha'));
$search_town = trim(GETPOST("search_town", 'alpha'));
$search_state = trim(GETPOST("search_state", 'alpha'));
$search_region = trim(GETPOST("search_region", 'alpha'));
$search_email = trim(GETPOST('search_email', 'alpha'));
$search_phone = trim(GETPOST('search_phone', 'alpha'));
$search_fax = trim(GETPOST('search_fax', 'alpha'));
$search_url = trim(GETPOST('search_url', 'alpha'));
$search_idprof1 = trim(GETPOST('search_idprof1', 'alpha'));
$search_idprof2 = trim(GETPOST('search_idprof2', 'alpha'));
$search_idprof3 = trim(GETPOST('search_idprof3', 'alpha'));
$search_idprof4 = trim(GETPOST('search_idprof4', 'alpha'));
$search_idprof5 = trim(GETPOST('search_idprof5', 'alpha'));
$search_idprof6 = trim(GETPOST('search_idprof6', 'alpha'));
$search_vat = trim(GETPOST('search_vat', 'alpha'));
$search_sale = GETPOST("search_sale", 'int');
$search_categ_cus = GETPOST("search_categ_cus", 'int');
$search_categ_sup = GETPOST("search_categ_sup", 'int');
$search_country = GETPOST("search_country", 'intcomma');
$search_type_thirdparty = GETPOST("search_type_thirdparty", 'int');
$search_status = GETPOST("search_status", 'int');
$search_type = GETPOST('search_type', 'alpha');
$search_stcomm = GETPOST('search_stcomm', 'int');
$search_import_key  = trim(GETPOST("search_import_key", "alpha"));
$search_parent_name = trim(GETPOST('search_parent_name', 'alpha'));

// Load sale and categ filters
$search_sale = GETPOST("search_sale", "int");
$search_categ = GETPOST("search_categ", "int");
$search_diagles=GETPOST("search_diagles", "int");
$search_contactid = GETPOST("search_contactid", "int");

$type=GETPOST('type', 'alpha');


$diroutputmassaction=$conf->societe->dir_output . '/temp/massgeneration/'.$user->id;

// Load variable for pagination
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "s.nom";
}
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action

$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object = new Societe($db);
$hookmanager->initHooks(array('thirdpartylist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	's.nom'=>"ThirdPartyName",
	's.name_alias'=>"AliasNameShort",
	's.code_client'=>"CustomerCode",
	's.code_compta'=>"CustomerAccountancyCodeShort",
	's.zip'=>"Zip",
	's.town'=>"Town",
	's.email'=>"EMail",
	's.url'=>"URL",
	's.tva_intra'=>"PatientVATIntra",
	's.siren'=>"ProfId1",
	's.siret'=>"ProfId2",
	's.ape'=>"ProfId3",
	's.phone'=>"Phone",
	's.fax'=>"Fax",
);
if (($tmp = $langs->transnoentities("ProfId4".$mysoc->country_code)) && $tmp != "ProfId4".$mysoc->country_code && $tmp != '-') {
	$fieldstosearchall['s.idprof4'] = 'ProfId4';
}
if (($tmp = $langs->transnoentities("ProfId5".$mysoc->country_code)) && $tmp != "ProfId5".$mysoc->country_code && $tmp != '-') {
	$fieldstosearchall['s.idprof5'] = 'ProfId5';
}
if (($tmp = $langs->transnoentities("ProfId6".$mysoc->country_code)) && $tmp != "ProfId6".$mysoc->country_code && $tmp != '-') {
	$fieldstosearchall['s.idprof6'] = 'ProfId6';
}
if (isModEnabled('barcode')) {
	$fieldstosearchall['s.barcode'] = 'Gencod';
}
// Personalized search criterias. Example: $conf->global->THIRDPARTY_QUICKSEARCH_ON_FIELDS = 's.nom=ThirdPartyName;s.name_alias=AliasNameShort;s.code_client=CustomerCode'
if (!empty($conf->global->THIRDPARTY_QUICKSEARCH_ON_FIELDS)) {
	$fieldstosearchall = dolExplodeIntoArray($conf->global->THIRDPARTY_QUICKSEARCH_ON_FIELDS);
}


$arrayfields=array(
's.rowid'=>array('label'=>"TechnicalID", 'checked'=>(getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID')?1:0), 'enabled'=>(getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID')?1:0)),
's.nom'=>array('label'=>"Patient", 'checked'=>1),
's.name_alias'=>array('label'=>"AliasNameShort", 'checked'=>0),
's.barcode'=>array('label'=>"Gencod", 'checked'=>0, 'enabled'=>isModEnabled("barcode")),
's.code_client'=>array('label'=>"PatientCode", 'checked'=>1),
's.code_fournisseur'=>array('label'=>"SupplierCodeShort", 'checked'=>0, 'enabled'=>isModEnabled("fournisseur")),
's.code_compta'=>array('label'=>"CustomerAccountancyCodeShort", 'checked'=>0),
's.code_compta_fournisseur'=>array('label'=>"SupplierAccountancyCodeShort", 'checked'=>0, 'enabled'=>isModEnabled("fournisseur")),
's.address'=>array('label'=>"Address", 'position'=>19, 'checked'=>0),
's.zip'=>array('label'=>"Zip", 'position'=>20, 'checked'=>1),
's.town'=>array('label'=>"Town", 'position'=>22, 'checked'=>1),
'state.nom'=>array('label'=>"State", 'position'=>25, 'checked'=>0),
'region.nom'=>array('label'=>"Region", 'position'=>26, 'checked'=>0),
'country.code_iso'=>array('label'=>"Country", 'position'=>27, 'checked'=>0),
's.email'=>array('label'=>"Email", 'checked'=>0),
's.url'=>array('label'=>"Url", 'checked'=>0),
's.phone'=>array('label'=>"Phone", 'checked'=>1),
's.fax'=>array('label'=>"Fax", 'checked'=>0),
'typent.code'=>array('label'=>"ThirdPartyType", 'checked'=>0),
's.siren'=>array('label'=>"ProfId1Short", 'position'=>40, 'checked'=>0),
's.siret'=>array('label'=>"ProfId2Short", 'position'=>41, 'checked'=>0),
's.ape'=>array('label'=>"ProfId3Short", 'position'=>42, 'checked'=>0),
's.idprof4'=>array('label'=>"ProfId4Short", 'position'=>43, 'checked'=>0),
's.idprof5'=>array('label'=>"ProfId5Short", 'position'=>44, 'checked'=>0),
's.idprof6'=>array('label'=>"ProfId6Short", 'position'=>45, 'checked'=>0),
's.tva_intra'=>array('label'=>"VATIntra", 'position'=>50, 'checked'=>0),
'customerorsupplier'=>array('label'=>'Nature', 'checked'=>0),
'nb'=>array('label'=>'NbConsult', 'position'=> 100, 'checked'=>1),
'lastcons'=>array('label'=>'LastConsultShort', 'position'=> 101, 'checked'=>1),
's.datec'=>array('label'=>"DateCreation", 'checked'=>0, 'position'=>500),
's.tms'=>array('label'=>"DateModificationShort", 'checked'=>0, 'position'=>500),
's.status'=>array('label'=>"Status", 'checked'=>1, 'position'=>1000),
's.import_key'=>array('label'=>"ImportId", 'checked'=>0, 'position'=>1100),
);

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Did we click on purge search criteria ?
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_id='';
		$search_nom='';
		$search_alias = '';
		$search_categ_cus = 0;
		$search_categ_sup = 0;
		$search_sale = '';
		$search_barcode = "";
		$search_customer_code = '';
		$search_supplier_code = '';
		$search_account_customer_code = '';
		$search_account_supplier_code = '';
		$search_address = '';
		$search_zip = "";
		$search_town = "";
		$search_state = "";
		$search_region = "";
		$search_country = '';
		$search_email = '';
		$search_phone = '';
		$search_fax = '';
		$search_url = '';
		$search_idprof1='';
		$search_idprof2='';
		$search_idprof3='';
		$search_idprof4='';
		$search_idprof5 = '';
		$search_idprof6 = '';
		$search_vat = '';
		$search_type = '';
		$search_price_level = '';
		$search_type_thirdparty = '';
		$search_staff = '';
		$search_contactid='';
		$search_status=-1;
		$search_birthday='';
		$search_birthmonth='';
		$search_birthyear='';
		$search_import_key = '';
		$toselect = array();
		$search_array_options=array();
	}

	// Mass actions
	$objectclass='Societe';
	$objectlabel='ThirdParty';
	$permissiontoread = $user->hasRight('societe', 'lire');
	$permissiontodelete = $user->hasRight('societe', 'supprimer');
	$permissiontoadd = $user->hasRight("societe", "creer");
	$uploaddir = $conf->societe->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	if ($action == 'setstcomm') {
		$object = new Client($db);
		$result=$object->fetch(GETPOST('stcommsocid'));
		$object->stcomm_id=dol_getIdFromCode($db, GETPOST('stcomm', 'alpha'), 'c_stcomm');
		$result=$object->update($object->id, $user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}

		$action='';
	}
}

if ($search_status=='') {
	$search_status=1; // always display active thirdparty first
}



/*
 * View
 */

/*
 REM: Rules on permissions to see thirdparties
 Internal or External user + No permission to see customers => See nothing
 Internal user socid=0 + Permission to see ALL customers    => See all thirdparties
 Internal user socid=0 + No permission to see ALL customers => See only thirdparties linked to user that are sale representative
 External user socid=x + Permission to see ALL customers    => Can see only himself
 External user socid=x + No permission to see ALL customers => Can see only himself
 */

$form=new Form($db);
$formother=new FormOther($db);
$companystatic=new Societe($db);
$companyparent = new Societe($db);
$formcompany=new FormCompany($db);
$prospectstatic=new Client($db);
$prospectstatic->client=2;
$prospectstatic->loadCacheOfProspStatus();

$now=dol_now();

//$help_url="EN:Module_MyObject|FR:Module_MyObject_FR|ES:Módulo_MyObject";
$help_url='';
$title = $langs->trans("ListOfPatients");

$sql = "SELECT s.rowid, s.nom as name, s.name_alias, s.barcode, s.address, s.town, s.zip, s.datec, s.code_client, s.code_fournisseur, s.logo,";
$sql .= " s.entity,";
$sql .= " s.client, s.fournisseur,";
$sql .= " s.email, s.phone, s.fax, s.url, s.siren as idprof1, s.siret as idprof2, s.ape as idprof3, s.idprof4 as idprof4, s.idprof5 as idprof5, s.idprof6 as idprof6, s.tva_intra, s.fk_pays,";
$sql .= " s.tms as date_update, s.datec, s.import_key,";
$sql .= " s.code_compta, s.code_compta_fournisseur, s.parent as fk_parent, s.price_level,";
$sql .= " s.canvas, s.status as status,";
$sql .= " country.code as country_code, country.label as country_label,";
$sql .= " MAX(c.datecons) as lastcons, COUNT(c.rowid) as nb";
// We'll need these fields in order to filter by sale (including the case where the user can only see his prospects)
if ($search_sale && $search_sale != '-1') {
	$sql .= ", sc.fk_soc, sc.fk_user";
}
// We'll need these fields in order to filter by categ
if ($search_categ_cus && $search_categ_cus != -1) {
	$sql .= ", cc.fk_categorie, cc.fk_soc";
}
if ($search_categ_sup && $search_categ_sup != -1) {
	$sql .= ", cs.fk_categorie, cs.fk_soc";
}
// Add fields from extrafields
if (! empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
	}
}
// Add fields from hooks
$parameters=array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX."c_stcomm as st";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (!$user->rights->societe->client->voir && !$socid)) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
// We'll need this table joined to the select in order to filter by categ
if ($search_categ > 0) $sql.= ", ".MAIN_DB_PREFIX."categorie_societe as cs";
$sql .= ", ".MAIN_DB_PREFIX."societe as s";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."cabinetmed_cons as c ON c.fk_soc = s.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as ef ON ef.fk_object = s.rowid";
$sql .= ' WHERE s.entity IN ('.getEntity('societe', 1).')';
$sql .= " AND s.canvas='patient@cabinetmed'";
$sql .= " AND s.fk_stcomm = st.id";
$sql .= " AND s.client IN (1, 3)";

if ($search_sale && $search_sale != '-1' && $search_sale != '-2') {
	$sql .= " AND s.rowid = sc.fk_soc"; // Join for the needed table to filter by sale
}
if ($search_sale == -2) {
	$sql .= " AND sc.fk_user IS NULL";
} elseif ($search_sale > 0) {
	$sql .= " AND sc.fk_user = ".((int) $search_sale);
}
$searchCategoryCustomerList = $search_categ_cus ? array($search_categ_cus) : array();;
$searchCategoryCustomerOperator = 0;
// Search for tag/category ($searchCategoryCustomerList is an array of ID)
if (!empty($searchCategoryCustomerList)) {
	$searchCategoryCustomerSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
		if (intval($searchCategoryCustomer) == -2) {
			$searchCategoryCustomerSqlList[] = "NOT EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as ck WHERE s.rowid = ck.fk_soc)";
		} elseif (intval($searchCategoryCustomer) > 0) {
			if ($searchCategoryCustomerOperator == 0) {
				$searchCategoryCustomerSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie = ".((int) $searchCategoryCustomer).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryCustomer);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryCustomerSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategoryCustomerOperator == 1) {
		if (!empty($searchCategoryCustomerSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategoryCustomerSqlList).")";
		}
	} else {
		if (!empty($searchCategoryCustomerSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategoryCustomerSqlList).")";
		}
	}
}
$searchCategorySupplierList = $search_categ_sup ? array($search_categ_sup) : array();
$searchCategorySupplierOperator = 0;
// Search for tag/category ($searchCategorySupplierList is an array of ID)
if (!empty($searchCategorySupplierList)) {
	$searchCategorySupplierSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategorySupplierList as $searchCategorySupplier) {
		if (intval($searchCategorySupplier) == -2) {
			$searchCategorySupplierSqlList[] = "NOT EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_fournisseur as ck WHERE s.rowid = ck.fk_soc)";
		} elseif (intval($searchCategorySupplier) > 0) {
			if ($searchCategorySupplierOperator == 0) {
				$searchCategorySupplierSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_fournisseur as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie = ".((int) $searchCategorySupplier).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategorySupplier);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategorySupplierSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_fournisseur as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategorySupplierOperator == 1) {
		if (!empty($searchCategorySupplierSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategorySupplierSqlList).")";
		}
	} else {
		if (!empty($searchCategorySupplierSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategorySupplierSqlList).")";
		}
	}
}

if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
if (strlen($search_cti)) {
	$sql .= natural_search('s.phone', $search_cti);
}

if ($search_id > 0) {
	$sql .= natural_search("s.rowid", $search_id, 1);
}
if ($search_nom) {
	$sql .= natural_search("s.nom", $search_nom);
}
if ($search_alias) {
	$sql .= natural_search("s.name_alias", $search_alias);
}
if ($search_nom_only) {
	$sql .= natural_search("s.nom", $search_nom_only);
}
if ($search_diagles) {
	$label = dol_getIdFromCode($db, $search_diagles, 'cabinetmed_diaglec', 'code', 'label');
	$sql .= natural_search("c.diaglesprinc", $label);
}
if (! $user->rights->societe->client->voir && ! $socid)	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid && empty($conf->global->MAIN_DISABLE_RESTRICTION_ON_THIRDPARTY_FOR_EXTERNAL)) $sql.= " AND s.rowid = ".$socid;
if ($search_sale > 0)  $sql.= " AND s.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
if ($search_categ > 0) $sql.= " AND s.rowid = cs.fk_soc";	// Join for the needed table to filter by categ
if ($search_zip) $sql.= natural_search("s.zip", $search_zip);
if ($search_town) $sql.= natural_search("s.town", $search_town);
if ($search_code)  $sql.= natural_search("s.code_client", $search_code);
// Insert categ filter
if ($search_categ > 0) {
	$sql .= " AND cs.fk_categorie = ".((int) $search_categ);
}
if ($search_contactid > 0) {
	$sql .= " AND s.rowid IN (SELECT ec.element_id FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc WHERE ec.fk_socpeople = ".$search_contactid." AND ec.fk_c_type_contact = tc.rowid AND tc.element='societe')";
}

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	if ($socid) {
		$sql .= " AND s.rowid = ".((int) $socid);
	}
}
$sql .= $hookmanager->resPrint;

$sql .= " GROUP BY s.rowid, s.nom, s.client, s.zip, s.town, st.libelle, s.prefix_comm, s.code_client, s.phone, s.fax, s.datec, s.canvas, s.status,";
$sql .= " s.client, s.fournisseur,";
$sql .= " s.name_alias, s.barcode, s.address, s.code_fournisseur, s.logo, s.entity, s.email, s.url, s.siren, s.siret, s.ape, s.idprof4, s.idprof5, s.idprof6, s.tva_intra,";
$sql .= " s.fk_pays, s.tms, s.import_key, s.code_compta, s.code_compta_fournisseur, s.parent, s.price_level,";
$sql .= " country.code, country.label";
if ($search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user";
// We'll need these fields in order to filter by categ
if ($search_categ_cus > 0) $sql .= ", cc.fk_categorie, cc.fk_soc";
if ($search_categ_sup > 0) $sql .= ", cs.fk_categorie, cs.fk_soc";
// Add fields from extrafields
if (! empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql.=($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key : '');
}
// Add GroupBy from hooks
$parameters = array('fieldstosearchall' => $fieldstosearchall);
$reshook = $hookmanager->executeHooks('printFieldListGroupBy', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

// Count total nb of records with no order and no limits
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^SELECT[a-zA-Z0-9\._\s\(\),=<>\:\-\']+\sFROM/Ui', 'SELECT COUNT(*) as nbtotalofrecords FROM', $sql);
	$sqlforcount = preg_replace('/LEFT JOIN '.MAIN_DB_PREFIX.'cabinetmed_cons as c ON c.fk_soc = s.rowid/', '', $sqlforcount);
	$sqlforcount = preg_replace('/GROUP BY .*/', '', $sqlforcount);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Complete request and execute it with limit
$sql.= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (! $resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

$arrayofselected=is_array($toselect)?$toselect:array();

// Direct jump if only one record found
if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && ($search_all != '' || $search_cti != '') && $action != 'list' && ! $page) {
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;

	$url = DOL_URL_ROOT.'/societe/card.php?socid='.$id;

	header("Location: ".$url);
	exit;
}

llxHeader('', $title, $help_url);

$param='';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}
if ($search_all != '')     $param = "&sall=".urlencode($search_all);
if ($sall != '')           $param.= "&sall=".urlencode($sall);
if ($search_categ_cus > 0) $param.= '&search_categ_cus='.urlencode($search_categ_cus);
if ($search_categ_sup > 0) $param.= '&search_categ_sup='.urlencode($search_categ_sup);
if ($search_sale > 0)	   $param.= '&search_sale='.urlencode($search_sale);
if ($search_id > 0)        $param.= "&search_id=".urlencode($search_id);
if ($search_nom != '')     $param.= "&search_nom=".urlencode($search_nom);
if ($search_alias != '')   $param.= "&search_alias=".urlencode($search_alias);
if ($search_address != '') {
	$param .= '&search_address='.urlencode($search_address);
}
if ($search_zip != '')     $param.= "&search_zip=".urlencode($search_zip);
if ($search_town != '')    $param.= "&search_town=".urlencode($search_town);
if ($search_phone != '')   $param.= "&search_phone=".urlencode($search_phone);
if ($search_fax != '')     $param.= "&search_fax=".urlencode($search_fax);
if ($search_email != '')   $param.= "&search_email=".urlencode($search_email);
if ($search_url != '')     $param.= "&search_url=".urlencode($search_url);
if ($search_state != '')   $param.= "&search_state=".urlencode($search_state);
if ($search_country != '') $param.= "&search_country=".urlencode($search_country);
if ($search_customer_code != '') $param.= "&search_customer_code=".urlencode($search_customer_code);
if ($search_supplier_code != '') $param.= "&search_supplier_code=".urlencode($search_supplier_code);
if ($search_account_customer_code != '') $param.= "&search_account_customer_code=".urlencode($search_account_customer_code);
if ($search_account_supplier_code != '') $param.= "&search_account_supplier_code=".urlencode($search_account_supplier_code);
if ($search_barcode != '') $param.= "&search_barcode=".urlencode($search_barcode);
if ($search_idprof1 != '') $param.= '&search_idprof1='.urlencode($search_idprof1);
if ($search_idprof2 != '') $param.= '&search_idprof2='.urlencode($search_idprof2);
if ($search_idprof3 != '') $param.= '&search_idprof3='.urlencode($search_idprof3);
if ($search_idprof4 != '') $param.= '&search_idprof4='.urlencode($search_idprof4);
if ($search_idprof5 != '') $param.= '&search_idprof5='.urlencode($search_idprof5);
if ($search_idprof6 != '') $param.= '&search_idprof6='.urlencode($search_idprof6);
if ($search_vat != '')     $param.= '&search_vat='.urlencode($search_vat);
if ($search_type_thirdparty != '')    $param.='&search_type_thirdparty='.urlencode($search_type_thirdparty);
if ($search_type != '')    $param.='&search_type='.urlencode($search_type);
if ($optioncss != '')      $param.='&optioncss='.urlencode($optioncss);
if ($search_status != '')  $param.='&search_status='.urlencode($search_status);
if ($search_stcomm != '')  $param.='&search_stcomm='.urlencode($search_stcomm);
//if ($search_level_from != '') $param.='&search_level_from='.urlencode($search_level_from);
//if ($search_level_to != '')   $param.='&search_level_to='.urlencode($search_level_to);
if ($search_import_key != '') $param.='&search_import_key='.urlencode($search_import_key);
if ($search_diagles != '')    $param.='&search_diagles='.urlencode($search_diagles);
if ($type != '') $param.='&type='.urlencode($type);
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// Show delete result message
if (GETPOST('delsoc')) {
	setEventMessages($langs->trans("CompanyDeleted", GETPOST('delsoc')), null, 'mesgs');
}

// List of mass actions available
$arrayofmassactions =  array(
	'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
	//'validate'=>$langs->trans("Validate"),
	//'generate_doc'=>$langs->trans("ReGeneratePDF"),
	//'builddoc'=>$langs->trans("PDFMerge"),
	//'presend'=>$langs->trans("SendByMail"),
);
//if($user->rights->societe->creer) $arrayofmassactions['createbills']=$langs->trans("CreateInvoiceForThisCustomer");
if (isModEnabled('category') && $user->hasRight("societe", "creer")) {
	$arrayofmassactions['preaffecttag'] = img_picto('', 'category', 'class="pictofixedwidth"').$langs->trans("AffectTag");
}
if ($user->hasRight("societe", "creer")) {
	$arrayofmassactions['preenable'] = img_picto('', 'stop-circle', 'class="pictofixedwidth"').$langs->trans("SetToStatus", $object->LibStatut($object::STATUS_INACTIVITY));
}
if ($user->hasRight("societe", "creer")) {
	$arrayofmassactions['predisable'] = img_picto('', 'stop-circle', 'class="pictofixedwidth"').$langs->trans("SetToStatus", $object->LibStatut($object::STATUS_CEASED));
}
if ($user->hasRight("societe", "creer")) {
	$arrayofmassactions['presetcommercial'] = img_picto('', 'user', 'class="pictofixedwidth"').$langs->trans("AllocateCommercial");
}
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete', 'preaffecttag', 'preenable', 'preclose'))) {
	$arrayofmassactions = array();
}
if ($user->hasRight('societe', 'supprimer')) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

$newcardbutton='';
if ($user->rights->societe->creer && $contextpage != 'poslist') {
	$typefilter='';
	$label='MenuNewPatient';

	if (! empty($type)) {
		$typefilter = '&type='.$type;
		if ($type == 'p') $label='MenuNewProspect';
		if ($type == 'c') $label='MenuNewCustomer';
		if ($type == 'f') $label='NewSupplier';
	}

	$newcardbutton = '<a class="butActionNew" href="card.php?action=create&canvas=patient@cabinetmed">';
	$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle" title="'.dol_escape_htmltag($langs->trans($label)).'"></span>';
	$newcardbutton.= '</a>';
}

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'" name="formfilter" autocomplete="off">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
//print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'user-injured', 0, $newcardbutton, '', $limit, 0, 0, 1);

$langs->load("other");
$textprofid=array();
foreach (array(1,2,3,4,5,6) as $key) {
	$label=$langs->transnoentities("ProfId".$key.$mysoc->country_code);
	$textprofid[$key]='';
	if ($label != "ProfId".$key.$mysoc->country_code) {	// Get only text between ()
		if (preg_match('/\((.*)\)/i', $label, $reg)) {
			$label = $reg[1];
		}
		$textprofid[$key]=$langs->trans("ProfIdShortDesc", $key, $mysoc->country_code, $label);
	}
}

// Add code for pre mass action (confirmation or email presend form)
$topicmail="Information";
$modelmail="thirdparty";
$objecttmp=new Societe($db);
$trackid='thi'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
	}
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all) . join(', ', $fieldstosearchall).'</div>';
}

// Filter on categories
$moreforfilter='';
if (isModEnabled("categorie")) {
	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter.=img_picto('', 'category', 'class="pictofixedwidth"').$formother->select_categories(2, $search_categ, 'search_categ', 1, $langs->trans('Categories'), 'maxwidth300 widthcentpercentminusx');
	$moreforfilter.='</div>';
}

// If the user can view prospects other than his'
if ($user->rights->societe->client->voir || $socid) {
	$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter.=img_picto('', 'user', 'class="pictofixedwidth"').$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, $langs->trans('ConsultCreatedBy'), 'maxwidth300 widthcentpercentminusx');
	$moreforfilter.='</div>';
}
// To add filter on contact
$width="200";
$moreforfilter.='<div class="divsearchfield">';
if ((float) DOL_VERSION >= 16.0) {
	$moreforfilter.=img_picto('', 'user-md', 'class="pictofixedwidth"').$form->selectcontacts(0, $search_contactid, 'search_contactid', $langs->trans('Correspondants'), '', '', 1, 'maxwidth300 widthcentpercentminusx');
} else {
	$moreforfilter.=img_picto('', 'user-md', 'class="pictofixedwidth"').$form->selectcontacts(0, $search_contactid, 'search_contactid', 1, '', '', 1);
}
$moreforfilter.='</div>';
// To add filter on diagnostic
$width="200";
$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('DiagnostiqueLesionnel'). ': ';
$moreforfilter.=listdiagles(1, $width, 'search_diagles', $search_diagles);
$moreforfilter.='</div>';

$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	$parameters = array('type'=>$type);
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '</div>';
}

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', '')); // This also change content of $arrayfields
// Show the massaction checkboxes only when this page is not opend from the Extended POS
if ($massactionbutton && $contextpage != 'poslist') {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
}

if (empty($arrayfields['customerorsupplier']['checked'])) {
	print '<input type="hidden" name="type" value="'.$type.'">';
}

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

// Fields title search
print '<tr class="liste_titre_filter">';
if (!empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
	// Action column
	print '<td class="liste_titre center actioncolumn">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
if (! empty($arrayfields['s.rowid']['checked'])) {
	print '<td class="liste_titre" data-key="id">';
	print '<input class="flat searchstring" type="text" name="search_id" size="1" value="'.dol_escape_htmltag($search_id).'">';
	print '</td>';
}
if (! empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre" data-key="ref">';
	if (!empty($search_nom_only) && empty($search_nom)) {
		$search_nom = $search_nom_only;
	}
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_nom" value="'.dol_escape_htmltag($search_nom).'">';
	print '</td>';
}
if (! empty($arrayfields['s.name_alias']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_alias" value="'.dol_escape_htmltag($search_alias).'">';
	print '</td>';
}
// Barcode
if (! empty($arrayfields['s.barcode']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_barcode" value="'.dol_escape_htmltag($search_barcode).'">';
	print '</td>';
}
// Customer code
if (! empty($arrayfields['s.code_client']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_customer_code" value="'.dol_escape_htmltag($search_customer_code).'">';
	print '</td>';
}
// Supplier code
if (! empty($arrayfields['s.code_fournisseur']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_supplier_code" value="'.dol_escape_htmltag($search_supplier_code).'">';
	print '</td>';
}
// Account Customer code
if (! empty($arrayfields['s.code_compta']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_account_customer_code" value="'.dol_escape_htmltag($search_account_customer_code).'">';
	print '</td>';
}
// Account Supplier code
if (! empty($arrayfields['s.code_compta_fournisseur']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth75imp" type="text" name="search_account_supplier_code" value="'.dol_escape_htmltag($search_account_supplier_code).'">';
	print '</td>';
}
// Address
if (!empty($arrayfields['s.address']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_address" value="'.dol_escape_htmltag($search_address).'">';
	print '</td>';
}
// Zip
if (! empty($arrayfields['s.zip']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_zip" value="'.dol_escape_htmltag($search_zip).'">';
	print '</td>';
}
// Town
if (! empty($arrayfields['s.town']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_town" value="'.dol_escape_htmltag($search_town).'">';
	print '</td>';
}
// State
if (! empty($arrayfields['state.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
	print '</td>';
}
// Region
if (! empty($arrayfields['region.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_region" value="'.dol_escape_htmltag($search_region).'">';
	print '</td>';
}
// Country
if (! empty($arrayfields['country.code_iso']['checked'])) {
	print '<td class="liste_titre center">';
	print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
	print '</td>';
}
// Company type
if (! empty($arrayfields['typent.code']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';
	// We use showempty=0 here because there is already an unknown value into dictionary.
	print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 1, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT), 'minwidth50 maxwidth125', 1);
	print '</td>';
}
// Multiprice level
if (!empty($arrayfields['s.price_level']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_price_level" value="'.dol_escape_htmltag($search_price_level).'">';
	print '</td>';
}
// Staff
if (!empty($arrayfields['staff.code']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';
	print $form->selectarray("search_staff", $formcompany->effectif_array(0), $search_staff, 0, 0, 0, '', 0, 0, 0, 'ASC', 'maxwidth100', 1);
	print '</td>';
}
if (! empty($arrayfields['s.email']['checked'])) {
	// Email
	print '<td class="liste_titre">';
	print '<input class="flat searchemail maxwidth50imp" type="text" name="search_email" value="'.dol_escape_htmltag($search_email).'">';
	print '</td>';
}
if (! empty($arrayfields['s.phone']['checked'])) {
	// Phone
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_phone" value="'.dol_escape_htmltag($search_phone).'">';
	print '</td>';
}
if (! empty($arrayfields['s.fax']['checked'])) {
	// Fax
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_fax" value="'.dol_escape_htmltag($search_fax).'">';
	print '</td>';
}
if (! empty($arrayfields['s.url']['checked'])) {
	// Url
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_url" value="'.dol_escape_htmltag($search_url).'">';
	print '</td>';
}
if (! empty($arrayfields['s.siren']['checked'])) {
	// IdProf1
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_idprof1" value="'.dol_escape_htmltag($search_idprof1).'">';
	print '</td>';
}
if (! empty($arrayfields['s.siret']['checked'])) {
	// IdProf2
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_idprof2" value="'.dol_escape_htmltag($search_idprof2).'">';
	print '</td>';
}
if (! empty($arrayfields['s.ape']['checked'])) {
	// IdProf3
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_idprof3" value="'.dol_escape_htmltag($search_idprof3).'">';
	print '</td>';
}
if (! empty($arrayfields['s.idprof4']['checked'])) {
	// IdProf4
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_idprof4" value="'.dol_escape_htmltag($search_idprof4).'">';
	print '</td>';
}
if (! empty($arrayfields['s.idprof5']['checked'])) {
	// IdProf5
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_idprof5" value="'.dol_escape_htmltag($search_idprof5).'">';
	print '</td>';
}
if (! empty($arrayfields['s.idprof6']['checked'])) {
	// IdProf6
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_idprof6" value="'.dol_escape_htmltag($search_idprof6).'">';
	print '</td>';
}
if (! empty($arrayfields['s.tva_intra']['checked'])) {
	// Vat number
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50imp" type="text" name="search_vat" value="'.dol_escape_htmltag($search_vat).'">';
	print '</td>';
}

// Nature (customer/prospect/supplier)
if (! empty($arrayfields['customerorsupplier']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';
	if ($type != '') {
		print '<input type="hidden" name="type" value="'.$type.'">';
	}
	print $formcompany->selectProspectCustomerType($search_type, 'search_type', 'search_type', 'list');
	print '</td>';
}
// Prospect level
if (!empty($arrayfields['s.fk_prospectlevel']['checked'])) {
	print '<td class="liste_titre center">';
	print $form->multiselectarray('search_level', $tab_level, $search_level, 0, 0, 'width75', 0, 0, '', '', '', 2);
	print '</td>';
}
// Prospect status
if (!empty($arrayfields['s.fk_stcomm']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';
	$arraystcomm = array();
	foreach ($prospectstatic->cacheprospectstatus as $key => $val) {
		$arraystcomm[$val['id']] = ($langs->trans("StatusProspect".$val['id']) != "StatusProspect".$val['id'] ? $langs->trans("StatusProspect".$val['id']) : $val['label']);
	}
	print $form->selectarray('search_stcomm', $arraystcomm, $search_stcomm, -2, 0, 0, '', 0, 0, 0, '', '', 1);
	print '</td>';
}
if (!empty($arrayfields['s2.nom']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_parent_name" value="'.dol_escape_htmltag($search_parent_name).'">';
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters=array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Nb
if (! empty($arrayfields['nb']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Last cons
if (! empty($arrayfields['lastcons']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Date creation
if (! empty($arrayfields['s.datec']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Date modification
if (! empty($arrayfields['s.tms']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Status
if (! empty($arrayfields['s.status']['checked'])) {
	print '<td class="liste_titre center minwidth75imp">';
	print $form->selectarray('search_status', array('0'=>$langs->trans('ActivityCeased'), '1'=>$langs->trans('InActivity')), $search_status, 1, 0, 0, '', 0, 0, 0, '', 'search_status minwidth75 maxwidth125 onrightofpage', 1);
	print '</td>';
}
if (! empty($arrayfields['s.import_key']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_import_key" value="'.dol_escape_htmltag($search_import_key).'">';
	print '</td>';
}
if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
	// Action column
	print '<td class="liste_titre center actioncolumn">';
	$searchpicto=$form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print "</tr>\n";

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
if (!empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch actioncolumn ');
}
if (! empty($arrayfields['s.rowid']['checked']))                   print_liste_field_titre($arrayfields['s.rowid']['label'], $_SERVER["PHP_SELF"], "s.rowid", "", $param, "", $sortfield, $sortorder);
if (! empty($arrayfields['s.nom']['checked']))                     print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", "", $param, "", $sortfield, $sortorder);
if (! empty($arrayfields['s.name_alias']['checked']))              print_liste_field_titre($arrayfields['s.name_alias']['label'], $_SERVER["PHP_SELF"], "s.name_alias", "", $param, "", $sortfield, $sortorder);
if (! empty($arrayfields['s.barcode']['checked']))                 print_liste_field_titre($arrayfields['s.barcode']['label'], $_SERVER["PHP_SELF"], "s.barcode", $param, '', '', $sortfield, $sortorder);
if (! empty($arrayfields['s.code_client']['checked']))             print_liste_field_titre($arrayfields['s.code_client']['label'], $_SERVER["PHP_SELF"], "s.code_client", "", $param, '', $sortfield, $sortorder);
if (! empty($arrayfields['s.code_fournisseur']['checked']))        print_liste_field_titre($arrayfields['s.code_fournisseur']['label'], $_SERVER["PHP_SELF"], "s.code_fournisseur", "", $param, '', $sortfield, $sortorder);
if (! empty($arrayfields['s.code_compta']['checked']))             print_liste_field_titre($arrayfields['s.code_compta']['label'], $_SERVER["PHP_SELF"], "s.code_compta", "", $param, '', $sortfield, $sortorder);
if (! empty($arrayfields['s.code_compta_fournisseur']['checked'])) print_liste_field_titre($arrayfields['s.code_compta_fournisseur']['label'], $_SERVER["PHP_SELF"], "s.code_compta_fournisseur", "", $param, '', $sortfield, $sortorder);
if (! empty($arrayfields['s.zip']['checked']))            print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], "s.zip", "", $param, '', $sortfield, $sortorder);
if (! empty($arrayfields['s.town']['checked']))           print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], "s.town", "", $param, '', $sortfield, $sortorder);
if (! empty($arrayfields['state.nom']['checked']))        print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
if (! empty($arrayfields['region.nom']['checked']))       print_liste_field_titre($arrayfields['region.nom']['label'], $_SERVER["PHP_SELF"], "region.nom", "", $param, '', $sortfield, $sortorder);
if (! empty($arrayfields['country.code_iso']['checked'])) print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country.code_iso", "", $param, 'align="center"', $sortfield, $sortorder);
if (! empty($arrayfields['typent.code']['checked']))      print_liste_field_titre($arrayfields['typent.code']['label'], $_SERVER["PHP_SELF"], "typent.code", "", $param, 'align="center"', $sortfield, $sortorder);
if (! empty($arrayfields['s.email']['checked']))          print_liste_field_titre($arrayfields['s.email']['label'], $_SERVER["PHP_SELF"], "s.email", "", $param, '', $sortfield, $sortorder);
if (! empty($arrayfields['s.phone']['checked']))          print_liste_field_titre($arrayfields['s.phone']['label'], $_SERVER["PHP_SELF"], "s.phone", "", $param, '', $sortfield, $sortorder);
if (! empty($arrayfields['s.fax']['checked'])) print_liste_field_titre($arrayfields['s.fax']['label'], $_SERVER["PHP_SELF"], "s.fax", "", $param, '', $sortfield, $sortorder);
if (! empty($arrayfields['s.url']['checked']))            print_liste_field_titre($arrayfields['s.url']['label'], $_SERVER["PHP_SELF"], "s.url", "", $param, '', $sortfield, $sortorder);
if (! empty($arrayfields['s.siren']['checked']))          print_liste_field_titre($form->textwithpicto($langs->trans("ProfId1Short"), $textprofid[1], 1, 0), $_SERVER["PHP_SELF"], "s.siren", "", $param, 'class="nowrap"', $sortfield, $sortorder);
if (! empty($arrayfields['s.siret']['checked']))          print_liste_field_titre($form->textwithpicto($langs->trans("ProfId2Short"), $textprofid[2], 1, 0), $_SERVER["PHP_SELF"], "s.siret", "", $param, 'class="nowrap"', $sortfield, $sortorder);
if (! empty($arrayfields['s.ape']['checked']))            print_liste_field_titre($form->textwithpicto($langs->trans("ProfId3Short"), $textprofid[3], 1, 0), $_SERVER["PHP_SELF"], "s.ape", "", $param, 'class="nowrap"', $sortfield, $sortorder);
if (! empty($arrayfields['s.idprof4']['checked']))        print_liste_field_titre($form->textwithpicto($langs->trans("ProfId4Short"), $textprofid[4], 1, 0), $_SERVER["PHP_SELF"], "s.idprof4", "", $param, 'class="nowrap"', $sortfield, $sortorder);
if (! empty($arrayfields['s.idprof5']['checked']))        print_liste_field_titre($form->textwithpicto($langs->trans("ProfId5Short"), $textprofid[4], 1, 0), $_SERVER["PHP_SELF"], "s.idprof5", "", $param, 'class="nowrap"', $sortfield, $sortorder);
if (! empty($arrayfields['s.idprof6']['checked']))        print_liste_field_titre($form->textwithpicto($langs->trans("ProfId6Short"), $textprofid[4], 1, 0), $_SERVER["PHP_SELF"], "s.idprof6", "", $param, 'class="nowrap"', $sortfield, $sortorder);
if (! empty($arrayfields['s.tva_intra']['checked']))      print_liste_field_titre($arrayfields['s.tva_intra']['label'], $_SERVER["PHP_SELF"], "s.tva_intra", "", $param, 'class="nowrap"', $sortfield, $sortorder);
if (! empty($arrayfields['customerorsupplier']['checked']))        print_liste_field_titre('');   // type of customer
if (! empty($arrayfields['s.fk_prospectlevel']['checked']))        print_liste_field_titre($arrayfields['s.fk_prospectlevel']['label'], $_SERVER["PHP_SELF"], "s.fk_prospectlevel", "", $param, 'align="center"', $sortfield, $sortorder);
if (! empty($arrayfields['s.fk_stcomm']['checked']))               print_liste_field_titre($arrayfields['s.fk_stcomm']['label'], $_SERVER["PHP_SELF"], "s.fk_stcomm", "", $param, 'align="center"', $sortfield, $sortorder);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (! empty($arrayfields['nb']['checked']))           print_liste_field_titre($arrayfields['nb']['label'], $_SERVER["PHP_SELF"], "nb", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
if (! empty($arrayfields['lastcons']['checked']))     print_liste_field_titre($arrayfields['lastcons']['label'], $_SERVER["PHP_SELF"], "lastcons", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
if (! empty($arrayfields['s.datec']['checked']))      print_liste_field_titre($arrayfields['s.datec']['label'], $_SERVER["PHP_SELF"], "s.datec", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
if (! empty($arrayfields['s.tms']['checked']))        print_liste_field_titre($arrayfields['s.tms']['label'], $_SERVER["PHP_SELF"], "s.tms", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
if (! empty($arrayfields['s.status']['checked']))     print_liste_field_titre($arrayfields['s.status']['label'], $_SERVER["PHP_SELF"], "s.status", "", $param, 'align="center"', $sortfield, $sortorder);
if (! empty($arrayfields['s.import_key']['checked'])) print_liste_field_titre($arrayfields['s.import_key']['label'], $_SERVER["PHP_SELF"], "s.import_key", "", $param, 'align="center"', $sortfield, $sortorder);
// Action column
if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'center maxwidthsearch actioncolumn ');
}
print '</tr>'."\n";


// Loop on record
// --------------------------------------------------------------------
$i = 0;
$totalarray = array();
$totalarray['nbfield'] = 0;
while ($i < min($num, $limit)) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) break;		// Should not happen

	// Store properties
	$companystatic->id=$obj->rowid;
	$companystatic->name=$obj->name;
	$companystatic->name_alias=$obj->name_alias;
	$companystatic->logo=$obj->logo;
	$companystatic->canvas=$obj->canvas;
	$companystatic->client=$obj->client;
	$companystatic->status=$obj->status;
	$companystatic->email=$obj->email;
	$companystatic->fournisseur=$obj->fournisseur;
	$companystatic->code_client=$obj->code_client;
	$companystatic->code_fournisseur=$obj->code_fournisseur;
	$companystatic->tva_intra = $obj->tva_intra;
	$companystatic->country_code = $obj->country_code;

	$companystatic->code_compta_client=$obj->code_compta;
	$companystatic->code_compta_fournisseur=$obj->code_compta_fournisseur;

	//$companystatic->fk_prospectlevel=$obj->fk_prospectlevel;

	print '<tr class="oddeven">';

	// Action column (Show the massaction button only when this page is not opend from the Extended POS)
	if (!empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
		print '<td class="nowrap center actioncolumn">';
		if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($obj->rowid, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (! empty($arrayfields['s.rowid']['checked'])) {
		print '<td class="tdoverflowmax50" data-key="id">';
		print $obj->rowid;
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (! empty($arrayfields['s.nom']['checked'])) {
		print '<td'.(empty($conf->global->MAIN_SOCIETE_SHOW_COMPLETE_NAME) ? ' class="tdoverflowmax200"' : '').' data-key="ref">';
		if ($contextpage == 'poslist') {
			print dol_escape_htmltag($obj->name);
		} else {
			print $companystatic->getNomUrl(1, '', 100, 0, 1, empty($arrayfields['s.name_alias']['checked']) ? 0 : 1);
		}
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (! empty($arrayfields['s.name_alias']['checked'])) {
		print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($companystatic->name_alias).'">';
		print dol_escape_htmltag($companystatic->name_alias);
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Barcode
	if (! empty($arrayfields['s.barcode']['checked'])) {
		print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->barcode).'>'.dol_escape_htmltag($obj->barcode).'</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Customer code
	if (! empty($arrayfields['s.code_client']['checked'])) {
		print '<td class="nowraponall">'.dol_escape_htmltag($obj->code_client).'</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Supplier code
	if (! empty($arrayfields['s.code_fournisseur']['checked'])) {
		print '<td class="nowraponall">'.dol_escape_htmltag($obj->code_fournisseur).'</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Account customer code
	if (! empty($arrayfields['s.code_compta']['checked'])) {
		print '<td>'.dol_escape_htmltag($obj->code_compta).'</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Account supplier code
	if (! empty($arrayfields['s.code_compta_fournisseur']['checked'])) {
		print '<td>'.dol_escape_htmltag($obj->code_compta_fournisseur).'</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Zip
	if (! empty($arrayfields['s.zip']['checked'])) {
		print "<td>".dol_escape_htmltag($obj->zip)."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	// Town
	if (! empty($arrayfields['s.town']['checked'])) {
		print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->town).'">'.dol_escape_htmltag($obj->town)."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	// State
	if (! empty($arrayfields['state.nom']['checked'])) {
		print "<td>".$obj->state_name."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	// Region
	if (! empty($arrayfields['region.nom']['checked'])) {
		print "<td>".$obj->region_name."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	// Country
	if (! empty($arrayfields['country.code_iso']['checked'])) {
		print '<td class="center">';
		$tmparray=getCountry($obj->fk_pays, 'all');
		print $tmparray['label'];
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Type ent
	if (! empty($arrayfields['typent.code']['checked'])) {
		print '<td class="center">';
		if (! is_array($typenArray) || count($typenArray)==0) $typenArray = $formcompany->typent_array(1);
		print $typenArray[$obj->typent_code];
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['s.email']['checked'])) {
		print "<td>".dol_print_email($obj->email, $obj->rowid, $obj->rowid, 'AC_EMAIL', 0, 0, 1)."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['s.phone']['checked'])) {
		print "<td>".dol_print_phone($obj->phone, $obj->country_code, 0, $obj->rowid, 'AC_TEL', ' ', 'phone')."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['s.fax']['checked'])) {
		print "<td>".dol_print_phone($obj->fax, $obj->country_code, 0, $obj->rowid, 'AC_TEL', ' ', 'fax')."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['s.url']['checked'])) {
		print "<td>".dol_print_url($obj->url, '', '', 1)."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['s.siren']['checked'])) {
		print "<td>".$obj->idprof1."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['s.siret']['checked'])) {
		print "<td>".$obj->idprof2."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['s.ape']['checked'])) {
		print "<td>".$obj->idprof3."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['s.idprof4']['checked'])) {
		print "<td>".$obj->idprof4."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['s.idprof5']['checked'])) {
		print "<td>".$obj->idprof5."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['s.idprof6']['checked'])) {
		print "<td>".$obj->idprof6."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['s.tva_intra']['checked'])) {
		print '<td class="tdoverflowmax125" title="'.dol_escape_htmltag($obj->tva_intra).'">';
		print $obj->tva_intra;
		print "</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	// Type
	if (! empty($arrayfields['customerorsupplier']['checked'])) {
		print '<td class="center">';
		print $companystatic->getTypeUrl(1);
		print '</td>';
		if (! $i) {
			$totalarray['nbfield']++;
		}
	}

	if (! empty($arrayfields['s.fk_prospectlevel']['checked'])) {
		// Prospect level
		print '<td class="center">';
		print $companystatic->getLibProspLevel();
		print "</td>";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (! empty($arrayfields['s.fk_stcomm']['checked'])) {
		// Prospect status
		print '<td class="center nowrap"><div class="nowraponall">';
		print '<div class="inline-block">';
		print $companystatic->LibProspCommStatut($obj->stcomm_id, 2, $prospectstatic->cacheprospectstatus[$obj->stcomm_id]['label']);
		print '</div> - <div class="inline-block">';
		foreach ($prospectstatic->cacheprospectstatus as $key => $val) {
			$titlealt='default';
			if (!empty($val['code']) && !in_array($val['code'], array('ST_NO', 'ST_NEVER', 'ST_TODO', 'ST_PEND', 'ST_DONE'))) {
				$titlealt = $val['label'];
			}
			if ($obj->stcomm_id != $val['id']) {
				print '<a class="pictosubstatus reposition" href="'.$_SERVER["PHP_SELF"].'?stcommsocid='.$obj->rowid.'&stcomm='.urlencode($val['code']).'&action=setstcomm&token='.newToken().$param.($page ? '&page='.urlencode($page) : '').'">'.img_action($titlealt, $val['code'], $val['picto']).'</a>';
			}
		}
		print '</div></div></td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Parent company
	if (!empty($arrayfields['s2.nom']['checked'])) {
		print '<td class="center tdoverflowmax100">';
		if ($companystatic->fk_parent > 0) {
			$companyparent->fetch($companystatic->fk_parent);
			print $companyparent->getNomUrl(1);
		}
		print "</td>";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Nb
	print '<td align="right">'.$obj->nb.'</td>';
	// Last consultation
	print '<td class="center nowraponall">';
	print dol_print_date($db->jdate($obj->lastcons), 'dayhour');
	print '</td>';
	// Date creation
	if (! empty($arrayfields['s.datec']['checked'])) {
		print '<td class="center nowraponall">';
		print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Date modification
	if (! empty($arrayfields['s.tms']['checked'])) {
		print '<td class="center nowraponall">';
		print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Status
	if (! empty($arrayfields['s.status']['checked'])) {
		print '<td class="center nowraponall">'.$companystatic->getLibStatut(3).'</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['s.import_key']['checked'])) {
		print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($obj->import_key).'">';
		print dol_escape_htmltag($obj->import_key);
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Action column (Show the massaction button only when this page is not opend from the Extended POS)
	if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
		print '<td class="nowrap center actioncolumn">';
		if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected=0;
			if (in_array($obj->rowid, $arrayofselected)) {
				$selected=1;
			}
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
		}
		print '</td>';
	}
	if (!$i) {
		$totalarray['nbfield']++;
	}

	print '</tr>'."\n";
	$i++;
}

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

$db->free($resql);

$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>\n";
print "</div>";

print "</form>\n";

// End of page
llxFooter();
$db->close();
