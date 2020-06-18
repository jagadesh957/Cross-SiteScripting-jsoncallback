<?php
defined('is_running') or die('Not an entry point...');


class admin_tools{


	function AdminScripts(){
		global $langmessage,$config;
		$scripts = array();


		$scripts['Admin_Menu']['script'] = '/include/admin/admin_menu_new.php';
		$scripts['Admin_Menu']['class'] = 'admin_menu_new';
		$scripts['Admin_Menu']['label'] = $langmessage['file_manager'];
		$scripts['Admin_Menu']['group'] = 'content';


		$scripts['Admin_Uploaded']['script'] = '/include/admin/admin_uploaded.php';
		$scripts['Admin_Uploaded']['class'] = 'admin_uploaded';
		$scripts['Admin_Uploaded']['label'] = $langmessage['uploaded_files'];
		$scripts['Admin_Uploaded']['group'] = 'content';


		$scripts['Admin_Theme_Content']['script'] = '/include/admin/admin_theme_content.php';
		$scripts['Admin_Theme_Content']['class'] = 'admin_theme_content';
		$scripts['Admin_Theme_Content']['label'] = $langmessage['layouts'];
		$scripts['Admin_Theme_Content']['group'] = 'appearance';


/*
		$scripts['Admin_Menus']['script'] = '/include/admin/admin_menus.php';
		$scripts['Admin_Menus']['class'] = 'admin_menus';
		$scripts['Admin_Menus']['label'] = $langmessage['Menus'];
		$scripts['Admin_Menus']['group'] = 'appearance';
*/



		$scripts['Admin_Extra']['script'] = '/include/admin/admin_extra.php';
		$scripts['Admin_Extra']['class'] = 'admin_extra';
		$scripts['Admin_Extra']['label'] = $langmessage['theme_content'];
		$scripts['Admin_Extra']['group'] = 'content';


		$scripts['Admin_Configuration']['script'] = '/include/admin/admin_configuration.php';
		$scripts['Admin_Configuration']['class'] = 'admin_configuration';
		$scripts['Admin_Configuration']['label'] = $langmessage['configuration'];
		$scripts['Admin_Configuration']['group'] = 'settings';
		$scripts['Admin_Configuration']['popup'] = true;


		$scripts['Admin_Users']['script'] = '/include/admin/admin_users.php';
		$scripts['Admin_Users']['class'] = 'admin_users';
		$scripts['Admin_Users']['label'] = $langmessage['user_permissions'];
		$scripts['Admin_Users']['group'] = 'settings';


		$scripts['Admin_Permalinks']['script'] = '/include/admin/admin_permalinks.php';
		$scripts['Admin_Permalinks']['class'] = 'admin_permalinks';
		$scripts['Admin_Permalinks']['label'] = $langmessage['permalinks'];
		$scripts['Admin_Permalinks']['group'] = 'settings';


		$scripts['Admin_Missing']['script'] = '/include/admin/admin_missing.php';
		$scripts['Admin_Missing']['class'] = 'admin_missing';
		$scripts['Admin_Missing']['label'] = $langmessage['Link Errors'];
		$scripts['Admin_Missing']['group'] = 'settings';


		$scripts['Admin_Trash']['script'] = '/include/admin/admin_trash.php';
		$scripts['Admin_Trash']['class'] = 'admin_trash';
		$scripts['Admin_Trash']['label'] = $langmessage['trash'];
		$scripts['Admin_Trash']['group'] = 'content';


		if( isset($config['admin_links']) && is_array($config['admin_links']) ){
			$scripts += $config['admin_links'];
		}

		$scripts['Admin_Port']['script'] = '/include/admin/admin_port.php';
		$scripts['Admin_Port']['class'] = 'admin_port';
		//$scripts['Admin_Port']['label'] = $langmessage['Import/Export'];
		$scripts['Admin_Port']['label'] = $langmessage['Export'];
		$scripts['Admin_Port']['group'] = 'settings';


		$scripts['Admin_Status']['script'] = '/include/admin/admin_rm.php';
		$scripts['Admin_Status']['class'] = 'admin_status';
		$scripts['Admin_Status']['label'] = $langmessage['Site Status'];
		$scripts['Admin_Status']['group'] = 'settings';


		$scripts['Admin_Uninstall']['script'] = '/include/admin/admin_rm.php';
		$scripts['Admin_Uninstall']['class'] = 'admin_rm';
		$scripts['Admin_Uninstall']['label'] = $langmessage['uninstall_prep'];
		$scripts['Admin_Uninstall']['group'] = 'settings';


		/*
		 * 	Unlisted
		 */


		$scripts['Admin_Addons']['script'] = '/include/admin/admin_addons.php';
		$scripts['Admin_Addons']['class'] = 'admin_addons';
		$scripts['Admin_Addons']['label'] = $langmessage['add-ons'];
		$scripts['Admin_Addons']['list'] = false;


/*
		$scripts['Admin_Addon_Themes']['script'] = '/include/admin/admin_addon_themes.php';
		$scripts['Admin_Addon_Themes']['class'] = 'admin_addon_themes';
		$scripts['Admin_Addon_Themes']['label'] = $langmessage['addon_themes'];
		$scripts['Admin_Addon_Themes']['list'] = false;
*/

		return $scripts;
	}


	//returns false if the user does not have permission for the $script
	function HasPermission($script){
		global $gpAdmin;

		//old
		$gpAdmin += array('granted'=>'');
		if( $gpAdmin['granted'] == 'all' ){
			return true;
		}

		$granted = ','.$gpAdmin['granted'].',';
		if( strpos($granted,','.$script.',') !== false ){
			return true;
		}

		return false;
	}


	function GetAdminPanel($new_versions){
		global $langmessage, $page, $gpAdmin, $config;

		//don't send the panel when it's a gpreq=json request
		if( !empty($_REQUEST['gpreq']) ){
			return;
		}

		$class = 'keep_viewable';
		if( isset($gpAdmin['gpui_cmpct']) && $gpAdmin['gpui_cmpct'] ){
			$class .= ' compact';
			if( $gpAdmin['gpui_cmpct'] === 2 ){
				$class .= ' min';
			}
		}
		$class = ' class="'.$class.'"';

		//$gpAdmin['gpui_ty'] = 0;
		$position = ' style="top:'.max(-10,$gpAdmin['gpui_ty']).'px;left:'.max(-10,$gpAdmin['gpui_tx']).'px"';

		echo "\n\n";
		echo '<div id="simplepanel"'.$class.$position.'>';

			//toolbar
			echo '<div class="toolbar cf">';
				echo '<a href="#" class="toggle_panel" name="toggle_panel" ></a>';
				echo common::Link('Admin_Main','','','class="icon_admin_home"');

				//echo '<a href="#" class="icon_toggle_edit" name="toggle_edit" ></a>';

				echo '<img src="'.common::GetDir('/include/imgs/blank.gif').'" height="16" width="16"  alt="" class="extra admin_arrow_out"/>';
			echo '</div>';


			admin_tools::AdminPanelLinks(true,$new_versions);

		echo '</div>'; //end simplepanel

		echo "\n\n";

		admin_tools::InlineEditArea();

	}

	function AdminPanelLinks($in_panel=true,$new_versions=array()){
		global $langmessage, $page, $gpAdmin, $config;

		$group2 = '<div class="panelgroup2 in_window" %s>';


		//current page
		if( count($page->admin_links) > 0 ){
			echo '<div class="panelgroup">';

				if( !$in_panel ){
					echo '<span>'.$langmessage['Current Page'].'</span>';
					echo '<div class="panelgroup2">';
				}else{
					echo '<a href="#" class="toplink icon_page_gear" name="toplink" rel="cur">';
					echo '<span>'.$langmessage['Current Page'].'</span>';
					echo '</a>';

					if( !isset($gpAdmin['gpui_cur']) || $gpAdmin['gpui_cur'] ){
						echo '<div class="panelgroup2 in_window">';
					}else{
						echo '<div class="panelgroup2 in_window" style="display:none">';
					}
				}

				echo '<ul class="submenu">';
				echo '<li class="submenu_top"><a class="submenu_top">'.$langmessage['Current Page'].'</a></li>';
				foreach($page->admin_links as $label => $link){
					echo '<li>';

						if( is_array($link) ){
							echo call_user_func_array(array('common','Link'),$link); /* preferred */

						}elseif( is_numeric($label) ){
							echo $link; //just a text label

						}elseif( empty($link) ){
							echo '<span>';
							echo $label;
							echo '</span>';

						}else{
							echo '<a href="'.$link.'">';
							echo $label;
							echo '</a>';
						}

					echo '</li>';
				}
				echo '</ul>';
				echo '</div>';

			echo '</div>';
		}


		//content
		if( $links = admin_tools::GetAdminGroup('content') ){
			echo '<div class="panelgroup">';

				$label = '<span>'.$langmessage['Content'].'</span>';
				if( !$in_panel ){
					echo '<span class="icon_page">'.$label.'</span>';
					echo '<div class="panelgroup2">';
				}else{
					echo '<a href="#" class="toplink icon_page" name="toplink" rel="con">';
					echo $label;
					echo '</a>';

					if( !isset($gpAdmin['gpui_con']) || $gpAdmin['gpui_con'] ){
						echo '<div class="panelgroup2 in_window">';
					}else{
						echo '<div class="panelgroup2 in_window" style="display:none">';
					}
				}

				echo '<ul class="submenu">';
				echo '<li class="submenu_top"><a class="submenu_top">'.$langmessage['Content'].'</a></li>';
				echo $links;
				echo '</ul>';
				echo '</div>';
			echo '</div>';
		}


		//appearance
		if( $links = admin_tools::GetAdminGroup('appearance') ){
			echo '<div class="panelgroup">';

				$label = '<span>'.$langmessage['Appearance'].'</span>';
				if( !$in_panel ){
					echo '<span class="icon_app">'.$label.'</span>';
					echo '<div class="panelgroup2">';
				}else{
					echo '<a href="#" class="toplink icon_app" name="toplink" rel="app">';
					echo $label;
					echo '</a>';

					if( isset($gpAdmin['gpui_app']) && $gpAdmin['gpui_app'] ){
						echo '<div class="panelgroup2 in_window">';
					}else{
						echo '<div class="panelgroup2 in_window" style="display:none">';
					}
				}


				echo '<ul class="submenu">';
				echo '<li class="submenu_top"><a class="submenu_top">'.$langmessage['Appearance'].'</a></li>';
				echo $links;
				echo '</ul>';
				echo '</div>';
			echo '</div>';
		}


		//add-ons
		$links = admin_tools::GetAddonLinks($in_panel);
		if( !empty($links) ){
			echo '<div class="panelgroup">';

				$label = '<span>'.$langmessage['plugins'].'</span>';
				if( !$in_panel ){
					echo '<span class="icon_plug">'.$label.'</span>';
					echo '<div class="panelgroup2">';
				}else{
					echo '<a href="#" class="toplink icon_plug" name="toplink" rel="add">';
					echo $label;
					echo '</a>';

					if( isset($gpAdmin['gpui_add']) && $gpAdmin['gpui_add'] ){
						echo '<div class="panelgroup2 in_window">';
					}else{
						echo '<div class="panelgroup2 in_window" style="display:none">';
					}
				}

				echo '<ul class="submenu">';
				echo '<li class="submenu_top"><a class="submenu_top">'.$langmessage['plugins'].'</a></li>';
				echo $links;
				echo '</ul>';
				echo '</div>';
			echo '</div>';
		}


		//settings
		if( $links = admin_tools::GetAdminGroup('settings') ){
			echo '<div class="panelgroup">';

				$label = '<span>'.$langmessage['Settings'].'</span>';
				if( !$in_panel ){
					echo '<span class="icon_cog">'.$label.'</span>';
					echo '<div class="panelgroup2">';
				}else{
					echo '<a href="#" class="toplink icon_cog" name="toplink" rel="set">';
					echo $label;
					echo '</a>';

					if( isset($gpAdmin['gpui_set']) && $gpAdmin['gpui_set'] ){
						echo '<div class="panelgroup2 in_window">';
					}else{
						echo '<div class="panelgroup2 in_window" style="display:none">';
					}
				}

				echo '<ul class="submenu">';
				echo '<li class="submenu_top"><a class="submenu_top">'.$langmessage['Settings'].'</a></li>';
				echo $links;
				echo '</ul>';
				echo '</div>';
			echo '</div>';
		}


		//updates
		if( count($new_versions) > 0 ){
			echo '<div class="panelgroup">';

			$label = '<span>'.$langmessage['updates'].'</span>';
			if( !$in_panel ){
				echo '<span class="icon_rfrsh">'.$label.'</span>';
				echo '<div class="panelgroup2">';
			}else{
				echo '<a href="#" class="toplink icon_rfrsh" name="toplink" rel="upd">';
				echo $label;
				echo '</a>';

				if( isset($gpAdmin['gpui_upd']) && $gpAdmin['gpui_upd'] ){
					echo '<div class="panelgroup2 in_window">';
				}else{
					echo '<div class="panelgroup2 in_window" style="display:none">';
				}
			}

			echo '<ul class="submenu">';
			echo '<li class="submenu_top"><a class="submenu_top">'.$langmessage['updates'].'</a></li>';


			if( isset($new_versions['core']) ){
				echo '<li>';
				echo '<a href="'.common::GetDir('/include/install/update.php').'">gpEasy '.$new_versions['core'].'</a>';
				echo '</li>';
			}

			foreach($new_versions as $addon_id => $new_addon_info){
				if( !is_numeric($addon_id) ){
					continue;
				}

				if( $new_addon_info['type'] == 'theme' ){
					echo '<li>';
					echo '<a href="'.$GLOBALS['addonBrowsePath'].'/Special_Addon_Themes?id='.$addon_id.'" name="remote">';
				}elseif( $new_addon_info['type'] == 'plugin' ){
					echo '<li>';
					echo '<a href="'.$GLOBALS['addonBrowsePath'].'/Special_Addon_Plugins?id='.$addon_id.'" name="remote">';
				}else{
					continue;
				}

				echo $new_addon_info['name'];
				echo ':  ';
				echo $new_addon_info['version'];
				echo '</a>';
				echo '</li>';
			}
			echo '</ul>';

			echo '</div>';
			echo '</div>';
		}


		//username
		echo '<div class="panelgroup">';

			$label = '<span>'.$gpAdmin['username'].'</span>';
			if( !$in_panel ){
				echo '<span class="icon_user">'.$label.'</span>';
				echo '<div class="panelgroup2">';
			}else{
				echo '<a href="#" class="toplink icon_user" name="toplink" rel="use">';
				echo $label;
				echo '</a>';

				if( isset($gpAdmin['gpui_use']) && $gpAdmin['gpui_use'] ){
					echo '<div class="panelgroup2 in_window">';
				}else{
					echo '<div class="panelgroup2 in_window" style="display:none">';
				}
			}

			echo '<ul class="submenu">';
			echo '<li class="submenu_top"><a class="submenu_top">'.$gpAdmin['username'].'</a></li>';
			admin_tools::GetFrequentlyUsed($in_panel);

			echo '<li>';
			echo common::Link('Admin_Preferences',$langmessage['Preferences'],'','name="admin_box"');
			echo '</li>';

			echo '<li>';
			echo common::Link($page->title,$langmessage['logout'],'cmd=logout',' name="creq" ');
			echo '</li>';

			echo '<li>';
			echo common::Link('Admin_About','About gpEasy','',' name="admin_box" ');
			echo '</li>';
			echo '</ul>';
			echo '</div>';

		echo '</div>';
	}

	function InlineEditArea(){
		global $langmessage;

		//inline editor html
		echo '<div id="ckeditor_wrap" style="display:none">';
		echo '<div id="ckeditor_area" class="gp_floating_area">';
		echo '<div class="cf">';
			echo '<div class="toolbar">';
				echo '<div class="right">';
				echo '<img src="'.common::GetDir('/include/imgs/blank.gif').'" height="16" width="16"  alt="" class="admin_arrow_out"/>';
				echo '<a class="docklink" name="ck_docklink"></a>';
				echo '</div>';
			echo '</div>';

			echo '<div class="tools">';

			echo '<div id="ckeditor_top"></div>';

			echo '<div id="ckeditor_controls">';
			echo '<a href="#" name="ck_save" class="ckeditor_control">'.$langmessage['save'].'</a>';
			echo '<a href="#" name="ck_close" class="ckeditor_control">'.$langmessage['Close'].'</a>';
			echo '<a href="#" name="ck_save" rel="ck_close" class="ckeditor_control">'.$langmessage['Save & Close'].'</a>';
			echo '</div>';

			echo '<div id="ckeditor_bottom"></div>';

			echo '</div>';

		echo '</div>';
		echo '</div>';
		echo '</div>';


	}

	function GetFrequentlyUsed($in_panel){
		global $langmessage,$gpAdmin;

		//frequently used
		echo '<li class="expand_child">';
			echo '<a href="#">';
			echo $langmessage['frequently_used'];
			echo '</a>';
			if( $in_panel ){
				echo '<ul class="in_window">';
			}else{
				echo '<ul>';
			}
			$scripts = admin_tools::AdminScripts();
			$add_one = true;
			if( isset($gpAdmin['freq_scripts']) ){
				foreach($gpAdmin['freq_scripts'] as $link => $hits ){
					if( isset($scripts[$link]) ){
						echo '<li>';
						echo common::Link($link,$scripts[$link]['label']);
						echo '</li>';
						if( $link === 'Admin_Menu' ){
							$add_one = false;
						}
					}
				}
				if( $add_one && count($gpAdmin['freq_scripts']) >= 5 ){
					$add_one = false;
				}
			}
			if( $add_one ){
				echo '<li>';
				echo common::Link('Admin_Menu',$scripts['Admin_Menu']['label']);
				echo '</li>';
			}
			echo '</ul>';
		echo '</li>';
	}

	function AdminContentPanel(){
		global $page;

		//the login form does not need the panel
		if( !common::LoggedIn() ){
			return;
		}

		echo '<div id="admincontent_panel" class="toolbar">';
		echo '<div class="right">';
		echo '<img src="'.common::GetDir('/include/imgs/blank.gif').'" height="16" width="16"  alt="" class="admin_arrow_out"/>';
		echo '<a class="docklink" name="gp_docklink"></a>';
		echo '</div>';
		echo common::Link('Admin_Main','gp|Easy Administration');
		if( !empty($page->title) && !empty($page->label) && $page->title != 'Admin_Main' ){
			echo ' » ';
			echo common::Link($page->title,$page->label);
		}
		echo '</div>';

	}


	//uses $status from update codes to execute some cleanup code on a regular interval (7 days)
	function ScheduledTasks($status){
		global $dataDir;


		switch($status){
			case 'embedcheck':
			case 'checkincompat':
				//these will continue
			break;

			case 'checklater':
			default:
			return;
		}

		//clean cache
		//delete files older than 2 weeks, they will be regenerated if needed
		$cache_dir = $dataDir.'/data/_cache';
		$cache_files = gpFiles::ReadDir($cache_dir,'css');
		$time = time();
		foreach($cache_files as $file){
			$full_path = $cache_dir.'/'.$file.'.css';
			if( $time - filemtime($full_path) > 1209600 ){
				@unlink($full_path);
			}
		}

	}


	//admin_tools::AdminHtml();
	function AdminHtml(){
		global $langmessage;

		includeFile('install/update_class.php');
		$update_status = update_class::VersionsAndCheckTime($new_versions);

		echo '<div id="gp_admin_html">';

			echo '<div id="loading1" style="display:none"></div>';
			echo '<div id="loading2" style="display:none"></div>';

			admin_tools::GetAdminPanel($new_versions);
			echo '<div style="display:none" id="gp_hidden"></div>';

		echo '</div>';

		admin_tools::CheckStatus($update_status);
		admin_tools::ScheduledTasks($update_status);

	}

	function CheckStatus($status){

		switch($status){
			case 'embedcheck':
				$img_path = common::GetUrl('Admin_Main','cmd=embededcheck');
				common::IdReq($img_path);
			break;
			case 'checkincompat':
				$img_path = common::IdUrl('ci'); //check in
				common::IdReq($img_path);
			break;
		}
	}



	/*
	 * @deprecated 2.3.1
	 */
	function GetAdminLinks($type=false){
		global $langmessage;

		$scripts = admin_tools::AdminScripts();

		$count = 0;
		$addon = false;
		echo '<ul>';
		foreach($scripts as $script => $info){
			if( isset($info['list']) && ($info['list'] === false) ){
				continue;
			}
			if( admin_tools::HasPermission($script) ){
				$class = '';
				if( isset($info['addon']) ){
					if( $addon == false ){
						$class = ' class="seperator" ';
					}
					$addon = true;
				}elseif( $addon ){
					$class = ' class="seperator" ';
				}

				echo '<li '.$class.'>';
				echo common::Link($script,$info['label']);
				echo '</li>';
				$count++;
			}
		}

		if( $count < 1 ){
			echo '<li>';
			echo common::Link('Admin_Preferences',$langmessage['Preferences']);
			echo '</li>';
		}
		echo '</ul>';
	}

	function GetAdminGroup($grouping){
		global $langmessage,$page;

		$scripts = admin_tools::AdminScripts();

		ob_start();
		foreach($scripts as $script => $info){
			if( isset($info['list']) && ($info['list'] === false) ){
				continue;
			}

			if( !isset($info['group']) || (strpos($info['group'],$grouping) === false) ){
				continue;
			}

			if( !admin_tools::HasPermission($script) ){
				continue;
			}
			echo '<li>';

			if( isset($info['popup']) && $info['popup'] == true ){
				echo common::Link($script,$info['label'],'','name="admin_box"');
			}else{
				echo common::Link($script,$info['label']);
			}

			echo '</li>';

			switch($script){
				case 'Admin_Menu':
					echo '<li>';
					echo common::Link('Admin_Menu','+ '.$langmessage['create_new_file'],'cmd=add_hidden&redir=redir',' title="'.$langmessage['create_new_file'].'" name="gpajax"');
					echo '</li>';
				break;
			}

		}


		//add more links
		switch($grouping){
			case 'appearance':
				if( !empty($page->gpLayout) && admin_tools::HasPermission('Admin_Theme_Content') ){
					echo '<li>';
					echo common::Link('Admin_Theme_Content',$langmessage['edit_this_layout'],'cmd=editlayout&layout='.urlencode($page->gpLayout));
					echo '</li>';
					echo '<li>';
					echo common::Link('Admin_Theme_Content',$langmessage['layout details'],'cmd=showdetails&layout='.urlencode($page->gpLayout));
					echo '</li>';
				}
				echo '<li>';
				echo '<a href="'.$GLOBALS['addonBrowsePath'].'/Special_Addon_Themes" name="remote">'.$langmessage['Download Themes'].'</a>';
				echo '</li>';
			break;
		}




		$result = ob_get_clean();
		if( !empty($result) ){
			return $result;
		}
		return false;
	}



	function CheckPostedNewPage($title=false){
		global $langmessage,$gp_index;

		if( $title === false ){
			$title = $_POST['title'];
		}

		$title = gpFiles::CleanTitle($title);

		if( isset($gp_index[$title]) ){
			message($langmessage['TITLE_EXISTS']);
			return false;
		}
		if( empty($title) ){
			message($langmessage['TITLE_REQUIRED']);
			return false;
		}

		$type = common::SpecialOrAdmin($title);
		if( $type !== false ){
			message($langmessage['TITLE_RESERVED']);
			return false;
		}

		//check for case
		$titles_lower = array_change_key_case($gp_index,CASE_LOWER);
		$title_lower = strtolower($title);
		if( isset($titles_lower[$title_lower]) ){
			message($langmessage['TITLE_EXISTS']);
			return false;
		}


		if( strlen($title) > 60 ){
			message($langmessage['LONG_TITLE']);
			return false;
		}
		return $title;
	}


	//
	//	functions for gp_menu, gp_titles
	//

	//admin_tools::SaveAllConfig();
	function SaveAllConfig(){
		if( !admin_tools::SaveConfig() ){
			return false;
		}

		if( !admin_tools::SavePagesPHP() ){
			return false;
		}
		return true;
	}

	/**
	 * Save the gpEasy configuration
	 * @return bool
	 *
	 */
	function SavePagesPHP(){
		global $gp_index, $gp_titles, $gp_menu, $gpLayouts, $dataDir;

		if( !is_array($gp_menu) || !is_array($gp_index) || !is_array($gp_titles) || !is_array($gpLayouts) ){
			return false;
		}

		$pages = array();
		$pages['gp_menu'] = $gp_menu;
		$pages['gp_index'] = $gp_index;
		$pages['gp_titles'] = $gp_titles;
		$pages['gpLayouts'] = $gpLayouts;

		if( !gpFiles::SaveArray($dataDir.'/data/_site/pages.php','pages',$pages) ){
			return false;
		}
		return true;

	}

	/**
	 * Save the gpEasy configuration
	 * @return bool
	 *
	 */
	function SaveConfig(){
		global $config, $dataDir;

		if( !is_array($config) ) return false;

		if( !isset($config['gpuniq']) ) $config['gpuniq'] = common::RandomString(20);

		return gpFiles::SaveArray($dataDir.'/data/_site/config.php','config',$config);
	}


	/**
	 * @deprecated
	 */
	function tidyFix(&$text){
		trigger_error('tidyFix should be called using gpFiles::tidyFix() instead of admin_tools:tidyFix()');
		return false;
	}



	/**
	 * Return the addon section of the admin panel
	 *
	 */
	function GetAddonLinks($in_panel){
		global $langmessage, $config;

		ob_start();

		$addon_permissions = admin_tools::HasPermission('Admin_Addons');

		if( $addon_permissions ){
			echo '<li>';
			echo common::Link('Admin_Addons',$langmessage['manage']);
			echo '</li>';
			echo '<li class="seperator">';
			echo '<a href="'.$GLOBALS['addonBrowsePath'].'/Special_Addon_Plugins" name="remote">'.$langmessage['Download Plugins'].'</a>';
			echo '</li>';
		}


		$show =& $config['addons'];
		if( is_array($show) ){

			foreach($show as $addon => $info){

				//backwards compat
				if( is_string($info) ){
					$addonName = $info;
				}elseif( isset($info['name']) ){
					$addonName = $info['name'];
				}else{
					$addonName = $addon;
				}

				$sublinks = admin_tools::GetAddonSubLinks($addon);

				if( !empty($sublinks) ){
					echo '<li class="expand_child">';
					if( $in_panel ){
						$sublinks = '<ul class="in_window">'.$sublinks.'</ul>';
					}else{
						$sublinks = '<ul>'.$sublinks.'</ul>';
					}
				}else{
					echo '<li>';
				}

				if( $addon_permissions ){
					echo common::Link('Admin_Addons',$addonName,'cmd=show&addon='.$addon);
				}else{
					echo '<a href="#">'.$addonName.'<a>';
				}
				echo $sublinks;

				echo '</li>';
			}
		}


		return ob_get_clean();

	}



	/**
	 * Determine if the installation should be allowed to process remote installations
	 *
	 */
	function CanRemoteInstall(){
		static $bool;

		if( isset($bool) ){
			return $bool;
		}

		includeFile('tool/RemoteGet.php');

		$bool = true;

		if( !gpRemoteGet::Test() ){
			$bool = false;
		}

		//used by pclzip
		if( !function_exists('gzinflate') ){
			$bool = false;
		}

		if( defined('gp_remote_addons') && gp_remote_addons === false ){
			$bool = false;
		}

		return $bool;
	}


	/**
	 * Return a formatted list of links associated with $addon
	 * @return string
	 */
	function GetAddonSubLinks($addon=false){
		global $config;

		$special_links = admin_tools::GetAddonTitles( $addon);
		$admin_links = admin_tools::GetAddonComponents( $config['admin_links'], $addon);


		$result = '';
		foreach($special_links as $linkName => $linkInfo){
			$result .= '<li>';
			$result .= common::Link($linkName,$linkInfo['label']);
			$result .= '</li>';
		}

		foreach($admin_links as $linkName => $linkInfo){
			if( admin_tools::HasPermission($linkName) ){
				$result .= '<li>';
				$result .= common::Link($linkName,$linkInfo['label']);
				$result .= '</li>';
			}
		}
		return $result;
	}




	/**
	 * Get the titles associate with $addon
	 * Similar to GetAddonComponents(), but built for $gp_titles
	 * @return array List of addon links
	 *
	 */
	function GetAddonTitles($addon){
		global $gp_index, $gp_titles;

		$sublinks = array();
		foreach($gp_index as $slug => $id){
			$info = $gp_titles[$id];
			if( !is_array($info) ){
				continue;
			}
			if( !isset($info['addon']) ){
				continue;
			}
			if( $info['addon'] !== $addon ){
				continue;
			}
			$sublinks[$slug] = $info;
		}
		return $sublinks;
	}

	/**
	 * Get the admin titles associate with $addon
	 * @return array List of addon links
	 *
	 */
	function GetAddonComponents($from,$addon){
		if( !is_array($from) ){
			return;
		}

		$result = array();
		foreach($from as $name => $value){
			if( !is_array($value) ){
				continue;
			}
			if( !isset($value['addon']) ){
				continue;
			}
			if( $value['addon'] !== $addon ){
				continue;
			}
			$result[$name] = $value;
		}

		return $result;
	}


}

