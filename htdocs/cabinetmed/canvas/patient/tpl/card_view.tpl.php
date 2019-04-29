<?php
/* Copyright (C) 2010-2011 Regis Houssin       <regis@dolibarr.fr>
 * Copyright (C) 2011      Laurent Destailleur <eldy@users.sourceforge.net>
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

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}


$object=$GLOBALS['object'];

global $db,$conf,$mysoc,$langs,$user,$hookmanager,$extrafields;

require_once(DOL_DOCUMENT_ROOT ."/core/class/html.formcompany.class.php");
require_once(DOL_DOCUMENT_ROOT ."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT ."/core/lib/date.lib.php");
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
dol_include_once("/cabinetmed/lib/cabinetmed.lib.php");

$form=new Form($GLOBALS['db']);
$formcompany=new FormCompany($GLOBALS['db']);
$formadmin=new FormAdmin($GLOBALS['db']);
$formfile=new FormFile($GLOBALS['db']);
?>

<!-- BEGIN PHP TEMPLATE CARD_VIEW.TPL.PHP PATIENT -->

<?php

$head = societe_prepare_head($object);
$now=dol_now();

/*foreach($head as $key => $val)
{
	var_dump($val);
}*/

//dol_fiche_head($head, 'tabpatientcard', $langs->trans("Patient"),0,'company');
if ((float) DOL_VERSION < 7) dol_fiche_head($head, 'card', $langs->trans("Patient"), 0, 'patient@cabinetmed');
else dol_fiche_head($head, 'card', $langs->trans("Patient"), -1, 'patient@cabinetmed');

dol_htmloutput_errors($error,$errors);


// Confirm delete third party
if ($action == 'delete' || ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)))
{
    $ret=$form->form_confirm($_SERVER["PHP_SELF"]."?socid=".$object->id,$langs->trans("DeleteACompany"),$langs->trans("ConfirmDeleteCompany"),"confirm_delete",'',0,"action-delete");
    if ($ret == 'html') print '<br>';
}

dol_htmloutput_errors($GLOBALS['error'],$GLOBALS['errors']);


$linkback = '<a href="'.dol_buildpath('/cabinetmed/patients.php', 1).'">'.$langs->trans("BackToList").'</a>';
dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border tableforfield" width="100%">';
if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
{
    print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
}

//if ($object->client)
//{
    print '<tr><td class="titlefield">';
    print $langs->trans('CustomerCode').'</td><td colspan="3">';
    print $object->code_client;
    if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongPatientCode").')</font>';
    print '</td></tr>';
//}

// Barcode
if ($conf->global->MAIN_MODULE_BARCODE)
{
    print '<tr><td>'.$langs->trans('Gencod').'</td><td colspan="3">'.$object->barcode.'</td></tr>';
}

// Prof ids
$i=1; $j=0;
while ($i <= 6)
{
    $key='CABINETMED_SHOW_PROFID'.$i;
	if (empty($conf->global->$key)) { $i++; continue; }

	$idprof=$langs->transcountry('ProfId'.$i,$object->country_code);
	if ($idprof!='-')
	{
		if (($j % 2) == 0) print '<tr>';
		print '<td>'.$idprof.'</td><td>';
		$key='idprof'.$i;
		print $object->$key;
		if ($object->$key)
		{
			if ($object->id_prof_check($i,$object) > 0) print ' &nbsp; '.$object->id_prof_url($i,$object);
			else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
		}
		print '</td>';
		if (($j % 2) == 1) print '</tr>';
		$j++;
	}
	$i++;
}
if ($j % 2 == 1)  print '<td colspan="2"></td></tr>';

// Height
/*
$profid=$langs->trans('HeightPeople');
print '<tr><td>'.$profid.'</td><td>';
print $object->idprof1;
print '</td>';
// Weight
$profid=$langs->trans('Weight');
print '<td>'.$profid.'</td><td>';
print $object->idprof2;
print '</td></tr>';

// Birthday
$profid=$langs->trans('DateToBirth');
print '<tr><td>'.$profid.'</td><td colspan="3">';
print $object->idprof3;
if ($object->idprof3)
{
    print ' &nbsp; ';
    $birthdatearray=dol_cm_strptime($object->idprof3,$conf->format_date_short);
    $birthdate=dol_mktime(0,0,0,$birthdatearray['tm_mon']+1,($birthdatearray['tm_mday']),($birthdatearray['tm_year']+1900),true);
    //var_dump($birthdatearray);
    $ageyear=convertSecondToTime($now-$birthdate,'year')-1970;
    $agemonth=convertSecondToTime($now-$birthdate,'month')-1;
    if ($ageyear >= 2) print '('.$ageyear.' '.$langs->trans("DurationYears").')';
    else if ($agemonth >= 2) print '('.$agemonth.' '.$langs->trans("DurationMonths").')';
    else print '('.$agemonth.' '.$langs->trans("DurationMonth").')';
}
print '</td>';
print '</tr>';
*/

// Num secu
print '<tr>';
print '<td class="nowrap">'.$langs->trans('PatientVATIntra').'</td><td colspan="3">';
if ($object->tva_intra)
{
    $s='';
    $s.=$object->tva_intra;
    $s.='<input type="hidden" name="tva_intra" size="12" maxlength="20" value="'.$object->tva_intra.'">';

    if (empty($conf->global->MAIN_DISABLEVATCHECK))
    {
        $s.=' &nbsp; ';

        if ($conf->use_javascript_ajax)
        {
            print "\n";
            print '<script language="JavaScript" type="text/javascript">';
            print "function CheckVAT(a) {\n";
            print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?vatNumber='+a,'".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."',500,285);\n";
            print "}\n";
            print '</script>';
            print "\n";
            $s.='<a href="#" onclick="javascript: CheckVAT(document.formsoc.tva_intra.value);">'.$langs->trans("VATIntraCheck").'</a>';
            $s = $form->textwithpicto($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
        }
        else
        {
            $s.='<a href="'.$langs->transcountry("VATIntraCheckURL",$object->id_pays).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
        }
    }
    print $s;
}
else
{
    print '&nbsp;';
}
print '</td>';
print '</tr>';

// Type + Staff => Genre
$arr = $formcompany->typent_array(1);
$object->typent= $arr[$object->typent_code];
print '<tr><td>'.$langs->trans("Gender").'</td><td colspan="3">'.$object->typent.'</td>';
//print '<td>'.$langs->trans("Staff").'</td><td>'.$object->effectif.'</td>';
print '</tr>';

// Juridical status => Secteur activité
print '<tr><td>'.$langs->trans('ActivityBranch').'</td><td colspan="3">'.$object->forme_juridique.'</td>';
print '</tr>';

// Default language
if ($conf->global->MAIN_MULTILANGS)
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
    print '<tr><td>'.$langs->trans("DefaultLang").'</td><td colspan="3">';
    //$s=picto_from_langcode($object->default_lang);
    //print ($s?$s.' ':'');
    $langs->load("languages");
    $labellang = ($object->default_lang?$langs->trans('Language_'.$object->default_lang):'');
    print $labellang;
    print '</td></tr>';
}

// Tags / categories
if (! empty($conf->categorie->enabled)  && ! empty($user->rights->categorie->lire))
{
    // Customer
    if ($object->prospect || $object->client) {
        print '<tr><td>' . $langs->trans("CustomersCategoriesShort") . '</td>';
        print '<td colspan="3">';
        print $form->showCategories($object->id, 'customer', 1);
        print "</td></tr>";
    }

    // Supplier
    if ($object->fournisseur) {
        print '<tr><td>' . $langs->trans("SuppliersCategoriesShort") . '</td>';
        print '<td colspan="3">';
        print $form->showCategories($object->id, 'supplier', 1);
        print "</td></tr>";
    }
}

// Other attributes
$parameters=array('socid'=>$socid, 'colspan' => ' colspan="3"', 'colspanvalue' => '3');
$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (empty($reshook))
{
  	$tmp=$object->showOptionals($extrafields);
  	print '<!-- extrafields -->'."\n";

  	// Replace tmp content to add age
  	if ($object->array_options['options_birthdate'])
  	{
  	    $now = dol_now();
      	//var_dump($object->array_options['options_birthdate']);
  	    if (is_numeric($object->array_options['options_birthdate']))
  	    {
  	        $birthdate=$object->array_options['options_birthdate'];
  	    }
  	    else
  	    {
            $birthdate=dol_stringtotime($object->array_options['options_birthdate'].' 00:00:00', 1);
  	    }
  	    if ($birthdate)
      	{
          	$newtmp=' &nbsp; ';
          	//$birthdatearray=dol_cm_strptime($dateval,$conf->format_date_short);
          	//$birthdate=dol_mktime(0,0,0,$birthdatearray['tm_mon']+1,($birthdatearray['tm_mday']),($birthdatearray['tm_year']+1900),true);
          	$ageyear=convertSecondToTime($now-$birthdate,'year')-1970;
          	$agemonth=convertSecondToTime($now-$birthdate,'month')-1;
          	if ($ageyear >= 1) $newtmp.='('.$ageyear.' '.$langs->trans("DurationYears").')';
          	else if ($agemonth >= 1) $newtmp.='('.$agemonth.' '.$langs->trans("DurationMonths").')';
          	else $newtmp.='('.$agemonth.' '.$langs->trans("DurationMonth").')';
            //print $newtmp;
          	$tmp=preg_replace('/'.preg_quote('<td','/').'[^>]*'.preg_quote('colspan="3">'.dol_print_date($birthdate, 'day').'</td>','/').'/','<td colspan="3">'.dol_print_date($birthdate, 'day').$newtmp.'</td>',$tmp);
      	}
  	}
  	print $tmp;

  	print '<!-- end extrafields -->';
}

// Ban
if (empty($conf->global->SOCIETE_DISABLE_BANKACCOUNT))
{
    print '<tr><td>';
    print '<table width="100%" class="nobordernopadding"><tr><td>';
    print $langs->trans('RIB');
    print '<td><td align="right">';
    if ($user->rights->societe->creer)
    {
    	if ((float) DOL_VERSION < 8.0)
    	{
    		print '<a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$object->id.'">'.img_edit().'</a>';
    	}
    	else
    	{
    		print '<a href="'.DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.$object->id.'">'.img_edit().'</a>';
    	}
    }
    else
    {
    	print '&nbsp;';
    }
    print '</td></tr></table>';
    print '</td>';
    print '<td colspan="3">';
    print $object->display_rib();
    print '</td></tr>';
}

// Parent company
/*
if (empty($conf->global->SOCIETE_DISABLE_PARENTCOMPANY))
{
    print '<tr><td>';
    print '<table width="100%" class="nobordernopadding"><tr><td>';
    print $langs->trans('ParentPatient');
    print '<td><td align="right">';
    if ($user->rights->societe->creer)
    print '<a href="'.DOL_URL_ROOT.'/societe/lien.php?socid='.$object->id.'">'.img_edit() .'</a>';
    else
    print '&nbsp;';
    print '</td></tr></table>';
    print '</td>';
    print '<td colspan="3">';
    if ($object->parent)
    {
        $objectm = new Societe($db);
        $objectm->fetch($object->parent);
        print $objectm->getNomUrl(1).' '.($objectm->code_client?"(".$objectm->code_client.")":"");
        print $objectm->town?' - '.$objectm->town:'';
    }
    else {
        print $langs->trans("NoParentCompany");
    }
    print '</td></tr>';
}
*/

    // Sales representative
    include DOL_DOCUMENT_ROOT.'/societe/tpl/linesalesrepresentative.tpl.php';

    // Module Adherent
    if (! empty($conf->adherent->enabled))
    {
        $langs->load("members");
        print '<tr><td>'.$langs->trans("LinkedToDolibarrMember").'</td>';
        print '<td colspan="3">';
        $adh=new Adherent($db);
        $result=$adh->fetch('','',$object->id);
        if ($result > 0)
        {
            $adh->ref=$adh->getFullName($langs);
            print $adh->getNomUrl(1);
        }
        else
        {
            print '<span class="opacitymedium">'.$langs->trans("ThirdpartyNotLinkedToMember").'</span>';
        }
        print '</td>';
        print "</tr>\n";
    }

    // Webservices url/key
    if (!empty($conf->syncsupplierwebservices->enabled)) {
        print '<tr><td>'.$langs->trans("WebServiceURL").'</td><td>'.dol_print_url($object->webservices_url).'</td>';
        print '<td class="nowrap">'.$langs->trans('WebServiceKey').'</td><td>'.$object->webservices_key.'</td></tr>';
    }

print '</table>';

print '</div>';

dol_fiche_end();


/*
 *  Actions
 */

print '<div class="tabsAction">'."\n";

$parameters=array();
$reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
if (empty($reshook))
{
	if (! empty($object->email))
	{
		$langs->load("mails");
		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendMail').'</a></div>';
	}
	else
	{
		$langs->load("mails");
		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans('SendMail').'</a></div>';
	}

	if ($user->rights->societe->creer)
	{
		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>'."\n";
	}

	if ($user->rights->societe->supprimer)
	{
		if ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile))	// We can't use preloaded confirm form with jmobile
		{
			print '<div class="inline-block divButAction"><span id="action-delete" class="butActionDelete">'.$langs->trans('Delete').'</span></div>'."\n";
		}
		else
		{
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a></div>'."\n";
		}
	}
}

print '</div>'."\n";


//Select mail models is same action as presend
if (GETPOST('modelselected')) {
	$action = 'presend';
}

if ((float) DOL_VERSION >= 7.0)
{
	// Presend form
	$modelmail='thirdparty';
	$defaulttopic='Information';
	$diroutput = $conf->societe->dir_output;
	$trackid = 'thi'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}
elseif ($action == 'presend')
{
	// By default if $action=='presend'
	$titreform='SendMail';
	$topicmail='';
	$action='send';
	$modelmail='thirdparty';

	print '<br>';
	print '<div class="titre">'.$langs->trans($titreform).'</div>';

	// Define output language
	$outputlangs = $langs;
	$newlang = '';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
		$newlang = $_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang))
		$newlang = $object->client->default_lang;

	// Cree l'objet formulaire mail
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	$formmail = new FormMail($db);
	$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
	$formmail->fromtype = 'user';
	$formmail->fromid   = $user->id;
	$formmail->fromname = $user->getFullName($langs);
	$formmail->frommail = $user->email;
	$formmail->trackid='thi'.$object->id;
	if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
	{
		include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'inv'.$object->id);
	}
	$formmail->withfrom=1;
	$formmail->withtopic=1;
	$liste=array();
	foreach ($object->thirdparty_and_contact_email_array(1) as $key=>$value) $liste[$key]=$value;
	$formmail->withto=GETPOST('sendto')?GETPOST('sendto'):$liste;
	$formmail->withtofree=0;
	$formmail->withtocc=$liste;
	$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
	$formmail->withfile=2;
	$formmail->withbody=1;
	$formmail->withdeliveryreceipt=1;
	$formmail->withcancel=1;
	// Tableau des substitutions
	$formmail->substit['__SIGNATURE__']=$user->signature;
	$formmail->substit['__PERSONALIZED__']='';		// deprecated
	$formmail->substit['__CONTACTCIVNAME__']='';


	// Tableau des parametres complementaires du post
	$formmail->param['action']=$action;
	$formmail->param['models']=$modelmail;
	$formmail->param['socid']=$object->id;
	$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?socid='.$object->id;

	// Init list of files
	if (GETPOST("mode")=='init')
	{
		$formmail->clear_attached_files();
		$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
	}

	print $formmail->get_form();

	print '<br>';
}


if ($action != 'presend')
{
	print '<br>';


	/*
	print '<table width="100%"><tr><td valign="top" width="50%">';
	print '<a name="builddoc"></a>'; // ancre

	$filedir=$conf->societe->dir_output.'/'.$object->id;
	$urlsource=$_SERVER["PHP_SELF"]."?socid=".$object->id;
	$genallowed=$user->rights->societe->creer;
	$delallowed=$user->rights->societe->supprimer;

	$var=true;

	print $formfile->showdocuments('company',$object->id,$filedir,$urlsource,$genallowed,$delallowed,'',0,0,0,28,0,'',0,'',$object->default_lang);

	print '</td>';
	print '<td>';
	print '</td>';
	print '</tr>';
	print '</table>';

	print '<br>';
	*/

	// Subsidiaries list
	$result=show_subsidiaries($conf,$langs,$db,$object);

	/*
	// Contacts list
	if (empty($conf->global->SOCIETE_DISABLE_CONTACTS))
	{
		$result=show_contacts($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
	}

	// Addresses list
	if (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT))
	{
		$result=show_addresses($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
	}
	 */

	// Projects list
	//$result=show_projects($conf,$langs,$db,$object);
}

?>

<!-- END PHP TEMPLATE -->
