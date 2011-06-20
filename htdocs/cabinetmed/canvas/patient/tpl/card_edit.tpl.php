<?php
/* Copyright (C) 2011 Laurent Destailleur <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id: card_edit.tpl.php,v 1.3 2011/06/07 22:04:37 eldy Exp $
 */

$soc=$GLOBALS['objcanvas']->control->object;

global $db,$conf,$mysoc,$langs,$user;

require_once(DOL_DOCUMENT_ROOT ."/core/class/html.formcompany.class.php");
require_once(DOL_DOCUMENT_ROOT ."/core/class/html.formfile.class.php");

$form=new Form($GLOBALS['db']);
$formcompany=new FormCompany($GLOBALS['db']);
$formadmin=new FormAdmin($GLOBALS['db']);
$formfile=new FormFile($GLOBALS['db']);


// Load object modCodeTiers
$module=$conf->global->SOCIETE_CODECLIENT_ADDON;
if (! $module) dolibarr_error('',$langs->trans("ErrorModuleThirdPartyCodeInCompanyModuleNotDefined"));
if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
{
    $module = substr($module, 0, dol_strlen($module)-4);
}
require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$module.".php");
$modCodeClient = new $module;
// We verified if the tag prefix is used
if ($modCodeClient->code_auto)
{
    $prefixCustomerIsUsed = $modCodeClient->verif_prefixIsUsed();
}


if ($_POST["nom"])
{
    $soc->client=1;

    $soc->nom=$_POST["nom"];
    $soc->prenom=$_POST["prenom"];
    $soc->particulier=0;
    $soc->prefix_comm=$_POST["prefix_comm"];
    $soc->client=$_POST["client"]?$_POST["client"]:$soc->client;
    $soc->code_client=$_POST["code_client"];
    $soc->fournisseur=$_POST["fournisseur"]?$_POST["fournisseur"]:$soc->fournisseur;
    $soc->code_fournisseur=$_POST["code_fournisseur"];
    $soc->adresse=$_POST["adresse"]; // TODO obsolete
    $soc->address=$_POST["adresse"];
    $soc->cp=$_POST["zipcode"];
    $soc->ville=$_POST["town"];
    $soc->departement_id=$_POST["departement_id"];
    $soc->tel=$_POST["tel"];
    $soc->fax=$_POST["fax"];
    $soc->email=$_POST["email"];
    $soc->url=$_POST["url"];
    $soc->capital=$_POST["capital"];
    $soc->gencod=$_POST["gencod"];
    $soc->siren=$_POST["idprof1"];
    $soc->siret=$_POST["idprof2"];
    $soc->ape=$_POST["idprof3"];
    $soc->idprof4=$_POST["idprof4"];
    $soc->typent_id=$_POST["typent_id"];
    $soc->effectif_id=$_POST["effectif_id"];

    $soc->tva_assuj = $_POST["assujtva_value"];
    $soc->status= $_POST["status"];

    //Local Taxes
    $soc->localtax1_assuj       = $_POST["localtax1assuj_value"];
    $soc->localtax2_assuj       = $_POST["localtax2assuj_value"];

    $soc->tva_intra=$_POST["tva_intra"];

    $soc->commercial_id=$_POST["commercial_id"];
    $soc->default_lang=$_POST["default_lang"];

    // We set pays_id, pays_code and label for the selected country
    $soc->pays_id=$_POST["pays_id"]?$_POST["pays_id"]:$mysoc->pays_id;
    if ($soc->pays_id)
    {
        $sql = "SELECT code, libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_pays";
        $sql.= " WHERE rowid = ".$soc->pays_id;
        $resql=$db->query($sql);
        if ($resql)
        {
            $obj = $db->fetch_object($resql);
        }
        else
        {
            dol_print_error($db);
        }
        $soc->pays_code=$obj->code;
        $soc->pays=$obj->libelle;
    }
    $soc->forme_juridique_code=$_POST['forme_juridique_code'];
}

?>

<!-- BEGIN PHP TEMPLATE CARD_EDIT.TPL.PHP PATIENT -->

<?php
print_fiche_titre($langs->trans("EditCompany"));

dol_htmloutput_errors($GLOBALS['error'],$GLOBALS['errors']);

print '<form action="'.$_SERVER["PHP_SELF"].'?socid='.$soc->id.'" method="post" name="formsoc">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="socid" value="'.$soc->id.'">';
print '<input type="hidden" name="private" value="0">';
print '<input type="hidden" name="status" value="'.$soc->status.'">';
print '<input type="hidden" name="client" value="'.$soc->client.'">';
if ($modCodeClient->code_auto || $modCodeFournisseur->code_auto) print '<input type="hidden" name="code_auto" value="1">';

print '<table class="border" width="100%">';

// Name
print '<tr><td><span class="fieldrequired">'.$langs->trans('ThirdPartyName').'</span></td><td><input type="text" size="40" maxlength="60" name="nom" value="'.$soc->nom.'"></td>';

// Prospect/Customer
print '<td width="25%">'.$langs->trans('CustomerCode').'</td><td>';

print '<table class="nobordernopadding"><tr><td>';
if ((!$soc->code_client || $soc->code_client == -1) && $modCodeClient->code_auto)
{
    $tmpcode=$soc->code_client;
    if (empty($tmpcode) && $modCodeClient->code_auto) $tmpcode=$modCodeClient->getNextValue($soc,0);
    print '<input type="text" name="code_client" size="16" value="'.$tmpcode.'" maxlength="15">';
}
else if ($soc->codeclient_modifiable())
{
    print '<input type="text" name="code_client" size="16" value="'.$soc->code_client.'" maxlength="15">';
}
else
{
    print $soc->code_client;
    print '<input type="hidden" name="code_client" value="'.$soc->code_client.'">';
}
print '</td><td>';
$s=$modCodeClient->getToolTip($langs,$soc,0);
print $form->textwithpicto('',$s,1);
print '</td></tr></table>';

print '</td></tr>';

// Barcode
if ($conf->global->MAIN_MODULE_BARCODE)
{
    print '<tr><td valign="top">'.$langs->trans('Gencod').'</td><td colspan="3"><input type="text" name="gencod" value="'.$soc->gencod.'">';
    print '</td></tr>';
}

// Address
print '<tr><td valign="top">'.$langs->trans('Address').'</td><td colspan="3"><textarea name="adresse" cols="40" rows="3" wrap="soft">';
print $soc->address;
print '</textarea></td></tr>';

// Zip / Town
print '<tr><td>'.$langs->trans('Zip').'</td><td>';
print $formcompany->select_ziptown($soc->cp,'zipcode',array('town','selectpays_id','departement_id'),6);
print '</td><td>'.$langs->trans('Town').'</td><td>';
print $formcompany->select_ziptown($soc->ville,'town',array('zipcode','selectpays_id','departement_id'));
print '</td></tr>';

// Country
print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
$form->select_pays($soc->pays_id,'pays_id');
if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
print '</td></tr>';

// State
if (empty($conf->global->SOCIETE_DISABLE_STATE))
{
    print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">';
    $formcompany->select_departement($soc->departement_id,$soc->pays_code);
    print '</td></tr>';
}

// Phone / Fax
print '<tr><td>'.$langs->trans('PhonePerso').'</td><td><input type="text" name="tel" value="'.$soc->tel.'"></td>';
print '<td>'.$langs->trans('PhoneMobile').'</td><td><input type="text" name="fax" value="'.$soc->fax.'"></td></tr>';

// EMail / Web
print '<tr><td>'.$langs->trans('EMail').($conf->global->SOCIETE_MAIL_REQUIRED?'*':'').'</td><td colspan="3"><input type="text" name="email" size="32" value="'.$soc->email.'"></td>';
print '</tr>';

print '<tr>';
// IdProf1 (SIREN for France)
$idprof=$langs->transcountry('ProfId1',$soc->pays_code);
print '<td>'.$idprof.'</td><td>';
print '<input type="text" name="idprof1" size="6" maxlength="6" value="'.$soc->siren.'">';
print '</td>';
// IdProf2 (SIRET for France)
$idprof=$langs->transcountry('ProfId2',$soc->pays_code);
print '<td>'.$idprof.'</td><td>';
print '<input type="text" name="idprof2" size="6" maxlength="6" value="'.$soc->siret.'">';
print '</td>';
print '</tr>';
print '<tr>';
// IdProf3 (APE for France)
$idprof=$langs->transcountry('ProfId3',$soc->pays_code);
print '<td>'.$idprof.'</td><td colspan="3">';
print '<input type="text" name="idprof3" size="18" maxlength="32" value="'.$soc->ape.'">';
print '</td>';
print '</tr>';

// Sexe
print '<tr><td>'.$langs->trans("ThirdPartyType").'</td><td colspan="3">';
print $form->selectarray("typent_id",$formcompany->typent_array(0, "AND code in ('TE_UNKNOWN', 'TE_HOMME', 'TE_FEMME')"), $soc->typent_id);
if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
print '</td>';
print '</tr>';

print '<tr><td>'.$langs->trans('JuridicalStatus').'</td><td>';
$formcompany->select_forme_juridique($soc->forme_juridique_code, $soc->pays_code, "AND f.code > '100000'");
print '</td>';
// IdProf4 (NU for France)
$idprof=$langs->transcountry('ProfId4',$soc->pays_code);
print '<td>'.$idprof.'</td>';
print '<td><input type="text" name="idprof4" size="32" value="'.$soc->idprof4.'"></td>';
print '</tr>';

// Num secu
print '<tr>';
print '<td nowrap="nowrap">'.$langs->trans('VATIntra').'</td>';
print '<td nowrap="nowrap" colspan="3">';
$s ='<input type="text" class="flat" name="tva_intra" size="12" maxlength="20" value="'.$soc->tva_intra.'">';

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
        $s.='<a href="'.$langs->transcountry("VATIntraCheckURL",$soc->id_pays).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
    }
}

print $s;

print '</td>';
print '</tr>';

if ($conf->global->MAIN_MULTILANGS)
{
    print '<tr><td>'.$langs->trans("DefaultLang").'</td><td colspan="3">'."\n";
    print $formadmin->select_language($soc->default_lang,'default_lang',0,0,1);
    print '</td>';
    print '</tr>';
}

print '</table>';
print '<br>';

print '<center>';
print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
print ' &nbsp; &nbsp; ';
print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
print '</center>';

print '</form>';
?>

<!-- END PHP TEMPLATE -->