<?php
/*------------------------------------------------------------------------
# dirform.php - Google Maps plugin
# ------------------------------------------------------------------------
# author    Mike Reumer
# copyright Copyright (C) 2011 tech.reumer.net. All Rights Reserved.
# @license - http://www.gnu.org/copyleft/gpl.html GNU/GPL
# Websites: http://tech.reumer.net
# Technical Support: http://tech.reumer.net/Contact-Us/Mike-Reumer.html 
# Documentation: http://tech.reumer.net/Google-Maps/Documentation-of-plugin-Googlemap/
--------------------------------------------------------------------------*/

defined( '_JEXEC' ) or die( 'Restricted access' );

$dirform="";
$dirform="<form id='directionform".$this->_mp->mapnm."' action='".$this->protocol.$this->googlewebsite."/maps' method='get' target='_blank' onsubmit='javascript:googlemap".$this->_mp->mapnm.".DirectionMarkersubmit(this);return false;' class='mapdirform'>";

$dirform.=$this->_mp->txtdir;

if ($type=='Marker') {
    $dirform.="<input ".(($this->_mp->txtto=='')?"type='hidden' ":"type='radio' ")." ".(($this->_mp->dirdefault=='0')?"checked='checked'":"")." name='dir' value='to'>".(($this->_mp->txtto!='')?$this->_mp->txtto."&nbsp;":"")."<input ".(($this->_mp->txtfrom=='')?"type='hidden' ":"type='radio' ").(($this->_mp->dirdefault=='1')?"checked='checked'":"")." name='dir' value='from'>".(($this->_mp->txtfrom!='')?$this->_mp->txtfrom:"");
    $dirform.="<br />".$this->_mp->txtdiraddr."<input type='text' class='inputbox' size='".$this->_mp->inputsize."' name='saddr' id='saddr' value='' />";

    if (!empty($this->_mp->address))
        $dirform.="<input type='hidden' name='daddr' value=\"".$this->_mp->address."\"/>";
    else
        $dirform.="<input type='hidden' name='daddr' value='".(($this->_mp->latitude!='')?$this->_mp->latitude:$this->_mp->deflatitude).", ".(($this->_mp->longitude!='')?$this->_mp->longitude:$this->_mp->deflongitude)."'/>";
}

if ($type=='Form') {
    $dirform.=(($this->_mp->txtfrom=='')?"":"<br />").$this->_mp->txtfrom."<input ".(($this->_mp->txtfrom=='')?"type='hidden' ":"type='text'")." class='inputbox' size='".$this->_mp->inputsize."' name='saddr' id='saddr' value=\"".(($this->_mp->formdir=='1')?$this->_mp->address:(($this->_mp->formdir=='2')?$this->_mp->toaddress:""))."\" />";

    $dirform.=(($this->_mp->txtto=='')?"":"<br />").$this->_mp->txtto."<input ".(($this->_mp->txtto=='')?"type='hidden' ":"type='text'")." class='inputbox' size='".$this->_mp->inputsize."' name='daddr' id='daddr' value=\"".(($this->_mp->formdir=='1')?$this->_mp->toaddress:(($this->_mp->formdir=='2')?$this->_mp->address:""))."\" />";
}

if (($this->_mp->txt_driving!=''||$this->_mp->txt_avhighways!=''||$this->_mp->txt_transit!=''||$this->_mp->txt_bicycle!=''||$this->_mp->txt_walking!='')&&$this->_mp->formdirtype=='1')
    $dirform.="<br />";	

$dirform.=$this->_processMapv3_templatedirform_dirtype($this->_mp->txt_driving, $this->_mp->dirtype, "D", "");
$dirform.=$this->_processMapv3_templatedirform_dirtype($this->_mp->txt_avhighways, $this->_mp->avoidhighways, "D", "h");
$dirform.=$this->_processMapv3_templatedirform_dirtype($this->_mp->txt_avtoll, $this->_mp->avoidtoll, "D", "t");
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

echo $dirform;

?>