<?php
/*------------------------------------------------------------------------
# plugin_googlemap3_proxy.php - Google Maps plugin
# ------------------------------------------------------------------------
# author    Mike Reumer
# copyright Copyright (C) 2011 tech.reumer.net. All Rights Reserved.
# @license - http://www.gnu.org/copyleft/gpl.html GNU/GPL
# Websites: http://tech.reumer.net
# Technical Support: http://tech.reumer.net/Contact-Us/Mike-Reumer.html 
# Documentation: http://tech.reumer.net/Google-Maps/Documentation-of-plugin-Googlemap/
--------------------------------------------------------------------------*/

// No protection of Joomla because this php program may be called directly to deliver content
// defined( '_JEXEC' ) or die( 'Restricted access' );

// No protection of Joomla because this php program may be called directly to deliver content
// It uses Joomla framework
// defined( '_JEXEC' ) or die( 'Restricted access' );

@define('_JEXEC', 1);
if (!defined('DS'))
	@define( 'DS', DIRECTORY_SEPARATOR );

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

class plugin_googlemap3_proxy_kml
{
	/**
	 * Display the application.
	 */
	function doExecute(){
		// Get config
		$plugin = JPluginHelper::getPlugin('system', 'plugin_googlemap3');
		
		$this->jversion = JVERSION;
		$params = new JRegistry();
		$params->loadString($plugin->params);

		// Get params
		$this->errorcode = 200;
		$this->proxy = intval($params->get('proxy', '0'));
		$response = "";
		$debug = intval(JRequest::getVar('debug', ''));
		if ($debug!=1)
			@ob_start();
			
		$this->googlewebsite = $params->get( 'googlewebsite', 'maps.google.com' );

		// Check access
		if ($_SERVER['SERVER_PORT'] == 443)
			$this->protocol = "https://";
		else
			$this->protocol = "http://";

		// Restrict access to own domain
		if (array_key_exists('HTTP_ORIGIN', $_SERVER))
			$origin = $_SERVER['HTTP_ORIGIN'];
		else 
			$origin = "";
		$pattern = "/(www.)?".$_SERVER['SERVER_NAME']."/i";
		if ($origin!=""&&preg_match($pattern, $origin)==0)
			$response = $this->_error(403, "Restricted access"); // 403
		
		if (array_key_exists('HTTP_REFERER', $_SERVER)) {
			$refering=parse_url($_SERVER['HTTP_REFERER']);
			if($refering['host']!=$_SERVER['SERVER_NAME'])
				$response = $this->_error(403, "Restricted access"); // 403
		}
		
		if (!JSession::checkToken( 'get' ) )
			$response = $this->_error(401, "Invalid token"); // 401
		
		if ($this->proxy==0)
			$response = $this->_error(406, "Method not available"); // 406

//		Get url
		$url = JRequest::getVar('url', '');
		if (!is_string($url)||$url=='')
			$response = $this->_error(406, "Wrong url"); // 406

		// Valid url
		$pattern = '/^(?:[;\/?:@&=+$,]|(?:[^\W_]|[-_.!~*\()\[\] ])|(?:%[\da-fA-F]{2}))*$/';
		if( preg_match( $pattern, $url ) == 0 ) {
			$response = $this->_error(406, "Wrong url"); // 406
		}
		
		$id = intval(JRequest::getVar('id', ''));

		if (!$this->_checkurl($id, $url))
			$response = $this->_error(406, "Wrong url"); // 406
		
		$url = $this->protocol.$url;

		// Set headers
		if ($debug!="1") {
			header('Content-type: application/vnd.google-earth.kml+xml', true, $this->errorcode);
		}

		header('Access-Control-Allow-Origin: '.$this->protocol.$_SERVER['SERVER_NAME']);
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Methods: GET');

		if ($response=="")
			$response = $this->_getkml($url);

		$oldValue = libxml_disable_entity_loader(true);
		$dom =  new DOMDocument('1.0','UTF-8');
		@$dom->loadXML($response);
		$kmlOutput = $dom->saveXML();
		
		if ($debug!=1)
			while (@ob_end_clean());
		
		echo $kmlOutput;
	}
	
	function _checkurl($id, $url) {
		// check if it is the twitter url
		$twitterurl = (($this->proxy=="0")?$this->base:"")."/plugins/system/plugin_googlemap3/plugin_googlemap3_twitter_kml.php?";
		if (strpos($url,$twitterurl) !== false)
			return true;
			
		// Check if it is based on MSID
		// So maps.google.xxx
		$googlereg = '/^maps\.google((\.[a-z]{2,3}){1,2})\/maps\/ms\?/';
		
		if (preg_match($googlereg, $url)== 1) {
			$this->protocol = "https://";
			return true;
		}
				
		// Check if url is defined on the website
		$database  = JFactory::getDBO();
		$query = "SELECT CONCAT(a.introtext, a.fulltext) as text FROM #__content as a WHERE a.id=".$id;
		$database->setQuery($query);
		
		if (!$database->query())
			return false;

		$text = $database->loadResult();
		if (substr($this->jversion,0,2)=="3.")
			$text = $database->escape($text);
		else
			$text = $database->getEscaped($text);
		//echo $text."<br/>";
		// Get the kml definitions
		preg_match_all("/kml((\(|\[)[0-9]+(\)|\]))?=\\\'(.*?)\\\'/msi", $text, $matches);
		$cnt = count($matches[0]);
		//print_r($matches);
		//echo "<br/>Url: ".$url."<br/>";
		// Check if the kml definitions are in the url
		for($counter = 0; $counter < $cnt; $counter++) {
			preg_match("/\\\'(.*?)\\\'/", $matches[0][$counter], $kml);
			$kml[1] = str_replace(array('http://', 'https://', '&amp;'), array('', '', '&'), $kml[1]);
			//echo $kml[1]."<br/>";
			if (strpos($url, $kml[1]) !== false)
				return true;
		}

		return false;
	}
	
	function _getkml($url) {
		if (!isset($HTTP_RAW_POST_DATA)){
			$HTTP_RAW_POST_DATA = file_get_contents('php://input');
		}
		$post_data = $HTTP_RAW_POST_DATA;
		
		$header[] = "Content-type: text/xml";
		$header[] = "Content-length: ".strlen($post_data);

		//get all session parameters
		$postcurl  = array();
		$post  = '';
		$cookie = '';
		$reg = '/^[a-f0-9]+$/si';
		foreach ($_COOKIE as $key => $value) {
			if (preg_match($reg,$key)>0) {
				$cookie.="$key=$value; "; // separation in cookies is ; with space!
				$postcurl[$key]=$value;
				$post.=((strlen($post)>0)?'&':'')."$key=$value";
			}
		}

		$ok = false;

//		File_get_contents is not supported anymore because additional headers has to be set
//		if (ini_get('allow_url_fopen'))
//			if (($response = @file_get_contents($url)))
//				$ok = true;
		
		if (!$ok) {
			$ch = curl_init( $url );
			
			// Send authentication
			$username = "";
			$password = "";
			// mod_php
			if (isset($_SERVER['PHP_AUTH_USER'])) {
				$username = $_SERVER['PHP_AUTH_USER'];
				$password = $_SERVER['PHP_AUTH_PW'];
			}
		   // most other servers
			elseif (isset($_SERVER['HTTP_AUTHENTICATION'])) {
				if (strpos(strtolower($_SERVER['HTTP_AUTHENTICATION']),'basic')===0) {
					list($username,$password) = explode(':',base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
				}  
			}
			if ($username!=""&&$password!="") {
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
				curl_setopt ($ch, CURLOPT_USERPWD, $username.":".$password); // set referer on redirect
			}
		
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			if( !ini_get('safe_mode')&&!ini_get('open_basedir') )
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

			if ( strlen($cookie)>0 ){
				curl_setopt($ch, CURLOPT_COOKIESESSION, false);  // False to keep all cookies of previous session
				curl_setopt($ch, CURLOPT_COOKIE, $cookie);
			}
			$refering=parse_url($_SERVER['HTTP_REFERER']);
			curl_setopt($ch, CURLOPT_REFERER, $this->protocol.$refering['host'].$refering['path']);				
			curl_setopt($ch, CURLOPT_TIMEOUT, 80);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_FAILONERROR, 0);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
//			curl_setopt($ch, CURLOPT_HEADER, true); // Show header info in response
			
			if ( strlen($post_data)>0 ){
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			}
			
			$response = curl_exec($ch);
			
//			echo $url." : ".$response. "<br/>";
//			print_r(curl_getinfo($ch));
			if (curl_errno($ch)) {
				print curl_error($ch);
			} else {
				curl_close($ch);
				$ok = true;
			}
		}
		
		if (!$ok) {
			$url = urldecode(JRequest::getVar('url', ''));
		
			// Do it the safe mode way for local files
			$pattern = "/(www.)?".$_SERVER["SERVER_NAME"]."/i";
			if (preg_match($pattern, $url)!=0) {
				$url = $_SERVER["DOCUMENT_ROOT"].preg_replace($pattern, "", $url);
			
				if (ini_get('allow_url_fopen'))
					if (($response = file_get_contents($url)))
						$ok = true;
				
				if (!$ok) {
					$ch = curl_init( $url );
				
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					if( !ini_get('safe_mode')&&!ini_get('open_basedir') )
						curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
					curl_setopt($ch, CURLOPT_TIMEOUT, 80);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
					curl_setopt($ch, CURLOPT_FAILONERROR, 0);
					curl_setopt($ch, CURLOPT_VERBOSE, 1);
					curl_setopt($ch, CURLOPT_COOKIEFILE, 1);
					
					if ( strlen($post_data)>0 ){
						curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
					}
					
					$response = curl_exec($ch);    
					if (curl_errno($ch)) {
						print curl_error($ch);
					} else {
						curl_close($ch);
						$ok = true;
					}
				}
			}
		}

		return $response;
	}
	
	function _error($errorcode, $errortext) {
		$this->errorcode = $errorcode;
		
		$error = "<errorData>";
		$error .= "<errorCode>".$errorcode."</errorCode>";
		$error .= "<errorMessage>".$errortext."</errorMessage>";
		$error .= "</errorData>";

		return $error;		
	}
}

// Instantiate the application.
$web = new plugin_googlemap3_proxy_kml;
 
// Run the application
$web->doExecute();

?> 