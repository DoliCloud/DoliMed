<?php
/* Copyright (C) 2011 Laurent Destailleur         <eldy@users.sourceforge.net>
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
 *	\file			htdocs/cabinetmed/core/substitutions/functions_cabinetmed.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains functions for plugin cabinetmed.
 */


/**
 * 		Function called to complete substitution array (before generating on ODT, or a personalized email)
 * 		functions xxx_completesubstitutionarray are called by make_substitutions() if file
 * 		is inside directory htdocs/core/substitutions
 *
 *		@param	array		$substitutionarray	Array with substitution key=>val
 *		@param	Translate	$langs				Output langs
 *		@param	Object		$object				Object to use to get values
 *      @param  Mixed		$parameters       	Add more parameters (useful to pass product lines)
 * 		@return	void							The entry parameter $substitutionarray is modified
 */
function cabinetmed_completesubstitutionarray(&$substitutionarray,$langs,$object,$parameters=null)
{
	global $conf,$db;

	dol_include_once('/cabinetmed/class/patient.class.php');
	dol_include_once('/cabinetmed/class/cabinetmedcons.class.php');
	dol_include_once('/cabinetmed/class/cabinetmedexambio.class.php');
    dol_include_once('/cabinetmed/class/cabinetmedexamother.class.php');

    $langs->load("cabinetmed@cabinetmed");

    $isbio=0;
    $isother=0;

    if (empty($parameters['mode']))	// For exemple when called by FormMail::getAvailableSubstitKey()
    {
        // If $object is Societe and not extended Patient, we reload object Patient to have all information specific to patient.
        if ($object && get_class($object) == 'Societe' && $object->canvas == 'patient@cabinetmed')
        {
        	$patientobj=new Patient($db);
        	$patientobj->fetch($object->id);
        	$object = $patientobj;
        }

        $substitutionarray['NotesPatient']=$langs->trans("Notes");
        if ($object)
        {
            $nbofnotes = ($object->note||$object->note_private)?1:0;
            if ($nbofnotes > 0) $substitutionarray['NotesPatient']=$langs->trans("Notes").' <span class="badge">'.$nbofnotes.'</span>';
        }

        $substitutionarray['Correspondants']=$langs->trans("Correspondants");
        if ($object && is_array($parameters) && $parameters['needforkey'] == 'SUBSTITUTION_Correspondants')
        {
    	    $nbChild = count($object->liste_contact(-1,'internal')) + count($object->liste_contact(-1,'external'));

            if ($nbChild > 0) $substitutionarray['Correspondants']=$langs->trans("Correspondants").' <span class="badge">'.$nbChild.'</span>';
        }

        $substitutionarray['ConsultationsShort']=$langs->trans("ConsultationsShort");
        if ($object && is_array($parameters) && $parameters['needforkey'] == 'SUBSTITUTION_ConsultationsShort')
        {
            $sql = "SELECT COUNT(n.rowid) as nb";
            $sql .= " FROM " . MAIN_DB_PREFIX . "cabinetmed_cons as n";
            $sql .= " WHERE fk_soc = " . $object->id;
            $resql = $db->query($sql);
            if ($resql) {
                $num = $db->num_rows($resql);
                $i = 0;
                while ($i < $num) {
                    $obj = $db->fetch_object($resql);
                    $nbChild = $obj->nb;
                    $i ++;
                }
            } else {
                dol_print_error($db);
            }

            if ($nbChild > 0) $substitutionarray['ConsultationsShort']=$langs->trans("ConsultationsShort").' <span class="badge">'.$nbChild.'</span>';
        }

        $substitutionarray['ResultExamBio']=$langs->trans("ResultExamBio");
        if ($object && is_array($parameters) && $parameters['needforkey'] == 'SUBSTITUTION_ResultExamBio')
        {
            $sql = "SELECT COUNT(n.rowid) as nb";
            $sql .= " FROM " . MAIN_DB_PREFIX . "cabinetmed_exambio as n";
            $sql .= " WHERE fk_soc = " . $object->id;
            $resql = $db->query($sql);
            if ($resql) {
                $num = $db->num_rows($resql);
                $i = 0;
                while ($i < $num) {
                    $obj = $db->fetch_object($resql);
                    $nbChild = $obj->nb;
                    $i ++;
                }
            } else {
                dol_print_error($db);
            }

            if ($nbChild > 0) $substitutionarray['ResultExamBio']=$langs->trans("ResultExamBio").' <span class="badge">'.$nbChild.'</span>';
        }

        $substitutionarray['ResultExamAutre']=$langs->trans("ResultExamAutre");
        if ($object && is_array($parameters) && $parameters['needforkey'] == 'SUBSTITUTION_ResultExamAutre')
        {
            $sql = "SELECT COUNT(n.rowid) as nb";
            $sql .= " FROM " . MAIN_DB_PREFIX . "cabinetmed_examaut as n";
            $sql .= " WHERE fk_soc = " . $object->id;
            $resql = $db->query($sql);
            if ($resql) {
                $num = $db->num_rows($resql);
                $i = 0;
                while ($i < $num) {
                    $obj = $db->fetch_object($resql);
                    $nbChild = $obj->nb;
                    $i ++;
                }
            } else {
                dol_print_error($db);
            }

            if ($nbChild > 0) $substitutionarray['ResultExamAutre']=$langs->trans("ResultExamAutre").' <span class="badge">'.$nbChild.'</span>';
        }


        $substitutionarray['TabAntecedentsShort']=$langs->trans("AntecedentsShort");
        if ($object)
       	{
       	    $nbofnotes = 0;
       	    if ($object->note_antemed) $nbofnotes++;
           	if ($object->note_antechirgen) $nbofnotes++;
           	if ($object->note_antechirortho) $nbofnotes++;
           	if ($object->note_anterhum) $nbofnotes++;
           	if ($object->note_traitallergie) $nbofnotes++;
           	if ($object->note_traitclass) $nbofnotes++;
           	if ($object->note_traitintol) $nbofnotes++;
           	if ($object->note_traitspec) $nbofnotes++;
            if ($nbofnotes > 0) $substitutionarray['TabAntecedentsShort']=$langs->trans("AntecedentsShort").' <span class="badge">'.$nbofnotes.'</span>';
       	}

        $substitutionarray['DocumentsPatient']=$langs->trans("DocumentsPatient");
        if ($object && is_array($parameters) && $parameters['needforkey'] == 'SUBSTITUTION_DocumentsPatient')
        {
            // Attached files
            require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
            require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
            $upload_dir = $conf->societe->dir_output . "/" . $object->id;
            $nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview.*\.png)$'));
            $nbLinks=0;
            if ((float) DOL_VERSION >= 4.0) $nbLinks=Link::count($db, $object->element, $object->id);
            if (($nbFiles+$nbLinks) > 0) $substitutionarray['DocumentsPatient']=$langs->trans("DocumentsPatient").' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
        }
    }

    // Consultation + Exams
    if (GETPOST('idconsult') > 0)
    {
    	$outcome=new CabinetmedCons($db);
    	$result1=$outcome->fetch(GETPOST('idconsult'));
		$isconsult=1;
    }
    if (GETPOST('idbio') > 0)
    {
        $exambio=new CabinetmedExamBio($db);
        $result2=$exambio->fetch(GETPOST('idbio'));
        $isbio=1;
    }
    if (GETPOST('idradio') > 0)
    {
        $examother=new CabinetmedExamOther($db);
        $result3=$examother->fetch(GETPOST('idradio'));
        $isother=1;
    }

    if ($isother || $isbio) $substitutionarray['examshows']=$langs->transnoentitiesnoconv("ExamsShow");
    else $substitutionarray['examshows']='';

    if ($isother)	// An image exam was selected
    {
        $substitutionarray['examother_title']=$langs->transnoentitiesnoconv("BilanImage").':';
        $substitutionarray['examother_principal_and_conclusion']=$examother->examprinc.' : '.$examother->concprinc;
        $substitutionarray['examother_principal']=$examother->examprinc;
        $substitutionarray['examother_conclusion']=$examother->concprinc;
    }
    else
    {
        $substitutionarray['examother_title']='';
        $substitutionarray['examother_principal_and_conclusion']='';
        $substitutionarray['examother_principal']='';
        $substitutionarray['examother_conclusion']='';
    }
    if ($isbio)	// A bio exam was selected
    {
        if (! empty($exambio->conclusion)) $substitutionarray['exambio_title']=$langs->transnoentitiesnoconv("BilanBio").':';
        else $substitutionarray['exambio_title']='';
        $substitutionarray['exambio_conclusion']=$exambio->conclusion;
    }
    else
    {
        $substitutionarray['exambio_title']='';
        $substitutionarray['exambio_conclusion']='';
    }
	if ($isconsult)	// A consultation was selected
	{
		$substitutionarray['outcome_id']=$outcome->id;
		$substitutionarray['outcome_date']=dol_print_date($outcome->datecons,'day');
		$substitutionarray['outcome_reason']=$outcome->motifconsprinc;
	    $substitutionarray['outcome_diagnostic']=$outcome->diaglesprinc;
	    $substitutionarray['outcome_history']=$outcome->hdm;
	    $substitutionarray['outcome_exam_clinic']=$outcome->examenclinique;
		// Sugested treatment
	    if (! empty($outcome->traitementprescrit))
	    {
	        $substitutionarray['treatment_title']=$langs->transnoentitiesnoconv("TreatmentSugested"); // old string
	        $substitutionarray['outcome_treatment_title']=$langs->transnoentitiesnoconv("TreatmentSugested");
	        $substitutionarray['outcome_treatment']=$outcome->traitementprescrit;
	    }
	    else
	    {
	        $substitutionarray['treatment_title']='';	// old string
	    	$substitutionarray['outcome_treatment_title']='';
	        $substitutionarray['outcome_treatment']='';
	    }
	    $substitutionarray['outcome_exam_sugested']=$outcome->examenprescrit;
	    // Payments
	    $substitutionarray['outcome_total_inctax_card']=$outcome->montant_carte;
    	$substitutionarray['outcome_total_inctax_cheque']=$outcome->montant_cheque;
    	$substitutionarray['outcome_total_inctax_cash']=$outcome->montant_espece;
    	$substitutionarray['outcome_total_inctax_other']=$outcome->montant_tiers;
    	$substitutionarray['outcome_total_ttc']=($outcome->montant_carte+$outcome->montant_cheque+$outcome->montant_espece+$outcome->montant_tiers);
	}
	else
	{
		$substitutionarray['outcome_id']='';
		$substitutionarray['outcome_date']='';
		$substitutionarray['outcome_reason']='';
	    $substitutionarray['outcome_diagnostic']='';
	    $substitutionarray['outcome_history']='';
	    $substitutionarray['outcome_exam_clinic']='';

	    //$substitutionarray['treatment_title']='';	// old string
		//$substitutionarray['outcome_treatment_title']='';	// old string
		$substitutionarray['outcome_treatment']='';

	    $substitutionarray['outcome_exam_sugested']='';
		$substitutionarray['outcome_total_inctax_card']='';
    	$substitutionarray['outcome_total_inctax_cheque']='';
    	$substitutionarray['outcome_total_inctax_cash']='';
    	$substitutionarray['outcome_total_inctax_other']='';
    	$substitutionarray['outcome_total_ttc']='';
	}

    $substitutionarray['outcome_comment']=GETPOST('outcome_comment');

    include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

    // Patient
    //$patient=new Patient($db);
    //var_dump($object);
    //$patient->fetch($object->fk_soc);
    if (is_object($object))
    {
    	$substitutionarray['patient_id']=$object->id;
    	$substitutionarray['patient_name']=$object->name;
    	$substitutionarray['patient_code']=$object->code_client;

    	$substitutionarray['patient_barcode']=$object->barcode;
    	$substitutionarray['patient_barcode_type']=$object->barcode_type_code;
    	$substitutionarray['patient_country_code']=$object->country_code;
    	$substitutionarray['patient_country']=$object->country;
    	$substitutionarray['patient_email']=$object->email;

    	$substitutionarray['patient_size']=$object->array_options['options_size'];
    	$substitutionarray['patient_weight']=$object->array_options['options_weight'];
    	if (is_numeric($object->array_options['options_birthdate']))
    	{
        	$substitutionarray['patient_birthdate']=dol_print_date($object->array_options['options_birthdate'],'day','',$langs);
    	}
    	else
    	{
    		$substitutionarray['patient_birthdate']=dol_print_date(dol_stringtotime($object->array_options['options_birthdate'].' 00:00:00'),'day','',$langs);
    	}
        $substitutionarray['patient_profession']=$object->array_options['options_prof'];

        $substitutionarray['patient_gender']=$object->typent_code;
        $substitutionarray['patient_socialnum']=$object->tva_intra;
    }

    // Replace contact tabs with GENERALREF if defined
    $substitutionarray['contact_title']='';
    $substitutionarray['contact_lastname']='';
    $substitutionarray['contact_firstname']='';
    $substitutionarray['contact_zip']='';
    $substitutionarray['contact_town']='';
    $substitutionarray['contact_address']='';
    $substitutionarray['contact_email']='';

    if (is_object($object) && method_exists($object, 'liste_contact'))
    {
        $tab = $object->liste_contact(-1,'external');
        foreach($tab as $key => $tmparray)
        {
            if ($tmparray['code'] == 'GENERALREF' && $tmparray['id'] > 0)
            {
                require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
                $contact=new Contact($db);
                $result = $contact->fetch($tmparray['id'], $user);
                if ($result > 0)
                {
                    $substitutionarray['contact_title']=$contact->civility_id;
                    $substitutionarray['contact_lastname']=$contact->lastname;
                    $substitutionarray['contact_firstname']=$contact->firstname;
                    $substitutionarray['contact_zip']=$contact->zip;
                    $substitutionarray['contact_town']=$contact->town;
                    $substitutionarray['contact_address']=preg_replace('/\n/',', ',$contact->address);
                    $substitutionarray['contact_email']=$contact->email;
                    break;
                }
            }
        }
    }
}

