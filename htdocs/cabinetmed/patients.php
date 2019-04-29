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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/cabinetmed/patients.php
 *	\ingroup    cabinetmed
 *	\brief      Page to show list of  patients
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once("./lib/cabinetmed.lib.php");

$langs->load("companies");
$langs->load("customers");
$langs->load("suppliers");
$langs->load("commercial");
$langs->load("other");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user,'societe',$socid,'');

$search_all=(GETPOSTISSET('search_all')?GETPOST('search_all', 'alpha'):GETPOST('sall', 'alpha'));
$search_cti=preg_replace('/^0+/', '', preg_replace('/[^0-9]/', '', GETPOST('search_cti', 'alphanohtml')));	// Phone number without any special chars

$search_id=trim(GETPOST("search_id","int"));
$search_nom=GETPOST("search_nom","alpha");
$search_ville=GETPOST("search_ville","alpha");
$search_code=GETPOST("search_code","alpha");

// Load sale and categ filters
$search_sale = GETPOST("search_sale","int");
$search_categ = GETPOST("search_categ","int");
$search_diagles=GETPOST("search_diagles","int");
$search_contactid = GETPOST("search_contactid","int");

$type=GETPOST('type','alpha');
$optioncss=GETPOST('optioncss','alpha');
$mode=GETPOST("mode",'');


$diroutputmassaction=$conf->societe->dir_output . '/temp/massgeneration/'.$user->id;

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Societe($db);
$hookmanager->initHooks(array('thirdpartylist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('societe');
if ((float) DOL_VERSION >= 9.0) $search_array_options=$extrafields->getOptionalsFromPost($object->table_element,'','search_');
else $search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	's.nom'=>"ThirdPartyName",
	's.name_alias'=>"AliasNameShort",
	's.code_client'=>"CustomerCode",
	's.code_compta'=>"CustomerAccountancyCodeShort",
	's.email'=>"EMail",
	's.tva_intra'=>"PatientVATIntra",
	's.phone'=>"Phone",
	's.fax'=>"Fax",
);
if (!empty($conf->barcode->enabled)) $fieldstosearchall['s.barcode']='Gencod';
// Personalized search criterias. Example: $conf->global->THIRDPARTY_QUICKSEARCH_ON_FIELDS = 's.nom=ThirdPartyName;s.name_alias=AliasNameShort;s.code_client=CustomerCode'
if (! empty($conf->global->THIRDPARTY_QUICKSEARCH_ON_FIELDS)) $fieldstosearchall=dolExplodeIntoArray($conf->global->THIRDPARTY_QUICKSEARCH_ON_FIELDS);


$arrayfields=array(
's.rowid'=>array('label'=>"TechnicalID", 'checked'=>($conf->global->MAIN_SHOW_TECHNICAL_ID?1:0), 'enabled'=>($conf->global->MAIN_SHOW_TECHNICAL_ID?1:0)),
's.nom'=>array('label'=>"Patient", 'checked'=>1),
's.name_alias'=>array('label'=>"AliasNameShort", 'checked'=>0),
's.barcode'=>array('label'=>"Gencod", 'checked'=>0, 'enabled'=>(! empty($conf->barcode->enabled))),
's.code_client'=>array('label'=>"PatientCode", 'checked'=>1),
's.code_fournisseur'=>array('label'=>"SupplierCodeShort", 'checked'=>0, 'enabled'=>(! empty($conf->fournisseur->enabled))),
's.code_compta'=>array('label'=>"CustomerAccountancyCodeShort", 'checked'=>0),
's.code_compta_fournisseur'=>array('label'=>"SupplierAccountancyCodeShort", 'checked'=>0, 'enabled'=>(! empty($conf->fournisseur->enabled))),
's.town'=>array('label'=>"Town", 'checked'=>1),
's.zip'=>array('label'=>"Zip", 'checked'=>1),
'state.nom'=>array('label'=>"State", 'checked'=>0),
'region.nom'=>array('label'=>"Region", 'checked'=>0),
'country.code_iso'=>array('label'=>"Country", 'checked'=>0),
's.email'=>array('label'=>"Email", 'checked'=>0),
's.url'=>array('label'=>"Url", 'checked'=>0),
's.phone'=>array('label'=>"Phone", 'checked'=>1),
's.fax'=>array('label'=>"Fax", 'checked'=>0),
'typent.code'=>array('label'=>"ThirdPartyType", 'checked'=>0),
's.siren'=>array('label'=>"ProfId1Short", 'checked'=>0),
's.siret'=>array('label'=>"ProfId2Short", 'checked'=>0),
's.ape'=>array('label'=>"ProfId3Short", 'checked'=>0),
's.idprof4'=>array('label'=>"ProfId4Short", 'checked'=>0),
's.idprof5'=>array('label'=>"ProfId5Short", 'checked'=>0),
's.idprof6'=>array('label'=>"ProfId6Short", 'checked'=>0),
's.tva_intra'=>array('label'=>"VATIntra", 'checked'=>0),
'customerorsupplier'=>array('label'=>'Nature', 'checked'=>0),
'nb'=>array('label'=>'NbConsult', 'checked'=>1),
'lastcons'=>array('label'=>'LastConsultShort', 'checked'=>1),
's.datec'=>array('label'=>"DateCreation", 'checked'=>0, 'position'=>500),
's.tms'=>array('label'=>"DateModificationShort", 'checked'=>0, 'position'=>500),
's.status'=>array('label'=>"Status", 'checked'=>1, 'position'=>1000),
's.import_key'=>array('label'=>"ImportId", 'checked'=>0, 'position'=>1100),
);
// Extra fields
//if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0) // v9+
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
	foreach($extrafields->attribute_label as $key => $val)
	{
		if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>(abs($extrafields->attribute_list[$key])!=3 && $extrafields->attribute_perms[$key]));
	}
}



/*
 * Actions
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Did we click on purge search criteria ?
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
	{
		$search_id='';
		$search_nom='';
		$search_categ='';
		$search_sale='';
		$search_code='';
		$search_diagles='';
		$socname="";
		$search_ville="";
		$search_idprof1='';
		$search_idprof2='';
		$search_idprof3='';
		$search_idprof4='';
		$search_contactid='';
		$search_status=-1;
		$search_birthday='';
		$search_birthmonth='';
		$search_birthyear='';
		$toselect='';
		$search_array_options=array();
	}

	// Mass actions
	$objectclass='Societe';
	$objectlabel='ThirdParty';
	$permtoread = $user->rights->societe->lire;
	$permtodelete = $user->rights->societe->supprimer;
	$uploaddir = $conf->societe->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	if ($action == 'setstcomm')
	{
		$object = new Client($db);
		$result=$object->fetch(GETPOST('stcommsocid'));
		$object->stcomm_id=dol_getIdFromCode($db, GETPOST('stcomm','alpha'), 'c_stcomm');
		$result=$object->update($object->id, $user);
		if ($result < 0) setEventMessages($object->error,$object->errors,'errors');

		$action='';
	}
}

if ($search_status=='') $search_status=1; // always display active thirdparty first



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
$formcompany=new FormCompany($db);
$prospectstatic=new Client($db);
$prospectstatic->client=2;
$prospectstatic->loadCacheOfProspStatus();

$title = $langs->trans("ListOfPatients");

$sql = "SELECT s.rowid, s.nom as name, s.client, s.town, st.libelle as stcomm, s.prefix_comm, s.code_client,";
$sql.= " s.datec, s.canvas, s.status as status,";
$sql.= " MAX(c.datecons) as lastcons, COUNT(c.rowid) as nb";
// We'll need these fields in order to filter by sale (including the case where the user can only see his prospects)
if ($search_sale) $sql .= ", sc.fk_soc, sc.fk_user";
// We'll need these fields in order to filter by categ
if ($search_categ_cus) $sql .= ", cc.fk_categorie, cc.fk_soc";
if ($search_categ_sup) $sql .= ", cs.fk_categorie, cs.fk_soc";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM (".MAIN_DB_PREFIX."c_stcomm as st";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale || !$user->rights->societe->client->voir) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
// We'll need this table joined to the select in order to filter by categ
if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_societe as cs";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
$sql.= ") LEFT JOIN ".MAIN_DB_PREFIX."cabinetmed_cons as c ON c.fk_soc = s.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as ef ON ef.fk_object = s.rowid";
$sql.= ' WHERE s.entity IN ('.getEntity('societe', 1).')';
$sql.= " AND s.canvas='patient@cabinetmed'";
$sql.= " AND s.fk_stcomm = st.id";
$sql.= " AND s.client IN (1, 3)";
if ($search_diagles)
{
    $label= dol_getIdFromCode($db,$search_diagles,'cabinetmed_diaglec','code','label');
    $sql.= natural_search("c.diaglesprinc", $label);
}
if (!$user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid && empty($conf->global->MAIN_DISABLE_RESTRICTION_ON_THIRDPARTY_FOR_EXTERNAL)) $sql.= " AND s.rowid = ".$socid;
if ($search_sale) $sql.= " AND s.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
if ($search_categ) $sql.= " AND s.rowid = cs.fk_soc";	// Join for the needed table to filter by categ
if ($search_nom)   $sql.= natural_search("s.nom", $search_nom);
if ($search_ville) $sql.= natural_search("s.town", $search_ville);
if ($search_code)  $sql.= natural_search("s.code_client", $search_code);
if ($search_all)   $sql.= natural_search(array_keys($fieldstosearchall), $search_all);
// Insert sale filter
if ($search_sale)
{
	$sql .= " AND sc.fk_user = ".$search_sale;
}
// Insert categ filter
if ($search_categ)
{
	$sql .= " AND cs.fk_categorie = ".$search_categ;
}
if ($socname)
{
	$sql.= natural_search("s.nom", $socname);
    $sortfield = "s.nom";
	$sortorder = "ASC";
}
if ($search_contactid)
{
	$sql .= " AND s.rowid IN (SELECT ec.element_id FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc WHERE ec.fk_socpeople = ".$search_contactid." AND ec.fk_c_type_contact = tc.rowid AND tc.element='societe')";
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= " GROUP BY s.rowid, s.nom, s.client, s.town, st.libelle, s.prefix_comm, s.code_client, s.datec, s.canvas, s.status";
if ($search_sale) $sql .= ", sc.fk_soc, sc.fk_user";
// We'll need these fields in order to filter by categ
if ($search_categ_cus) $sql .= ", cc.fk_categorie, cc.fk_soc";
if ($search_categ_sup) $sql .= ", cs.fk_categorie, cs.fk_soc";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= $db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql.= $db->plimit($limit+1, $offset);

$resql = $db->query($sql);
if (! $resql)
{
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

$arrayofselected=is_array($toselect)?$toselect:array();

if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && ($search_all != '' || $search_cti != '') && $action != 'list')
{
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;

	$url = DOL_URL_ROOT.'/societe/card.php?socid='.$id;
	if ((float) DOL_VERSION < 6.0) DOL_URL_ROOT.'/societe/soc.php?socid='.$id;	// For backward compatibility

	header("Location: ".$url);
	exit;
}

$help_url='';
llxHeader('', $title,$help_url);

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
if ($search_all != '')     $param = "&sall=".urlencode($search_all);
if ($sall != '')           $param.= "&sall=".urlencode($sall);
if ($search_categ_cus > 0) $param.= '&search_categ_cus='.urlencode($search_categ_cus);
if ($search_categ_sup > 0) $param.= '&search_categ_sup='.urlencode($search_categ_sup);
if ($search_sale > 0)	   $param.= '&search_sale='.urlencode($search_sale);
if ($search_id > 0)        $param.= "&search_id=".urlencode($search_id);
if ($search_nom != '')     $param.= "&search_nom=".urlencode($search_nom);
if ($search_alias != '')   $param.= "&search_alias=".urlencode($search_alias);
if ($search_town != '')    $param.= "&search_town=".urlencode($search_town);
if ($search_zip != '')     $param.= "&search_zip=".urlencode($search_zip);
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
if ($search_level_from != '') $param.='&search_level_from='.urlencode($search_level_from);
if ($search_level_to != '')   $param.='&search_level_to='.urlencode($search_level_to);
if ($search_import_key != '') $param.='&search_import_key='.urlencode($search_import_key);
if ($type != '') $param.='&type='.urlencode($type);
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

if ($search_diagles != '')    $param.='&amp;search_diagles='.urlencode($search_diagles);


// List of mass actions available
$arrayofmassactions =  array(
//	'presend'=>$langs->trans("SendByMail"),
//    'builddoc'=>$langs->trans("PDFMerge"),
);
//if($user->rights->societe->creer) $arrayofmassactions['createbills']=$langs->trans("CreateInvoiceForThisCustomer");
//if ($user->rights->societe->supprimer) $arrayofmassactions['predelete']='<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

if ((float) DOL_VERSION >= 9.0)
{
	$newcardbutton='';
	if ($user->rights->societe->creer)
	{
		$typefilter='';
		$label='MenuNewPatient';

		if(! empty($type))
		{
			$typefilter = '&type='.$type;
			if($type == 'p') $label='MenuNewProspect';
			if($type == 'c') $label='MenuNewCustomer';
			if($type == 'f') $label='NewSupplier';
		}

		$newcardbutton = '<a class="butActionNew" href="'.DOL_URL_ROOT.'/societe/card.php?action=create&canvas=patient'.$typefilter.'"><span class="valignmiddle text-plus-circle">'.$langs->trans($label).'</span>';
		$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
		$newcardbutton.= '</a>';
	}
}

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="formfilter" autocomplete="off">'."\n";
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_companies', 0, $newcardbutton, '', $limit);

$langs->load("other");
$textprofid=array();
foreach(array(1,2,3,4,5,6) as $key)
{
	$label=$langs->transnoentities("ProfId".$key.$mysoc->country_code);
	$textprofid[$key]='';
	if ($label != "ProfId".$key.$mysoc->country_code)
	{	// Get only text between ()
		if (preg_match('/\((.*)\)/i',$label,$reg)) $label=$reg[1];
		$textprofid[$key]=$langs->trans("ProfIdShortDesc",$key,$mysoc->country_code,$label);
	}
}

$topicmail="Information";
$modelmail="thirdparty";
$objecttmp=new Societe($db);
$trackid='thi'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all)
{
	foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all) . join(', ',$fieldstosearchall).'</div>';
}

// Filter on categories
$moreforfilter='';
if (! empty($conf->categorie->enabled))
{
	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
 	$moreforfilter.='<div class="divsearchfield">';
 	$moreforfilter.=$langs->trans('Categories'). ': ';
	$moreforfilter.=$formother->select_categories(2,$search_categ,'search_categ');
 	$moreforfilter.='</div>';
}

// If the user can view prospects other than his'
if ($user->rights->societe->client->voir || $socid)
{
 	$moreforfilter.='<div class="divsearchfield">';
 	$moreforfilter.=$langs->trans('SalesRepresentatives'). ': ';
	$moreforfilter.=$formother->select_salesrepresentatives($search_sale,'search_sale',$user, 0, 1, 'maxwidth300');
	$moreforfilter.='</div>';
}
// To add filter on contact
$width="200";
$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('Correspondants'). ': ';
$moreforfilter.=$form->selectcontacts(0, $search_contactid, 'search_contactid', 1, '', '', 1);
$moreforfilter.='</div>';
// To add filter on diagnostic
$width="200";
$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('DiagnostiqueLesionnel'). ': ';
$moreforfilter.=listdiagles(1,$width,'search_diagles',$search_diagles);
$moreforfilter.='</div>';

if (! empty($moreforfilter))
{
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	$parameters=array('type'=>$type);
	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '</div>';
}

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

if (empty($arrayfields['customerorsupplier']['checked'])) print '<input type="hidden" name="type" value="'.$type.'">';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

// Fields title search
print '<tr class="liste_titre_filter">';
if (! empty($arrayfields['s.rowid']['checked']))
{
	print '<td class="liste_titre">';
	print '<input class="flat searchstring" type="text" name="search_id" size="1" value="'.dol_escape_htmltag($search_id).'">';
	print '</td>';
}
if (! empty($arrayfields['s.nom']['checked']))
{
	print '<td class="liste_titre">';
	if (! empty($search_nom_only) && empty($search_nom)) $search_nom=$search_nom_only;
	print '<input class="flat searchstring maxwidth50" type="text" name="search_nom" value="'.dol_escape_htmltag($search_nom).'">';
	print '</td>';
}
if (! empty($arrayfields['s.name_alias']['checked']))
{
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_alias" value="'.dol_escape_htmltag($search_alias).'">';
	print '</td>';
}
// Barcode
if (! empty($arrayfields['s.barcode']['checked']))
{
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_barcode" value="'.dol_escape_htmltag($search_barcode).'">';
	print '</td>';
}
// Customer code
if (! empty($arrayfields['s.code_client']['checked']))
{
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_customer_code" value="'.dol_escape_htmltag($search_customer_code).'">';
	print '</td>';
}
// Supplier code
if (! empty($arrayfields['s.code_fournisseur']['checked']))
{
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_supplier_code" value="'.dol_escape_htmltag($search_supplier_code).'">';
	print '</td>';
}
// Account Customer code
if (! empty($arrayfields['s.code_compta']['checked']))
{
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_account_customer_code" value="'.dol_escape_htmltag($search_account_customer_code).'">';
	print '</td>';
}
// Account Supplier code
if (! empty($arrayfields['s.code_compta_fournisseur']['checked']))
{
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth50" type="text" name="search_account_supplier_code" value="'.dol_escape_htmltag($search_account_supplier_code).'">';
	print '</td>';
}
// Town
if (! empty($arrayfields['s.town']['checked']))
{
	print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="6" type="text" name="search_town" value="'.dol_escape_htmltag($search_town).'">';
	print '</td>';
}
// Zip
if (! empty($arrayfields['s.zip']['checked']))
{
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_zip" value="'.dol_escape_htmltag($search_zip).'">';
	print '</td>';
}
// State
if (! empty($arrayfields['state.nom']['checked']))
{
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
	print '</td>';
}
// Region
if (! empty($arrayfields['region.nom']['checked']))
{
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_region" value="'.dol_escape_htmltag($search_region).'">';
	print '</td>';
}
// Country
if (! empty($arrayfields['country.code_iso']['checked']))
{
	print '<td class="liste_titre" align="center">';
	print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
	print '</td>';
}
// Company type
if (! empty($arrayfields['typent.code']['checked']))
{
	print '<td class="liste_titre maxwidthonsmartphone" align="center">';
	print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 0, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT)?'ASC':$conf->global->SOCIETE_SORT_ON_TYPEENT));
	print '</td>';
}
if (! empty($arrayfields['s.email']['checked']))
{
	// Email
	print '<td class="liste_titre">';
	print '<input class="flat searchemail maxwidth50" type="text" name="search_email" value="'.dol_escape_htmltag($search_email).'">';
	print '</td>';
}
if (! empty($arrayfields['s.phone']['checked']))
{
	// Phone
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_phone" value="'.dol_escape_htmltag($search_phone).'">';
	print '</td>';
}
if (! empty($arrayfields['s.fax']['checked']))
{
	// Fax
	print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="4" type="text" name="search_fax" value="'.dol_escape_htmltag($search_fax).'">';
	print '</td>';
}
if (! empty($arrayfields['s.url']['checked']))
{
	// Url
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_url" value="'.dol_escape_htmltag($search_url).'">';
	print '</td>';
}
if (! empty($arrayfields['s.siren']['checked']))
{
	// IdProf1
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_idprof1" value="'.dol_escape_htmltag($search_idprof1).'">';
	print '</td>';
}
if (! empty($arrayfields['s.siret']['checked']))
{
	// IdProf2
	print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="4" type="text" name="search_idprof2" value="'.dol_escape_htmltag($search_idprof2).'">';
	print '</td>';
}
if (! empty($arrayfields['s.ape']['checked']))
{
	// IdProf3
	print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="4" type="text" name="search_idprof3" value="'.dol_escape_htmltag($search_idprof3).'">';
	print '</td>';
}
if (! empty($arrayfields['s.idprof4']['checked']))
{
	// IdProf4
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_idprof4" value="'.dol_escape_htmltag($search_idprof4).'">';
	print '</td>';
}
if (! empty($arrayfields['s.idprof5']['checked']))
{
	// IdProf5
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_idprof5" value="'.dol_escape_htmltag($search_idprof5).'">';
	print '</td>';
}
if (! empty($arrayfields['s.idprof6']['checked']))
{
	// IdProf6
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_idprof6" value="'.dol_escape_htmltag($search_idprof6).'">';
	print '</td>';
}
if (! empty($arrayfields['s.tva_intra']['checked']))
{
	// Vat number
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_vat" value="'.dol_escape_htmltag($search_vat).'">';
	print '</td>';
}

// Type (customer/prospect/supplier)
if (! empty($arrayfields['customerorsupplier']['checked']))
{
	print '<td class="liste_titre maxwidthonsmartphone" align="middle">';
	if ($type != '') print '<input type="hidden" name="type" value="'.$type.'">';
	print '<select class="flat" name="search_type">';
	print '<option value="-1"'.($search_type==''?' selected':'').'>&nbsp;</option>';
	if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print '<option value="1,3"'.($search_type=='1,3'?' selected':'').'>'.$langs->trans('Customer').'</option>';
	if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print '<option value="2,3"'.($search_type=='2,3'?' selected':'').'>'.$langs->trans('Prospect').'</option>';
	//if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print '<option value="3"'.($search_type=='3'?' selected':'').'>'.$langs->trans('ProspectCustomer').'</option>';
	print '<option value="4"'.($search_type=='4'?' selected':'').'>'.$langs->trans('Supplier').'</option>';
	print '<option value="0"'.($search_type=='0'?' selected':'').'>'.$langs->trans('Others').'</option>';
	print '</select></td>';
}
if (! empty($arrayfields['s.fk_prospectlevel']['checked']))
{
	// Prospect level
	print '<td class="liste_titre" align="center">';
	$options_from = '<option value="">&nbsp;</option>';	 	// Generate in $options_from the list of each option sorted
	foreach ($tab_level as $tab_level_sortorder => $tab_level_label)
	{
		$options_from .= '<option value="'.$tab_level_sortorder.'"'.($search_level_from == $tab_level_sortorder ? ' selected':'').'>';
		$options_from .= $langs->trans($tab_level_label);
		$options_from .= '</option>';
	}
	array_reverse($tab_level, true);	// Reverse the list
	$options_to = '<option value="">&nbsp;</option>';		// Generate in $options_to the list of each option sorted in the reversed order
	foreach ($tab_level as $tab_level_sortorder => $tab_level_label)
	{
		$options_to .= '<option value="'.$tab_level_sortorder.'"'.($search_level_to == $tab_level_sortorder ? ' selected':'').'>';
		$options_to .= $langs->trans($tab_level_label);
		$options_to .= '</option>';
	}

	// Print these two select
	print $langs->trans("From").' <select class="flat" name="search_level_from">'.$options_from.'</select>';
	print ' ';
	print $langs->trans("to").' <select class="flat" name="search_level_to">'.$options_to.'</select>';

	print '</td>';
}

if (! empty($arrayfields['s.fk_stcomm']['checked']))
{
	// Prospect status
	print '<td class="liste_titre maxwidthonsmartphone" align="center">';
	$arraystcomm=array();
	foreach($prospectstatic->cacheprospectstatus as $key => $val)
	{
		$arraystcomm[$val['id']]=($langs->trans("StatusProspect".$val['id']) != "StatusProspect".$val['id'] ? $langs->trans("StatusProspect".$val['id']) : $val['label']);
	}
	print $form->selectarray('search_stcomm', $arraystcomm, $search_stcomm, -2);
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// nb
if (! empty($arrayfields['nb']['checked']))
{
	print '<td class="liste_titre">';
	print '</td>';
}
// last cons
if (! empty($arrayfields['lastcons']['checked']))
{
	print '<td class="liste_titre">';
	print '</td>';
}
// Date creation
if (! empty($arrayfields['s.datec']['checked']))
{
	print '<td class="liste_titre">';
	print '</td>';
}
// Date modification
if (! empty($arrayfields['s.tms']['checked']))
{
	print '<td class="liste_titre">';
	print '</td>';
}
// Status
if (! empty($arrayfields['s.status']['checked']))
{
	print '<td class="liste_titre maxwidthonsmartphone center">';
	print $form->selectarray('search_status', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')), $search_status, 1);
	print '</td>';
}
if (! empty($arrayfields['s.import_key']['checked']))
{
	print '<td class="liste_titre center">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_import_key" value="'.dol_escape_htmltag($search_import_key).'">';
	print '</td>';
}
// Action column
print '<td class="liste_titre" align="right">';
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';

print "</tr>\n";

print '<tr class="liste_titre">';
if (! empty($arrayfields['s.rowid']['checked']))                   print_liste_field_titre($arrayfields['s.rowid']['label'], $_SERVER["PHP_SELF"],"s.rowid","",$param,"",$sortfield,$sortorder);
if (! empty($arrayfields['s.nom']['checked']))                     print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
if (! empty($arrayfields['s.name_alias']['checked']))              print_liste_field_titre($arrayfields['s.name_alias']['label'], $_SERVER["PHP_SELF"],"s.name_alias","",$param,"",$sortfield,$sortorder);
if (! empty($arrayfields['s.barcode']['checked']))                 print_liste_field_titre($arrayfields['s.barcode']['label'], $_SERVER["PHP_SELF"], "s.barcode",$param,'','',$sortfield,$sortorder);
if (! empty($arrayfields['s.code_client']['checked']))             print_liste_field_titre($arrayfields['s.code_client']['label'],$_SERVER["PHP_SELF"],"s.code_client","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.code_fournisseur']['checked']))        print_liste_field_titre($arrayfields['s.code_fournisseur']['label'],$_SERVER["PHP_SELF"],"s.code_fournisseur","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.code_compta']['checked']))             print_liste_field_titre($arrayfields['s.code_compta']['label'],$_SERVER["PHP_SELF"],"s.code_compta","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.code_compta_fournisseur']['checked'])) print_liste_field_titre($arrayfields['s.code_compta_fournisseur']['label'],$_SERVER["PHP_SELF"],"s.code_compta_fournisseur","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.town']['checked']))           print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],"s.town","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.zip']['checked']))            print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],"s.zip","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['state.nom']['checked']))        print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['region.nom']['checked']))       print_liste_field_titre($arrayfields['region.nom']['label'],$_SERVER["PHP_SELF"],"region.nom","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['country.code_iso']['checked'])) print_liste_field_titre($arrayfields['country.code_iso']['label'],$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['typent.code']['checked']))      print_liste_field_titre($arrayfields['typent.code']['label'],$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['s.email']['checked']))          print_liste_field_titre($arrayfields['s.email']['label'],$_SERVER["PHP_SELF"],"s.email","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.phone']['checked']))          print_liste_field_titre($arrayfields['s.phone']['label'],$_SERVER["PHP_SELF"],"s.phone","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.fax']['checked'])) print_liste_field_titre($arrayfields['s.fax']['label'],$_SERVER["PHP_SELF"],"s.fax","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.url']['checked']))            print_liste_field_titre($arrayfields['s.url']['label'],$_SERVER["PHP_SELF"],"s.url","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.siren']['checked']))          print_liste_field_titre($form->textwithpicto($langs->trans("ProfId1Short"),$textprofid[1],1,0),$_SERVER["PHP_SELF"],"s.siren","",$param,'class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.siret']['checked']))          print_liste_field_titre($form->textwithpicto($langs->trans("ProfId2Short"),$textprofid[2],1,0),$_SERVER["PHP_SELF"],"s.siret","",$param,'class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.ape']['checked']))            print_liste_field_titre($form->textwithpicto($langs->trans("ProfId3Short"),$textprofid[3],1,0),$_SERVER["PHP_SELF"],"s.ape","",$param,'class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.idprof4']['checked']))        print_liste_field_titre($form->textwithpicto($langs->trans("ProfId4Short"),$textprofid[4],1,0),$_SERVER["PHP_SELF"],"s.idprof4","",$param,'class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.idprof5']['checked']))        print_liste_field_titre($form->textwithpicto($langs->trans("ProfId5Short"),$textprofid[4],1,0),$_SERVER["PHP_SELF"],"s.idprof5","",$param,'class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.idprof6']['checked']))        print_liste_field_titre($form->textwithpicto($langs->trans("ProfId6Short"),$textprofid[4],1,0),$_SERVER["PHP_SELF"],"s.idprof6","",$param,'class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.tva_intra']['checked']))      print_liste_field_titre($arrayfields['s.tva_intra']['label'],$_SERVER["PHP_SELF"],"s.tva_intra","",$param,'class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['customerorsupplier']['checked']))        print_liste_field_titre('');   // type of customer
if (! empty($arrayfields['s.fk_prospectlevel']['checked']))        print_liste_field_titre($arrayfields['s.fk_prospectlevel']['label'],$_SERVER["PHP_SELF"],"s.fk_prospectlevel","",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['s.fk_stcomm']['checked']))               print_liste_field_titre($arrayfields['s.fk_stcomm']['label'],$_SERVER["PHP_SELF"],"s.fk_stcomm","",$param,'align="center"',$sortfield,$sortorder);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (! empty($arrayfields['nb']['checked']))           print_liste_field_titre($arrayfields['nb']['label'],$_SERVER["PHP_SELF"],"nb","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['lastcons']['checked']))     print_liste_field_titre($arrayfields['lastcons']['label'],$_SERVER["PHP_SELF"],"lastcons","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.datec']['checked']))      print_liste_field_titre($arrayfields['s.datec']['label'],$_SERVER["PHP_SELF"],"s.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.tms']['checked']))        print_liste_field_titre($arrayfields['s.tms']['label'],$_SERVER["PHP_SELF"],"s.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.status']['checked']))     print_liste_field_titre($arrayfields['s.status']['label'],$_SERVER["PHP_SELF"],"s.status","",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['s.import_key']['checked'])) print_liste_field_titre($arrayfields['s.import_key']['label'],$_SERVER["PHP_SELF"],"s.import_key","",$param,'align="center"',$sortfield,$sortorder);
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
print "</tr>\n";


$i = 0;
$totalarray=array();
while ($i < min($num, $limit))
{
	$obj = $db->fetch_object($resql);

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

	$companystatic->code_compta_client=$obj->code_compta;
	$companystatic->code_compta_fournisseur=$obj->code_compta_fournisseur;

	$companystatic->fk_prospectlevel=$obj->fk_prospectlevel;

    print '<tr class="oddeven">';
    if (! empty($arrayfields['s.rowid']['checked']))
    {
    	print '<td class="tdoverflowmax50">';
    	print $obj->rowid;
    	print "</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    if (! empty($arrayfields['s.nom']['checked']))
    {
    	$savalias = $obj->name_alias;
    	if (! empty($arrayfields['s.name_alias']['checked'])) $companystatic->name_alias='';
    	print '<td class="tdoverflowmax200">';
    	print $companystatic->getNomUrl(1, '', 100, 0, 1);
    	print "</td>\n";
    	$companystatic->name_alias = $savalias;
    	if (! $i) $totalarray['nbfield']++;
    }
    if (! empty($arrayfields['s.name_alias']['checked']))
    {
    	print '<td class="tdoverflowmax200">';
    	print $companystatic->name_alias;
    	print "</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    // Barcode
    if (! empty($arrayfields['s.barcode']['checked']))
    {
    	print '<td>'.$obj->barcode.'</td>';
    	if (! $i) $totalarray['nbfield']++;
    }
    // Customer code
    if (! empty($arrayfields['s.code_client']['checked']))
    {
    	print '<td>'.$obj->code_client.'</td>';
    	if (! $i) $totalarray['nbfield']++;
    }
    // Supplier code
    if (! empty($arrayfields['s.code_fournisseur']['checked']))
    {
    	print '<td>'.$obj->code_fournisseur.'</td>';
    	if (! $i) $totalarray['nbfield']++;
    }
    // Account customer code
    if (! empty($arrayfields['s.code_compta']['checked']))
    {
    	print '<td>'.$obj->code_compta.'</td>';
    	if (! $i) $totalarray['nbfield']++;
    }
    // Account supplier code
    if (! empty($arrayfields['s.code_compta_fournisseur']['checked']))
    {
    	print '<td>'.$obj->code_compta_fournisseur.'</td>';
    	if (! $i) $totalarray['nbfield']++;
    }
    // Town
    if (! empty($arrayfields['s.town']['checked']))
    {
    	print "<td>".$obj->town."</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    // Zip
    if (! empty($arrayfields['s.zip']['checked']))
    {
    	print "<td>".$obj->zip."</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    // State
    if (! empty($arrayfields['state.nom']['checked']))
    {
    	print "<td>".$obj->state_name."</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    // Region
    if (! empty($arrayfields['region.nom']['checked']))
    {
    	print "<td>".$obj->region_name."</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    // Country
    if (! empty($arrayfields['country.code_iso']['checked']))
    {
    	print '<td align="center">';
    	$tmparray=getCountry($obj->fk_pays,'all');
    	print $tmparray['label'];
    	print '</td>';
    	if (! $i) $totalarray['nbfield']++;
    }
    // Type ent
    if (! empty($arrayfields['typent.code']['checked']))
    {
    	print '<td align="center">';
    	if (! is_array($typenArray) || count($typenArray)==0) $typenArray = $formcompany->typent_array(1);
    	print $typenArray[$obj->typent_code];
    	print '</td>';
    	if (! $i) $totalarray['nbfield']++;
    }
    if (! empty($arrayfields['s.email']['checked']))
    {
    	print "<td>".$obj->email."</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    if (! empty($arrayfields['s.phone']['checked']))
    {
    	print "<td>".dol_print_phone($obj->phone, $obj->country_code, 0, $obj->rowid)."</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    if (! empty($arrayfields['s.fax']['checked']))
    {
    	print "<td>".dol_print_phone($obj->fax, $obj->country_code, 0, $obj->rowid)."</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    if (! empty($arrayfields['s.url']['checked']))
    {
    	print "<td>".$obj->url."</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    if (! empty($arrayfields['s.siren']['checked']))
    {
    	print "<td>".$obj->idprof1."</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    if (! empty($arrayfields['s.siret']['checked']))
    {
    	print "<td>".$obj->idprof2."</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    if (! empty($arrayfields['s.ape']['checked']))
    {
    	print "<td>".$obj->idprof3."</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    if (! empty($arrayfields['s.idprof4']['checked']))
    {
    	print "<td>".$obj->idprof4."</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    if (! empty($arrayfields['s.idprof5']['checked']))
    {
    	print "<td>".$obj->idprof5."</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    if (! empty($arrayfields['s.idprof6']['checked']))
    {
    	print "<td>".$obj->idprof6."</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    if (! empty($arrayfields['s.tva_intra']['checked']))
    {
    	print "<td>".$obj->tva_intra."</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }
    // Type
    if (! empty($arrayfields['customerorsupplier']['checked']))
    {
    	print '<td align="center">';
    	$s='';
    	if (($obj->client==1 || $obj->client==3) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))
    	{
    		$companystatic->name=$langs->trans("Customer");
    		$companystatic->name_alias='';
    		$s.=$companystatic->getNomUrl(0,'customer',0,1);
    	}
    	if (($obj->client==2 || $obj->client==3) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
    	{
    		if ($s) $s.=" / ";
    		$companystatic->name=$langs->trans("Prospect");
    		$companystatic->name_alias='';
    		$s.=$companystatic->getNomUrl(0,'prospect',0,1);
    	}
    	if ((! empty($conf->fournisseur->enabled) || ! empty($conf->supplier_proposal->enabled)) && $obj->fournisseur)
    	{
    		if ($s) $s.=" / ";
    		$companystatic->name=$langs->trans("Supplier");
    		$companystatic->name_alias='';
    		$s.=$companystatic->getNomUrl(0,'supplier',0,1);
    	}
    	print $s;
    	print '</td>';
    	if (! $i) $totalarray['nbfield']++;
    }

    if (! empty($arrayfields['s.fk_prospectlevel']['checked']))
    {
    	// Prospect level
    	print '<td align="center">';
    	print $companystatic->getLibProspLevel();
    	print "</td>";
    	if (! $i) $totalarray['nbfield']++;
    }

    if (! empty($arrayfields['s.fk_stcomm']['checked']))
    {
    	// Prospect status
    	print '<td align="center" class="nowrap"><div class="nowrap">';
    	print '<div class="inline-block">'.$companystatic->LibProspCommStatut($obj->stcomm_id,2,$prospectstatic->cacheprospectstatus[$obj->stcomm_id]['label']);
    	print '</div> - <div class="inline-block">';
    	foreach($prospectstatic->cacheprospectstatus as $key => $val)
    	{
    		$titlealt='default';
    		if (! empty($val['code']) && ! in_array($val['code'], array('ST_NO', 'ST_NEVER', 'ST_TODO', 'ST_PEND', 'ST_DONE'))) $titlealt=$val['label'];
    		if ($obj->stcomm_id != $val['id']) print '<a class="pictosubstatus" href="'.$_SERVER["PHP_SELF"].'?stcommsocid='.$obj->rowid.'&stcomm='.$val['code'].'&action=setstcomm'.$param.($page?'&page='.urlencode($page):'').'">'.img_action($titlealt,$val['code']).'</a>';
    	}
    	print '</div></div></td>';
    	if (! $i) $totalarray['nbfield']++;
    }
    // Extra fields
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
    // Fields from hook
    $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
    $reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    // Nb
    print '<td align="right">'.$obj->nb.'</td>';
    // Last consultation
    print '<td align="center">';
    print dol_print_date($db->jdate($obj->lastcons),'day');
    print '</td>';
    // Date creation
    if (! empty($arrayfields['s.datec']['checked']))
    {
    	print '<td align="center" class="nowrap">';
    	print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
    	print '</td>';
    	if (! $i) $totalarray['nbfield']++;
    }
    // Date modification
    if (! empty($arrayfields['s.tms']['checked']))
    {
    	print '<td align="center" class="nowrap">';
    	print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
    	print '</td>';
    	if (! $i) $totalarray['nbfield']++;
    }
    // Status
    if (! empty($arrayfields['s.status']['checked']))
    {
    	print '<td align="center" class="nowrap">'.$companystatic->getLibStatut(3).'</td>';
    	if (! $i) $totalarray['nbfield']++;
    }
    if (! empty($arrayfields['s.import_key']['checked']))
    {
    	print '<td class="tdoverflowmax100">';
    	print $obj->import_key;
    	print "</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    }

    // Action column
    print '<td class="nowrap" align="center">';
    if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
    {
    	$selected=0;
    	if (in_array($obj->rowid, $arrayofselected)) $selected=1;
    	print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
    }
    print '</td>';
    if (! $i) $totalarray['nbfield']++;

    print '</tr>'."\n";


	$i++;
}

$db->free($resql);

$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>\n";
print "</div>";

print "</form>\n";

// End of page
llxFooter();
$db->close();
