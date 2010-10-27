<?php
/*
Copyright 2010 Monotype Imaging Inc.  
This program is distributed under the terms of the GNU General Public License
*/
/*
* PROJECT LISTING AND IMPORTING FUNCTION
*/
function wfs_project_listing(){
	global $wpdb;
	global $wp_wfs_configure_table;	
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	global $wfs_api_token;

?>
<div class="wrap">
<div class="wfs_web_logo"><a style="text-decoration: none;" target="_blank" href="http://webfonts.fonts.com"><img src="<?php echo WP_PLUGIN_URL . '/'.FOLDER_NAME.'/images/logo.gif'; ?>" /></a></div>

<div class="wfs_right_link">
<a class="home" href="admin.php?page=wfs_options"><?php _e('Home'); ?></a> | <a href="admin.php?page=wfs_options&mode=login"><?php _e('My WFS account'); ?></a>
</div>
<div class="icon32" id="icon-edit"><br/></div>
<h2><?php _e('My Project List'); ?></h2>

<form action="admin.php?page=wfs_options&func=prj_act" method="post">
<div class="tablenav">

<div class="alignleft actions">
Select: <a href="javascript:;" class="selectAll"><?php _e('All'); ?></a>, <a href="javascript:;" class="selectNone"><?php _e('None'); ?></a>

<input type="submit" class="button-secondary action" id="sync" name="submit" value="Sync"/>
</div>
<div id="wfs-actions">
	<div id="wfs-first" >More Action</div>
    <div id="wfs-toggle"><br/></div>
    <div id="wfs-inside" style="width: 126px; display: none;" class="slideUp">
    <div class="wfs-action"><input type="submit" name="submit" value="Activate" class="wfs_action_button" /></div>
    <div class="wfs-action"><input type="submit" name="submit" value="Deactivate" class="wfs_action_button" /></div>
     <div class="wfs-action"><input type="submit"  name="submit" value="Delete" class="wfs_action_button" /></div>

</div></div>
</div>
<table cellspacing="0" cellpadding="0" border="0" class="widefat">
<thead>
     <tr>
        <th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox" class="plist"/></th>
        <th><?php _e('Project Name'); ?></th>
        <th><?php _e('Active'); ?></th>
    </tr>
</thead>
<tfoot>
     <tr>
         <th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox" class="plist"/></th>
        <th><?php _e('Project Name'); ?></th>
        <th><?php _e('Active'); ?></th>
    </tr>
</tfoot>
<tbody>
<?php 

/*$projects_list = $wpdb->get_results("SELECT * FROM ".$wp_wfs_configure_table." where `user_id` = '".$wfs_userid."' order by is_active DESC, updated_date DESC  ", ARRAY_A); */
$projects_list = $wpdb->get_results($wpdb->prepare(
  "SELECT * FROM ".$wp_wfs_configure_table." where `user_id` = %d order by is_active DESC, updated_date DESC",
  $wfs_userid), ARRAY_A);
if($projects_list){
foreach($projects_list as $project)
{
?>
<tr>
     <th class="check-column" scope="row"><input type="checkbox" class="plist" name="project_key[]" value="<?php echo $project['project_key'] ?>" /></th>
     <td><a href="admin.php?page=wfs_options&func=configure&pid=<?php echo $project['wfs_configure_id']; ?>"><?php echo $project['project_name']; ?></a></td>
     <td><img src="<?php echo WP_PLUGIN_URL . '/'.FOLDER_NAME.'/images/'; echo ($project['is_active']==1)?'tick.gif':'cross.gif'; ?>" /></td>
   </tr>
<?php } 
}else{?>
<tr>
      <td colspan="3" align="center"><?php _e('No project available.'); ?></td>
   </tr>
<?php	
}
?>  
</tbody>
</table>
<div class="tablenav">

<div class="alignleft actions">
<?php _e('Select'); ?>: <a href="javascript:;" class="selectAll"><?php _e('All'); ?></a>, <a href="javascript:;" class="selectNone"><?php _e('None'); ?></a>
</div>
</div>

<div id="poststuff">
<div class="meta-box-sortables ui-sortable">
<div class="postbox closed">
	<h3 id="wfs_importProject"  style="cursor:pointer;"><span ><?php _e('Imports Projects'); ?></span><span style="display: block;" class=""></span></h3>
    <div class="inside" >
    	<div id="imp_prj">
    <?php
  
   //Fetching the json data from WFS
	$apiurl = "json/Projects/?wfspstart=0&wfsplimit=".PROJECT_LIMIT;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$jsonUrl = $wfs_api->wfs_getInfo_post();
	//Creating JSON Instance
	if($jsonUrl != ""){	//CHECK IF json FILE EXISTS OR NOT
	//Creating array from json data
	$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
	$projectsArray = $json->decode($jsonUrl);
	$message = $projectsArray['Projects']['Message'];
	$cnt = 1;
	if($message == "Success"){
	//fetching projects array
	$projects = $projectsArray['Projects']['Project'];
	echo '<ul >';
	if(!empty($projects)){
		$is_multi = is_multi($projects);
		if($is_multi == 1){
			$projectName = $projects['ProjectName'];
			$projectKey = $projects['ProjectKey'];
			$webfonts_added_project =   webfonts_project_profile_load($projectKey, "project_key");
				if(empty($webfonts_added_project->project_key)){
				echo '<li><label class="selectit" for="'.$projectKey.'">
						   <input id="'.$projectKey.'" type="checkbox"  value="'.$projectKey.'" name="project_import[]" class="import_project"/>&nbsp;&nbsp;'.$projectName.'.
						</label><input  type="hidden"  value="'.$projectName.'" name="project_name['.$projectKey.']"/></li>';
				$cnt++;
				}else{
					echo '<li><label class="selectit" for="'.$projectKey.'">
						   <input id="'.$projectKey.'" type="checkbox" disabled="disabled"  value="'.$projectKey.'" name="project_import[]" class="import_project"/>&nbsp;&nbsp;'.$projectName.' <i style="font-size:10px;color:#21759B;">(Project already added.)</i>
						</label><input  type="hidden"  value="'.$projectName.'" name="project_name['.$projectKey.']"/></li>';
						$cnt++;
					}
		}else{
		foreach( $projects as $project )
			{
			$projectName = $project['ProjectName'];
			$projectKey = $project['ProjectKey'];
			$webfonts_added_project =   webfonts_project_profile_load($projectKey, "project_key");
				if(empty($webfonts_added_project->project_key)){
				echo '<li><label class="selectit" for="'.$projectKey.'">
						   <input id="'.$projectKey.'" type="checkbox"  value="'.$projectKey.'" name="project_import[]" class="import_project"/>&nbsp;&nbsp;'.$projectName.'.
						</label><input  type="hidden"  value="'.$projectName.'" name="project_name['.$projectKey.']"/></li>';
				$cnt++;
				}else{
					echo '<li><label class="selectit" for="'.$projectKey.'">
						   <input id="'.$projectKey.'" type="checkbox" disabled="disabled"  value="'.$projectKey.'" name="project_import[]" class="import_project"/>&nbsp;&nbsp;'.$projectName.' <i style="font-size:10px;color:#21759B;">(Project already added.)</i>
						</label><input  type="hidden"  value="'.$projectName.'" name="project_name['.$projectKey.']"/></li>';
						$cnt++;
					}
				
			}//end of foreach
		}//end of else for is_multi
	}// end of if for empty projects
	if($cnt == 1){
		echo '<li> No Projects Available.</li>';
		}
	echo '</ul>';
	//Getting the data for pagintaion from array
		$totalRecord =$projectsArray['Projects']['TotalRecords'];
		$pageStart =$projectsArray['Projects']['PageStart'];
		$pageLimit =$projectsArray['Projects']['PageLimit'];
	} //End of if for success message
    ?>
   </div>
   <input type="hidden" id="prj_page_start" value="<?php echo $pageStart?>" /><input type="hidden" id="prj_page_limit" value="<?php echo $pageLimit?>" /><input type="hidden" id="prj_total_record" value="<?php echo $totalRecord?>" />
   <div class="pagination_div" id="project_pagination_div"><?php 
   if($totalRecord !="" && $pageLimit!="" && $cnt != 1){
		$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,'imp_prj','project_pagination_div',"wfs_project_action");
		echo $wfs_pg->getPagination();}?></div>
   <?php  //end of if condition for blank total record.. 
	} //END OF IF CONDITION FOR jsonfile CHECKING  ?>
	<div>
   		<input class="button-secondary" type="button" id="refresh_project" name="refresh_project" value="<?php _e('Refresh'); ?>"/>
         <input class="button-secondary" type="submit" id="add_project" name="submit" value="<?php _e('Import Project'); ?>"    <?php if($cnt == 0){ echo 'disabled="disabled"';}?>/> 
	</div>
    </div>
</div>
</div>
</div>
</form>
</div>
<?php } 
/* 
* Login Form for authentication
*/
function wfs_login_form(){
global $wfs_userid;
global $wfs_public_key;
global $wfs_private_key;
global $wfs_usertype;
?>
   
<div class="wrap">
    <div class="wfs_web_logo"><a style="text-decoration: none;" target="_blank" href="http://webfonts.fonts.com"><img src="<?php echo WP_PLUGIN_URL . '/'.FOLDER_NAME.'/images/logo.gif'; ?>" /></a></div> 
     <?php if ( !empty($_SESSION['wfs_message']) ) echo '<div id="message" class="error fade"><p><strong>'.$_SESSION['wfs_message'].'.</strong></p></div>';unset($_SESSION['wfs_message']); ?>
    <div class="wfs_right_link">
  <a class="home" href="admin.php?page=wfs_options"><?php _e('Home'); ?></a> | <a href="admin.php?page=wfs_options&mode=login"><?php _e('My WFS Account'); ?></a>
    </div>
    <div class="icon32" id="icon-users"><br/></div>
    <h2><?php _e('My Account'); ?></h2>
    <form method="post" name="frmlogin" id="frmlogin" action="admin.php?page=wfs_options">
    <div class="ui-sortable metabox-holder" >
        <div class="postbox">
        	<div title="Click to toggle" class="handlediv"><br/></div>
        	<h3 class="hndle"><span><?php _e('Web Fonts Info'); ?></span></h3>
        	<div class="inside wfs_margin" >
            	<div class="wfs_info_text wfs_day_row">
<?php _e('You must be the member of'); ?> <a href="<?php echo SIGNUPURI; ?>" target="_blank">webfonts.fonts.com</a><?php _e(' to use the plugin. If you have not registered yet, Please'); ?> <a target="_blank" href="<?php echo SIGNUPURI; ?>"><?php _e('sign up'); ?></a> <?php _e('here'); ?>.</div>
				
               
                 <div class="wfs_row">
                 	<label style="font-weight:bold;width:200px;">Authentication key token  </label>
                 </div>
                
                 <div class="wfs_row" id="token_div" >
                 <?php $api_key = get_option('webfonts_public_key','').'--'.get_option('webfonts_private_key','')?>
                    <input type="text" maxlength="256" name="webfonts_api_token" id="webfonts_api_token" value="<?php echo $api_key;?>" size="145" />
                 </div>
                 <div class="wfs_row" style="margin:10px 0 10px 0;" >
                   <a href="<?php echo SIGNUPURI; ?>" target="_blank" class="button-secondary"><?php _e('Sign Up'); ?></a>&nbsp;&nbsp;<a href="<?php echo GETKEYURI; ?>" target="_blank" class="button-secondary"><?php _e('Get Authentication Token Key'); ?></a>
                    
                </div>
                <div class="clear"></div>	
        	</div>
        </div>
         <input class="button-primary" type="submit" value="<?php _e('Save Configuration'); ?>" />
         <input class="button-secondary" type="reset" id="reset_config" name="reset_config" value="<?php _e('Reset Configuration'); ?>"/>
    </div>
    </form>
</div>

<?php }
/*
* Projects action page
*/
function wfs_project_action(){
	global $wpdb;
	global $wp_wfs_configure_table;
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;

	$case = strtolower($_POST['submit']);
	$project_keys = $_POST['project_key'];
	switch($case)
		{
			case "activate":
			case "deactivate":
				$status = ($case == "activate")?'1':'0';
				if(is_array($project_keys)){
					foreach($project_keys as $project_key){
					$wpdb->update($wp_wfs_configure_table, array('is_active' => $status ), array('project_key' => $project_key ));
					}
					}
				break;
			case "delete";
				if(is_array($project_keys)){
					foreach($project_keys as $project_key){
						$wpdb->query($wpdb->prepare("DELETE FROM ".$wp_wfs_configure_table." WHERE project_key = %s", $project_key));
					}
				}
				break;
			case "sync":
				// load a json file.
				
				//Fetching the json data from WFS
				$apiurl = "json/Projects/?wfspstart=0&wfsplimit=".PROJECT_LIMIT;
				$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
				$jsonUrl = $wfs_api->wfs_getInfo_post();
				//Creating JSON Instance
				//Creating array from json data
				$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
				$projectsArray = $json->decode($jsonUrl);
				//check the message status
				$message = $projectsArray['Projects']['Message'];
				if($message == "Success") {
				//fetching array data
				$projects = $projectsArray['Projects']['Project'];
				foreach( $projects as $project )
				{
					$projectName = $project['ProjectName'];
					$projectKey = $project['ProjectKey'];
				
					//update the projects that are database
					$webfonts_added_project =   webfonts_project_profile_load($projectKey, "project_key");
					if(!empty($webfonts_added_project->project_key))
						{
					$wpdb->update($wp_wfs_configure_table, array('project_name' => $projectName,'project_key' => $projectKey  ), array('wfs_configure_id' => $webfonts_added_project->wfs_configure_id ));
						} 
					}
				}
				//Delete all the projects that are not in the user accounts
				
				$apiurl = "xml/Projects/?wfspstart=0&wfsplimit=".PROJECT_LIMIT;
				$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
				$xmlData = $wfs_api->wfs_getInfo_post();
				
				$rs = $wpdb->get_results("SELECT * FROM ".$wp_wfs_configure_table , ARRAY_A); 
				if ($rs) {
					foreach ($rs as $data ) {
						preg_match("/<ProjectKey>".$data['project_key']."/", $xmlData, $matches);
						if($matches[0]=="")
						{
							$wpdb->query($wpdb->prepare("DELETE FROM ".$wp_wfs_configure_table." WHERE  wfs_configure_id = %d and user_id = %d",$data['wfs_configure_id'], $wfs_userid));
						}
					}
				}
				break;
			case "import project":
				$project_keys = $_POST['project_import'];
				$project_names = $_POST['project_name'];
				if(is_array($project_keys)){
					foreach($project_keys as $key=>$project_key){
						$wpdb->insert($wp_wfs_configure_table, array( 'project_key' => $project_key, 'project_name' => $project_names[$project_key],'user_id'=> $wfs_userid,'user_type'=>$wfs_usertype), array( '%s', '%s', '%d', '%d' ) );
						}
					}
				break;
			default:
				break;
		}
	wp_redirect(get_option('siteurl') . '/wp-admin/admin.php?page=wfs_options');
	}
/*
* Project details configuration page
*/	
function wfs_project_configure(){ 
	global $wpdb;
	global $wp_wfs_configure_table;
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	global $wfs_editor_fonts;
$pid = $_GET['pid']; 
$result = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wp_wfs_configure_table." WHERE wfs_configure_id = %d ",$pid ));
?>
<div class="wrap">
    <div class="wfs_web_logo"><a style="text-decoration: none;" target="_blank" href="http://webfonts.fonts.com"><img src="<?php echo WP_PLUGIN_URL . '/'.FOLDER_NAME.'/images/logo.gif'; ?>" /></a></div>

    <div class="wfs_right_link">
    <a class="home" href="admin.php?page=wfs_options">Home</a> | <a href="admin.php?page=wfs_options&mode=login">My WFS account</a>
    </div>
    <div id="errMsg" class="error" style="display:none;margin:10px;"></div>
    <div id="succMsg" class="updated" style="display:none;margin:10px;"></div>
	<div class="icon32" id="icon-edit"><br/></div>
	<h2>Project Detail for <?php echo $result->project_name; ?></h2>
    
  <ul class="wfs_tabNav">
 	<li class="wfs_current"><a href="#" id="configure">Configure</a></li>
	 <li><a href="#" id="stylesheet">Work on stylesheet</a></li>
 <li><a href="#" id="domain">Domain</a></li>
</ul>  
<div class="clear"></div>
<div class="wfs_tabContainer">
 <!-- COnfigure Tab -->
 
 <div class="wfs_tab wfs_current">
 <form method="post" action="admin.php?page=wfs_options&func=configure_act">
    <div id="poststuff">
    	<div class="meta-box-sortables ui-sortable" >
             <div id="page_display_container" class="postbox">
                 <h3  style="cursor:default;"><span ><?php _e('Page Display Setting'); ?></span></h3>
                 <div class="inside" >
                 	<div class="wfs_day_row wfs_info_text"><?php _e('You can select the pages to display web fonts, please make sure your Web Font Project has the fonts selected and has the corresponding Selectors'); ?></div>
                    <div class="clear"></div>
                    <div class="wfs_visible">
                    <label for="every_page"><input type="radio" id="every_page" name="page_visiblity" <?php echo ($result->project_page_option == 0)?'checked="checked"':''; ?> value="0"/>&nbsp;&nbsp;<?php _e('Show on every page except listed page, leave blank to show on all pages'); ?></label><br />
                    <label for="listed_page"><input type="radio" id="listed_page" name="page_visiblity" value="1"  <?php echo ($result->project_page_option == 1)?'checked="checked"':''; ?>/>&nbsp;&nbsp;<?php _e('Show on only listed pages'); ?></label>
                     </div>
                     <div class="clear"></div>
                     <!--Page listing div -->
                     <div id="page_listing_container" class="postbox closed">
                       <h3 id="wfs_importProject"  style="cursor:pointer;"><span ><?php _e('Page List'); ?></span><span style="display: block;" class=""></span></h3>
                        <div class="inside">
						  <?php 
                          $pageArrDb = array();
                          $pageArrDb = explode(',',$result->project_pages);?>
							<ul> <?php $page_arr = array(
												'1' => 'is_front_page',
												'2' => 'is_home',
												'3' => 'is_page',
												'4' => 'is_single',
												'5' => 'is_archive',
												'6' => 'is_404'
												);
						
						foreach($page_arr as $key=>$pagelist){?>
                                <li >
                                    <label for="<?php echo $pagelist; ?>">
                              <input id="<?php echo $pagelist; ?>" type="checkbox"  value="<?php echo $key; ?>" name="page_list[]"  <?php if(in_array($key,$pageArrDb)){ echo 'checked="checked"';} ?> />&nbsp;&nbsp;<?php echo str_replace('_',' ',$pagelist); ?>
                                    </label>
                                 </li>
                         <?php } ?>        
                            </ul>
                        </div>
                      </div>
                      <!--End of page listing -->
                      <div class="wfs_day_row wfs_info_text">Display for day of week <input type="text" name="days" id="edit-days" size="3" value="<?php if($result->project_day){echo $result->project_day;}else{ echo '0-6';} ?>" />  0 for Sunday, 1 for Monday...and 6 for Saturday, use - for day range {eg: 0-6 for displaying from Sunday to Saturday}, you can also use comma separate days{eg: 1,3,4 for displaying on Monday, Wednesday and Thursday}</div>
                    <div class="clear"></div>
                </div>
            </div>
            <?php if($wfs_usertype == 1){ ?>
            <div id="source_option_container" class="postbox">
                 <h3  style="cursor:default";><span >Source Type</span></h3>
                 <div class="inside" >
                  <span><strong><?php _e('Please select the option to be used'); ?></strong></span>
                     <div class="clear"></div>
                  <div class="wfs_visible">
                    <label for="javscript"><input type="radio" id="javscript" name="source_selection"<?php echo ($result->project_options == 0)?'checked="checked"':''; ?>  value="0"/>&nbsp;&nbsp;Javascript</label><br />
                    <label for="stylesheet"><input type="radio" id="stylesheet" name="source_selection" <?php echo ($result->project_options == 1)?'checked="checked"':''; ?>  value="1" />&nbsp;&nbsp;Stylesheet</label>
                  </div>
                </div>
            </div>
           <?php } ?>
            <div id="editor_option_container" class="postbox">
                <h3  style="cursor:default";><span ><?php _e('Webfonts Editor Option'); ?></span></h3>
                <div class="inside" >
                    <span><strong><?php _e('Please select the editor to be use'); ?></strong></span>
                    <div class="clear"></div>
                    <div class="wfs_visible">
                        <label for="tinymce"><input type="radio" id="tinymce" name="editor_select"<?php echo ($result->editor_select == 0)?'checked="checked"':''; ?>  value="0"/>&nbsp;&nbsp;TinyMCE</label><br />
                        <label for="ckeditor"><input type="radio" id="ckeditor" name="editor_select" <?php echo ($result->editor_select == 1)?'checked="checked"':''; ?>  value="1" />&nbsp;&nbsp;Ckeditor</label>
                    </div>
                    
                    <!-- Changes for: Option to show system fonts in editor - By Keshant -->
                    <div class="clear"></div>
                    <span><strong><?php _e('Enable client system fonts for WYSIWYG editor?'); ?></strong></span>
                    <div class="clear"></div>
                    <div class="wfs_visible">
                    	<label for="system_fonts_admin"><input type="radio" id="system_fonts_admin" name="system_fonts"<?php echo ($result->show_system_fonts == 1)?'checked="checked"':'';?> value="1" />&nbsp;&nbsp;Admin only</label><br />
                        <label for="system_fonts_front"><input type="radio" id="system_fonts_front" name="system_fonts"<?php echo ($result->show_system_fonts == 2)?'checked="checked"':'';?> value="2" />&nbsp;&nbsp;Front end only</label><br />
                        <label for="system_fonts_both"><input type="radio" id="system_fonts_both" name="system_fonts"<?php echo ($result->show_system_fonts == 3)?'checked="checked"':'';?> value="3" />&nbsp;&nbsp;Both</label><br />
                        <label for="system_fonts_none"><input type="radio" id="system_fonts_none" name="system_fonts"<?php echo ($result->show_system_fonts == 0)?'checked="checked"':'';?> value="0" />&nbsp;&nbsp;None</label>
                    </div>
                    <!-- End -->
                    
                    <div class="clear"></div>
                    <div class="wfs_day_row wfs_info_text"><?php _e('Please deactivate other module that replaces editor in the wordpress before enabling the editor.'); ?></div>  <div class="clear"></div>
                    <div class="wfs_visible">
                        <label for="enable_editor">
                        <input id="enable_editor" type="checkbox"  value="1" name="enable_editor" <?php echo ($result->wysiwyg_enabled == 1)?'checked="checked"':''; ?>  />&nbsp;&nbsp;<?php _e('Enable the online editor with web fonts from your Web Font Project.'); ?>
                        </label>
                    </div>
                    <div class="clear"></div>
                    <div><a href="javascript:;" id="editor_sample">See online editor with webfonts</a></div>
                    <div id="wfs_display_editor" style="display:none;">
						<?php
                        wp_tiny_mce(false,
                        array("editor_selector" => "wfs_sample_editor")
                        ); ?>
                        <div><strong>TinyMCE Sample</strong></div> 
                        <div class="clear"></div>
                        <textarea name="wfs_sample_editor" class="wfs_sample_editor" style="height:80px;"></textarea> 
                        <div class="clear"></div>
                        <div><strong>Ckeditor Sample</strong></div> 
                        <div class="clear"></div>
                        <textarea name="wfs_sample_ckeditor" id="wfs_sample_ckeditor" class="wfs_sample_ckeditor"></textarea> 
                    </div>
                </div>
            </div>
	    </div>
    </div>
    <div>
    <input type="hidden" name="project_id" id="project_id" value="<?php echo $pid ?>" />
    <input type="submit" value="<?php _e('Save'); ?>" name="submit" class="button-primary" /> </div>
</form>
 </div>
 
<!-- End of configure tab -->
<!-- Start of Work on stylesheet tab -->
<div class="wfs_tab">	
	<div id="poststuff">
    	<div class="meta-box-sortables ui-sortable" >
            <!-- Selectors list div-->            
            <div id="selector_option_container" class="postbox">
                <h3  style="cursor:default";><span >Selectors</span></h3>
                <div class="inside" >
                <form action="admin.php?page=wfs_options&func=selector_act&pid=<?php echo $_GET['pid'] ?>" method="post">
                
                <!--
                Changes: Add selector section moved
                -By: Keshant
                -->
                <!-- Add Selectors list div-->  
                <span><b>Add Selector</b></span>                    
                <input type="text" name="add_selector_text" id="add_selector_text" style="width:200px;" /> 
                <input type="button" value="<?php _e('Add'); ?>" name="submit" class="button-secondary" id="add_selector_btn" />                    
                <!-- End of add selectors div -->
                <!-- End -->
                
                <div id="selectors_list">
                    <table cellspacing="0" cellpadding="0" border="0" class="widefat" style="margin-top:20px;" >
                        <tbody>
                        	<?php
							
							 // load a json file.
							$apiurl = "json/Selectors/?wfspstart=0&wfsplimit=".SELECTOR_LIMIT."&wfspid=".$result->project_key;
							$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
							$jsonUrl = $wfs_api->wfs_getInfo_post();
							//Creating JSON Instance
							if($jsonUrl != ""){
							//Creating array from json data
							$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
							$selectorsArray = $json->decode($jsonUrl);
							//fetching Array data
							$count = 1;
							$wfsSelectorTag=array();
							$message = $selectorsArray['Selectors']['Message'];
							if($message == "Success"){
							$selectors = $selectorsArray['Selectors']['Selector'];
							if(!empty($selectors)){
								$is_multi = is_multi($selectors);
								if($is_multi == 1){ 
								$SelectorTag = $selectors['SelectorTag'];
								$wfsSelectorTag[]=$SelectorTag;//array for list of selectors						
								$SelectorID = $selectors['SelectorID'];
								$SelectorFontID = $selectors['SelectorFontID'];
								
								$fontsArr = wfs_font_list($result->project_key,$SelectorFontID,$count);
							?>
								<tr style="height:40px;">
                                <td><?php echo $SelectorTag; ?></td>
                                <td><?php echo $fontsArr[0]; ?></td>
                                <td><span class="wfs_selectors" style="font-size:26px;font-family:<?php echo $fontsArr[3]; ?>" id="fontid_<?php echo $count;?>"><?php echo $fontsArr[1]?></span></td>
                                <td><a href="admin.php?page=wfs_options&func=selector_act&pid=<?php echo $pid; ?>&sid=<?php echo $SelectorID ?>" onclick="return confirm('Are you sure want to delete selector <?php echo $SelectorTag; ?>?');" >Remove</a><input type="hidden" id="selector_<?php echo $count?>" name="selector_<?php echo $count?>" value="<?php echo $SelectorID; ?>" />
                                </td>
                            </tr>
								<?php 
								$count++;
								}
								else{
								foreach( $selectors as $selector ){
										$SelectorTag = $selector['SelectorTag'];
										$wfsSelectorTag[]=$SelectorTag;//array for list of selectors						
										$SelectorID = $selector['SelectorID'];
										$SelectorFontID = $selector['SelectorFontID'];
										$fontsArr = wfs_font_list($result->project_key,$SelectorFontID,$count);
										?>
										<tr style="height:40px;">
											<td><?php echo $SelectorTag; ?></td>
											<td><?php echo $fontsArr[0]; ?></td>
											<td><span class="wfs_selectors" style="font-size:26px;font-family:<?php echo $fontsArr[3]; ?>" id="fontid_<?php echo $count;?>"><?php echo $fontsArr[1]?></span></td>
											<td><a href="admin.php?page=wfs_options&func=selector_act&pid=<?php echo $pid; ?>&sid=<?php echo $SelectorID ?>" onclick="return confirm('Are you sure want to delete selector <?php echo $SelectorTag; ?>?');" >Remove</a><input type="hidden" id="selector_<?php echo $count?>" name="selector_<?php echo $count?>" value="<?php echo $SelectorID; ?>" />
											</td>
										</tr>
										<?php
										$count++;
										}//end of for each
								}//end of else for is_multi
							}//end of if for empty selectors
							$totalRecord =$selectorsArray['Selectors']['TotalRecords'];
							$pageStart = $selectorsArray['Selectors']['PageStart'];
							$pageLimit = $selectorsArray['Selectors']['PageLimit'];
							if($count == 1)
							{ ?>
							<tr style="height:40px;">
                                <td colspan="4" align="center"><?php _e('No Selectors available.'); ?></td>
                            </tr>	
							<?php }
							}//END of success
							else{ ?>
								<tr style="height:40px;">
                                <td colspan="4" align="center"><?php _e('Sorry! Your request could not be completed. Please reload the page again.'); ?></td>
                            </tr>
							<?php } 
							} //ENd of xmlurl checking 
							else{ ?>
								<tr style="height:40px;">
                                <td colspan="4" align="center"><?php _e('Sorry! Your request could not be completed. Please reload the page again.'); ?></td>
                            </tr>
							<?php } ?>
                         </tbody>
                    </table>
                    <div class="clear"></div>
                             
                </div>
                <div class="pagination_div" id="selector_pagination_div"><?php if($totalRecord !="" && $pageLimit!="" && $count!=1){
							$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,'selectors_list','selector_pagination_div',"wfs_selector_action_pagination"); echo $wfs_pg->getPagination(); }?></div><input type="hidden" id="selector_page_start" value="<?php echo $pageStart; ?>" /><input type="hidden" id="selector_page_limit" value="<?php echo $pageLimit; ?>" /><input type="hidden" id="selector_total_record" value="<?php echo $totalRecord; ?>" /> 
                
                 <input type="submit" value="<?php _e('Save'); ?>" name="submit" class="button-primary" />
                </form>
                </div>
            </div>
            <!-- end of selecotors list -->
          
         	 <?php /* 
			 Changes: Moved above the selector listing section
			 - By: Keshant
			 
			 ?><!-- Add Selectors list div-->  
            <div id="selector_option_container" class="postbox">
                <h3  style="cursor:default";><span >Add Selector</span></h3>
                <div class="inside" >
                	<input type="text" name="add_selector_text" id="add_selector_text" style="width:200px;" /> 
                    <input type="button" value="<?php _e('Add'); ?>" name="submit" class="button-secondary" id="add_selector_btn" />
                </div>
            </div>
            <!-- End of add selectors div --><?php 	
			*/?>
            
         	 <!-- Font and existing selectors tab-->  
                <ul class="wfs_tabNav2">
                    <li class="wfs_current"><a href="#"><?php _e('Fonts'); ?></a></li>
                    <li><a href="#"><?php _e('Wordpress existing selectors'); ?></a></li>
                </ul> 
                <div class="clear"></div>
                <div class="wfs_tabContainer">
                	<div class="wfs_tab wfs_current">
                        <!-- Font list div-->  
                        <form action="admin.php?page=wfs_options&func=fontlist_act" name="FontList" id="FontList" method="post">
                        <div id="font_option_container" class="postbox">
                            <h3  style="cursor:default";><span ><?php _e('Fonts'); ?></span></h3>
                          
                            <div style="clear:both"></div>
                            <div class="inside" >
							
                           <!--
                           Changes: Modified the font listing header
                           -By: Keshant
                           -->
						   <table style="width:100%;" cellpadding="2" cellspacing="2" id="fonts_main">
						     <tr style="text-align:left;">
                            	<th style="padding:8px;" rowspan="2">Preview</th><th colspan="2">Online WYSIWYG editor</th>                                
                            </tr>
                            <tr>
                            	<th style="width:100px;text-align:left">Non-Admin</th><th style="width:100px;;text-align:left">Admin</th>
                            </tr>
                            <!-- End -->
                            
                            <?php 
							$pkey = $result->project_key;
							$fontsArray = getPrjFonts($result->project_key);
							if(count($fontsArray["FontName"])>0){
							 $result = $wpdb->get_results("SELECT * FROM `".$wfs_editor_fonts."` WHERE  pid = '$pid' and is_active = '1'");
							 
							$editorFontNameArr = array();
							$editorFontNameArrStatus = array();
							for($k=0;$k<count($result);$k++){
									$editorFontNameArr[] = $result[$k]->tinymce_name;
									$editorFontNameArrStatus[] = $result[$k]->is_admin;
							}  
							for($i=0;$i< count($fontsArray["FontName"]);$i++){
							$checkedFront = "";
							$checkedBack  = "";
							if(in_array($fontsArray["FontName"][$i].'='.$fontsArray["FontCSSName"][$i].';',$editorFontNameArr))
							{
								$keyFontData =  array_search($fontsArray["FontName"][$i].'='.$fontsArray["FontCSSName"][$i].';',$editorFontNameArr);
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
								echo '<tr class="font_sep '.$class.'">
									<td style="padding:8px;">
										<div class="font_img" style="font-family:\''.$fontsArray["FontCSSName"][$i].'\' !important;font-size:30px;">'.$fontsArray["FontPreviewTextLong"][$i].'</div>
										
										<div class="fontnames"><u>'.$fontsArray["FontName"][$i].'</u> | <u>'.$fontsArray["FontFondryName"][$i].'</u>
										| <u>'.$fontsArray["FontLanguage"][$i].'</u>
										'.$fontsArray["FontSize"][$i].'
										</div>
									</td>
									<td style=" width:120px;"><input type="checkbox" name="frontend['.$i.']" id="frontend['.$i.']" value="1" '.$checkedFront.' /></td>
									<td style="width:120px;"><input type="checkbox" name="backend['.$i.']" id="backend['.$i.']" value="1" '.$checkedBack.'/>
									<input  type="hidden"  name="fontlist['.$i.']" value="'.$fontsArray["FontName"][$i].'--'.$fontsArray["FontCSSName"][$i].';">
									</td>
								</tr>
								';
								}
							}
							else{
									echo '<tr class="font_sep odd" style="text-align:center;"><td colspan="3"> No fonts available </td></tr>';
								}
							 $totalRecord = $fontsArray['TotalRecords'];
							 $pageStart = $fontsArray['PageStart'];
							 $pageLimit = $fontsArray['PageLimit'];
								?>
								
                            </table>
							
                            <div class="pagination_div" id="font_pagination_div"><?php if($totalRecord!="" && $pageLimit!="" && count($fontsArray["FontName"])!=0){
							$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,'fonts_main','font_pagination_div',"wfs_font_action"); echo $wfs_pg->getPagination(); }?></div><input type="hidden" id="font_page_start" value="<?php echo $pageStart; ?>" /><input type="hidden" id="font_page_limit" value="<?php echo $pageLimit; ?>" /><input type="hidden" id="font_total_record" value="<?php echo $totalRecord; ?>" /> 
                            
                            </div>
                        <div>
                        <div style="padding:7px; ">
                        <input type="hidden" name="project_id" id="project_id" value="<?php echo $pid ?>" />
                        <input type="submit" name="saveFontListsBtn" id="saveFontListsBtn" value="<?php _e('SaveEditorFonts');?>" class="button-primary">
                        </div>
                        </div>
                        </div>
                        </form>
                        <!-- End of Font div -->
                    </div>
                   	<div class="wfs_tab">
                           <!-- Font list div-->  
                        <div id="font_option_container" class="postbox" >
                            <h3  style="cursor:default;"><span ><?php _e('Wordpress Existing Selector'); ?></span></h3>
                            <div class="inside" style="overflow-y:scroll;height:225px;">
                             <?php $wfs_selector=getAllActiveSelectors();
								if(!empty($wfs_selector)){
									//for($i=0;$i< count($wfs_selector);$i++){
									foreach($wfs_selector as $selector){
										if(($i%2)==0){$class = "even";}else{$class = "odd";}
										$link = in_array($selector,$wfsSelectorTag)?'Added to webfonts':'<a href="javascript:void(0);" id="'.$selector.'" class="addtowebfonts">Add to webfonts</a>';
										echo '<div class="wfs_row '.$class.'">
												<div class="wfs_existing_selector">'.$selector.'</div>';
										echo '<div class="wfs_existing_selector_link" id="addFonts_'.$i.'">'.$link.'</div>
										</div>';
									}
								}
						   ?>
                            </div>
                        </div>
                        <!-- End of Font div -->
                    </div>
                </div>
            <!-- End of Font and existing selectors tab -->
            
		</div>
	</div>
</div>
<!-- End of Work on stylesheet tab -->
<!-- Start of Domain tab -->
<div class="wfs_tab">
<div id="poststuff">
    	<div class="meta-box-sortables ui-sortable" >
            <!-- Selectors list div-->            
            <div id="domain_option_container" class="postbox">
                <h3  style="cursor:default";><span >Domains</span></h3>
                <div class="inside" >
             
                <div id="domain_list">
                    <table cellspacing="0" cellpadding="0" border="0" class="widefat" style="margin-top:20px;" >
                        <tbody>
                        	<?php
						
							 // load a json file.
							$apiurl = "json/Domains/?wfspstart=0&wfsplimit=".DOMAIN_LIMIT."&wfspid=".$pkey;
							$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
							$jsonUrl = $wfs_api->wfs_getInfo_post();
							//Creating JSON Instance
							//Creating array from json data
							$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
							$domainsArray = $json->decode($jsonUrl);
							//fetching Array data from json
							$count = 1;
							$message = $domainsArray['Domains']['Message'];
							if(strtolower($message)=="success"){
								$domains = $domainsArray['Domains']['Domain'];
								if(!empty($domains)){
									$is_multi = is_multi($domains);
									if($is_multi == 1){
										$domainName = $domains['DomainName'];
										$domainID = $domains['DomainID'];
										?>
										<tr style="height:40px;">
										<td><?php echo '<a href="http://'.$domainName.'" target="_blank">'.$domainName.'</a>'; ?></td>
										
										<td><a href="admin.php?page=wfs_options&func=domain_act&pid=<?php echo $pid; ?>&did=<?php echo $domainID ?>&dname=<?php echo $domainName ?>&mode=edit"  >Edit</a>&nbsp;|&nbsp;<a href="admin.php?page=wfs_options&func=domain_act&pid=<?php echo $pid; ?>&did=<?php echo $domainID ?>" onclick="return confirm('Are you sure want to delete domain <?php echo $domainName; ?>?');" >Remove</a>
										</td>
										</tr>
										<?php
										$count++;
									}else{
										foreach( $domains as $domain )
											{
											$domainName = $domain['DomainName'];
											$domainID = $domain['DomainID'];
											?>
											<tr style="height:40px;">
											<td><?php echo '<a href="http://'.$domainName.'" target="_blank">'.$domainName.'</a>'; ?></td>
											
											<td><a href="admin.php?page=wfs_options&func=domain_act&pid=<?php echo $pid; ?>&did=<?php echo $domainID ?>&dname=<?php echo $domainName ?>&mode=edit"  >Edit</a>&nbsp;|&nbsp;<a href="admin.php?page=wfs_options&func=domain_act&pid=<?php echo $pid; ?>&did=<?php echo $domainID ?>" onclick="return confirm('Are you sure want to delete domain <?php echo $domainName; ?>?');" >Remove</a>
											</td>
											</tr>
											<?php
											$count++;
											}//end of foreach
									}//end of else for is_multi
								}//end of if for domain empty
								if($count == 1)
								{ ?>
								<tr style="height:40px;">
									<td colspan="4" align="center"><?php _e('No Domain available.'); ?></td>
								</tr>	
								<?php }
								//Defining the pagination variable from json file
								$totalRecord =$domainsArray['Domains']['TotalRecords'];
								$pageStart =$domainsArray['Domains']['PageStart'];
								$pageLimit =$domainsArray['Domains']['PageLimit'];
							} else{
								 ?>
							<tr style="height:40px;">
                                <td colspan="4" align="center"><?php _e('Sorry! Your request could not be completed. Please reload the page again.'); ?></td>
                            </tr>	
							<?php
								}?>
                         </tbody>
                    </table>
                    <div class="clear"></div>
                   
                </div>
				 <div class="pagination_div" id="domain_pagination_div"><?php  if($totalRecord != "" && $pageLimit!="" && $count != 1){
				$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,'domain_list','domain_pagination_div',"wfs_domain_action_pagination"); echo $wfs_pg->getPagination(); }?></div><input type="hidden" id="domain_page_start" value="<?php echo $pageStart; ?>" /><input type="hidden" id="domain_page_limit" value="<?php echo $pageLimit; ?>" /><input type="hidden" id="domain_total_record" value="<?php echo $totalRecord; ?>" /> 
                </div>
            </div>
            <!-- end of selecotors list -->
          
         	 <!-- Add Selectors list div-->  
            <div id="domain_option_container" class="postbox">
                <h3  style="cursor:default";><span >Add Domain</span></h3>
                <div class="inside" >
                	<input type="text" name="add_domain_text" id="add_domain_text" style="width:200px;" /> 
                    <input type="button" value="<?php _e('Add'); ?>" name="submit" class="button-secondary" id="add_domain_btn" />
                </div>
            </div>
            <!-- End of add selectors div -->
         	
                
         
            
		</div>
	</div>
 </div>
<!-- END of Domain tab -->
</div>  

</div>
<?php } 
/*
* Configuration page action 
*
*/
function wfs_project_configure_action(){
	global $wpdb;
	global $wp_wfs_configure_table;
	$pid = $_POST['project_id'];
	if(!empty($pid)){
		if(!empty($_POST['page_list'])){
			$pages = implode(',',$_POST['page_list']);
		}
	if(isset($_POST['source_selection'])){ $project_options = $_POST['source_selection'];} else $project_options = 0;
	/***** 
	Changes for: Option to show system fonts in editor 
	Changes: show_system_fonts added
	- By Keshant  
	*****/
	$wpdb->update($wp_wfs_configure_table, array('project_pages' => $pages,
													 'project_page_option' => $_POST['page_visiblity'],
													 'project_day' => $_POST['days'],
													 'project_options' => $project_options,
													 'wysiwyg_enabled' => $_POST['enable_editor'],
													 'editor_select' => $_POST['editor_select'],
													 'show_system_fonts' => $_POST['system_fonts']), array('wfs_configure_id' => $pid));
	}
	wp_redirect(get_option('siteurl') . '/wp-admin/admin.php?page=wfs_options&func=configure&pid='.$pid);

}
/*
* FontList page action 
*
*/
function wfs_project_fontlist_action(){
	global $wpdb;
	global $wp_wfs_configure_table;
	global $wfs_editor_fonts;
	$pid = $_POST['project_id'];
	
			$editorFontArray = array();
			$fontInPage = $_POST['fontlist'];
			$frontEndFonts = $_POST['frontend'];
			$backEndFonts = $_POST['backend'];
			foreach($fontInPage  as $key => $fontName)
			{
				if($frontEndFonts[$key]==1 && $backEndFonts[$key]==1)
				{
					$editorFontArray['FontName'][]=$fontName;
					$editorFontArray['isFront'][]=2;
					$editorFontArray['isStatus'][]=1;
				}
				else if($frontEndFonts[$key]==1 && $backEndFonts[$key]==0)
				{
					$editorFontArray['FontName'][]=$fontName;
					$editorFontArray['isFront'][]=1;
					$editorFontArray['isStatus'][]=1;
				}
					else if($frontEndFonts[$key]==0 && $backEndFonts[$key]==1)
				{
					$editorFontArray['FontName'][]=$fontName;
					$editorFontArray['isFront'][]=0;
					$editorFontArray['isStatus'][]=1;
				}
				else{
					$editorFontArray['FontName'][]=$fontName;
					$editorFontArray['isFront'][]=0;
					$editorFontArray['isStatus'][]=0;
					}
			}
			if(!empty($editorFontArray)){
				foreach($editorFontArray['FontName'] as $keyEditor => $editorFont){
					 $fontArr = explode("--",$editorFont);
					$tinymce = mysql_escape_string($fontArr[0].'='.$fontArr[1]);echo "<br>";
					$ckeditor = mysql_escape_string($fontArr[0].'/'.$fontArr[1]);echo "<br>";
					//echo $tinymce."--".$ckeditor."--".$editorFontArray['isFront'][$keyEditor]."--".$pid."--".$editorFontArray['isStatus'][$keyEditor];echo "<br>";
					
					$check = $wpdb->get_col("SELECT wfs_font_id FROM `".$wfs_editor_fonts."` WHERE `tinymce_name` = '$tinymce' and `ckeditor_name` = '$ckeditor' and pid = '$pid'");
					//echo $check[0];
					//print_r($check);die();
					if(!empty($check)){
						//$wpdb->update("UPDATE `{".$wfs_editor_fonts."}` SET tinymce_name = '%s', ckeditor_name = '%s', is_admin = '%d', is_active = '%d', pid = '%d' WHERE wfs_font_id = '%d'",$tinymce, $ckeditor, $editorFontArray['isFront'][$keyEditor], $editorFontArray['isStatus'][$keyEditor], $pid, $countrows);
						$wpdb->update($wfs_editor_fonts, array( 'tinymce_name' => $tinymce, 'ckeditor_name' => $ckeditor, 'is_admin' => $editorFontArray['isFront'][$keyEditor],'pid'=> $pid,'is_active'=>$editorFontArray['isStatus'][$keyEditor]),array('wfs_font_id' => $check[0]));
						}
					else{
						$wpdb->insert($wfs_editor_fonts, array( 'tinymce_name' => $tinymce, 'ckeditor_name' => $ckeditor, 'is_admin' => $editorFontArray['isFront'][$keyEditor],'pid'=> $pid,'is_active'=>$editorFontArray['isStatus'][$keyEditor]));
						
						} /* */
				}
			}
	if(!empty($pid)){
		wp_redirect(get_option('siteurl') . '/wp-admin/admin.php?page=wfs_options&func=configure&pid='.$pid);
	}else{
		wp_redirect(get_option('siteurl') . '/wp-admin/admin.php?page=wfs_options');
	}
	
}
/*
* Selector action submit function
*/
function wfs_project_selector_action(){
		global $wfs_userid;
		global $wfs_public_key;
		global $wfs_private_key;
		global $wfs_usertype;
		global $wp_wfs_configure_table;
		global $wpdb;
		$data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wp_wfs_configure_table." WHERE wfs_configure_id = %d ",$_GET['pid']));
	if(isset($_GET['sid'])){
		// load a json file.
		$apiurl = "json/Selectors/?wfspid=".$data->project_key."&wfsselector_id=".urlencode($_GET['sid']);
		$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
		$jsonUrl = $wfs_api->deleteSelector();
	}else{
		$fontIdList = array();
		$selectorIdList = array();
		foreach($_POST['font_list'] as $key => $fontname)
			{
			$fontidarr = explode("@!",$fontname);
			if($fontidarr[2] != ''){
				array_push($fontIdList,$fontidarr[2]);
			}else{
				array_push($fontIdList,'-1');
			} 
				$cnt = $key + 1;
				$selctor_id = $_POST['selector_'.$cnt];
				array_push($selectorIdList,$selctor_id);
			}
		 $fontids = implode(",",$fontIdList);
		$selectorsids = implode(",",$selectorIdList);
		//Fetching the json data from WFS
		$apiurl = "json/Selectors/?wfspid=".$data->project_key;
		$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
		$jsonUrl = $wfs_api->saveSelector($fontids,$selectorsids);
	}
	wp_redirect(get_option('siteurl') . '/wp-admin/admin.php?page=wfs_options&func=configure&pid='.$_GET['pid']);
}
/*
*Domain action submit function
*/	
function wfs_project_domain_action(){
		global $wfs_userid;
		global $wfs_public_key;
		global $wfs_private_key;
		global $wfs_usertype;
		global $wp_wfs_configure_table;
		global $wpdb;
		$pid = (isset($_POST['pid']))?$_POST['pid']:$_GET['pid'];
				$data = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wp_wfs_configure_table." WHERE wfs_configure_id = %d ",$pid));
	if(isset($_GET['mode']) && $_GET['mode'] == 'edit'){
		 ?>
		<div class="wrap">
    <div class="wfs_web_logo"><a style="text-decoration: none;" target="_blank" href="http://webfonts.fonts.com"><img src="<?php echo WP_PLUGIN_URL . '/'.FOLDER_NAME.'/images/logo.gif'; ?>" /></a></div>

    <div class="wfs_right_link">
    <a class="home" href="admin.php?page=wfs_options"><?php _e('Home'); ?></a> | <a href="admin.php?page=wfs_options&mode=login"><?php _e('My WFS account'); ?></a>
    </div>
    <div id="errMsg" class="error" style="display:none;margin:10px;"></div>
	<div class="icon32" id="icon-edit"><br/></div>
	<h2><?php _e('Edit project for'); ?> <?php echo $data->project_name; ?></h2>
        <div id="poststuff">
    	<div class="meta-box-sortables ui-sortable" >
            <!-- Selectors list div-->            
            <div id="domain_option_container" class="postbox">
                <h3  style="cursor:default";><span >Edit Domain</span></h3>
                <div class="inside" >
                <form action="admin.php?page=wfs_options&func=domain_act" method="post">
                	<input type="text" name="edit_domain_text" id="edit_domain_text" style="width:200px;"  value="<?php echo $_GET['dname'] ?>"  /> 
                    <input type="hidden" name="pid" value="<?php echo $pid ?>" />
                     <input type="hidden" name="did" value="<?php echo $_GET['did'] ?>" />
                    <input type="submit" value="<?php _e('Save'); ?>" name="submit" class="button-secondary" />
                 </form>
                </div>
             </div>
        </div>
        </div>
        </div>
		<?php
		die(); 
	}else if($_POST['submit']=='Save'){
		$domain_id = $_POST['did'];
		$domain_name = $_POST['edit_domain_text'];
		//Fetching the json data from WFS
		$apiurl = "json/Domains/?wfspid=".$data->project_key.'&wfsdomain_id='.urlencode($domain_id);
		$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
		$jsonUrl = $wfs_api->editDomain($domain_name);
		}else{
		//Fetching the json data from WFS
		$apiurl = "json/Domains/?wfspid=".$data->project_key."&wfsdomain_id=".urlencode($_GET['did']);
		$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
		$jsonUrl = $wfs_api->deleteDomain();
	}
	wp_redirect(get_option('siteurl') . '/wp-admin/admin.php?page=wfs_options&func=configure&pid='.$pid);
}
/*
*Fetch all the fonts given a project key
@project key: string
*/
function getPrjFonts($project_key){
	global $wfs_public_key;
	global $wfs_private_key;
	
	$apiurl = "json/Fonts/?wfspstart=0&wfsplimit=".FONT_LIMIT."&wfspid=".$project_key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$jsonUrl = $wfs_api->wfs_getInfo_post();
	//Creating array from json data
	$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
	$fontsArray = $json->decode($jsonUrl);
	$fonts = $fontsArray['Fonts']['Font'];

	$webfonts=array();
	$webfonts['TotalRecords']= $fontsArray['Fonts']['TotalRecords'];
	$webfonts['PageLimit']= $fontsArray['Fonts']['PageLimit'];
	$webfonts['PageStart']= $fontsArray['Fonts']['PageStart'];
	if(!empty($fonts)){
	$is_multi = is_multi($fonts);
	if($is_multi == 1){
		$webfonts['fontid'][]= $fonts['FontID'];
		$webfonts['FontName'][]= $fonts['FontName'];
		$webfonts['FontPreviewTextLong'][]= $fonts['FontPreviewTextLong'];
		$webfonts['FontFondryName'][]= $fonts['FontFondryName'];
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
	}//end of else for is_multi
	}//end of if for empty fonts
	return $webfonts;
}




$auth[0]=0;
if(isset($_POST['webfonts_api_token'])) {
	$webfonts_token=explode("--",trim($_POST['webfonts_api_token']));
	$auth=wfs_authSubmit(trim($webfonts_token[0]),trim($webfonts_token[1]));
	login_validate($auth[0]);
}
else { 
	$auth=wfs_authSubmit(trim($wfs_public_key),trim($wfs_private_key));
	login_validate($auth[0]);		

}

/*
page displaying function
*/
function login_validate($auth){
$wfs_details = getUnPass();
$wfs_userid = $wfs_details['0'];
$wfs_public_key = $wfs_details['1'];
$wfs_private_key = $wfs_details['2'];
$wfs_usertype = $wfs_details['3'];
if(isset($_GET['func']) && trim($_GET['func'])!='' && $auth==1){	
	//defines which page to display
	$func = $_GET['func'];
	switch($func)
		{
		case "prj_act":	
				wfs_project_action();
				break;
		case "import":	
				wfs_project_list();
				break;
		case "configure":	
				wfs_project_configure();
				break;
		case "configure_act":	
				wfs_project_configure_action();
				break;
		case "fontlist_act":	
				wfs_project_fontlist_action();
				break;
		case "selector_act":	
				wfs_project_selector_action();
				break;
		case "domain_act":	
				wfs_project_domain_action();
				break;
		default;
				wfs_project_listing();
				break;
		}
}else{
	if($auth==1){//verify whether logged in user or not
			if($_GET['mode']=='login')
				{
					wfs_login_form();	
				}else{
					wfs_project_listing();
				}
		}else{
			wfs_login_form();
		}
}
}