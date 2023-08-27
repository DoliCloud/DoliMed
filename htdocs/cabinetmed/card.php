<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Brian Fraval         <brian@fraval.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2008	   Patrick Raguin       <patrick.raguin@auguria.net>
 * Copyright (C) 2010-2013 Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/cabinetmed/card.php
 *  \ingroup    cabinetmed
 *  \brief      Third party card page
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
if (isModEnabled("adherent")) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

$langs->loadLangs(array("cabinetmed@cabinetmed", "companies","commercial","bills","banks","users","other"));
if (isModEnabled("categorie")) $langs->load("categories");
if (isModEnabled("incoterm")) $langs->load("incoterm");
if (isModEnabled("notification")) $langs->load("mails");

$mesg=''; $error=0; $errors=array();

$action		= (GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view');
$cancel		= GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');

$socid		= GETPOST('socid', 'int')?GETPOST('socid', 'int'):GETPOST('id', 'int');
if ($user->socid) $socid=$user->socid;
if (empty($socid) && $action == 'view') $action='create';

$object = new Societe($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartycard','globalcard'));

if ($socid > 0) $object->fetch($socid);

if (! ($object->id > 0) && $action == 'view') {
	$langs->load("errors");
	print($langs->trans('ErrorRecordNotFound'));
	exit;
}

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$object->getCanvas($socid);
$canvas = $object->canvas?$object->canvas:GETPOST("canvas");
$objcanvas=null;
if (! empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('thirdparty', 'card', $canvas);
}

// Security check
$result = restrictedArea($user, 'societe', $socid, '&societe', '', 'fk_soc', 'rowid', 0);



/*
 * Actions
 */

$parameters=array('id'=>$socid, 'objcanvas'=>$objcanvas);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if ($cancel) {
		$action='';
		if (! empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
	}

	if ($action == 'confirm_merge' && $confirm == 'yes' && $user->rights->societe->creer) {
		$error = 0;
		$soc_origin_id = GETPOST('soc_origin', 'int');
		$soc_origin = new Societe($db);

		if ($soc_origin_id <= 0) {
			$langs->load('errors');
			$langs->load('companies');
			setEventMessages($langs->trans('ErrorThirdPartyIdIsMandatory', $langs->transnoentitiesnoconv('MergeOriginThirdparty')), null, 'errors');
		} else {
			if (!$error && $soc_origin->fetch($soc_origin_id) < 1) {
				setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
				$error++;
			}

			if (!$error) {
				// TODO Move the merge function into class of object.

				$db->begin();

				// Recopy some data
				$object->client = $object->client | $soc_origin->client;
				$object->fournisseur = $object->fournisseur | $soc_origin->fournisseur;
				$listofproperties=array(
					'address', 'zip', 'town', 'state_id', 'country_id', 'phone', 'phone_pro', 'fax', 'email', 'url', 'barcode',
					'idprof1', 'idprof2', 'idprof3', 'idprof4', 'idprof5', 'idprof6',
					'tva_intra', 'effectif_id', 'forme_juridique', 'remise_percent', 'remise_supplier_percent', 'mode_reglement_supplier_id', 'cond_reglement_supplier_id', 'name_bis',
					'stcomm_id', 'outstanding_limit', 'price_level', 'parent', 'default_lang', 'ref', 'ref_ext', 'import_key', 'fk_incoterms', 'fk_multicurrency',
					'code_client', 'code_fournisseur', 'code_compta', 'code_compta_fournisseur',
					'model_pdf', 'fk_projet'
				);
				foreach ($listofproperties as $property) {
					if (empty($object->$property)) $object->$property = $soc_origin->$property;
				}

				// Concat some data
				$listofproperties=array(
					'note_public', 'note_private'
				);
				foreach ($listofproperties as $property) {
					$object->$property = dol_concatdesc($object->$property, $soc_origin->$property);
				}

				// Merge extrafields
				if (is_array($soc_origin->array_options)) {
					foreach ($soc_origin->array_options as $key => $val) {
						if (empty($object->array_options[$key])) $object->array_options[$key] = $val;
					}
				}

				// Merge categories
				$static_cat = new Categorie($db);

				$custcats_ori = $static_cat->containing($soc_origin->id, 'customer', 'id');
				$custcats = $static_cat->containing($object->id, 'customer', 'id');
				$custcats = array_merge($custcats, $custcats_ori);
				$object->setCategories($custcats, 'customer');

				$suppcats_ori = $static_cat->containing($soc_origin->id, 'supplier', 'id');
				$suppcats = $static_cat->containing($object->id, 'supplier', 'id');
				$suppcats = array_merge($suppcats, $suppcats_ori);
				$object->setCategories($suppcats, 'supplier');

				// If thirdparty has a new code that is same than origin, we clean origin code to avoid duplicate key from database unique keys.
				if ($soc_origin->code_client == $object->code_client
					|| $soc_origin->code_fournisseur == $object->code_fournisseur
					|| $soc_origin->barcode == $object->barcode) {
					dol_syslog("We clean customer and supplier code so we will be able to make the update of target");
					$soc_origin->code_client = '';
					$soc_origin->code_fournisseur = '';
					$soc_origin->barcode = '';
					$soc_origin->update($soc_origin->id, $user, 0, 1, 1, 'merge');
				}

				// Update
				$result = $object->update($object->id, $user, 0, 1, 1, 'merge');
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}

				// Move links
				if (! $error) {
					$objects = array(
						'Adherent' => '/adherents/class/adherent.class.php',
						'Societe' => '/societe/class/societe.class.php',
						//'Categorie' => '/categories/class/categorie.class.php',
						'ActionComm' => '/comm/action/class/actioncomm.class.php',
						'Propal' => '/comm/propal/class/propal.class.php',
						'Commande' => '/commande/class/commande.class.php',
						'Facture' => '/compta/facture/class/facture.class.php',
						'FactureRec' => '/compta/facture/class/facture-rec.class.php',
						'LignePrelevement' => '/compta/prelevement/class/ligneprelevement.class.php',
						'Contact' => '/contact/class/contact.class.php',
						'Contrat' => '/contrat/class/contrat.class.php',
						'Expedition' => '/expedition/class/expedition.class.php',
						'Fichinter' => '/fichinter/class/fichinter.class.php',
						'CommandeFournisseur' => '/fourn/class/fournisseur.commande.class.php',
						'FactureFournisseur' => '/fourn/class/fournisseur.facture.class.php',
						'SupplierProposal' => '/supplier_proposal/class/supplier_proposal.class.php',
						'ProductFournisseur' => '/fourn/class/fournisseur.product.class.php',
						'Livraison' => '/livraison/class/livraison.class.php',
						'Product' => '/product/class/product.class.php',
						'Project' => '/projet/class/project.class.php',
						'User' => '/user/class/user.class.php',
					);

					//First, all core objects must update their tables
					foreach ($objects as $object_name => $object_file) {
						require_once DOL_DOCUMENT_ROOT.$object_file;

						if (!$error && !$object_name::replaceThirdparty($db, $soc_origin->id, $object->id)) {
							$error++;
							setEventMessages($db->lasterror(), null, 'errors');
						}
					}
				}

				// External modules should update their ones too
				if (! $error) {
					$reshook = $hookmanager->executeHooks('replaceThirdparty', array(
						'soc_origin' => $soc_origin->id,
						'soc_dest' => $object->id
					), $object, $action);

					if ($reshook < 0) {
						setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
						$error++;
					}
				}


				if (! $error) {
					$object->context=array('merge'=>1, 'mergefromid'=>$soc_origin->id);

					// Call trigger
					$result=$object->call_trigger('COMPANY_MODIFY', $user);
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					}
					// End call triggers
				}

				if (!$error) {
					//We finally remove the old thirdparty
					if ($soc_origin->delete($soc_origin->id, $user) < 1) {
						$error++;
					}
				}

				if (!$error) {
					setEventMessages($langs->trans('ThirdpartiesMergeSuccess'), null, 'mesgs');
					$db->commit();
				} else {
					$langs->load("errors");
					setEventMessages($langs->trans('ErrorsThirdpartyMerge'), null, 'errors');
					$db->rollback();
				}
			}
		}
	}

	if (GETPOST('getcustomercode')) {
		// We defined value code_client
		$_POST["customer_code"]="Acompleter";
	}

	if (GETPOST('getsuppliercode')) {
		// We defined value code_fournisseur
		$_POST["supplier_code"]="Acompleter";
	}

	if ($action=='set_localtax1') {
		//obtidre selected del combobox
		$value=GETPOST('lt1');
		$object->fetch($socid);
		$res=$object->setValueFrom('localtax1_value', $value, '', null, 'text', '', $user, 'COMPANY_MODIFY');
	}
	if ($action=='set_localtax2') {
		//obtidre selected del combobox
		$value=GETPOST('lt2');
		$object->fetch($socid);
		$res=$object->setValueFrom('localtax2_value', $value, '', null, 'text', '', $user, 'COMPANY_MODIFY');
	}

	if ($action == 'update_extras') {
		$object->fetch($socid);

		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'none'));
		if ($ret < 0) $error++;

		if (! $error) {
			$result = $object->insertExtraFields('COMPANY_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) $action = 'edit_extras';
	}

	// Add new or update third party
	if ((! GETPOST('getcustomercode') && ! GETPOST('getsuppliercode'))
	&& ($action == 'add' || $action == 'update') && $user->rights->societe->creer) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		if (! GETPOST('name')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ThirdPartyName")), null, 'errors');
			$error++;
		}

		if (! $error) {
			if ($action == 'update') {
				$ret=$object->fetch($socid);
				$object->oldcopy=clone $object;
			} else $object->canvas=$canvas;

			if (GETPOST("private", 'int') == 1) {	// Ask to create a contact
				$object->particulier       = GETPOST("private");

				$object->name              = dolGetFirstLastname(GETPOST('firstname', 'alpha'), GETPOST('name', 'alpha'));
				$object->civilite_id       = GETPOST('civilite_id')?GETPOST('civilite_id'):GETPOST('civility_id');
				$object->civility_id       = GETPOST('civility_id');	// Note: civility id is a code, not an int
				// Add non official properties
				$object->name_bis          = GETPOST('name', 'alpha');
				$object->firstname         = GETPOST('firstname', 'alpha');
			} else {
				$object->name              = GETPOST('name')?GETPOST('name', 'alpha'):GETPOST('nom', 'alpha');
				$object->name_alias        = GETPOST('name_alias');
			}
			$object->entity					= (GETPOSTISSET('entity')?GETPOST('entity', 'int'):$conf->entity);
			$object->name_alias				= GETPOST('name_alias');
			$object->address				= GETPOST('address');
			$object->zip					= GETPOST('zipcode', 'alpha');
			$object->town					= GETPOST('town', 'alpha');
			$object->country_id				= GETPOST('country_id', 'int');
			$object->state_id				= GETPOST('state_id', 'int');
			$object->phone					= GETPOST('phone', 'alpha');
			$object->fax					= GETPOST('fax', 'alpha');
			$object->email					= trim(GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL));
			$object->url					= trim(GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL));
			$object->idprof1				= trim(GETPOST('idprof1', 'alpha'));
			$object->idprof2				= trim(GETPOST('idprof2', 'alpha'));
			$object->idprof3				= trim(GETPOST('idprof3', 'alpha'));
			$object->idprof4				= trim(GETPOST('idprof4', 'alpha'));
			$object->idprof5				= trim(GETPOST('idprof5', 'alpha'));
			$object->idprof6				= trim(GETPOST('idprof6', 'alpha'));
			$object->prefix_comm			= GETPOST('prefix_comm', 'alpha');
			$object->code_client			= GETPOSTISSET('customer_code')?GETPOST('customer_code', 'alpha'):GETPOST('code_client', 'alpha');
			$object->code_fournisseur		= GETPOSTISSET('supplier_code')?GETPOST('supplier_code', 'alpha'):GETPOST('code_fournisseur', 'alpha');
			$object->capital				= GETPOST('capital', 'alpha');
			$object->barcode				= GETPOST('barcode', 'alpha');

			$object->tva_intra				= GETPOST('tva_intra', 'alpha');
			$object->tva_assuj				= GETPOST('assujtva_value', 'alpha');
			$object->status					= GETPOST('status', 'alpha');


			// Local Taxes
			$object->localtax1_assuj		= GETPOST('localtax1assuj_value', 'alpha');
			$object->localtax2_assuj		= GETPOST('localtax2assuj_value', 'alpha');

			$object->localtax1_value		= GETPOST('lt1', 'alpha');
			$object->localtax2_value		= GETPOST('lt2', 'alpha');

			$object->forme_juridique_code  = GETPOST('forme_juridique_code', 'int');
			$object->effectif_id           = GETPOST('effectif_id', 'int');
			$object->typent_id				= GETPOST('typent_id', 'int');

			$object->typent_code			= dol_getIdFromCode($db, $object->typent_id, 'c_typent', 'id', 'code');	// Force typent_code too so check in verify() will be done on new type

			$object->client					= GETPOST('client', 'int');
			$object->fournisseur			= GETPOST('fournisseur', 'int');

			$object->commercial_id         = GETPOST('commercial_id', 'int');

			$object->default_lang          = GETPOST('default_lang');

			// Webservices url/key
			$object->webservices_url		= GETPOST('webservices_url', 'custom', 0, FILTER_SANITIZE_URL);
			$object->webservices_key		= GETPOST('webservices_key', 'san_alpha');

			// Incoterms
			if (isModEnabled("incoterm")) {
				$object->fk_incoterms		= GETPOST('incoterm_id', 'int');
				$object->location_incoterms	= GETPOST('location_incoterms', 'alpha');
			}

			// Multicurrency
			if (isModEnabled("multicurrency")) {
				$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
			}

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				 $error++;
			}

			if (GETPOST('deletephoto')) $object->logo = '';
			elseif (! empty($_FILES['photo']['name'])) $object->logo = dol_sanitizeFileName($_FILES['photo']['name']);

			// Check parameters
			if (! GETPOST('cancel', 'alpha')) {
				if (! empty($object->email) && ! isValidEMail($object->email)) {
					$langs->load("errors");
					$error++;
					setEventMessages($langs->trans("ErrorBadEMail", $object->email), null, 'errors');
				}
				if (! empty($object->url) && ! isValidUrl($object->url)) {
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorBadUrl", $object->url), null, 'errors');
				}
				if (! empty($object->webservices_url)) {
					//Check if has transport, without any the soap client will give error
					if (strpos($object->webservices_url, "http") === false) {
						$object->webservices_url = "http://".$object->webservices_url;
					}
					if (! isValidUrl($object->webservices_url)) {
						$langs->load("errors");
						$error++; $errors[] = $langs->trans("ErrorBadUrl", $object->webservices_url);
					}
				}

				// We set country_id, country_code and country for the selected country
				$object->country_id=GETPOST('country_id')!=''?GETPOST('country_id'):$mysoc->country_id;
				if ($object->country_id) {
					$tmparray=getCountry($object->country_id, 'all');
					$object->country_code=$tmparray['code'];
					$object->country=$tmparray['label'];
				}
			}
		}

		if (! $error) {
			if ($action == 'add') {
				$error = 0;

				$db->begin();

				if (empty($object->client))      $object->code_client='';
				if (empty($object->fournisseur)) $object->code_fournisseur='';

				$result = $object->create($user);
				if ($result >= 0) {
					if ($object->particulier) {
						dol_syslog("We ask to create a contact/address too", LOG_DEBUG);
						$result=$object->create_individual($user);
						if ($result < 0) {
							setEventMessages($object->error, $object->errors, 'errors');
							$error++;
						}
					}

					// Links with users
					//var_dump(DOL_VERSION);exit;
					if ((float) DOL_VERSION >= 8) {
						$salesreps = GETPOST('commercial', 'array');

						$result = $object->setSalesRep($salesreps);
						if ($result < 0) {
							$error++;
							setEventMessages($object->error, $object->errors, 'errors');
						}
					}

					// Customer categories association
					$custcats = GETPOST('custcats', 'array');
					$result = $object->setCategories($custcats, 'customer');
					if ($result < 0) {
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}

					// Supplier categories association
					$suppcats = GETPOST('suppcats', 'array');
					$result = $object->setCategories($suppcats, 'supplier');
					if ($result < 0) {
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}

					// Logo/Photo save
					$dir     = $conf->societe->multidir_output[$conf->entity]."/".$object->id."/logos/";
					$file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
					if ($file_OK) {
						if (image_format_supported($_FILES['photo']['name'])) {
							dol_mkdir($dir);

							if (@is_dir($dir)) {
								$newfile=$dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
								$result = dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1);

								if (! $result > 0) {
									$errors[] = "ErrorFailedToSaveFile";
								} else {
									// Create thumbs
									$object->addThumbs($newfile);
								}
							}
						}
					} else {
						switch ($_FILES['photo']['error']) {
							case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
							case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
								$errors[] = "ErrorFileSizeTooLarge";
							  break;
							case 3: //uploaded file was only partially uploaded
								$errors[] = "ErrorFilePartiallyUploaded";
							  break;
						}
					}
					// Gestion du logo de la société
				} else {
					if ($db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') { // TODO Sometime errors on duplicate on profid and not on code, so we must manage this case
						$duplicate_code_error = true;
						$object->code_fournisseur = null;
						$object->code_client = null;
					}

					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}

				if ($result >= 0 && ! $error) {
					$db->commit();

					if (! empty($backtopage)) {
						if (preg_match('/\?/', $backtopage)) $backtopage.='&socid='.$object->id;
						header("Location: ".$backtopage);
						exit;
					} else {
						$url=$_SERVER["PHP_SELF"]."?socid=".$object->id;
						/*if (($object->client == 1 || $object->client == 3) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) $url=DOL_URL_ROOT."/comm/card.php?socid=".$object->id;
						else if ($object->fournisseur == 1) $url=DOL_URL_ROOT."/fourn/card.php?socid=".$object->id;*/

						header("Location: ".$url);
						exit;
					}
				} else {
					$db->rollback();
					$action='create';
				}
			}

			if ($action == 'update') {
				$error = 0;

				if (GETPOST('cancel', 'alpha')) {
					if (! empty($backtopage)) {
						header("Location: ".$backtopage);
						exit;
					} else {
						header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$socid);
						exit;
					}
				}

				// To not set code if third party is not concerned. But if it had values, we keep them.
				if (empty($object->client) && empty($object->oldcopy->code_client))          $object->code_client='';
				if (empty($object->fournisseur)&& empty($object->oldcopy->code_fournisseur)) $object->code_fournisseur='';
				//var_dump($object);exit;

				$result = $object->update($socid, $user, 1, $object->oldcopy->codeclient_modifiable(), $object->oldcopy->codefournisseur_modifiable(), 'update', 0);
				if ($result <=  0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}

				// Links with users
				$salesreps = GETPOST('commercial', 'array');
				$result = $object->setSalesRep($salesreps);
				if ($result < 0) {
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}

				// Prevent thirdparty's emptying if a user hasn't rights $user->rights->categorie->lire (in such a case, post of 'custcats' is not defined)
				if (! $error && !empty($user->rights->categorie->lire)) {
					// Customer categories association
					$categories = GETPOST('custcats', 'array');
					$result = $object->setCategories($categories, 'customer');
					if ($result < 0) {
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}

					// Supplier categories association
					$categories = GETPOST('suppcats', 'array');
					$result = $object->setCategories($categories, 'supplier');
					if ($result < 0) {
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}

				// Logo/Photo save
				$dir     = $conf->societe->multidir_output[$object->entity]."/".$object->id."/logos";
				$file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
				if (GETPOST('deletephoto') && $object->logo) {
					$fileimg=$dir.'/'.$object->logo;
					$dirthumbs=$dir.'/thumbs';
					dol_delete_file($fileimg);
					dol_delete_dir_recursive($dirthumbs);
				}
				if ($file_OK) {
					if (image_format_supported($_FILES['photo']['name']) > 0) {
						dol_mkdir($dir);

						if (@is_dir($dir)) {
							$newfile=$dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
							$result = dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1);

							if (! $result > 0) {
								$errors[] = "ErrorFailedToSaveFile";
							} else {
								// Create thumbs
								$object->addThumbs($newfile);

								// Index file in database
								if (! empty($conf->global->THIRDPARTY_LOGO_ALLOW_EXTERNAL_DOWNLOAD)) {
									require_once DOL_DOCUMENT_ROOT .'/core/lib/files.lib.php';
									// the dir dirname($newfile) is directory of logo, so we should have only one file at once into index, so we delete indexes for the dir
									deleteFilesIntoDatabaseIndex(dirname($newfile), '', '');
									// now we index the uploaded logo file
									addFileIntoDatabaseIndex(dirname($newfile), basename($newfile), '', 'uploaded', 1);
								}
							}
						}
					} else {
						$errors[] = "ErrorBadImageFormat";
					}
				} else {
					switch ($_FILES['photo']['error']) {
						case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
						case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
							$errors[] = "ErrorFileSizeTooLarge";
						  break;
						case 3: //uploaded file was only partially uploaded
							$errors[] = "ErrorFilePartiallyUploaded";
						  break;
					}
				}
				// Gestion du logo de la société


				// Update linked member
				if (! $error && $object->fk_soc > 0) {
					$sql = "UPDATE ".MAIN_DB_PREFIX."adherent";
					$sql.= " SET fk_soc = NULL WHERE fk_soc = ".((int) $id);
					if (! $object->db->query($sql)) {
						$error++;
						$object->error .= $object->db->lasterror();
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}

				if (! $error && ! count($errors)) {
					if (! empty($backtopage)) {
						header("Location: ".$backtopage);
						exit;
					} else {
						header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$socid);
						exit;
					}
				} else {
					$object->id = $socid;
					$action= "edit";
				}
			}
		} else {
			$action = ($action=='add'?'create':'edit');
		}
	}

	// Delete third party
	if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->societe->supprimer) {
		$object->fetch($socid);
		$result = $object->delete($socid, $user);

		if ($result > 0) {
			setEventMessage($langs->trans("PatientDeleted", $object->name));
			header("Location: ".dol_buildpath("/cabinetmed/patients.php", 1));
			exit;
		} else {
			$langs->load("errors");
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
			$action='';
		}
	}

	// Set parent company
	if ($action == 'set_thirdparty' && $user->rights->societe->creer) {
		$object->fetch($socid);
		if (method_exists($object, 'set_parent')) {
			$result = $object->set_parent(GETPOST('editparentcompany', 'int'));
		} else {
			$result = $object->setParent(GETPOST('editparentcompany', 'int'));
		}
	}

	// Set incoterm
	if ($action == 'set_incoterms' && isModEnabled("incoterm")) {
		$object->fetch($socid);
		$result = $object->setIncoterms(GETPOST('incoterm_id', 'int'), GETPOST('location_incoterms', 'alpha'));
	}

	$id=$socid;
	$object->fetch($socid);

	// Actions to send emails
	$trigger_name='COMPANY_SENTBYMAIL';
	$paramname='socid';
	$mode='emailfromthirdparty';
	$trackid='thi'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';


	// Actions to build doc
	$id = $socid;
	$upload_dir = $conf->societe->dir_output;
	$permissiontoadd=$user->rights->societe->creer;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}



/*
 *  View
 */

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $langs->trans("ThirdParty"), $help_url);

$form = new Form($db);
$formfile = new FormFile($db);
$formadmin = new FormAdmin($db);
$formcompany = new FormCompany($db);

if ($socid > 0 && empty($object->id)) {
	$result=$object->fetch($socid);
	if ($result <= 0) dol_print_error('', $object->error);
}

$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';


if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action)) {
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------
	$objcanvas->assign_values($action, $object->id, $object->ref);	// Set value for templates
	$objcanvas->display_canvas($action);							// Show template
} else {
	dol_print_error('', 'Error this page must be called for canvas pages only');
}


// End of page
llxFooter();
$db->close();
