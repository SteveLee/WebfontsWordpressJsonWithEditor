<?php
/*
Copyright 2010 Monotype Imaging Inc.  
This program is distributed under the terms of the GNU General Public License
*/
header("content-type: text/css");	
include_once('../../../wp-load.php');
include_once('../../../wp-includes/wp-db.php');

global $wpdb;
global $wp_wfs_configure_table;
$wfs_details = getUnPass();
$wfs_public_key = $wfs_details['1'];
$wfs_private_key = $wfs_details['2'];

if(empty($_GET['pid'])){
	$project_array = wfs_get_key();
}else{
$project_array = wfs_get_key('admin');
}
$key = $project_array[0];
$browser = browserName();
$apiurl = "json/Fonts/?wfspid=".$key;
$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
$jsonUrl = $wfs_api->wfs_getInfo_post();
//Creating JSON Instance

	//Creating array from json data
	$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
	$fontsArray = $json->decode($jsonUrl);
	$fonts =  $fontsArray['Fonts']['Font'];
	$webfonts=array();
	$fontsList="";
	$stylesheetcss="";
	if(!empty($fonts)){
		$is_multi = is_multi($fonts);
		if($is_multi == 1){	
				$FontName= $fonts['FontName'];
				$FontCSSName=  $fonts['FontCSSName'];
				$CDNKey=  $fonts['CDNKey'];
				if($browser =="Internet Explorer (MSIE/Compatible)")
				{
				$TTF=  $fonts['EOT'];
				$ext=".eot";
				}else{
				$TTF=  $fonts['TTF'];
				$ext=".ttf";
				}
				
				$fontsList.= "\"".$FontName."/'".$FontCSSName."';\" + ";
				$stylesheetcss.="@font-face{font-family:'".$FontCSSName."';src:url('".FONTFCURI.$TTF.$ext."?".$CDNKey."&projectId=".$key."');}";
		}
		else{
			foreach($fonts as $font){
				$FontName= $font['FontName'];
				$FontCSSName=  $font['FontCSSName'];
				$CDNKey=  $font['CDNKey'];
				if($browser =="Internet Explorer (MSIE/Compatible)")
				{
				$TTF=  $font['EOT'];
				$ext=".eot";
				}else{
				$TTF=  $font['TTF'];
				$ext=".ttf";
				}
				
				$fontsList.= "\"".$FontName."/'".$FontCSSName."';\" + ";
				$stylesheetcss.="@font-face{font-family:'".$FontCSSName."';src:url('".FONTFCURI.$TTF.$ext."?".$CDNKey."&projectId=".$key."');}";
			}
		}
	}
echo $stylesheetcss;


?>