<?php
/* Copyright (C) 2004-2026  Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \file       htdocs/cabinetmed/core/modules/societe/doc/pdf_medicalrecord.modules.php
 * \ingroup    cabinetmed
 * \brief      File of class to generate a medical record (dossier médical) PDF for a patient
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 * Class to generate a medical record (dossier médical) PDF for a patient.
 *
 * The PDF includes:
 *  - Patient information (identity, contact details, physical data)
 *  - Medical history (antécédents médicaux, chirurgicaux, rhumatologiques, allergies, traitements)
 *  - List of consultations with notes and prescriptions
 * No payment information is included.
 */
class pdf_medicalrecord extends ModeleThirdPartyDoc
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Model name
	 */
	public $name;

	/**
	 * @var string Model description (short text)
	 */
	public $description;

	/**
	 * @var string Document type
	 */
	public $type;

	/**
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
	 */
	public $version = 'dolibarr';

	/**
	 * @var int Save the name of generated file as the main doc when generating a doc with this template
	 */
	public $update_main_doc_field;

	/**
	 * @var Societe Company object (sender)
	 */
	public $emetteur;

	/**
	 * @var float Page width
	 */
	public $page_largeur;

	/**
	 * @var float Page height
	 */
	public $page_hauteur;

	/**
	 * @var array{0:float,1:float} Page format
	 */
	public $format;

	/**
	 * @var float Left margin
	 */
	public $marge_gauche;

	/**
	 * @var float Right margin
	 */
	public $marge_droite;

	/**
	 * @var float Top margin
	 */
	public $marge_haute;

	/**
	 * @var float Bottom margin
	 */
	public $marge_basse;

	/**
	 * @var int Corner radius
	 */
	public $corner_radius;

	/**
	 * @var int<0,1> Display logo
	 */
	public $option_logo;

	/**
	 * @var int<0,1> Support add of a watermark on drafts
	 */
	public $option_draft_watermark;

	/**
	 * @var int<0,1> Enable content injection from other sources
	 */
	public $option_multilang;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $mysoc;

		$langs->loadLangs(array("main", "companies", "cabinetmed@cabinetmed"));

		$this->db = $db;
		$this->name = $langs->trans('MedicalRecord');
		$this->description = $langs->trans('MedicalRecord');
		$this->update_main_doc_field = 1;

		// Page size for A4 format
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
		$this->marge_droite = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
		$this->marge_haute = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
		$this->marge_basse = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);
		$this->corner_radius = getDolGlobalInt('MAIN_PDF_FRAME_CORNER_RADIUS', 0);
		$this->option_logo = 1;
		$this->option_draft_watermark = 0;
		$this->option_multilang = 1;

		$this->emetteur = $mysoc;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Function to build a PDF document on disk
	 *
	 * @param  Societe|Patient $object         Object source to build document (patient)
	 * @param  Translate       $outputlangs    Lang output object
	 * @param  string          $srctemplatepath Full path of source filename (unused for PDF)
	 * @param  int<0,1>        $hidedetails    Do not show line details
	 * @param  int<0,1>        $hidedesc       Do not show desc
	 * @param  int<0,1>        $hideref        Do not show ref
	 * @return int<-1,1>                        1 if OK, <=0 if KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $hookmanager;

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO
		if (getDolGlobalString('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		$outputlangs->loadLangs(array("main", "companies", "cabinetmed@cabinetmed"));

		// Definition of $dir and $file
		$entity = isset($object->entity) ? $object->entity : 1;
		$basedir = empty($conf->societe->multidir_output[$entity]) ? $conf->societe->dir_output : $conf->societe->multidir_output[$entity];
		if (empty($basedir)) {
			$this->error = $langs->trans("ErrorConstantNotDefined", "SOCIETE_OUTPUTDIR");
			return 0;
		}

		if (!empty($object->specimen)) {
			$dir = $basedir;
			$file = $dir."/SPECIMEN.pdf";
		} else {
			$objectref = dol_sanitizeFileName($object->ref ? $object->ref : $object->id);
			$dir = $basedir."/".$object->id;
			$file = $dir."/MedicalRecord_".$objectref.".pdf";
		}

		if (!file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		}

		if (file_exists($dir)) {
			// Add pdfgeneration hook
			if (!is_object($hookmanager)) {
				include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
				$hookmanager = new HookManager($this->db);
			}
			$hookmanager->initHooks(array('pdfgeneration'));
			$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
			global $action;
			$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);

			// Create pdf instance
			$pdf = pdf_getInstance($this->format);
			$default_font_size = pdf_getPDFFontSize($outputlangs);
			$heightforfooter = $this->marge_basse + 12;

			$pdf->setAutoPageBreak(true, 0);

			if (class_exists('TCPDF')) {
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}
			$pdf->SetFont(pdf_getPDFFont($outputlangs));

			$pdf->Open();
			$pagenb = 0;
			$pdf->SetDrawColor(128, 128, 128);

			$pdf->SetTitle($outputlangs->convToOutputCharset($object->name)." - ".$outputlangs->transnoentities("DossierMedical"));
			$pdf->SetSubject($outputlangs->transnoentities("DossierMedical"));
			$pdf->SetCreator("Dolibarr ".DOL_VERSION);
			$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getAnonymisableFullName($outputlangs)));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->name)." ".$outputlangs->transnoentities("DossierMedical"));
			if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
				$pdf->SetCompression(false);
			}

			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

			// New page
			$pdf->AddPage();
			$pagenb++;
			$separator_y = $this->_pagehead($pdf, $object, 1, $outputlangs);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(0, 3, '');
			$pdf->SetTextColor(0, 0, 0);

			$tab_top = $separator_y + 8;

			// Section 1: Patient information
			$tab_top = $this->_section_patient($pdf, $object, $outputlangs, $tab_top, $default_font_size);

			// Section 2: Antécédents (medical history)
			$tab_top = $this->_section_antecedents($pdf, $object, $outputlangs, $tab_top, $default_font_size);

			// Section 3: Consultations
			$tab_top = $this->_section_consultations($pdf, $object, $outputlangs, $tab_top, $default_font_size, $heightforfooter);

			// Page footer
			$this->_pagefoot($pdf, $object, $outputlangs);
			if (method_exists($pdf, 'AliasNbPages')) {
				$pdf->AliasNbPages();
			}

			$pdf->Close();

			$pdf->Output($file, 'F');

			// Add pdfgeneration hook
			$hookmanager->initHooks(array('pdfgeneration'));
			$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
			$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);
			$this->warnings = $hookmanager->warnings;
			if ($reshook < 0) {
				$this->error = $hookmanager->error;
				$this->errors = $hookmanager->errors;
				dolChmod($file);
				return -1;
			}

			dolChmod($file);

			$this->result = array('fullpath' => $file);

			return 1;
		} else {
			$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
			return 0;
		}
	}


	/**
	 * Show header of page (company info)
	 *
	 * @param  TCPDF    $pdf         Object PDF
	 * @param  Societe  $object      Object to show
	 * @param  int      $showaddress Show company address (1=yes, 0=no)
	 * @param  Translate $outputlangs Object lang for output
	 * @return float                  Y position of the separator line
	 */
	protected function _pagehead($pdf, $object, $showaddress, $outputlangs)
	{
		global $conf, $mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		// Show company logo and name
		$logo = $conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		$logo_height = 0;
		if ($this->emetteur->logo && is_file($logo)) {
			$logo_height = pdf_getHeightForLogo($logo);
			$pdf->Image($logo, $this->marge_gauche, 5, 0, $logo_height);
		}

		// Company info below the logo
		$info_y = 5 + ($logo_height > 0 ? $logo_height + 3 : 0);

		$pdf->SetFont('', 'B', $default_font_size + 4);
		$pdf->SetXY($this->marge_gauche, $info_y);
		$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');

		$pdf->SetFont('', '', $default_font_size);
		$pdf->SetXY($this->marge_gauche, $pdf->GetY() + 1);

		$carac_emetteur = '';
		// Address
		if (!empty($this->emetteur->address)) {
			$carac_emetteur .= ($carac_emetteur ? "\n" : '').$outputlangs->convToOutputCharset($this->emetteur->address);
		}
		// Zip + Town
		$zip_town = '';
		if (!empty($this->emetteur->zip)) {
			$zip_town .= $this->emetteur->zip;
		}
		if (!empty($this->emetteur->town)) {
			$zip_town .= ($zip_town ? ' ' : '').$this->emetteur->town;
		}
		if ($zip_town) {
			$carac_emetteur .= ($carac_emetteur ? "\n" : '').$outputlangs->convToOutputCharset($zip_town);
		}
		// Country
		if (!empty($this->emetteur->country)) {
			$carac_emetteur .= ($carac_emetteur ? "\n" : '').$outputlangs->convToOutputCharset($this->emetteur->country);
		}
		// Phone
		if (!empty($this->emetteur->phone)) {
			$carac_emetteur .= ($carac_emetteur ? "\n" : '').$outputlangs->transnoentities("Phone").': '.$outputlangs->convToOutputCharset($this->emetteur->phone);
		}

		if ($carac_emetteur) {
			$pdf->MultiCell(100, 3, $carac_emetteur, 0, 'L');
		}

		// Document title on the right
		$pdf->SetFont('', 'B', $default_font_size + 4);
		$pdf->SetXY($this->page_largeur - $this->marge_droite - 80, 5);
		$pdf->MultiCell(80, 4, $outputlangs->transnoentities("DossierMedical"), 0, 'R');
		$pdf->SetFont('', '', $default_font_size);
		$pdf->SetXY($this->page_largeur - $this->marge_droite - 80, $pdf->GetY() + 1);
		$pdf->MultiCell(80, 3, $outputlangs->transnoentities("DateGeneration").': '.dol_print_date(dol_now(), 'day', 'auto', $outputlangs), 0, 'R');

		// Line separator (dynamic position based on content height)
		/*
		$separator_y = max(40, $pdf->GetY() + 5);
		$pdf->SetY($separator_y);
		$pdf->SetDrawColor(128, 128, 128);
		$pdf->line($this->marge_gauche, $separator_y, $this->page_largeur - $this->marge_droite, $separator_y);
		*/

		$separator_y = $pdf->GetY() + 40;
		$pdf->SetY($separator_y);

		return $separator_y;
	}


	/**
	 * Show footer of page
	 *
	 * @param  TCPDF    $pdf         Object PDF
	 * @param  Societe  $object      Object to show
	 * @param  Translate $outputlangs Object lang for output
	 * @param  int      $hidefreetext Hide free text (0=no, 1=yes)
	 * @return void
	 */
	protected function _pagefoot($pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		$pdf->SetY(-15);
		$pdf->SetFont('', 'I', 8);
		$pdf->MultiCell(0, 3, $outputlangs->transnoentities("DossierMedical").' - '.$outputlangs->convToOutputCharset($object->name).' - '.$outputlangs->transnoentities("Page").' '.$pdf->getAliasNumPage().'/'.$pdf->getAliasNbPages(), 0, 'C');
	}


	/**
	 * Write the patient information section
	 *
	 * @param  TCPDF    $pdf              Object PDF
	 * @param  Societe  $object           Patient object
	 * @param  Translate $outputlangs     Object lang for output
	 * @param  float    $tab_top          Current Y position
	 * @param  int      $default_font_size Default font size
	 * @return float                       New Y position after the section
	 */
	protected function _section_patient($pdf, $object, $outputlangs, $tab_top, $default_font_size)
	{
		$pdf->SetFont('', 'B', $default_font_size + 2);
		$pdf->SetXY($this->marge_gauche, $tab_top);
		$pdf->MultiCell(0, 5, $outputlangs->transnoentities("PatientInformations"), 0, 'L');
		$tab_top = $pdf->GetY() + 1;

		$pdf->SetDrawColor(192, 192, 192);
		$pdf->line($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite, $tab_top);
		$tab_top += 2;

		$pdf->SetFont('', '', $default_font_size);

		// Helper to write a label:value line
		$writeLine = function ($label, $value) use ($pdf, $outputlangs, $default_font_size, &$tab_top) {
			if ($value === '' || $value === null) {
				return;
			}
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->SetXY($this->marge_gauche + 2, $tab_top);
			$pdf->Cell(50, 4, $outputlangs->transnoentities($label).':', 0, 0, 'L');
			$pdf->SetFont('', '', $default_font_size);
			$pdf->SetXY($this->marge_gauche + 54, $tab_top);
			$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche - 54, 4, $outputlangs->convToOutputCharset($value), 0, 'L');
			$tab_top = $pdf->GetY() + 1;
		};

		// Name
		$writeLine("PatientName", $object->name);

		// Gender
		if (!empty($object->typent_code)) {
			$writeLine("Gender", $outputlangs->transnoentities($object->typent_code));
		}

		// Birthdate + Age
		$birthdate = '';
		if (!empty($object->array_options['options_birthdate'])) {
			$birthdate = dol_print_date($object->array_options['options_birthdate'], 'day', 'auto', $outputlangs);
			// Calculate age
			$age = floor((dol_now() - $object->array_options['options_birthdate']) / (365.25 * 24 * 3600));
			if ($age >= 0) {
				$birthdate .= ' ('.$age.' '.$outputlangs->transnoentities("YearsUnit").')';
			}
		}
		$writeLine("DateOfBirth", $birthdate);

		// Profession
		if (!empty($object->array_options['options_prof'])) {
			$writeLine("Profession", $object->array_options['options_prof']);
		}

		// Social number (INSEE / Sécurité Sociale)
		if (!empty($object->tva_intra)) {
			$writeLine("PatientVATIntra", $object->tva_intra);
		}

		// Height / Weight
		$height = !empty($object->array_options['options_height']) ? $object->array_options['options_height'] : '';
		$weight = !empty($object->array_options['options_weight']) ? $object->array_options['options_weight'] : '';
		if ($height || $weight) {
			$hw = trim(($height ? $outputlangs->transnoentities("HeightPeople").': '.$height : '').($height && $weight ? '  ' : '').($weight ? $outputlangs->transnoentities("WeigthPeople").': '.$weight : ''));
			$writeLine("PhysicalData", $hw);
		}

		// Address
		$address = $object->address;
		$zip_town = trim(($object->zip ? $object->zip.' ' : '').($object->town ? $object->town : ''));
		if ($zip_town) {
			$address .= ($address ? "\n" : '').$zip_town;
		}
		if (!empty($object->country)) {
			$address .= ($address ? "\n" : '').$object->country;
		}
		$writeLine("Address", $address);

		// Phone
		$writeLine("Phone", $object->phone);

		// Mobile (fax field is used as mobile in this module)
		if (!empty($object->fax)) {
			$writeLine("Mobile", $object->fax);
		}

		// Email
		$writeLine("Email", $object->email);

		$tab_top += 8;

		return $tab_top;
	}


	/**
	 * Write the antécédents (medical history) section
	 *
	 * @param  TCPDF    $pdf              Object PDF
	 * @param  Societe  $object           Patient object
	 * @param  Translate $outputlangs     Object lang for output
	 * @param  float    $tab_top          Current Y position
	 * @param  int      $default_font_size Default font size
	 * @return float                       New Y position after the section
	 */
	protected function _section_antecedents($pdf, $object, $outputlangs, $tab_top, $default_font_size)
	{
		// Check if there is any antécédent data
		$hasAntecedents = !empty($object->note_antemed) || !empty($object->note_antechirgen)
			|| !empty($object->note_antechirortho) || !empty($object->note_anterhum)
			|| !empty($object->note_traitclass) || !empty($object->note_traitallergie)
			|| !empty($object->note_traitintol) || !empty($object->note_traitspec)
			|| !empty($object->note_other);

		$pdf->SetFont('', 'B', $default_font_size + 2);
		$pdf->SetXY($this->marge_gauche, $tab_top);
		$pdf->MultiCell(0, 5, $outputlangs->transnoentities("Antecedents"), 0, 'L');
		$tab_top = $pdf->GetY() + 1;

		$pdf->SetDrawColor(192, 192, 192);
		$pdf->line($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite, $tab_top);
		$tab_top += 2;

		$pdf->SetFont('', '', $default_font_size);

		$antecedents = array(
			'AntecedentsMed' => $object->note_antemed,
			'AntecedentsChirGene' => $object->note_antechirgen,
			'AntecedentsChirOrtho' => $object->note_antechirortho,
			'AntecedentsRhumato' => $object->note_anterhum,
			'Allergies' => $object->note_traitallergie,
			'Intolerances' => $object->note_traitintol,
			'SpecPharma' => $object->note_traitspec,
			'TraitEtAllergies' => $object->note_traitclass,
			'Other' => $object->note_other,
		);

		foreach ($antecedents as $label => $value) {
			if (empty($value)) {
				continue;
			}
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->SetXY($this->marge_gauche + 2, $tab_top);
			$pdf->Cell(50, 4, $outputlangs->transnoentities($label).':', 0, 0, 'L');
			$pdf->SetFont('', '', $default_font_size);
			$pdf->SetXY($this->marge_gauche + 54, $tab_top);
			$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche - 54, 4, $outputlangs->convToOutputCharset($value), 0, 'L');
			$tab_top = $pdf->GetY() + 1;
		}

		if (!$hasAntecedents) {
			$pdf->SetFont('', 'I', $default_font_size - 1);
			$pdf->SetXY($this->marge_gauche + 2, $tab_top);
			$pdf->MultiCell(0, 4, $outputlangs->transnoentities("None"), 0, 'L');
			$tab_top = $pdf->GetY() + 1;
		}

		$tab_top += 8;

		return $tab_top;
	}


	/**
	 * Write the consultations section
	 *
	 * @param  TCPDF    $pdf              Object PDF
	 * @param  Societe  $object           Patient object
	 * @param  Translate $outputlangs     Object lang for output
	 * @param  float    $tab_top          Current Y position
	 * @param  int      $default_font_size Default font size
	 * @param  float    $heightforfooter  Height reserved for footer
	 * @return float                       New Y position after the section
	 */
	protected function _section_consultations($pdf, $object, $outputlangs, $tab_top, $default_font_size, $heightforfooter)
	{
		global $db;

		$pdf->SetFont('', 'B', $default_font_size + 2);
		$pdf->SetXY($this->marge_gauche, $tab_top);
		$pdf->MultiCell(0, 5, $outputlangs->transnoentities("Consultations"), 0, 'L');
		$tab_top = $pdf->GetY() + 1;

		$pdf->SetDrawColor(192, 192, 192);
		$pdf->line($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite, $tab_top);
		$tab_top += 3;

		// Fetch consultations for this patient
		$sql = "SELECT c.rowid, c.datecons, c.typevisit, c.motifconsprinc, c.motifconssec,";
		$sql .= " c.diaglesprinc, c.diaglessec, c.hdm, c.examenclinique, c.examenprescrit,";
		$sql .= " c.traitementprescrit, c.comment, c.fk_user";
		$sql .= " FROM ".MAIN_DB_PREFIX."cabinetmed_cons as c";
		$sql .= " WHERE c.fk_soc = ".((int) $object->id);
		$sql .= " AND c.entity IN (".getEntity('cabinetmed_cons').")";
		$sql .= " ORDER BY c.datecons DESC, c.rowid DESC";

		$resql = $db->query($sql);
		if (!$resql) {
			dol_syslog(get_class($this)."::_section_consultations SQL error: ".$db->lasterror(), LOG_ERR);
			$pdf->SetFont('', 'I', $default_font_size - 1);
			$pdf->SetXY($this->marge_gauche + 2, $tab_top);
			$pdf->MultiCell(0, 4, $outputlangs->transnoentities("ErrorLoadingConsultations"), 0, 'L');
			return $pdf->GetY() + 3;
		}

		$num = $db->num_rows($resql);
		if ($num == 0) {
			$pdf->SetFont('', 'I', $default_font_size - 1);
			$pdf->SetXY($this->marge_gauche + 2, $tab_top);
			$pdf->MultiCell(0, 4, $outputlangs->transnoentities("None"), 0, 'L');
			return $pdf->GetY() + 3;
		}

		require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		$usertmp = new User($db);

		$nbpage = 0;
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			$nbpage++;

			// Check if we need a new page (leave room for footer and at least 30mm of content)
			if ($tab_top > $this->page_hauteur - $heightforfooter - 30) {
				$this->_pagefoot($pdf, $object, $outputlangs, 1);
				$pdf->AddPage();
				$separator_y = $this->_pagehead($pdf, $object, 0, $outputlangs);
				$tab_top = $separator_y + 8;
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');
				$pdf->SetTextColor(0, 0, 0);
			}

			// Consultation header: date + visit type + caregiver
			$pdf->SetFillColor(230, 230, 230);
			$pdf->SetFont('', 'B', $default_font_size + 1);
			$pdf->SetXY($this->marge_gauche, $tab_top);

			$consultTitle = $outputlangs->transnoentities("Consultation").' '.$nbpage.' - '.dol_print_date($db->jdate($obj->datecons), 'day', 'auto', $outputlangs);
			if (!empty($obj->typevisit)) {
				$consultTitle .= ' ('.$outputlangs->transnoentities($obj->typevisit).')';
			}

			// Caregiver name
			$caregiver = '';
			if (!empty($obj->fk_user)) {
				$usertmp->fetch($obj->fk_user);
				$caregiver = $usertmp->getFullName($outputlangs);
			}

			$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($consultTitle), 0, 'L', true);
			$tab_top = $pdf->GetY();

			if ($caregiver) {
				$pdf->SetFont('', 'I', $default_font_size - 1);
				$pdf->SetXY($this->marge_gauche + 2, $tab_top);
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities("ConsultCreatedBy").' '.$outputlangs->convToOutputCharset($caregiver), 0, 'L');
				$tab_top = $pdf->GetY();
			}

			$tab_top += 1;

			// Consultation details
			$pdf->SetFont('', '', $default_font_size);

			$consultFields = array(
				'MotifPrincipal' => $obj->motifconsprinc,
				'MotifSecondaires' => $obj->motifconssec,
				'DiagLesPrincipal' => $obj->diaglesprinc,
				'DiagLesSecondaires' => $obj->diaglessec,
				'HistoireDeLaMaladie' => $obj->hdm,
				'ExamensCliniques' => $obj->examenclinique,
				'ExamensPrescrits' => $obj->examenprescrit,
				'TreatmentSugested' => $obj->traitementprescrit,
				'Commentaires' => $obj->comment,
			);

			foreach ($consultFields as $label => $value) {
				if (empty($value)) {
					continue;
				}
				// Check page break before each field
				if ($tab_top > $this->page_hauteur - $heightforfooter - 15) {
					$this->_pagefoot($pdf, $object, $outputlangs, 1);
					$pdf->AddPage();
					$separator_y = $this->_pagehead($pdf, $object, 0, $outputlangs);
					$tab_top = $separator_y + 8;
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->MultiCell(0, 3, '');
					$pdf->SetTextColor(0, 0, 0);
					$pdf->SetFont('', '', $default_font_size);
				}

				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->SetXY($this->marge_gauche + 2, $tab_top);
				$pdf->Cell(50, 4, $outputlangs->transnoentities($label).':', 0, 0, 'L');
				$pdf->SetFont('', '', $default_font_size);
				$pdf->SetXY($this->marge_gauche + 54, $tab_top);
				$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche - 54, 4, $outputlangs->convToOutputCharset($value), 0, 'L');
				$tab_top = $pdf->GetY() + 1;
			}

			// Separator between consultations
			$tab_top += 1;
			$pdf->SetDrawColor(192, 192, 192);
			$pdf->line($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite, $tab_top);
			$tab_top += 4;

			$i++;
		}

		$db->free($resql);

		return $tab_top;
	}
}
