<?php
/* Copyright (C) 2004-2014      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   \file       htdocs/cabinetmed/documents.php
 *   \brief      Tab for courriers
 *   \ingroup    cabinetmed
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/images.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
include_once("./lib/cabinetmed.lib.php");
include_once("./class/patient.class.php");
include_once("./class/cabinetmedcons.class.php");
include_once("./class/html.formfilecabinetmed.class.php");

$action=GETPOST("action");
$confirm=GETPOST('confirm');

$idconsult=GETPOST('idconsult','int')?GETPOST('idconsult','int'):GETPOST('idconsult','int');  // Id consultation
$ref = GETPOST('ref');
$mesg=GETPOST('mesg');

$langs->loadLangs(array("companies", "bills", "banks", "other", "cabinetmed@cabinetmed"));

// Security check
$id = (GETPOST('socid','int') ? GETPOST('socid','int') : GETPOST('id','int'));
$socid=$id;
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'societe', $socid);

if (!$user->rights->cabinetmed->read) accessforbidden();

$error=0;
$errors=array();

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='date';
if (! $sortorder) $sortorder='DESC';
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;

$now=dol_now();

$object = new Patient($db);
$consult = new CabinetmedCons($db);

if ($id > 0 || ! empty($ref))
{
	$result = $object->fetch($id, $ref);

	$entity = $object->entity;

	$upload_dir = $conf->societe->multidir_output[$entity] . "/" . $object->id ;
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('thirdpartycard', 'documentcabinetmed'));

$permissiontoadd = $user->rights->societe->creer;

if (! isset($permission)) $permission = $permissiontoadd;
if (! isset($permtoedit)) $permtoedit = $permissiontoadd;


/*
 * Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$res=@include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';
	if (! $res) include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';

	// Actions to build doc
	/* avec 3.9
	$id = $socid;
	$upload_dir = $conf->societe->dir_output;
	$permissiontoadd=$user->rights->societe->creer;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
	*/

	// Generate document
	if ($action == 'builddoc')  // En get ou en post
	{
	    if (! GETPOST('model'))
	    {
	        $errors[]=$langs->trans("WarningNoDocumentModelActivated");
	    }
	    else if (is_numeric(GETPOST('model')))
	    {
	        $errors[]=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Model"));
	    }
	    else
	    {
	        require_once(DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php');

	        // Save last template used to generate document
	        // Possible with 3.9 only
	        //if (GETPOST('model')) $object->setDocModel($user, GETPOST('model','alpha'));

	        $consult = new CabinetmedCons($db);
	        $consult->fetch($idconsult);

	        // Define output language
	        $outputlangs = $langs;
	        $newlang='';
	        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang=GETPOST('lang_id','aZ09');
	        //if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$fac->client->default_lang;
	        if (! empty($newlang))
	        {
	            $outputlangs = new Translate("",$conf);
	            $outputlangs->setDefaultLang($newlang);
	        }

	        $object->generateDocument(GETPOST('model','alpha'), $outputlangs, 0, 0, 0);
	        if ($result <= 0)
	        {
	            dol_print_error($db,$result);
	            exit;
	        }
	    }
	}
}


// Actions to send emails
$trigger_name='COMPANY_SENTBYMAIL';
$paramname='socid';
$mode='emailfromthirdparty';
$trackid='thi'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';


/*
 *	View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$contactstatic = new Contact($db);

$width="242";

llxHeader('',$langs->trans("Courriers"));

if ($object->id) {
    if ($idconsult && ! $consult->id) {
        $result=$consult->fetch($idconsult);
        if ($result < 0) dol_print_error($db,$consult->error);

        $result=$consult->fetch_bankid();
        if ($result < 0) dol_print_error($db,$consult->error);
    }

    // Show tabs

    if (isModEnabled("notification")) $langs->load("mails");

    $head = societe_prepare_head($object);
    if ((float) DOL_VERSION < 7) dol_fiche_head($head, 'tabdocument', $langs->trans("Patient"), 0, 'patient@cabinetmed');
    elseif ((float) DOL_VERSION < 15) dol_fiche_head($head, 'tabdocument', $langs->trans("Patient"), -1, 'patient@cabinetmed');
    else dol_fiche_head($head, 'tabdocument', $langs->trans("Patient"), -1, 'user-injured');

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';

    // Construit liste des fichiers
    $filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
    $totalsize=0;
    foreach($filearray as $key => $file)
    {
        $totalsize+=$file['size'];
    }

    $linkback = '<a href="'.dol_buildpath('/cabinetmed/patients.php', 1).'">'.$langs->trans("BackToList").'</a>';
    dol_banner_tab($object, 'socid', $linkback, ($user->socid?0:1), 'rowid', 'nom');

    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border tableforfield centpercent">';

    // Prefix
	if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
	{
		print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
	}

    if ($object->client)
    {
        print '<tr><td class="titlefield">';
        print $langs->trans('CustomerCode').'</td><td colspan="3">';
        print $object->code_client;
        if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
        print '</td></tr>';
    }

    if ($object->fournisseur)
    {
        print '<tr><td class="titlefield">';
        print $langs->trans('SupplierCode').'</td><td colspan="3">';
        print $object->code_fournisseur;
        if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
        print '</td></tr>';
    }

    // Nbre fichiers
    print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

    //Total taille
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

    print '</table>';

    print '</div>';

    dol_fiche_end();

    print '</form>';


    $modulepart = 'societe';
	$permission = $user->rights->societe->creer;
    $permtoedit = $user->rights->societe->creer;
	$param = '&id=' . $object->id;
	/*
	 include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
	*/

    if ($mesg) dol_htmloutput_mesg($mesg);
    else dol_htmloutput_mesg($error,$errors,'error');

    $param='';

    if ($action == 'delete')
    {
		$langs->load("companies");	// Need for string DeleteFile+ConfirmDeleteFiles
		$formconfirm = $form->formconfirm(
				$_SERVER["PHP_SELF"] . '?id=' . $object->id . '&urlfile=' . urlencode(GETPOST("urlfile")) . '&linkid=' . GETPOST('linkid', 'int') . (empty($param)?'':$param),
				$langs->trans('DeleteFile'),
				$langs->trans('ConfirmDeleteFile'),
				'confirm_deletefile',
				'',
				0,
				1
		);
		print $formconfirm;
    }




    // Affiche formulaire upload
    $formfile=new FormFile($db);
    $title=img_picto('','filenew').' '.$langs->trans("AttachANewFile");
    $formfile->form_attach_new_file($_SERVER["PHP_SELF"].'?socid='.$socid,$title,0,0,$user->rights->societe->creer, 40, $object, '', 1, '', 1);


    print '<a name="builddoc"></a>'; // ancre

    /*
     * Documents generes
     */
    $filedir=$conf->societe->dir_output.'/'.$object->id;
    $urlsource=$_SERVER["PHP_SELF"]."?socid=".$object->id;
    $genallowed=$user->rights->societe->creer;
    $delallowed=$user->rights->societe->supprimer;

    $title=img_picto('','filenew').' '.$langs->trans("GenerateADocument");
    $tooltipmessage = $langs->trans("EditOrAddTemplateFromSetupOfThirdPartyModule", $langs->trans("Module1Name"), $langs->trans("Home"), $langs->trans("Setup"), $langs->trans("Modules"));

    print $formfile->showdocuments('company','','',$urlsource,$genallowed,$delallowed,'',0,0,0,0,0,'',$title,'',$object->default_lang,'',$object, 0, 'remove_file', $tooltipmessage);

    // List of document
    print '<br><br>';
    $param='&socid='.$object->id;
    $disablemove = 0;

    $formfilecabinetmed=new FormFileCabinetmed($db);
    $formfilecabinetmed->list_of_documents(
        $filearray,
        $object,
        $modulepart,
        $param,
        0,
        '',		// relative path with no file. For example "0/1"
        $permission,
        0,
        '',
        0,
        '',
        '',
        0,
        $permtoedit,
        $upload_dir,
        $sortfield,
        $sortorder,
        $disablemove);

	print "<br>";

	//List of links
	$formfile->listOfLinks($object, $delallowed, $action, GETPOST('linkid', 'int'), $param);

    print '<br>';


    /*
     * Action presend
     */
    if ($action == 'presend')
    {
        $fullpathfile=$upload_dir . '/' . GETPOST('urlfile');

        $lesTypes = $object->liste_type_contact('external', 'libelle', 1);

        // List of contacts
        $withtolist = array();
        foreach(array('external') as $source)
        {
            $tab = $object->liste_contact(-1,$source);
            $num=count($tab);

            $i = 0;
            while ($i < $num)
            {
                $contactstatic->id=$tab[$i]['id'];
                $contactstatic->civility=$tab[$i]['civility'];
                $contactstatic->name=$tab[$i]['lastname'];
                $contactstatic->firstname=$tab[$i]['firstname'];
                $name=$contactstatic->getFullName($langs,1);
                $email=$tab[$i]['email'];
                $withtolist[$contactstatic->id]=$name.' <'.$email.'>'.($tab[$i]['code']?' - '.(empty($lesTypes[$tab[$i]['code']])?'':$lesTypes[$tab[$i]['code']]):'');
                //print 'xx'.$withtolist[$email];
                $i++;
            }
        }

		print '<div id="sendform" name="formmailbeforetitle"></div>';
        print '<br>';

        // Presend form
        $modelmail='thirdparty';
        $defaulttopic='SendConsultationRef';
        $diroutput = $conf->societe->dir_output;
        $trackid = 'thi'.$object->id;

        $file = $fullpathfile;

        include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
    }
}


llxFooter();

$db->close();
