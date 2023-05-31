<?php
/* Copyright (C) 2001-2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006      Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010           Juanjo Menent        <jmenent@2byte.es>
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
 *   \file       htdocs/cabinetmed/exambio.php
 *   \brief      Tab for examens bio
 *   \ingroup    cabinetmed
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
include_once DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php";
include_once "./lib/cabinetmed.lib.php";
include_once "./class/patient.class.php";
include_once "./class/cabinetmedcons.class.php";
include_once "./class/cabinetmedexambio.class.php";

$action = GETPOST("action");
$optioncss = GETPOST('optioncss', 'aZ09');

$id=GETPOST('id', 'int');  // Id consultation

$langs->load("companies");
$langs->load("bills");
$langs->load("banks");
$langs->load("cabinetmed@cabinetmed");


$mesgarray=array();

// Load variable for pagination
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='t.dateexam';
if (! $sortorder) $sortorder='DESC';

$exambio = new CabinetmedExamBio($db);
$object = $exambio;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('thirdpartycard','exambiocard','globalcard'));

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'societe', $socid);

if (!$user->rights->cabinetmed->read) {
	accessforbidden();
}


/*
 * Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Delete exam
	if (GETPOST("action") == 'confirm_delete' && GETPOST("confirm") == 'yes' && $user->rights->societe->supprimer) {
		$exambio->fetch($id);
		$result = $exambio->delete($user);
		if ($result >= 0) {
			Header("Location: ".$_SERVER["PHP_SELF"].'?socid='.$socid);
			exit;
		} else {
			$langs->load("errors");
			$mesg=$langs->trans($exambio->error);
			$action='';
		}
	}

	// Add exam
	if ($action == 'add' || $action == 'update') {
		if (! GETPOST('cancel', 'alpha')) {
			$error=0;

			$dateexam=dol_mktime(0, 0, 0, $_POST["exammonth"], $_POST["examday"], $_POST["examyear"]);

			if ($action == 'update') {
				$result=$exambio->fetch($id);
				if ($result <= 0) {
					dol_print_error($db, $exambio);
					exit;
				}
			}

			$exambio->fk_soc=$_POST["socid"];
			$exambio->dateexam=$dateexam;
			$exambio->resultat=trim($_POST["resultat"]);
			$exambio->conclusion=trim($_POST["conclusion"]);
			$exambio->comment=trim($_POST["comment"]);
			$exambio->suivipr_ad=trim($_POST["suivipr_ad"]);
			$exambio->suivipr_ag=trim($_POST["suivipr_ag"]);
			$exambio->suivipr_vs=trim($_POST["suivipr_vs"]);
			$exambio->suivipr_eva=trim($_POST["suivipr_eva"]);
			$exambio->suivipr_err=trim($_POST["suivipr_err"]);
			$exambio->suivipr_das28=trim($_POST["suivipr_das28"]);
			$exambio->suivisa_fat=trim($_POST["suivisa_fat"]);
			$exambio->suivisa_dax=trim($_POST["suivisa_dax"]);
			$exambio->suivisa_dpe=trim($_POST["suivisa_dpe"]);
			$exambio->suivisa_dpa=trim($_POST["suivisa_dpa"]);
			$exambio->suivisa_rno=trim($_POST["suivisa_rno"]);
			$exambio->suivisa_dma=trim($_POST["suivisa_dma"]);
			$exambio->suivisa_basdai=trim($_POST["suivisa_basdai"]);

			if (empty($dateexam)) {
				$error++;
				$mesgarray[]=$langs->trans("ErrorFieldRequired", $langs->transnoentities("Date"));
			}

			$db->begin();

			if (! $error) {
				if ($action == 'add') {
					$result=$exambio->create($user);
				}
				if ($action == 'update') {
					$result=$exambio->update($user);
				}
				if ($result < 0) {
					$mesgarray[]=$exambio->error;
					$error++;
				}
			}

			if (! $error) {
				$db->commit();
				header("Location: ".$_SERVER["PHP_SELF"].'?socid='.$exambio->fk_soc);
				exit(0);
			} else {
				$db->rollback();
				$mesgarray[]=$exambio->error;
				if ($action == 'add')    $action='create';
				if ($action == 'update') $action='edit';
			}
		} else {
			$action='';
		}
	}
}


/*
 *	View
 */

$form = new Form($db);
$width="242";

llxHeader('', $langs->trans("ResultExamBio"));

if ($socid > 0) {
	$societe = new Patient($db);
	$societe->fetch($socid);

	$object = $societe;		// Use on test by module tabs declaration

	if ($id && ! $exambio->id) {
		$result=$exambio->fetch($id);
		if ($result < 0) dol_print_error($db, $exambio->error);
	}

	// Show tabs
	if (isModEnabled("notification")) {
		$langs->load("mails");
	}

	$head = societe_prepare_head($societe);
	if ((float) DOL_VERSION < 7) dol_fiche_head($head, 'tabexambio', $langs->trans("Patient"), 0, 'patient@cabinetmed');
	elseif ((float) DOL_VERSION < 15) dol_fiche_head($head, 'tabexambio', $langs->trans("Patient"), -1, 'patient@cabinetmed');
	else dol_fiche_head($head, 'tabexambio', $langs->trans("Patient"), -1, 'user-injured');

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
		if ($societe->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
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

	// Form to create
	if ($action == 'create' || $action == 'edit') {
		//dol_fiche_head();
		print '<br>';

		$x=1;
		$nboflines=4;

		print '<script type="text/javascript">
        var changed=false;
        jQuery(function() {
            jQuery(window).bind(\'beforeunload\', function(){
				/* alert(changed); */
            	if (changed) return \''.dol_escape_js($langs->transnoentitiesnoconv("WarningExitPageWithoutSaving")).'\';
			});
            jQuery(".flat").change(function () {
 				changed=true;
    		});
            jQuery(".ignorechange").click(function () {
 				changed=false;
    		});
        	jQuery("#addmotifprinc").click(function () {
                /*alert(jQuery("#listmotifcons option:selected" ).val());
                alert(jQuery("#listmotifcons option:selected" ).text());*/
                var t=jQuery("#listmotifcons").children( ":selected" ).text();
                if (t != "")
                {
                    jQuery("#motifconsprinc").val(t);
                    jQuery(".ui-autocomplete-input").val("");
                    jQuery(".ui-autocomplete-input").text("");
                    jQuery("#listmotifcons").get(0).selectedIndex = 0;
                }
            });
            jQuery("#addmotifsec").click(function () {
                var t=jQuery("#listmotifcons").children( ":selected" ).text();
                if (t != "")
                {
                    if (jQuery("#motifconsprinc").val() == t)
                    {
                        alert(\'Le motif "\'+t+\'" est deja en motif principal\');
                    }
                    else
                    {
                        jQuery("#motifconssec").append(t+"\n");
                        jQuery(".ui-autocomplete-input").val("");
                        jQuery(".ui-autocomplete-input").text("");
                        jQuery("#listmotifcons").get(0).selectedIndex = 0;
                    }
                }
            });
            jQuery("#adddiaglesprinc").click(function () {
                var t=jQuery("#listdiagles").children( ":selected" ).text();
                if (t != "")
                {
                    jQuery("#diaglesprinc").val(t);
                    jQuery(".ui-autocomplete-input").val("");
                    jQuery(".ui-autocomplete-input").text("");
                    jQuery("#listdiagles").get(0).selectedIndex = 0;
                }
            });
            jQuery("#adddiaglessec").click(function () {
                var t=jQuery("#listdiagles").children( ":selected" ).text();
                if (t != "")
                {
                    jQuery("#diaglessec").append(t+"\n");
                    jQuery(".ui-autocomplete-input").val("");
                    jQuery(".ui-autocomplete-input").text("");
                    jQuery("#listmotifcons").get(0).selectedIndex = 0;
                }
            });

            function init_das()
            {
                var ad=parseFloat(jQuery("#suivipr_ad").val());
                var ag=parseFloat(jQuery("#suivipr_ag").val());
                var vs=parseFloat(jQuery("#suivipr_vs").val());
                var eva=parseFloat(jQuery("#suivipr_eva").val());

                var t=-1;
                if (jQuery("#suivipr_ad").val() != \'\' &&
                jQuery("#suivipr_ag").val() != \'\' &&
                jQuery("#suivipr_vs").val() != \'\' &&
                jQuery("#suivipr_eva").val() != \'\')
                {
                    t=(0.56 * Math.sqrt(ad)) + (0.28 * Math.sqrt(ag)) + (0.7 * Math.log(vs)) + (0.014 * eva);
                    /* alert(t); */
                    t=Math.round(t*100)/100;
                }
                if (t >= 0)
                {
                    jQuery("#suivipr_das28_view").html(\'<b>\'+t+\'</b>\');
                    jQuery("#suivipr_das28").val(t);
                }
                else
                {
                    jQuery("#suivipr_das28_view").html(\''.dol_escape_js($langs->trans("NotCalculable")).'\');
                    jQuery("#suivipr_das28").val(\'\');
                }
            }
            init_das();
            jQuery(".suivipr").keyup(function () {
                init_das();
            });

            function init_basdai()
            {
                var fat=parseFloat(jQuery("#suivisa_fat").val());
                var dax=parseFloat(jQuery("#suivisa_dax").val());
                var dpe=parseFloat(jQuery("#suivisa_dpe").val());
                var dpa=parseFloat(jQuery("#suivisa_dpa").val());
                var rno=parseFloat(jQuery("#suivisa_rno").val());
                var dma=parseFloat(jQuery("#suivisa_dma").val());

                var u=-1;
                if (jQuery("#suivisa_fat").val() != \'\' &&
                jQuery("#suivisa_dax").val() != \'\' &&
                jQuery("#suivisa_dpe").val() != \'\' &&
                jQuery("#suivisa_dpa").val() != \'\' &&
                jQuery("#suivisa_rno").val() != \'\' &&
                jQuery("#suivisa_dma").val() != \'\')
                {
                    u=(fat + dax + dpe + dpa + (rno + dma)/2)/5;
                    /* alert(u); */
                    u=Math.round(u*100)/100;
                }

                /* jQuery("#suivisa_basdai").val(); */
                if (u >= 0)
                {
                    jQuery("#suivisa_basdai_view").html(\'<b>\'+u+\'</b> / 10\');
                    jQuery("#suivisa_basdai").val(u);
                }
                else
                {
                    jQuery("#suivisa_basdai_view").html(\''.dol_escape_js($langs->trans("NotCalculable")).'\');
                    jQuery("#suivisa_basdai").val(\'\');
                }
            }
            init_basdai();
            jQuery(".suivisa").keyup(function () {
                init_basdai();
            });

        });
        </script>';

		// DAS28 = 0.55 * (suivipr_ad + 0.284 * suivipr_ag + 0.33 * log10( suivipr_vs ) + 0.0142 * suivipr_eva

		print '
            <style>
            .ui-autocomplete-input { width: '.$width.'px; }
            </style>
            ';

		//print_fiche_titre($langs->trans("NewConsult"),'','');

		// General
		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		if ($action=='create') print '<input type="hidden" name="action" value="add">';
		if ($action=='edit')   print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="socid" value="'.$socid.'">';
		print '<input type="hidden" name="id" value="'.$id.'">';

		print '<fieldset id="fieldsetanalyse">';
		print '<legend>'.$langs->trans("Examen");
		if ($action=='edit' || $action=='update') {
			print ' - '.$langs->trans("Numero").': <strong>'.sprintf("%08d", $exambio->id).'</strong>';
		}
		if ($exambio->fk_user > 0) {
			$fuser=new User($db);
			$fuser->fetch($exambio->fk_user);
			print ' - '.$langs->trans("CreatedBy").': <strong>'.$fuser->getFullName($langs).'</strong>';
		}
		print '</legend>'."\n";

		print '<table class="notopnoleftnoright" width="100%">';
		print '<tr><td width="60%" class="fieldrequired">';
		print $langs->trans("Date").': ';
		print $form->selectDate($exambio->dateexam, 'exam');
		print '</td><td>';
		print '</td></tr>';

		print '</table>';
		//print '</fieldset>';

		//print '<br>';

		// Analyse
		//        print '<fieldset id="fieldsetanalyse">';
		//        print '<legend>'.$langs->trans("Diagnostiques et prescriptions").'</legend>'."\n";
		print '<div class="centpercent" style="margin-top: 5px; margin-bottom: 8px; border-bottom: 1px solid #eee;"></div>';

		//print '<table class="notopnoleftnoright" width="100%">';
		//print '<tr><td width="50%">';
		print '<div class="fichecenter"><div class="fichehalfleft">';

		print '<table class="notopnoleftnoright" width="100%">';
		print '<tr><td valign="top" width="160">';
		print $langs->trans("Result").':<br>';
		print '<textarea class="flat" name="resultat" id="resultat" cols="60" rows="'.ROWS_9.'">';
		print $exambio->resultat;
		print '</textarea>';
		print '</td>';
		print '</tr>';
		print '</table>';

		//print '</td><td class="tdtop">';
		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		print $langs->trans("Conclusion").':<br>';
		print '<textarea class="flat" name="conclusion" id="conclusion" cols="60" rows="'.ROWS_4.'">';
		print $exambio->conclusion;
		print '</textarea>';

		print '<br>';

		print $langs->trans("Comment").':<br>';
		print '<textarea class="flat" name="comment" id="comment" cols="60" rows="'.ROWS_4.'">';
		print $exambio->comment;
		print '</textarea>';

		//print '</td></tr>';
		//print '</table>';
		print '</div></div></div>';


		print '<div class="fichecenter" style="height:10px;"></div>';
		//print '<hr style="height:1px; color: #dddddd;">';

		if (! empty($conf->global->CABINETMED_RHEUMATOLOGY_ON)) {
			//print '<table width="100%">';
			//print '<tr><td width="50%" valign="top">';
			print '<div class="fichecenter"><div class="fichehalfleft">';

			print '<fieldset id="suivipr">';
			print '<legend>'.$langs->trans("SuiviPR").'</legend>';
			print '<table>';
			print '<tr><td width="90px">'.$langs->trans("AD").':</td><td><input autocomplete="off" class="flat suivipr" type="text" size="2" id="suivipr_ad" name="suivipr_ad" value="'.$exambio->suivipr_ad.'"></td></tr>';
			print '<tr><td>'.$langs->trans("AG").':</td><td><input autocomplete="off" class="flat suivipr" type="text" size="2" id="suivipr_ag" name="suivipr_ag" value="'.$exambio->suivipr_ag.'"></td></tr>';
			print '<tr><td>'.$langs->trans("EVA").':</td><td><input autocomplete="off" class="flat suivipr" type="text" size="2" id="suivipr_eva" name="suivipr_eva" value="'.$exambio->suivipr_eva.'"></td></tr>';
			print '<tr><td>'.$langs->trans("VS").':</td><td><input autocomplete="off" class="flat suivipr" type="text" size="2" id="suivipr_vs" name="suivipr_vs" value="'.$exambio->suivipr_vs.'"></td></tr>';
			print '<tr><td><b><font color="#884466">'.$langs->trans("DAS28").':</font></b></td><td>';
			print '<div id="suivipr_das28_view"></div>';
			print '<input type="hidden" id="suivipr_das28" name="suivipr_das28">';
			print '</td></tr>';
			print '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
			print '<tr><td>'.$langs->trans("ERRX").':</td><td><input type="text" size="2" id="suivipr_err" name="suivipr_err" value="'.$exambio->suivipr_err.'"></td></tr>';
			print '</table>';
			print '</fieldset>';

			//print '</td><td width="50%" valign="top">';
			print '</div><div class="fichehalfright"><div class="ficheaddleft">';

			print '<fieldset id="suivisa">';
			print '<legend>'.$langs->trans("SuiviSA").'</legend>';
			print '<table>';
			// 4 items de 0 à 10 -> Somme A
			print '<tr><td width="140px">'.$langs->trans("EVAFatigue").':</td><td><input autocomplete="off" class="flat suivisa" type="text" size="2" id="suivisa_fat" name="suivisa_fat" value="'.$exambio->suivisa_fat.'"> / 10</td></tr>';
			print '<tr><td>'.$langs->trans("EVADouleurAxiale").':</td><td><input autocomplete="off" class="flat suivisa" type="text" size="2" id="suivisa_dax" name="suivisa_dax" value="'.$exambio->suivisa_dax.'"> / 10</td></tr>';
			print '<tr><td>'.$langs->trans("EVADouleurPeriph").':</td><td><input autocomplete="off" class="flat suivisa" type="text" size="2" id="suivisa_dpe" name="suivisa_dpe" value="'.$exambio->suivisa_dpe.'"> / 10</td></tr>';
			print '<tr><td>'.$langs->trans("EVADouleurPalp").':</td><td><input autocomplete="off" class="flat suivisa" type="text" size="2" id="suivisa_dpa" name="suivisa_dpa" value="'.$exambio->suivisa_dpa.'"> / 10</td></tr>';
			// 2 items de 0 à 10 -> moyenne B
			print '<tr><td>'.$langs->trans("EVARaideurMat").':</td><td><input autocomplete="off" class="flat suivisa" type="text" size="2" id="suivisa_rno" name="suivisa_rno" value="'.$exambio->suivisa_rno.'"> / 10</td></tr>';
			print '<tr><td>'.$langs->trans("EVADerrMat").':</td><td><input autocomplete="off" class="flat suivisa" type="text" size="2" id="suivisa_dma" name="suivisa_dma" value="'.$exambio->suivisa_dma.'"> / 10</td></tr>';
			print '<tr><td><b><font color="#884466">'.$langs->trans("BASDAI").':</font><b></td><td>';
			print '<div id="suivisa_basdai_view"></div>';
			print '<input type="hidden" id="suivisa_basdai" name="suivisa_basdai">';
			print '</td></tr>';
			//print '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
			print '</table>';
			print '</fieldset>';
			// (A1+A2+A3+A4+(B1+B2/2))/5 -> C sur 10

			//print '</td></tr></table>';
			print '</div></div></div>';

			print '<br>';
		}

		dol_htmloutput_errors($mesg, $mesgarray);


		print '<div class="fichecenter" style="height:10px;"></div>';


		print '<center>';
		if ($action == 'edit') {
			print '<input type="submit" class="button ignorechange" name="update" value="'.$langs->trans("Save").'">';
		}
		if ($action == 'create') {
			print '<input type="submit" class="button ignorechange" name="add" value="'.$langs->trans("Add").'">';
		}
		print ' &nbsp; &nbsp; ';
		print '<input type="submit" class="button ignorechange" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</center>';


		print '</form>';
	}


	//dol_fiche_end();
}


/*
 * Boutons actions
 */
if ($action == '' || $action == 'delete') {
	print '<div class="tabsAction">';

	if ($user->rights->societe->creer) {
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?socid='.$societe->id.'&amp;action=create">'.$langs->trans("NewExamBio").'</a>';
	}

	print '</div>';
}


if ($action == '' || $action == 'delete') {
	// Confirm delete exam
	if (GETPOST("action") == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$socid.'&id='.GETPOST('id', 'int'), $langs->trans("DeleteAnExam"), $langs->trans("ConfirmDeleteExam"), "confirm_delete", '', 0, 1);
		print $formconfirm;
	}


	print_fiche_titre($langs->trans("ListOfExamBio"));

	$param='&socid='.$socid;

	$totalarray = array();
	$totalarray['nbfield'] = 0;

	print "\n";
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	//print_liste_field_titre($langs->trans('Num'),$_SERVER['PHP_SELF'],'t.rowid','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Date'), $_SERVER['PHP_SELF'], 't.dateexam', '', $param, 'align="left"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
	print_liste_field_titre($langs->trans("Result"));
	$totalarray['nbfield']++;
	if (! empty($conf->global->CABINETMED_RHEUMATOLOGY_ON)) {
		print_liste_field_titre($langs->trans("Das28"));
		$totalarray['nbfield']++;
		print_liste_field_titre($langs->trans("Basdai"));
		$totalarray['nbfield']++;
	}
	print '<td>&nbsp;</td>';
	print '</tr>';


	// List des consult
	$sql = "SELECT";
	$sql.= " t.rowid,";
	$sql.= " t.fk_soc,";
	$sql.= " t.dateexam,";
	$sql.= " t.resultat,";
	$sql.= " t.conclusion,";
	$sql.= " t.comment,";
	$sql.= " t.tms,";
	$sql.= " t.suivipr_das28,";
	$sql.= " t.suivisa_basdai";
	$sql.= " FROM ".MAIN_DB_PREFIX."cabinetmed_exambio as t";
	$sql.= " WHERE t.fk_soc = ".$socid;
	$sql.= " ORDER BY ".$sortfield." ".$sortorder.", t.rowid DESC";

	$resql=$db->query($sql);
	if ($resql) {
		$i = 0 ;
		$num = $db->num_rows($resql);
		$var=true;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$var=!$var;
			print '<tr class="oddeven">';
			//print '<td>';
			//print '<a href="'.$_SERVER["PHP_SELF"].'?socid='.$obj->fk_soc.'&id='.$obj->rowid.'&action=edit&token='.newToken().'">'.sprintf("%08d",$obj->rowid).'</a>';
			//print '</td>';
			print '<td>';
			print '<a href="'.$_SERVER["PHP_SELF"].'?socid='.$obj->fk_soc.'&id='.$obj->rowid.'&action=edit&token='.newToken().'">';
			print dol_print_date($db->jdate($obj->dateexam), 'day');
			print '</a>';
			print '</td>';
			print '<td>';
			print dol_trunc($obj->resultat, 40);
			print '</td>';
			if (! empty($conf->global->CABINETMED_RHEUMATOLOGY_ON)) {
				print '<td>';
				print $obj->suivipr_das28;
				print '</td>';
				print '<td>';
				print $obj->suivisa_basdai;
				print '</td>';
			}
			print '<td align="right">';
			print '<a class="reposition editfielda" href="'.$_SERVER["PHP_SELF"].'?socid='.$obj->fk_soc.'&id='.$obj->rowid.'&action=edit&token='.newToken().'">'.img_edit().'</a>';
			if ($user->rights->societe->supprimer) {
				print ' &nbsp; ';
				print '<a href="'.$_SERVER["PHP_SELF"].'?socid='.$obj->fk_soc.'&id='.$obj->rowid.'&action=delete&token='.newToken().'">'.img_delete().'</a>';
			}
			print '</td>';
			print '</tr>';
			$i++;
		}

		if ($num == 0) {
			print '<tr><td colspan="'.($totalarray['nbfield']).'"><span class="opacitymedium">'.$langs->trans("None").'</span><td></tr>';
		}
	} else {
		dol_print_error($db);
	}
}


llxFooter();

$db->close();
