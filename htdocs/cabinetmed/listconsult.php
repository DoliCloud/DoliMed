<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/cabinetmed/listconsult.php
 *	\ingroup    cabinetmed
 *	\brief      List of consultation
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
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
require_once "./class/cabinetmedcons.class.php";
require_once "./lib/cabinetmed.lib.php";

$langs->load("companies");
$langs->load("customers");
$langs->load("suppliers");
$langs->load("commercial");
$langs->load("cabinetmed@cabinetmed");

$contextpage= GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'consultationlist';   // To manage different context of search

// Security check
$socid = GETPOST('socid', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '');

if (!$user->rights->cabinetmed->read) accessforbidden();

// Load variable for pagination
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC,DESC";
if (! $sortfield) $sortfield="c.datecons,c.rowid";

$search_nom  = GETPOST("search_nom", 'alpha');
$search_ville= GETPOST("search_ville", 'alpha');
$search_code = GETPOST("search_code", 'alpha');
$search_ref  = GETPOST("search_ref", 'alpha');

// Load sale and categ filters
$search_sale         = GETPOST("search_sale", "int");
$search_categ        = GETPOST("search_categ", "int");
$search_motifprinc   = GETPOST("search_motifprinc", "alpha");
$search_diaglesprinc = GETPOST("search_diaglesprinc", "alpha");
$search_contactid    = GETPOST("search_contactid", "int");

$object = new CabinetmedCons($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$now=dol_now();

$arrayfields=array(
	'c.rowid'=>array('label'=>"IdConsultShort", 'checked'=>1, 'enabled'=>1),
	's.nom'=>array('label'=>"Patient", 'checked'=>1, 'enabled'=>1),
	's.code_client'=>array('label'=>"PatientCode", 'checked'=>1, 'enabled'=>1),
	'c.datecons'=>array('label'=>"DateConsultationShort", 'checked'=>1, 'enabled'=>1),
	'c.datec'=>array('label'=>"CreatedBy", 'checked'=>1, 'enabled'=>1),
	'c.motifconsprinc'=>array('label'=>"MotifPrincipal", 'checked'=>1, 'enabled'=>1),
	'c.diaglesprinc'=>array('label'=>"DiagLesPrincipal", 'checked'=>1, 'enabled'=>1),
	'c.typepriseencharge'=>array('label'=>"Type prise en charge", 'checked'=>1, 'enabled'=>(empty($conf->global->CABINETMED_FRENCH_PRISEENCHARGE)?0:1)),
	'c.typevisit'=>array('label'=>"ConsultActe", 'checked'=>1, 'enabled'=>1),
	'amountpayment'=>array('label'=>"MontantPaiement", 'checked'=>1, 'enabled'=>1),
	'typepayment'=>array('label'=>"TypePaiement", 'checked'=>1, 'enabled'=>1),
);
// Extra fields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		if (! empty($extrafields->attributes[$object->table_element]['list'][$key]))
			$arrayfields["ef.".$key]=array('label'=>$extrafields->attributes[$object->table_element]['label'][$key], 'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key]<0)?0:1), 'position'=>$extrafields->attributes[$object->table_element]['pos'][$key], 'enabled'=>(abs((int) $extrafields->attributes[$object->table_element]['list'][$key])!=3 && $extrafields->attributes[$object->table_element]['perms'][$key]));
	}
}
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

$datecons=dol_mktime(0, 0, 0, GETPOST('consmonth', 'int'), GETPOST('consday', 'int'), GETPOST('consyear', 'int'));


/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Do we click on purge search criteria ?
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_categ='';
		$search_sale='';
		$socname="";
		$search_nom="";
		$search_ville="";
		$search_idprof1='';
		$search_idprof2='';
		$search_idprof3='';
		$search_idprof4='';
		$search_motifprinc='';
		$search_diaglesprinc='';
		$search_contactid='';
		$datecons='';
		$toselect='';
		$search_array_options=array();
	}

	// Mass actions
	$objectclass='Consultations';
	$objectlabel='Consultations';
	$permissiontoread = $user->rights->societe->lire;
	$permissiontodelete = $user->rights->societe->supprimer;
	$uploaddir = $conf->societe->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * view
 */

$form=new Form($db);
$formother=new FormOther($db);
$thirdpartystatic=new Societe($db);
$consultstatic = new CabinetmedCons($db);
$userstatic = new User($db);


//$help_url="EN:Module_MyObject|FR:Module_MyObject_FR|ES:Módulo_MyObject";
$help_url='';
$title = $langs->trans("ListOfConsultations");

$sql = "SELECT s.rowid, s.nom as name, s.client, s.town, st.libelle as stcomm, s.prefix_comm, s.code_client,";
$sql.= " s.datec, s.canvas,";
$sql.= " c.rowid as cid, c.datecons, c.typepriseencharge, c.typevisit, c.motifconsprinc, c.diaglesprinc, c.examenprescrit, c.traitementprescrit, c.fk_user, c.fk_user_creation,";
$sql.= " c.montant_cheque,";
$sql.= " c.montant_espece,";
$sql.= " c.montant_carte,";
$sql.= " c.montant_tiers,";
$sql.= " c.banque,";
// We'll need these fields in order to filter by categ
if ($search_categ) $sql .= " cs.fk_categorie, cs.fk_soc,";
// Add fields from extrafields
if (! empty($extrafields->attributes[$object->table_element]['label']))
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql.=($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key." as options_".$key.', ' : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.=preg_replace('/^,/', '', $hookmanager->resPrint);
$sql =preg_replace('/,\s*$/', '', $sql);
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,";
$sql.= " ".MAIN_DB_PREFIX."cabinetmed_cons as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."cabinetmed_cons_extrafields as ef ON ef.fk_object = c.rowid";
$sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st";
// We'll need this table joined to the select in order to filter by categ
if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_societe as cs";
$sql.= " WHERE s.fk_stcomm = st.id AND c.fk_soc = s.rowid";
$sql.= " AND s.client IN (1, 3)";
$sql.= ' AND s.entity IN ('.getEntity('societe', 1).')';
if ($datecons > 0) $sql.=" AND c.datecons = '".$db->idate($datecons)."'";
//if ($datecons > 0) $sql.= dolSqlDateFilter("c.datecons", GETPOST('consday', 'int'), GETPOST('consmonth', 'int'), GETPOST('consyear', 'int'));

if ($search_motifprinc) {
	$label= dol_getIdFromCode($db, $search_motifprinc, 'cabinetmed_motifcons', 'code', 'label');
	$sql.= " AND c.motifconsprinc LIKE '%".$db->escape($label)."%'";
}
if ($search_diaglesprinc) {
	$label= dol_getIdFromCode($db, $search_diaglesprinc, 'cabinetmed_diaglec', 'code', 'label');
	$sql.= " AND c.diaglesprinc LIKE '%".$db->escape($label)."%'";
}
if (!$user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid && empty($conf->global->MAIN_DISABLE_RESTRICTION_ON_THIRDPARTY_FOR_EXTERNAL)) $sql.= " AND s.rowid = ".$socid;
if ($search_ref)   $sql.= " AND c.rowid = ".$db->escape($search_ref);
if ($search_categ) $sql.= " AND s.rowid = cs.fk_soc";	// Join for the needed table to filter by categ
if ($search_nom)   $sql.= natural_search("s.nom", $search_nom);
if ($search_ville) $sql.= natural_search("s.town", $search_ville);
if ($search_code)  $sql.= natural_search("s.code_client", $search_code);
// Insert sale filter
if ($search_sale > 0) {
	$sql .= " AND c.fk_user = ".((int) $search_sale);
}
// Insert categ filter
if ($search_categ > 0) {
	$sql .= " AND cs.fk_categorie = ".((int) $search_categ);
}
if ($socname) {
	$sql.= natural_search("s.nom", $socname);
	$sortfield = "s.nom";
	$sortorder = "ASC";
}
//if ($search_contactid) $sql.=", ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc";
//if ($search_contactid) $sql.= " AND ec.element_id = s.rowid AND ec.fk_socpeople = ".$search_contactid." AND ec.fk_c_type_contact = tc.rowid AND tc.element='societe'";
if ($search_contactid) {
	$sql .= " AND s.rowid IN (SELECT ec.element_id FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc WHERE ec.fk_socpeople = ".$search_contactid." AND ec.fk_c_type_contact = tc.rowid AND tc.element='societe')";
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}
// if total resultset is smaller than limit, no need to do paging and restart select with limits.
if (is_numeric($nbtotalofrecords) && $limit > $nbtotalofrecords) {
	$num = $nbtotalofrecords;
} else {
	$sql.= $db->plimit($limit+1, $offset);

	$resql=$db->query($sql);
	if (! $resql) {
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}

$arrayofselected=is_array($toselect)?$toselect:array();

// List of mass actions available
$arrayofmassactions = array(
	//'validate'=>$langs->trans("Validate"),
	//'generate_doc'=>$langs->trans("ReGeneratePDF"),
	//'builddoc'=>$langs->trans("PDFMerge"),
	//'presend'=>$langs->trans("SendByMail"),
);


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);

if ($resql) {
	$param='';
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);

	if ($search_nom != '')          $param = "&search_nom=".urlencode($search_nom);
	if ($search_code != '')         $param.= "&search_code=".urlencode($search_code);
	if ($search_ville != '')        $param.= "&search_ville=".urlencode($search_ville);
	if ($search_categ > 0)          $param.= '&search_categ='.urlencode($search_categ);
	if ($search_sale > 0)	        $param.= '&search_sale='.urlencode($search_sale);
	if ($search_motifprinc != '')	$param.= '&search_motifprinc='.urlencode($search_motifprinc);
	if ($search_diaglesprinc != '')	$param.= '&search_diaglesprinc='.urlencode($search_diaglesprinc);
	if ($search_contactid != '')	$param.= '&search_contactid='.urlencode($search_contactid);

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'" name="formfilter" autocomplete="off">'."\n";
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_companies', 0, $newcardbutton, '', $limit);

	$i = 0;

	// Add code for pre mass action (confirmation or email presend form)
	$topicmail="Information";
	$modelmail="consultation";
	$objecttmp=new Societe($db);
	$trackid='cons'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	// Filter on categories
	$moreforfilter='';
	if (! empty($conf->categorie->enabled)) {
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$formother->select_categories(2, $search_categ, 'search_categ');
		$moreforfilter.='</div>';
	}

	// If the user can view prospects other than his'
	if ($user->rights->societe->client->voir || $socid) {
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('ConsultCreatedBy'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth300');
		$moreforfilter.='</div>';
	}
	// To add filter on contact
	$width="200";
	$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter.=$langs->trans('Correspondants'). ': ';
	$moreforfilter.=$form->selectcontacts(0, $search_contactid, 'search_contactid', 1, '', '', 1);
	$moreforfilter.='</div>';

	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if (! empty($moreforfilter)) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
	$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">';

	print '<tr class="liste_titre_filter">';
	if (! empty($arrayfields['c.rowid']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat maxwidth75" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	if (! empty($arrayfields['s.nom']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat maxwidth100" name="search_nom" value="'.dol_escape_htmltag($search_nom).'">';
		print '</td>';
	}
	if (! empty($arrayfields['s.code_client']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat maxwidth75" name="search_code" value="'.dol_escape_htmltag($search_code).'">';
		print '</td>';
	}
	// Date
	if (! empty($arrayfields['c.datecons']['checked'])) {
		print '<td class="liste_titre" align="center">';
		print $form->selectDate($datecons, 'cons', 0, 0, 1, '', 1, 0);
		print '</td>';
	}
	if (! empty($arrayfields['c.datec']['checked'])) {
		print '<td class="liste_titre"></td>';
	}
	if (! empty($arrayfields['c.motifconsprinc']['checked'])) {
		print '<td class="liste_titre">';
		$width='200';
		print listmotifcons(1, $width, 'search_motifprinc', $search_motifprinc);
		print '</td>';
	}
	if (! empty($arrayfields['c.diaglesprinc']['checked'])) {
		print '<td class="liste_titre">';
		$width='200';
		print listdiagles(1, $width, 'search_diaglesprinc', $search_diaglesprinc);
		print '</td>';
	}
	if (! empty($arrayfields['c.typepriseencharge']['checked'])) {
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}
	if (! empty($arrayfields['c.typevisit']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	if (! empty($arrayfields['amountpayment']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	if (! empty($arrayfields['typepayment']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListOption', $parameters, $object);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Action column
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto=$form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print '</tr>'."\n";

	// Fields title label
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	if (! empty($arrayfields['c.rowid']['checked']))                    print_liste_field_titre($arrayfields['c.rowid']['label'], $_SERVER["PHP_SELF"], "c.rowid", "", $param, "", $sortfield, $sortorder);
	if (! empty($arrayfields['s.nom']['checked']))                      print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", "", $param, "", $sortfield, $sortorder);
	if (! empty($arrayfields['s.code_client']['checked']))              print_liste_field_titre($arrayfields['s.code_client']['label'], $_SERVER["PHP_SELF"], "s.code_client", "", $param, "", $sortfield, $sortorder);
	if (! empty($arrayfields['c.datecons']['checked']))                 print_liste_field_titre($arrayfields['c.datecons']['label'], $_SERVER["PHP_SELF"], "c.datecons,c.rowid", "", $param, 'align="center"', $sortfield, $sortorder);
	if (! empty($arrayfields['c.datec']['checked']))                   	print_liste_field_titre($arrayfields['c.datec']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['c.motifconsprinc']['checked']))           print_liste_field_titre($arrayfields['c.motifconsprinc']['label'], $_SERVER["PHP_SELF"], "c.motifconsprinc", "", $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['c.diaglesprinc']['checked']))             print_liste_field_titre($arrayfields['c.diaglesprinc']['label'], $_SERVER["PHP_SELF"], "c.diaglesprinc", "", $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['c.typepriseencharge']['checked']))        print_liste_field_titre($arrayfields['c.typepriseencharge']['label'], $_SERVER['PHP_SELF'], 'c.typepriseencharge', '', $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['c.typevisit']['checked']))                print_liste_field_titre($arrayfields['c.typevisit']['label'], $_SERVER['PHP_SELF'], 'c.typevisit', '', $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['amountpayment']['checked']))              print_liste_field_titre($arrayfields['amountpayment']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'align="right"', $sortfield, $sortorder);
	if (! empty($arrayfields['typepayment']['checked']))                print_liste_field_titre($arrayfields['typepayment']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	$reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', $param, 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	print '</tr>'."\n";

	while ($i < min($num, $limit)) {
		$obj = $db->fetch_object($resql);

		print '<tr class="oddeven">';

		if (! empty($arrayfields['c.rowid']['checked'])) {
			print '<td>';
			$consultstatic->id=$obj->cid;
			$consultstatic->fk_soc=$obj->rowid;
			print $consultstatic->getNomUrl(1, '&amp;backtopage='.urlencode($_SERVER["PHP_SELF"]));
			print '</td>';
		}

		if (! empty($arrayfields['s.nom']['checked'])) {
			print '<td class="tdoverflowmax250" title="'.dol_escape_htmltag($obj->name).'">';
			$thirdpartystatic->id=$obj->rowid;
			$thirdpartystatic->name=$obj->name;
			$thirdpartystatic->client=$obj->client;
			$thirdpartystatic->canvas=$obj->canvas;
			print $thirdpartystatic->getNomUrl(1);
			print '</td>';
		}

		if (! empty($arrayfields['s.code_client']['checked'])) {
			print '<td class="nowraponall">'.$obj->code_client.'</td>';
		}

		if (! empty($arrayfields['c.datecons']['checked'])) {
			print '<td class="center">'.dol_print_date($db->jdate($obj->datecons), 'day').'</td>';
		}

		if (! empty($arrayfields['c.datec']['checked'])) {
			print '<td class="nowraponall">';
			$userstatic->fetch($obj->fk_user_creation);
			print $userstatic->getNomUrl(1);
			print '</td>';
		}

		if (! empty($arrayfields['c.motifconsprinc']['checked'])) {
			print '<td>'.$obj->motifconsprinc.'</td>';
		}

		if (! empty($arrayfields['c.diaglesprinc']['checked'])) {
			print '<td>';
			print dol_trunc($obj->diaglesprinc, 20);
			print '</td>';
		}

		if (! empty($arrayfields['c.typepriseencharge']['checked'])) {
			print '<td>';
			print $obj->typepriseencharge;
			print '</td>';
		}

		if (! empty($arrayfields['c.typevisit']['checked'])) {
			print '<td>';
			print $langs->trans($obj->typevisit);
			print '</td>';
		}

		if (! empty($arrayfields['amountpayment']['checked'])) {
			print '<td class="right">';
			$foundamount=0;
			if (price2num($obj->montant_cheque) > 0) {
				if ($foundamount) print '+';
				print price($obj->montant_cheque);
				$foundamount++;
			}
			if (price2num($obj->montant_espece) > 0) {
				if ($foundamount) print '+';
				print price($obj->montant_espece);
				$foundamount++;
			}
			if (price2num($obj->montant_carte) > 0) {
				if ($foundamount) print '+';
				print price($obj->montant_carte);
				$foundamount++;
			}
			if (price2num($obj->montant_tiers) > 0) {
				if ($foundamount) print '+';
				print price($obj->montant_tiers);
				$foundamount++;
			}
			print '</td>';
		}

		$bankid = array();

		if (! empty($arrayfields['typepayment']['checked'])) {
			print '<td>';
			$foundamount=0;
			if (price2num($obj->montant_cheque) > 0) {
				if ($foundamount) print ' + ';
				print $langs->trans("Cheque");
				if ($conf->banque->enabled && $bankid['CHQ']['account_id']) {
					$bank=new Account($db);
					$bank->fetch($bankid['CHQ']['account_id']);
					print '&nbsp;('.$bank->getNomUrl(0, 'transactions').')';
				}
				$foundamount++;
			}
			if (price2num($obj->montant_espece) > 0) {
				if ($foundamount) print ' + ';
				print $langs->trans("Cash");
				if ($conf->banque->enabled && $bankid['LIQ']['account_id']) {
					$bank=new Account($db);
					$bank->fetch($bankid['LIQ']['account_id']);
					print '&nbsp;('.$bank->getNomUrl(0, 'transactions').')';
				}
				$foundamount++;
			}
			if (price2num($obj->montant_carte) > 0) {
				if ($foundamount) print ' + ';
				print $langs->trans("CreditCard");
				if ($conf->banque->enabled && $bankid['CB']['account_id']) {
					$bank=new Account($db);
					$bank->fetch($bankid['CB']['account_id']);
					print '&nbsp;('.$bank->getNomUrl(0, 'transactions').')';
				}
				$foundamount++;
			}
			if (price2num($obj->montant_tiers) > 0) {
				if ($foundamount) print ' + ';
				print $langs->trans("PaymentTypeThirdParty");
				if ($conf->banque->enabled && $bankid['OTH']['account_id']) {
					$bank=new Account($db);
					$bank->fetch($bankid['OTH']['account_id']);
					print '&nbsp;('.$bank->getNomUrl(0, 'transactions').')';
				}
				$foundamount++;
			}
			print '</td>';
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		// Action column
		print '<td class="nowrap" align="center">';
		if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected=0;
			if (in_array($obj->rowid, $arrayofselected)) $selected=1;
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
		}
		print '</td>';
		if (! $i) $totalarray['nbfield']++;

		print "</tr>\n";
		$i++;
	}
	//print_barre_liste($langs->trans("ListOfCustomers"), $page, $_SERVER["PHP_SELF"],'',$sortfield,$sortorder,'',$num);
	print "</table>\n";
	print '</div>';

	print "</form>\n";

	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	$db->free($resql);
} else {
	dol_print_error($db);
}


// End of page
llxFooter();

$db->close();
