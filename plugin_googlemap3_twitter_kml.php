<?php
/*------------------------------------------------------------------------
# plugin_googlemap3_twitter.php - Google Maps plugin
# ------------------------------------------------------------------------
# author    Mike Reumer
# copyright Copyright (C) 2012 tech.reumer.net. All Rights Reserved.
# @license - http://www.gnu.org/copyleft/gpl.html GNU/GPL
# Websites: http://tech.reumer.net
# Technical Support: http://tech.reumer.net/Contact-Us/Mike-Reumer.html 
# Documentation: http://tech.reumer.net/Google-Maps/Documentation-of-plugin-Googlemap/
--------------------------------------------------------------------------*/

// Restrict access to own domain
if ($_SERVER['SERVER_PORT'] == 443)
	$protocol = "https://";
else
	$protocol = "http://";

// No check if the referer is the website, because it can be used by Google servers too.

//header('Access-Control-Allow-Origin: '.$protocol.$_SERVER['SERVER_NAME']);
//header('Access-Control-Allow-Credentials: true');
//header('Access-Control-Allow-Methods: GET');
//
//if (array_key_exists('HTTP_ORIGIN', $_SERVER))
//	$origin = $_SERVER['HTTP_ORIGIN'];
//else 
//	$origin = "";
//$pattern = "/(www.)?".$_SERVER['SERVER_NAME']."/i";
//if ($origin!=""&&preg_match($pattern, $origin)==0)
//	die( 'Restricted access' );
//
//$refering=parse_url($_SERVER['HTTP_REFERER']);
//if($refering['host']!=$_SERVER['SERVER_NAME'])
//	die( 'Restricted access' );

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

// No check if the referer is the website, because it can be used by Google servers too.
//JSession::checkToken( 'get' ) or die( 'Invalid Token' );

class Twitter {
	private $user = null;
	private $tweets = null;
	
	function __construct($user, $twitterconsumerkey, $twitterconsumersecret, $twitteraccesstoken, $twitteraccesstokensecret) {
		$this->user = $user;
		require_once 'plugin_googlemap3_twitteroauth.php';
		$this->twitterConnection = new TwitterOAuth(
			$twitterconsumerkey, // Consumer Key
			$twitterconsumersecret, // Consumer secret
			$twitteraccesstoken, // Access token
			$twitteraccesstokensecret	// Access token secret
		);
	}
	
	function getUserTimeLine($count = 19, $retweets=0) {
		$ch = curl_init();

		$this->tweets = $this->twitterConnection->get(
			'statuses/user_timeline',
			array(
				'screen_name' => $this->user,
				'count' => $count,
				'include_rts' => $retweets
			)
		);
	
		if (is_object($this->tweets)&&isset($this->tweets->errors))
			$this->tweets = array();
		
		if (count($this->tweets)==0)
			$this->tweets = array();
		
		return $this->tweets;
	}
	
	function getProfile() {
		$profile = array();

		if(!empty($this->tweets)&&!isset($this->tweets->errors)) {
			$profile = $this->tweets[0]->user;
		}
		
		return $profile;
	}
	
	function timeSince($date) {
		$datetime = strtotime($date);
		$offset = time() - $datetime;
		
		$units = array(
			'second' => 1,
			'minute' => 60,
			'hour' => 3600,
			'day' => 86400,
			'month' => 2629743,
			'year' => 31556926);
		
		foreach($units as $unit => $value) {
			if($offset >= $value) {
				$result = floor($offset / $value);
				
				if(!in_array($unit, array('month','year'))) {
					if($result > 1) {
						$unit .= 's';
					}
					
					$timeAgo = 'About'.' '.$result.' '.$unit.' '.'Ago';
				} else {
					return date('j M Y', $datetime);
				}
			}
		}
		
		return $timeAgo;
	}
	
	function parseText($text) {
		// url
		$text = preg_replace( "/(([[:alnum:]]+:\/\/)|www\.)([^[:space:]]*)([[:alnum:]#?\/&=])/i", "<a href=\"\\1\\3\\4\" target=\"_blank\">\\1\\3\\4</a>", ' '.$text);
		$text = str_replace('href="www.', 'href="http://www.', $text);
		// mailto
		$text = preg_replace( "/(([a-z0-9_]|\\-|\\.)+@([^[:space:]]*)([[:alnum:]-]))/i", "<a href=\"mailto:\\1\">\\1</a>", $text);
		// user
		$text = preg_replace( "/ +@([a-z0-9_]*) ?/i", " <a href=\"http://twitter.com/\\1\" target=\"_blank\">@\\1</a> ", $text);
		// argument
		$text = preg_replace( "/ +#([a-z0-9_]*) ?/i", " <a href=\"http://twitter.com/search?q=%23\\1\" target=\"_blank\">#\\1</a> ", $text);
		// truncates long url
		$text = preg_replace("/>(([[:alnum:]]+:\/\/)|www\.)([^[:space:]]{30,40})([^[:space:]]*)([^[:space:]]{10,20})([[:alnum:]#?\/&=])</", ">\\3...\\5\\6<", $text);
		
		return trim($text);
	}

}

class plugin_googlemap3_twitter_kml
{
		/**
		 * Display the application.
		 */
		function doExecute(){
			// Get config
			$plugin = JPluginHelper::getPlugin('system', 'plugin_googlemap3');
			
			$jversion = JVERSION;
			$params = new JRegistry();
			$params->loadString($plugin->params);
			
			// Get params
			$twitterconsumerkey = $params->get('twitterconsumerkey', '');
			$twitterconsumersecret = $params->get('twitterconsumersecret', '');
			$twitteraccesstoken = $params->get('twitteraccesstoken', '');
			$twitteraccesstokensecret = $params->get('twitteraccesstokensecret', '');
			
			$twittername = JRequest::getVar('twittername', '');
			if (!is_string($twittername))
				$twittername ="";
			else
				$twittername = urldecode($twittername);
			if ($twittername=="")
				$twittername = $params->get('twittername', '');
				
			$twittertweets = JRequest::getVar('twittertweets', '');
			if (!is_string($twittertweets))
				$twittertweets ="";
			else
				$twittertweets = urldecode($twittertweets);
			if ($twittertweets=="")
				$twittertweets = $params->get('twittertweets', '15');
				
			$line = JRequest::getVar('twitterline', '');
			if (!is_string($line))
				$line ="";
			else
				$line = urldecode($line);
			if ($line=="")
				$line = $params->get('twitterline', '');
				
			$twitterlinewidth = JRequest::getVar('twitterlinewidth', '');
			if (!is_string($twitterlinewidth))
				$twitterlinewidth ="";
			else
				$twitterlinewidth = urldecode($twitterlinewidth);
			if ($twitterlinewidth=="")
				$twitterlinewidth = $params->get('twitterlinewidth', '5');

			$twitterstartloc = JRequest::getVar('twitterstartloc', '');
			if (!is_string($twitterstartloc))
				$twitterstartloc ="";
			else
				$twitterstartloc = urldecode($twitterstartloc);
			if ($twitterstartloc=="")
				$twitterstartloc = $params->get('twitterstartloc', '5');
				
			$twitter = new Twitter(ltrim(rtrim($twittername)), $twitterconsumerkey, $twitterconsumersecret, $twitteraccesstoken, $twitteraccesstokensecret);
			$tweets = $twitter->getUserTimeLine(ltrim(rtrim($twittertweets)), 1);
			$profile = $twitter->getProfile();
			
			// Start KML file, create parent node
			$dom = new DOMDocument('1.0','UTF-8');
			
			//Create the root KML element and append it to the Document
			$node = $dom->createElementNS('http://earth.google.com/kml/2.1','kml');
			$parNode = $dom->appendChild($node);
			
			//Create a Folder element and append it to the KML element
			$docnode = $dom->createElement('Document');
			$parNode = $parNode->appendChild($docnode);
			
			$twitterStyleNode = $dom->createElement('Style');
			$twitterStyleNode->setAttribute('id', 'tweetStyle');
			$twitterIconstyleNode = $dom->createElement('IconStyle');
			$twitterIconstyleNode->setAttribute('id', 'tweetIcon');
			$twitterIconNode = $dom->createElement('Icon');
			$twitterHref = $dom->createElement('href', $params->get('twittericon', ''));
			
			$twitterIconNode->appendChild($twitterHref);
			$twitterIconstyleNode->appendChild($twitterIconNode);
			$twitterStyleNode->appendChild($twitterIconstyleNode);
			$docnode->appendChild($twitterStyleNode);

			if ($line!='') {
				// Create a line of travelling
				$twitterStyleNode = $dom->createElement('Style');
				$twitterStyleNode->setAttribute('id', 'lineStyle');
				$twitterLinestyleNode = $dom->createElement('LineStyle');
				$twitterColorNode = $dom->createElement('color', ltrim(rtrim($line)));
				$twitterLinestyleNode->appendChild($twitterColorNode);
				$twitterWidthNode = $dom->createElement('width', ltrim(rtrim($twitterlinewidth)));
				$twitterLinestyleNode->appendChild($twitterWidthNode);
				$twitterStyleNode->appendChild($twitterLinestyleNode);
				$docnode->appendChild($twitterStyleNode);
			}
			
			//Create a Folder element and append it to the KML element
			$fnode = $dom->createElement('Folder');
			$folderNode = $parNode->appendChild($fnode);
			$nameNode = $dom->createElement('name', 'Tweets '.$twittername);
			$folderNode->appendChild($nameNode);
			
			$tweets = array_reverse($tweets);
			$prev_location = explode(',', $twitterstartloc);
			// swap lat and long values. In kml is it different first long then lat
			$lat = $prev_location[0];
			$prev_location[0] = $prev_location[1];
			$prev_location[1] = $lat;
			
			foreach($tweets as $tweet) {
				if ($tweet->coordinates=="")
					$tweet->coordinates->coordinates = $prev_location;
				else
					$prev_location = $tweet->coordinates->coordinates;
			}
			$tweets = array_reverse($tweets);

			foreach($tweets as $tweet) {
				//Create a Placemark and append it to the document

				$node = $dom->createElement('Placemark');
				$placeNode = $folderNode->appendChild($node);
				
				//Create an id attribute and assign it the value of id column
				$placeNode->setAttribute('id','tweet_'.$tweet->id_str);
				
				//Create name, description, and address elements and assign them the values of 
				//the name, type, and address columns from the results
				
				$nameNode = $dom->createElement('name', date('d m Y g:i:s', strtotime($tweet->created_at)));
				$placeNode->appendChild($nameNode);
				
				$styleUrl = $dom->createElement('styleUrl', '#tweetStyle');
				$placeNode->appendChild($styleUrl);
				
				$descText  = "";
				$descText .="<a href='http://www.twitter.com/".$profile->screen_name."' target='_blank' title='Follow us'><h4 class='tw_user'><img src='".$profile->profile_image_url."' alt='".$profile->name."' />".$profile->name."</h4></a>";
				
				$descText .= "<span class='tw_text'>".$twitter->parseText($tweet->text)."</span>";
				$descText .="<br/><span class='tw_date'>".$twitter->timeSince($tweet->created_at)."</span>";
				
				$descNode = $dom->createElement('description', '');
				$cdataNode = $dom->createCDATASection($descText);
				$descNode->appendChild($cdataNode);
				$placeNode->appendChild($descNode);
				
				$pointNode = $dom->createElement('Point');
				$placeNode->appendChild($pointNode);
			
				$coor_pointNode = $dom->createElement('coordinates',implode(",",$tweet->coordinates->coordinates));
				$pointNode->appendChild($coor_pointNode);
			}
			
			if ($line!=''&&count($tweets)>0) {
				// Create a line of travelling
				
				//Create a Placemark and append it to the document
				$node = $dom->createElement('Placemark');
				$placeNode = $folderNode->appendChild($node);
				
				//Create an id attribute and assign it the value of id column
				$placeNode->setAttribute('id','tweetline');
				
				//Create name, description, and address elements and assign them the values of 
				//the name, type, and address columns from the results
				
				$nameNode = $dom->createElement('name','');
				$placeNode->appendChild($nameNode);
				
				$styleUrl = $dom->createElement('styleUrl', '#lineStyle');
				$placeNode->appendChild($styleUrl);

				//Create a LineString element
				$lineNode = $dom->createElement('LineString');
				$placeNode->appendChild($lineNode);
				$exnode = $dom->createElement('extrude', '1');
				$lineNode->appendChild($exnode);
				$almodenode =$dom->createElement('altitudeMode','relativeToGround');
				$lineNode->appendChild($almodenode);
				
				$coordinates = "";
				
				foreach($tweets as $tweet) {
					$coordinates .= " ".implode(",",$tweet->coordinates->coordinates);
				}
				//Create a coordinates element and give it the value of the lng and lat columns from the results
				$coorNode = $dom->createElement('coordinates',$coordinates);
				$lineNode->appendChild($coorNode);
			}
			
			$kmlOutput = $dom->saveXML();
			
			//assign the KML headers. 
			header('Content-type: application/vnd.google-earth.kml+xml');
			echo $kmlOutput;
 		}
}
// Instantiate the application.
$web = new plugin_googlemap3_twitter_kml;
 
// Run the application
$web->doExecute();

?>