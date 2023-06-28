<?php
/* Copyright (C) 2011-2022 Laurent Destailleur  <eldy@users.sourceforge.net>
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
dol_include_once("/cabinetmed/class/cabinetmedcons.class.php");


/**
 *	Class to manage hooks for module Cabinetmed
 */
class ActionsCabinetmed
{
	var $db;
	var $error;
	var $errors=array();

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
	function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Complete search forms
	 *
	 * @param	array	$parameters		Array of parameters
	 * @return	string					HTML content to add by hook
	 */
	function addSearchEntry($parameters)
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
	function doActions($parameters, &$object, &$action)
	{
		global $db,$langs,$conf,$backtopage;

		$ret=0;
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		$arraytmp=dol_getdate(dol_now());

		// Define cabinetmed context
		$cabinetmedcontext=0;
		if ((isset($parameters['id']) || isset($parameters['socid'])) && isset($parameters['currentcontext'])
			&& in_array($parameters['currentcontext'], array('agendathirdparty','categorycard','commcard','thirdpartycard','thirdpartycontactcard','thirdpartycomm','thirdpartysupplier','projectthirdparty','thirdpartypartnership','infothirdparty','thirdpartybancard','consumptionthirdparty','thirdpartynotification','thirdpartymargins','thirdpartycustomerprice','thirdpartyticket')) && (empty($action) || $action == 'view' || $action == 'edit')) {
			$thirdparty=new Societe($db);
			$idthirdparty = empty($parameters['id']) ? (empty($parameters['socid']) ? 0 : $parameters['socid']) : $parameters['id'];

			if ($idthirdparty > 0) {
				$thirdparty->fetch($idthirdparty);
				if ($thirdparty->canvas == 'patient@cabinetmed') {
					$cabinetmedcontext++;
				}
			}
		}

		if (GETPOST('canvas') == 'patient@cabinetmed' || preg_match('/(consultationcard|exambiocard|examothercard)/', $parameters['context'])) {
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
		if ($action == 'convertintopatient' && !empty($object) && in_array($object->element, array('societe', 'thirdparty'))) {
			$sql = 'UPDATE '.MAIN_DB_PREFIX."societe as s SET canvas = 'patient@cabinetmed' WHERE rowid = ".$object->id;

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
			$nametocheck=GETPOST('name');
			$date=GETPOST('options_birthdate');
			//$confirmduplicate=$_POST['confirmduplicate'];

			// Check on date
			$birthdatearray=dol_cm_strptime($date, $conf->format_date_short);
			$day=(int) $birthdatearray['tm_mday'];
			$month=((int) $birthdatearray['tm_month'] + 1);
			$year=((int) $birthdatearray['tm_year'] + 1900);
			$birthdate=dol_mktime(0, 0, 0, $month, $day, $year, true, true);
			if (GETPOST('options_birthdate') && (empty($birthdatearray['tm_year']) || (empty($birthdate) && $birthdate != '0') || ($day > 31) || ($month > 12) || ($year >( $arraytmp['year']+1)))) {
				$langs->load("errors");
				$this->errors[] = $langs->trans("ErrorBadDateFormat", $date);
				$ret=-1;
			}

			// Check duplicate
			if (! $ret) {
				$sql = 'SELECT s.rowid, s.nom, s.entity, s.ape FROM '.MAIN_DB_PREFIX.'societe as s';
				$sql.= ' WHERE s.entity = '.((int) $conf->entity);
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
							$this->errors[]=$langs->trans("ErrorPatientNameAlreadyExists", $nametocheck);
							$ret=-1;
						}
					} else {
						// Create object, set $id to its id and return 1
						// or
						// Do something else and return 0 to use standard code to create;
						// or
						// Do nothing
					}
				} else dol_print_error($this->db);
			}

			if ($ret == 0 && $parameters['id'] > 0) $backtopage=$_SERVER["PHP_SELF"]."?socid=".$parameters['id'];
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
	 * Add statistics line
	 *
	 * @param	array	$parameters		Array of parameters
	 * @return	void
	 */
	function addStatisticLine($parameters)
	{
		global $langs;
		$langs->load("cabinetmed@cabinetmed");

		$board=new Cabinetmedcons($this->db);
		$board->load_state_board();

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
		if (versioncompare(versiondolibarrarray(), array(4,0,-4)) >= 0) $this->resprints=$out;
		else print $out;

		return 0;
	}


	/**
	 * Complete doc forms
	 *
	 * @param	array	$parameters		Array of parameters
	 * @return	void
	 */
	function addDemoProfile($parameters)
	{
		global $conf;

		if (isModEnabled('cabinetmed')) {
			if (! empty($conf->global->CABINETMED_DEMO_URL)) {
				// $conf->global->CABINETMED_DEMO_URL = 'http://demodolimed.dolibarr.org'
				$GLOBALS['demoprofiles'][]=array(
					'default'=>'0',
					'key'=>'profdemomed',
					'lang'=>'cabinetmed@cabinetmed',
					'label'=>'DemoCabinetMed',
					'url'=>$conf->global->CABINETMED_DEMO_URL,
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
	function formObjectOptions($parameters, &$object, &$action, &$hookmanager)
	{
		global $langs, $user, $conf;
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
	function addMoreActionsButtons($parameters, &$object, &$action, &$hookmanager)
	{
		global $langs, $user, $conf;

		if ($parameters['currentcontext'] == 'xxx' && !empty($object) && !empty($object->societe->id) && $object->societe->id > 0 && ! empty($object->societe->canvas)) {
			if ($object->societe->canvas == 'patient@cabinetmed') {
				if ($action != 'edit') {
					print dolGetButtonAction($langs->trans('NewConsult'), $langs->trans('NewConsult'), 'default', dol_buildpath('/cabinetmed/consultations.php', 1).'?socid='.$object->societe->id.'&action=create&fk_agenda='.$object->id, '', $user->hasRight('societe', 'creer'));
				}
			}
		}

		if ($parameters['currentcontext'] == 'thirdpartycard' && !empty($object) && $object->canvas != 'patient@cabinetmed') {
			if ($action != 'edit') {
				print dolGetButtonAction($langs->transnoentitiesnoconv('ConvertIntoPatientDesc'), img_picto('', 'user-injured', 'class="pictofixedwidth"').$langs->trans('ConvertIntoPatient'), 'default', dol_buildpath('/societe/card.php', 1).'?socid='.$object->id.'&action=convertintopatient&token='.newToken(), '', $user->hasRight('societe', 'creer'));
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
	function formBuilddocOptions($parameters, $object)
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
		$sql='SELECT rowid, datecons as date FROM '.MAIN_DB_PREFIX.'cabinetmed_cons where fk_soc='.$parameters['socid'];
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
		$sql='SELECT rowid, dateexam as date FROM '.MAIN_DB_PREFIX.'cabinetmed_exambio where fk_soc='.$parameters['socid'];
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
		$sql='SELECT rowid, dateexam as date FROM '.MAIN_DB_PREFIX.'cabinetmed_examaut where fk_soc='.$parameters['socid'];
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
		$out.= '<textarea name="doc_comment" style="width: 95%" rows="8">'.(GETPOST('doc_comment')?GETPOST('doc_comment'):'').'</textarea>';
		//$out.='<input type="text" name="doc_comment" size="90" value="'.(GETPOST('doc_comment')?GETPOST('doc_comment'):'').'">';
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
	function showLinkToObjectBlock($parameters, &$object, &$action, &$hookmanager)
	{
		global $langs, $user, $conf, $db;

		$langs->load("cabinetmed@cabinetmed");
		$this->results = array('cabinetmed_cabinetmedcons'=>array('enabled'=>isModEnabled('cabinetmed'), 'perms'=>1, 'label'=>'LinkToConsultation', 'sql'=>"SELECT s.rowid as socid, s.nom as name, t.rowid, t.rowid as ref, '' as ref_supplier, (".$db->ifsql('t.montant_cheque IS NULL', '0', 't.montant_cheque')." + ".$db->ifsql('t.montant_carte IS NULL', '0', 't.montant_carte')." + ".$db->ifsql('t.montant_espece IS NULL', '0', 't.montant_espece')." + ".$db->ifsql('t.montant_tiers IS NULL', '0', 't.montant_tiers').") as total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."cabinetmed_cons as t WHERE t.fk_soc = s.rowid AND t.fk_soc = ".$object->thirdparty->id));
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
	function showLinkedObjectBlock($parameters, &$object, &$action, &$hookmanager)
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
	function beforePDFCreation($parameters, &$object, &$action, &$hookmanager)
	{
		global $langs, $user, $conf, $db;

		//if (! in_array($object->element, array('fichinter','facture','invoice','order','commande','propal'))) return;


		//$object->note_public=dol_concatdesc($text,$object->note_public);
	}
}
