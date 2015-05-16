<?php
/*------------------------------------------------------------------------
# plugin_googlemaps.css.php - Google Maps plugin
# ------------------------------------------------------------------------
# author    Mike Reumer
# copyright Copyright (C) 2012 tech.reumer.net. All Rights Reserved.
# @license - http://www.gnu.org/copyleft/gpl.html GNU/GPL
# Websites: http://tech.reumer.net
# Technical Support: http://tech.reumer.net/Contact-Us/Mike-Reumer.html 
# Documentation: http://tech.reumer.net/Google-Maps/Documentation-of-plugin-Googlemap/
--------------------------------------------------------------------------*/

/* Googlemaps CSS
To solve known css problems that break the design of a map 
*/

@define('_JEXEC', 1);

if (!defined('DS'))
	@define( 'DS', DIRECTORY_SEPARATOR );

@ob_start();

// Fix magic quotes.
@ini_set('magic_quotes_runtime', 0);
 
// Maximise error reporting.
//@ini_set('zend.ze1_compatibility_mode', '0');
//error_reporting(E_ALL);
//@ini_set('display_errors', 1);

/*
 * Ensure that required path constants are defined.
 */
if (!defined('JPATH_BASE'))
{
	$path = dirname(__FILE__);

	// Joomla 1.6.x/1.7.x/2.5.x
	$path = str_replace('/plugins/system/plugin_googlemap3', '', $path);
	$path = str_replace('\plugins\system\plugin_googlemap3', '', $path);
	
	define('JPATH_BASE', $path);
}

require_once ( JPATH_BASE.'/includes/defines.php' );
require_once ( JPATH_BASE.'/includes/framework.php' );
/* To use Joomla's Database Class */
require_once ( JPATH_BASE.'/libraries/joomla/factory.php' );
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();
$user = JFactory::getUser();
$session = JFactory::getSession();

class plugin_googlemap3_css {
		function doExecute(){
			// Get config
			$plugin = JPluginHelper::getPlugin('system', 'plugin_googlemap3');
			
			$jversion = JVERSION;
			$params = new JRegistry();
			$params->loadString($plugin->params);
			
			// Plugin code
			$mapcss = $params->get('mapcss', '');
			
			// Clean already send output
			while (@ob_end_clean());
			
			// Set correct header
			header('Content-type: text/css; charset=utf-8');

			echo $mapcss;
		}
}

// Instantiate the application.
$web = new plugin_googlemap3_css;

// Run the application
$web->doExecute();

?>

