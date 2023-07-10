<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**     \defgroup   cabinetmed     Module CabinetMed
 *      \brief      Module to manage a medical center
 */

/**
 *      \file       htdocs/cabinetmed/core/modules/modCabinetMed.class.php
 *      \ingroup    cabinetmed
 *      \brief      Description and activation file for module CabinetMed
 */
include_once DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php";


/**
 * Description and activation class for module CabinetMed
 */
class modCabinetMed extends DolibarrModules
{
	/**
	 *  Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *  @param		DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		global $langs,$conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 101700;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'cabinetmed';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "crm";
		// Module position in the family
		$this->module_position = '09';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Module DoliMed - Manage your patients and consultations";

		$this->editor_name = 'DoliCloud';
		$this->editor_url = 'https://www.dolimed.com';

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '10.0.0';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='stetho.png@cabinetmed';

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/cabinetmed/temp");
		$this->dirs = array();
		$r=0;

		// Config pages. Put here list of php page names stored in admin directory used to setup module.
		$this->config_page_url = array('admin.php@cabinetmed');

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			'triggers' => 1,
			'substitutions' => 1,
			'menus' => 1,
			'css' => array('/cabinetmed/css/styles.css.php'),
			'hooks' => array('index', 'searchform', 'thirdpartycard', 'thirdpartycontactcard', 'thirdpartysupplier', 'thirdpartycomm', 'thirdpartypartnership', 'commcard', 'categorycard', 'contactcard', 'actioncard', 'agendathirdparty', 'projectthirdparty', 'infothirdparty', 'thirdpartybancard', 'consumptionthirdparty', 'thirdpartynotification', 'thirdpartymargins', 'thirdpartycustomerprice', 'thirdpartyticket', 'documentcabinetmed', 'searchform', 'demo'),
			'moduleforexternal' => 1
		);

		// Dependencies
		$this->depends = array('modSociete');       // List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();    // List of modules id to disable if this one is disabled
		$this->phpmin = array(5,6);                 // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(15, 0, -3);   // Minimum version of Dolibarr required by module
		$this->langfiles = array('cabinetmed@cabinetmed','companies');

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0)
		//                             2=>array('MAIN_MODULE_MYMODULE_HOOKS','chaine','hookcontext1:hookcontext2','To say: This module manage hooks in hookcontext1 and hookcontext2',1,'current',1)
		// );
		$this->const = array(
							 1=>array('MAIN_MENU_CHEQUE_DEPOSIT_ON','chaine','1','Enabled menu cheque deposit even if module invoice not enabled',1,'current',1),
							 2=>array('CABINETMED_DELAY_TO_LOCK_RECORD','chaine','0','Number of days before locking edit of consultation',1,'current',0),		// Visible, Do not remove if module removed
							 8=>array('MAIN_DISABLEPROFIDRULES','chaine','1','Disable info/check links near professional id fields',1,'current',1),
							 9=>array('MAIN_FORCELANGDIR','chaine','/cabinetmed','Language files are searched into this dir first',1,'current',1),
							11=>array('MAIN_DISABLEVATCHECK','chaine','1','Disable link to VAT check',1,'current',1),
							12=>array('MAIN_DISABLEDRAFTSTATUS','chaine','1','Disable draft status',1,'current',1),
							16=>array('MAIN_SUPPORT_SHARED_CONTACT_BETWEEN_THIRDPARTIES','chaine','1','Can add third party type of contact',1,'current',1),
							17=>array('MAIN_SUPPORT_CONTACT_TYPE_FOR_THIRDPARTIES','chaine','1','Can add third party type of contact',1,'current',1),	// old one. Replaced with MAIN_SUPPORT_SHARED_CONTACT_BETWEEN_THIRDPARTIES
							18=>array('MAIN_APPLICATION_TITLE','chaine','DoliMed '.$this->version,'Change software title',1,'current',1),
							19=>array('CABINETMED_RHEUMATOLOGY_ON','chaine','0','Enable features for rheumatology',0,'current',0),		// Not visible, Do not remove if module removed
							20=>array('SOCIETE_DISABLE_CUSTOMERS','chaine','1','Hide customer features',1,'current',1),
							21=>array('SOCIETE_DISABLE_PROSPECTS','chaine','1','Hide prospect features',1,'current',1),
							22=>array('SOCIETE_DISABLE_PARENTCOMPANY','chaine','1','Hide parent company field',1,'current',1),
							23=>array('CABINETMED_HIDETHIRPARTIESMENU','chaine','1','Hide thirdparties',0,'current',1)					// Not visible
		);

		// Array to add new pages in new tabs
		$this->tabs = array(
						'thirdparty:+tabpatientcontacts:SUBSTITUTION_Correspondants:cabinetmed@cabinetmed:$user->rights->cabinetmed->read && ($object->canvas=="patient@cabinetmed" || $soc->canvas=="patient@cabinetmed"):/cabinetmed/contact.php?socid=__ID__',
						'thirdparty:+tabantecedents:SUBSTITUTION_TabAntecedentsShort:cabinetmed@cabinetmed:$user->rights->cabinetmed->read && ($object->canvas=="patient@cabinetmed" || $soc->canvas=="patient@cabinetmed"):/cabinetmed/antecedant.php?socid=__ID__',
						//'thirdparty:+tabtraitetallergies:TraitEtAllergies:cabinetmed@cabinetmed:/cabinetmed/traitetallergies.php?socid=__ID__',
						'thirdparty:+tabnotes:SUBSTITUTION_NotesPatient:cabinetmed@cabinetmed:$user->rights->cabinetmed->read && ($object->canvas=="patient@cabinetmed" || $soc->canvas=="patient@cabinetmed"):/cabinetmed/notes.php?socid=__ID__',
						'thirdparty:+tabconsultations:SUBSTITUTION_ConsultationsShort:cabinetmed@cabinetmed:$user->rights->cabinetmed->read && ($object->canvas=="patient@cabinetmed" || $soc->canvas=="patient@cabinetmed"):/cabinetmed/consultations.php?socid=__ID__',
						'thirdparty:+tabexambio:SUBSTITUTION_ResultExamBio:cabinetmed@cabinetmed:$user->rights->cabinetmed->read && ($object->canvas=="patient@cabinetmed" || $soc->canvas=="patient@cabinetmed"):/cabinetmed/exambio.php?socid=__ID__',
						'thirdparty:+tabexamautre:SUBSTITUTION_ResultExamAutre:cabinetmed@cabinetmed:$user->rights->cabinetmed->read && ($object->canvas=="patient@cabinetmed" || $soc->canvas=="patient@cabinetmed"):/cabinetmed/examautre.php?socid=__ID__',
						'thirdparty:+tabdocument:SUBSTITUTION_DocumentsPatient:cabinetmed@cabinetmed:$user->rights->cabinetmed->read && ($object->canvas=="patient@cabinetmed" || $soc->canvas=="patient@cabinetmed"):/cabinetmed/documents.php?socid=__ID__',
						'thirdparty:-contact:NU:($object->canvas=="patient@cabinetmed" || $soc->canvas=="patient@cabinetmed" || $obj->canvas=="patient@cabinetmed")',
						'thirdparty:-document:NU:($object->canvas=="patient@cabinetmed" || $soc->canvas=="patient@cabinetmed" || $obj->canvas=="patient@cabinetmed")',
						//'thirdparty:-notify:NU:($object->canvas=="patient@cabinetmed" || $soc->canvas=="patient@cabinetmed" || $obj->canvas=="patient@cabinetmed")',
						'thirdparty:-note:NU:($object->canvas=="patient@cabinetmed" || $soc->canvas=="patient@cabinetmed" || $obj->canvas=="patient@cabinetmed")',
						'contact:+tabpatient:Patients:cabinetmed@cabinetmed:$user->rights->cabinetmed->read:/cabinetmed/patients_of_contact.php?id=__ID__'
					);
		// where entity can be
		// 'thirdparty'       to add a tab in third party view
		// 'intervention'     to add a tab in intervention view
		// 'order_supplier'   to add a tab in supplier order view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'invoice'          to add a tab in customer invoice view
		// 'order'            to add a tab in customer order view
		// 'product'          to add a tab in product view
		// 'stock'            to add a tab in stock view
		// 'propal'           to add a tab in propal view
		// 'member'           to add a tab in fundation member view
		// 'contract'         to add a tab in contract view
		// 'user'             to add a tab in user view
		// 'group'            to add a tab in group view
		// 'contact'          to add a tab in contact view
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)

		// Dictionaries
		if (! isset($conf->cabinetmed->enabled)) {
			$conf->cabinetmed=new stdClass();
			$conf->cabinetmed->enabled = 0;
		}
		$this->dictionaries=array(
			'langs'=>'cabinetmed@cabinetmed',
			'tabname'=>array("cabinetmed_motifcons",
							 "cabinetmed_diaglec",
							 "cabinetmed_examenprescrit",
							 "cabinetmed_c_examconclusion",
							 "cabinetmed_c_banques"
							 ),
			'tablib'=>array("MotifConsultation",
							"DiagnostiqueLesionnel",
							"Examens",
							"ExamenConclusion",
							"BankNameList"
							 //,"ResultatExamBio","ResultatExamAutre"
							 ),
			'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'cabinetmed_motifcons as f',
							'SELECT f.rowid as rowid, f.code, f.label, f.active, f.lang FROM '.MAIN_DB_PREFIX.'cabinetmed_diaglec as f',
							'SELECT f.rowid as rowid, f.code, f.label, f.biorad, f.active FROM '.MAIN_DB_PREFIX.'cabinetmed_examenprescrit as f',
							'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'cabinetmed_c_examconclusion as f',
							'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'cabinetmed_c_banques as f'
							),
			'tabsqlsort'=>array("label ASC", "label ASC","biorad ASC, label ASC","label ASC","label ASC"),
			'tabfield'=>array("code,label","code,label,lang","code,label,biorad","code,label","code,label"), // Nom des champs en resultat de select pour affichage du dictionnaire
			'tabfieldvalue'=>array("code,label","code,label,lang","code,label,biorad","code,label","code,label"),  // Nom des champs d'edition pour modification d'un enregistrement
			'tabfieldinsert'=>array("code,label","code,label,lang","code,label,biorad","code,label","code,label"),
			'tabrowid'=>array("rowid","rowid","rowid","rowid","rowid"),
			'tabcond'=>array(isModEnabled('cabinetmed'),isModEnabled('cabinetmed'),isModEnabled('cabinetmed'),isModEnabled('cabinetmed'),isModEnabled('cabinetmed')),
			'tabhelp'=>array("",array("icd"=>'http://en.wikipedia.org/wiki/International_Statistical_Classification_of_Diseases_and_Related_Health_Problems'),array("biorad"=>"RADIO|BIO|OTHER")),
			'tabfieldcheck'=>array("","",array("biorad"=>"/(RADIO|BIO|AUTRE|OTHER)/"))
		);

		// Boxes
		$this->boxes = array(array('file' => "box_patients@cabinetmed", 'enabledbydefaulton' => 1));	// List of boxes
		$r=0;

		// Add here list of php file(s) stored in includes/boxes that contains class to show a box.
		// Example:
		//$this->boxes[$r][1] = "myboxa.php";
		//$r++;
		//$this->boxes[$r][1] = "myboxb.php";
		//$r++;


		// Permissions
		$this->rights = array();        // Permission array used by this module
		$r=0;

		// Add here list of permission defined by an id, a label, a boolean and two constant strings.
		// Example:
		$this->rights[$r][0] = 101701;               // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read patient outcomes';      // Permission label
		$this->rights[$r][3] = 0;                    // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';               // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';                   // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 101702;               // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Modify patient outcomes';      // Permission label
		$this->rights[$r][3] = 0;                    // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'write';              // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';                   // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		// Main menu entries
		$this->menu = array();         // List of menus to add
		$r=0;

		// Add here entries to declare new menus
		$this->menu[$r]=array(  'fk_menu'=>0,           // Put 0 if this is a top menu
									'type'=>'top',          // This is a Top menu entry
									'titre'=>'PatientsAndConsultations',
									'prefix'=>img_picto('', 'user-injured', 'class="pictofixedwidth"'),
									'mainmenu'=>'patients',
									'url'=>'/cabinetmed/index.php',
									'langs'=>'cabinetmed@cabinetmed',    // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>25,
									'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
									'perms'=>'1',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
									'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(  'fk_menu'=>0,           // Put 0 if this is a top menu
									'type'=>'top',          // This is a Top menu entry
									'titre'=>'Correspondants',
									'prefix'=>img_picto('', 'user-injured', 'class="pictofixedwidth"'),
									'mainmenu'=>'contacts',
									'url'=>'/contact/list.php',
									'langs'=>'cabinetmed@cabinetmed',    // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>26,
									'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
									'perms'=>'1',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
									'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		/*        $this->menu[$r]=array(  'fk_menu'=>0,           // Put 0 if this is a top menu
									'type'=>'top',          // This is a Top menu entry
									'titre'=>'MenuFinancialMed',
									'mainmenu'=>'accountancy2',
									'url'=>'/cabinetmed/compta.php?mainmenu=accountancy2&leftmenu=&search_sale=__USER_ID__',
									'langs'=>'cabinetmed@cabinetmed',    // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>55,
									'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
									'perms'=>'1',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
									'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		*/
		// Left Menu entry:
		$this->menu[$r]=array(  'fk_menu'=>'fk_mainmenu=patients',
								'type'=>'left',         // This is a Left menu entry
								'prefix'=>img_picto('', 'user-injured', 'class="paddingright pictofixedwidth valignmiddle"'),
								'titre'=>'Patients',
								'mainmenu'=>'patients',
								'leftmenu'=>'patients',
								'url'=>'/cabinetmed/index.php?leftmenu=thirdparties',
								'langs'=>'cabinetmed@cabinetmed',  // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>100,
								'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
								'perms'=>'1',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(  'fk_menu'=>'fk_mainmenu=patients,fk_leftmenu=patients',
								'type'=>'left',         // This is a Left menu entry
								'titre'=>'MenuNewPatient',
								'mainmenu'=>'patients',
								'leftmenu'=>'patients_new',
								'url'=>'/cabinetmed/card.php?action=create&canvas=patient@cabinetmed',
								'langs'=>'cabinetmed@cabinetmed',  // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>110,
								'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
								'perms'=>'$user->rights->societe->creer',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
								'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(  'fk_menu'=>'fk_mainmenu=patients,fk_leftmenu=patients',
								'type'=>'left',         // This is a Left menu entry
								'titre'=>'ListPatient',
								'mainmenu'=>'patients',
								'leftmenu'=>'patients_list',
								'url'=>'/cabinetmed/patients.php?leftmenu=customers&search_sale=__USER_ID__',
								'langs'=>'cabinetmed@cabinetmed',  // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>110,
								'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
								'perms'=>'1',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
								'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(   'fk_menu'=>'fk_mainmenu=patients',
							'type'=>'left',         // This is a Left menu entry
							'titre'=>'Consultations',
							'prefix'=>img_picto('', 'briefcase-medical', 'class="paddingright pictofixedwidth valignmiddle"'),
							'mainmenu'=>'patients',
							'leftmenu'=>'consultations',
							'url'=>'/cabinetmed/index.php?leftmenu=thirdparties',
							'langs'=>'cabinetmed@cabinetmed',  // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
							'position'=>120,
							'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
							'perms'=>'1',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
							'target'=>'',
							'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(   'fk_menu'=>'fk_mainmenu=patients,fk_leftmenu=consultations',
							'type'=>'left',         // This is a Left menu entry
							'titre'=>'NewConsultation',
							'mainmenu'=>'patients',
							'leftmenu'=>'consultations_new',
							'url'=>'/cabinetmed/consultations.php?action=create&canvas=patient@cabinetmed',
							'langs'=>'cabinetmed@cabinetmed',  // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
							'position'=>121,
							'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
							'perms'=>'$user->rights->societe->creer',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
							'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(   'fk_menu'=>'fk_mainmenu=patients,fk_leftmenu=consultations',
								'type'=>'left',         // This is a Left menu entry
								'titre'=>'ListConsult',
								'mainmenu'=>'patients',
								'leftmenu'=>'consultations_list',
								'url'=>'/cabinetmed/listconsult.php?leftmenu=customers&search_sale=__USER_ID__',
								'langs'=>'cabinetmed@cabinetmed',  // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>122,
								'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
								'perms'=>'1',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
								'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(   'fk_menu'=>'fk_mainmenu=patients,fk_leftmenu=consultations',
							'type'=>'left',         // This is a Left menu entry
							'titre'=>'Statistics',
							'mainmenu'=>'patients',
							'leftmenu'=>'consultations_stats',
							'url'=>'/cabinetmed/stats/index.php?leftmenu=customers&userid=__USER_ID__',
							'langs'=>'cabinetmed@cabinetmed',  // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
							'position'=>123,
							'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
							'perms'=>'1',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
							'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(   'fk_menu'=>'fk_mainmenu=patients,fk_leftmenu=consultations',
								'type'=>'left',         // This is a Left menu entry
								'titre'=>'Revenues',
								'mainmenu'=>'patients',
								'leftmenu'=>'consultations_compta',
								'url'=>'/cabinetmed/compta.php?mainmenu=patients&leftmenu=&search_sale=__USER_ID__',
								'langs'=>'cabinetmed@cabinetmed',  // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>124,
								'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
								'perms'=>'1',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
								'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both

		$r++;
		// Categories
		$this->menu[$r]=array(   'fk_menu'=>'fk_mainmenu=patients,fk_leftmenu=patients',
								'type'=>'left',         // This is a Left menu entry
								'titre'=>'PatientsCategoriesShort',
								'mainmenu'=>'patients',
								'leftmenu'=>'categorypatients',
								'url'=>'/categories/index.php?leftmenu=categorypatients&type=2',
								'langs'=>'categories',  // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>151,
								'enabled'=>'isModEnabled("categorie")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
								'perms'=>'$user->rights->categorie->lire',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;

		// Left menu contacts
		$this->menu[$r]=array(   'fk_menu'=>'fk_mainmenu=contacts',
		'type'=>'left',         // This is a Left menu entry
		'titre'=>'Correspondants',
		'prefix'=>img_picto('', 'user-md', 'class="paddingright pictofixedwidth valignmiddle"'),
		'mainmenu'=>'contacts',
		'leftmenu'=>'contacts',
		'url'=>'/contact/list.php',
		'langs'=>'companies',  // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		'position'=>110,
		'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
		'perms'=>'$user->rights->societe->contact->lire',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
		'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(   'fk_menu'=>'fk_mainmenu=contacts,fk_leftmenu=contacts',
		'type'=>'left',         // This is a Left menu entry
		'titre'=>'NewContact',
		'mainmenu'=>'contacts',
		'leftmenu'=>'',
		'url'=>'/contact/card.php?leftmenu=contacts&amp;action=create',
		'langs'=>'companies',  // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		'position'=>120,
		'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
		'perms'=>'1',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
		'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(   'fk_menu'=>'fk_mainmenu=contacts,fk_leftmenu=contacts',
		'type'=>'left',         // This is a Left menu entry
		'titre'=>'List',
		'mainmenu'=>'contacts',
		'leftmenu'=>'',
		'url'=>'/contact/list.php',
		'langs'=>'companies',  // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		'position'=>130,
		'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
		'perms'=>'$user->rights->societe->contact->lire',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
		'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(   'fk_menu'=>'fk_mainmenu=contacts,fk_leftmenu=contacts',
		'type'=>'left',         // This is a Left menu entry
		'titre'=>'Statistics',
		'mainmenu'=>'contacts',
		'leftmenu'=>'',
		'url'=>'/cabinetmed/stats/index_contacts.php?leftmenu=customers&userid=__USER_ID__',
		'langs'=>'',  // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		'position'=>140,
		'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
		'perms'=>'$user->rights->societe->lire',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
		'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		// Left menu financial
		$this->menu[$r]=array(   'fk_menu'=>'fk_mainmenu=accountancy2',
		'type'=>'left',         // This is a Left menu entry
		'titre'=>'ReportingsMed',
		'mainmenu'=>'accountancy2',
		'leftmenu'=>'',
		'url'=>'/cabinetmed/compta.php?mainmenu=accountancy2&leftmenu=&search_sale=__USER_ID__',
		'langs'=>'',  // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		'position'=>100,
		'enabled'=>'isModEnabled("cabinetmed")',         // Define condition to show or hide menu entry. Use '$conf->voyage->enabled' if entry must be visible if module is enabled.
		'perms'=>'$user->rights->cabinetmed->read',           // Use 'perms'=>'$user->rights->voyage->level1->level2' if you want your menu with a permission rules
		'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
		$r++;

		// Exports
		$r=0;

		// Export list of patient and attributes
		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='ExportDataset_patient_1';
		$this->export_icon[$r]='company';
		$this->export_permission[$r]=array(array("societe","export"));
		$this->export_fields_array[$r]=array('s.rowid'=>"Id",'s.nom'=>"Name",'s.datec'=>"DateCreation",'s.tms'=>"DateLastModification",'s.code_client'=>"CustomerCode",'s.address'=>"Address",'s.zip'=>"Zip",'s.town'=>"Town",'d.nom'=>'State','p.label'=>"Country",'p.code'=>"CountryCode",'s.phone'=>"Phone",'s.fax'=>"Mobile",'s.url'=>"Url",'s.email'=>"Email",'s.siret'=>"Taille",'s.siren'=>"Poids",'s.ape'=>"Date de naissance",'s.idprof4'=>"Profession",'s.tva_intra'=>"INSEE",'s.capital'=>"Tarif de base consultation",'s.note_public'=>"Note",'t.libelle'=>"ThirdPartyType",'ce.code'=>"Regime","cfj.libelle"=>"JuridicalStatus",
		'pa.note_antemed'=>'AntecedentsMed',
		'pa.note_antechirgen'=>'AntecedentsChirGene',
		'pa.note_antechirortho'=>'AntecedentsChirOrtho',
		'pa.note_anterhum'=>'AntecedentsRhumato',
		'pa.note_other'=>'Other',
		//        'pa.note_traitclass'=>'Classes',
		'pa.note_traitallergie'=>'Allergies',
		'pa.note_traitintol'=>'Intolerances',
		'pa.note_traitspec'=>'SpecPharma',
		'co.rowid'=>'IdConsult',
		'co.datecons'=>'DateConsultation',
		'co.fk_user'=>'Author',
		'co.typepriseencharge'=>'TypePriseEnCharge',
		'co.motifconsprinc'=>'MotifPrincipal',
		'co.motifconssec'=>'MotifSecondaires',
		'co.diaglesprinc'=>'DiagLesPrincipal',
		'co.diaglessec'=>'DiagLesSecondaires',
		'co.hdm'=>'HistoireDeLaMaladie',
		'co.examenclinique'=>'ExamensCliniques',
		'co.examenprescrit'=>'ExamensPrescrits',
		'co.traitementprescrit'=>'TraitementsPrescrits',
		'co.comment'=>'Comment',
		'co.typevisit'=>'TypeVisite',
		'co.infiltration'=>'Infiltration',
		'co.codageccam'=>'CCAM',
		'co.montant_cheque'=>'MontantCheque',
		'co.montant_espece'=>'MontantEspece',
		'co.montant_carte'=>'MontantCarte',
		'co.montant_tiers'=>'MontantTiers',
		'co.banque'=>'Banque'
		);
		$this->export_entities_array[$r]=array(
		'co.rowid'=>'generic:Consultation',
		'co.datecons'=>'generic:Consultation',
		'co.fk_user'=>'generic:Consultation',
		'co.typepriseencharge'=>'generic:Consultation',
		'co.motifconsprinc'=>'generic:Consultation',
		'co.motifconssec'=>'generic:Consultation',
		'co.diaglesprinc'=>'generic:Consultation',
		'co.diaglessec'=>'generic:Consultation',
		'co.hdm'=>'generic:Consultation',
		'co.examenclinique'=>'generic:Consultation',
		'co.examenprescrit'=>'generic:Consultation',
		'co.traitementprescrit'=>'generic:Consultation',
		'co.comment'=>'generic:Consultation',
		'co.typevisit'=>'generic:Consultation',
		'co.infiltration'=>'generic:Consultation',
		'co.codageccam'=>'generic:Consultation',
		'co.montant_cheque'=>'generic:Consultation',
		'co.montant_espece'=>'generic:Consultation',
		'co.montant_carte'=>'generic:Consultation',
		'co.montant_tiers'=>'generic:Consultation',
		'co.banque'=>'generic:Consultation'
		);   // We define here only fields that use another picto

		$keyforselect='cabinetmed_cons'; $keyforaliasextra='extra'; $keyforelement='consultation';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'societe as s';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'cabinetmed_patient as pa ON s.rowid = pa.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'cabinetmed_cons as co ON s.rowid = co.fk_soc';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'cabinetmed_cons_extrafields as extra ON co.rowid = extra.fk_object';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_typent as t ON s.fk_typent = t.id';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as p ON s.fk_pays = p.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_effectif as ce ON s.fk_effectif = ce.id';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_forme_juridique as cfj ON s.fk_forme_juridique = cfj.code';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as d ON s.fk_departement = d.rowid';
		$this->export_sql_end[$r] .=' WHERE s.entity = '.$conf->entity;
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories.
	 *
	 *  @param      string	$options	Options when disabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	function init($options = '')
	{
		global $langs;

		$result=$this->load_tables();

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$dirodt=DOL_DATA_ROOT.'/doctemplates/thirdparties';
		dol_mkdir($dirodt);
		$src=dol_buildpath('/cabinetmed/install/doctemplates/thirdparties/template_consultation.odt');
		$dest=$dirodt.'/template_consultation.odt';
		$result=dol_copy($src, $dest, 0, 0);
		if ($result < 0) {
			$langs->load("errors");
			$this->error=$langs->trans('ErrorFailToCopyFile', $src, $dest);
			return 0;
		}

		$src=dol_buildpath('/cabinetmed/install/doctemplates/thirdparties/template_prescription.odt');
		$dest=$dirodt.'/template_prescription.odt';
		$result=dol_copy($src, $dest, 0, 0);
		if ($result < 0) {
			$langs->load("errors");
			$this->error=$langs->trans('ErrorFailToCopyFile', $src, $dest);
			return 0;
		}

		$sql = array(
		"UPDATE ".MAIN_DB_PREFIX."c_typent          set active=1 where module = 'cabinetmed'",
		"UPDATE ".MAIN_DB_PREFIX."c_forme_juridique set active=1 where module = 'cabinetmed'",
		"UPDATE ".MAIN_DB_PREFIX."c_type_contact    set active=1 where module = 'cabinetmed'",
		"UPDATE ".MAIN_DB_PREFIX."c_typent          set active=0 where (module != 'cabinetmed' OR module IS NULL) AND code != 'TE_UNKNOWN'",
		"UPDATE ".MAIN_DB_PREFIX."c_forme_juridique set active=0 where module != 'cabinetmed' OR module IS NULL",
		"UPDATE ".MAIN_DB_PREFIX."c_type_contact    set active=0 where element='societe' and source='external' and (module != 'cabinetmed' OR module IS NULL)"
		);

		$ignoreerror=1;
		$sqlwithignoreerror="INSERT INTO ".MAIN_DB_PREFIX."document_model set nom='generic_odt', type='company', libelle='ODT templates', description='COMPANY_ADDON_PDF_ODT_PATH'";
		$this->db->query($sqlwithignoreerror, $ignoreerror);

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted.
	 *
	 *  @param      string	$options	Options when disabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	function remove($options = '')
	{
		$sql = array(
		"UPDATE ".MAIN_DB_PREFIX."c_actioncomm      set active=0 where module = 'cabinetmed'",
		"UPDATE ".MAIN_DB_PREFIX."c_typent          set active=0 where module = 'cabinetmed'",
		"UPDATE ".MAIN_DB_PREFIX."c_forme_juridique set active=0 where module = 'cabinetmed'",
		"UPDATE ".MAIN_DB_PREFIX."c_type_contact    set active=0 where module = 'cabinetmed'",
		"UPDATE ".MAIN_DB_PREFIX."c_typent          set active=1 where module != 'cabinetmed' OR module IS NULL",
		"UPDATE ".MAIN_DB_PREFIX."c_forme_juridique set active=1 where module != 'cabinetmed' OR module IS NULL",
		"UPDATE ".MAIN_DB_PREFIX."c_type_contact    set active=1 where element='societe' and source='external' and (module != 'cabinetmed' OR module IS NULL)"
		);

		// Create extrafields
		/*
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);
		$result1=$extrafields->delete('prof', 'thirdparty');
		$result2=$extrafields->delete('height', 'thirdparty');
		$result3=$extrafields->delete('weight', 'thirdparty');
		*/

		return $this->_remove($sql, $options);
	}


	/**
	 *     Create tables, keys and data required by module
	 *     Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 *     and create data commands must be stored in directory /voyage/sql/
	 *     This function is called by this->init.
	 *
	 *     @return     int     <=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		global $langs;

		$langs->load("cabinetmed@cabinetmed");
		$langs->load("other");

		// Create extrafields
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);
		$result1=$extrafields->addExtraField('height', $langs->trans("HeightPeople"), 'varchar', 1, 128, 'thirdparty');
		$result2=$extrafields->addExtraField('weight', $langs->trans("WeigthPeople"), 'varchar', 2, 128, 'thirdparty');
		$result3=$extrafields->addExtraField('prof', $langs->trans("Profession"), 'varchar', 3, 128, 'thirdparty');
		$result4=$extrafields->addExtraField('birthdate', $langs->trans(((float) DOL_VERSION < 13) ? 'DateToBirth' : 'DateOfBirth'), 'date', 4, 0, 'thirdparty');

		return $this->_load_tables('/cabinetmed/sql/');
	}
}
