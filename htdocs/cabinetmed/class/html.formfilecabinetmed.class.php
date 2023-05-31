<?php
/* Copyright (c) 2008-2011 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2011 Regis Houssin		<regis@dolibarr.fr>
 * Copyright (c) 2010      Juanjo Menent		<jmenent@2byte.es>
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
 *	\file       htdocs/cabinetmed/class/html.formfilecabinetmed.class.php
 *  \ingroup    core
 *	\brief      File of class to offer components to list and upload files
 */

include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';


/**
 *	FormFileCabinetmed
 */
class FormFileCabinetmed extends FormFile
{
	var $db;
	var $error;

	var $numoffiles;


	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		$this->numoffiles=0;

		return 1;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Show list of documents in $filearray (may be they are all in same directory but may not)
	 *  This also sync database if $upload_dir is defined.
	 *
	 *  @param	 array	$filearray          Array of files loaded by dol_dir_list('files') function before calling this.
	 * 	@param	 Object	$object				Object on which document is linked to.
	 * 	@param	 string	$modulepart			Value for modulepart used by download or viewimage wrapper.
	 * 	@param	 string	$param				Parameters on sort links (param must start with &, example &aaa=bbb&ccc=ddd)
	 * 	@param	 int	$forcedownload		Force to open dialog box "Save As" when clicking on file.
	 * 	@param	 string	$relativepath		Relative path of docs (autodefined if not provided), relative to module dir, not to MAIN_DATA_ROOT.
	 * 	@param	 int	$permonobject		Permission on object (so permission to delete or crop document)
	 * 	@param	 int	$useinecm			Change output for use in ecm module:
	 * 										0 or 6: Add a preview column. Show also a rename button. Show also a crop button for some values of $modulepart (must be supported into hard coded list in this function + photos_resize.php + restrictedArea + checkUserAccessToObject)
	 * 										1: Add link to edit ECM entry
	 * 										2: Add rename and crop link
	 *                                      4: Add a preview column
	 *                                      5: Add link to edit ECM entry and Add a preview column
	 * 	@param	 string	$textifempty		Text to show if filearray is empty ('NoFileFound' if not defined)
	 *  @param   int	$maxlength          Maximum length of file name shown.
	 *  @param	string	$title				Title before list. Use 'none' to disable title.
	 *  @param	string  $url				Full url to use for click links ('' = autodetect)
	 *  @param	int	    $showrelpart		0=Show only filename (default), 1=Show first level 1 dir
	 *  @param  int     $permtoeditline     Permission to edit document line (You must provide a value, -1 is deprecated and must not be used any more)
	 *  @param  string  $upload_dir         Full path directory so we can know dir relative to MAIN_DATA_ROOT. Fill this to complete file data with database indexes.
	 *  @param  string  $sortfield          Sort field ('name', 'size', 'position', ...)
	 *  @param  string  $sortorder          Sort order ('ASC' or 'DESC')
	 *  @param  int     $disablemove        1=Disable move button, 0=Position move is possible.
	 *  @param	int	    $addfilterfields	Add line with filters
	 *  @param	int		$disablecrop		Disable crop feature on images (-1 = auto, prefer to set it explicitely to 0 or 1)
	 *  @param	string	$moreattrondiv		More attributes on the div for responsive. Example 'style="height:280px; overflow: auto;"'
	 *  @return int                 		<0 if KO, nb of files shown if OK
	 *  @see list_of_autoecmfiles()
	 */
	public function list_of_documents($filearray, $object, $modulepart, $param = '', $forcedownload = 0, $relativepath = '', $permonobject = 1, $useinecm = 0, $textifempty = '', $maxlength = 0, $title = '', $url = '', $showrelpart = 0, $permtoeditline = -1, $upload_dir = '', $sortfield = '', $sortorder = 'ASC', $disablemove = 1, $addfilterfields = 0, $disablecrop = -1, $moreattrondiv = '')
	{
        // phpcs:enable
		global $user, $conf, $langs, $hookmanager;
		global $sortfield, $sortorder, $maxheightmini;
		global $dolibarr_main_url_root;
		global $form;

		$disablecrop=1;
		if (in_array($modulepart, array('bom','expensereport','holiday','member','project','product','produit','service','societe','tax','ticket','user'))) {
			$disablecrop=0;
		}

		// Define relative path used to store the file
		if (empty($relativepath)) {
			$relativepath=(! empty($object->ref)?dol_sanitizeFileName($object->ref):'').'/';
			if ($object->element == 'invoice_supplier') $relativepath=get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier').$relativepath;	// TODO Call using a defined value for $relativepath
			if ($object->element == 'project_task') $relativepath='Call_not_supported_._Call_function_using_a_defined_relative_path_.';
		}
		// For backward compatiblity, we detect file stored into an old path
		if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO) && $filearray[0]['level1name'] == 'photos') {
			$relativepath=preg_replace('/^.*\/produit\//', '', $filearray[0]['path']).'/';
		}
		// Defined relative dir to DOL_DATA_ROOT
		$relativedir = '';
		if ($upload_dir) {
			$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $upload_dir);
			$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
		}

		$hookmanager->initHooks(array('formfile'));
		$parameters=array(
			'filearray' => $filearray,
			'modulepart'=> $modulepart,
			'param' => $param,
			'forcedownload' => $forcedownload,
			'relativepath' => $relativepath,    // relative filename to module dir
			'relativedir' => $relativedir,      // relative dirname to DOL_DATA_ROOT
			'permtodelete' => $permonobject,
			'permissiontodelete' => $permonobject,
			'useinecm' => $useinecm,
			'textifempty' => $textifempty,
			'maxlength' => $maxlength,
			'title' => $title,
			'url' => $url
		);
		$reshook=$hookmanager->executeHooks('showFilesList', $parameters, $object);
		if (!isset($reshook) || $reshook === '') { // null or '' for bypass
			return $reshook;
		} else {
			if (! is_object($form)) {
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';		// The compoent may be included into ajax page that does not include the Form class
				$form=new Form($this->db);
			}

			if (! preg_match('/&id=/', $param) && isset($object->id)) $param.='&id='.$object->id;
			$relativepathwihtoutslashend=preg_replace('/\/$/', '', $relativepath);
			if ($relativepathwihtoutslashend) $param.= '&file='.urlencode($relativepathwihtoutslashend);

			if ($permtoeditline < 0) {  // Old behaviour for backward compatibility. New feature should call method with value 0 or 1
				$permtoeditline=0;
				if (in_array($modulepart, array('product','produit','service'))) {
					if ($user->rights->produit->creer && $object->type == Product::TYPE_PRODUCT) $permtoeditline=1;
					if ($user->rights->service->creer && $object->type == Product::TYPE_SERVICE) $permtoeditline=1;
				}
			}
			if (empty($conf->global->MAIN_UPLOAD_DOC)) {
				$permtoeditline=0;
				$permonobject=0;
			}

			// Show list of existing files
			if ((empty($useinecm) || $useinecm == 6) && $title != 'none') print load_fiche_titre($title?$title:$langs->trans("AttachedFiles"));
			if (empty($url)) $url=$_SERVER["PHP_SELF"];

			print '<!-- html.formfile::list_of_documents -->'."\n";
			print '<div class="div-table-responsive-no-min">';
			print '<table width="100%" id="tablelines" class="liste noborder nobottom">'."\n";
			print '<tr class="liste_titre">';
			print_liste_field_titre($langs->trans("Date"), $_SERVER["PHP_SELF"], "date", "", $param, 'align="left"', $sortfield, $sortorder);
			print_liste_field_titre($langs->trans("Documents2"), $_SERVER["PHP_SELF"], "name", "", $param, 'align="left"', $sortfield, $sortorder);
			print_liste_field_titre($langs->trans("Size"), $_SERVER["PHP_SELF"], "size", "", $param, 'align="right"', $sortfield, $sortorder);
			print_liste_field_titre('', '', '');
			print_liste_field_titre('', '', '');
			if (empty($useinecm)) print_liste_field_titre('', $_SERVER["PHP_SELF"], "", "", $param, 'align="center"');
			print_liste_field_titre('', '', '');
			print '</tr>';

			$nboffiles=count($filearray);
			if ($nboffiles > 0) include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

			$i=0; $nboflines = 0; $lastrowid=0;
			foreach ($filearray as $key => $file) {      // filearray must be only files here
				if ($file['name'] != '.' && $file['name'] != '..' && $file['name'] != 'CVS' && ! preg_match('/\.meta$/i', $file['name'])) {
					if (!empty($filearray[$key]['rowid']) && $filearray[$key]['rowid'] > 0) {
						$lastrowid = $filearray[$key]['rowid'];
					}
					$filepath=$relativepath.$file['name'];

					// Define relative path used to store the file
					if (! $relativepath) $relativepath=dol_sanitizeFileName($object->ref).'/';

					$editline=0;
					$nboflines++;
					print '<!-- Line list_of_documents '.$key.' relativepath = '.$relativepath.' -->'."\n";
					// Do we have entry into database ?
					print '<!-- In database: position='.$filearray[$key]['position'].' -->'."\n";
					print '<tr class="oddeven" id="row-'.((!empty($filearray[$key]['rowid']) && $filearray[$key]['rowid'] > 0) ? $filearray[$key]['rowid'] : 'AFTER'.$lastrowid.'POS'.($i+1)).'">';

					// Date
					print '<td class="left" style="width: 140px">'.dol_print_date($file['date'], "dayhour", "tzuser").'</td>';	// 140px = width for date with PM format

					// File name
					print '<td class="minwith200">';

					// Show file name with link to download
					//print "XX".$file['name']; //$file['name'] must be utf8
					print '<a class="paddingright" href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart;
					if ($forcedownload) print '&attachment=1';
					if (! empty($object->entity)) print '&entity='.$object->entity;
					print '&file='.urlencode($filepath);
					print '">';
					print img_mime($file['name'], $file['name'].' ('.dol_print_size($file['size'], 0, 0).')', 'inline-block valignbottom paddingright');
					if ($showrelpart == 1) print $relativepath;
					//print dol_trunc($file['name'],$maxlength,'middle');
					if (GETPOST('action', 'aZ09') == 'editfile' && $file['name'] == basename(GETPOST('urlfile', 'alpha'))) {
						print '</a>';
						$section_dir=dirname(GETPOST('urlfile', 'alpha'));
						print '<input type="hidden" name="section_dir" value="'.$section_dir.'">';
						print '<input type="hidden" name="renamefilefrom" value="'.dol_escape_htmltag($file['name']).'">';
						print '<input type="text" name="renamefileto" class="quatrevingtpercent" value="'.dol_escape_htmltag($file['name']).'">';
						$editline=1;
					} else {
						print dol_trunc($file['name'], 200);
						print '</a>';
					}
					// Preview link
					if (! $editline) print $this->showPreview($file, $modulepart, $filepath, 0, '&entity='.(! empty($object->entity)?$object->entity:$conf->entity));

					print "</td>\n";

					// Size
					$sizetoshow = dol_print_size($file['size'], 1, 1);
					$sizetoshowbytes = dol_print_size($file['size'], 0, 1);

					print '<td class="right" style="width: 80px">';
					if ($sizetoshow == $sizetoshowbytes) print $sizetoshow;
					else {
						print $form->textwithpicto($sizetoshow, $sizetoshowbytes, -1);
					}
					print '</td>';

					// Preview
					if (empty($useinecm) || $useinecm == 4 || $useinecm == 5|| $useinecm == 6) {
						/*print '<td class="center">';
						$tmp=explode('.',$file['name']);
						$minifile=$tmp[0].'_mini.'.$tmp[1];
						if (image_format_supported($file['name']) > 0) print '<img border="0" height="'.$maxheightmini.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&file='.urlencode($relativepath.'thumbs/'.$minifile).'" title="">';
						else print '&nbsp;';
						print '</td>';*/
						$fileinfo = pathinfo($file['name']);
						print '<td class="center">';
						if (image_format_supported($file['name']) > 0) {
							if ($useinecm == 5 || $useinecm == 6) {
								$minifile=getImageFileNameForSize($file['name'], '');     // There is no thumb for ECM module and Media filemanager, so we use true image
							} else {
								$minifile=getImageFileNameForSize($file['name'], '_mini'); // For new thumbs using same ext (in lower case howerver) than original
							}
							if (! dol_is_file($file['path'].'/'.$minifile)) $minifile=getImageFileNameForSize($file['name'], '_mini', '.png'); // For backward compatibility of old thumbs that were created with filename in lower case and with .png extension
							//print $file['path'].'/'.$minifile.'<br>';

							$urlforhref=getAdvancedPreviewUrl($modulepart, $relativepath.$fileinfo['filename'].'.'.strtolower($fileinfo['extension']), 1, '&entity='.(!empty($object->entity)?$object->entity:$conf->entity));
							if (empty($urlforhref)) {
								$urlforhref=DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.(!empty($object->entity)?$object->entity:$conf->entity).'&file='.urlencode($relativepath.$fileinfo['filename'].'.'.strtolower($fileinfo['extension']));
								print '<a href="'.$urlforhref.'" class="aphoto" target="_blank">';
							} else {
								print '<a href="'.$urlforhref['url'].'" class="'.$urlforhref['css'].'" target="'.$urlforhref['target'].'" mime="'.$urlforhref['mime'].'">';
							}
							print '<img class="photo" height="'.(($useinecm == 4 || $useinecm == 5 || $useinecm == 6)? '12' : $maxheightmini).'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.(!empty($object->entity)?$object->entity:$conf->entity).'&file='.urlencode($relativepath.$minifile).'" title="">';
							print '</a>';
						} else print '&nbsp;';
						print '</td>';
					}

					// Hash of file (only if we are in a mode where a scan of dir were done and we have id of file in ECM table)
					print '<td class="center">';
					if ($relativedir && !empty($filearray[$key]['rowid']) && $filearray[$key]['rowid'] > 0) {
						if ($editline) {
							print $langs->trans("FileSharedViaALink").' ';
							print '<input class="inline-block" type="checkbox" name="shareenabled"'.($file['share']?' checked="checked"':'').' /> ';
						} else {
							if ($file['share']) {
								// Define $urlwithroot
								$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
								$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
								//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

								//print '<span class="opacitymedium">'.$langs->trans("Hash").' : '.$file['share'].'</span>';
								$forcedownload=0;
								$paramlink='';
								if (! empty($file['share'])) $paramlink.=($paramlink?'&':'').'hashp='.$file['share'];			// Hash for public share
								if ($forcedownload) $paramlink.=($paramlink?'&':'').'attachment=1';

								$fulllink=$urlwithroot.'/document.php'.($paramlink?'?'.$paramlink:'');

								print img_picto($langs->trans("FileSharedViaALink"), 'object_globe.png').' ';
								print '<input type="text" class="quatrevingtpercent minwidth200imp" id="downloadlink" name="downloadexternallink" value="'.dol_escape_htmltag($fulllink).'">';
							} else {
								//print '<span class="opacitymedium">'.$langs->trans("FileNotShared").'</span>';
							}
						}
					}
					print '</td>';

					// Send by email
					print '<td class="right">';
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=presend&mode=init&socid='.$object->id.'&urlfile='.urlencode($file['name']).'#sendform">';
					print img_object($langs->trans("SendOutcomeByEmail"), 'email');
					print '</a>';
					print '</td>';

					// Actions buttons
					if (! $editline) {
						// Delete or view link ($param must start with &)
						// The permission must be permission to add/update a record (managing attachment is updating a record)
						print '<td class="valignmiddle right actionbuttons nowraponall"><!-- action on files -->';
						if ($permtoeditline) print '<a class="reposition" href="'.$url.'?socid='.$object->id.'&section='.GETPOST("section", "aplha").'&action=delete&token='.newToken().'&urlfile='.urlencode($file['name']).'">'.img_delete().'</a>';
						else print '&nbsp;';
						print "</td>";
					} else {
						print '<td class="right">';
						print '<input type="hidden" name="ecmfileid" value="'.$filearray[$key]['rowid'].'">';
						print '<input type="submit" class="button" name="renamefilesave" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
						print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
						print '</td>';
						if (empty($disablemove)) print '<td class="right"></td>';
					}
					print "</tr>\n";

					$i++;
				}
			}
			if ($nboffiles == 0) {
				$colspan='6';
				if (empty($disablemove)) $colspan++;		// 6 columns or 7
				print '<tr class="oddeven"><td colspan="'.$colspan.'">';
				if (empty($textifempty)) {
					print '<span class="opacitymedium">'.$langs->trans("NoFileFound").'</span>';
				} else {
					print $textifempty;
				}
				print '</td></tr>';
			}
			print "</table>";
			print '</div>';

			if ($nboflines > 1 && is_object($object)) {
				if (! empty($conf->use_javascript_ajax) && $permtoeditline) {
					$table_element_line = 'ecm_files';
					include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
				}
			}

			print ajax_autoselect('downloadlink');

			return $nboffiles;
		}
	}
}
