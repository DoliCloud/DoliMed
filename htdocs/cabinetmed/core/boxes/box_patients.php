<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 */

/**
 *	\file       htdocs/core/boxes/box_patients.php
 *	\ingroup    cabinetmed
 *	\brief      Module de generation de l'affichage de la box patient
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last thirdparties
 */
class box_patients extends ModeleBoxes
{
	var $boxcode="lastpatients";
	var $boximg="object_company";
	var $boxlabel="BoxLastPatients";
	var $depends = array("cabinetmed@cabinetmed");

	var $db;
	var $enabled = 1;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
	 *  @param	string	$param		More parameters
	 */
	function __construct($db, $param = '')
	{
		global $conf, $user;

		$this->db = $db;
	}

	/**
	 *  Load data for box to show them later
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	function loadBox($max = 5)
	{
		global $user, $langs, $db, $conf;
		$langs->load("boxes");

		$this->max=$max;

		include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		$thirdpartystatic=new Societe($db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastModifiedPatients", $max));

		if ($user->rights->societe->lire) {
			$sql = "SELECT s.nom, s.rowid as socid, s.datec, s.tms, s.status";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql.= " WHERE s.client IN (1, 3) and s.canvas = 'patient@cabinetmed'";
			$sql.= " AND s.entity IN (".getEntity('societe', 1).")";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
			if ($user->socid) $sql.= " AND s.rowid = $user->socid";
			$sql.= " ORDER BY s.tms DESC";
			$sql.= $db->plimit($max, 0);

			dol_syslog(get_class($this)."::loadBox", LOG_DEBUG);
			$result = $db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);
				if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) $url= DOL_URL_ROOT."/comm/card.php?socid=";
				else {
					if ((float) DOL_VERSION < 6.0) $url= DOL_URL_ROOT."/societe/soc.php?socid=";
					else $url= DOL_URL_ROOT."/societe/card.php?socid=";
				}

				$i = 0;
				while ($i < $num) {
					$objp = $db->fetch_object($result);
					$datec=$db->jdate($objp->datec);
					$datem=$db->jdate($objp->tms);

					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
					'logo' => $this->boximg,
					'url' => $url.$objp->socid);

					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
					'text' => $objp->nom,
					'url' => $url.$objp->socid);

					$this->info_box_contents[$i][2] = array('td' => 'align="right"',
					'text' => dol_print_date($datem, "dayhour"));

					$this->info_box_contents[$i][3] = array('td' => 'align="right" width="18"',
					'text' => $thirdpartystatic->LibStatut($objp->status, 3));

					$i++;
				}

				if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoRecordedPatients"));

				$db->free($result);
			} else {
				$this->info_box_contents[0][0] = array(	'td' => 'align="left"',
														'maxlength'=>500,
														'text' => ($db->error().' sql='.$sql));
			}
		} else {
			$this->info_box_contents[0][0] = array('align' => 'left',
			'text' => $langs->trans("ReadPermissionNotAllowed"));
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
