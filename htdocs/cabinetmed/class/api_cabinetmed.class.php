<?php
/* Copyright (C) 2026 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \file    htdocs/cabinetmed/class/api_cabinetmed.class.php
 * \ingroup cabinetmed
 * \brief   REST API of the medical office: patients, antecedents, consultations, biologies and explorations.
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
dol_include_once('/cabinetmed/class/patient.class.php');
dol_include_once('/cabinetmed/class/cabinetmedcons.class.php');
dol_include_once('/cabinetmed/class/cabinetmedexambio.class.php');
dol_include_once('/cabinetmed/class/cabinetmedexamother.class.php');
dol_include_once('/cabinetmed/lib/cabinetmed.lib.php');


/**
 * API class for the medical office (patients, consultations, biologies, explorations)
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class CabinetmedApi extends DolibarrApi
{
	/**
	 * Canvas of the third parties that are patients
	 */
	const PATIENT_CANVAS = 'patient@cabinetmed';

	/**
	 * Patient fields that may be set on creation
	 *
	 * @var string[]
	 */
	public static $patientFields = array('name', 'name_alias', 'address', 'zip', 'town', 'country_id', 'phone', 'phone_mobile', 'email', 'tva_intra', 'idprof1', 'idprof2', 'note_public', 'note_private');

	/**
	 * Antecedent text fields of a patient (table llx_cabinetmed_patient)
	 *
	 * @var string[]
	 */
	public static $antecedentFields = array('note_antemed', 'note_antechirgen', 'note_antechirortho', 'note_anterhum', 'note_traitallergie', 'note_traitclass', 'note_traitintol', 'note_traitspec');

	/**
	 * Consultation fields that may be set on creation
	 *
	 * @var string[]
	 */
	public static $consultationFields = array('typevisit', 'typepriseencharge', 'motifconsprinc', 'diaglesprinc', 'motifconssec', 'diaglessec', 'hdm', 'examenclinique', 'examenprescrit', 'traitementprescrit', 'comment');

	/**
	 * Biology fields that may be set on creation
	 *
	 * @var string[]
	 */
	public static $biologyFields = array('resultat', 'conclusion', 'comment');

	/**
	 * Exploration (radio/other exam) fields that may be set on creation
	 *
	 * @var string[]
	 */
	public static $explorationFields = array('examprinc', 'examsec', 'concprinc', 'concsec');

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}


	/**
	 * List patients
	 *
	 * Return the third parties that are patients of the medical office.
	 *
	 * @param	string	$sortfield	Sort field
	 * @param	string	$sortorder	Sort order
	 * @param	int		$limit		Limit for list
	 * @param	int		$page		Page number
	 * @param	string	$sqlfilters	Other criteria to filter answers separated by a comma. Syntax example "(t.nom:like:'Smith%') and (t.datec:>:'20160101')"
	 * @param	string	$properties	Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return	array				Array of patients
	 * @phan-return array<int,stdClass>
	 * @phpstan-return array<int,stdClass>
	 *
	 * @url	GET patients
	 *
	 * @throws RestException 400 Bad value for sqlfilters
	 * @throws RestException 403 Not allowed
	 * @throws RestException 503 System error
	 */
	public function listPatients($sortfield = "t.nom", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		$this->checkRight('read');

		$sql = "SELECT t.rowid as id, t.nom as name, t.name_alias, t.code_client, t.address, t.zip, t.town, t.phone, t.phone_mobile, t.email,";
		$sql .= " t.tva_intra, t.siren as idprof1, t.siret as idprof2, t.status, t.datec";
		$sql .= " FROM ".$this->db->prefix()."societe as t";
		$sql .= " WHERE t.entity IN (".getEntity('societe').")";
		$sql .= " AND t.canvas = '".$this->db->escape(self::PATIENT_CANVAS)."'";
		$sql .= $this->sqlPatientRestriction('t.rowid');
		$sql .= $this->sqlFilters($sqlfilters);

		return $this->fetchList($sql, $sortfield, $sortorder, $limit, $page, $properties, array('datec'));
	}

	/**
	 * Get a patient
	 *
	 * Return the patient (third party with the patient canvas) with its antecedents.
	 *
	 * @param	int		$id		ID of the patient (rowid of the third party)
	 * @return	Object			Patient with cleaned properties
	 *
	 * @url	GET patients/{id}
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function getPatient($id)
	{
		$this->checkRight('read');

		return $this->_cleanObjectDatas($this->fetchPatient($id));
	}

	/**
	 * Create a patient
	 *
	 * Create a third party with the patient canvas. A customer code is generated automatically.
	 *
	 * @param	array	$request_data	Patient data: name (required), name_alias, address, zip, town, country_id, phone, phone_mobile, email, tva_intra (social security number), idprof1 (height), idprof2 (weight), note_public, note_private
	 * @phan-param ?array<string,mixed> $request_data
	 * @phpstan-param ?array<string,mixed> $request_data
	 * @return	int						ID of the patient
	 *
	 * @url	POST patients
	 *
	 * @throws RestException 400 Bad request
	 * @throws RestException 403 Not allowed
	 * @throws RestException 500 System error
	 */
	public function createPatient($request_data = null)
	{
		$this->checkRight('write');
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403, 'Not allowed to create third parties');
		}
		$data = $this->allowedData($request_data, self::$patientFields);
		if (empty($data['name'])) {
			throw new RestException(400, 'name field missing');
		}

		$patient = new Societe($this->db);
		foreach ($data as $field => $value) {
			$patient->$field = $this->_checkValForAPI($field, $value, $patient);
		}
		$patient->canvas = self::PATIENT_CANVAS;
		$patient->client = 1;
		$patient->code_client = 'auto';		// A patient always needs a customer code (same as card.php)

		if ($patient->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error creating patient', array_merge(array($patient->error), $patient->errors));
		}

		return (int) $patient->id;
	}

	/**
	 * Get the antecedents of a patient
	 *
	 * Return medical, surgical, orthopedic and rheumatologic antecedents, treatments, allergies and intolerances, with their alert flags.
	 *
	 * @param	int		$id		ID of the patient
	 * @return	array			Antecedents of the patient
	 * @phan-return array<string,mixed>
	 * @phpstan-return array<string,mixed>
	 *
	 * @url	GET patients/{id}/antecedents
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function getAntecedents($id)
	{
		$this->checkRight('read');

		$patient = $this->fetchPatient($id);

		$result = array('id' => (int) $patient->id, 'name' => $patient->name);
		foreach (self::$antecedentFields as $field) {
			$alert = 'alert_'.substr($field, 5);
			$result[$field] = (string) $patient->$field;
			$result[$alert] = (int) $patient->$alert;
		}

		return $result;
	}

	/**
	 * Create or modify the antecedents of a patient
	 *
	 * Only the fields given are changed. Fields: note_antemed (medical), note_antechirgen (general surgery),
	 * note_antechirortho (orthopedic surgery), note_anterhum (rheumatology), note_traitallergie (allergies),
	 * note_traitclass (current treatments), note_traitintol (intolerances), note_traitspec (specific treatments),
	 * and for each one an alert flag alert_xxx (0 or 1).
	 *
	 * @param	int		$id				ID of the patient
	 * @param	array	$request_data	Antecedent fields to set
	 * @phan-param ?array<string,mixed> $request_data
	 * @phpstan-param ?array<string,mixed> $request_data
	 * @return	array					Antecedents of the patient after the change
	 * @phan-return array<string,mixed>
	 * @phpstan-return array<string,mixed>
	 *
	 * @url	PUT patients/{id}/antecedents
	 *
	 * @throws RestException 400 Bad request
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 500 System error
	 */
	public function updateAntecedents($id, $request_data = null)
	{
		$this->checkRight('write');
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403, 'Not allowed to modify third parties');
		}
		$patient = $this->fetchPatient($id);

		$alertFields = array();
		foreach (self::$antecedentFields as $field) {
			$alertFields[] = 'alert_'.substr($field, 5);
		}
		$notes = $this->allowedData($request_data, self::$antecedentFields);
		$alerts = $this->allowedData($request_data, $alertFields);
		if (empty($notes) && empty($alerts)) {
			throw new RestException(400, 'No antecedent field given');
		}

		$this->db->begin();

		$error = '';
		if (!empty($notes)) {
			// The row of llx_cabinetmed_patient shares the rowid of the third party and may not exist yet
			$sql = "SELECT rowid FROM ".$this->db->prefix()."cabinetmed_patient WHERE rowid = ".((int) $patient->id);
			$resql = $this->db->query($sql);
			$exists = ($resql && $this->db->num_rows($resql) > 0);

			$sqlvalues = array();
			foreach ($notes as $field => $value) {
				$sqlvalues[$field] = "'".$this->db->escape(sanitizeVal((string) $value, 'restricthtml'))."'";
			}
			if ($exists) {
				$sqlset = array();
				foreach ($sqlvalues as $field => $value) {
					$sqlset[] = $field." = ".$value;
				}
				$sql = "UPDATE ".$this->db->prefix()."cabinetmed_patient SET ".implode(', ', $sqlset)." WHERE rowid = ".((int) $patient->id);
			} else {
				$sql = "INSERT INTO ".$this->db->prefix()."cabinetmed_patient (rowid, ".implode(', ', array_keys($sqlvalues)).")";
				$sql .= " VALUES (".((int) $patient->id).", ".implode(', ', $sqlvalues).")";
			}
			if (!$this->db->query($sql)) {
				$error = $this->db->lasterror();
			}
		}
		foreach ($alerts as $field => $value) {
			if ($error === '') {
				$error = addAlert($this->db, $field, $patient->id, (empty($value) ? '0' : '1'));
			}
		}

		if ($error !== '') {
			$this->db->rollback();
			throw new RestException(500, 'Error when saving antecedents: '.$error);
		}
		$this->db->commit();

		return $this->getAntecedents($id);
	}

	/**
	 * List consultations
	 *
	 * Return the consultations, of all patients or of one patient.
	 *
	 * @param	int		$socid		ID of the patient to restrict to (0 = all patients)
	 * @param	string	$sortfield	Sort field
	 * @param	string	$sortorder	Sort order
	 * @param	int		$limit		Limit for list
	 * @param	int		$page		Page number
	 * @param	string	$sqlfilters	Other criteria to filter answers separated by a comma. Syntax example "(t.datecons:>=:'2026-01-01') and (t.typevisit:=:'CS')"
	 * @param	string	$properties	Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return	array				Array of consultations
	 * @phan-return array<int,stdClass>
	 * @phpstan-return array<int,stdClass>
	 *
	 * @url	GET consultations
	 *
	 * @throws RestException 400 Bad value for sqlfilters
	 * @throws RestException 403 Not allowed
	 * @throws RestException 503 System error
	 */
	public function listConsultations($socid = 0, $sortfield = "t.datecons", $sortorder = 'DESC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		return $this->listPatientRecords('cabinetmed_cons', 'datecons', self::$consultationFields, $socid, $sortfield, $sortorder, $limit, $page, $sqlfilters, $properties);
	}

	/**
	 * Get a consultation
	 *
	 * @param	int		$id		ID of the consultation
	 * @return	Object			Consultation with cleaned properties
	 *
	 * @url	GET consultations/{id}
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function getConsultation($id)
	{
		return $this->getPatientRecord(new CabinetmedCons($this->db), $id);
	}

	/**
	 * Create a consultation
	 *
	 * @param	array	$request_data	Consultation data: socid (patient, required), datecons (YYYY-MM-DD, required), typevisit (CS, CS2, CCAM...), typepriseencharge, motifconsprinc, diaglesprinc, motifconssec, diaglessec, hdm, examenclinique, examenprescrit, traitementprescrit, comment
	 * @phan-param ?array<string,mixed> $request_data
	 * @phpstan-param ?array<string,mixed> $request_data
	 * @return	int						ID of the consultation
	 *
	 * @url	POST consultations
	 *
	 * @throws RestException 400 Bad request
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Patient not found
	 * @throws RestException 500 System error
	 */
	public function createConsultation($request_data = null)
	{
		$object = new CabinetmedCons($this->db);
		$object->datecons = $this->prepareCreate($object, $request_data, 'datecons', self::$consultationFields);
		if (empty($object->typevisit)) {
			$object->typevisit = 'CS';
		}

		return $this->createRecord($object);
	}

	/**
	 * List biologies
	 *
	 * Return the biological examinations, of all patients or of one patient.
	 *
	 * @param	int		$socid		ID of the patient to restrict to (0 = all patients)
	 * @param	string	$sortfield	Sort field
	 * @param	string	$sortorder	Sort order
	 * @param	int		$limit		Limit for list
	 * @param	int		$page		Page number
	 * @param	string	$sqlfilters	Other criteria to filter answers separated by a comma. Syntax example "(t.dateexam:>=:'2026-01-01')"
	 * @param	string	$properties	Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return	array				Array of biologies
	 * @phan-return array<int,stdClass>
	 * @phpstan-return array<int,stdClass>
	 *
	 * @url	GET biologies
	 *
	 * @throws RestException 400 Bad value for sqlfilters
	 * @throws RestException 403 Not allowed
	 * @throws RestException 503 System error
	 */
	public function listBiologies($socid = 0, $sortfield = "t.dateexam", $sortorder = 'DESC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		return $this->listPatientRecords('cabinetmed_exambio', 'dateexam', self::$biologyFields, $socid, $sortfield, $sortorder, $limit, $page, $sqlfilters, $properties);
	}

	/**
	 * Get a biology
	 *
	 * @param	int		$id		ID of the biological examination
	 * @return	Object			Biology with cleaned properties
	 *
	 * @url	GET biologies/{id}
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function getBiology($id)
	{
		return $this->getPatientRecord(new CabinetmedExamBio($this->db), $id);
	}

	/**
	 * Create a biology
	 *
	 * @param	array	$request_data	Biology data: socid (patient, required), dateexam (YYYY-MM-DD, required), resultat, conclusion, comment
	 * @phan-param ?array<string,mixed> $request_data
	 * @phpstan-param ?array<string,mixed> $request_data
	 * @return	int						ID of the biology
	 *
	 * @url	POST biologies
	 *
	 * @throws RestException 400 Bad request
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Patient not found
	 * @throws RestException 500 System error
	 */
	public function createBiology($request_data = null)
	{
		$object = new CabinetmedExamBio($this->db);
		$object->dateexam = $this->prepareCreate($object, $request_data, 'dateexam', self::$biologyFields);

		return $this->createRecord($object);
	}

	/**
	 * List explorations
	 *
	 * Return the radiological and other examinations (explorations), of all patients or of one patient.
	 *
	 * @param	int		$socid		ID of the patient to restrict to (0 = all patients)
	 * @param	string	$sortfield	Sort field
	 * @param	string	$sortorder	Sort order
	 * @param	int		$limit		Limit for list
	 * @param	int		$page		Page number
	 * @param	string	$sqlfilters	Other criteria to filter answers separated by a comma. Syntax example "(t.examprinc:like:'%IRM%')"
	 * @param	string	$properties	Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return	array				Array of explorations
	 * @phan-return array<int,stdClass>
	 * @phpstan-return array<int,stdClass>
	 *
	 * @url	GET explorations
	 *
	 * @throws RestException 400 Bad value for sqlfilters
	 * @throws RestException 403 Not allowed
	 * @throws RestException 503 System error
	 */
	public function listExplorations($socid = 0, $sortfield = "t.dateexam", $sortorder = 'DESC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		return $this->listPatientRecords('cabinetmed_examaut', 'dateexam', self::$explorationFields, $socid, $sortfield, $sortorder, $limit, $page, $sqlfilters, $properties);
	}

	/**
	 * Get an exploration
	 *
	 * @param	int		$id		ID of the exploration (radio/other examination)
	 * @return	Object			Exploration with cleaned properties
	 *
	 * @url	GET explorations/{id}
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function getExploration($id)
	{
		return $this->getPatientRecord(new CabinetmedExamOther($this->db), $id);
	}

	/**
	 * Create an exploration
	 *
	 * @param	array	$request_data	Exploration data: socid (patient, required), dateexam (YYYY-MM-DD, required), examprinc (main exam), examsec (other exams), concprinc (main conclusion), concsec (other conclusions)
	 * @phan-param ?array<string,mixed> $request_data
	 * @phpstan-param ?array<string,mixed> $request_data
	 * @return	int						ID of the exploration
	 *
	 * @url	POST explorations
	 *
	 * @throws RestException 400 Bad request
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Patient not found
	 * @throws RestException 500 System error
	 */
	public function createExploration($request_data = null)
	{
		$object = new CabinetmedExamOther($this->db);
		$object->dateexam = $this->prepareCreate($object, $request_data, 'dateexam', self::$explorationFields);

		return $this->createRecord($object);
	}


	/**
	 * Throw a 403 if the API user has not the given permission of the module
	 *
	 * @param	string	$perm	'read' or 'write'
	 * @return	void
	 *
	 * @throws RestException 403 Not allowed
	 */
	private function checkRight($perm)
	{
		if (!DolibarrApiAccess::$user->hasRight('cabinetmed', $perm)) {
			throw new RestException(403);
		}
	}

	/**
	 * Load a patient the API user may access
	 *
	 * @param	int		$id		ID of the patient
	 * @return	Patient			Patient
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 */
	private function fetchPatient($id)
	{
		if (!DolibarrApi::_checkAccessToResource('societe', (int) $id)) {
			throw new RestException(403, 'Access to patient id='.((int) $id).' not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$patient = new Patient($this->db);
		if ($patient->fetch((int) $id) <= 0 || $patient->canvas != self::PATIENT_CANVAS) {
			throw new RestException(404, 'Patient not found');
		}

		return $patient;
	}

	/**
	 * Keep only the allowed fields of a request
	 *
	 * @param	?array<string,mixed>	$request_data	Request data
	 * @param	string[]				$fields			Allowed fields
	 * @return	array<string,mixed>						Allowed fields that were given
	 */
	private function allowedData($request_data, $fields)
	{
		return is_array($request_data) ? array_intersect_key($request_data, array_flip($fields)) : array();
	}

	/**
	 * SQL restriction of patients for an external user or a user restricted to its own customers
	 *
	 * @param	string	$socidfield		SQL field holding the patient id
	 * @return	string					SQL to append
	 */
	private function sqlPatientRestriction($socidfield)
	{
		$sql = '';
		if (DolibarrApiAccess::$user->socid > 0) {
			$sql .= " AND ".$socidfield." = ".((int) DolibarrApiAccess::$user->socid);
		} elseif (!DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " AND ".getSalesRepresentativeSqlFilter($socidfield, (int) DolibarrApiAccess::$user->id);
		}

		return $sql;
	}

	/**
	 * SQL of the universal search filters
	 *
	 * @param	string	$sqlfilters		Universal search filters
	 * @return	string					SQL to append
	 *
	 * @throws RestException 400 Bad value for sqlfilters
	 */
	private function sqlFilters($sqlfilters)
	{
		if (!$sqlfilters) {
			return '';
		}
		$errormessage = '';
		$sql = forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
		if ($errormessage) {
			throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
		}

		return $sql;
	}

	/**
	 * Run a list query and return its records, in one query
	 *
	 * @param	string		$sql			SQL without order and limit
	 * @param	string		$sortfield		Sort field
	 * @param	string		$sortorder		Sort order
	 * @param	int			$limit			Limit for list
	 * @param	int			$page			Page number
	 * @param	string		$properties		Properties to return
	 * @param	string[]	$datefields		Fields to convert into timestamps
	 * @return	array<int,stdClass>			Records
	 *
	 * @throws RestException 503 System error
	 */
	private function fetchList($sql, $sortfield, $sortorder, $limit, $page, $properties, $datefields)
	{
		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			$sql .= $this->db->plimit($limit, $limit * max(0, (int) $page));
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RestException(503, 'Error when retrieving list: '.$this->db->lasterror());
		}
		$records = array();
		while ($obj = $this->db->fetch_object($resql)) {
			foreach ($datefields as $datefield) {
				$obj->$datefield = $this->db->jdate($obj->$datefield);
			}
			$records[] = $this->_filterObjectProperties($obj, $properties);
		}
		$this->db->free($resql);

		return $records;
	}

	/**
	 * List the records of a table linked to patients by fk_soc
	 *
	 * @param	string		$table			Table without prefix
	 * @param	string		$datefield		Date field of the record
	 * @param	string[]	$fields			Other fields to return
	 * @param	int			$socid			ID of the patient to restrict to (0 = all)
	 * @param	string		$sortfield		Sort field
	 * @param	string		$sortorder		Sort order
	 * @param	int			$limit			Limit for list
	 * @param	int			$page			Page number
	 * @param	string		$sqlfilters		Universal search filters
	 * @param	string		$properties		Properties to return
	 * @return	array<int,stdClass>			Records
	 *
	 * @throws RestException
	 */
	private function listPatientRecords($table, $datefield, $fields, $socid, $sortfield, $sortorder, $limit, $page, $sqlfilters, $properties)
	{
		$this->checkRight('read');

		$sql = "SELECT t.rowid as id, t.fk_soc as socid, s.nom as patient_name, t.fk_user, t.".$datefield;
		foreach ($fields as $field) {
			$sql .= ", t.".$field;
		}
		$sql .= " FROM ".$this->db->prefix().$table." as t";
		$sql .= " INNER JOIN ".$this->db->prefix()."societe as s ON s.rowid = t.fk_soc";
		$sql .= " WHERE s.entity IN (".getEntity('societe').")";
		if ($socid > 0) {
			$sql .= " AND t.fk_soc = ".((int) $socid);
		}
		$sql .= $this->sqlPatientRestriction('t.fk_soc');
		$sql .= $this->sqlFilters($sqlfilters);

		return $this->fetchList($sql, $sortfield, $sortorder, $limit, $page, $properties, array($datefield));
	}

	/**
	 * Load a record linked to a patient the API user may access
	 *
	 * @param	CabinetmedCons|CabinetmedExamBio|CabinetmedExamOther	$object		Empty object to load
	 * @param	int														$id			ID of the record
	 * @return	Object																Record with cleaned properties
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 */
	private function getPatientRecord($object, $id)
	{
		$this->checkRight('read');

		if ($object->fetch((int) $id) <= 0 || empty($object->id)) {
			throw new RestException(404, 'Record not found');
		}
		$this->fetchPatient($object->fk_soc);

		return $this->_cleanObjectDatas($object);
	}

	/**
	 * Check the rights and the patient of a record to create, and set its fields from the request
	 *
	 * @param	CabinetmedCons|CabinetmedExamBio|CabinetmedExamOther	$object			Object to fill
	 * @param	?array<string,mixed>									$request_data	Request data
	 * @param	string													$datefield		Name of the mandatory date field
	 * @param	string[]												$fields			Other fields allowed
	 * @return	int																		Timestamp of the date field
	 *
	 * @throws RestException
	 */
	private function prepareCreate($object, $request_data, $datefield, $fields)
	{
		$this->checkRight('write');

		$patient = $this->fetchPatient((int) ($request_data['socid'] ?? 0));
		$object->fk_soc = $patient->id;

		$date = $request_data[$datefield] ?? '';
		$date = is_numeric($date) ? (int) $date : dol_stringtotime((string) $date, 0);
		if (empty($date)) {
			throw new RestException(400, $datefield.' field missing or not a valid date (YYYY-MM-DD)');
		}

		foreach ($this->allowedData($request_data, $fields) as $field => $value) {
			$object->$field = sanitizeVal((string) $value, 'restricthtml');
		}

		return $date;
	}

	/**
	 * Create a record linked to a patient
	 *
	 * @param	CabinetmedCons|CabinetmedExamBio|CabinetmedExamOther	$object		Object to create
	 * @return	int																	ID of the record
	 *
	 * @throws RestException 500 System error
	 */
	private function createRecord($object)
	{
		if ($object->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error creating record', array_merge(array($object->error), $object->errors));
		}

		return (int) $object->id;
	}
}
