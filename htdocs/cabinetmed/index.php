<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/cabinetmed/index.php
 *  \ingroup    societe
 *  \brief      Home page for third parties area
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

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
dol_include_once('/cabinetmed/class/patient.class.php');

$langs->load("companies");

$socid = GETPOST('socid', 'int');
if ($user->socid) $socid=$user->socid;

// Security check
$result=restrictedArea($user, 'societe', 0, '', '', '', '');

$thirdparty_static = new Patient($db);


/*
 * View
 */

$transAreaType = $langs->trans("PatientsArea");
$helpurl='';

llxHeader("", $langs->trans("Patients"), $helpurl);

print_fiche_titre($transAreaType);


print '<div class="fichecenter"><div class="fichethirdleft">';


/*
 * Search area
 */

$rowspan=2;
print '<form method="post" action="'.DOL_URL_ROOT.'/societe/list.php">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<th colspan="3">'.$langs->trans("Search").'</th></tr>';
print '<tr class="oddeven nohover"><td>';
print $langs->trans("Name").':</td><td class="oddeven"><input class="flat" type="text" size="14" name="search_nom_only"></td>';
print '<td rowspan="'.$rowspan.'" class="oddeven nohover"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
print '<tr class="oddeven nohover"><td>';
print $langs->trans("Other").':</td><td><input class="flat" type="text" size="14" name="search_all"></td>';
//print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';
print '</tr>';

print "</table></form><br>";


/*
 * Statistics area
 */

$third = array();
$total=0;

$sql = "SELECT s.rowid, s.client, s.fournisseur";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ' WHERE s.entity IN ('.getEntity('societe', 1).')';
$sql.= " AND s.canvas='patient@cabinetmed'";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid && empty($conf->global->MAIN_DISABLE_RESTRICTION_ON_THIRDPARTY_FOR_EXTERNAL))	$sql.= " AND s.rowid = ".$socid;
//if (! $user->rights->fournisseur->lire) $sql.=" AND (s.fournisseur <> 1 OR s.client <> 0)";    // client=0, fournisseur=0 must be visible
//print $sql;
$resql = $db->query($sql);
if ($resql) {
	while ($objp = $db->fetch_object($resql)) {
		$found=0;
		if (isModEnabled('cabinetmed')) {
			$found=1;
			if (empty($third['patient'])) {
				$third['patient'] = 0;
			}
			$third['patient']++;
		}
		if ($found) $total++;
	}
} else dol_print_error($db);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").'</th></tr>';
$statstring = '';
if (isModEnabled('cabinetmed')) {
	$statstring.= '<tr class="oddeven">';
	$statstring.= '<td><a href="'.dol_buildpath('/cabinetmed/patients.php', 1).'">'.$langs->trans("Patients").'</a></td><td align="right">'.(isset($third['patient']) ? round($third['patient']) : 0).'</td>';
	$statstring.= "</tr>";
}
print $statstring;
//print $statstring2;
print '<tr class="liste_total"><td>'.$langs->trans("UniquePatients").'</td><td align="right">';
print $total;
print '</td></tr>';
print '</table>';


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Last patients modified
 */
$max=15;
$sql = "SELECT s.rowid, s.nom as name, s.client, s.fournisseur, s.canvas, s.tms as datem, s.status as status";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ' WHERE s.entity IN ('.getEntity('societe', 1).')';
$sql.= " AND s.canvas='patient@cabinetmed'";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)	$sql.= " AND s.rowid = ".$socid;
//if (! $user->rights->fournisseur->lire) $sql.=" AND (s.fournisseur <> 1 OR s.client <> 0)";
$sql.= $db->order("s.tms", "DESC");
$sql.= $db->plimit($max, 0);

//print $sql;
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$i = 0;

	if ($num > 0) {
		$transRecordedType = $langs->trans("LastModifiedPatients", $max);

		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre"><th colspan="2">'.$transRecordedType.'</td>';
		print '<th>&nbsp;</td>';
		print '<th align="right">'.$langs->trans('Status').'</td>';
		print '</tr>';

		while ($i < $num) {
			$objp = $db->fetch_object($resql);

			$thirdparty_static->id=$objp->rowid;
			$thirdparty_static->name=$objp->name;
			$thirdparty_static->client=$objp->client;
			$thirdparty_static->fournisseur=$objp->fournisseur;
			$thirdparty_static->datem=$db->jdate($objp->datem);
			$thirdparty_static->status=$objp->status;
			$thirdparty_static->canvas=$objp->canvas;

			print '<tr class="oddeven">';

			// Name
			print '<td class="nowrap">';
			print $thirdparty_static->getNomUrl(1);
			print "</td>\n";

			// Type
			print '<td class="center">';
			$thirdparty_static->name=$langs->trans("Patient");
			print $thirdparty_static->getNomUrl(0, 'patient');
			print '</td>';

			// Last modified date
			print '<td align="right">';
			print dol_print_date($thirdparty_static->datem, 'day');
			print "</td>";

			print '<td align="right" nowrap="nowrap">';
			print $thirdparty_static->getLibStatut(3);
			print "</td>";

			print "</tr>\n";

			$i++;
		}

		print "</table>";
	}

	$db->free($resql);
} else {
	dol_print_error($db);
}


print '</div></div></div>';


llxFooter();

$db->close();
