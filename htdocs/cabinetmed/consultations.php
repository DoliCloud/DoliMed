<?php
/* Copyright (C) 2004-2019      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   \file       htdocs/cabinetmed/consultations.php
 *   \brief      Tab for consultations
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

require_once DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/company.lib.php";
require_once DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php";
require_once DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
require_once "./class/patient.class.php";
require_once "./class/cabinetmedcons.class.php";
require_once "./lib/cabinetmed.lib.php";

$optioncss = GETPOST('optioncss', 'aZ09');

$action=GETPOST("action");
$id=GETPOST('id', 'int');  // Id consultation
$fk_agenda=GETPOST('fk_agenda', 'int');	// Id event if consultation is created from an event

$langs->load("companies");
$langs->load("bills");
$langs->load("banks");
$langs->load("cabinetmed@cabinetmed");

$contextpage= GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'consultationthirdpartylist';   // To manage different context of search

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'societe', $socid, '');

if (!$user->rights->cabinetmed->read) accessforbidden();

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
if (! $sortorder) $sortorder='DESC,DESC';
if (! $sortfield) $sortfield='t.datecons,t.rowid';

$soc = new Patient($db);
$object = new CabinetmedCons($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('thirdpartycard','consultationcard','globalcard'));

$now=dol_now();

$arrayfields=array(
	't.rowid'=>array('label'=>"IdConsultShort", 'checked'=>1, 'enabled'=>1),
	//'s.nom'=>array('label'=>"Patient", 'checked'=>1, 'enabled'=>1),
	//'s.code_client'=>array('label'=>"PatientCode", 'checked'=>1, 'enabled'=>1),
	't.datecons'=>array('label'=>"DateConsultationShort", 'checked'=>1, 'enabled'=>1),
	't.fk_user'=>array('label'=>"CreatedBy", 'checked'=>1, 'enabled'=>1),
	't.motifconsprinc'=>array('label'=>"MotifPrincipal", 'checked'=>1, 'enabled'=>1),
	't.diaglesprinc'=>array('label'=>"DiagLesPrincipal", 'checked'=>1, 'enabled'=>1),
	't.typepriseencharge'=>array('label'=>"Type prise en charge", 'checked'=>1, 'enabled'=>(empty($conf->global->CABINETMED_FRENCH_PRISEENCHARGE)?0:1)),
	't.typevisit'=>array('label'=>"ConsultActe", 'checked'=>1, 'enabled'=>1),
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

$search_array_options=array();

$arrayofmassactions = array();


/*
 * Actions
 */

$parameters=array('id'=>$socid, 'objcanvas'=>(empty($objcanvas) ? null : $objcanvas));
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

	// Delete consultation
	if (GETPOST("action") == 'confirm_delete' && GETPOST("confirm") == 'yes' && $user->rights->societe->supprimer) {
		$object->fetch($id);
		$result = $object->delete($user);
		if ($result >= 0) {
			header("Location: ".$_SERVER["PHP_SELF"].'?socid='.$socid);
			exit;
		} else {
			$langs->load("errors");
			$mesg=$langs->trans($object->error);
			$action='';
		}
	}

	// Add consultation
	if ($action == 'add' || $action == 'update') {
		if (! GETPOST('cancel', 'alpha')) {
			$error=0;

			$datecons=dol_mktime(0, 0, 0, GETPOST("consmonth", 'int'), GETPOST("consday", 'int'), GETPOST("consyear", 'int'));

			if ($action == 'update') {
				$result=$object->fetch($id);
				if ($result <= 0) {
					dol_print_error($db, $object);
					exit;
				}

				$result=$object->fetch_bankid();

				$oldconsult=dol_clone($object);

				$object->datecons=$datecons;
			} else {
				$object->datecons = $datecons;
				$object->fk_soc = GETPOST("socid", 'int');
			}

			$amount=array();
			if (! empty($_POST["montant_cheque"])) $amount['CHQ'] = price2num(GETPOST("montant_cheque"), 'MT', 2);
			if (! empty($_POST["montant_carte"]))  $amount['CB'] = price2num(GETPOST("montant_carte"), 'MT', 2);
			if (! empty($_POST["montant_espece"])) $amount['LIQ'] = price2num(GETPOST("montant_espece"), 'MT', 2);
			if (! empty($_POST["montant_tiers"]))  $amount['VIR'] = price2num(GETPOST("montant_tiers"), 'MT', 2);
			$banque=array();
			if (! empty($_POST["bankchequeto"]))   $banque['CHQ'] = GETPOST("bankchequeto");
			if (! empty($_POST["bankcarteto"]))    $banque['CB'] = GETPOST("bankcarteto");
			if (! empty($_POST["bankespeceto"]))   $banque['LIQ'] = GETPOST("bankespeceto");
			if (! empty($_POST["banktiersto"]))    $banque['VIR'] = GETPOST("banktiersto");  // Should be always empty

			unset($object->montant_carte);
			unset($object->montant_cheque);
			unset($object->montant_espece);
			unset($object->montant_tiers);
			if (GETPOST("montant_cheque") != '') $object->montant_cheque = price2num(GETPOST("montant_cheque"), 'MT', 2);
			if (GETPOST("montant_espece") != '') $object->montant_espece = price2num(GETPOST("montant_espece"), 'MT', 2);
			if (GETPOST("montant_carte") != '')  $object->montant_carte = price2num(GETPOST("montant_carte"), 'MT', 2);
			if (GETPOST("montant_tiers") != '')  $object->montant_tiers = price2num(GETPOST("montant_tiers"), 'MT', 2);

			$object->banque=trim(GETPOST("banque"));
			$object->num_cheque=trim(GETPOST("num_cheque"));
			$object->typepriseencharge=GETPOST("typepriseencharge");
			$object->motifconsprinc=GETPOST("motifconsprinc");
			$object->diaglesprinc=GETPOST("diaglesprinc");
			$object->motifconssec=GETPOST("motifconssec");
			$object->diaglessec=GETPOST("diaglessec");
			$object->hdm=trim(GETPOST("hdm"));
			$object->examenclinique=trim(GETPOST("examenclinique"));
			$object->examenprescrit=trim(GETPOST("examenprescrit"));
			$object->traitementprescrit=trim(GETPOST("traitementprescrit"));
			$object->comment=trim(GETPOST("comment"));
			$object->typevisit=GETPOST("typevisit");
			$object->infiltration=trim(GETPOST("infiltration"));
			$object->codageccam=trim(GETPOST("codageccam"));
			$object->fk_agenda=GETPOST("fk_agenda");

			//print "X".$_POST["montant_cheque"].'-'.$_POST["montant_espece"].'-'.$_POST["montant_carte"].'-'.$_POST["montant_tiers"]."Z";
			$nbnotempty=0;
			if (trim($_POST["montant_cheque"])!='') $nbnotempty++;
			if (trim($_POST["montant_espece"])!='') $nbnotempty++;
			if (trim($_POST["montant_carte"])!='')  $nbnotempty++;
			if (trim($_POST["montant_tiers"])!='')  $nbnotempty++;
			if ($nbnotempty==0) {
				$error++;
				$mesgarray[]=$langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount"));
			}
			if ((trim($_POST["montant_cheque"])!='' && price2num(GETPOST("montant_cheque")) == 0)
			|| (trim($_POST["montant_espece"])!='' && price2num(GETPOST("montant_espece")) == 0)
			|| (trim($_POST["montant_carte"])!='' && price2num(GETPOST("montant_carte")) == 0)) {
				$error++;
				$mesgarray[]=$langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount"));
			}
			// If bank module enabled, bank account is required.
			if (isModEnabled("banque")) {
				if (! empty($_POST["montant_cheque"]) && (! GETPOST('bankchequeto') || GETPOST('bankchequeto') < 0)) { $error++; $mesgarray[]=$langs->trans("ErrorFieldRequired", $langs->transnoentities("RecBank")); }
				if (! empty($_POST["montant_carte"])  && (! GETPOST('bankcarteto')  || GETPOST('bankcarteto') < 0)) { $error++; $mesgarray[]=$langs->trans("ErrorFieldRequired", $langs->transnoentities("RecBank")); }
				if (! empty($_POST["montant_espece"]) && (! GETPOST('bankespeceto') || GETPOST('bankespeceto') < 0)) { $error++; $mesgarray[]=$langs->trans("ErrorFieldRequired", $langs->transnoentities("RecBank")); }
			}
			// Other
			if (trim(GETPOST("montant_cheque")) != '' && ! empty($conf->global->CABINETMED_BANK_PATIENT_REQUIRED) && ! trim(GETPOST("banque"))) {
				$error++;
				$mesgarray[]=$langs->trans("ErrorFieldRequired", $langs->transnoentities("ChequeBank"));
			}
			if (empty($object->typevisit)) {
				$error++;
				$mesgarray[]=$langs->trans("ErrorFieldRequired", $langs->transnoentities("TypeVisite"));
			}
			if (empty($datecons)) {
				$error++;
				$mesgarray[]=$langs->trans("ErrorFieldRequired", $langs->transnoentities("Date"));
			}
			if (empty($object->motifconsprinc)) {
				$error++;
				$mesgarray[]=$langs->trans("ErrorFieldRequired", $langs->transnoentities("MotifConsultation"));
			}
			if (empty($object->diaglesprinc) && empty($conf->global->DIAGNOSTIC_IS_NOT_MANDATORY)) {
				$error++;
				$mesgarray[]=$langs->trans("ErrorFieldRequired", $langs->transnoentities("DiagnostiqueLesionnel"));
			}

			// Fill array 'array_options' with data from add form
			if (! $error) {
				$ret = $extrafields->setOptionalsFromPost(null, $object);
				if ($ret < 0) $error++;
			}


			$db->begin();

			if (! $error) {
				if ($action == 'add') {
					$result=$object->create($user);
					if ($result < 0) {
						$mesg=$object->error;
						$error++;
					}

					if (! $error) {
						$soc->fetch($object->fk_soc);

						if (GETPOST('generateinvoice')) {
							include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
							include_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

							$invoice = new Facture($db);
							$invoice->socid = $soc->id;
							$invoice->fk_soc = $soc->id;
							$invoice->date = $datecons;

							$vattouse = GETPOST('vat');

							$product = new Product($db);
							$product->type = Product::TYPE_SERVICE;

							if (GETPOST('prodid') > 0) {      // TODO
								$product->fetch(GETPOST('prodid'));
								if (GETPOST('vat') == '') {
									$vattouse = get_default_tva(societe_vendeuse, societe_acheteuse, $product);
								}
							}

							$objectamount = $object->montant_cheque + $object->montant_carte + $object->montant_espece + $object->montant_tiers;

							$invoice->linked_objects['cabinetmed_cabinetmedcons']=$object->id;

							$result = $invoice->create($user);
							if ($result > 0) {
								$result = $invoice->addline(
									$langs->trans('Consultation'),
									$objectamount,		 	// subprice
									1, 						// quantity
									$vattouse,     // vat rate
									0,                      // localtax1_tx
									0, 						// localtax2_tx
									$product->id, 	// fk_product
									0, 						// remise_percent
									0, 						// date_start
									0, 						// date_end
									0,
									0, // info_bits
									0,
									'HT',
									0,
									$product->type, 						// product_type
									1,
									$lines[$i]->special_code,
									$object->origin,
									$object->id,
									0,
									0,
									0,
									''
									);

								$result = $invoice->validate($user);
								if ($result > 0) {
									// Enter payment
									foreach (array('CHQ','CB','LIQ','VIR') as $key) {
										$tmpamount=0;
										if ($key == 'CHQ') $tmpamount = $object->montant_cheque;
										if ($key == 'CB')  $tmpamount = $object->montant_carte;
										if ($key == 'LIQ') $tmpamount = $object->montant_espece;
										if ($key == 'VIR') $tmpamount = $object->montant_tiers;
										if (! ($tmpamount > 0)) continue;

										// Creation of payment line
										$paiement = new Paiement($db);
										$paiement->datepaye     = $datecons;
										$paiement->amounts      = array($invoice->id => $tmpamount);    // Array with all payments dispatching
										$paiement->paiementid   = dol_getIdFromCode($db, $key, 'c_paiement');
										$paiement->num_paiement = $object->num_cheque;
										$paiement->note         = '';

										if (! $error) {
											$paiement_id = $paiement->create($user, 1);
											if ($paiement_id < 0) {
												setEventMessages($paiement->error, $paiement->errors, 'errors');
												$error++;
											}
										}

										// Create entry into bank account for the payment
										if (! $error) {
											if (isModEnabled("banque") && isset($banque[$key]) && $banque[$key] > 0) {
												$label='(CustomerInvoicePayment)';
												if ((float) DOL_VERSION >= 13) {
													$accountancycode = empty($conf->global->CABINETMED_ACCOUNTANCY_CODE_FOR_CONSULTATION) ? '' : $conf->global->CABINETMED_ACCOUNTANCY_CODE_FOR_CONSULTATION;
													$result=$paiement->addPaymentToBank($user, 'payment', $label, $banque[$key], $soc->name, $object->banque, $accountancycode);
												} else {
													$result=$paiement->addPaymentToBank($user, 'payment', $label, $banque[$key], $soc->name, $object->banque);
												}
												if ($result < 0) {
													setEventMessages($paiement->error, $paiement->errors, 'errors');
													$error++;
												}
											}
										}
									}
								}
							} else {
								$error++;
								$object->error = $invoice->error;
							}
						} else {
							// Create direct entry into bank account
							foreach (array('CHQ','CB','LIQ','VIR') as $key) {
								if (isModEnabled("banque") && isset($banque[$key]) && $banque[$key] > 0) {
									//var_dump($key.' '.$banque[$key].' '.$soc->name.' '.$object->banque);exit;
									$bankaccount=new Account($db);
									$result=$bankaccount->fetch($banque[$key]);
									if ($result < 0) dol_print_error($db, $bankaccount->error);
									if ($key == 'CHQ') $lineid=$bankaccount->addline($datecons, $key, $langs->trans("CustomerInvoicePayment"), $amount[$key], $object->num_cheque, '', $user, $soc->name, $object->banque);
									else $lineid=$bankaccount->addline($datecons, $key, $langs->trans("CustomerInvoicePayment"), $amount[$key], '', '', $user, $soc->name, '');
									if ($lineid <= 0) {
										$error++;
										$object->error=$bankaccount->error;
									}
									if (! $error) {
										$result1=$bankaccount->add_url_line($lineid, $object->id, dol_buildpath('/cabinetmed/consultations.php', 1).'?action=edit&token='.newToken().'&socid='.$object->fk_soc.'&id=', 'Consultation', 'consultation');
										$result2=$bankaccount->add_url_line($lineid, $object->fk_soc, '', $soc->name, 'company');
										if ($result1 <= 0 || $result2 <= 0) {
											$error++;
										}
									}
								}
							}
						}
					}
				}
				if ($action == 'update') {
					$result=$soc->fetch($object->fk_soc);

					$result=$object->update($user);
					if ($result < 0) {
						$mesg=$object->error;
						$error++;
					}

					if (! $error) {
						foreach (array('CHQ','CB','LIQ','THIRD') as $key) {
							$bankmodified=0;

							if ($key == 'CHQ' &&
							(price2num($oldconsult->montant_cheque, 'MT') != price2num($_POST["montant_cheque"], 'MT') ||
							$oldconsult->banque != trim($_POST["banque"]) ||
							$oldconsult->num_cheque != trim($_POST["num_cheque"]) ||
							$oldconsult->bank['CHQ']['account_id'] != $_POST["bankchequeto"])) $bankmodified=1;
							if ($key == 'CB' &&
							(price2num($oldconsult->montant_carte, 'MT') != price2num($_POST["montant_carte"], 'MT') ||
							$oldconsult->bank['CB']['account_id'] != $_POST["bankcarteto"])) $bankmodified=1;
							if ($key == 'LIQ' &&
							(price2num($oldconsult->montant_espece, 'MT') != price2num($_POST["montant_espece"], 'MT') ||
							$oldconsult->bank['LIQ']['account_id'] != $_POST["bankespeceto"])) $bankmodified=1;
							if ($key == 'VIR' &&
							(price2num($oldconsult->montant_tiers, 'MT') != price2num($_POST["montant_tiers"], 'MT'))) $bankmodified=1;

							if (isModEnabled("banque") && $bankmodified) {
								// TODO Check if cheque is already into a receipt
								if ($key == 'CHQ' && 1 == 1) {
								}
								// TODO Check if bank record is already conciliated
							}

							//print 'xx '.$key.' => '.$bankmodified;exit;
							//if ($key == 'CB') { var_dump($oldconsult->bank);exit; }

							// If we changed bank informations for this key
							if ($bankmodified) {
								// If consult has a bank id for this key, we remove it
								if ($object->bank[$key]['bank_id'] && ! $object->bank[$key]['rappro']) {
									$bankaccountline=new AccountLine($db);
									$result=$bankaccountline->fetch($object->bank[$key]['bank_id']);
									$bank_chq=$bankaccountline->bank_chq;
									$fk_bordereau=$bankaccountline->fk_bordereau;
									$bankaccountline->delete($user);
								}

								if (isModEnabled("banque") && isset($banque[$key]) && $banque[$key] > 0) {
									$bankaccount=new Account($db);
									$result=$bankaccount->fetch($banque[$key]);
									if ($result < 0) dol_print_error($db, $bankaccount->error);
									if ($key == 'CHQ') $lineid=$bankaccount->addline($object->datecons, $key, $langs->trans("CustomerInvoicePayment"), $amount[$key], $object->num_cheque, '', $user, $soc->name, $object->banque);
									else $lineid=$bankaccount->addline($object->datecons, $key, $langs->trans("CustomerInvoicePayment"), $amount[$key], '', '', $user, $soc->name, '');
									$result1=$bankaccount->add_url_line($lineid, $object->id, dol_buildpath('/cabinetmed/consultations.php', 1).'?action=edit&token='.newToken().'&socid='.$object->fk_soc.'&id=', 'Consultation', 'consultation');
									$result2=$bankaccount->add_url_line($lineid, $object->fk_soc, '', $soc->name, 'company');
									if ($lineid <= 0 || $result1 <= 0 || $result2 <= 0) {
										$error++;
									}
								}
							}
						}
					} else {
						$error++;
					}
				}
			}

			if (! $error) {
				$db->commit();
				header("Location: ".$_SERVER["PHP_SELF"].'?socid='.$object->fk_soc);
				exit(0);
			} else {
				$db->rollback();
				$mesgarray[]=$object->error;
				if ($action == 'add')    $action='create';
				if ($action == 'update') $action='edit';
			}
		} else {
			if (GETPOST('backtopage', 'alpha')) {
				header("Location: ".GETPOST('backtopage', 'alpha'));
				exit(0);
			}
			$action='';
		}
	}
}


/*
 * view
 */

$form=new Form($db);
$formother=new FormOther($db);
$thirdpartystatic=new Societe($db);
$objectstatic = new CabinetmedCons($db);
$fuser = new User($db);

//$help_url="EN:Module_MyObject|FR:Module_MyObject_FR|ES:Módulo_MyObject";
$help_url='';
$title = $langs->trans("Consultation");

$width="300";
if ($conf->browser->layout == 'phone') $width = '150';

llxHeader('', $title);

if (! ($socid > 0)) {
	print '<br><br>';
	print $langs->trans("ToCreateAConsultationGoOnPatientRecord");
	print '<br><br>';
} else {
	$result=$soc->fetch($socid);
	if ($result < 0) { dol_print_error('', $soc->error); }

	if ($id && ! ($object->id > 0)) {
		$result=$object->fetch($id);
		if ($result < 0) dol_print_error($db, $object->error);

		$result=$object->fetch_bankid();
		if ($result < 0) dol_print_error($db, $object->error);
	}

	/*
	 * Affichage onglets
	 */
	if (isModEnabled("notification")) $langs->load("mails");

	$savobject = $object;
	$object = $soc;

	$head = societe_prepare_head($soc);

	$object = $savobject;

	// General
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	if ($action=='create') print '<input type="hidden" name="action" value="add">';
	if ($action=='edit')   print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="socid" value="'.$socid.'">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="backtopage" value="'.GETPOST('backtopage', 'alpha').'">';

	if ((float) DOL_VERSION < 7) dol_fiche_head($head, 'tabconsultations', $langs->trans("Patient"), 0, 'patient@cabinetmed');
	elseif ((float) DOL_VERSION < 15) dol_fiche_head($head, 'tabconsultations', $langs->trans("Patient"), -1, 'patient@cabinetmed');
	else dol_fiche_head($head, 'tabconsultations', $langs->trans("Patient"), -1, 'user-injured');

	$linkback = '<a href="'.dol_buildpath('/cabinetmed/patients.php', 1).'">'.$langs->trans("BackToList").'</a>';
	dol_banner_tab($soc, 'socid', $linkback, ($user->socid?0:1), 'rowid', 'nom');

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield" width="100%">';

	if ($soc->client) {
		print '<tr><td class="titlefield">';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $soc->code_client;
		if ($soc->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
	}

	if ($soc->fournisseur) {
		print '<tr><td class="titlefield">';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $soc->code_fournisseur;
		if ($soc->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
	}

	print "</table>";

	print '</div>';

	dol_fiche_end();

	// Form to create
	if ($action == 'create' || $action == 'edit') {
		//dol_fiche_head();
		print '<br>';

		$x=1;
		$nboflines=4;

		print '<script type="text/javascript" language="javascript">
        var changed=false;
        function init_montant_cheque()
        {
	        if (jQuery("#idmontant_cheque").val() != "")
	        {
		        jQuery("#banque").removeAttr(\'disabled\');
		        jQuery("#selectbankchequeto").removeAttr(\'disabled\');
	    	    jQuery("#idnum_cheque").removeAttr(\'disabled\');
    	    }
	    	else
	    	{
	    		jQuery("#banque").attr(\'disabled\', \'disabled\');
	    		jQuery("#selectbankchequeto").attr(\'disabled\', \'disabled\');
	    		jQuery("#idnum_cheque").attr(\'disabled\', \'disabled\');
    		}
			/* jQuery("#selectbankchequeto").selectmenu("refresh"); */
    	}
        function init_montant_carte()
        {
            if (jQuery("#idmontant_carte").val() != "")
            {
                jQuery("#selectbankcarteto").removeAttr(\'disabled\');
            }
            else
            {
                jQuery("#selectbankcarteto").attr(\'disabled\', \'disabled\');
            }
			/* jQuery("#selectbankcarteto").selectmenu("refresh"); */
    	}
        function init_montant_espece()
        {
            if (jQuery("#idmontant_espece").val() != "")
            {
                jQuery("#selectbankespeceto").removeAttr(\'disabled\');
            }
            else
            {
                jQuery("#selectbankespeceto").attr(\'disabled\', \'disabled\');
            }
			/* jQuery("#selectbankespeceto").selectmenu("refresh"); */
    	}
        jQuery(document).ready(function()
        {
           	init_montant_cheque();
           	init_montant_carte();
           	init_montant_espece();

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
    		jQuery("#cs").click(function () {
                jQuery("#idcodageccam").attr(\'disabled\', \'disabled\');
            });
            jQuery("#c2").click(function () {
                jQuery("#idcodageccam").attr(\'disabled\', \'disabled\');
            });
            jQuery("#ccam").click(function () {
                jQuery("#idcodageccam").removeAttr(\'disabled\');
            });
            jQuery("#idmontant_cheque").keyup(function () {
            	init_montant_cheque();
            });
            jQuery("#idmontant_carte").keyup(function () {
				init_montant_carte();
    		});
            jQuery("#idmontant_espece").keyup(function () {
           		init_montant_espece();
            });

            jQuery("#addmotifprinc").click(function () {
                /*alert(jQuery("#listmotifcons option:selected" ).val());
                alert(jQuery("#listmotifcons option:selected" ).text());*/
                var t=jQuery("#listmotifcons").children( ":selected" ).text();
            	console.log("Add value t="+t)
           	    if (t != "" && t != " ")
                {
                    jQuery("#motifconsprinc").val(t);
                    jQuery("#addmotifbox .ui-autocomplete-input").val("");
                    jQuery("#addmotifbox .ui-autocomplete-input").text("");
                    jQuery("#listmotifcons").get(0).selectedIndex = 0;
 					changed=true;
    		}
            });
            jQuery("#addmotifsec").click(function () {
                var t=jQuery("#listmotifcons").children( ":selected" ).text();
            	console.log("Add value t="+t)
           	    if (t != "" && t != " ")
            	{
                    if (jQuery("#motifconsprinc").val() == t)
                    {
                        alert(\'Le motif "\'+t+\'" est deja en motif principal\');
                    }
                    else
                    {
                        var box = jQuery("#motifconssec");
                        u=box.val() + (box.val() != \'\' ? "\n" : \'\') + t;
                        box.val(u); box.html(u);
                        jQuery("#addmotifbox .ui-autocomplete-input").val("");
                        jQuery("#addmotifbox .ui-autocomplete-input").text("");
                        jQuery("#listmotifcons").get(0).selectedIndex = 0;
 						changed=true;
    				}
                }
            });
            jQuery("#adddiaglesprinc").click(function () {
                var t=jQuery("#listdiagles").children( ":selected" ).text();
            	console.log("Add value t="+t)
           	    if (t != "" && t != " ")
                {
                    jQuery("#diaglesprinc").val(t);
                    jQuery("#adddiagbox .ui-autocomplete-input").val("");
                    jQuery("#adddiagbox .ui-autocomplete-input").text("");
                    jQuery("#listdiagles").get(0).selectedIndex = 0;
 					changed=true;
    			}
            });
            jQuery("#adddiaglessec").click(function () {
                var t=jQuery("#listdiagles").children( ":selected" ).text();
            	console.log("Add value t="+t)
                if (t != "" && t != " ")
                {
                    var box = jQuery("#diaglessec");
                    u=box.val() + (box.val() != \'\' ? "\n" : \'\') + t;
                    box.val(u); box.html(u);
                    jQuery("#adddiagbox .ui-autocomplete-input").val("");
                    jQuery("#adddiagbox .ui-autocomplete-input").text("");
                    jQuery("#listmotifcons").get(0).selectedIndex = 0;
 					changed=true;
    			}
            });
            jQuery("#addexamenprescrit").click(function () {
                var t=jQuery("#listexamenprescrit").children( ":selected" ).text();
            	console.log("Add value t="+t)
            	if (t != "" && t != " ")
                {
                    var box = jQuery("#examenprescrit");
                    u=box.val() + (box.val() != \'\' ? "\n" : \'\') + t;
                    box.val(u); box.html(u);
                    jQuery("#addexambox .ui-autocomplete-input").val("");
                    jQuery("#addexambox .ui-autocomplete-input").text("");
                    jQuery("#listexamenprescrit").get(0).selectedIndex = 0;
 					changed=true;
    			}
            });
    		';
		if ($object->typevisit != 'CCAM') {
			print ' jQuery("#idcodageccam").attr(\'disabled\',\'disabled\'); '."\n";
		}
		print '
        });
        </script>


        <style>
            #addmotifbox .ui-autocomplete-input { width: '.$width.'px; }
            #adddiagbox .ui-autocomplete-input { width: '.$width.'px; }
            #addexambox .ui-autocomplete-input { width: '.$width.'px; }
            #paymentsbox .ui-autocomplete-input { width: 140px !important; }
        </style>

		';

		print ajax_combobox('listmotifcons');
		print ajax_combobox('listdiagles');
		print ajax_combobox('listexamenprescrit');
		print ajax_combobox('banque');


		print '<div id="fieldsetanalyse">';
		//print '<legend>'.$langs->trans("InfoGenerales").'</legend>'."\n";

		$fk_agenda=empty($fk_agenda)?$object->fk_agenda:$fk_agenda;

		if ($action=='edit' || $action=='update' || $fk_agenda) print '<table class="notopnoleftnoright" width="100%">';
		if ($action=='edit' || $action=='update') {
			print '<tr><td width="180px" class="paddingtopbottom">'.img_picto('', 'briefcase-medical').' <span class="opacitymedium">'.$langs->trans('ConsultationNumero').':</span> <div class="refid inline-block"><strong>'.sprintf("%08d", $object->id).'</strong></div>';
			if ($object->fk_user > 0) {
				$fuser->fetch($object->fk_user);
				print '<span class="opacitymedium"> - '.$langs->trans("CreatedBy").': </span><strong>'.$fuser->getFullName($langs).'</strong>';
			}
			if ($object->date_c > 0) {
				print '<span class="opacitymedium"> - '.$langs->trans("DateCreation").': </span><strong>'.dol_print_date($object->date_c, 'dayhour').'</strong>';
			}
			if ($object->date_m > 0) {
				print '<span class="opacitymedium"> - '.$langs->trans("DateModificationShort").': </span><strong>'.dol_print_date($object->date_m, 'dayhour').'</strong>';
			}
			print '</td>';
			print '</tr>';
		}
		if ($fk_agenda) {
			$actioncomm=new ActionComm($db);
			$result=$actioncomm->fetch($fk_agenda);
			if ($result > 0) {
				print '<tr style="height: 24px;"><td colspan="2">';
				print $langs->trans("RecordCreatedFromRDV", $actioncomm->getNomUrl(1), dol_print_date($actioncomm->datep, 'dayhour')).'<br>';
				print '<input type="hidden" name="fk_agenda" value="'.$actioncomm->id.'">';
				print '</td></tr>';
			}
		}
		if ($action=='edit' || $action=='update' || $fk_agenda) print '</table>';


		if ($action=='edit' || $action=='update' || $fk_agenda) print '<div class="centpercent" style="margin-top: 5px; margin-bottom: 8px; border-bottom: 1px solid #eee;"></div>';

		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<table class="notopnoleftnoright" width="100%">';

		print '<tr><td class="titlefield fieldrequired">';
		print $langs->trans("Date").': ';
		print '</td><td align="left">';
		print $form->selectDate(($object->datecons?$object->datecons:''), 'cons', 0, 0, 0, '', 1, 1);
		print '</td></tr>';
		print '</table>';

		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		if (! empty($conf->global->CABINETMED_FRENCH_PRISEENCHARGE)) {
			print $langs->trans("Priseencharge").': &nbsp;';
			print '<input type="radio" class="flat" name="typepriseencharge" value=""'.(empty($object->typepriseencharge)?' checked="checked"':'').'> '.$langs->trans("None");
			print ' &nbsp; ';
			print '<input type="radio" class="flat" name="typepriseencharge" value="ALD"'.($object->typepriseencharge=='ALD'?' checked="checked"':'').'> ALD';
			print ' &nbsp; ';
			print '<input type="radio" class="flat" name="typepriseencharge" value="INV"'.($object->typepriseencharge=='INV'?' checked="checked"':'').'> INV';
			print ' &nbsp; ';
			print '<input type="radio" class="flat" name="typepriseencharge" value="AT"'.($object->typepriseencharge=='AT'?' checked="checked"':'').'> AT';
			print ' &nbsp; ';
			print '<input type="radio" class="flat" name="typepriseencharge" value="CMU"'.($object->typepriseencharge=='CMU'?' checked="checked"':'').'> CMU';
			print ' &nbsp; ';
			print '<input type="radio" class="flat" name="typepriseencharge" value="AME"'.($object->typepriseencharge=='AME'?' checked="checked"':'').'> AME';
			print ' &nbsp; ';
			print '<input type="radio" class="flat" name="typepriseencharge" value="ACS"'.($object->typepriseencharge=='ACS'?' checked="checked"':'').'> ACS';
		}

		print '</div></div></div>';

		print '<div class="fichecenter"></div>';

		print '<div class="centpercent" style="margin-top: 5px; margin-bottom: 8px; border-bottom: 1px solid #eee;"></div>';

		print '<div class="fichecenter"><div class="fichehalfleft">';

		print '<table class="notopnoleftnoright" id="addmotifbox" width="100%">';
		print '<tr><td class="titlefield">';
		print $langs->trans("MotifConsultation").':';
		print '</td><td>';
		listmotifcons(1, $width);
		print ' <input type="button" class="button small" id="addmotifprinc" name="addmotifprinc" value="+P" title="'.dol_escape_htmltag($langs->trans("ClickHereToSetPrimaryReason")).'">';
		print ' <input type="button" class="button small" id="addmotifsec" name="addmotifsec" value="+S" title="'.dol_escape_htmltag($langs->trans("ClickHereToSetSecondaryReason")).'">';
		if ($user->admin) print ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		print '</td></tr>';
		print '<tr><td class="fieldrequired">'.$langs->trans("MotifPrincipal").':';
		print '</td><td>';
		print '<input type="text" class="flat minwidth200" name="motifconsprinc" value="'.$object->motifconsprinc.'" id="motifconsprinc"><br>';
		print '</td></tr>';
		print '<tr><td>'.$langs->trans("MotifSecondaires").':';
		print '</td><td>';
		print '<textarea class="flat centpercent" name="motifconssec" id="motifconssec" rows="'.ROWS_3.'">';
		print $object->motifconssec;
		print '</textarea>';
		print '</td>';
		print '</tr>';
		print '</table>';

		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		print ''.$langs->trans("HistoireDeLaMaladie").'<br>';
		print '<textarea name="hdm" id="hdm" class="flat centpercent" rows="'.ROWS_5.'">'.$object->hdm.'</textarea>';

		print '</div></div></div>';

		print '<div class="fichecenter"><div class="fichehalfleft">';

		print '<table class="notopnoleftnoright" id="adddiagbox" width="100%">';
		//print '<tr><td><br></td></tr>';
		print '<tr><td class="titlefield">';
		print $langs->trans("DiagnostiqueLesionnel").':';
		print '</td><td>';
		//print '<input type="text" size="3" class="flat" name="searchdiagles" value="'.GETPOST("searchdiagles").'" id="searchdiagles">';
		print listdiagles(1, $width);
		print ' <input type="button" class="button small" id="adddiaglesprinc" name="adddiaglesprinc" value="+P" title="'.dol_escape_htmltag($langs->trans("ClickHereToSetPrimaryDiagnostic")).'">';
		print ' <input type="button" class="button small" id="adddiaglessec" name="adddiaglessec" value="+S" title="'.dol_escape_htmltag($langs->trans("ClickHereToSetSecondaryDiagnostic")).'">';
		if ($user->admin) print ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		print '</td></tr>';
		print '<tr><td class="'.(empty($conf->global->DIAGNOSTIC_IS_NOT_MANDATORY)?'fieldrequired':'').'">'.$langs->trans("DiagLesPrincipal").':';
		print '</td><td>';
		print '<input type="text" class="flat minwidth200" name="diaglesprinc" value="'.$object->diaglesprinc.'" id="diaglesprinc"><br>';
		print '</td></tr>';
		print '<tr><td>'.$langs->trans("DiagLesSecondaires").':';
		print '</td><td>';
		print '<textarea class="flat centpercent" name="diaglessec" id="diaglessec" rows="'.ROWS_3.'">';
		print $object->diaglessec;
		print '</textarea>';
		print '</td>';
		print '</tr>';
		print '</table>';

		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		print ''.$langs->trans("ExamensCliniques").'<br>';
		print '<textarea name="examenclinique" id="examenclinique" class="flat centpercent" rows="'.ROWS_6.'">'.$object->examenclinique.'</textarea>';

		print '</div></div></div>';

		print '<div class="fichecenter"></div>';

		// Prescriptions
		print '<div class="centpercent" style="margin-top: 5px; margin-bottom: 8px; border-bottom: 1px solid #eee;"></div>';

		print '<div class="fichecenter"><div class="fichehalfleft">';

		print '<table class="notopnoleftnoright" id="addexambox" width="100%">';

		print '<tr><td class="titlefield">';
		print $langs->trans("ExamensPrescrits").':';
		print '</td><td>';
		listexamen(1, $width, '', 0, 'examenprescrit');
		print ' <input type="button" class="button small" id="addexamenprescrit" name="addexamenprescrit" value="+">';
		if ($user->admin) print ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		print '</td></tr>';
		print '<tr><td>';
		print '</td><td>';
		print '<textarea class="flat centpercent" name="examenprescrit" id="examenprescrit" rows="'.ROWS_4.'">';
		print $object->examenprescrit;
		print '</textarea>';
		print '</td>';
		print '</tr>';

		print '<tr><td class="tdtop"><br>'.$langs->trans("Commentaires").':';
		print '</td><td><br>';
		print '<textarea name="comment" id="comment" class="flat centpercent" rows="'.($nboflines-1).'">'.$object->comment.'</textarea>';
		print '</td></tr>';

		// Other attributes
		$parameters=array();
		$reshook=$hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if (empty($reshook)) {
			$params=array('colspan'=>1);
			print $object->showOptionals($extrafields, 'edit', $params);
		}

		print '</table>';

		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		print $langs->trans("TraitementsPrescrits").'<br>';
		print '<textarea name="traitementprescrit" class="flat centpercent" rows="'.($nboflines+1).'">'.$object->traitementprescrit.'</textarea><br>';
		print $langs->trans("Infiltrations").'<br>';
		print '<textarea name="infiltration" id="infiltration" class="flat centpercent" rows="'.ROWS_2.'">'.$object->infiltration.'</textarea><br>';

		print '<br><b>'.$langs->trans("TypeVisite").'</b>: &nbsp; &nbsp; &nbsp; ';
		print '<input type="radio" class="flat" name="typevisit" value="CS" id="cs"'.($object->typevisit=='CS'?' checked="checked"':'').'> <label for="cs">'.$langs->trans("CS").'</label>';
		print ' &nbsp; &nbsp; ';
		print '<input type="radio" class="flat" name="typevisit" value="CS2" id="c2"'.($object->typevisit=='CS2'?' checked="checked"':'').'> <label for="c2">'.$langs->trans("CS2").'</label>';
		print ' &nbsp; &nbsp; ';
		print '<input type="radio" class="flat" name="typevisit" value="CCAM" id="ccam"'.($object->typevisit=='CCAM'?' checked="checked"':'').'> <label for="ccam">'.$langs->trans("CCAM").'</label>';
		print '<br>';
		print '<br>'.$langs->trans("CodageCCAM").': &nbsp; ';
		print '<input type="text" class="flat" name="codageccam" id="idcodageccam" value="'.$object->codageccam.'" size="30">';	// name must differ from id
		print '</td></tr>';

		print '</table>';

		print '</div></div></div>';

		print '</div>'; // End of general information

		print '<div class="clearboth"><div class="divpayment" style="padding-top: 25px; margin-bottom: 20px;">';

		print load_fiche_titre($langs->trans("Payment"), '', 'title_accountancy');

		print '<hr>';
		//print '<fieldset id="fieldsetanalyse">';
		//print '<legend>'.$langs->trans("Paiement").'</legend>'."\n";

		// Try to autodetect the default bank account to use. For this we search opened account with user name into label or owner
		$defaultbankaccountchq=0;
		$defaultbankaccountliq=0;
		$sql="SELECT rowid, label, bank, courant";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
		$sql.= " WHERE clos = 0";
		$sql.= " AND entity = ".((int) $conf->entity);
		$sql.= " AND (proprio LIKE '%".$db->escape($user->lastname)."%' OR label LIKE '%".$db->escape($user->lastname)."%')";
		$sql.= " ORDER BY label";
		//print $sql;
		$resql=$db->query($sql);
		if ($resql) {
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num) {
				$obj=$db->fetch_object($resql);
				if ($obj) {
					if ($obj->courant == 1) $defaultbankaccountchq=$obj->rowid;
					if ($obj->courant == 2) $defaultbankaccountliq=$obj->rowid;
				}
				$i++;
			}
		}


		// Payment area
		print '<table class="notopnoleftnoright centpercent" id="paymentsbox">';

		if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && ! empty($conf->global->CABINETMED_AUTOGENERATE_INVOICE)) {
			print '<tr><td></td><td>';
			if ($object->id > 0 && ! $error) {
				print '<input name="generateinvoice" type="checkbox" disabled="disabled"> <span class="opacitymedium">'.$langs->trans("GenerateInvoiceAndPayment").'</span><span class="hideonsmartphone"> - '.$langs->trans("YouMustEditInvoiceManually").'</span>';
			} else {
				print '<input name="generateinvoice" type="checkbox" checked="checked"> '.$langs->trans("GenerateInvoiceAndPayment");
			}
			print '</td></tr>';
		}

		// Cheque
		print '<tr class="cabpaymentcheque"><td class="titlefield">';
		print $langs->trans("PaymentTypeCheque").'</td><td>';
		print '<input type="text" class="flat" name="montant_cheque" id="idmontant_cheque" value="'.($object->montant_cheque!=''?price($object->montant_cheque):'').'" size="4"';
		print ' placeholder="'.($conf->currency != $langs->getCurrencySymbol($conf->currency) ? $langs->getCurrencySymbol($conf->currency) : '').'"';
		print '>';
		if (isModEnabled("banque")) {
			print ' &nbsp; '.$langs->trans("RecBank").' ';
			$form->select_comptes(GETPOST('bankchequeto')?GETPOST('bankchequeto'):($object->bank['CHQ']['account_id']?$object->bank['CHQ']['account_id']:$defaultbankaccountchq), 'bankchequeto', 2, 'courant = 1', 1);
		}
		print ' &nbsp; ';
		print $langs->trans("ChequeBank").' ';
		listebanques(1, 0, $object->banque);
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		if (isModEnabled("banque")) {
			print ' &nbsp; '.$langs->trans("ChequeOrTransferNumber").' ';
			print '<input type="text" class="flat" name="num_cheque" id="idnum_cheque" value="'.$object->num_cheque.'" size="6">';
		}
		print '</td></tr>';
		// Card
		print '<tr class="cabpaymentcarte"><td class="">';
		print $langs->trans("PaymentTypeCarte").'</td><td>';
		print '<input type="text" class="flat" name="montant_carte" id="idmontant_carte" value="'.($object->montant_carte!=''?price($object->montant_carte):'').'" size="4"';
		print ' placeholder="'.($conf->currency != $langs->getCurrencySymbol($conf->currency) ? $langs->getCurrencySymbol($conf->currency) : '').'"';
		print '>';
		if (isModEnabled("banque")) {
			print ' &nbsp; '.$langs->trans("RecBank").' ';
			$form->select_comptes(GETPOST('bankcarteto')?GETPOST('bankcarteto'):($object->bank['CB']['account_id']?$object->bank['CB']['account_id']:$defaultbankaccountchq), 'bankcarteto', 2, 'courant = 1', 1);
		}
		print '</td></tr>';
		// Cash
		print '<tr class="cabpaymentcash"><td class="">';
		print $langs->trans("PaymentTypeEspece").'</td><td>';
		print '<input type="text" class="flat" name="montant_espece" id="idmontant_espece" value="'.($object->montant_espece!=''?price($object->montant_espece):'').'" size="4"';
		print ' placeholder="'.($conf->currency != $langs->getCurrencySymbol($conf->currency) ? $langs->getCurrencySymbol($conf->currency) : '').'"';
		print '>';
		if (isModEnabled("banque")) {
			print ' &nbsp; '.$langs->trans("RecBank").' ';
			$form->select_comptes(GETPOST('bankespeceto')?GETPOST('bankespeceto'):($object->bank['LIQ']['account_id']?$object->bank['LIQ']['account_id']:$defaultbankaccountliq), 'bankespeceto', 2, 'courant = 2', 1);
		}
		print '</td></tr>';

		// Third party
		print '<tr class="cabpaymentthirdparty"><td class="">';
		print $langs->trans("PaymentTypeThirdParty").'</td><td>';
		print '<input type="text" class="flat" name="montant_tiers" id="idmontant_tiers" value="'.($object->montant_tiers!=''?price($object->montant_tiers):'').'" size="4"';
		print ' placeholder="'.($conf->currency != $langs->getCurrencySymbol($conf->currency) ? $langs->getCurrencySymbol($conf->currency) : '').'"';
		print '>';
		print '<span class="opacitymedium"> &nbsp; ('.$langs->trans("ZeroHereIfNoPayment").')</span>';
		print '</td></tr>';

		print '</table>';

		print '</div></div>';

		print '<br>';

		dol_htmloutput_errors($mesg, $mesgarray);
	}

	//dol_fiche_end();

	if ($action == 'create' || $action == 'edit') {
		print '<center>';
		if ($action == 'edit') {
			// Set option if not defined
			if (! isset($conf->global->CABINETMED_DELAY_TO_LOCK_RECORD)) $conf->global->CABINETMED_DELAY_TO_LOCK_RECORD=30;

			// If consult was create before current date - CABINETMED_DELAY_TO_LOCK_RECORD days.
			if (! empty($conf->global->CABINETMED_DELAY_TO_LOCK_RECORD) && $object->date_c < ($now - ($conf->global->CABINETMED_DELAY_TO_LOCK_RECORD * 24 * 3600))) {
				print '<input type="submit" class="button ignorechange" id="updatebutton" name="update" value="'.$langs->trans("Save").'" disabled="disabled" title="'.dol_escape_htmltag($langs->trans("ConsultTooOld", $conf->global->CABINETMED_DELAY_TO_LOCK_RECORD)).'">';
			} else {
				print '<input type="submit" class="button ignorechange" id="updatebutton" name="update" value="'.$langs->trans("Save").'">';
			}
		}
		if ($action == 'create') {
			print '<input type="submit" class="button ignorechange" id="addbutton" name="add" value="'.$langs->trans("Add").'">';
		}
		print ' &nbsp; &nbsp; ';
		print '<input type="submit" class="button ignorechange" id="cancelbutton" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</center>';
	}

	print '</form>';
}


/*
 * Boutons actions
 */

if ($action == '' || $action == 'list' || $action == 'delete') {
	print '<div class="tabsAction">';

	if ($user->rights->societe->creer) {
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?socid='.$soc->id.'&action=create">'.$langs->trans("NewConsult").'</a>';
	}

	print '</div>';
}


if ($action == '' || $action == 'list' || $action == 'delete') {
	if ($soc->alert_antemed)       $mesgs[]=$langs->transnoentitiesnoconv("Warning").': '.$langs->transnoentitiesnoconv("AlertTriggered", $langs->transnoentitiesnoconv("AntecedentsMed"));
	if ($soc->alert_antechirgen)   $mesgs[]=$langs->transnoentitiesnoconv("Warning").': '.$langs->transnoentitiesnoconv("AlertTriggered", $langs->transnoentitiesnoconv("AntecedentsChirGene"));
	if ($soc->alert_antechirortho) $mesgs[]=$langs->transnoentitiesnoconv("Warning").': '.$langs->transnoentitiesnoconv("AlertTriggered", $langs->transnoentitiesnoconv("AntecedentsChirOrtho"));
	if ($soc->alert_anterhum)      $mesgs[]=$langs->transnoentitiesnoconv("Warning").': '.$langs->transnoentitiesnoconv("AlertTriggered", $langs->transnoentitiesnoconv("AntecedentsRhumato"));
	if ($soc->alert_other)         $mesgs[]=$langs->transnoentitiesnoconv("Warning").': '.$langs->transnoentitiesnoconv("AlertTriggered", $langs->transnoentitiesnoconv("AntecedentsMed"));
	if ($soc->alert_traitclass)    $mesgs[]=$langs->transnoentitiesnoconv("Warning").': '.$langs->transnoentitiesnoconv("AlertTriggered", $langs->transnoentitiesnoconv("xxx"));
	if ($soc->alert_traitallergie) $mesgs[]=$langs->transnoentitiesnoconv("Warning").': '.$langs->transnoentitiesnoconv("AlertTriggered", $langs->transnoentitiesnoconv("Allergies"));
	if ($soc->alert_traitintol)    $mesgs[]=$langs->transnoentitiesnoconv("Warning").': '.$langs->transnoentitiesnoconv("AlertTriggered", $langs->transnoentitiesnoconv("Intolerances"));
	if ($soc->alert_traitspec)     $mesgs[]=$langs->transnoentitiesnoconv("Warning").': '.$langs->transnoentitiesnoconv("AlertTriggered", $langs->transnoentitiesnoconv("SpecPharma"));
	if ($soc->alert_note)          $mesgs[]=$langs->transnoentitiesnoconv("Warning").': '.$langs->transnoentitiesnoconv("AlertTriggered", $langs->transnoentitiesnoconv("Note"));

	// Confirm delete consultation
	if (GETPOST("action") == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$socid.'&id='.GETPOST('id', 'int'), $langs->trans("DeleteAConsultation"), $langs->trans("ConfirmDeleteConsultation"), "confirm_delete", '', 0, 1);
		print $formconfirm;
	}

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'" name="formfilter" autocomplete="off">'."\n";
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print '<input type="hidden" name="socid" value="'.$socid.'">';

	print_fiche_titre($langs->trans("ListOfConsultations"));

	$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
	$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
	$selectedfields.=((is_array($arrayofmassactions) && count($arrayofmassactions)) ? $form->showCheckAddButtons('checkforselect', 1) : '');

	dol_htmloutput_mesg('', $mesgs, 'warning');


	$param='&socid='.$socid;

	print "\n";

	$totalarray = array();
	$totalarray['nbfield'] = 0;

	print '<div class="div-table-responsive">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	if (! empty($arrayfields['t.rowid']['checked'])) {
		print_liste_field_titre($langs->trans('Num'), $_SERVER['PHP_SELF'], 't.rowid', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (! empty($arrayfields['t.datecons']['checked'])) {
		print_liste_field_titre($arrayfields['t.datecons']['label'], $_SERVER["PHP_SELF"], "t.datecons,t.rowid", "", $param, 'align="center"', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (! empty($arrayfields['t.fk_user']['checked'])) {
		print_liste_field_titre($langs->trans('CreatedBy'), $_SERVER['PHP_SELF'], 't.fk_user', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (! empty($arrayfields['t.motifconsprinc']['checked'])) {
		print_liste_field_titre($langs->trans("MotifPrincipal"), $_SERVER["PHP_SELF"], "t.motifconsprinc", "", $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (! empty($arrayfields['t.diaglesprinc']['checked'])) {
		print_liste_field_titre($langs->trans('DiagLesPrincipal'), $_SERVER['PHP_SELF'], 't.diaglesprinc', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (! empty($arrayfields['t.typepriseencharge']['checked'])) {
		print_liste_field_titre($langs->trans('Priseencharge'), $_SERVER['PHP_SELF'], 't.typepriseencharge', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (! empty($arrayfields['t.typevisit']['checked'])) {
		print_liste_field_titre($langs->trans('ConsultActe'), $_SERVER['PHP_SELF'], 't.typevisit', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (! empty($arrayfields['amountpayment']['checked'])) {
		print_liste_field_titre($langs->trans('MontantPaiement'), $_SERVER['PHP_SELF'], '', '', $param, 'align="right"', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (! empty($arrayfields['typepayment']['checked'])) {
		print_liste_field_titre($langs->trans('TypePaiement'), $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	$reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', $param, 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	print '</tr>';

	// List des consult
	$sql = "SELECT";
	$sql.= " t.rowid,";
	$sql.= " t.fk_soc,";
	$sql.= " t.datecons,";
	$sql.= " t.typepriseencharge,";
	$sql.= " t.motifconsprinc,";
	$sql.= " t.diaglesprinc,";
	$sql.= " t.motifconssec,";
	$sql.= " t.diaglessec,";
	$sql.= " t.hdm,";
	$sql.= " t.examenclinique,";
	$sql.= " t.examenprescrit,";
	$sql.= " t.traitementprescrit,";
	$sql.= " t.comment,";
	$sql.= " t.typevisit,";
	$sql.= " t.infiltration,";
	$sql.= " t.codageccam,";
	$sql.= " t.montant_cheque,";
	$sql.= " t.montant_espece,";
	$sql.= " t.montant_carte,";
	$sql.= " t.montant_tiers,";
	$sql.= " t.banque,";
	$sql.= " t.fk_user_creation,";
	$sql.= " t.fk_user,";
	// Add fields from extrafields
	if (! empty($extrafields->attributes[$object->table_element]['label']))
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql.=($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key." as options_".$key.', ' : '');
	// Add fields from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
	$sql.=preg_replace('/^,/', '', $hookmanager->resPrint);
	$sql =preg_replace('/,\s*$/', '', $sql);
	$sql.= " FROM ".MAIN_DB_PREFIX."cabinetmed_cons as t";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."cabinetmed_cons_extrafields as ef ON ef.fk_object = t.rowid";
	$sql.= " WHERE t.fk_soc = ".$socid;
	// Add where from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
	$sql.=$db->order($sortfield, $sortorder);

	$consultstatic = new CabinetmedCons($db);

	$resql=$db->query($sql);
	if ($resql) {
		$i = 0 ;
		$num = $db->num_rows($resql);

		$usertmp = new User($db);

		while ($i < $num) {
			$totalarray['nbfield'] = 0;

			$obj = $db->fetch_object($resql);

			$object->id=$obj->rowid;
			$object->fetch_bankid();

			print '<tr class="oddeven">';

			if (! empty($arrayfields['t.rowid']['checked'])) {
				print '<td>';
				$consultstatic->id=$obj->rowid;
				$consultstatic->fk_soc=$obj->fk_soc;
				print $consultstatic->getNomUrl(1, '&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$obj->fk_soc));
				print '</td>';
				$totalarray['nbfield']++;
			}

			if (! empty($arrayfields['t.datecons']['checked'])) {
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->datecons), 'day');
				print '</td>';
				$totalarray['nbfield']++;
			}

			if (! empty($arrayfields['t.fk_user']['checked'])) {
				print '<td>';
				if ($obj->fk_user_creation > 0) {
					$usertmp->fetch($obj->fk_user_creation);
					print $usertmp->getNomUrl(1);
				}
				print '</td>';
				$totalarray['nbfield']++;
			}

			if (! empty($arrayfields['t.motifconsprinc']['checked'])) {
				print '<td>'.dol_trunc($obj->motifconsprinc, 32).'</td>';
				$totalarray['nbfield']++;
			}

			if (! empty($arrayfields['t.diaglesprinc']['checked'])) {
				print '<td>';
				print dol_trunc($obj->diaglesprinc, 32);
				print '</td>';
				$totalarray['nbfield']++;
			}

			if (! empty($arrayfields['t.typepriseencharge']['checked'])) {
				print '<td>';
				print $obj->typepriseencharge;
				print '</td>';
				$totalarray['nbfield']++;
			}

			if (! empty($arrayfields['t.typevisit']['checked'])) {
				print '<td>';
				print $langs->trans($obj->typevisit);
				print '</td>';
				$totalarray['nbfield']++;
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
				$totalarray['nbfield']++;
			}

			if (! empty($arrayfields['typepayment']['checked'])) {
				print '<td>';
				$foundamount=0;
				if (price2num($obj->montant_cheque) > 0) {
					if ($foundamount) print ' + ';
					print $langs->trans("Cheque");
					if (isModEnabled("banque") && $object->bank['CHQ']['account_id']) {
						$bank=new Account($db);
						$bank->fetch($object->bank['CHQ']['account_id']);
						print '&nbsp;('.$bank->getNomUrl(0, 'transactions').')';
					}
					$foundamount++;
				}
				if (price2num($obj->montant_espece) > 0) {
					if ($foundamount) print ' + ';
					print $langs->trans("Cash");
					if (isModEnabled("banque") && $object->bank['LIQ']['account_id']) {
						$bank=new Account($db);
						$bank->fetch($object->bank['LIQ']['account_id']);
						print '&nbsp;('.$bank->getNomUrl(0, 'transactions').')';
					}
					$foundamount++;
				}
				if (price2num($obj->montant_carte) > 0) {
					if ($foundamount) print ' + ';
					print $langs->trans("CreditCard");
					if (isModEnabled("banque") && $object->bank['CB']['account_id']) {
						$bank=new Account($db);
						$bank->fetch($object->bank['CB']['account_id']);
						print '&nbsp;('.$bank->getNomUrl(0, 'transactions').')';
					}
					$foundamount++;
				}
				if (price2num($obj->montant_tiers) > 0) {
					if ($foundamount) print ' + ';
					print $langs->trans("PaymentTypeThirdParty");
					if (isModEnabled("banque") && $object->bank['OTH']['account_id']) {
						$bank=new Account($db);
						$bank->fetch($object->bank['OTH']['account_id']);
						print '&nbsp;('.$bank->getNomUrl(0, 'transactions').')';
					}
					$foundamount++;
				}
				print '</td>';
				$totalarray['nbfield']++;
			}

			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
			// Fields from hook
			$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
			$reshook=$hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;

			print '<td class="nowraponall">';
			print '<a class="reposition editfielda" href="'.$_SERVER["PHP_SELF"].'?socid='.$obj->fk_soc.'&id='.$obj->rowid.'&action=edit&token='.newToken().'">'.img_edit().'</a>';
			if (!empty($user->rights->societe->supprimer)) {
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
	print '</table>';
	print '</div>';

	print "</form>\n";
}


llxFooter();

$db->close();
