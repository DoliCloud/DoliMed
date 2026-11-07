<?php
/* Copyright (C) 2011-2025 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/cabinetmed/class/actions_cabinetmed.class.php
 *	\ingroup    cabinetmed
 *	\brief      File to control actions
 */
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
dol_include_once("/cabinetmed/lib/cabinetmed.lib.php");
dol_include_once("/cabinetmed/class/patient.class.php");
dol_include_once("/cabinetmed/class/cabinetmedcons.class.php");


/**
 *	Class to manage hooks for module Cabinetmed
 */
class ActionsCabinetmed
{
	public $db;
	public $error;
	public $errors=array();

	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Complete search forms
	 *
	 * @param	array	$parameters		Array of parameters
	 * @return	string					HTML content to add by hook
	 */
	public function addSearchEntry($parameters)
	{
		global $langs;

		$langs->load("cabinetmed@cabinetmed");
		$search_boxvalue = $parameters['search_boxvalue'];

		if ((float) DOL_VERSION < 8) {
			$this->results['searchintoapatient']=array('position'=>5, 'img'=>'object_patient@cabinetmed', 'label'=>$langs->trans("SearchIntoPatients", $search_boxvalue), 'text'=>img_picto('', 'user-injured', 'class="pictofixedwidth"').' '.$langs->trans("Patients", $search_boxvalue), 'url'=>dol_buildpath('/cabinetmed/patients.php', 1).'?search_all='.urlencode($search_boxvalue));
		} else {
			$this->results['searchintoapatient']=array('position'=>5, 'img'=>'object_patient@cabinetmed', 'label'=>$langs->trans("SearchIntoPatients", $search_boxvalue), 'text'=>img_picto('', 'user-injured', 'class="pictofixedwidth"').' '.$langs->trans("Patients", $search_boxvalue), 'url'=>dol_buildpath('/cabinetmed/patients.php', 1));
		}

		return 0;
	}


	/**
	 *    Execute action
	 *
	 *    @param	array	$parameters		Array of parameters
	 *    @param    mixed	$object      	Deprecated. This field is not used
	 *    @param    string	$action      	'add', 'update', 'view'
	 *    @return   int         			<0 if KO,
	 *                              		=0 if OK but we want to process standard actions too,
	 *                              		>0 if OK and we want to replace standard actions.
	 */
	public function doActions($parameters, &$object, &$action)
	{
		global $db, $langs, $conf, $user, $backtopage, $confirmduplicate;

		$ret=0;
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		// The core page of files of a thirdparty must not show the files of a patient to a user without the right to read medical data
		if (isset($parameters['currentcontext']) && $parameters['currentcontext'] == 'thirdpartydocument'
			&& is_object($object) && $object->canvas == 'patient@cabinetmed' && !$user->hasRight('cabinetmed', 'read')) {
			accessforbidden();
		}

		$arraytmp=dol_getdate(dol_now());

		// Define cabinetmed context
		$cabinetmedcontext=0;
		if ((isset($parameters['id']) || isset($parameters['socid'])) && isset($parameters['currentcontext'])
			&& in_array($parameters['currentcontext'], array(
				'agendathirdparty',
				'categorycard',
				'commcard',
				'thirdpartybancard',
				'thirdpartycard',
				'thirdpartycontact',
				'thirdpartycontactcard',
				'thirdpartyconsumption',
				'thirdpartycomm',
				'thirdpartynotification',
				'thirdpartymargins',
				'thirdpartycustomerprice',
				'thirdpartypartnership',
				'thirdpartyproject',
				'thirdpartysupplier',
				'thirdpartyticket',
				'thirdpartywebsite',
				'projectthirdparty',
				'infothirdparty',
				'consumptionthirdparty',
			)) && (empty($action) || $action == 'view' || $action == 'edit')) {
			$thirdparty=new Societe($db);
			$idthirdparty = empty($parameters['id']) ? (empty($parameters['socid']) ? 0 : $parameters['socid']) : $parameters['id'];

			if ($idthirdparty > 0) {
				$thirdparty->fetch($idthirdparty);
				if ($thirdparty->canvas == 'patient@cabinetmed') {
					$cabinetmedcontext++;
				}
			}
		}

		if (GETPOST('canvas') == 'patient@cabinetmed' || (is_object($object) && property_exists($object, 'canvas') && $object->canvas == 'patient@cabinetmed') || preg_match('/(consultationcard|exambiocard|examothercard)/', $parameters['context'])) {
			$cabinetmedcontext++;
		}

		if ($cabinetmedcontext) {
			$langs->tab_translate["ThirdParty"]=$langs->transnoentitiesnoconv("Patient");
			$langs->tab_translate["ThirdPartyName"]=$langs->transnoentitiesnoconv("PatientName");
			//$langs->tab_translate["CustomerCode"]=$langs->transnoentitiesnoconv("PatientCode");
			$langs->load("errors");
			$langs->tab_translate["ErrorBadThirdPartyName"]=$langs->transnoentitiesnoconv("ErrorBadPatientName");
		}

		require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
		require_once dol_buildpath('/cabinetmed/lib/cabinetmed.lib.php', 0);

		// Hook called when asking to add a new record
		if ($action == 'convertintopatient' && !empty($object) && in_array($object->element, array('societe', 'thirdparty')) && $user->hasRight('societe', 'creer') && $user->hasRight('cabinetmed', 'write')) {
			// The action name is not in the list of actions protected by main.inc.php, so the CSRF token is checked here
			if (!GETPOST('token', 'alpha') || !hash_equals((string) currentToken(), (string) GETPOST('token', 'alpha'))) {
				accessforbidden('Bad value for CSRF token');
			}
			$sql = 'UPDATE '.MAIN_DB_PREFIX."societe as s SET canvas = 'patient@cabinetmed' WHERE rowid = ".((int) $object->id);

			$resql = $db->query($sql);
			if ($resql) {
				header("Location: ".DOL_URL_ROOT.'/societe/card.php?socid='.$object->id);
				exit;
			} else {
				$langs->load("errors");
				$this->errors[] = $langs->trans("Error").' '.$db->lasterror();
				$ret=-1;
			}
		}

		// Hook called when asking to add a new record
		if ($action == 'add') {
			$nametocheck = GETPOST('name');
			$date = GETPOST('options_birthdate');
			//$confirmduplicate=$_POST['confirmduplicate'];

			// Check on date
			$birthdatearray = dol_cm_strptime($date, $conf->format_date_short);
			$day=(int) $birthdatearray['tm_mday'];
			$month=((int) $birthdatearray['tm_month'] + 1);
			$year=((int) $birthdatearray['tm_year'] + 1900);
			$birthdate=dol_mktime(0, 0, 0, $month, $day, $year, true, true);

			if (GETPOST('options_birthdate') && (empty($birthdatearray['tm_year']) || (empty($birthdate) && $birthdate != '0') || ($day > 31) || ($month > 12) || ($year >( $arraytmp['year']+1)))) {
				$langs->load("errors");
				$this->errors[] = $langs->trans("ErrorBadDateFormat", $date);
				$ret = -1;
			}

			// Check duplicate
			if (! $ret) {
				$sql = 'SELECT s.rowid, s.nom, s.entity, s.ape FROM '.MAIN_DB_PREFIX.'societe as s';
				$sql.= ' WHERE s.entity IN ('.getEntity('societe').')';
				$sql.= " AND s.nom = '".trim($this->db->escape($nametocheck))."'";
				if (! empty($date)) {
					$sql.= " AND (s.ape IS NULL OR s.ape = '' OR s.ape = '".trim($this->db->escape($date))."')";
				}
				$resql=$this->db->query($sql);
				if ($resql) {
					$obj=$this->db->fetch_object($resql);
					if ($obj) {
						//if (empty($confirmduplicate) || $nametocheck != $_POST['confirmduplicate'])
						if (empty($confirmduplicate)) {
							// If already exists, we want to block creation
							//$_POST['confirmduplicate']=$nametocheck;
							$langs->load("errors");
							$this->errors[] = $langs->trans("ErrorPatientNameAlreadyExists", $nametocheck);
							$ret=-1;
						}
					} else {
						// Create object, set $id to its id and return 1
						// or
						// Do something else and return 0 to use standard code to create;
						// or
						// Do nothing
					}
				} else {
					dol_print_error($this->db);
				}
			}

			if ($ret == 0 && $parameters['id'] > 0) {
				$backtopage=$_SERVER["PHP_SELF"]."?socid=".$parameters['id'];
			}
		}

		// Hook called when asking to update a record
		if ($action == 'update') {
			$nametocheck=GETPOST('name');
			$date=GETPOST('options_birthdate');
			//$confirmduplicate=$_POST['confirmduplicate'];

			// Check on date
			$birthdatearray=dol_cm_strptime($date, $conf->format_date_short);
			$day=(int) $birthdatearray['tm_mday'];
			$month=((int) $birthdatearray['tm_month'] + 1);
			$year=((int) $birthdatearray['tm_year'] + 1900);
			//var_dump($birthdatearray);
			//var_dump($date."-".$birthdate."-".$day."-".$month."-".$year);exit;
			$birthdate=dol_mktime(0, 0, 0, $month, $day, $year, true, true);
			if (GETPOST('options_birthdate') && (empty($birthdatearray['tm_year']) || empty($birthdate) || ($day > 31) || ($month > 12) || ($year > ($arraytmp['year']+1)))) {
				$langs->load("errors");
				$this->errors[]=$langs->trans("ErrorBadDateFormat", $date);
				$ret=-1;
			}
		}

		// Hook called when asking to view a record
		if ($action == 'view') {
		}

		return $ret;
	}


	/**
	 * Refuse the download, by core document.php or viewimage.php, of a file of a patient to a user without the right to read medical data
	 *
	 * @param	array<string,mixed>	$parameters		Array of parameters
	 * @param	stdClass			$object			Object (not used)
	 * @param	string				$action			Action
	 * @param	HookManager			$hookmanager	Hook manager
	 * @return	int									-1 if access refused, 0 otherwise
	 */
	public function downloadDocument($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user;

		if (!in_array($parameters['modulepart'], array('societe', 'company', 'thirdparty')) || $user->hasRight('cabinetmed', 'read')) {
			return 0;
		}
		// A file shared with a public link stays available
		if (GETPOST('hashp', 'aZ09')) {
			return 0;
		}

		// The first level of the directory of thirdparty files is the id of the thirdparty
		$tmp = explode('/', ltrim((string) $parameters['original_file'], '/'));
		$socid = (int) $tmp[0];
		if ($socid <= 0) {
			return 0;
		}

		$sql = "SELECT s.canvas FROM ".$this->db->prefix()."societe as s";
		$sql .= " WHERE s.rowid = ".((int) $socid)." AND s.entity IN (".getEntity('societe').")";
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(get_class($this)."::downloadDocument ".$this->db->lasterror(), LOG_ERR);
			$this->errors[] = $langs->trans("Error");
			return -1;
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if ($obj && $obj->canvas == 'patient@cabinetmed') {
			$this->errors[] = $langs->trans("NotEnoughPermissions");
			return -1;
		}

		return 0;
	}


	/**
	 * Add statistics line
	 *
	 * @param	array	$parameters		Array of parameters
	 * @return	int						0
	 */
	public function addStatisticLine($parameters)
	{
		global $langs, $user;

		if (!$user->hasRight('cabinetmed', 'read')) {
			return 0;
		}
		$langs->load("cabinetmed@cabinetmed");

		$board=new Cabinetmedcons($this->db);
		$board->loadStateBoard();

		$out = '';

		$out.='<a href="'.dol_buildpath('/cabinetmed/patients.php', 1).'" class="boxstatsindicator thumbstat nobold nounderline">';
		$out.='<div class="boxstats">';
		$out.='<span class="boxstatstext">';
		$out.=$langs->trans("Patients");
		$out.='</span>';
		$out.='<br>';
		//$out.='</a>';
		//$out.='<a href="'.$links[$key].'">';
		$out.='<span class="boxstatsindicator">';
		$out.=img_object("", 'company').' ';
		$out.=$board->nb['Patients'];
		$out.='</span>';
		$out.='</div>';
		$out.='</a>';

		$out.='<a href="'.dol_buildpath('/cabinetmed/listconsult.php', 1).'" class="boxstatsindicator thumbstat nobold nounderline">';
		$out.='<div class="boxstats">';
		$out.='<span class="boxstatstext">';
		$out.=$langs->trans("ConsultationsShort");
		$out.='</span>';
		$out.='<br>';
		//$out.='</a>';
		//$out.='<a href="'.$links[$key].'">';
		$out.='<span class="boxstatsindicator">';
		$out.=img_object("", 'generic').' ';
		$out.=$board->nb['Cabinetmedcons'];
		$out.='</span>';
		$out.='</div>';
		$out.='</a>';

		include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
		$this->resprints = $out;

		return 0;
	}


	/**
	 * Complete doc forms
	 *
	 * @param	array	$parameters		Array of parameters
	 * @return	void
	 */
	public function addDemoProfile($parameters)
	{
		global $conf;

		if (isModEnabled('cabinetmed')) {
			if (getDolGlobalString('CABINETMED_DEMO_URL')) {
				// $conf->global->CABINETMED_DEMO_URL = 'http://demodolimed.dolibarr.org'
				$GLOBALS['demoprofiles'][]=array(
					'default'=>'0',
					'key'=>'profdemomed',
					'lang'=>'cabinetmed@cabinetmed',
					'label'=>'DemoCabinetMed',
					'url'=>getDolGlobalString('CABINETMED_DEMO_URL'),
					'disablemodules'=>'adherent,boutique,don,externalsite',
					'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot9.png'
				);
			}
		}
	}


	/**
	 * Complete search forms
	 *
	 * @param	array	$parameters		Array of parameters
	 * @return	string					HTML content to add by hook
	 */
	/*
	function printSearchForm($parameters)
	{
		global $langs, $user, $conf;

		$searchform='';
		if (isModEnabled('cabinetmed') && $user->hasRight('cabinetmed', 'read'))
		{
			$langs->load("companies");
			$langs->load("cabinetmed@cabinetmed");
			$searchform=printSearchForm(dol_buildpath('/cabinetmed/patients.php',1), dol_buildpath('/cabinetmed/patients.php',1), img_picto('','user-injured').' '.$langs->trans("Patients"), '', 'search_nom');
		}
		$this->resprints = $searchform;

		return 0;
	}*/

	/**
	 * Add fields into tr form of objects
	 *
	 * @param	array	$parameters		Array of parameters
	 * @param   mixed	$object      	Object
	 * @param   string	$action      	'add', 'update', 'view'
	 * @param   string	$hookmanager  	'add', 'update', 'view'
	 * @return	string					HTML content to add by hook
	 */
	public function formObjectOptions($parameters, &$object, &$action, &$hookmanager)
	{
		//global $langs, $user, $conf;
	}


	/**
	 * Add more actions buttons
	 *
	 * @param	array	$parameters		Array of parameters
	 * @param   mixed	$object      	Object
	 * @param   string	$action      	'add', 'update', 'view'
	 * @param   string	$hookmanager  	'add', 'update', 'view'
	 * @return	string					HTML content to add by hook
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, &$hookmanager)
	{
		global $langs, $user;

		if ($parameters['currentcontext'] == 'xxx' && !empty($object) && !empty($object->societe->id) && $object->societe->id > 0 && ! empty($object->societe->canvas)) {
			if ($object->societe->canvas == 'patient@cabinetmed') {
				if ($action != 'edit') {
					print dolGetButtonAction($langs->trans('NewConsult'), $langs->trans('NewConsult'), 'default', dol_buildpath('/cabinetmed/consultations.php', 1).'?socid='.$object->societe->id.'&action=create&fk_agenda='.$object->id, '', $user->hasRight('societe', 'creer'));
				}
			}
		}

		if ($parameters['currentcontext'] == 'thirdpartycard' && !empty($object) && $object->canvas != 'patient@cabinetmed') {
			if ($action != 'edit') {
				print dolGetButtonAction($langs->transnoentitiesnoconv('ConvertIntoPatientDesc'), img_picto('', 'user-injured', 'class="pictofixedwidth"').$langs->trans('ConvertIntoPatient'), 'default', dol_buildpath('/societe/card.php', 1).'?socid='.$object->id.'&action=convertintopatient&token='.newToken(), '', $user->hasRight('societe', 'creer') && $user->hasRight('cabinetmed', 'write'));
			}
		}
	}


	/**
	 * Complete doc forms
	 *
	 * @param	array	$parameters		Array of parameters
	 * @param	Object	$object			Object
	 * @return	int						0 if nothing done, 1 to replace, -1 if KO
	 */
	public function formBuilddocOptions($parameters, $object)
	{
		global $langs, $user, $conf, $form;

		if (empty($parameters['modulepart']) || $parameters['modulepart'] != 'company') return 0;	// Add nothing

		include_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';
		$modellist=ModeleThirdPartyDoc::liste_modeles($this->db);

		if ($object->canvas != 'patient@cabinetmed') return 0;

		if (! preg_match('/documentcabinetmed/', $parameters['context'])) {
			return 0;
		}

		$out='';
		$out.='<tr>';
		$out.='<td align="left" colspan="4" valign="top" class="formdoc">';

		// Add javascript to disable/enabled button
		if (is_array($modellist) && count($modellist) > 0) {
			$out.="\n".'<script type="text/javascript" language="javascript">';
			$out.='jQuery(document).ready(function () {';
			$out.='    function initbutton(param) {';
			$out.='        if (param >= 0) { jQuery("#builddoc_generatebutton").removeAttr(\'disabled\'); }';
			$out.='        else { jQuery("#builddoc_generatebutton").attr(\'disabled\',true); }';
			$out.='    }';
			$out.='    initbutton(jQuery("#idconsult").val()); ';
			$out.='    jQuery("#idconsult").change(function() { initbutton(jQuery(this).val()); });';
			$out.='});';
			$out.='</script>'."\n";
		} else {
			$langs->load("errors");
			$out.=' &nbsp; '.img_warning($langs->transnoentitiesnoconv("ErrorModuleSetupNotComplete")).' &nbsp; ';
		}

		$firstid=0;
		$out.='<font class="fieldrequired">'.$langs->trans("Consultation").':</font> ';
		$array_consult=array();
		$sql='SELECT rowid, datecons as date FROM '.MAIN_DB_PREFIX.'cabinetmed_cons where fk_soc='.((int) $parameters['socid']);
		$sql.=' ORDER BY datecons DESC, rowid DESC';
		$resql=$this->db->query($sql);
		if ($resql) {
			$num=$this->db->num_rows($resql);
			$i=0;
			while ($i < $num) {
				$obj=$this->db->fetch_object($resql);
				$array_consult[$obj->rowid]=sprintf("%08d", $obj->rowid).' - '.dol_print_date($this->db->jdate($obj->date), 'day');
				if (empty($firstid)) $firstid=$obj->rowid;
				$i++;
			}
		} else dol_print_error($this->db);
		$out.=$form->selectarray('idconsult', $array_consult, $firstid, 1);
		//print '</td>';
		//print '<td class="center">';

		$out.=' &nbsp; &nbsp; &nbsp; ';

		$out.=$langs->trans("ResultExamBio").': ';
		$array_consult=array();
		$sql='SELECT rowid, dateexam as date FROM '.MAIN_DB_PREFIX.'cabinetmed_exambio where fk_soc='.((int) $parameters['socid']);
		$sql.=' ORDER BY dateexam DESC, rowid DESC';
		$resql=$this->db->query($sql);
		if ($resql) {
			$num=$this->db->num_rows($resql);
			$i=0;
			while ($i < $num) {
				$obj=$this->db->fetch_object($resql);
				$array_consult[$obj->rowid]=dol_print_date($this->db->jdate($obj->date), 'day');
				$i++;
			}
		} else dol_print_error($this->db);
		$out.=$form->selectarray('idbio', $array_consult, GETPOST('idbio')?GETPOST('idbio'):'', 1);
		//$out.= '</td>';
		//$out.= '<td class="center">';

		$out.=' &nbsp; &nbsp; &nbsp; ';

		$out.=$langs->trans("ResultExamAutre").': ';
		$array_consult=array();
		$sql='SELECT rowid, dateexam as date FROM '.MAIN_DB_PREFIX.'cabinetmed_examaut where fk_soc='.((int) $parameters['socid']);
		$sql.=' ORDER BY dateexam DESC, rowid DESC';
		$resql=$this->db->query($sql);
		if ($resql) {
			$num=$this->db->num_rows($resql);
			$i=0;
			while ($i < $num) {
				$obj=$this->db->fetch_object($resql);
				$array_consult[$obj->rowid]=dol_print_date($this->db->jdate($obj->date), 'day');
				$i++;
			}
		} else dol_print_error($this->db);
		$out.=$form->selectarray('idradio', $array_consult, GETPOST('idradio')?GETPOST('idradio'):'', 1);

		if (! is_array($modellist) || count($modellist) == 0) {
			$langs->load("errors");
			$out.=' &nbsp; '.img_warning($langs->transnoentitiesnoconv("ErrorModuleSetupNotComplete")).' &nbsp; ';
		}

		$out.='</td>';
		$out.='</tr>';

		$out.='<tr><td colspan="4" valign="top" class="formdoc">';
		$out.=$langs->trans("Comment").': ';
		$out.= '<textarea name="doc_comment" style="width: 95%" rows="'.ROWS_6.'">'.GETPOST('doc_comment').'</textarea>';
		$out.='</td></tr>';

		$this->resprints = $out;

		return 0;
	}


	/**
	 * Complete array with linkto
	 *
	 * @param	array	$parameters		Array of parameters
	 * @param   mixed	$object      	Object
	 * @param   string	$action      	'add', 'update', 'view'
	 * @param   string	$hookmanager  	'add', 'update', 'view'
	 * @return	string					HTML content to add by hook
	 */
	public function showLinkToObjectBlock($parameters, &$object, &$action, &$hookmanager)
	{
		global $langs, $user, $conf, $db;

		$langs->load("cabinetmed@cabinetmed");
		$this->results = array('cabinetmed_cabinetmedcons'=>array('enabled'=>isModEnabled('cabinetmed'), 'perms'=>(int) $user->hasRight('cabinetmed', 'read'), 'label'=>'LinkToConsultation', 'sql'=>"SELECT s.rowid as socid, s.nom as name, t.rowid, t.rowid as ref, '' as ref_supplier, (".$db->ifsql('t.montant_cheque IS NULL', '0', 't.montant_cheque')." + ".$db->ifsql('t.montant_carte IS NULL', '0', 't.montant_carte')." + ".$db->ifsql('t.montant_espece IS NULL', '0', 't.montant_espece')." + ".$db->ifsql('t.montant_tiers IS NULL', '0', 't.montant_tiers').") as total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."cabinetmed_cons as t WHERE t.fk_soc = s.rowid AND t.fk_soc = ".((int) $object->thirdparty->id)));
	}

	/**
	 * Complete array with linkto
	 *
	 * @param	array	$parameters		Array of parameters
	 * @param   mixed	$object      	Object
	 * @param   string	$action      	'add', 'update', 'view'
	 * @param   string	$hookmanager  	'add', 'update', 'view'
	 * @return	string					HTML content to add by hook
	 */
	public function showLinkedObjectBlock($parameters, &$object, &$action, &$hookmanager)
	{
		global $langs, $user, $conf, $db;

		/* not required. standard showLinkedObjectBlock already load correctly record for cabinetmed_cabinetmedcons

		$newentry=array();

		$consultation = new CabinetmedCons($db);
		$consultation->fetch()
		$newentry['consultation']

		if (count($newentry))
		{
			$object->linkedObjects = array_merge($object->linkedObjects, $newentry);
		}*/

		return 0;
	}

	/**
	 * Complete object before generationg PDF
	 *
	 * @param	array	$parameters		Array of parameters
	 * @param   mixed	$object      	Object
	 * @param   string	$action      	'add', 'update', 'view'
	 * @param   string	$hookmanager  	'add', 'update', 'view'
	 * @return	string					HTML content to add by hook
	 */
	public function beforePDFCreation($parameters, &$object, &$action, &$hookmanager)
	{
		global $langs, $user, $conf, $db;

		//if (! in_array($object->element, array('fichinter','facture','invoice','order','commande','propal'))) return;


		//$object->note_public=dol_concatdesc($text,$object->note_public);
	}


	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param	array	$parameters		Array of parameters
	 * @param   mixed	$object      	Object patient we keep
	 * @param   string	$action      	'add', 'update', 'view'
	 * @param   string	$hookmanager  	'add', 'update', 'view'
	 * @return bool						True=SQL success, False=SQL error
	 */
	public function replaceThirdparty($parameters, &$object, &$action, &$hookmanager)
	{
		global $db;

		$origin_id = $parameters['soc_origin'];
		$dest_id = $parameters['soc_dest'];

		$oldpatienttodelete = new Patient($db);
		$oldpatienttodelete->fetch($origin_id);


		$ok = 1;

		if ($ok) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."cabinetmed_cons SET fk_soc = ".((int) $dest_id)." WHERE fk_soc = ".((int) $origin_id);
			if (!$db->query($sql)) {
				$ok=0;
			}
		}

		if ($ok) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."cabinetmed_examaut SET fk_soc = ".((int) $dest_id)." WHERE fk_soc = ".((int) $origin_id);
			if (!$db->query($sql)) {
				$ok=0;
			}
		}

		if ($ok) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."cabinetmed_exambio SET fk_soc = ".((int) $dest_id)." WHERE fk_soc = ".((int) $origin_id);
			if (!$db->query($sql)) {
				$ok=0;
			}
		}

		if ($ok) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."cabinetmed_patient SET note_antemed = '".$db->escape($oldpatienttodelete->note_antemed)."' WHERE rowid = ".((int) $dest_id)." AND (note_antemed IS NULL OR note_antemed = '')";
			if (!$db->query($sql)) {
				$ok=0;
			}
		}
		if ($ok) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."cabinetmed_patient SET note_traitspec = '".$db->escape($oldpatienttodelete->note_traitspec)."' WHERE rowid = ".((int) $dest_id)." AND (note_traitspec IS NULL OR note_traitspec = '')";
			if (!$db->query($sql)) {
				$ok=0;
			}
		}


		if ($ok) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Overloading the loadDataForCustomReports function : returns data to complete the customreport tool
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function loadDataForCustomReports($parameters, &$action, $hookmanager)
	{
		global $langs;

		$langs->load("cabinetmed@cabinetmed");

		$this->results = array();

		$head = array();
		$h = 0;

		/*
		if ($parameters['tabfamily'] == 'sellyoursaas') {
			//$this->results['modenotusedforlist'] = 1;
			$head[$h][0] = dol_buildpath('/sellyoursaas/backoffice/index.php', 1);
			$head[$h][1] = $langs->trans("Home");
			$head[$h][2] = 'home';
			$h++;

			if (!getDolGlobalString('SELLYOURSAAS_OBJECT_DEPLOYMENT_SERVER_MIGRATION')) {
				$head[$h][0] = dol_buildpath('/sellyoursaas/backoffice/deployment_servers.php', 1);
				$head[$h][1] = $langs->trans("DeploymentServers");
				$head[$h][2] = 'deploymentservers';
				$h++;
			} else {
				$head[$h][0] = '/custom/sellyoursaas/deploymentserver_list.php';
				$head[$h][0] = dol_buildpath('/sellyoursaas/deploymentserver_list.php', 1);
				$head[$h][1] = $langs->trans("DeploymentServers");
				$head[$h][2] = 'deploymentservers';
				$h++;
			}

			$head[$h][0] = dol_buildpath('/sellyoursaas/backoffice/setup_antispam.php', 1);
			$head[$h][1] = $langs->trans("AntiSpam");
			$head[$h][2] = 'antispam';
			$h++;

			$this->results['title'] = $langs->trans("DoliCloudArea");
			$this->results['picto'] = 'sellyoursaas@sellyoursaas';
		}

		if ($parameters['tabfamily'] == 'sellyoursaas') {
			$head[$h][0] = 'customreports.php?objecttype='.$parameters['objecttype'].(empty($parameters['tabfamily']) ? '' : '&tabfamily='.$parameters['tabfamily']);
			$head[$h][1] = $langs->trans("CustomReports");
			$head[$h][2] = 'customreports';
			$h++;
		}

		if ($parameters['tabfamily'] == 'sellyoursaas') {
			$head[$h][0] = dol_buildpath('/sellyoursaas/backoffice/notes.php', 1);
			$head[$h][1] = $langs->trans("Notes");
			$head[$h][2] = 'notes';
			$h++;
		}
		*/

		$this->results['head'] = $head;

		$arrayoftypes = array(
			'consultations@cabinetmed' => array('label' => 'Consultations', 'picto'=>'label', 'ObjectClassName' => 'CabinetmedCons', 'enabled' => isModEnabled('cabinetmed'), 'ClassPath' => "/cabinetmed/class/cabinetmecons.class.php", 'langs'=>'cabinetmed@cabinetmed')
		);
		$this->results['arrayoftype'] = $arrayoftypes;

		return 0;
	}


	/**
	 * Declare the methods of the REST API of the module (class/api_cabinetmed.class.php) that the AI assistant
	 * may use as tools (hook 'addMcpApiBridgeEnrichments' of the 'aimcp' context).
	 *
	 * @param   array<string,mixed> $parameters 	Hook metadatas (db, user, conf)
	 * @param   ToolApiBridge   	$object         The AI API bridge
	 * @param   string          	$action         Current action
	 * @param   HookManager     	$hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                 	            0 on success
	 */
	public function addMcpApiBridgeEnrichments($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		// Translation keys of the write confirmations are read from the module language file
		$langs->load("cabinetmed@cabinetmed");

		$readright = array('cabinetmed', 'read');
		$writeright = array('cabinetmed', 'write');
		$patientparam = "Patient: rowid, or the name of the patient.";
		$listparams = array(
			'socid' => "Rowid of the patient to restrict to. Omit (0) for all patients.",
		);
		$datesdoc = " Dates are returned as Unix timestamps.";

		$methods = array(
			'listPatients' => array(
				'suffix' => 'patients_list',
				'right' => $readright,
				'default_properties' => 'id,name,code_client,zip,town,phone,phone_mobile,email',
				'description' => "List / search the patients of the medical office. Search by name with sqlfilters \"(t.nom:like:'%smith%')\". The rowid ('id') of a patient is needed by the other cabinetmed tools.".$datesdoc,
			),
			'getPatient' => array(
				'suffix' => 'patient_get',
				'right' => $readright,
				'description' => "Get one patient of the medical office with its contact details and its antecedents.",
				'params' => array('id' => "Rowid of the patient."),
			),
			'createPatient' => array(
				'suffix' => 'patient_create',
				'description' => "Create a patient in the medical office. Give at least its name. The user confirms before anything is written.",
				'write' => array(
					'preview' => 'CabinetmedAIPreviewCreatePatient',
					'required' => array('name'),
					'args' => array('name'),
					'right' => $writeright,
					'read_right' => array('societe', 'creer'),
				),
				'params' => array(
					'name' => "Full name of the patient (last name and first name).",
					'address' => "Street address.",
					'zip' => "Postal code.",
					'town' => "Town.",
					'phone' => "Phone.",
					'phone_mobile' => "Mobile phone.",
					'email' => "Email.",
					'tva_intra' => "Social security number.",
					'idprof1' => "Height.",
					'idprof2' => "Weight.",
					'note_private' => "Private note.",
				),
			),
			'getAntecedents' => array(
				'suffix' => 'antecedents_get',
				'right' => $readright,
				'description' => "Get the antecedents of a patient: medical (note_antemed), general surgery (note_antechirgen), orthopedic surgery (note_antechirortho), rheumatology (note_anterhum), allergies (note_traitallergie), current treatments (note_traitclass), intolerances (note_traitintol), specific treatments (note_traitspec), each with its alert flag (alert_xxx).",
				'params' => array('id' => "Rowid of the patient."),
			),
			'updateAntecedents' => array(
				'suffix' => 'antecedents_set',
				'description' => "Create or modify the antecedents of a patient. Give 'id' (rowid of the patient) and ONLY the fields to change, with their full new text (read them first with the antecedents_get tool to append to an existing text). The user confirms before anything is written.",
				'write' => array(
					'preview' => 'CabinetmedAIPreviewSetAntecedents',
					'required' => array('id'),
					'args' => array('id'),
					'args_objects' => array('id' => 'Societe'),
					'right' => $writeright,
					'read_right' => array('societe', 'creer'),
				),
				'params' => array(
					'id' => "Rowid of the patient.",
					'note_antemed' => "Medical antecedents.",
					'note_antechirgen' => "General surgery antecedents.",
					'note_antechirortho' => "Orthopedic surgery antecedents.",
					'note_anterhum' => "Rheumatology antecedents.",
					'note_traitallergie' => "Allergies.",
					'note_traitclass' => "Current treatments.",
					'note_traitintol' => "Intolerances.",
					'note_traitspec' => "Specific treatments.",
					'alert_antemed' => "1 to show an alert on the medical antecedents, 0 for none.",
					'alert_traitallergie' => "1 to show an alert on the allergies, 0 for none.",
					'alert_traitintol' => "1 to show an alert on the intolerances, 0 for none.",
				),
			),
			'listConsultations' => array(
				'suffix' => 'consultations_list',
				'right' => $readright,
				'default_properties' => 'id,socid,patient_name,datecons,typevisit,motifconsprinc,diaglesprinc,traitementprescrit',
				'description' => "List the consultations, of one patient (socid) or of all patients, latest first.".$datesdoc,
				'params' => $listparams,
			),
			'getConsultation' => array(
				'suffix' => 'consultation_get',
				'right' => $readright,
				'description' => "Get one consultation: reasons, diagnoses, history of the disease, clinical examination, prescribed examinations and treatments.".$datesdoc,
				'params' => array('id' => "Rowid of the consultation."),
			),
			'createConsultation' => array(
				'suffix' => 'consultation_create',
				'description' => "Record a consultation for a patient. Give socid (rowid or name of the patient) and datecons. The user confirms before anything is written.",
				'write' => array(
					'preview' => 'CabinetmedAIPreviewAddConsultation',
					'required' => array('socid', 'datecons'),
					'thirdparty' => 'socid',
					'args' => array('socid', 'datecons'),
					'right' => $writeright,
				),
				'params' => array(
					'socid' => $patientparam,
					'datecons' => "Date of the consultation, YYYY-MM-DD.",
					'typevisit' => "Type of visit: CS (consultation, default), CS2 (second consultation), CCAM (technical act).",
					'motifconsprinc' => "Main reason for the consultation.",
					'motifconssec' => "Other reasons for the consultation.",
					'diaglesprinc' => "Main diagnosis.",
					'diaglessec' => "Other diagnoses.",
					'hdm' => "History of the disease.",
					'examenclinique' => "Clinical examination.",
					'examenprescrit' => "Prescribed examinations.",
					'traitementprescrit' => "Prescribed treatment.",
					'comment' => "Comment.",
				),
			),
			'listBiologies' => array(
				'suffix' => 'biologies_list',
				'right' => $readright,
				'default_properties' => 'id,socid,patient_name,dateexam,resultat,conclusion',
				'description' => "List the biological examinations (biologies), of one patient (socid) or of all patients, latest first.".$datesdoc,
				'params' => $listparams,
			),
			'getBiology' => array(
				'suffix' => 'biology_get',
				'right' => $readright,
				'description' => "Get one biological examination (biology) with its results and conclusion.".$datesdoc,
				'params' => array('id' => "Rowid of the biology."),
			),
			'createBiology' => array(
				'suffix' => 'biology_create',
				'description' => "Record a biological examination (biology) for a patient. Give socid (rowid or name of the patient) and dateexam. The user confirms before anything is written.",
				'write' => array(
					'preview' => 'CabinetmedAIPreviewAddBiology',
					'required' => array('socid', 'dateexam'),
					'thirdparty' => 'socid',
					'args' => array('socid', 'dateexam'),
					'right' => $writeright,
				),
				'params' => array(
					'socid' => $patientparam,
					'dateexam' => "Date of the examination, YYYY-MM-DD.",
					'resultat' => "Results.",
					'conclusion' => "Conclusion.",
					'comment' => "Comment.",
				),
			),
			'listExplorations' => array(
				'suffix' => 'exams_list',
				'right' => $readright,
				'default_properties' => 'id,socid,patient_name,dateexam,examprinc,concprinc',
				'description' => "List the examinations (explorations: radiology, scanner, MRI, ECG, other non biological exams), of one patient (socid) or of all patients, latest first.".$datesdoc,
				'params' => $listparams,
			),
			'getExploration' => array(
				'suffix' => 'exam_get',
				'right' => $readright,
				'description' => "Get one examination (exploration: radiology or other non biological exam) with its conclusions.".$datesdoc,
				'params' => array('id' => "Rowid of the examination."),
			),
			'createExploration' => array(
				'suffix' => 'exam_create',
				'description' => "Record an examination (exploration: radiology or other non biological exam) for a patient. Give socid (rowid or name of the patient) and dateexam. The user confirms before anything is written.",
				'write' => array(
					'preview' => 'CabinetmedAIPreviewAddExploration',
					'required' => array('socid', 'dateexam'),
					'thirdparty' => 'socid',
					'args' => array('socid', 'dateexam'),
					'right' => $writeright,
				),
				'params' => array(
					'socid' => $patientparam,
					'dateexam' => "Date of the examination, YYYY-MM-DD.",
					'examprinc' => "Main examination.",
					'examsec' => "Other examinations.",
					'concprinc' => "Main conclusion.",
					'concsec' => "Other conclusions.",
				),
			),
		);

		// Keyed by module, so the bridge reads one list of endpoint entries per module
		$this->results = array(
			'cabinetmed' => array(
				'cabinetmed' => array(
					'label' => 'medical office (patients, antecedents, consultations, biologies, examinations)',
					'methods' => $methods,
				),
			),
		);

		return 0;
	}
}
