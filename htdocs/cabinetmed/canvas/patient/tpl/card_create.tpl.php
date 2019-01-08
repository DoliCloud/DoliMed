<?php
/* Copyright (C) 2011-2013 Laurent Destailleur <eldy@users.sourceforge.net>
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

global $db,$conf,$mysoc,$langs,$user,$hookmanager,$extrafields,$object;

$module=$conf->global->SOCIETE_CODECLIENT_ADDON;
if (! $module) dolibarr_error('',$langs->trans("ErrorModuleThirdPartyCodeInCompanyModuleNotDefined"));
if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
{
    $module = substr($module, 0, dol_strlen($module)-4);
}
// Load object modCodeClient
$dirsociete=array_merge(array('/core/modules/societe/'),$conf->modules_parts['societe']);
foreach ($dirsociete as $dirroot)
{
	$res=dol_include_once($dirroot.$module.".php");
    if ($res) break;
}
require_once(DOL_DOCUMENT_ROOT ."/core/class/html.formcompany.class.php");
require_once(DOL_DOCUMENT_ROOT ."/core/class/html.formadmin.class.php");
$modCodeClient = new $module;

$form=new Form($GLOBALS['db']);
$formcompany=new FormCompany($GLOBALS['db']);
$formadmin=new FormAdmin($GLOBALS['db']);



$object->client=-1;
if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && ! empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) $object->client=1;
if (! empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) $object->client=2;
if (! empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && ! empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) $object->client=3;
if (! empty($conf->global->THIRDPARTY_CUSTOMERPROSPECT_BY_DEFAULT))  { $object->client=3; }

$object->name=$_POST["name"];
$object->lastname=$_POST["name"];
$object->firstname=$_POST["firstname"];
$object->particulier=0;
$object->prefix_comm=$_POST["prefix_comm"];
$object->client=$_POST["client"]?$_POST["client"]:$object->client;
$object->code_client=$_POST["code_client"];
$object->fournisseur=$_POST["fournisseur"]?$_POST["fournisseur"]:$object->fournisseur;
$object->code_fournisseur=$_POST["code_fournisseur"];
$object->adresse=$_POST["address"]; // TODO obsolete
$object->address=$_POST["address"];
$object->zip=$_POST["zipcode"];
$object->town=$_POST["town"];
$object->state_id=$_POST["departement_id"];
$object->phone=$_POST["phone"];
$object->fax=$_POST["fax"];
$object->email=$_POST["email"];
$object->url=$_POST["url"];
$object->capital=$_POST["capital"];
$object->barcode=$_POST["barcode"];
$object->idprof1=$_POST["idprof1"];
$object->idprof2=$_POST["idprof2"];
$object->idprof3=$_POST["idprof3"];
$object->idprof4=$_POST["idprof4"];
$object->typent_id=$_POST["typent_id"];
$object->effectif_id=$_POST["effectif_id"];

$object->tva_assuj = $_POST["assujtva_value"];
$object->status= $_POST["status"];

//Local Taxes
$object->localtax1_assuj       = $_POST["localtax1assuj_value"];
$object->localtax2_assuj       = $_POST["localtax2assuj_value"];

$object->tva_intra=$_POST["tva_intra"];

$object->commercial_id=$_POST["commercial_id"];
$object->default_lang=$_POST["default_lang"];

$countrytable="c_pays";
$fieldlabel='libelle';
include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
if (versioncompare(versiondolibarrarray(),array(3,7,-3)) >= 0)
{
	$countrytable="c_country";
	$fieldlabel='label';
}

// We set country_id, country_code and label for the selected country
$object->country_id=$_POST["country_id"]?$_POST["country_id"]:$mysoc->country_id;
if ($object->country_id)
{
    $sql = "SELECT code, ".$fieldlabel." as label";
    $sql.= " FROM ".MAIN_DB_PREFIX.$countrytable;
    $sql.= " WHERE rowid = ".$object->country_id;
    $resql=$db->query($sql);
    if ($resql)
    {
        $obj = $db->fetch_object($resql);
    }
    else
    {
        dol_print_error($db);
    }
    $object->country_code=$obj->code;
    $object->country=$obj->label;
}
$object->forme_juridique_code=$_POST['forme_juridique_code'];

?>

<!-- BEGIN PHP TEMPLATE -->
<?php
print_fiche_titre($langs->trans("NewPatient"));

dol_htmloutput_errors($GOBALS['error'],$GLOBALS['errors']);

?>

<script type="text/javascript">$(document).ready(function () {
    $("#selectcountry_id").change(function() {
        document.formsoc.action.value="create";
        document.formsoc.submit();
    });
})
</script>

<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST" name="formsoc" enctype="multipart/form-data">

<input type="hidden" name="canvas" value="<?php echo $GLOBALS['canvas'] ?>">
<input type="hidden" name="action" value="add">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="private" value="0">
<input type="hidden" name="status" value="1">
<input type="hidden" name="client" value="<?php echo $object->client; ?>">
<?php if ($modCodeClient->code_auto || $modCodeFournisseur->code_auto) print '<input type="hidden" name="code_auto" value="1">';

dol_fiche_head('');

?>

<table class="border" style="width: 100%;">

<tr>
	<td class="titlefield"><span class="fieldrequired"><?php echo $langs->trans('PatientName'); ?></span></td>
	<td><input type="text" size="40" maxlength="60" name="name" value="<?php echo $object->name; ?>" autofocus="autofocus"></td>
    <td width="25%"><?php echo $langs->trans('PatientCode'); ?></td>
    <td width="25%">
<?php
        print '<table class="nobordernopadding"><tr><td>';
        $tmpcode=$object->code_client;
        if ($modCodeClient->code_auto) $tmpcode=$modCodeClient->getNextValue($object,0);
        print '<input type="text" name="code_client" size="16" value="'.$tmpcode.'" maxlength="15">';
        print '</td><td>';
        $s=$modCodeClient->getToolTip($langs,$object,0);
        print $form->textwithpicto('',$s,1);
        print '</td></tr></table>';
?>
    </td>
</tr>

<?php

    // Prospect/Customer
    if (! empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && ! empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
    {
        print '<!-- -->';
    }
    else
    {
        print '<tr><td class="titlefieldcreate">'.fieldLabel('ProspectCustomer','customerprospect',1).'</td>';
        print '<td class="maxwidthonsmartphone">';
        $selected=isset($_POST['client'])?GETPOST('client'):$object->client;
        print '<select class="flat" name="client" id="customerprospect">';
        if (GETPOST("type") == '') print '<option value="-1"></option>';
        if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print '<option value="2"'.($selected==2?' selected':'').'>'.$langs->trans('Prospect').'</option>';
        if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print '<option value="3"'.($selected==3?' selected':'').'>'.$langs->trans('ProspectCustomer').'</option>';
        if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print '<option value="1"'.($selected==1?' selected':'').'>'.$langs->trans('Customer').'</option>';
        print '<option value="0"'.((string) $selected == '0'?' selected':'').'>'.$langs->trans('NorProspectNorCustomer').'</option>';
        print '</select></td>';
    }
?>
<tr>
	<td class="titlefield tdtop"><?php echo $langs->trans('Address'); ?></td>
	<td colspan="3"><textarea name="address" class="quatrevingtpercent" rows="3"><?php echo $object->address; ?></textarea></td>
</tr>

<?php
        // Zip / Town
        print '<tr><td>'.$langs->trans('Zip').'</td><td>';
        print $formcompany->select_ziptown($object->zip,'zipcode',array('town','selectcountry_id','departement_id'),6);
        print '</td><td>'.$langs->trans('Town').'</td><td>';
        print $formcompany->select_ziptown($object->town,'town',array('zipcode','selectcountry_id','departement_id'));
        print '</td></tr>';

        // Country
        print '<tr><td width="25%">'.$langs->trans('Country').'</td><td colspan="3">';
        print $form->select_country($object->country_id,'country_id');
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
        print '</td></tr>';

        // State
        if (empty($conf->global->SOCIETE_DISABLE_STATE))
        {
            print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">';
            $formcompany->select_departement($object->state_id,$object->country_code);
            print '</td></tr>';
        }
?>

<tr>
	<td><?php echo $langs->trans('PhonePerso'); ?></td>
	<td><input type="text" name="phone" value="<?php echo $object->phone; ?>"></td>
	<td><?php echo $langs->trans('PhoneMobile'); ?></td>
	<td><input type="text" name="fax" value="<?php echo $object->fax; ?>"></td>
</tr>

<tr>
	<td><?php echo $langs->trans('EMail').($conf->global->SOCIETE_EMAIL_MANDATORY?'*':''); ?></td>
	<td colspan="3"><input type="text" name="email" size="32" value="<?php echo $object->email; ?>"></td>
</tr>

<?php
/*
        print '<tr>';
        // Height
        $idprof=$langs->trans('HeightPeople');
        print '<td>'.$idprof.'</td><td>';
        print '<input type="text" name="idprof1" size="6" maxlength="6" value="'.$object->idprof1.'">';
        print '</td>';
        // Weight
        $idprof=$langs->trans('Weight');
        print '<td>'.$idprof.'</td><td>';
        print '<input type="text" name="idprof2" size="6" maxlength="6" value="'.$object->idprof2.'">';
        print '</td>';
        print '</tr>';
        print '<tr>';
*/
       // Prof ids
        $i=1; $j=0;
        while ($i <= 6)
        {
        	$key='CABINETMED_SHOW_PROFID'.$i;
        	if (empty($conf->global->$key)) { $i++; continue; }

            $idprof=$langs->transcountry('ProfId'.$i,$object->country_code);
            if ($idprof!='-')
            {
	            $key='idprof'.$i;

                if (($j % 2) == 0) print '<tr>';

                $idprof_mandatory ='SOCIETE_IDPROF'.($i).'_MANDATORY';
               	if(empty($conf->global->$idprof_mandatory))
                	print '<td><label for="'.$key.'">'.$idprof.'</label></td><td>';
                else
                    print '<td><span class="fieldrequired"><label for="'.$key.'">'.$idprof.'</label></td><td>';

                print $formcompany->get_input_id_prof($i,$key,$object->$key,$object->country_code);
                print '</td>';
                if (($j % 2) == 1) print '</tr>';
                $j++;
            }
            $i++;
        }
        if ($j % 2 == 1) print '<td colspan="2"></td></tr>';

       	// Birthday
/*
        $idprof=$langs->trans('DateToBirth');
        print '<td>'.$idprof.'</td><td colspan="3">';

        print '<input type="text" name="idprof3" size="18" maxlength="32" value="'.$object->idprof3.'"> ('.$conf->format_date_short_java.')';
        //$conf->global->MAIN_POPUP_CALENDAR='none';
        //print $form->select_date(-1,'birthdate');
        print '</td>';
        print '</tr>';
*/
        print '<tr>';
        print '<td class="nowrap">'.$langs->trans('PatientVATIntra').'</td>';
        print '<td class="nowrap" colspan="3">';
        print '<input type="text" class="flat" name="tva_intra" size="18" maxlength="32" value="'.$object->tva_intra.'">';
        print '</td></tr>';

        // Genre
        print '<tr><td>'.$langs->trans("Gender").'</td><td colspan="3">'."\n";
        print $form->selectarray("typent_id",$formcompany->typent_array(0, "AND code in ('TE_UNKNOWN', 'TE_HOMME', 'TE_FEMME')"), $object->typent_id);
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
        print '</td></tr>';

        // Legal Form
        print '<tr><td>'.$langs->trans('ActivityBranch').'</td>';
        print '<td colspan="3">';
        if ($GLOBALS['mysoc']->country_id)
        {
            print $formcompany->select_juridicalstatus($object->forme_juridique_code, $GLOBALS['mysoc']->country_code, "AND (f.module = 'cabinetmed' OR f.code > '100000')");	// > 100000 is the only way i found to not see other entries
        }
        else
        {
            print $GLOBALS['countrynotdefined'];
        }
        print '</td>';
        print '</tr>';

        if ($conf->global->MAIN_MULTILANGS)
        {
            print '<tr><td>'.$langs->trans("DefaultLang").'</td><td colspan="3">'."\n";
            print $formadmin->select_language(($object->default_lang?$object->default_lang:$conf->global->MAIN_LANG_DEFAULT),'default_lang',0,0,1);
            print '</td>';
            print '</tr>';
        }

        // Categories
        if (! empty($conf->categorie->enabled)  && ! empty($user->rights->categorie->lire))
        {
            $langs->load('categories');

            // Customer
            if ($object->prospect || $object->client) {
                print '<tr><td class="toptd">' . fieldLabel('CustomersCategoriesShort', 'custcats') . '</td><td colspan="3">';
                $cate_arbo = $form->select_all_categories(Categorie::TYPE_CUSTOMER, null, 'parent', null, null, 1);
                print $form->multiselectarray('custcats', $cate_arbo, GETPOST('custcats', 'array'), null, null, null,
                    null, "90%");
                print "</td></tr>";
            }

            // Supplier
            if ($object->fournisseur) {
                print '<tr><td class="toptd">' . fieldLabel('SuppliersCategoriesShort', 'suppcats') . '</td><td colspan="3">';
                $cate_arbo = $form->select_all_categories(Categorie::TYPE_SUPPLIER, null, 'parent', null, null, 1);
                print $form->multiselectarray('suppcats', $cate_arbo, GETPOST('suppcats', 'array'), null, null, null,
                    null, "90%");
                print "</td></tr>";
            }
        }

        // Other attributes
        $parameters=array('colspan' => ' colspan="3"', 'colspanvalue' => '3');
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        if (empty($reshook))
        {
            print $object->showOptionals($extrafields,'edit');
        }

        // Ajout du logo
        print '<tr class="hideonsmartphone">';
        print '<td>'.fieldLabel('Logo','photoinput').'</td>';
        print '<td colspan="3">';
        print '<input class="flat" type="file" name="photo" id="photoinput" />';
        print '</td>';
        print '</tr>';

        // Assign a sale representative
        print '<tr>';
        print '<td>'.$form->editfieldkey('AllocateCommercial', 'commercial_id', '', $object, 0).'</td>';
        print '<td colspan="3" class="maxwidthonsmartphone">';
        $userlist = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, '', 0, '', '', 0, 1);
        // Note: If user has no right to "see all thirdparties", we for selection of sale representative to him, so after creation he can see the record.
        $selected = (count(GETPOST('commercial', 'array')) > 0 ? GETPOST('commercial', 'array') : (GETPOST('commercial', 'int') > 0 ? array(GETPOST('commercial', 'int')) : (empty($user->rights->societe->client->voir)?array($user->id):array())));
        print $form->multiselectarray('commercial', $userlist, $selected, null, null, null, null, "90%");
        print '</td></tr>';
?>
</table>

<?php

dol_fiche_end();

?>

<div align="center">
	<input type="submit" class="button" value="<?php echo $langs->trans('AddPatient'); ?>">
</div>

</form>

<!-- END PHP TEMPLATE -->
