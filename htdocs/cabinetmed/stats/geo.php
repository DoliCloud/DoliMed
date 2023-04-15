<?php
/* Copyright (c) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/adherents/stats/geo.php
 *      \ingroup    member
 *		\brief      Page with geographical statistics on members
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
dol_include_once("/cabinetmed/lib/cabinetmed.lib.php");
dol_include_once("/cabinetmed/class/cabinetmedcons.class.php");
dol_include_once("/cabinetmed/class/cabinetmedstats.class.php");

$graphwidth = 700;
$mapratio = 0.5;
$graphheight = round($graphwidth * $mapratio);

$mode=GETPOST('mode')?GETPOST('mode'):'';


// Security check
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

if (!isModEnabled('cabinetmed')) {
	accessforbidden();
}

$year = dol_print_date(dol_now(), '%Y');
$startyear = $year - (empty($conf->global->MAIN_STATS_GRAPHS_SHOW_N_YEARS) ? 2 : max(1, min(10, $conf->global->MAIN_STATS_GRAPHS_SHOW_N_YEARS)));
$endyear=$year;



/*
 * View
 */

$langs->load("cabinetmed@cabinetmed");

$arrayjs = array('https://www.google.com/jsapi');
if (!empty($conf->dol_use_jmobile)) {
	$arrayjs = array();
}

$title=$langs->trans("Statistics");

llxHeader('', $title, '', '', 0, 0, $arrayjs);

print_fiche_titre($title, '');

dol_mkdir($dir);

$countrytable="c_pays";
$fieldlabel='libelle';
include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
if (versioncompare(versiondolibarrarray(), array(3,7,-3)) >= 0) {
	$countrytable="c_country";
	$fieldlabel="label";
}

if ($mode) {
	// Define sql
	if ($mode == 'cabinetmedbycountry') {
		$label=$langs->trans("Country");
		$tab='statscountry';

		$data = array();
		$sql.="SELECT COUNT(d.rowid) as nb, MAX(d.datevalid) as lastdate, c.code, c.label";
		$sql.=" FROM ".MAIN_DB_PREFIX."adherent as d LEFT JOIN ".MAIN_DB_PREFIX.$countrytable." as c on d.country = c.rowid";
		$sql.=" WHERE d.statut = 1";
		$sql.=" GROUP BY c.label, c.code";
		//print $sql;
	}
	if ($mode == 'cabinetmedbystate') {
		$label=$langs->trans("Country");
		$label2=$langs->trans("State");
		$tab='statsstate';

		$data = array();
		$sql.="SELECT COUNT(d.rowid) as nb, MAX(d.datevalid) as lastdate, p.code, p.label, c.nom as label2";
		$sql.=" FROM ".MAIN_DB_PREFIX."cabinetmed_cons as d LEFT JOIN ".MAIN_DB_PREFIX."c_departements as c on d.fk_departement = c.rowid";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_regions as r on c.fk_region = r.code_region";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX.$countrytable." as p on d.country = p.rowid";
		$sql.=" WHERE d.statut = 1";
		//if (!$user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid && empty($conf->global->MAIN_DISABLE_RESTRICTION_ON_THIRDPARTY_FOR_EXTERNAL)) $sql.= " AND s.rowid = ".$socid;
		$sql.=" GROUP BY p.label, p.code, c.nom";
		//print $sql;
	}
	if ($mode == 'cabinetmedbytown') {
		$label=$langs->trans("Country");
		$label2=$langs->trans("Town");
		$tab='statstown';

		$data = array();
		$sql.="SELECT COUNT(d.rowid) as nb, MAX(d.datecons) as lastdate, p.code, p.label, s.town as label2";
		$sql.=" FROM ".MAIN_DB_PREFIX."cabinetmed_cons as d, ".MAIN_DB_PREFIX."societe as s";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX.$countrytable." as p on s.fk_pays = p.rowid";
		$sql.=" WHERE d.fk_soc = s.rowid";
		$sql.=' AND s.entity IN ('.getEntity('societe', 1).')';
		//if (!$user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid && empty($conf->global->MAIN_DISABLE_RESTRICTION_ON_THIRPARTY_FOR_EXTERNAL)) $sql.= " AND s.rowid = ".$socid;
		$sql.=" GROUP BY p.label, p.code, s.town";
		//print $sql;
	}

	$langsen=new Translate('', $conf);
	$langsen->setDefaultLang('en_US');
	$langsen->load("dict");
	//print $langsen->trans("Country"."FI");exit;

	// Define $data array
	dol_syslog("Count cabinetmed sql=".$sql);
	$resql=$db->query($sql);
	if ($resql) {
		$num=$db->num_rows($resql);
		$i=0;
		while ($i < $num) {
			$obj=$db->fetch_object($resql);
			if ($mode == 'cabinetmedbycountry') {
				$data[]=array('label'=>(($obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code)?$langs->trans("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
							'label_en'=>(($obj->code && $langsen->transnoentitiesnoconv("Country".$obj->code)!="Country".$obj->code)?$langsen->transnoentitiesnoconv("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
							'code'=>$obj->code,
							'nb'=>$obj->nb,
							'lastdate'=>$db->jdate($obj->lastdate)
				);
			}
			if ($mode == 'cabinetmedbystate') {
				$data[]=array('label'=>(($obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code)?$langs->trans("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
							'label_en'=>(($obj->code && $langsen->transnoentitiesnoconv("Country".$obj->code)!="Country".$obj->code)?$langsen->transnoentitiesnoconv("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
							'label2'=>($obj->label2?$obj->label2:$langs->trans("Unknown")),
							'nb'=>$obj->nb,
							'lastdate'=>$db->jdate($obj->lastdate)
				);
			}
			if ($mode == 'cabinetmedbytown') {
				$data[]=array(
					'label'=>(($obj->code && $langs->trans("Country".$obj->code) != "Country".$obj->code) ? img_picto('', DOL_URL_ROOT.'/theme/common/flags/'.strtolower($obj->code).'.png', '', 1).' '.$langs->trans("Country".$obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>')),
					'label_en'=>(($obj->code && $langsen->transnoentitiesnoconv("Country".$obj->code) != "Country".$obj->code) ? $langsen->transnoentitiesnoconv("Country".$obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>')),
					'label2'=>($obj->label2 ? $obj->label2 : '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>'),
					'code'=>$obj->code,
					'nb'=>$obj->nb,
					'lastdate'=>$db->jdate($obj->lastdate)
				);
			}

			$i++;
		}
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


$head = patient_stats_prepare_head(null);

dol_fiche_head($head, $tab, '', ((float) DOL_VERSION < 7.0 ? 0 : -1), '');


// Print title
if ($mode && ! count($data)) {
	print $langs->trans("NoRecordFound").'<br>';
	print '<br>';
} else {
	if ($mode == 'cabinetmedbycountry') print '<span class="opacitymedium">'.$langs->trans("ConsultsByCountryDesc").'</span><br>';
	elseif ($mode == 'cabinetmedbystate') print '<span class="opacitymedium">'.$langs->trans("ConsultsByStateDesc").'</span><br>';
	elseif ($mode == 'cabinetmedbytown') print '<span class="opacitymedium">'.$langs->trans("ConsultsByTownDesc").'</span><br>';
	else {
		print '<span class="opacitymedium">'.$langs->trans("ConsultsStatisticsDesc").'</span><br>';
		print '<br>';
		print '<a href="'.$_SERVER["PHP_SELF"].'?mode=cabinetmedbycountry">'.$langs->trans("ConsultsStatisticsByCountries").'</a><br>';
		print '<br>';
		print '<a href="'.$_SERVER["PHP_SELF"].'?mode=cabinetmedbystate">'.$langs->trans("ConsultsStatisticsByState").'</a><br>';
		print '<br>';
		print '<a href="'.$_SERVER["PHP_SELF"].'?mode=cabinetmedbytown">'.$langs->trans("ConsultsStatisticsByTown").'</a><br>';
	}
	print '<br>';
}


// Show graphics
if ($mode == 'cabinetmedbycountry') {
	// Assume we've already included the proper headers so just call our script inline
	print "\n<script type='text/javascript'>\n";
	print "google.load('visualization', '1', {'packages': ['geomap']});\n";
	print "google.setOnLoadCallback(drawMap);\n";
	print "function drawMap() {\n\tvar data = new google.visualization.DataTable();\n";

	// Get the total number of rows
	print "\tdata.addRows(".count($data).");\n";
	print "\tdata.addColumn('string', 'Country');\n";
	print "\tdata.addColumn('number', 'Number');\n";

	// loop and dump
	$i=0;
	foreach ($data as $val) {
		$valcountry = strtoupper($val['code']); // Should be ISO-3166 code (faster)
		if (empty($valcountry)) {
			$valcountry=ucfirst($val['label_en']);
		}
		if ($valcountry == 'Great Britain') {
			$valcountry = 'United Kingdom';
		}    // fix case of uk (when we use labels)
		print "\tdata.setValue(".$i.", 0, \"".$valcountry."\");\n";
		print "\tdata.setValue(".$i.", 1, ".$val['nb'].");\n";
		// Google's Geomap only supports up to 400 entries
		if ($i >= 400) {
			break;
		}
		$i++;
	}

	print "\tvar options = {};\n";
	print "\toptions['dataMode'] = 'regions';\n";
	print "\toptions['showZoomOut'] = false;\n";
	//print "\toptions['zoomOutLabel'] = '".dol_escape_js($langs->transnoentitiesnoconv("Numbers"))."';\n";
	print "\toptions['width'] = ".$graphwidth.";\n";
	print "\toptions['height'] = ".$graphheight.";\n";
	print "\toptions['colors'] = [0x".colorArrayToHex($theme_datacolor[1], 'BBBBBB').", 0x".colorArrayToHex($theme_datacolor[0], '444444')."];\n";
	print "\tvar container = document.getElementById('".$mode."');\n";
	print "\tvar geomap = new google.visualization.GeoMap(container);\n";
	print "\tgeomap.draw(data, options);\n";
	print "};\n";
	print "</script>\n";

	// print the div tag that will contain the map
	print '<div class="center" id="'.$mode.'"></div>'."\n";
}

if ($mode) {
	// Print array
	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	print '<table class="liste centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$label.'</td>';
	if (isset($label2)) {
		print '<td class="center">'.$label2.'</td>';
	}
	print '<td class="right">'.$langs->trans("NbConsult").'</td>';
	print '<td class="center">'.$langs->trans("LastConsultShort").'</td>';
	print '</tr>';

	foreach ($data as $val) {
		$year = isset($val['year']) ? $val['year'] : '';
		print '<tr class="oddeven">';
		print '<td>';
		/*if ($val['code']) {
			print picto_from_langcode($codelang);
		}*/
		print $val['label'];
		print '</td>';
		if ($label2) print '<td class="center">'.$val['label2'].'</td>';
		print '<td align="right">'.$val['nb'].'</td>';
		print '<td class="center">'.dol_print_date($val['lastdate'], 'dayhour').'</td>';
		print '</tr>';
	}

	print '</table>';
	print '</div>';
}


dol_fiche_end();

llxFooter();

$db->close();
