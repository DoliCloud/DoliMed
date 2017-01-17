<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville         <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne                  <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin                <regis@dolibarr.fr>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)  <raphael.bertrand@resultic.fr>
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
 *      \file       htdocs/cabinetmed/admin/admin.php
 *      \ingroup    cabinetmed
 *      \brief      Page to setup module cabinetmed
 */

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res && file_exists("../../../../main.inc.php")) $res=@include("../../../../main.inc.php");
if (! $res && preg_match('/\/dolibarr([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../../dolibarr".$reg[1]."/htdocs/main.inc.php"); // Used on dev env only
if (! $res && preg_match('/\/dolimed([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../../dolibarr".$reg[1]."/htdocs/main.inc.php"); // Used on dev env only
if (! $res) die("Include of main fails");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
include_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
include_once("../lib/cabinetmed.lib.php");

$langs->load("admin");
$langs->load("companies");
$langs->load("bills");
$langs->load("other");
$langs->load("errors");
$langs->load("cabinetmed@cabinetmed");

if (!$user->admin)
accessforbidden();

$typeconst=array('yesno','texte','chaine');
$mesg='';
$action=GETPOST("action");



/*
 * Actions
 */

if ($action == 'update')
{
    $res=dolibarr_set_const($db, 'CABINETMED_RHEUMATOLOGY_ON', GETPOST("CABINETMED_RHEUMATOLOGY_ON"), 'texte', 0, '', $conf->entity);

   	$res=dolibarr_set_const($db, 'CABINETMED_HIDETHIRPARTIESMENU', GETPOST("CABINETMED_HIDETHIRPARTIESMENU"), 'texte', 0, '', $conf->entity);

   	$res=dolibarr_set_const($db, 'SOCIETE_DISABLE_CUSTOMERS', GETPOST("SOCIETE_DISABLE_CUSTOMERS"), 'texte', 1, '', $conf->entity);
	$res=dolibarr_set_const($db, 'SOCIETE_DISABLE_PROSPECTS', GETPOST("SOCIETE_DISABLE_PROSPECTS"), 'texte', 1, '', $conf->entity);

	$res=dolibarr_set_const($db, 'MAIN_SEARCHFORM_SOCIETE', GETPOST("MAIN_SEARCHFORM_SOCIETE")?0:1, 'texte', 0, '', $conf->entity);        // We also hide search of companies

    $res=dolibarr_set_const($db, 'CABINETMED_BANK_PATIENT_REQUIRED', GETPOST("CABINETMED_BANK_PATIENT_REQUIRED"), 'texte', 0, '', $conf->entity);

    if ($res == 1) $mesg=$langs->trans("RecordModifiedSuccessfully");
    else
    {
        dol_print_error($db);
    }
}


/*
 * View
 */

llxHeader("",$langs->trans("CabinetMedSetup"),'');


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("CabinetMedSetup"),$linkback,'title_setup');
print '<br>';

dol_htmloutput_mesg($mesg);


$h=0;
$head[$h][0] = $_SERVER["PHP_SELF"];
$head[$h][1] = $langs->trans("Setup");
$head[$h][2] = 'tabsetup';
$h++;

$head[$h][0] = 'cabinetmed_cons_extrafields.php';
$head[$h][1] = $langs->trans("ExtraFields").' ('.$langs->trans("Consultations").')';
$head[$h][2] = 'tabconsextrafields';
$h++;

$head[$h][0] = 'about.php';
$head[$h][1] = $langs->trans("About");
$head[$h][2] = 'tababout';
$h++;


print '<form name="cabinetmed" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="action" value="update">';

dol_fiche_head($head, 'tabsetup', '');

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("EnableSpecificFeaturesToRheumatology").'</td>';
print '<td>'.$form->selectyesno('CABINETMED_RHEUMATOLOGY_ON',$conf->global->CABINETMED_RHEUMATOLOGY_ON,1).'</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("HideCustomerFeatures").'</td>';
print '<td>'.$form->selectyesno('SOCIETE_DISABLE_CUSTOMERS',$conf->global->SOCIETE_DISABLE_CUSTOMERS,1).'</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("HideProspectFeatures").'</td>';
print '<td>'.$form->selectyesno('SOCIETE_DISABLE_PROSPECTS',$conf->global->SOCIETE_DISABLE_PROSPECTS,1).'</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("CABINETMED_BANK_PATIENT_REQUIRED").'</td>';
print '<td>'.$form->selectyesno('CABINETMED_BANK_PATIENT_REQUIRED',$conf->global->CABINETMED_BANK_PATIENT_REQUIRED,1).'</td>';
print '</tr>';

print '</table>';

dol_fiche_end();

print '<div class="center"><input type="submit" name="save" value="'.$langs->trans("Save").'" class="button"></div>';
print '</form>';


print '<br>';

// List of substitutions available
$arraylist=array();
complete_substitutions_array($arraylist,$langs);
//print join('<br>',array_keys($arraylist));

llxFooter();

$db->close();

