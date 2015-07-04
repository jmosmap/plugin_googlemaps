<?php
/*------------------------------------------------------------------------
# plugin_googlemap3_helper.php - Google Maps plugin
# ------------------------------------------------------------------------
# author    Mike Reumer
# copyright Copyright (C) 2011 tech.reumer.net. All Rights Reserved.
# @license - http://www.gnu.org/copyleft/gpl.html GNU/GPL
# Websites: http://tech.reumer.net
# Technical Support: http://tech.reumer.net/Contact-Us/Mike-Reumer.html 
# Documentation: http://tech.reumer.net/Google-Maps/Documentation-of-plugin-Googlemap/
--------------------------------------------------------------------------*/

defined( '_JEXEC' ) or die( 'Restricted access' );

if (!defined('_CMN_JAVASCRIPT')) define('_CMN_JAVASCRIPT', "<b>JavaScript must be enabled in order for you to use Google Maps.</b> <br/>However, it seems JavaScript is either disabled or not supported by your browser. <br/>To view Google Maps, enable JavaScript by changing your browser options, and then try again.");

class plgSystemPlugin_googlemap3_helper
{
	var $jversion;
	var $params;
	var $regex;
	var $document;
	var $brackets;
	var $debug_plugin;
	var $debug_text;
	var $protocol;
	var $googlewebsite;
	var $urlsetting;
	var $googlekey;
	var $language;
	var $langtype;
	var $iso;
	var $no_javascript;
	var $pagebreak;
	var	$google_API_version;
	var $mapcss;
	var	$langanim;
	var	$first_google;
	var	$first_googlemaps;
	var	$first_mootools;
	var	$first_modalbox;
	var	$first_localsearch;
	var	$first_kmlrenderer;
	var	$first_kmlelabel;
	var	$first_svcontrol;
	var	$first_animdir;
	var	$first_arcgis;
	var $initparams;
	var $clientgeotype;
	var $event;
	var $_text;
	var	$_langanim;
	var	$_client_geo;
	var $_inline_coords;
	var $_inline_tocoords;
	var $_kmlsbwidthorig;
	var $_lbxwidthorig;
	var $googlescript_libraries;
	var $googlescript_lang;
	
	/**
	 * Constructor
	 *
	 * @access      protected
	 * @since       1.0
	 */
	 // Can we use _construct or should we use init?
	 //	function init() {
	public function __construct($jversion, $params, $regex, $document, $brackets)
	{
		// The params of the plugin
		$this->jversion = $jversion;
		$this->params = $params;
		$this->regex = $regex;
		$this->document = $document;
		$this->brackets = $brackets;
		// Set debug
		$this->debug_plugin = $this->params->get( 'debug', '0' );
		$this->debug_text = '';
		// Get ID
		$this->id = intval( JRequest::getVar('id', null) );	
		$this->id = explode(":", $this->id);
		$this->id = $this->id[0];
		// What is the url of website without / at the end
		$this->url = preg_replace('/\/$/', '', JURI::base());
		$this->_debug_log("url base(): ".$this->url);			
		$this->base = JURI::base(true);
		$this->_debug_log("url base(true): ".$this->base);			
		// Protocol not working with maps.google.com only with enterprise account
		if ($_SERVER['SERVER_PORT'] == 443)
			$this->protocol = "https://";
		else
			$this->protocol = "http://";
		$this->_debug_log("Protocol: ".$this->protocol);
		// Get language
		$this->langtype = $this->params->get( 'langtype', '' );
		$this->lang = JFactory::getLanguage();
		$this->language = $this->_getlang();
		$this->no_javascript = JText::_( 'CMN_JAVASCRIPT', _CMN_JAVASCRIPT);
		// Get region
		$this->region = $this->params->get( 'region', '' );
		// Define encoding
		$this->iso = "utf-8";
		// Get params
		$this->googlewebsite = $this->params->get( 'googlewebsite', 'maps.google.com' );
		$this->_debug_log("googlewebsite: ".$this->googlewebsite);
		$this->googlewebsiteext = str_replace("maps.google", "", $this->googlewebsite);
		$this->googlewebsiteext = str_replace("ditu.google", "", $this->googlewebsiteext);
		$this->urlsetting = $this->params->get( 'urlsetting', 'http_host' );
		$this->_debug_log("urlsetting: ".$this->urlsetting);
		if ($this->urlsetting=='mosconfig')
			$this->urlsetting = $this->url;
		else 
			$this->urlsetting = $_SERVER['HTTP_HOST'];
		$this->google_API_version = $this->params->get( 'Google_API_version', '3' );
		if (substr($this->google_API_version,0,1)=='2')
			$this->google_API_version = '3';
		if (substr($this->google_API_version,1,2)=='.x')
			$this->google_API_version = '3.exp';
		$this->mapcss = $this->params->get( 'mapcss', '' );
		$this->clientgeotype = $this->params->get( 'clientgeotype', '0' );
		$this->langanim = $this->params->get( 'langanim', 'en-GB;The requested panorama could not be displayed|Could not generate a route for the current start and end addresses|Street View coverage is not available for this route|You have reached your destination|miles|miles|ft|kilometers|kilometer|meters|In|You will reach your destination|Stop|Drive|Press Drive to follow your route|Route|Speed|Fast|Medium|Slow' );
		// Get key
		$this->googlekey = $this->_get_API_key();
		// Pagebreak regular expression
		$this->pagebreak = '/<hr\s(title=".*"\s)?class="system-pagebreak"(\stitle=".*")?\s\/>/si';
		// load scripts once
		$this->first_google=true;
		$this->first_googlemaps=true;
		$this->first_mootools=true;
		$this->first_modalbox=true;
		$this->first_localsearch=true;
		$this->first_kmlrenderer=true;
		$this->first_kmlelabel=true;
		$this->first_svcontrol=true;
		$this->first_animdir= true;
		$this->first_arcgis=true;
		$this->first_geoloc=true;
		// collect libraries of all maps 
		$this->googlescript_libraries = array();
		// define language of all maps
		$this->googlescript_lang = $this->language;

		$this->_debug_log("brackets: ".$this->brackets);
		// Get params
		$this->initparams = (object) null;
		$this->_getInitialParams();
	}	
	
	function process($match, $match_offset, $params, &$text, $counter, $event) {
		$startmem = round($this->_memory_get_usage()/1024);
		$this->_debug_log("Memory Usage Start (_process): " . $startmem . " KB");
		$this->_text = &$text;
		$this->event = $event;
		
		// Parameters can get the default from the plugin if not empty or from the administrator part of the plugin
		$this->_mp = clone $this->initparams;

		// Language initial value
		$this->_mp->lang = $this->language;
		
		// Next parameters can be set as default out of the administrtor module or stay empty and the plugin-code decides the default. 
		$this->_mp->zoomtype = $this->params->get( 'zoomType', '' );
		$this->_mp->mapType = strtolower($this->params->get( 'mapType', '' )); 

		// Default global process parameters
		$this->_client_geo = 0;
		//track if coordinates different from config
		$this->_inline_coords = 0;
		$this->_inline_tocoords = 0;
		$this->_mp->geocoded = 0;

		// default empty and should be filled as a parameter with the plugin out of the content item
		$this->_mp->tolat='';
		$this->_mp->tolon='';
		$this->_mp->toaddress='';
		$this->_mp->description='';
		$this->_mp->tooltip='';
		$this->_mp->kml = array();
		$this->_mp->kmlsb = array();
		$this->_mp->layer = array();
		$this->_mp->lookat = array();
		$this->_mp->camera = array();
		$this->_mp->msid = array();
		$this->_mp->searchtext='';
		$this->_mp->latitude='';
		$this->_mp->longitude='';
		$this->_mp->waypoints = array();
		$this->_mp->openbyid = "";
		$this->_mp->openbyname = "";
		
		// Give the map a random name so it won't interfere with another map
		$this->_mp->mapnm = $this->id."_".$this->_randomkeys(5)."_".$counter;
		
		// Match the field details to build the html
		$fields = explode("|", $params);

		foreach($fields as $value) {
			$value = trim($value, " \xC2\xA0\n\t\r\0\x0B");
			$values = explode("=",$value, 2);
			$values[0] = trim(strtolower($values[0]), " \xC2\xA0\n\t\r\0\x0B");
			$values[0] = preg_replace(array('/\r/','/\n/','/\<.*?\b[^>]*>/si'), '', $values[0]);
			$values=preg_replace("/^'/", '', $values);
			$values=preg_replace("/'$/", '', $values);
			$values=preg_replace("/^&#0{0,2}39;/",'',$values);
			$values=preg_replace("/&#0{0,2}39;$/",'',$values);
//			echo "<br/>".$values[0]." = ".$values[1];
				
			if (count($values)>1) {
				$values[1] = trim($values[1], " \xC2\xA0\n\t\r\0\x0B");

				if($values[0]=='debug'){
					$this->debug_plugin=$values[1];
				}else if($values[0]=='gmv'){
					$this->google_API_version = $values[1];
				}else if($values[0]=='lat'&&$values[1]!=''){
					$this->_mp->latitude=$this->_remove_html_tags($values[1]);
					$this->_inline_coords = 1;
				}else if($values[0]=='lon'&&$values[1]!=''){
					$this->_mp->longitude=$this->_remove_html_tags($values[1]);
					$this->_inline_coords = 1;
				}else if($values[0]=='centerlat'){
					$this->_mp->centerlat=$this->_remove_html_tags($values[1]);
					$this->_inline_coords = 1;
				}else if($values[0]=='centerlon'){
					$this->_mp->centerlon=$this->_remove_html_tags($values[1]);
					$this->_inline_coords = 1;
				}else if($values[0]=='tolat'){
					$this->_mp->tolat=$this->_remove_html_tags($values[1]);
					$this->_inline_tocoords = 1;
				}else if($values[0]=='tolon'){
					$this->_mp->tolon=$this->_remove_html_tags($values[1]);
					$this->_inline_tocoords = 1;
				}else if($values[0]=='text'){
					$this->_mp->description=html_entity_decode(html_entity_decode(trim($values[1])));
					if(!$this->_is_utf8($this->_mp->description)) 
						$this->_mp->description = utf8_encode($this->_mp->description);
					$this->_mp->description=str_replace("&#0{0,2}39;","'", $this->_mp->description);
				}else if($values[0]=='tooltip'){
					$this->_mp->tooltip=html_entity_decode(html_entity_decode(trim($values[1])));
					$this->_mp->tooltip=str_replace("&amp;","&", $this->_mp->tooltip);
					if(!$this->_is_utf8($this->_mp->tooltip)) 
						$this->_mp->tooltip= utf8_encode($this->_mp->tooltip);
				}else if($values[0]=='maptype'){
					$this->_mp->mapType=strtolower($values[1]);
				}else if ($values[0]=='waypoint'){
					$this->_mp->waypoints[0] = $values[1];
				}else if(($this->brackets=='both'||$this->brackets=='[')&&preg_match("/waypoint\([0-9]+\)/", $values[0])){
					$this->_mp->waypoints[$this->_get_index($values[0], '(')] = $values[1];
				}else if(($this->brackets=='both'||$this->brackets=='{')&&preg_match("/waypoint\[[0-9]+\]/", $values[0])){
					$this->_mp->waypoints[$this->_get_index($values[0], '[')] = $values[1];
				}else if($values[0]=='kml'){
					$this->_mp->kml[0]=$this->_remove_html_tags($values[1]);
				}else if(($this->brackets=='both'||$this->brackets=='[')&&preg_match("/kml\([0-9]+\)/", $values[0])){
					$this->_mp->kml[$this->_get_index($values[0], '(')] = $this->_remove_html_tags($values[1]);
				}else if(($this->brackets=='both'||$this->brackets=='{')&&preg_match("/kml\[[0-9]+\]/", $values[0])){
					$this->_mp->kml[$this->_get_index($values[0], '[')] = $this->_remove_html_tags($values[1]);
				}else if($values[0]=='msid'){
					$this->_mp->msid[0]=$this->_remove_html_tags($values[1]);
				}else if(($this->brackets=='both'||$this->brackets=='[')&&preg_match("/msid\([0-9]+\)/", $values[0])){
					$this->_mp->msid[$this->_get_index($values[0], '(')] = $this->_remove_html_tags($values[1]);
				}else if(($this->brackets=='both'||$this->brackets=='{')&&preg_match("/msid\[[0-9]+\]/", $values[0])){
					$this->_mp->msid[$this->_get_index($values[0], '[')] = $this->_remove_html_tags($values[1]);
				}else if($values[0]=='kmlsb'){
					$this->_mp->kmlsb[0]=$this->_remove_html_tags($values[1]);
				}else if(($this->brackets=='both'||$this->brackets=='[')&&preg_match("/kmlsb\([0-9]+\)/", $values[0])){
					$this->_mp->kmlsb[$this->_get_index($values[0], '(')] = $this->_remove_html_tags($values[1]);
				}else if(($this->brackets=='both'||$this->brackets=='{')&&preg_match("/kmlsb\[[0-9]+\]/", $values[0])){
					$this->_mp->kmlsb[$this->_get_index($values[0], '[')] = $this->_remove_html_tags($values[1]);
				}else if($values[0]=='layer'){
					$this->_mp->layer[0]=$this->_remove_html_tags($values[1]);
				}else if(($this->brackets=='both'||$this->brackets=='[')&&preg_match("/layer\([0-9]+\)/", $values[0])){
					$this->_mp->layer[$this->_get_index($values[0], '(')] = $this->_remove_html_tags($values[1]);
				}else if(($this->brackets=='both'||$this->brackets=='{')&&preg_match("/layer\[[0-9]+\]/", $values[0])){
					$this->_mp->layer[$this->_get_index($values[0], '[')] = $this->_remove_html_tags($values[1]);
				}else if($values[0]=='lookat'){
					$this->_mp->lookat[0]=$values[1];
				}else if(($this->brackets=='both'||$this->brackets=='[')&&preg_match("/lookat\([0-9]+\)/", $values[0])){
					$this->_mp->lookat[$this->_get_index($values[0], '(')] = $values[1];
				}else if(($this->brackets=='both'||$this->brackets=='{')&&preg_match("/lookat\[[0-9]+\]/", $values[0])){
					$this->_mp->lookat[$this->_get_index($values[0], '[')] = $values[1];
				}else if($values[0]=='camera'){
					$this->_mp->camera[0]=$values[1];
				}else if(($this->brackets=='both'||$this->brackets=='[')&&preg_match("/camera\([0-9]+\)/", $values[0])){
					$this->_mp->camera[$this->_get_index($values[0], '(')] = $values[1];
				}else if(($this->brackets=='both'||$this->brackets=='{')&&preg_match("/camera\[[0-9]+\]/", $values[0])){
					$this->_mp->camera[$this->_get_index($values[0], '[')] = $values[1];
				}else if($values[0]=='tilelayer'){
					$this->_mp->tilelayer=$this->_remove_html_tags($values[1]);
				}else {
					// other parameters
					if ($values[0]!='')
						$this->_mp->$values[0]=$values[1];
				}
			}
		}
		
		// Search for geo parameters inside the text
		//$this->_findgeoparam();
		
		//Translate parameters
		$this->_mp->txtgotoaddr = $this->_translate($this->_mp->txtgotoaddr, $this->_mp->lang);
		$this->_mp->erraddr = $this->_translate($this->_mp->erraddr, $this->_mp->lang);
		$this->_mp->txtaddr = $this->_translate($this->_mp->txtaddr, $this->_mp->lang);
		$this->_mp->txtaddr = str_replace(array("\r\n", "\r", "\n"), '', $this->_mp->txtaddr );
		$this->_mp->txtgetdir = $this->_translate($this->_mp->txtgetdir, $this->_mp->lang);
		$this->_mp->txtfrom = $this->_translate($this->_mp->txtfrom, $this->_mp->lang);
		$this->_mp->txtto = $this->_translate($this->_mp->txtto, $this->_mp->lang);
		$this->_mp->txtdiraddr = $this->_translate($this->_mp->txtdiraddr, $this->_mp->lang);
		$this->_mp->txtdir = $this->_translate($this->_mp->txtdir, $this->_mp->lang);
		$this->_mp->txtlightbox = $this->_translate(html_entity_decode($this->_mp->txtlightbox), $this->_mp->lang);
		$this->_mp->txt_driving = $this->_translate($this->_mp->txt_driving, $this->_mp->lang);
		$this->_mp->txt_avhighways = $this->_translate($this->_mp->txt_avhighways, $this->_mp->lang);
		$this->_mp->txt_avtoll = $this->_translate($this->_mp->txt_avtoll, $this->_mp->lang);
		$this->_mp->txt_walking = $this->_translate($this->_mp->txt_walking, $this->_mp->lang);
		$this->_mp->txt_bicycle = $this->_translate($this->_mp->txt_bicycle, $this->_mp->lang);
		$this->_mp->txt_transit = $this->_translate($this->_mp->txt_transit, $this->_mp->lang);
		$this->_mp->txt_optimize = $this->_translate($this->_mp->txt_optimize, $this->_mp->lang);
		$this->_mp->txt_alternatives = $this->_translate($this->_mp->txt_alternatives, $this->_mp->lang);
		$this->_langanim = $this->_translate($this->langanim, $this->_mp->lang);
		$this->_langanim = explode("|", $this->_langanim);
		$this->_mp->txtsrchnrby = $this->_translate($this->_mp->txtsrchnrby, $this->_mp->lang);
		$this->_mp->txtzoomhere = $this->_translate($this->_mp->txtzoomhere, $this->_mp->lang);
		$this->_mp->txtaddrstart = $this->_translate($this->_mp->txtaddrstart, $this->_mp->lang);
		$this->_mp->txtkmlgetdir = $this->_translate($this->_mp->txtkmlgetdir, $this->_mp->lang);
		$this->_mp->txtback = $this->_translate($this->_mp->txtback, $this->_mp->lang);
		$this->_mp->txtsearchnearby = $this->_translate($this->_mp->txtsearchnearby, $this->_mp->lang);
		$this->_mp->txtsearch = $this->_translate($this->_mp->txtsearch, $this->_mp->lang);
		$this->_mp->txtzoomin = $this->_translate($this->_mp->txtzoomin, $this->_mp->lang);
		$this->_mp->txtclustercount1 = $this->_translate($this->_mp->txtclustercount1, $this->_mp->lang);
		$this->_mp->txtclustercount2 = $this->_translate($this->_mp->txtclustercount2, $this->_mp->lang);

		$this->_debug_log("clientgeotype: ".$this->clientgeotype);
		
		if ($this->_mp->twittername!="") {
			$url = (($this->_mp->proxy=="0")?$this->base:"")."/plugins/system/plugin_googlemap3/plugin_googlemap3_twitter_kml.php?";
			$token = JSession::getFormToken();
			
			$url .= "twittername=".urlencode($this->_mp->twittername);
			$url .= "&twittertweets=".urlencode($this->_mp->twittertweets);
			$url .= "&twittericon=".urlencode($this->_mp->twittericon);
			$url .= "&twitterline=".urlencode($this->_mp->twitterline);
			$url .= "&twitterlinewidth=".urlencode($this->_mp->twitterlinewidth);
			$url .= "&twitterstartloc=".urlencode($this->_mp->twitterstartloc);
			$url .= "&".$token."=1";
			
			$this->_mp->kml[] = $url;
			unset($url, $this->_mp->twittername, $this->_mp->twittertweets, $this->_mp->twittericon, $this->_mp->twitterline, $this->_mp->twitterlinewidth, $this->_mp->twitterstartloc);
		}

		if($this->_inline_coords == 0 && !empty($this->_mp->address))	{
			if ($this->clientgeotype=="local")
				$coord = "";
			else
				$coord = $this->get_geo($this->_mp->address);
				
			if ($coord=='') {
				$this->_client_geo = 1;
			} else {
				list ($this->_mp->latitude, $this->_mp->longitude) = explode(",", $coord);
				$this->_inline_coords = 1;
				$this->_mp->geocoded = 1;
			}
		}

		if($this->_inline_tocoords == 0 && !empty($this->_mp->toaddress))	{
			if ($this->clientgeotype=="local")
				$tocoord = "";
			else
				$tocoord = $this->get_geo($this->_mp->toaddress);
			if ($tocoord=='') {
				$client_togeo = 1;
			} else {
				list ($this->_mp->tolon, $this->_mp->tolat) = explode(",", $tocoord);
				$this->_inline_tocoords = 1;
			}
		}

		if (is_numeric($this->_mp->svwidth)) 
			$this->_mp->svwidth .= "px";
			
		if (is_numeric($this->_mp->svheight))
			$this->_mp->svheight.= "px";

		if (is_numeric($this->_mp->kmlsbwidth)) {
			$this->_kmlsbwidthorig = $this->_mp->kmlsbwidth;
			$this->_mp->kmlsbwidth .= "px";
		} else 
			$this->_kmlsbwidthorig = 0;
			
		$this->_lbxwidthorig = $this->_mp->lbxwidth;
		
		if (is_numeric($this->_mp->lbxwidth))
			$this->_mp->lbxwidth .= "px";
		
		if (is_numeric($this->_mp->lbxheight))
			$this->_mp->lbxheight .= "px";
			
		if (is_numeric($this->_mp->width))
			$this->_mp->width .= "px";
			
		if (is_numeric($this->_mp->height))
			$this->_mp->height .= "px";

		if (count($this->_mp->msid)>0) {
			foreach ($this->_mp->msid as $idx=>$val) {
				$this->_mp->msid[$idx]=$this->protocol.$this->googlewebsite.'/maps/ms?';
				if ($this->_mp->lang!='')
					$this->_mp->msid[$idx] .= "hl=".$this->_mp->lang."&amp;";
				$this->_mp->msid[$idx].='ie='.$this->iso.'&amp;msa=0&amp;msid='.$val.'&amp;output=kml';
				$this->_debug_log("- msid[".$idx."]: ".$this->_mp->msid[$idx]);
				$this->_mp->kml[] = $this->_mp->msid[$idx];
			}
		}
		unset($this->_mp->msid);

		if ($this->_mp->imageurl!='') {
			if ($this->_mp->imageposition!="") {
				$this->_mp->imageposition= strtoupper($this->_mp->imageposition);
				if (!in_array($this->_mp->imageposition,  array("TOP_CENTER", "TOP_LEFT", "TOP_RIGHT", "LEFT_TOP", "RIGHT_TOP", "LEFT_CENTER", "RIGHT_CENTER", "LEFT_BOTTOM", "RIGHT_BOTTOM", "BOTTOM_CENTER", "BOTTOM_LEFT", "BOTTOM_RIGHT")))
					$this->_mp->imageposition = 'RIGHT_TOP';
			}
		} else
			unset($this->_mp->imageurl, $this->_mp->imageposition, $this->_mp->imageindex, $this->_mp->imagewidth, $this->_mp->imageheight);
			
		// Get the code to be added to the text
		list ($code, $lbcode) = $this->_processMapv3();
		
		// Get memory before adding code to text
		$endmem = round($this->_memory_get_usage()/1024);
		$diffmem = $endmem-$startmem;
		$this->_debug_log("Memory Usage End: " . $endmem . " KB (".$diffmem." KB)");

		// Add code to text
		$code = "\n<!-- Plugin Google Maps version 3.3 by Mike Reumer ".(($this->debug_text!='')?$this->debug_text."\n":"")."-->".$code;

		// Clean up debug text for next _process
		$this->debug_text = '';
		
		// Depending of show place the code at end of page or on the {mosmap} position		
		$offset = strpos($this->_text, $match);

		if ($this->_mp->show==0) {
			// Removed the -1 in first piece of text.
			$this->_text = substr($this->_text, 0, $offset).preg_replace('/'.preg_quote($match, '/').'/', $lbcode, substr($this->_text,$offset), 1);

			// If pagebreak add code before pagebreak
			preg_match($this->pagebreak, $this->_text, $m, PREG_OFFSET_CAPTURE, $offset);
			if (count($m)>0)
				$offsetpagebreak = $m[0][1];
			else
				$offsetpagebreak = 0;
			if ($offsetpagebreak!=0) 
				$this->_text = substr($this->_text, 0, $offsetpagebreak).$code.substr($this->_text, $offsetpagebreak);
			else
				$this->_text .= $code;
		} else
			$this->_text = substr($this->_text, 0, $offset).preg_replace('/'.preg_quote($match, '/').'/', $code, substr($this->_text,$offset), 1);

		// Clean up generated variables
		unset($startmem, $endmem, $diffmem, $offset, $lbcode, $m, $offsetpagebreak, $code, $token);
		
		return true;
	}
	
	function _findgeoparam() {
		// Find latitude, longitude or address inside the text
		// Later tolat, tolon or toaddress
	
		$reg='/<td\b[^>]*><strong>Latitude:<\/strong>(.*?)<\/td>/si';
		$c=preg_match_all($reg,$this->_text,$m);
		if ($c>0) {
			$this->_mp->latitude=$this->_remove_html_tags($m[1][0]);
			$this->_inline_coords = 1;
		}
			
		$reg='/<td\b[^>]*><strong>Longitude:<\/strong>(.*?)<\/td>/si';
		$c=preg_match_all($reg,$this->_text,$m);
		if ($c>0) {
			$this->_mp->longitude=$this->_remove_html_tags($m[1][0]);
			$this->_inline_coords = 1;
		}
		$reg='/<td\b[^>]*><strong>City:<\/strong>(.*?)<\/td>/si';
		$c=preg_match_all($reg,$this->_text,$m);
		if ($c>0)
			$this->_mp->address = $m[1][0];
	}
	
	function _processMapv3() {
		// Variables of process
		$code='';
		$lbcode='';
		
		//Detect browsers for special changes

		$iphone = strpos($_SERVER['HTTP_USER_AGENT']," iPhone");
		$android = strpos($_SERVER['HTTP_USER_AGENT'],"Android");
		$ipod = strpos($_SERVER['HTTP_USER_AGENT']," iPod");
//		Setting width and height is not correct because in mobile browser it's a wesbite rendering and width 100% or height 100% i snot supported.
//		if($iphone || $android || $ipod) {
//			$this->_mp->width = '100%';
//			$this->_mp->height = '100%';
//		}
		
		// Iphone or Ipod add special meta tag
//		if($iphone || $ipod) {
//			$this->document->setMetaData("viewport", "initial-scale=1.0, user-scalable=no");
//		}
		
		// No inline coordinates and no kml => standard configuration show marker based on defaults
		if ($this->_inline_coords == 0 && $this->_client_geo != 1 && count($this->_mp->kml)==0) { 
			$this->_mp->latitude = $this->_mp->deflatitude;
			$this->_mp->longitude = $this->_mp->deflongitude;
		}
		
		if (is_array($this->_mp->waypoints)) {
			$waypoints = array();
			foreach ($this->_mp->waypoints as $wp) {
				array_push($waypoints, $wp);
			}
			$this->_mp->waypoints = $waypoints;
			unset($waypoints);
		}

		if ($this->_mp->styledmap)
			$this->_styledmap = $this->_mp->styledmap;
		else
			$this->_styledmap = "null";
		
		unset($this->_mp->styledmap);
		
		$this->_processMapv3_scripts();
		
		list ($code, $lbcode) = $this->_processMapv3_template();
		
		$this->_processMapv3_markers();
		$this->_processMapv3_kml();
		$this->_processMapv3_tiles();
		$code .= $this->_processMapv3_icons();
		$this->_processMapv3_streetview();
		// Remove unnecessary parameters
		unset($this->_mp->inputsize);
	
		$code.="\n<script type='text/javascript'>/*<![CDATA[*/";
		
		if ($this->_mp->kmlrenderer=='geoxml') {
			if ($this->_mp->proxy=="1") 
				$code .= "\nvar proxy = '".$this->base."/plugins/system/plugin_googlemap3/plugin_googlemap3_kmlprxy.php?';";
			$code.="\ntop.publishdirectory = '".$this->base."/media/plugin_googlemap3/site/geoxmlv3/';";
		}
		
		if ($this->_mp->visualrefresh=="0") {
			$code.= "\ngoogle.maps.visualRefresh = false;";
			unset($this->_mp->visualrefresh);
		}

		$code.= "\nvar mapconfig".$this->_mp->mapnm." = ".$this->json_encode($this->_mp).";";
		$code.= "\nvar mapstyled".$this->_mp->mapnm." = ".$this->_styledmap.";";
		$code.= "\nvar googlemap".$this->_mp->mapnm." = new GoogleMaps('".$this->_mp->mapnm."', mapconfig".$this->_mp->mapnm.", mapstyled".$this->_mp->mapnm.");";
		$code.= "\n/*]]>*/</script>";
		
		return array($code, $lbcode);
	}
	
	function json_encode($a=false)
	{
		if (!function_exists('json_encode')) {
			if (is_null($a)) return 'null';
			if ($a === false) return 'false';
			if ($a === true) return 'true';
			if (is_scalar($a))
			{
			  if (is_float($a))
			  {
				// Always use "." for floats.
				return floatval(str_replace(",", ".", strval($a)));
			  }
			
			  if (is_string($a))
			  {
				static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
				return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';

			  }
			  else
				return $a;
			}
			$isList = true;
			for ($i = 0, reset($a); $i < count($a); $i++, next($a))
			{
			  if (key($a) !== $i)
			  {
				$isList = false;
				break;
			  }
			}
			$result = array();
			if ($isList)
			{
			  foreach ($a as $v) $result[] = $this->json_encode($v);
			  return '[' . join(",", $result) . ']';
			}
			else
			{
			  foreach ($a as $k => $v) $result[] = $this->json_encode($k).':'.$this->json_encode($v);
			  return '{' . join(",", $result) . '}';
			}
		} else
			return json_encode($a);
	}
	
	function _processMapv3_scripts() {
		// Only add the scripts and css once
		//Load mootools first because it's necessary for the extra functions like lightbox or effects
		// For effects we need to load mootools-more/framework true too
		if (($this->_mp->loadmootools=="1"&&$this->_mp->kmllightbox=="1"||$this->_mp->lightbox=="1"||$this->_mp->effect!="none"||$this->_mp->dir=="3"||$this->_mp->dir=="4"||strpos($this->_mp->description, "MOOdalBox"))&&$this->first_mootools) {
			if ($this->event!='onAfterRender')
				JHtml::_('behavior.framework',(($this->_mp->effect!="none")?true:false));				
			else {
				$mooconfig = JFactory::getConfig();
				$moodebug = $mooconfig->get('debug');
				$moouncompressed   = $moodebug ? '-uncompressed' : '';
				$url = $this->base."/media/system/js/mootools-core".$moouncompressed.".js";
				$this->_addscript($url);
				if ($this->_mp->effect!="none") {
					$url = $this->base."/media/system/js/mootools-more".$moouncompressed.".js";
					$this->_addscript($url);
				}
				unset($mooconfig, $moodebug, $moouncompressed);
			}
			$this->first_mootools = false;
		}
		
		if ($this->_mp->autocompl!='none'&&!in_array('places', $this->googlescript_libraries))
			$this->googlescript_libraries[]='places';
		if (($this->_mp->weather=='1'||$this->_mp->weathercloud=='1')&&(!in_array('weather', $this->googlescript_libraries)))
			$this->googlescript_libraries[]='weather';
		if ($this->_mp->panoramio=="1"&&!in_array('panoramio', $this->googlescript_libraries))
			$this->googlescript_libraries[]='panoramio';

		// Define the language for the maps thru the script
		$this->googlescript_lang = $this->_mp->lang;
		
		if($this->first_googlemaps) {
			$url = $this->base."/media/plugin_googlemap3/site/googlemaps/googlemapsv3.js";
			$this->_addscript($url);
			if ($this->mapcss!='') {
				$url = $this->base."/plugins/system/plugin_googlemap3/plugin_googlemap3.css.php";
				$this->_addstylesheet($url);
			}
			$this->first_googlemaps=false;
		}		
		

		if ($this->first_kmlelabel&&(($this->_mp->kmlpolylabel!=""&&$this->_mp->kmlpolylabelclass!="")||($this->_mp->kmlmarkerlabel!=""&&$this->_mp->kmlmarkerlabelclass!=""))) {
			$this->_addscript($this->base."/media/plugin_googlemap3/site/elabel/elabel_v3.js");
			$this->first_kmlelabel = false;
		}

		if (($this->_mp->kmlrenderer=='geoxml'||count($this->_mp->kmlsb)!=0)&&$this->first_kmlrenderer) {
			$this->_addscript($this->base."/media/plugin_googlemap3/site/geoxmlv3/geoxmlv3.js");
			$this->first_kmlrenderer = false;
		}

		if ($this->_mp->kmlrenderer=='arcgis'&&$this->first_arcgis) {
			$this->_addscript($this->base."/media/plugin_googlemap3/site/arcgislinkv3/arcgislink_compiled.js");
			$this->first_arcgis = false;
		}

		if (($this->_mp->kmllightbox=="1"||$this->_mp->lightbox=="1"||$this->_mp->dir=="3"||$this->_mp->dir=="4"||strpos($this->_mp->description, "MOOdalBox"))&&$this->first_modalbox)	{
			$this->_addscript($this->base."/media/plugin_googlemap3/site/moodalbox/js/modalbox1.3hackv3.js");
			
			$this->_addstylesheet($this->base."/media/plugin_googlemap3/site/moodalbox/css/moodalbox.css");
			$this->first_modalbox = false;
		}
		
		if ($this->_mp->clientgeotype=='local'&&$this->first_localsearch) {
			$this->_addscript($this->protocol."www.google".$this->googlewebsiteext."/uds/api?file=uds.js&amp;v=1.0&amp;key=".$this->googlekey);
			$style = "@import url('".$this->protocol."www.google".$this->googlewebsiteext."/uds/css/gsearch.css');\n@import url('".$this->protocol."www.google".$this->googlewebsiteext."/uds/solutions/localsearch/gmlocalsearch.css');";
			$this->_addstyledeclaration($style);
			$this->first_localsearch = false;
		}
		
		if ($this->_mp->geoloc=='1'&&$this->first_geoloc) {
			$this->_addscript($this->base."/media/plugin_googlemap3/site/geolocation/js/geolocationmarker.js");
			$this->first_geoloc = false;
		}

		// Clean up variables except generated code and memory variables
		unset($url);
	}
	
	function add_google_script() {
		if($this->first_google) {
			$url = 'maps.googleapis.com';
			$url = $this->protocol.$url."/maps/api/js?v=".$this->google_API_version;
			
			if ($this->googlekey!="")
				$url .= "&amp;key=".$this->googlekey;

			if ($this->_mp->lang!='') 
				$url .= "&amp;language=".$this->googlescript_lang;
			if ($this->region!='') 
				$url .= "&amp;region=".$this->region;

			if (count($this->googlescript_libraries)>0)
				$url .= "&amp;libraries=".implode(',', $this->googlescript_libraries);

			if ($this->_mp->signedin!='0') 
				$url .= "&amp;signed_in=true";
				
			// Get the rendered body text
			$text = JResponse::getBody();
			
			if (substr($this->jversion,0,3)=="2.5") {
				// Check if mootools is loaded
				// load after mootools script.
				$this->_addscriptinheaderaftermootools($url, $text, true);
			} else
				$this->_addscriptinheader($url, $text, true);
	
			// Set the body text with the replaced result
			JResponse::setBody($text);
	
			$this->first_google=false;
			
			unset($url, $text);
		}
	}
	
	function _processMapv3_markers() {
		$this->_mp->descr = ($this->_mp->description!='')?'1':'0';
		if ($this->_mp->description!=''||$this->_mp->dir!='0') {
			if ($this->_mp->dir!='0')
				$dirform =$this->_processMapv3_templatedirform('Marker');
			else
				$dirform = "";

			// Where to add dirform? tab or add the end of description?
			if (is_array($this->_mp->description)) {
				$this->_mp->description[$z+1]->title = $this->_mp->txtdir;
				$this->_mp->description[$z+1]->text = htmlspecialchars_decode($dirform, ENT_NOQUOTES);
			} else {
				$pat="/&lt;\/div&gt;$/";
				if (preg_match($pat, $this->_mp->description))
					$this->_mp->description = preg_replace($pat, $dirform."</div>", $this->_mp->description);
				else {
					$pat="/<\/div>$/";
					if (preg_match($pat, $this->_mp->description))
						$this->_mp->description = preg_replace($pat, $dirform."</div>", $this->_mp->description);
					else
						$this->_mp->description.=$dirform;
				}
			}

			
			if (!is_array($this->_mp->description))
				$this->_mp->description = htmlspecialchars_decode($this->_mp->description, ENT_NOQUOTES);
				
			// Encrypt description
			$this->_mp->description = htmlentities($this->_mp->description, ENT_QUOTES, "UTF-8");
		}
		$this->_mp->tooltip =  htmlentities($this->_mp->tooltip, ENT_QUOTES, "UTF-8");
	}
	
	function _processMapv3_tiles () {
		if ($this->_mp->tilelayer!="") {
			$this->_mp->tilebounds=explode(",", $this->_mp->tilebounds);
			if (count($this->_mp->tilebounds)==4) {
				$checkboundtiles = "if (googlemap".$this->_mp->mapnm.".checkboundTilelayer(coord, zoom)) {";
			} else {
				$checkboundtiles = "";
				unset($this->_mp->tilebounds);
			}
	
			if ($this->_mp->tilemethod!='maptiler') { 
				$this->_mp->tilemethod = str_replace('[', '{', $this->_mp->tilemethod);
				$this->_mp->tilemethod = str_replace(']', '}', $this->_mp->tilemethod);
				$this->_mp->tilemethod = str_replace('&amp;', '&', $this->_mp->tilemethod);
				$this->_mp->tilemethod = str_replace('{x}', '"+coord.x+"', $this->_mp->tilemethod);
				$this->_mp->tilemethod = str_replace('{X}', '"+coord.x+"', $this->_mp->tilemethod);
				$this->_mp->tilemethod = str_replace('{y}', '"+coord.y+"', $this->_mp->tilemethod);
				$this->_mp->tilemethod = str_replace('{Y}', '"+coord.y+"', $this->_mp->tilemethod);
				$this->_mp->tilemethod = str_replace('{z}', '"+zoom+"', $this->_mp->tilemethod);
				$this->_mp->tilemethod = str_replace('{Z}', '"+zoom+"', $this->_mp->tilemethod);
				$this->_mp->tilemethod = "function(coord, zoom) {".$checkboundtiles." return \"".$this->_mp->tilemethod."\";} }";
			} else {
				$this->_mp->tilemethod = "function(coord, zoom) {".$checkboundtiles." var ymax = 1 << zoom; var y = ymax - coord.y -1; return '".$this->_make_absolute($this->_mp->tilelayer)."/'+zoom+'/'+coord.x+'/'+y+'.png';} }";
			}
			
			unset($checkboundtiles);
		}
	}
	
	function _processMapv3_icons () {
		$code = "";
		if ($this->_mp->icon!='') {
			$code .= "\n<img src='".$this->_mp->icon."' style='display:none' alt='icon' />";
			if ($this->_mp->iconshadow!='')
				$code .= "\n<img src='".$this->_mp->iconshadow."' style='display:none' alt='icon shadow' />";
		
			// icon
			$icon = new stdClass();
			$icon->name = "A";
			$icon->imageurl = $this->_mp->icon;
			$icon->iconwidth = $this->_mp->iconwidth;
			$icon->iconheight = $this->_mp->iconheight;
			$icon->iconshadow = $this->_mp->iconshadow;
			$icon->iconshadowwidth = $this->_mp->iconshadowwidth;
			$icon->iconshadowheight = $this->_mp->iconshadowheight;
			$icon->iconanchorx = $this->_mp->iconanchorx;
			$icon->iconanchory = $this->_mp->iconanchory;
			if ($this->_mp->iconimagemap!="")
				$icon->iconimagemap = $this->_mp->iconimagemap;
			else
				$icon->iconimagemap = 	"13,0,15,1,16,2,17,3,18,4,18,5,19,6,19,7,19,8,19,9,19,10,19,11,19,12,19,13,18,14,18,15,17,16,16,17,15,18,14,19,14,20,13,21,13,22,12,23,12,24,12,25,12,26,11,27,11,28,11,29,11,30,11,31,11,32,11,33,8,33,8,32,8,31,8,30,8,29,8,28,8,27,8,26,7,25,7,24,7,23,6,22,6,21,5,20,5,19,4,18,3,17,2,16,1,15,1,14,0,13,0,12,0,11,0,10,0,9,0,8,0,7,0,6,1,5,1,4,2,3,3,2,4,1,6,0,13,0";
	
			$this->_mp->markericon = array($icon);
			$this->_mp->icontype ="A";
		} else
			$this->_mp->icontype ="";

		unset($icon, $this->_mp->icon, $this->_mp->iconwidth, $this->_mp->iconheight, $this->_mp->iconshadow, $this->_mp->iconshadowwidth, $this->_mp->iconshadowheight, $this->_mp->iconanchorx, $this->_mp->iconanchory, $this->_mp->iconimagemap, $this->_mp->iconshadowanchorx, $this->_mp->iconshadowanchory, $this->_mp->iconshadowanchorx, $this->_mp->iconshadowanchory, $this->_mp->iconinfoanchorx, $this->_mp->iconinfoanchory, $this->_mp->icontransparent);
		
		return $code;
	}
	
	function _processMapv3_streetview() {
		if ($this->_mp->sv!='none'&&$this->_mp->animdir=='0') {
			if ($this->_mp->sv=='top'||$this->_mp->sv=='bottom')
				$this->_mp->sv = "svpanorama".$this->_mp->mapnm;
				
			$this->_mp->svopt = new stdClass();
			if ($this->_mp->svyaw!='0')
				$this->_mp->svopt->heading = (int) $this->_mp->svyaw;
			else
				$this->_mp->svopt->heading = 0;
			if ($this->_mp->svpitch!='0')
				$this->_mp->svopt->pitch = (int) $this->_mp->svpitch;
			else
				$this->_mp->svopt->pitch = 0;
			if ($this->_mp->svzoom!='')
				$this->_mp->svopt->zoom = (int) $this->_mp->svzoom;
			else
				$this->_mp->svopt->zoom = 1;
				
			if ($this->_mp->svaddress=='0')
				$this->_mp->svaddress = false;
			else
				$this->_mp->svaddress = true;
		}		
		
		unset($this->_mp->svyaw,$this->_mp->svpitch,$this->_mp->svzoom);
	}

	function _processMapv3_kml() {
		// Rename parameter so they can be used by geoxml
		$this->_mp->geoxmloptions = new stdClass();
		
		// Change kml url if proxy is used
		if ($this->_mp->proxy=='1') {
			$token = JSession::getFormToken();
			
			$this->_mp->geoxmloptions->token = $token;
			$this->_mp->geoxmloptions->id = $this->id;
			
			foreach ($this->_mp->kml as $idx=>$val) {
				$this->_mp->kml[$idx] = $this->_make_absolute($val);
			}
		}

		// Set the style of the title of placemark to empty
		$this->_mp->geoxmloptions->titlestyle = ' class=kmlinfoheader ';
		$this->_mp->geoxmloptions->descstyle = ' class=kmlinfodesc ';
		
		if ($this->_mp->kmlsidebar=="left"||$this->_mp->kmlsidebar=="right") {
			$this->_mp->geoxmloptions->sidebarid = 'kmlsidebar'.$this->_mp->mapnm;
		} else {
			if ($this->_mp->kmlsidebar!="none")
				$this->_mp->geoxmloptions->sidebarid = $this->_mp->kmlsidebar;
		}
		
		if ($this->_mp->kmlmessshow=='0') {
			$this->_mp->geoxmloptions->veryquiet = true;
			$this->_mp->geoxmloptions->quiet = true;
		}
	
		if ($this->_inline_coords==1)
			$this->_mp->geoxmloptions->nozoom = true;

		if ($this->_mp->dir!='0')
			$this->_mp->geoxmloptions->directions = true;
			
		if ($this->_mp->kmlfoldersopen!='0')
			$this->_mp->geoxmloptions->allfoldersopen = true;
			
		if ($this->_mp->kmlhide!='0')
			$this->_mp->geoxmloptions->hideall = true;

		if ($this->_mp->kmlscale!='0')
			$this->_mp->geoxmloptions->scale=  true;

		if ($this->_mp->kmlopenmethod!='0')
			$this->_mp->geoxmloptions->iwmethod = $this->_mp->kmlopenmethod;
		
		if ($this->_mp->kmlsbsort=='asc') {
			$this->_mp->geoxmloptions->sortbyname = 'asc';
		}elseif ($this->_mp->kmlsbsort=='desc') {
			$this->_mp->geoxmloptions->sortbyname= 'desc';
		} else 	
			$this->_mp->geoxmloptions->sortbyname = null;

		if ($this->_mp->kmlclickablemarkers!='1') {
			$this->_mp->geoxmloptions->clickablemarkers = false;
			$this->_mp->geoxmloptions->clickablelines = false;
			$this->_mp->geoxmloptions->dohilite = false;
		}

		if ($this->_mp->kmlzoommarkers!='0')
			$this->_mp->geoxmloptions->zoomhere = intval($this->_mp->kmlzoommarkers);

		if ($this->_mp->kmlopendivmarkers!='')
			$this->_mp->geoxmloptions->opendivmarkers = $this->_mp->kmlopendivmarkers;

		if ($this->_mp->kmlcontentlinkmarkers!='0')
			$this->_mp->geoxmloptions->extcontentmarkers = true;

		if ($this->_mp->kmllinkablemarkers!='0')
			$this->_mp->geoxmloptions->contentlinkmarkers = true;

		if ($this->_mp->kmllinktarget!='')
			$this->_mp->geoxmloptions->linktarget = $this->_mp->kmllinktarget;

		if ($this->_mp->kmllinkmethod!='')
			$this->_mp->geoxmloptions->linkmethod = $this->_mp->kmllinkmethod;

		if ($this->_mp->kmlhighlite!='') {
			$this->_mp->kmlhighlite = str_replace('\"', '"', $this->_mp->kmlhighlite);
			$this->_mp->kmlhighlite = str_replace('\'', '"', $this->_mp->kmlhighlite);
			$this->_mp->geoxmloptions->hilite = json_decode($this->_mp->kmlhighlite);
		}
		
		if (($this->_mp->kmlpolylabel!=""&&$this->_mp->kmlpolylabelclass!="")) {
			$this->_mp->geoxmloptions->polylabelopacity = $this->_mp->kmlpolylabel;
			$this->_mp->geoxmloptions->polylabelclass = $this->_mp->kmlpolylabelclass;
		}
		if (($this->_mp->kmlmarkerlabel!=""&&$this->_mp->kmlmarkerlabelclass!="")) {
			$this->_mp->geoxmloptions->pointlabelopacity = $this->_mp->kmlmarkerlabel;
			$this->_mp->geoxmloptions->pointlabelclass = $this->_mp->kmlmarkerlabelclass;
		}
		if ($this->_mp->icon!='')
			$this->_mp->geoxmloptions->baseicon = "A";

		$this->_mp->geoxmloptions->lang = new stdClass();
		$this->_mp->geoxmloptions->lang->txtdir = $this->_mp->txtdir;
		$this->_mp->geoxmloptions->lang->txtto = $this->_mp->txtto;
		$this->_mp->geoxmloptions->lang->txtfrom = $this->_mp->txtfrom;
		$this->_mp->geoxmloptions->lang->txtsrchnrby = $this->_mp->txtsrchnrby;
		$this->_mp->geoxmloptions->lang->txtzoomhere = $this->_mp->txtzoomhere;
		$this->_mp->geoxmloptions->lang->txtaddrstart = $this->_mp->txtaddrstart;
		$this->_mp->geoxmloptions->lang->txtgetdir = $this->_mp->txtkmlgetdir;
		$this->_mp->geoxmloptions->lang->txtback = $this->_mp->txtback;
		$this->_mp->geoxmloptions->lang->txtsearchnearby = $this->_mp->txtsearchnearby;
		$this->_mp->geoxmloptions->lang->txtsearch = $this->_mp->txtsearch;

		$this->_mp->geoxmloptions->inputsize = $this->_mp->inputsize;
		
		if ($this->_mp->openbyid!="")
			$this->_mp->geoxmloptions->openbyid = $this->_mp->openbyid;
		
		if ($this->_mp->openbyname!="")
			$this->_mp->geoxmloptions->openbyname = $this->_mp->openbyname;
		
		if ($this->_mp->maxcluster!=''&&$this->_mp->gridsize!='') {
			$clusteroptions = new stdClass();
			if ($this->_mp->maxcluster!='')
				$clusteroptions->maxVisibleMarkers = intval($this->_mp->maxcluster);
			if ($this->_mp->gridsize!='')
				$clusteroptions->gridSize = intval($this->_mp->gridsize);
			if ($this->_mp->minmarkerscluster!='')
				$clusteroptions->minMarkersPerCluster = intval($this->_mp->minmarkerscluster);
			if ($this->_mp->maxlinesinfocluster!='')
				$clusteroptions->maxLinesPerInfoBox = intval($this->_mp->maxlinesinfocluster);
			if ($this->_mp->clusterinfowindow!='')
				$clusteroptions->ClusterInfoWindow = $this->_mp->clusterinfowindow;
			if ($this->_mp->clusterzoom!='')
				$clusteroptions->ClusterZoom = $this->_mp->clusterzoom;
			if ($this->_mp->clustermarkerzoom!='')
				$clusteroptions->ClusterMarkerZoom = intval($this->_mp->clustermarkerzoom);
			if ($this->_mp->clustericonurl!='')
				$clusteroptions->ClusterIconUrl = $this->_mp->clustericonurl;

			$clusteroptions->lang = new stdClass();
			$clusteroptions->lang->txtzoomin = $this->_mp->txtzoomin;
			$clusteroptions->lang->txtclustercount1 = $this->_mp->txtclustercount1;
			$clusteroptions->lang->txtclustercount2 = $this->_mp->txtclustercount2;

			$this->_mp->geoxmloptions->clustering = $clusteroptions;
		}
		
		unset($this->_mp->kmlmessshow, $this->_mp->kmlfoldersopen, $this->_mp->kmlhide, $this->_mp->kmlscale, $this->_mp->kmlopenmethod, $this->_mp->kmlsbsort, $this->_mp->kmlsbsort, $this->_mp->kmlclickablemarkers, $this->_mp->kmlzoommarkers, $this->_mp->kmlopendivmarkers, $this->_mp->kmlcontentlinkmarkers, $this->_mp->kmllinkablemarkers, $this->_mp->kmllinktarget, $this->_mp->kmllinkmethod, $this->_mp->polyhighlite, $this->_mp->kmlpolylabel, $this->_mp->kmlpolylabelclass, $this->_mp->kmlmarkerlabel, $this->_mp->kmlmarkerlabelclass, $this->_mp->txtsrchnrby, $this->_mp->txtzoomhere, $this->_mp->txtaddrstart, $this->_mp->txtkmlgetdir, $this->_mp->txtback, $this->_mp->txtsearchnearby, $this->_mp->txtsearch, $this->_mp->openbyid, $this->_mp->openbyname, $this->_mp->maxcluster, $this->_mp->gridsize, $this->_mp->maxcluster, $this->_mp->minmarkerscluster, $this->_mp->maxlinesinfocluster, $this->_mp->clusterinfowindow, $this->_mp->clusterzoom, $this->_mp->clustermarkerzoom, $this->_mp->clustericonurl, $this->_mp->txtzoomin, $this->_mp->txtclustercount1,  $this->_mp->txtclustercount2, $clusteroptions, $idx, $val, $token);
	}
	
	function _processMapv3_template() {
		$code = "";
		$lbcode = "";

		$code.= "<!-- fail nicely if the browser has no Javascript -->
				<noscript><blockquote class='warning'><p>".$this->no_javascript."</p></blockquote></noscript>";			

		if ($this->_mp->mapprint!='none') {
	        // checks template image directory for image, if non found default are loaded
			if ($this->_mp->mapprint=='icon')
				$text = JHtml::_('image', 'system/printButton.png', JText::_('JGLOBAL_PRINT'), null, true);
			elseif ($this->_mp->mapprint=='text')
				$text = JText::_('JGLOBAL_PRINT');
			elseif  ($this->_mp->mapprint=='both')
				$text = '<span class="icon-print"></span>&#160;' . JText::_('JGLOBAL_PRINT') . '&#160;';
			else 			
				$text = $this->_mp->mapprint;
				
			$attribs['title']   = JText::_('JGLOBAL_PRINT');
			$attribs['onclick'] = "javascript:googlemap".$this->_mp->mapnm.".gmapPrint();return false;";
			$attribs['rel']     = 'nofollow';
			$attribs['class']     = 'mapprint';
			
			$code .= JHtml::_('link', "#", $text, $attribs);
		}

		$code.="<div id='mapplaceholder".$this->_mp->mapnm."'>";
		if ($this->_mp->align!='none')
			$code.="<div id='mapbody".$this->_mp->mapnm."' style=\"display: none; text-align:".$this->_mp->align."\">";
		else
			$code.="<div id='mapbody".$this->_mp->mapnm."' style=\"display: none;\">";
			
		if ($this->_mp->lightbox=='1') {
			$lboptions = array();
			if ($this->_mp->lbxzoom!="")
				$lboptions[] = "zoom : ".$this->_mp->lbxzoom;
			if ($this->_mp->lbxcenterlat!=""&&$this->_mp->lbxcenterlon!="")
				$lboptions[] = "mapcenter : \"".$this->_mp->lbxcenterlat." ".$this->_mp->lbxcenterlon."\"";
				
			$this->_lbxwidthorig = (is_numeric($this->_lbxwidthorig)?(($this->_mp->kmlsidebar=="left"||$this->_mp->kmlsidebar=="right")?$this->_lbxwidthorig+$this->_kmlsbwidthorig+5:$this->_lbxwidthorig)."px":$this->_lbxwidthorig);
			$lbname = (($this->_mp->gotoaddr=='1'||(($this->_mp->kmlrenderer=="google"&&count($this->_mp->kmlsb)!=0)||($this->_mp->kmlrenderer=="geoxml"&&(count($this->_mp->kml)!=0||count($this->_mp->kmlsb)!=0))&&($this->_mp->kmlsidebar=="left"||$this->_mp->kmlsidebar=="right"))||$this->_mp->animdir!='0'||$this->_mp->sv=='top'||$this->_mp->sv=='bottom'||$this->_mp->dir=='5'||($this->_mp->formaddress==1&&$this->_mp->animdir==0))?"lightbox":"googlemap");
			
			if ($this->_mp->show==1) {
				$code.="<a href='javascript:void(0)' onclick='javascript:MOOdalBox.open(\"".$lbname.$this->_mp->mapnm."\", \"".$this->_mp->lbxcaption."\", \"".$this->_lbxwidthorig." ".$this->_mp->lbxheight."\", googlemap".$this->_mp->mapnm.".map, {".implode(",",$lboptions)."});return false;' class='lightboxlink'>".html_entity_decode($this->_mp->txtlightbox)."</a>";
				$code .= "<div id='lightbox".$this->_mp->mapnm."' class='maplightbox' ".(($this->_mp->align!='none')?"style='text-align:".$this->_mp->align."'":"").">";
			} else {
				$lbcode.="<a href='javascript:void(0)' onclick='javascript:MOOdalBox.open(\"".$lbname.$this->_mp->mapnm."\", \"".$this->_mp->lbxcaption."\", \"".$this->_lbxwidthorig." ".$this->_mp->lbxheight."\", googlemap".$this->_mp->mapnm.".map, {".implode(",",$lboptions)."});return false;' class='lightboxlink'>".html_entity_decode($this->_mp->txtlightbox)."</a>";
				$code .= "<div id='lightbox".$this->_mp->mapnm."' class='maplightbox' style='display:none;".(($this->_mp->align!='none')?"text-align:".$this->_mp->align.";":"")."'>";
			}
		}
		
		if ($this->_mp->gotoaddr=='1')	{
			$code.="<form id=\"gotoaddress".$this->_mp->mapnm."\" class=\"gotoaddress\" onSubmit=\"javascript:googlemap".$this->_mp->mapnm.".gotoAddress();return false;\">";
			$code.="	<input id=\"txtAddress".$this->_mp->mapnm."\" name=\"txtAddress".$this->_mp->mapnm."\" type=\"text\" size=\"".$this->_mp->inputsize."\" value=\"\">";
			$code.="	<input name=\"Goto\" type=\"button\" class=\"button\" onClick=\"javascript:googlemap".$this->_mp->mapnm.".gotoAddress();return false;\" value=\"".$this->_mp->txtgotoaddr."\">";
			$code.="</form>";

		}

		if ($this->_mp->formaddress==1)
			$code.=$this->_processMapv3_templatedirform('Form');
			
		if ((($this->_mp->kmlrenderer=="google"&&count($this->_mp->kmlsb)!=0)||($this->_mp->kmlrenderer=="geoxml"&&(count($this->_mp->kml)!=0||count($this->_mp->kmlsb)!=0)))&&($this->_mp->kmlsidebar=="left"||$this->_mp->kmlsidebar=="right"))
			$code.="<table style=\"width:100%;border-spacing:0px;\">
					<tr>";

		if ((($this->_mp->kmlrenderer=="google"&&count($this->_mp->kmlsb)!=0)||($this->_mp->kmlrenderer=="geoxml"&&(count($this->_mp->kml)!=0||count($this->_mp->kmlsb)!=0)))&&$this->_mp->kmlsidebar=="left")
			$code.="<td style=\"width:".$this->_mp->kmlsbwidth.";height:".$this->_mp->height.";vertical-align:top;\"><div id=\"kmlsidebar".$this->_mp->mapnm."\" class=\"kmlsidebar\" style=\"align:left;width:".$this->_mp->kmlsbwidth.";height:".$this->_mp->height.";overflow:auto;\"></div></td>";

		if ((($this->_mp->kmlrenderer=="google"&&count($this->_mp->kmlsb)!=0)||($this->_mp->kmlrenderer=="geoxml"&&(count($this->_mp->kml)!=0||count($this->_mp->kmlsb)!=0)))&&($this->_mp->kmlsidebar=="left"||$this->_mp->kmlsidebar=="right"))
			$code.="<td>";
			
		if ($this->_mp->sv=='top'||($this->_mp->animdir!='0'&&$this->_mp->animdir!='3')) {
			$code.="<div id='svpanel".$this->_mp->mapnm."' class='svPanel' style='" . ($this->_mp->align != 'none' ? ($this->_mp->align == 'center' || $this->_mp->align == 'left' ? 'margin-right: auto; ' : '') . ($this->_mp->align == 'center' || $this->_mp->align == 'right' ? 'margin-left: auto; ' : '') : '') . "width:".$this->_mp->svwidth."; height:".$this->_mp->svheight."'><div id='svpanorama".$this->_mp->mapnm."' class='streetview' style='width:".$this->_mp->svwidth."; height:".$this->_mp->svheight.(($this->_mp->kmlsidebar=="right")?"float:left;":"").";'></div>";
			$code.="<div style=\"clear: both;\"></div>";
			$code.="</div>";
		}
			
		$code.="<div id=\"googlemap".$this->_mp->mapnm."\" ".((!empty($this->_mp->mapclass))?"class=\"".$this->_mp->mapclass."\"" :"class=\"map\"")." style=\"" . ($this->_mp->align != 'none' ? ($this->_mp->align == 'center' || $this->_mp->align == 'left' ? 'margin-right: auto; ' : '') . ($this->_mp->align == 'center' || $this->_mp->align == 'right' ? 'margin-left: auto; ' : '') : '') . "width:".$this->_mp->width."; height:".$this->_mp->height.";".(($this->_mp->show==0&&$this->_mp->lightbox==0)?"display:none;":"").(((($this->_mp->kmlrenderer=="google"&&count($this->_mp->kmlsb)!=0)||($this->_mp->kmlrenderer=="geoxml"&&(count($this->_mp->kml)!=0||count($this->_mp->kmlsb)!=0))&&$this->_mp->kmlsidebar=="right")||$this->_mp->animdir=='2')?"float:left;":"")."\"></div>";

		if ($this->_mp->sv=='bottom'||$this->_mp->animdir=="3") {
			$code.="<div style=\"clear: both;\"></div>";
			$code.="</div>";
			$code.="<div id='svpanel".$this->_mp->mapnm."' class='svPanel' style='" . ($this->_mp->align != 'none' ? ($this->_mp->align == 'center' || $this->_mp->align == 'left' ? 'margin-right: auto; ' : '') . ($this->_mp->align == 'center' || $this->_mp->align == 'right' ? 'margin-left: auto; ' : '') : '') . "width:".$this->_mp->svwidth."; height:".$this->_mp->svheight."'><div id='svpanorama".$this->_mp->mapnm."' class='streetview' style='width:".$this->_mp->svwidth."; height:".$this->_mp->svheight.(($this->_mp->kmlsidebar=="right")?"float:left;":"").";'></div>";
		}

		if ((($this->_mp->kmlrenderer=="google"&&count($this->_mp->kmlsb)!=0)||($this->_mp->kmlrenderer=="geoxml"&&(count($this->_mp->kml)!=0||count($this->_mp->kmlsb)!=0)))&&($this->_mp->kmlsidebar=="left"||$this->_mp->kmlsidebar=="right"))
			$code.="</td>";
		
		if ((($this->_mp->kmlrenderer=="google"&&count($this->_mp->kmlsb)!=0)||($this->_mp->kmlrenderer=="geoxml"&&(count($this->_mp->kml)!=0||count($this->_mp->kmlsb)!=0)))&&$this->_mp->kmlsidebar=="right")
			$code.="<td style=\"width:".$this->_mp->kmlsbwidth.";height:".$this->_mp->height.";vertical-align:top;\"><div id=\"kmlsidebar".$this->_mp->mapnm."\"  class=\"kmlsidebar\" style=\"align:left;width:".$this->_mp->kmlsbwidth.";height:".$this->_mp->height.";overflow:auto;\"></div></td>";
			
		if ((($this->_mp->kmlrenderer=="google"&&count($this->_mp->kmlsb)!=0)||($this->_mp->kmlrenderer=="geoxml"&&(count($this->_mp->kml)!=0||count($this->_mp->kmlsb)!=0)))&&($this->_mp->kmlsidebar=="left"||$this->_mp->kmlsidebar=="right"))
			$code.="</tr>
					</table>";

		if ($this->_mp->formaddress==2)
			$code.=$this->_processMapv3_templatedirform('Form');
			
		if (((!empty($this->_mp->tolat)&&!empty($this->_mp->tolon))||!empty($this->_mp->address)||($this->_mp->dir=='5'))&&($this->_mp->animdir!='2'||($this->_mp->animdir=='2'&&$this->_mp->showdir=='0')))
			$code.= "<div id=\"dirsidebar".$this->_mp->mapnm."\" class='directions' ".(($this->_mp->showdir=='0')?"style='display:none'":"")."></div>";

		if ($this->_mp->lightbox=='1')
			$code .= "</div>";

		// Close of mapbody div
		$code.="</div>";
		// Close of mapplaceholder div
		$code.="</div>";
		
		return array($code, $lbcode);
	}
	
	function _processMapv3_templatedirform($type) {
//		// Adding Joomla template structure 
//		// Get the path for the layout file
//		$path = JPluginHelper::getLayoutPath('system', 'plugin_googlemap3');
//		$layout      = new JLayoutFile('dirform', $path);
//
//		unset($path);
//
//		return $layout->render($this);
		
		$dirform="";
		$dirform="<form id='directionform".$this->_mp->mapnm."' action='".$this->protocol.$this->googlewebsite."/maps' method='get' target='_blank' onsubmit='javascript:googlemap".$this->_mp->mapnm.".DirectionMarkersubmit(this);return false;' class='mapdirform'>";
		
        $dirform.="<span class=\"txtdir\">".$this->_mp->txtdir."</span>";
		
		if ($type=='Marker') {
			$dirform.="<input ".(($this->_mp->txtto=='')?"type='hidden' ":"type='radio' ")." ".(($this->_mp->dirdefault=='0')?"checked='checked'":"")." name='dir' value='to'>".(($this->_mp->txtto!='')?"<span class=\"dirlabel dirto\">".$this->_mp->txtto."&nbsp;</span>":"")."<input type='radio' ".(($this->_mp->dirdefault=='1')?"checked='checked'":"")." name='dir' value='from'".(($this->_mp->txtfrom=='')?"style='display:none'":"").">".(($this->_mp->txtfrom!='')?"<span class=\"dirlabel dirfrom\">".$this->_mp->txtfrom."</span>":"");
			$dirform.="<br /><span class=\"dirlabel diraddr\">".$this->_mp->txtdiraddr."</span><input type='text' class='inputbox' size='".$this->_mp->inputsize."' name='saddr' id='saddr' value='' />";

			if (!empty($this->_mp->address))
				$dirform.="<input type='hidden' name='daddr' value=\"".$this->_mp->address."\"/>";
			else
				$dirform.="<input type='hidden' name='daddr' value='".(($this->_mp->latitude!='')?$this->_mp->latitude:$this->_mp->deflatitude).", ".(($this->_mp->longitude!='')?$this->_mp->longitude:$this->_mp->deflongitude)."'/>";
		}
		
		if ($type=='Form') {
			$dirform.=(($this->_mp->txtfrom=='')?"":"<br />")."<span class=\"dirlabel dirfrom\">".$this->_mp->txtfrom."</span><input ".(($this->_mp->txtfrom=='')?"type='hidden' ":"type='text'")." class='inputbox' size='".$this->_mp->inputsize."' name='saddr' id='saddr' value=\"".(($this->_mp->formdir=='1')?$this->_mp->address:(($this->_mp->formdir=='2')?$this->_mp->toaddress:""))."\" />";

			$dirform.=(($this->_mp->txtto=='')?"":"<br />")."<span class=\"dirlabel dirto\">".$this->_mp->txtto."</span><input ".(($this->_mp->txtto=='')?"type='hidden' ":"type='text'")." class='inputbox' size='".$this->_mp->inputsize."' name='daddr' id='daddr' value=\"".(($this->_mp->formdir=='1')?$this->_mp->toaddress:(($this->_mp->formdir=='2')?$this->_mp->address:""))."\" />";
		}
		
		if (($this->_mp->txt_driving!=''||$this->_mp->txt_avhighways!=''||$this->_mp->txt_transit!=''||$this->_mp->txt_bicycle!=''||$this->_mp->txt_walking!='')&&$this->_mp->formdirtype=='1')
			$dirform.="<br />";	

		$dirform.=$this->_processMapv3_templatedirform_dirtype($this->_mp->txt_driving, $this->_mp->dirtype, "D", "");
		$dirform.=$this->_processMapv3_templatedirform_dirtype($this->_mp->txt_avhighways, $this->_mp->avoidhighways, "1", "h");
		$dirform.=$this->_processMapv3_templatedirform_dirtype($this->_mp->txt_avtoll, $this->_mp->avoidtoll, "1", "t");
		$dirform.=$this->_processMapv3_templatedirform_dirtype($this->_mp->txt_transit, $this->_mp->dirtype, "R", "r");
		$dirform.=$this->_processMapv3_templatedirform_dirtype($this->_mp->txt_bicycle, $this->_mp->dirtype, "B", "b");
		$dirform.=$this->_processMapv3_templatedirform_dirtype($this->_mp->txt_walking, $this->_mp->dirtype, "W", "w");
		$dirform.=$this->_processMapv3_templatedirform_checktype($this->_mp->txt_optimize, $this->_mp->diroptimize, "diroptimize");
		$dirform.=$this->_processMapv3_templatedirform_checktype($this->_mp->txt_alternatives, $this->_mp->diralternatives, "diralternatives");
			
		$dirform.="<br/><input value='".$this->_mp->txtgetdir."' class='button' type='submit' style='margin-top: 2px;'>";
		
		if ($this->_mp->dir=='2')
			$dirform.= "<input type='hidden' name='pw' value='2'/>";

		if ($this->_mp->lang!='') 
			$dirform.= "<input type='hidden' name='hl' value='".$this->_mp->lang."'/>";

		$dirform.="</form>";

		return $dirform;
	}
	
	function _processMapv3_templatedirform_dirtype($dirtext, $dircheck, $dirchecktype, $dirvalue) {
		if ($dirtext!=''||$dircheck==$dirchecktype) {
			$text = "<input type='radio' class='radio' name='dirflg' value='".$dirvalue."' ".(($dircheck==$dirchecktype)?"checked='checked'":"").(($dirtext=='')?"style='display:none'":"")." />".(($dirtext!=''&&$this->_mp->formdirtype=='1')?"<span class=\"dirlabel dirtype\">".$dirtext."&nbsp;</span>":"");
		} else
			$text="";
		
		return $text;
	}
	
	function _processMapv3_templatedirform_checktype($dirtext, $dircheck, $dirname) {
		$text =(($dirtext!=''&&$this->_mp->formdirtype=='1')?"<br/>":"")."<input ".(($dirtext==''||$this->_mp->formdirtype=='0')?"type='hidden' ":"type='checkbox' ")."class='checkbox' name='".$dirname."' value='".$dircheck."' ".(($dircheck=='1')?"checked='checked'":"")." />".(($dirtext!=''&&$this->_mp->formdirtype=='1')?"<span class=\"dirlabel dircheck\">".$dirtext."</span>":"");
		
		return $text;
	}
	
	function _getInitialParams() {
		$filename = JPATH_SITE."/plugins/system/plugin_googlemap3/plugin_googlemap3.xml";
		
		// PHP changed external entity loading. This causes a warning when reading a local file. Switch to false to enable external entity loading.
		if (function_exists('libxml_disable_entity_loader'))
			$oldValue = libxml_disable_entity_loader( false );
		
		if ($xml = simplexml_load_file($filename)) {
			if (isset($xml->config[0]->fields[0]))
				$root = $xml->config[0]->fields[0];
			else
				$root =& $xml;
		
			foreach ($root->children() as $params) {
				foreach($params->children() as $param) {
					if ($param->attributes()->export=='1') {
						$name = $param->attributes()->name;
						if ($name=='lat') {
							$this->initparams->deflatitude = $this->params->get($name, $param->attributes()->default);
						} elseif ($name=='lon') {
							$this->initparams->deflongitude = $this->params->get($name, $param->attributes()->default);
						} elseif (substr($name,0,3)=='txt') {
							$nm = strtolower($name);
							$this->initparams->$nm = $this->params->get($name, '');
						} else {
							$nm = strtolower($name);
							$this->initparams->$nm = (string) $this->params->get($name, $param->attributes()->default);
						}
					}
				}
			}
		}

		if (function_exists('libxml_disable_entity_loader'))
			libxml_disable_entity_loader( $oldValue );	
			
		// Clean up generated variables
		unset($filename, $xml, $root, $params, $param, $name, $nm, $oldValue);
	}
	
	function _getURL($url) {
		$ok = false;
		$getpage = "";
		if (ini_get('allow_url_fopen')) { 
			if (file_exists($url)) {
				$getpage = file_get_contents($url);
				$ok = true;
			}
		} 
		
		if (!$ok) { 
			$this->_debug_log("URI couldn't be opened probably ALLOW_URL_FOPEN off");
			if (function_exists('curl_init')) {
				$this->_debug_log("curl_init does exists");
				$ch = curl_init();
				$timeout = 5; // set to zero for no timeout
				curl_setopt ($ch, CURLOPT_URL, $url);
				curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
				$getpage = curl_exec($ch);
				curl_close($ch);
			} else
				$this->_debug_log("curl_init doesn't exists");
		}
		$this->_debug_log("Returned page: ".htmlentities($getpage));
		
		// Clean up generated variables
		unset($ok, $ch, $timeout);
		
		return $getpage;
	}

	function get_geo($address)
	{
		$this->_debug_log("get_geo(".$address.")");
	
		$coords = '';
		$getpage='';
		$replace = array("\n", "\r", "&lt;br/&gt;", "&lt;br /&gt;", "&lt;br&gt;", "<br>", "<br />", "<br/>");
		$address = str_replace($replace, '', $address);

		// Convert address to utf-8 encoding
		if (function_exists('mb_detect_encoding')) {
			$enc = mb_detect_encoding($address);
			if (!empty($enc))
				$address = mb_convert_encoding($address, "utf-8", $enc);
			else
				$address = mb_convert_encoding($address, "utf-8");
		}

		$this->_debug_log("Address: ".$address);
		
		$uri = 'maps.googleapis.com';
		$uri = $this->protocol.$uri."/maps/api/geocode/xml?address=".urlencode($address)."&sensor=false";
		$this->_debug_log("get_geo(".$uri.")");
		$getpage = $this->_getURL($uri);

		if (function_exists('mb_detect_encoding')) {
			$enc = mb_detect_encoding($getpage);
			if (!empty($enc))
				$getpage = mb_convert_encoding($getpage, "utf-8", $enc);
		}

		if ($getpage <>'') {
			$expr = '/xmlns/';
			$getpage = preg_replace($expr, 'id', $getpage);
			try {
				$xml = new SimpleXMLElement($getpage);
				foreach($xml->xpath('//location') as $coordinates) {
					$coords = $coordinates->lat.", ".$coordinates->lng;
					break;
				}
				if ($coords=='')
					$this->_debug_log("Coordinates: null");
				else
					$this->_debug_log("Coordinates: ".$coords);
			} catch(Exception $e) {
				$this->_debug_log("Coordinates: ERROR");
		    }
		} else
			$this->_debug_log("get_geo totally wrong end!");
	
		// Clean up variables
		unset($coord, $getpage, $replace, $enc, $uri, $ok, $ch, $timeout, $expr, $xml, $coordinates);
		
		return $coords;
	}
	
	function _debug_log($text)
	{
		if ($this->debug_plugin =='1')
			$this->debug_text .= "\n// ".$text." (".round($this->_memory_get_usage()/1024)." KB)";
	
		return;
	}
	
	function _get_index($string)
	{
		if ($this->brackets=='{') {
			$string = preg_replace("/^(.*?)\[/", '', $string);
			$string = preg_replace("/\](.*?)$/", '', $string);
			
		} else {
			$string = preg_replace("/^.*\(/", '', $string);
			$string = preg_replace("/\).*$/", '', $string);
		}
		
		return $string;
	}
	
    function _memory_get_usage()
    {
		if ( function_exists( 'memory_get_usage' ) )
			return memory_get_usage(); 
		else
			return 0;
    }

	function _get_API_key () {
		$url = trim($this->urlsetting);
		$replace = array('http://', 'https://');
		$url = str_replace($replace, '', $url);


		$url = (($this->protocol=='https://')?$this->protocol:'').$url;
		$this->_debug_log("url: ".$url);
		$key = '';
		$multikey = trim($this->params->get( 'Google_Multi_API_key', '' ));
		if ($multikey!='') {
			$this->_debug_log("multikey: ".$multikey);
			$replace = array("\n", "\r", "<br/>", "<br />", "<br>");
			$sites = preg_split("/[\n\r]+/", $multikey);
			foreach($sites as $site)
			{
				$values = explode(";",$site, 2);
				if (count($values)>1) {
					$values[0] = trim(str_replace($replace, '', $values[0]));
					$values[1] = str_replace($replace, '', $values[1]);
					$this->_debug_log("values[0]: ".$values[0]);
					$this->_debug_log("values[1]: ".$values[1]);
					if ($url==$values[0])
					{
						$key = trim($values[1]);
						break;
					}
				}
			}
		}
		if ($key=='')
			$key = trim($this->params->get( 'Google_API_key', '' ));

		// Clean up variables
		unset($url, $replace, $multikey, $sites, $site, $values);
		$this->_debug_log("key: ".$key);
		return $key;
	}
	
	function _randomkeys($length)
	{
		$key = "";
		$pattern = "1234567890abcdefghijklmnopqrstuvwxyz";
		for($i=0;$i<$length;$i++)
		{
			$key .= $pattern{rand(0,35)};
		}
		
		// Clean up variables
		unset($i, $pattern);
		return $key;
	}

	function _translate($orgtext, $lang) {
		$langtexts = preg_split("/[\n\r]+/", $orgtext);
		$text = "";

		if (is_array($langtexts)) {
			$replace = array("\n", "\r", "<br/>", "<br />", "<br>");
			$firsttext = "";
			foreach($langtexts as $langtext) {
				$values = explode(";",$langtext, 2);
				if (count($values)>1) {
					$values[0] = trim(str_replace($replace, '', $values[0]));
					if ($firsttext == "")
						$firsttext = $values[1];
						
					if (trim($lang)==$values[0])
					{
						$text = $values[1];
						break;
					}
				}
			}
			// Not found
			if ($text=="")
				$text = $firsttext;
		}	
		
		if ($text=="")
			$text = $orgtext;
	
		$text = htmlspecialchars_decode($text, ENT_NOQUOTES);
	
		// Clean up variables
		unset($langtexts, $replace, $langtext, $values);
		return $text;
	}
	
	function _getlang() {
		$this->_debug_log("langtype: ".$this->langtype);

		if ($this->langtype == 'site') {
			$lang = $this->lang->getTag();
			$this->_debug_log("site lang: ".$lang);
		} else if ($this->langtype == 'config') {
			$lang = $this->params->get( 'lang', '' );
			$this->_debug_log("config lang: ".$lang);
		} else if ($this->langtype == 'joomfish'&&isset($_COOKIE['jfcookie'])) {
			$lang = $_COOKIE['jfcookie']['lang']; 
			$this->_debug_log("Joomfish lang: ".$lang);
		} else {
			$lang = '';
			$this->_debug_log("No language: ".$lang);
		} 
		
		// Clean up variables
		unset($locale_parts);
		return $lang;
	}
	
	function _remove_html_tags($text) {
		$reg[] = "/<span[^>]*?>/si";
		$repl[] = '';
		$reg[] = "/<\/span>/si";
		$repl[] = '';
		$text = preg_replace( $reg, $repl, $text );
		
		// Clean up variables
		unset($reg, $repl);
		return $text;
	}
	
	function _make_absolute($link) {
		if (substr($link,0, 7)!='http://'&&substr($link,0, 8)!='https://') {
			if (substr($link,0, 4)!='www.') {
				if (substr($link,0,1)=='/')
					return $this->url.$link;
				else
					return $this->url.'/'.$link;
			} else
				return $this->protocol.$link;
		}
		return $link;
	}
	
	function _addscript($url) {
		// The method depends on event type. onAfterRender is complex and others are simple based on framework
		if ($this->event!='onAfterRender')
			$this->document->addScript($url);
		else
			$this->_addscriptinheader($url, $this->_text, false);
	}
	
	function _addscriptinheader($url, &$text, $first_script) {
		// Get header
		$reg = "/(<HEAD[^>]*>)(.*?)(<\/HEAD>)(.*)/si";
		$count = preg_match_all($reg,$text,$html);	
		if ($count>0) {
			$head=$html[2][0];
		} else {
			$head='';
		}
		// clean browser if statements
		$reg = "/<!--\[if(.*?)<!\[endif\]-->/si";
		$head = preg_replace($reg, '', $head);

		// define scripts regex
		$reg = '/<script.*src=[\'\"](.*?)[\'\"][^>]*[^<]*(<\/script>)?/i';
		$found = false;
		
		$count = preg_match_all($reg,$head,$scripts,PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);	

		if ($count>0)
			foreach ($scripts[1] as $script) {
				if ($script[0]==$url) {
					$found = true;
					break;
				}
			}
			
		if (!$found) {
			$script = "\n<script type='text/javascript' src='".$url."'></script>\n";
			if ($count==0) {
				// No scripts then just add it before </head>
				$text = preg_replace("/<head(| .*?)>(.*?)<\/head>/is", "<head$1>$2".$script."</head>", $text);
			} else {
				if ($first_script) {
					// add script before the first script
					// position first script
					$pos = strpos($text, trim($scripts[0][0][0]));
					$text = substr($text,0, $pos).$script.substr($text,$pos);
				} else {
					// add script after the last script
					// position last script and add length
					$pos = strpos($text, trim($scripts[0][$count-1][0]))+strlen(trim($scripts[0][$count-1][0]));
					$text = substr($text,0, $pos).$script.substr($text,$pos);
				}
			}
		}
		
		// Clean up variables
		unset($reg, $count, $head, $found, $scripts, $script, $pos);
	}
	
	function _addscriptinheaderaftermootools($url, &$text, $first_script) {
		// Get header
		$reg = "/(<HEAD[^>]*>)(.*?)(<\/HEAD>)(.*)/si";
		$count = preg_match_all($reg,$text,$html);	
		if ($count>0) {
			$head=$html[2][0];
		} else {
			$head='';
		}
		// clean browser if statements
		$reg = "/<!--\[if(.*?)<!\[endif\]-->/si";
		$head = preg_replace($reg, '', $head);

		// define scripts regex
		$reg = '/<script.*src=[\'\"](.*?)[\'\"][^>]*[^<]*(<\/script>)?/i';
		$found = false;
		
		$count = preg_match_all($reg,$head,$scripts,PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);	

		if ($count>0)
			foreach ($scripts[1] as $script) {
				if ($script[0]==$url) {
					$found = true;
					break;
				}
			}
			
		if (!$found) {
			$code = "\n<script type='text/javascript' src='".$url."'></script>\n";
			if ($count==0) {
				// No scripts then just add it before </head>
				$this->_debug_log("Load only script on page");			
				$text = preg_replace("/<head(| .*?)>(.*?)<\/head>/is", "<head$1>$2".$code."</head>", $text);
			} else {
				// Find mootools
				$mootools = false;
				$cnt = 0;
				foreach ($scripts[1] as $script) {
					if (strpos($script[0], "mootools")) {
						$mootools = true;
						break;
					}
					$cnt++;
				}
				
				if($mootools) {
					$this->_debug_log("Load script direct after mootools");			
					$pos = strpos($text, trim($scripts[0][$cnt][0]))+strlen(trim($scripts[0][$cnt][0]));
					$text = substr($text,0, $pos).$code.substr($text,$pos);
				} else {
					if ($first_script) {
						// add script before the first script
						// position first script
						$this->_debug_log("Load script first");			
						$pos = strpos($text, trim($scripts[0][0][0]));
						$text = substr($text,0, $pos).$code.substr($text,$pos);
					} else {
						// add script after the last script
						// position last script and add length
						$this->_debug_log("Load script last");			
						$pos = strpos($text, trim($scripts[0][$count-1][0]))+strlen(trim($scripts[0][$count-1][0]));
						$text = substr($text,0, $pos).$code.substr($text,$pos);
					}
				}
			}
		}
		
		// Clean up variables
		unset($reg, $count, $head, $found, $scripts, $script, $pos, $mootools, $code);
	}

	function _addstylesheet($url) {
		// The method depends on event type. onAfterRender is complex and others are simple based on framework
		if ($this->event!='onAfterRender')
			$this->document->addStyleSheet($url);
		else {
			// Get header
			$reg = "/(<HEAD[^>]*>)(.*?)(<\/HEAD>)(.*)/si";
			$count = preg_match_all($reg,$this->_text,$html);	
			if ($count>0) {
				$head=$html[2][0];
			} else {
				$head='';
			}
			
			// clean browser if statements
			$reg = "/<!--\[if(.*?)<!\[endif\]-->/si";
			$head = preg_replace($reg, '', $head);

			// define scripts regex
			$reg = '/<link.*href=[\'\"](.*?)[\'\"][^>]*[^<]*(<\/link>)?/i';
			$found = false;
			
			$count = preg_match_all($reg,$head,$styles,PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);	
			if ($count>0)
				foreach ($styles[1] as $style) {
					if ($style[0]==$url) {
						$found = true;
						break;
					}
				}
				
			if (!$found) {
				$style = "\n<link href='".$url."' rel='stylesheet' type='text/css' />\n";
				if ($count==0) {
					// No styles then just add it before </head>
					$this->_text = preg_replace("/<head(| .*?)>(.*?)<\/head>/is", "<head$1>$2".$style."</head>", $this->_text);
				} else {
					//add style after the last style
					// position last style and add length
					$pos = strpos($this->_text, trim($styles[0][$count-1][0]))+strlen(trim($styles[0][$count-1][0]));
					$this->_text = substr($this->_text,0, $pos).$style.substr($this->_text,$pos);
				}
			}
			
			// Clean up variables
			unset($reg, $count, $head, $found, $styles, $style, $pos);
		}
	}
	function _addstyledeclaration($source) {
		// The method depends on event type. onAfterRender is complex and others are simple based on framework
		if ($this->event!='onAfterRender')
			$this->document->addStyleDeclaration($source);
		else {
			// Get header
			$reg = "/(<HEAD[^>]*>)(.*?)(<\/HEAD>)(.*)/si";
			$count = preg_match_all($reg,$this->_text,$html);	
			if ($count>0) {
				$head=$html[2][0];
			} else {
				$head='';
			}
			
			// clean browser if statements
			$reg = "/<!--\[if(.*?)<!\[endif\]-->/si";
			$head = preg_replace($reg, '', $head);

			// define scripts regex
			$reg = '/<style[^>]*>(.*?)<\/style>/si';
			$found = false;
			
			$count = preg_match_all($reg,$head,$styles,PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);	
			if ($count>0)
				foreach ($styles[1] as $style) {
					if ($style[0]==$source) {
						$found = true;
						break;
					}
				}
				
			if (!$found) {
				$source = "\n<style type='text/css'>\n".$source."\n</style>\n";
				if ($count==0) {
					// No styles then just add it before </head>
					$this->_text = preg_replace("/<head(| .*?)>(.*?)<\/head>/is", "<head$1>$2".$source."</head>", $this->_text);
				} else {
					//add style after the last style
					// position last style and add length
					$pos = strpos($this->_text, trim($styles[0][$count-1][0]))+strlen(trim($styles[0][$count-1][0]));
					$this->_text = substr($this->_text,0, $pos).$source.substr($this->_text,$pos);
				}
			}
			
			// Clean up variables
			unset($reg, $count, $head, $found, $styles, $style, $pos);
		}
	}
	

	function _is_utf8($string) { // v1.01
	//	define('_is_utf8_split',5000);
	//	if (strlen($string) > _is_utf8_split) {
		if (strlen($string) > 5000) {
			// Based on: http://mobile-website.mobi/php-utf8-vs-iso-8859-1-59
			for ($i=0,$s=_is_utf8_split,$j=ceil(strlen($string)/_is_utf8_split);$i < $j;$i++,$s+=_is_utf8_split) {

				if (is_utf8(substr($string,$s,_is_utf8_split)))
					return true;
			}
			return false;
		} else {
			// From http://w3.org/International/questions/qa-forms-utf-8.html
			return preg_match('%^(?:
					[\x09\x0A\x0D\x20-\x7E]            # ASCII
				| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
				|  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
				| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
				|  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
				|  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
				| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
				|  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
			)*$%xs', $string);
		}
	} 
}

?>