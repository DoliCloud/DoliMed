<?php
/* Copyright (C) 2013  Laurent Destailleur  <eldy@users.sourceforge.net>
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

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (! defined('NOLOGIN'))         define('NOLOGIN', 1);          // File must be accessed by logon page so without login
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU', 1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');

session_cache_limiter(false);

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

// Define css type
header('Content-type: text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at
// each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

//$path='/cabinetmed';    // This value may be used in future for external module to overwrite theme
//$theme='cabinetmed';
$path='';    	// This value may be used in future for external module to overwrite theme
$theme='eldy';	// Value of theme
if (! empty($conf->global->MAIN_OVERWRITE_THEME_RES)) { $path='/'.$conf->global->MAIN_OVERWRITE_THEME_RES; $theme=$conf->global->MAIN_OVERWRITE_THEME_RES; }

// Define image path files
$dol_hide_topmenu=$conf->dol_hide_topmenu;
$dol_hide_leftmenu=$conf->dol_hide_leftmenu;
$dol_optimize_smallscreen=$conf->dol_optimize_smallscreen;
$dol_no_mouse_hover=$conf->dol_no_mouse_hover;
$dol_use_jmobile=$conf->dol_use_jmobile;
?>

legend
{
	font-weight: normal;
	color: #442288;
}


<?php if ((float) DOL_VERSION >= 10) { ?>
div.mainmenu.patients::before {
	content: "\f728";
}

div.mainmenu.contacts::before {
	content: "\f0f0";
}
<?php } else { ?>
div.mainmenu.patients {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/members.png', 1) ?>);
}

div.mainmenu.contacts {
	background-image: url(<?php echo dol_buildpath('/cabinetmed/img/menus/stethoscope.png', 1) ?>);
}
<?php } ?>


.quatrevingtpercent, .inputsearch {
	width: 80%;
}


<?php if (! empty($dol_use_jmobile)) { ?>
.cabpaymentcheque { border-top: 1px solid #AAA; margin-top: 4px; }
.cabpaymentcarte { border-top: 1px solid #AAA; margin-top: 4px; }
.cabpaymentcash { border-top: 1px solid #AAA; margin-top: 4px; }
.cabpaymentthirdparty { border-top: 1px solid #AAA; margin-top: 4px; }
	<?php
}
