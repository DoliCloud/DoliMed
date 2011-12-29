<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \defgroup   mantis     Module mantis
 * \brief      Module to include Mantis into Dolibarr
 * \file       htdocs/mantis/core/modules/modMantis.class.php
 * \ingroup    mantis
 * \brief      Description and activation file for module Mantis
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 * \class      modMantis
 * \brief      Description and activation class for module Mantis
 */

class modMantis extends DolibarrModules
{

    /**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param		DoliDB		$db		Database handler
     */
	function modMantis($db)
	{
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id.
		$this->numero = 50300;

		// Family can be 'crm','financial','hr','projects','product','technic','other'
		// It is used to sort modules in module setup page
		$this->family = "projects";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description used translation string 'ModuleXXXDesc' not found (XXX is id value)
		$this->description = "Interfacage avec le bug tracking Mantis";
		// Possible values for version are: 'experimental' or 'dolibarr' or version
		$this->version = 'development';
		// Id used in llx_const table to manage module status (enabled/disabled)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=other)
		$this->special = 1;
		// Name of png file (without png) used for this module
		$this->picto='calendar';

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Config pages
		$this->config_page_url = array("mantis.php@mantis");

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled

		// Constants
		$this->const = array();			// List of parameters

		// Boxes
		$this->boxes = array();			// List of boxes

		// Permissions
		$this->rights_class = 'mantis';	// Permission key
		$this->rights = array();		// Permission array used by this module

        // Menus
		//------
		$r=0;

		$this->menu[$r]=array('fk_menu'=>0,
		                      'type'=>'top',
		                      'titre'=>'BugTracker',
		                      'mainmenu'=>'mantis',
		                      'url'=>'/mantis/mantis.php',
		                      'langs'=>'other',
		                      'position'=>100,
		                      'enabled'=>'$conf->mantis->enabled',
		                      'perms'=>'',
		                      'target'=>'',
		                      'user'=>0);
		$r++;

	}

	/**
     *		\brief      Function called when module is enabled.
     *					Add constants, boxes and permissions into Dolibarr database.
     *					It also creates data directories.
     */
	function init()
  	{
    	$sql = array();

    	return $this->_init($sql);
  	}

	/**
	 *		\brief		Function called when module is disabled.
 	 *              	Remove from database constants, boxes and permissions from Dolibarr database.
 	 *					Data directories are not deleted.
 	 */
	function remove()
	{
    	$sql = array();

    	return $this->_remove($sql);
  	}

}

?>