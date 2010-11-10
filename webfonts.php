<?php
ob_start();
/*
Plugin Name: Webfonts
Plugin URI: http://webfonts.fonts.com/developers
Version: 1.0
Description: A Plugin that will add a webfonts service in wordpress.
Author: MTI
Author URI: http://webfonts.fonts.com/

Copyright 2010 Monotype Imaging Inc.  
This program is distributed under the terms of the GNU General Public License
*/

/* 
* Function to call when the plugins is avtivated
*/
function wfs_error_log($errors, $show=false) {
	if($show) {
		if(is_array($errors) || is_object($errors)) {
			print_r($errors);	
		}
		else {
			echo $errors;	
		}
	}
}

function wfs_activate(){ //This is all the stuff the plug-in needs to do when it is activated
	global $wp_wfs_configure_table;
	global $wfs_editor_fonts;
	
	/***** 
	Changes for: Option to show system fonts in editor 
	Changes: show_system_fonts added
	- By Keshant  
	*****/	
	
	$sql = "DROP TABLE IF EXISTS `".$wp_wfs_configure_table."`;
		CREATE TABLE `".$wp_wfs_configure_table."` (
		`wfs_configure_id` int(200) NOT NULL auto_increment,
		`project_name` varchar(255) NOT NULL default '',
		`project_key` varchar(255) NOT NULL default '',
		`project_day` varchar(255) NOT NULL default '0-6',
		`project_page_option` enum('0','1','2') NOT NULL default '0',
		`project_pages` text NOT NULL,
		`project_options` enum('0','1') NOT NULL default '0',
		`wysiwyg_enabled` enum('0','1') NOT NULL default '0' COMMENT '0>disabled, 1>enabled',
		`is_active` enum('0','1') NOT NULL default '0' COMMENT '0>inActive, 1>Active',
		`user_id` varchar(255) NOT NULL default '',
		`user_type` enum('0','1') NOT NULL default '0' COMMENT '0>free, 1> paid',
		`editor_select` enum('0','1') NOT NULL DEFAULT '0',
		`show_system_fonts` enum('0','1','2','3') NOT NULL DEFAULT '0',
		`updated_date` timestamp NOT NULL default '0000-00-00 00:00:00' on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`wfs_configure_id`)
		) ENGINE=MyISAM  AUTO_INCREMENT=0  DEFAULT CHARSET=utf8 ;";
	/**** End ****/
	
	createTable($wp_wfs_configure_table, $sql); //Little function to check that the table does not already exist
	
	$editor_table_var = "DROP TABLE IF EXISTS `".$wfs_editor_fonts."`;
		CREATE TABLE IF NOT EXISTS `".$wfs_editor_fonts."` (
	  	`wfs_font_id` int(11) NOT NULL AUTO_INCREMENT,
	  	`tinymce_name` varchar(250) NOT NULL,
	  	`ckeditor_name` varchar(250) NOT NULL,
	  	`is_admin` enum('0','1','2') NOT NULL DEFAULT '1',
	  	`pid` int(11) NOT NULL,
	  `is_active` enum('0','1') NOT NULL DEFAULT '0',
	  	PRIMARY KEY (`wfs_font_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1;";
	
	createTable($wfs_editor_fonts, $editor_table_var); //Little function to check that the table does not already exist
}
 
/*
*Function to creat table in wordpress datbaase
*@call in the activation
*/
function createTable($tableName, $sql){//reusable function
    global $wpdb;//call $wpdb to the give us the access to the DB
    if($wpdb->get_var("show tables like '". $tableName . "'") != $tableName) { //check whether the table exists or not
    require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);
    }
}



function wfs_deactivate () {
	global $wp_wfs_configure_table, $wfs_editor_fonts, $wpdb;
	$wpdb->query("DROP TABLE {$wp_wfs_configure_table}");
	$wpdb->query("DROP TABLE {$wfs_editor_fonts}");
	delete_option('webfonts_public_key');
	delete_option('webfonts_private_key');
	delete_option('webfonts_userid');
	delete_option('webfonts_usertype');
}

/*
* Add a webfonts menu in the admin section
*/
function wfs_menu() {//Function to create our menu
	$wfs_admin_page = add_menu_page('Fonts.com Webfonts', 'Fonts.com Webfonts', 'administrator', 'wfs_options', 'wfs_options');
	add_action( "admin_print_scripts-$wfs_admin_page", 'wfs_admin_head' );
	
}

/*
Admin interface for the plugin
*/
function wfs_options(){
	global $wpdb;
	global $wp_wfs_configure_table;
	global $wfs_editor_fonts;
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	include_once('webfonts_admin.php');
}
/**
* Authenticating with Webfonts when we provide username and password.
* pass two parameters username and password of webfonts
*/
function wfs_authSubmit($wfs_public_key,$wfs_private_key){
		//start checking in webfonts for authentication
		
		//Fetching the json data from WFS
		$apiurl = "json/Projects/";
		$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
		$jsonUrl = $wfs_api->wfs_getInfo_post();
		//Creating JSON Instance
		//authenticate stored username and password...
		update_option('webfonts_public_key',$wfs_public_key);
		update_option('webfonts_private_key',$wfs_private_key);
		//Creating array from json data
		$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		$loginsArray = $json->decode($jsonUrl);
		$Message  = $loginsArray['Projects']['Message'];
		if($Message=="Success"){
			$UserId = $loginsArray['Projects']['UserId'];
			$UserRole  = $loginsArray['Projects']['UserRole'];
			update_option('webfonts_userid',$UserId);
			update_option('webfonts_usertype',(strtolower($UserRole)=="free")?0:1);
			
			$status=1;
		}else{
			$_SESSION['wfs_message'] = $loginsArray['Message'];
			$status=0;			
		}
		
		wfs_error_log($jsonUrl);
		
		return array($status);
		//end checking in webfonts for authentication
}

/*
* Listing the project from ajax call
*/
function wfs_project_list(){
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	$output = "";
	
	$pageStart = (!empty($_POST['pageStart']))?$_POST['pageStart']:0;
   	//fetchin json data from fonts
	$apiurl = "json/Projects/?wfspstart=".$pageStart."&wfsplimit=".PROJECT_LIMIT;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$jsonUrl = $wfs_api->wfs_getInfo_post();
	//create json instance
	if($jsonUrl != ""){
	//Creating array from json data
	$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
	$projectsArray = $json->decode($jsonUrl);
	
	$message = $projectsArray['Projects']['Message'];
	if($message == "Success"){
	//fetching Array data
	$cnt = 1;
	$projects = $projectsArray['Projects']['Project'];
	$output.= '<ul >';
	if(!empty($projects)){
		$is_multi = is_multi($projects);
		if($is_multi == 1){
			$projectName = $projects['ProjectName'];
			$projectKey = $projects['ProjectKey'];
			$webfonts_added_project =   webfonts_project_profile_load($projectKey, "project_key");
			if(empty($webfonts_added_project->project_key)){
			$output.= '<li><label class="selectit" for="'.$projectKey.'">
					   <input id="'.$projectKey.'" type="checkbox"  value="'.$projectKey.'" name="project_import[]" class="import_project"/>&nbsp;&nbsp;'.$projectName.'
					</label><input  type="hidden"  value="'.$projectName.'" name="project_name['.$projectKey.']"/></li>';
			$cnt++;
			$status = true;
			}
			else{
				$output.= '<li><label class="selectit" for="'.$projectKey.'">
					   <input id="'.$projectKey.'" type="checkbox" disabled="disabled"  value="'.$projectKey.'" name="project_import[]" class="import_project"/>&nbsp;&nbsp;'.$projectName.' <i style="font-size:10px;color:#21759B;">(Project already added.)</i>
					</label><input  type="hidden"  value="'.$projectName.'" name="project_name['.$projectKey.']"/></li>';
					$cnt++;
					$status = true;
				}
			}
		else{
			foreach( $projects as $project )
				{
				$projectName = $project['ProjectName'];
				$projectKey = $project['ProjectKey'];
				$webfonts_added_project =   webfonts_project_profile_load($projectKey, "project_key");
				if(empty($webfonts_added_project->project_key)){
				$output.= '<li><label class="selectit" for="'.$projectKey.'">
						   <input id="'.$projectKey.'" type="checkbox"  value="'.$projectKey.'" name="project_import[]" class="import_project"/>&nbsp;&nbsp;'.$projectName.'
						</label><input  type="hidden"  value="'.$projectName.'" name="project_name['.$projectKey.']"/></li>';
				$cnt++;
				$status = true;
				}
				else{
					$output.= '<li><label class="selectit" for="'.$projectKey.'">
						   <input id="'.$projectKey.'" type="checkbox" disabled="disabled"  value="'.$projectKey.'" name="project_import[]" class="import_project"/>&nbsp;&nbsp;'.$projectName.' <i style="font-size:10px;color:#21759B;">(Project already added.)</i>
						</label><input  type="hidden"  value="'.$projectName.'" name="project_name['.$projectKey.']"/></li>';
						$cnt++;
						$status = true;
					}
				
			} //end of foreach
		}//end of else for is_multi
	}// end of empty projects
	
	//Getting the data for pagintaion from array
	$totalRecordjson =$projectsArray['Projects']['TotalRecords'];
	$pageStartjson =$projectsArray['Projects']['PageStart'];
	$pageLimitjson =$projectsArray['Projects']['PageLimit'];

	if($cnt == 1){
		$output.= '<li> No project available.</li>';
		$status = true;
		}
	$output.= '</ul>';
	}else{
		$status = false;
		$output = $message;
		}
	$pagination="&nbsp;";
	$pageLimit =(!empty($_POST['pageLimit']))?$_POST['pageLimit']:$pageLimitjson;
	 $totalRecord = (!empty($_POST['totalRecords']))?$_POST['totalRecords']:$totalRecordjson;
	$contentDiv = $_POST['contentDiv'];
	$paginationDiv = $_POST['paginationDiv'];
	if($totalRecord !=0 && $pageLimit!="" && $cnt != 1){
		$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,$contentDiv,$paginationDiv,"wfs_project_action");
		$pagination = $wfs_pg->getPagination();
		}
	}//end of json url if
	else {
		$status = false;
		}
echo json_encode(array('data'=>$output,'status'=>$status,'pagination'=>$pagination));

// display erros
wfs_error_log($jsonUrl);

exit;
}
/*
* Listing the selectors for ajax call
*/
function wfs_selector_list(){
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	global $wp_wfs_configure_table;
	global $wpdb;
	$output = "";
	$data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wp_wfs_configure_table." WHERE wfs_configure_id = %d ",$_POST['pid']));
	$pageStart= 0;

	//fetchin json data from fonts
	$apiurl = "json/Selectors/?wfspstart=0&wfsplimit=".SELECTOR_LIMIT."&wfspid=".$data->project_key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$jsonUrl = $wfs_api->addSelector($_POST['selectorname']);
	//create json instance
	if($jsonUrl!=""){
	//Creating array from json data
	$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
	$selectorsArray = $json->decode($jsonUrl);
	
	$message = $selectorsArray['Selectors']['Message'];
	$count = 1;
	
	if($message == "Success"){
	//fetching XML data
	$selectors = $selectorsArray['Selectors']['Selector'];
	$output.= '<table cellspacing="0" cellpadding="0" border="0" class="widefat" style="margin-top:20px;" ><tbody>';
	if(!empty($selectors)){
	$is_multi = is_multi($selectors);
	if($is_multi == 1){
		$SelectorTag = $selectors['SelectorTag'];
			$SelectorID =  $selectors['SelectorID'];
			$SelectorFontID = $selectors['SelectorFontID'];
			$fontsArr = wfs_font_list($data->project_key,$SelectorFontID,$count);
			$output.='<tr style="height:40px;">
				<td>'.$SelectorTag.'</td>
				<td>'.$fontsArr[0].'</td>
				<td><span class="wfs_selectors" style="font-size:26px;font-family:'.$fontsArr[3].'" id="fontid_'.$count.'">'.$fontsArr[1].'</span></td>
				<td><a href="admin.php?page=wfs_options&func=selector_act&pid='.$_POST['pid'].'&sid='.$SelectorID.'" onclick="return confirm(\'Are you sure want to delete selector '.$SelectorTag.'?\');">Remove</a><input type="hidden" name="selector_'.$count.'"  id="selector_'.$count.'" value="'.$SelectorID.'" />
				</td>
			</tr>';
			$count++;
	}else{	
	foreach( $selectors as $selector )
	{
			$SelectorTag = $selector['SelectorTag'];
			$SelectorID =  $selector['SelectorID'];
			$SelectorFontID = $selector['SelectorFontID'];
			$fontsArr = wfs_font_list($data->project_key,$SelectorFontID,$count);
			$output.='<tr style="height:40px;">
				<td>'.$SelectorTag.'</td>
				<td>'.$fontsArr[0].'</td>
				<td><span class="wfs_selectors" style="font-size:26px;font-family:'.$fontsArr[3].'" id="fontid_'.$count.'">'.$fontsArr[1].'</span></td>
				<td><a href="admin.php?page=wfs_options&func=selector_act&pid='.$_POST['pid'].'&sid='.$SelectorID.'" onclick="return confirm(\'Are you sure want to delete selector '.$SelectorTag.'?\');">Remove</a><input type="hidden" name="selector_'.$count.'"  id="selector_'.$count.'" value="'.$SelectorID.'" />
				</td>
			</tr>';
			$count++;
		}//end of foreach
		}//end of else for is multi condition
	}
	if($count == 1){
	     $output.='<tr style="height:40px;">
            <td colspan="4" align="center">No Selectors available.</td>
        </tr>';
	} 
	$output.='</tbody></table><div class="clear"></div>';/*<input type="submit" value="'._e('Save').'" name="submit" class="button-primary" />';*/
	$totalRecord = $selectorsArray['Selectors']['TotalRecords'];
	$pageStart = $selectorsArray['Selectors']['PageStart'];
	$pageLimit = $selectorsArray['Selectors']['PageLimit'];
	if($totalRecord!="" && $pageLimit!="" && $count!=1){
		$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,'selectors_list','selector_pagination_div',"wfs_selector_action_pagination");
		$pagination =$wfs_pg->getPagination();	
		}
	$status = true;
	}else{
	$status = true;
		}
	}else{
		$status = false;
		}
echo json_encode(array('data'=>$output,'status'=>$status,'errMsg'=>$message,'pagination'=>$pagination));

// display erros
wfs_error_log($jsonUrl);

exit;
}
/*
* Listing the selectors for ajax call for pagination
*/
function wfs_selector_list_pagination(){
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	global $wp_wfs_configure_table;
	global $wpdb;
	$output = "";
	$data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wp_wfs_configure_table." WHERE wfs_configure_id = %d ",$_POST['pid']));
	$pageStart = (!empty($_POST['pageStart']))?$_POST['pageStart']:0;
	
	//fetchin json data from fonts
	$apiurl = "json/Selectors/?wfspstart=".$pageStart."&wfsplimit=".SELECTOR_LIMIT."&wfspid=".$data->project_key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$jsonUrl = $wfs_api->wfs_getInfo_post();
	//create json instance
	if($jsonUrl !=""){
	//Creating array from json data
	$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
	$selectorsArray = $json->decode($jsonUrl);
	
	$message =  $selectorsArray['Selectors']['Message'];
	$count = 1;
	
	if($message == "Success"){
	//fetching XML data
	$selectors = $selectorsArray['Selectors']['Selector'];
	$output.= '<table cellspacing="0" cellpadding="0" border="0" class="widefat" style="margin-top:20px;" ><tbody>';
	if(!empty($selectors)){
	$is_multi = is_multi($selectors);
	if($is_multi == 1){
			$SelectorTag = $selectors['SelectorTag'];
			$SelectorID = $selectors['SelectorID'];
			$SelectorFontID = $selectors['SelectorFontID'];
			$fontsArr = wfs_font_list($data->project_key,$SelectorFontID,$count);
			$output.='<tr style="height:40px;">
				<td>'.$SelectorTag.'</td>
				<td>'.$fontsArr[0].'</td>
				<td><span class="wfs_selectors" style="font-size:26px;font-family:'.$fontsArr[3].'" id="fontid_'.$count.'">'.$fontsArr[1].'</span></td>
				<td><a href="admin.php?page=wfs_options&func=selector_act&pid='.$_POST['pid'].'&sid='.$SelectorID.'" onclick="return confirm(\'Are you sure want to delete selector '.$SelectorTag.'?\');">Remove</a><input type="hidden" name="selector_'.$count.'"  id="selector_'.$count.'" value="'.$SelectorID.'" />
				</td>
			</tr>';
			$count++;
		}else{	
		foreach( $selectors as $selector )
			{
			$SelectorTag = $selector['SelectorTag'];
			$SelectorID = $selector['SelectorID'];
			$SelectorFontID = $selector['SelectorFontID'];
			$fontsArr = wfs_font_list($data->project_key,$SelectorFontID,$count);
			$output.='<tr style="height:40px;">
				<td>'.$SelectorTag.'</td>
				<td>'.$fontsArr[0].'</td>
				<td><span class="wfs_selectors" style="font-size:26px;font-family:'.$fontsArr[3].'" id="fontid_'.$count.'">'.$fontsArr[1].'</span></td>
				<td><a href="admin.php?page=wfs_options&func=selector_act&pid='.$_POST['pid'].'&sid='.$SelectorID.'" onclick="return confirm(\'Are you sure want to delete selector '.$SelectorTag.'?\');">Remove</a><input type="hidden" name="selector_'.$count.'"  id="selector_'.$count.'" value="'.$SelectorID.'" />
				</td>
			</tr>';
			$count++;
			} //end of foreach
		}//end of else for is multi condition
	}//end of if condtion or empty selectors
	if($count == 1){
	     $output.='<tr style="height:40px;">
            <td colspan="4" align="center">No Selectors available.</td>
        </tr>';
	} 
	$output.='</tbody></table><div class="clear"></div>';/*<input type="submit" value="'._e('Save').'" name="submit" class="button-primary" />';*/
	$status = true;
	$pageLimit =$_POST['pageLimit'];
	$totalRecord = $_POST['totalRecords'];
	$contentDiv = $_POST['contentDiv'];
	$paginationDiv = $_POST['paginationDiv'];
	if($pageLimit != "" && $totalRecord != "" && $count!=1){
		$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,'selectors_list','selector_pagination_div',"wfs_selector_action_pagination");
		$pagination =$wfs_pg->getPagination();	
		}	
	}else{
	$status = false;
		}
	} else{ $status = false; }
echo json_encode(array('data'=>$output,'status'=>$status,'errMsg'=>$message,'pagination'=>$pagination));

// display erros
wfs_error_log($jsonUrl);

exit;
}
/*
* Listing the domain for ajax call
*/
function wfs_domain_list(){
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	global $wp_wfs_configure_table;
	global $wpdb;
	$output = "";
	$pid = $_POST['pid'];
	$data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wp_wfs_configure_table." WHERE wfs_configure_id = %d ",$pid));
	$pageStart = 0;
	
	//fetchin json data from fonts
	$apiurl = "json/Domains/?wfspstart=".$pageStart."&wfsplimit=".DOMAIN_LIMIT."&wfspid=".$data->project_key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$jsonUrl = $wfs_api->addDomain($_POST['domainname']);
	//create json instance
	$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
	$domainsArray = $json->decode($jsonUrl);
							
	//fetching Array data
	$count = 1;
	$message = $domainsArray['Domains']['Message'];
	if(strtolower($message)=="success"){
		//fetching json data
		$domains = $domainsArray['Domains']['Domain'];
		$output.= '<table cellspacing="0" cellpadding="0" border="0" class="widefat" style="margin-top:20px;" ><tbody>';
		if(!empty($domains)){
			$is_multi = is_multi($domains);
			if($is_multi == 1){
					$domainName = $domains['DomainName'];
					$domainID = $domains['DomainID'];
					$output.='<tr style="height:40px;">
						<td><a href="http://'.$domainName.'" target="_blank">'.$domainName.'</a></td>
						<td><a href="admin.php?page=wfs_options&func=domain_act&pid='.$pid.'&did='.$domainID.'&dname='.$domainName.'&mode=edit"  >Edit</a>&nbsp;|&nbsp;<a href="admin.php?page=wfs_options&func=domain_act&pid='.$pid.'&did='.$domainID.'" onclick="return confirm(\'Are you sure want to delete selector '.$domainName.'?\');" >Remove</a></td>				
					</tr>';
					$count++;
				}else{
				foreach( $domains as $domain )
				{
					$domainName = $domain['DomainName'];
					$domainID = $domain['DomainID'];
					$output.='<tr style="height:40px;">
						<td><a href="http://'.$domainName.'" target="_blank">'.$domainName.'</a></td>
						<td><a href="admin.php?page=wfs_options&func=domain_act&pid='.$pid.'&did='.$domainID.'&dname='.$domainName.'&mode=edit"  >Edit</a>&nbsp;|&nbsp;<a href="admin.php?page=wfs_options&func=domain_act&pid='.$pid.'&did='.$domainID.'" onclick="return confirm(\'Are you sure want to delete selector '.$domainName.'?\');" >Remove</a></td>				
					</tr>';
					$count++;
				} //end of foreach
			}// end of else for is_multi
		}//end of if condition for empty domain
	if($count == 1){
	     $output.='<tr style="height:40px;">
            <td colspan="4" align="center">No domain available.</td>
        </tr>';
	} 
	$output.='</tbody></table><div class="clear"></div>';/*<input type="submit" value="'._e('Save').'" name="submit" class="button-primary" />';*/
	$status = true;
	 
	//Defining the pagination variable from json file
	$totalRecord =$domainsArray['Domains']['TotalRecords'];
	$pageStart =$domainsArray['Domains']['PageStart'];
	$pageLimit =$domainsArray['Domains']['PageLimit'];;

	$contentDiv = $_POST['contentDiv'];
	$paginationDiv = $_POST['paginationDiv'];
	if($totalRecord!="" && $pageLimit!="" && $count!=1){
		$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,$contentDiv,$paginationDiv,"wfs_domain_action_pagination");
		$pagination = $wfs_pg->getPagination();	
		}
	}//end of if condition for success
	else{
		$status = false;
	}
 
echo json_encode(array('data'=>$output,'status'=>$status,'errMsg'=>$message,'pagination'=>$pagination));

// display erros
wfs_error_log($jsonUrl);

exit;
}
/*
* Listing the domain for ajax call for pagination
*/
function wfs_domain_list_pagination(){
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	global $wp_wfs_configure_table;
	global $wpdb;
	$output = "";
	$pid = $_POST['pid'];
	$data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wp_wfs_configure_table." WHERE wfs_configure_id = %d ",$pid));
	$pageStart = (!empty($_POST['pageStart']))?$_POST['pageStart']:0;
	//fetchin json data from fonts
	$apiurl = "json/Domains/?wfspstart=".$pageStart."&wfsplimit=".DOMAIN_LIMIT."&wfspid=".$data->project_key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$jsonUrl = $wfs_api->wfs_getInfo_post();
	//create json instance
	//Creating array from json data
	$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
	$domainsArray = $json->decode($jsonUrl);
	//fetching XML data
	$count = 1;
	$message = $domainsArray['Domains']['Message'];
	if(strtolower($message)=="success"){
		//fetching XML data
		$domains = $domainsArray['Domains']['Domain'];
		$output.= '<table cellspacing="0" cellpadding="0" border="0" class="widefat" style="margin-top:20px;" ><tbody>';
		if(!empty($domains)){
			$is_multi = is_multi($domains);
			if($is_multi == 1){
					$domainName =  $domains['DomainName'];
					$domainID =  $domains['DomainID'];
					$output.='<tr style="height:40px;">
						<td><a href="http://'.$domainName.'" target="_blank">'.$domainName.'</a></td>
						<td><a href="admin.php?page=wfs_options&func=domain_act&pid='.$pid.'&did='.$domainID.'&dname='.$domainName.'&mode=edit"  >Edit</a>&nbsp;|&nbsp;<a href="admin.php?page=wfs_options&func=domain_act&pid='.$pid.'&did='.$domainID.'" onclick="return confirm(\'Are you sure want to delete selector '.$domainName.'?\');" >Remove</a></td>				
					</tr>';
					$count++;

				}else{
				foreach( $domains as $domain )
				{
					$domainName =  $domain['DomainName'];
					$domainID =  $domain['DomainID'];
					$output.='<tr style="height:40px;">
						<td><a href="http://'.$domainName.'" target="_blank">'.$domainName.'</a></td>
						<td><a href="admin.php?page=wfs_options&func=domain_act&pid='.$pid.'&did='.$domainID.'&dname='.$domainName.'&mode=edit"  >Edit</a>&nbsp;|&nbsp;<a href="admin.php?page=wfs_options&func=domain_act&pid='.$pid.'&did='.$domainID.'" onclick="return confirm(\'Are you sure want to delete selector '.$domainName.'?\');" >Remove</a></td>				
					</tr>';
					$count++;
		} //end of forach
			}//end of else for is_multi
		}//end of empty domains
	if($count == 1){
	     $output.='<tr style="height:40px;">
            <td colspan="4" align="center">No domain available.</td>
        </tr>';
	} 
	$output.='</tbody></table><div class="clear"></div>';/*<input type="submit" value="'._e('Save').'" name="submit" class="button-primary" />';*/
	$status = true;
			
	
	$pageLimit =$_POST['pageLimit'];
	$totalRecord = $_POST['totalRecords'];
	$contentDiv = $_POST['contentDiv'];
	$paginationDiv = $_POST['paginationDiv'];
	if($totalRecord!="" && $pageLimit!=""){
		$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,$contentDiv,$paginationDiv,"wfs_domain_action_pagination");
		$pagination = $wfs_pg->getPagination();
	}
	}else{
	$status = false;
	
		}
 
echo json_encode(array('data'=>$output,'status'=>$status,'errMsg'=>$message,'pagination'=>$pagination));

// display erros
wfs_error_log($jsonUrl);

exit;
}
/*
** fetch the font list drop down in selectors tab
*/
function wfs_font_list($project_key,$defaultFont="null",$count){ 
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	$result = array();
	$options ='<select id="fonts-list@'.$count.'" class="fonts-list" name="font_list[]">';  
	$options.= '<option value="-1" >- - - - - Please select a font- - - - --</option>';  
	// json feed file/URL 
	//fetchin json data from fonts
	$apiurl = "json/Fonts/?wfspid=".$project_key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$jsonUrl = $wfs_api->wfs_getInfo_post();
	//create json instance
	//Creating array from json data
	$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
	$fontsArray = $json->decode($jsonUrl);
	//fetching json data  
	$fonts = $fontsArray['Fonts']['Font'];
	if(!empty($fonts)){
	$is_multi = is_multi($fonts);
	if($is_multi == 1){
		$FontName =  $fonts['FontName']; 
		$FontCSSName =  $fonts['FontCSSName'];
		$FontID =  $fonts['FontID']; 
		$FontPreviewTextLong =  $fonts['FontPreviewTextLong'];
		$selected =($defaultFont == $FontID)?"Selected":"";
			if($defaultFont == $FontID){
				$fontCssName=$FontCSSName;
				$fontPreviewTextLong = $FontPreviewTextLong;
			}
		$options.= '<option value="'.$FontCSSName.'@!'.$FontPreviewTextLong.'@!'.$FontID.'" '.$selected.' >'.$FontName.'</option>'; 	
	}else{	
		foreach( $fonts as $font )
		{
		$FontName =  $font['FontName']; 
		$FontCSSName = $font['FontCSSName'];
		$FontID =  $font['FontID']; 
		$FontPreviewTextLong =  $font['FontPreviewTextLong'];
		$selected =($defaultFont == $FontID)?"Selected":"";
			if($defaultFont == $FontID){
				$fontCssName=$FontCSSName;
				$fontPreviewTextLong = $FontPreviewTextLong;
			}
		$options.= '<option value="'.$FontCSSName.'@!'.$FontPreviewTextLong.'@!'.$FontID.'" '.$selected.' >'.$FontName.'</option>'; 
		}
	}//end of else for is multi.
	}//end of if conditon for empyt fonts
$options.= '</select>';	
array_push($result,$options);
array_push($result,$fontPreviewTextLong);
array_push($result,$FontName);
array_push($result,$fontCssName);

// display erros
wfs_error_log($jsonUrl);

return $result;
}
/*
*Fetch all the fonts given a project key from ajax call
@pid: string
*/
function wfs_font_list_pagination(){
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	global $wpdb;
	global $wp_wfs_configure_table;
	global $wfs_editor_fonts;
	
	
	$pageStart = (!empty($_POST['pageStart']))?$_POST['pageStart']:0;
	$pid = $_POST['pid'];
	$data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wp_wfs_configure_table." WHERE wfs_configure_id = %d ",$pid));
	
	//fetchin json data from fonts
	$apiurl = "json/Fonts/?wfspstart=".$pageStart."&wfsplimit=".FONT_LIMIT."&wfspid=".$data->project_key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$jsonUrl = $wfs_api->wfs_getInfo_post();
	//create json instance
	if($jsonUrl!=""){	
	//Creating array from json data
	$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
	$fontsArray = $json->decode($jsonUrl);
	$fonts = $fontsArray['Fonts']['Font'];
	$webfonts=array();
	$Message= $fontsArray['Fonts']['Message'];
	if($Message == "Success"){
	$is_multi = is_multi($fonts);
	if($is_multi == 1){
		$webfonts['fontid'][]= $fonts['FontID'];
		$webfonts['FontName'][]= $fonts['FontName'];
		$webfonts['FontPreviewTextLong'][]= $fonts['FontPreviewTextLong'];
		$webfonts['FontFondryName'][]=$fonts['FontFondryName'];
		$webfonts['FontCSSName'][]= $fonts['FontCSSName'];
		$webfonts['FontLanguage'][]= $fonts['FontLanguage'];
		$webfonts['FontSize'][]= $fonts['FontSize'];
		$webfonts['EnableSubsetting'][]= $fonts['EnableSubsetting']; 
	}else{
		foreach($fonts as $font){
			$webfonts['fontid'][]= $font['FontID'];
			
			$webfonts['FontName'][]= $font['FontName'];
			
			$webfonts['FontPreviewTextLong'][]= $font['FontPreviewTextLong'];
			
			$webfonts['FontFondryName'][]= $font['FontFondryName'];
			
			$webfonts['FontCSSName'][]= $font['FontCSSName'];
			
			$webfonts['FontLanguage'][]= $font['FontLanguage'];
			
			$webfonts['FontSize'][]= $font['FontSize'];
			
			$webfonts['EnableSubsetting'][]= $font['EnableSubsetting'];
		}
	}
	$output="";
	$output .= '<tr style="text-align:left;">
                            	<th style="padding:8px;" rowspan="2">Preview</th><th colspan="2">Online WYSIWYG editor</th>                                
                            </tr>
                            <tr>
                            	<th style="width:100px;text-align:left">Non-Admin</th><th style="width:100px;;text-align:left">Admin</th>
                            </tr>';
	
	$result = $wpdb->get_results("SELECT * FROM `".$wfs_editor_fonts."` WHERE  pid = '$pid' and is_active = '1'");
							 
	$editorFontNameArr = array();
	$editorFontNameArrStatus = array();
	for($k=0;$k<count($result);$k++){
		$editorFontNameArr[] = $result[$k]->tinymce_name;
		$editorFontNameArrStatus[] = $result[$k]->is_admin;
	}  
							
	for($i=0;$i< count($webfonts["FontName"]);$i++){
		$checkedFront = "";
		$checkedBack  = "";
		if(in_array($webfonts["FontName"][$i].'='.$webfonts["FontCSSName"][$i].';',$editorFontNameArr))
			{
				$keyFontData =  array_search($webfonts["FontName"][$i].'='.$webfonts["FontCSSName"][$i].';',$editorFontNameArr);
				if($editorFontNameArrStatus[$keyFontData] == 2)
					{
						$checkedFront = 'checked = "checked"';
						$checkedBack = 'checked = "checked"';
					}
				else if($editorFontNameArrStatus[$keyFontData] == 1){
					$checkedFront = 'checked = "checked"';
					}
				else if($editorFontNameArrStatus[$keyFontData] == 0){
					$checkedBack = 'checked = "checked"';
					}
			}
							
		if(($i%2)==0){$class = "even";}else{$class = "odd";}
								$output.= '<tr class="font_sep '.$class.'">
								<td style="padding:8px;">
								<div class="font_img" style="font-family:\''.$webfonts["FontCSSName"][$i].'\' !important;font-size:30px;">'.$webfonts["FontPreviewTextLong"][$i].'</div>
								<div class="fontnames"><u>'.$webfonts["FontName"][$i].'</u> | <u>'.$webfonts["FontFondryName"][$i].'</u>
								| <u>'.$webfonts["FontLanguage"][$i].'</u>
								'.$webfonts["FontSize"][$i].'
								</div>
								</td>
								<td style=" width:120px;"><input type="checkbox" name="frontend['.$i.']" id="frontend['.$i.']" value="1" '.$checkedFront.' /></td>
									<td style="width:120px;"><input type="checkbox" name="backend['.$i.']" id="backend['.$i.']" value="1" '.$checkedBack.'/>
									<input  type="hidden"  name="fontlist['.$i.']" value="'.$webfonts["FontName"][$i].'--'.$webfonts["FontCSSName"][$i].';">
								</td>
								</tr>';
	}
		$pageLimit =$_POST['pageLimit'];
		$totalRecord = $_POST['totalRecords'];
		$contentDiv = $_POST['contentDiv'];
		$paginationDiv = $_POST['paginationDiv'];
		if($pageLimit!="" && $totalRecord!="" && count($webfonts["FontName"])!=0){
			$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,$contentDiv,$paginationDiv,"wfs_font_action");
			$pagination = $wfs_pg->getPagination();
		}
		 $status = true;
	}else{
		$status = true;
		}
	}else{
		$status = true;
		}
		
echo  json_encode(array('status' => $status, 'data' => $output,'pagination'=>$pagination));

// display erros
wfs_error_log($jsonUrl);

exit;
}
/*
* Adding javascript to the wordpress front end
*/
function wfs_front_head(){
	global $wfs_userid;
		global $wfs_public_key;
		global $wfs_private_key;
		global $wfs_usertype;
		global $wpdb;
		global $wfs_editor_fonts;
	$project_array = wfs_get_key();
	$key = $project_array[0];	
	if(!(wfs_visibility_checking($project_array[2]) xor $project_array[1]) ){
	if($project_array[3] == 1){
		echo '<link rel="stylesheet" href="'.FFCSSURL.$key.'.css" type="text/css" />\n';
	}else{
		$script = '<script type="text/javascript" src="'.FFJSAPIURI.$key.'.js"></script>';
		}
	if(is_single()){
	if($project_array[4]==1 && $project_array[5]==0){
		
		
		
		
		/***** Changes for: Option to show system fonts in editor - By PRABESH *****/
		$resultdata = $wpdb->get_results($wpdb->prepare(
	  "SELECT tinymce_name,ckeditor_name FROM `".$wfs_editor_fonts."` where  `pid` = '%d' and is_active = '1' and (is_admin='1' OR is_admin='2')",
	  $project_array[7]), ARRAY_A);
			if($resultdata){
				$fontsListTM="";
				foreach($resultdata as $fontdata)
				{
					$fontsListTM.= $fontdata['tinymce_name']; 
				}
			}
		
			/**** End ****
	
		
		/***** Changes for: Option to show system fonts in editor - By Keshant  *****/		
		$default_font = "";
		
		if($project_array[6]==2 || $project_array[6]==3) {
			$default_font = "Andale Mono=andale mono,times;".
			"Arial=arial,helvetica,sans-serif;".
			"Arial Black=arial black,avant garde;".
			"Book Antiqua=book antiqua,palatino;".
			"Comic Sans MS=comic sans ms,sans-serif;".
			"Courier New=courier new,courier;".
			"Georgia=georgia,palatino;".
			"Helvetica=helvetica;".
			"Impact=impact,chicago;".
			"Symbol=symbol;".
			"Tahoma=tahoma,arial,helvetica,sans-serif;".
			"Terminal=terminal,monaco;".
			"Times New Roman=times new roman,times;".
			"Trebuchet MS=trebuchet ms,geneva;".
			"Verdana=verdana,geneva;".
			"Webdings=webdings;".
			"Wingdings=wingdings,zapf dingbats";
		}
		/***** End *****/
		
		//change the source if you have tinymce js in different folder other than mention below.
		echo '<script type="text/javascript" src="'.WP_PLUGIN_URL . '/'.FOLDER_NAME.'/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>';
		echo '<script type="text/javascript">
		tinyMCE.init({
		mode : "textareas",
		theme : "advanced",
		theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
		theme_advanced_fonts : \''.$fontsListTM.$default_font.'\',
		content_css:	"' . WP_PLUGIN_URL . '/'.FOLDER_NAME.'/font.php"
	});
		</script>';
		
		echo '<style>.mceEditor table, .mceEditor table tr td { margin: 0 !important; padding: 0 !important; width: auto !important; }</style>';	
	 }
	 if($project_array[4]==1 && $project_array[5]==1){
		global $keyTM;
		
		//fetchin json data from fonts
		/*$apiurl = "json/Fonts/?wfspid=".$key;
		$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
		$jsonUrl = $wfs_api->wfs_getInfo_post();
		//create json instance
		
		$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		$fontsArray = $json->decode($jsonUrl);
		
		$fonts = $fontsArray['Fonts']['Font'];
		$fontsList="";
		if(!empty($fonts)){
			$is_multi = is_multi($fonts);
			if($is_multi == 1){
				$FontName= $fonts['FontName'];
				$FontCSSName= $fonts['FontCSSName'];
				$fontsList.= $FontName."/".$FontCSSName.";";
				}
			else{
				foreach($fonts as $font){
							$FontName= $font['FontName'];
							$FontCSSName= $font['FontCSSName'];
							$fontsList.= $FontName."/".$FontCSSName.";";
							}
				}
		}*/
			/***** Changes for: Option to show system fonts in editor - By PRABESH *****/
		$resultdata = $wpdb->get_results($wpdb->prepare(
	  "SELECT tinymce_name,ckeditor_name FROM `".$wfs_editor_fonts."` where  `pid` = '%d' and is_active = '1' and (is_admin='1' OR is_admin='2')",
	  $project_array[7]), ARRAY_A);
			if($resultdata){
				$fontsList="";
				foreach($resultdata as $fontdata)
				{
					$fontsList.= $fontdata['ckeditor_name']; 
				}
			}
		
			/**** End ****
		
		/***** Changes for: Option to show system fonts in editor - By Keshant  *****/	
		$default_font_CK = "";
		
		if($project_array[6]==2 || $project_array[6]==3) {
			$default_font_CK = "Andale Mono/andale mono,times;".
			"Arial/arial,helvetica,sans-serif;".
			"Arial Black/arial black,avant garde;".
			"Book Antiqua/book antiqua,palatino;".
			"Comic Sans MS/comic sans ms,sans-serif;".
			"Courier New/courier new,courier;".
			"Georgia/georgia,palatino;".
			"Helvetica/helvetica;".
			"Impact/impact,chicago;".
			"Symbol/symbol;".
			"Tahoma/tahoma,arial,helvetica,sans-serif;".
			"Terminal/terminal,monaco;".
			"Times New Roman/times new roman,times;".
			"Trebuchet MS/trebuchet ms,geneva;".
			"Verdana/verdana,geneva;";
		}
		/***** End *****/
		
		echo '<script type="text/javascript" src="'.WP_PLUGIN_URL . '/'.FOLDER_NAME.'/ckeditor/ckeditor.js"></script>';
		define('SITECOOKIEPATH', preg_replace('|https?://[^/]+|i', '', get_option('siteurl') . '/' ) );
		
		/***** Changes for: Option to show system fonts in editor - By Keshant  *****/
		echo '<script type="text/javascript">var wfs_info ={"ckfonts" : "'.$fontsList.$default_font_CK.'" };var userSettings = {
			\'url\': \''.SITECOOKIEPATH.'\'} </script>';
		/***** End *****/
		
		echo "<script type='text/javascript' src='".SITECOOKIEPATH."/wp-admin/load-scripts.php?c=1&amp;load=jquery,utils'></script>";
		
		echo '<script type="text/javascript" src="'.WP_PLUGIN_URL . '/'.FOLDER_NAME.'/js/ckeditortwp.js"></script>';
	 }
	}
	echo $script;
	}
	
	// display erros
	wfs_error_log($jsonUrl);
}
function wfs_editor_head(){
	$project_array = wfs_get_key();
	$key = $project_array[0];	
	if(!(wfs_visibility_checking($project_array[2]) xor $project_array[1]) ){
		 if (is_admin()) {
        switch (basename($_SERVER['SCRIPT_FILENAME'])) {
            case "post.php":
            case "post-new.php":
            case "page.php":
            case "page-new":
            case "comment.php":
				if($project_array[3] == 1){
						echo '<link rel="stylesheet" href="'.FFCSSHDLRURI.$key.'" type="text/css" />';	
				}else{
						$script = '<script type="text/javascript" src="'.FFJSAPIURI.$key.'.js"></script>';
				}
				echo $script;
                break;
            default:
                return;
        }
	}
	}
}
/*
* Adding javascript to WP backend
*/ 
function wfs_admin_head() { 
	wp_enqueue_script('wfscookie',WP_PLUGIN_URL . '/'.FOLDER_NAME.'/js/jquery_cookie.js','','1.0');
	wp_enqueue_script('webfontsScript',WP_PLUGIN_URL . '/'.FOLDER_NAME.'/js/webfonts.js','','1.0');
	$project_array = wfs_get_key('admin');
	//wp_enqueue_script('wfs_js',FFJSAPIURI.$project_array[0].'.js');
	echo '<script type="text/javascript" src="'.FFJSAPIURI.$project_array[0].'.js"></script>';
	echo "<link rel='stylesheet' href='".WP_PLUGIN_URL ."/".FOLDER_NAME."/css/webfonts.css' type='text/css' />\n";
	
}
function wfs_ckeditor_head(){
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	global $wpdb;
		global $wfs_editor_fonts;
	if(!empty($_GET['pid'])){
	$project_array = wfs_get_key('admin');
	}
	else{
		$project_array = wfs_get_key();
		}
	$key = $project_array[0];
	if($_GET['page'] == "wfs_options"){
	//fetchin json data from fonts
	$apiurl = "json/Fonts/?wfspid=".$key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$jsonUrl = $wfs_api->wfs_getInfo_post();
	//create json instance
	$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
	$fontsArray = $json->decode($jsonUrl);
	$fonts = $fontsArray['Fonts']['Font'];
	$fontsList="";
	if(!empty($fonts)){
		$is_multi = is_multi($fonts);
		if($is_multi == 1){
			$FontName= $fonts['FontName'];
			$FontCSSName= $fonts['FontCSSName'];
			$fontsList.= $FontName."/".$FontCSSName.";";
		}
		else{
			foreach($fonts as $font){
				$FontName= $font['FontName'];
				$FontCSSName= $font['FontCSSName'];
				$fontsList.= $FontName."/".$FontCSSName.";";
			}
		}
	}
}else{
		/***** Changes for: Option to show system fonts in editor - By PRABESH *****/
		$resultdata = $wpdb->get_results($wpdb->prepare(
	  "SELECT tinymce_name,ckeditor_name FROM `".$wfs_editor_fonts."` where  `pid` = '%d' and is_active = '1' and (is_admin='0' OR is_admin='2')",
	  $project_array[7]), ARRAY_A);
			if($resultdata){
				$fontsList="";
				foreach($resultdata as $fontdata)
				{
					$fontsList.= $fontdata['ckeditor_name']; 
				}
			}
	}
	/***** Changes for: Option to show system fonts in editor - By Keshant  *****/
	$default_font_CK = "";
	
	if($project_array[6] == 1 || $project_array[6] == 3) {
		$default_font_CK = "Andale Mono/andale mono,times;".
			"Arial/arial,helvetica,sans-serif;".
			"Arial Black/arial black,avant garde;".
			"Book Antiqua/book antiqua,palatino;".
			"Comic Sans MS/comic sans ms,sans-serif;".
			"Courier New/courier new,courier;".
			"Georgia/georgia,palatino;".
			"Helvetica/helvetica;".
			"Impact/impact,chicago;".
			"Symbol/symbol;".
			"Tahoma/tahoma,arial,helvetica,sans-serif;".
			"Terminal/terminal,monaco;".
			"Times New Roman/times new roman,times;".
			"Trebuchet MS/trebuchet ms,geneva;".
			"Verdana/verdana,geneva;";
	}
	/**** End ****/
	
	wp_enqueue_script('ckeditorScriptwp',WP_PLUGIN_URL . '/'.FOLDER_NAME.'/ckeditor/ckeditor.js','','1.0');
	
	/***** Changes for: Option to show system fonts in editor - By Keshant  *****/
	echo '<script type="text/javascript">var wfs_info ={"ckfonts" : "'.$fontsList.$default_font_CK.'" }</script>';
	/**** End ****/
	
	wp_enqueue_script('ckeditortwp',WP_PLUGIN_URL . '/'.FOLDER_NAME.'/js/ckeditortwp.js','','1.0',true);
	// display erros
	wfs_error_log($jsonUrl);
	}
/*
* Checking the page condition
*/
function wfs_visibility_checking($pages){
	$pageArrDb = array();
    $pageArrDb = explode(',',$pages);
	$retval = false;
			if((in_array('1', $pageArrDb)) && is_front_page()) { 
				$retval = true;
			}

			if(in_array('2', $pageArrDb) && is_home()) { 
				$retval = true;
				
			}

			if(in_array('3', $pageArrDb) && is_page() ) { 
				$retval = true;
			}

			if(in_array('4', $pageArrDb) && is_single() ) { 
				$retval = true;
			}

			if(in_array('5', $pageArrDb) && is_archive() ) { 
				$retval = true;
			}

			if(in_array('6', $pageArrDb) && is_404() ) { 
				$retval = true;
			}
	
	return $retval;
	}

/*
Generate the key depending upon the condition
*/
function wfs_get_key($section="front"){
	global $wpdb;
	global $wp_wfs_configure_table;
	global $wfs_userid;
	$project = array();
	if($section == "admin"){
		/***** 
		Changes for: Option to show system fonts in editor 
		Changes: show_system_fonts added
		- By Keshant  
		*****/
		$data = $wpdb->get_row( $wpdb->prepare("SELECT project_key,project_page_option,project_options,project_pages,project_day,wysiwyg_enabled,editor_select,show_system_fonts,wfs_configure_id FROM ".$wp_wfs_configure_table." WHERE `user_id` = %d and wfs_configure_id = %d", $wfs_userid, $_GET['pid']));	
			$project[]=$data->project_key;
			$project[]=$data->project_page_option;
			$project[]=$data->project_pages;
			$project[]=$data->project_options;
			$project[]=$data->wysiwyg_enabled;
			$project[]=$data->editor_select;
			$project[]=$data->show_system_fonts;
			$project[]=$data->wfs_configure_id;
	
	}else{
		/***** 
		Changes for: Option to show system fonts in editor 
		Changes: show_system_fonts added
		- By Keshant  
		*****/
		$resultArr = $wpdb->get_results( $wpdb->prepare("SELECT project_key,project_page_option,project_options,project_pages,project_day,wysiwyg_enabled,editor_select,show_system_fonts,wfs_configure_id FROM ".$wp_wfs_configure_table." WHERE `is_active` ='1' and `user_id` = %d ORDER BY `updated_date` desc", $wfs_userid));
		foreach($resultArr as $data)
		{
			$dayValue = $data->project_day;
			if(checkday($dayValue)){
				$project[]=$data->project_key;
				$project[]=$data->project_page_option;
				$project[]=$data->project_pages;
				$project[]=$data->project_options;
				$project[]=$data->wysiwyg_enabled;
				$project[]=$data->editor_select;
				$project[]=$data->show_system_fonts;
				$project[]=$data->wfs_configure_id;
				break;
			}
		}
	}
	/*echo '<pre>';
	print_r($resultArr);
	*/
	
	return $project;
	}

//Declare the global variable to connect to database
global $wpdb;
//Give our table a name and use the WP prefix
global $wp_wfs_configure_table;
global $wfs_editor_fonts;
/*User details globalising
* @Userid = $wfs_userid
* @Username = $wfs_username
* @Password = $wfs_password
* @Usertype = $wfs_usertype
*/
global $wfs_userid;
global $wfs_public_key;
global $wfs_private_key;
global $wfs_usertype;
include( dirname(__FILE__) . '/includes/includes.php');
include( dirname(__FILE__) . '/includes/wfs_pagination.php');
include( dirname(__FILE__) . '/includes/json.class.php');
include( dirname(__FILE__) . '/includes/wfsapi.class.php');


$wpdb->show_errors();
register_activation_hook( __FILE__, 'wfs_activate' ); //hook to call the function when it is activated
register_deactivation_hook( __FILE__, 'wfs_deactivate' );
/*
Setting the varible value
*/
$wp_wfs_configure_table = $wpdb->prefix. "wfs_configure"; //Wfs table name
$wfs_editor_fonts = $wpdb->prefix. "wfs_editor_fonts"; //Wfs table name

$wfs_details = getUnPass();

$wfs_userid = $wfs_details['0'];
$wfs_public_key = $wfs_details['1'];
$wfs_private_key = $wfs_details['2'];
$wfs_usertype = $wfs_details['3'];

/*
*End of setting up variable
*/
add_action('admin_menu', 'wfs_menu');
add_action('wp_ajax_wfs_project_action', 'wfs_project_list');
add_action('wp_ajax_wfs_selector_action', 'wfs_selector_list');
add_action('wp_ajax_wfs_selector_action_pagination', 'wfs_selector_list_pagination');
add_action('wp_ajax_wfs_domain_action', 'wfs_domain_list');
add_action('wp_ajax_wfs_font_action', 'wfs_font_list_pagination');
add_action('wp_ajax_wfs_domain_action_pagination', 'wfs_domain_list_pagination');
add_action('wp_head', 'wfs_front_head');
add_action( "admin_print_scripts", 'wfs_editor_head' );
$project_array = wfs_get_key();
global $keyTM;
$keyTM = $project_array[0];			
if(($project_array[4]==1 && $project_array[5]==0 )|| $_GET['page'] == 'wfs_options'){
/*
*Adding fonts to the font family
*/
if ( ! function_exists('wfs_fonts_tinymce') ) {
		
		function wfs_fonts_tinymce($init){
			global $wfs_public_key;
			global $wfs_private_key;
			global $wfs_usertype;
			global $wpdb;
			global $wfs_editor_fonts;
			if(!empty($_GET['pid'])){
			$project_array = wfs_get_key("admin");
			}else{
				$project_array = wfs_get_key();
				}
			$key = $project_array[0];	
			
			if($_GET['page'] == "wfs_options"){
			//fetchin json data from fonts
			$apiurl = "json/Fonts/?wfspid=".$key;
			$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
			$jsonUrl = $wfs_api->wfs_getInfo_post();
			//create json instance
		
			$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
			$fontsArray = $json->decode($jsonUrl);
	
			$fonts = $fontsArray['Fonts']['Font'];
			$fontsListTM="";
			if(!empty($fonts)){
			$is_multi = is_multi($fonts);
			if($is_multi == 1){	
				$FontName= $fonts['FontName'];
				$FontCSSName= $fonts['FontCSSName'];
				$fontsListTM.= $FontName.'='.$FontCSSName.'; '; 
				}
			else{
				foreach($fonts as $font){
					$FontName= $font['FontName'];
					$FontCSSName= $font['FontCSSName'];
					$fontsListTM.= $FontName.'='.$FontCSSName.'; '; 
					}
				}
			}
			}else{
			/***** Changes for: Option to show system fonts in editor - By PRABESH *****/
				$resultdata = $wpdb->get_results($wpdb->prepare(
		  "SELECT tinymce_name,ckeditor_name FROM `".$wfs_editor_fonts."` where  `pid` = '%d' and is_active = '1' and (is_admin='0' OR is_admin='2')",
		  $project_array[7]), ARRAY_A);
				if($resultdata){
					$fontsListTM="";
					foreach($resultdata as $fontdata)
					{
						$fontsListTM.= $fontdata['tinymce_name']; 
					}
				}
			}
			/**** End ****
			/***** Changes for: Option to show system fonts in editor - By Keshant  *****/
			$default_font = "";
			
			if($project_array[6] == 1 || $project_array[6] == 3) {
				$default_font = "Andale Mono=andale mono,times;".
				"Arial=arial,helvetica,sans-serif;".
				"Arial Black=arial black,avant garde;".
				"Book Antiqua=book antiqua,palatino;".
				"Comic Sans MS=comic sans ms,sans-serif;".
				"Courier New=courier new,courier;".
				"Georgia=georgia,palatino;".
				"Helvetica=helvetica;".
				"Impact=impact,chicago;".
				"Symbol=symbol;".
				"Tahoma=tahoma,arial,helvetica,sans-serif;".
				"Terminal=terminal,monaco;".
				"Times New Roman=times new roman,times;".
				"Trebuchet MS=trebuchet ms,geneva;".
				"Verdana=verdana,geneva;".
				"Webdings=webdings;".
				"Wingdings=wingdings,zapf dingbats";
			}
			/**** End ****/
			
			$init['theme_advanced_fonts'] =$fontsListTM.$default_font;
			return $init;
			}
			
			// display erros
			wfs_error_log($jsonUrl);
		}	
		add_filter( 'tiny_mce_before_init', 'wfs_fonts_tinymce' );
		/*
		* Adding fonts face css Tiny Mce Iframe
		*/
		if ( ! function_exists('wfs_css_tinymce') ) {
			function wfs_css_tinymce($wp) {
				$wp .= ',' . WP_PLUGIN_URL . '/'.FOLDER_NAME.'/font.php?pid='.$_GET['pid'];
				return trim($wp, ' ,');
			}
		}
		add_filter( 'mce_css', 'wfs_css_tinymce' );
		/*
		* Adding Font selecting drop down to Tiny Mce
		*/
		
		if ( ! function_exists('wfs_fontfamily_tinymce') ) {
			function wfs_fontfamily_tinymce($init) {
			
			$init['theme_advanced_buttons1'] = 'fontselect,fontsizeselect';
			return $init;
			}
		}
		
		add_filter( 'mce_buttons', 'wfs_fontfamily_tinymce', 999 );
	}
	
if(($project_array[4]==1 && $project_array[5]==1 )|| $_GET['page'] == 'wfs_options'){
	add_action( "admin_print_scripts", 'wfs_ckeditor_head' );
	}
$wpdb->hide_errors(); 