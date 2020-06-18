<?php
defined('is_running') or die('Not an entry point...');


/*
what can be moved?
	* .editable_area

How do we position elements?
	* above, below in relation to another editable_area

How do we do locate them programatically
	* We need to know the calling functions that output the areas
		then be able to organize a list of output functions within each of the calling functions
		!each area is represented by a list, either a default value if an override hasn't been defined, or the custom list created by the user

How To Identify the Output Functions for the Output Lists?
	* Gadgets have:
		$info['script']
		$info['data']
		$info['class']


$gpOutConf = array() of output functions/classes.. to use with the theme content
	==potential values==
	$gpOutConf[-ident-]['script'] = -path relative to datadir or rootdir?
	$gpOutConf[-ident-]['data'] = -path relative to datadir-
	$gpOutConf[-ident-]['class'] = -path relative to datadir or rootdir?
	$gpOutConf[-ident-]['method'] = string or array: string=name of function, array(class,method)


	$gpLayout['Loyout_Name']['handlers'][-ident-] = array(0=>-ident-,1=>-ident-)
	$gpLayout['Loyout_Name']['color'] = '#123456'
	$gpLayout['Loyout_Name']['theme'] = 'One_Point_5/Blue'

*/

//includeFile('admin/admin_menu_tools.php');
includeFile('admin/admin_addon_install.php');

class admin_theme_content extends admin_addon_install{

	var $curr_layout;
	var $LayoutArray;


	//remote install variables
	var $config_index = 'themes';
	var $addon_folder_name = '_themes';
	var $browser_path = 'Admin_Theme_Content';
	var $can_install_links = false;


	function admin_theme_content(){
		global $page,$config,$gpLayouts;

		//message('request: '.showArray($_REQUEST));

		$GLOBALS['GP_ARRANGE_CONTENT'] = true;

		$page->head_js[] = '/include/js/theme_content.js';
		$page->head_js[] = '/include/js/dragdrop.js';

		$page->css_admin[] = '/include/css/theme_content.css';

		$this->curr_layout = $config['gpLayout'];

		$this->SetLayoutArray();

		$cmd = common::GetCommand();
		switch($cmd){

			//remote themes
			case 'remote_install':
			case 'remote_install2':
			case 'remote_install3':
				$this->RemoteInstallMain($cmd);
			return;

			case 'deletetheme':
				$this->DeleteTheme();
			return;

			case 'delete_theme_confirmed':
				$this->DeleteThemeConfirmed();
			break;


			//adminlayout
			case 'adminlayout':
				$this->AdminLayout();
			return;

			//theme ratings
			case 'Update Review';
			case 'Send Review':
			case 'rate':
				includeFile('admin/admin_addons_tool.php');
				$rating = new admin_addons_tool();
				$rating->admin_addon_rating('theme','Admin_Theme_Content');
				if( $rating->ShowRatingText ){
					return;
				}
			break;

			//new layouts
			case 'preview':
				if( $this->PreviewTheme() ){
					return;
				}
			break;
			case 'newlayout':
				$this->NewLayout();
			break;
			//copy
			case 'copy':
				$this->CopyLayout();
			break;



			//editing layouts
			case 'layout_details_drag':
			case 'restore_drag':
			case 'addcontent':
			case 'drag':
			case 'editlayout':
			case 'rm':
			case 'insert':
				if( $this->EditLayout($cmd) ){
					return;
				}
			break;


			case 'restore':
			case 'rmgadget':
			case 'showdetails':
			case 'layout_details_show':
			case 'change_layout_color':
				if( $this->EditLayout($cmd,false) ){
					return;
				}
			break;



			//layout options
			case 'makedefault':
				$this->MakeDefault();
			break;
			case 'deletelayout':
				$this->DeleteLayout();
			return;
			case 'deletelayoutconfirmed':
				$this->DeleteLayoutConfirmed();
			break;

			case 'layout_details':
				$this->LayoutDetails();
			break;


			//links
			case 'editlinks':
			case 'editcustom':
				$this->SelectLinks();
			return;
			case 'savelinks':
				$this->SaveLinks();
			break;

			//text
			case 'edittext':
				$this->EditText();
			return;
			case 'savetext':
				$this->SaveText();
			break;


			case 'saveaddontext':
				$this->SaveAddonText();
			break;
			case 'addontext':
				$this->AddonText();
			return;


		}

		//message(showArray($_GET));
		$this->Show();
	}


	function AdminLayout(){
		global $langmessage;

		echo '<div class="inline_box">';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';

		$admin_layout = $langmessage['default'];
		echo '<h2>'.'Admin Layout'.'</h2>';

		echo '<select name="">';
			echo '<option value="">'.$langmessage['default'].'</option>';
		echo '</select>';
		echo ' <input type="submit" name="" value="'.$langmessage['continue'].'" />';

		echo '</form>';
		echo '</div>';
	}


	/**
	 * Edit layout properties
	 * 		Layout Identification
	 * 		Content Arrangement
	 * 		Gadget Visibility
	 *
	 */
	function EditLayout($cmd, $drag_n_drop = true ){
		global $page,$gpLayouts,$langmessage,$config,$GP_GETALLGADGETS;


		if( !isset($_REQUEST['layout']) ){
			$layout = display::OrConfig(false,'gpLayout'); //will get the default layout
		}else{
			$layout = $_REQUEST['layout'];
		}

		if( !isset($gpLayouts[$layout]) ){
			message($langmessage['OOPS'].' (Invalid Layout)');
			return false;
		}

		if( $drag_n_drop ){
			$page->head .= "\n".'<script type="text/javascript">var gpLayouts=true;</script>';
			$page->head_js[] = '/include/js/inline_edit/inline_editing.js';
			$page->show_admin_content = false;
		}


		$this->curr_layout = $layout;
		$page->SetTheme($layout);
		$this->LoremIpsum();

		gpOutput::TemplateSettings();

		gpPlugin::Action('edit_layout_cmd',array($layout));

		switch($cmd){
			case 'change_layout_color':
				$this->ChangeLayoutColor($layout);
			break;

			case 'rmgadget':
				$this->RmGadget();
			break;

			case 'layout_details_drag':
			case 'layout_details_show':
				$this->LayoutDetails();
			break;

			case 'restore_drag':
			case 'restore':
				$this->Restore();
			break;
			case 'drag':
				$this->Drag();
			break;

			//insert
			case 'insert':
				$this->SelectContent();
			return true;

			case 'addcontent':
				$this->AddContent();
			break;

			//remove
			case 'rm':
				$this->RemoveArea();
			break;

		}


		$layout_info = common::LayoutInfo($layout);
		$handlers_count = 0;
		if( isset($layout_info['handlers']) && is_array($layout_info['handlers']) ){
			foreach($layout_info['handlers'] as $val){
				$int = count($val);
				if( $int === 0){
					$handlers_count++;
				}
				$handlers_count += $int;
			}
		}


		//not showing the content if it's drag n drop
		if( $drag_n_drop ){
			$this->DragDropNote( $layout, $layout_info, $handlers_count );
			return true;
		}


		echo common::Link('Admin_Theme_Content','« '.$langmessage['layouts'],'',' style="float:right" ');

		echo '<h2>';
		echo common::Link('Admin_Theme_Content',$langmessage['layouts']);
		echo ' » ';
		echo $langmessage['edit_this_layout'];
		//echo $langmessage['current_layout'];
		echo '</h2>';


		echo '<p class="gp_notice" id="getallgadgets_warning" style="display:none">';
		echo 'You may need to change the $GP_GETALLGADGETS setting in the settings.php file for this theme.';
		echo '</p>';

		echo '<div>';
		echo '</div>';

		//layout options
		echo '<table class="bordered" style="width:100%">';
		echo '<tr><th colspan="2">';
		echo $langmessage['current_layout'];
		echo '</th></tr>';

		echo '<tr><td>';
		echo $langmessage['label'];
		echo '</td><td>';
		echo '<a href="#" name="layout_id" title="'.$layout_info['color'].'" rel="'.$layout_info['color'].'">';
		echo '<input type="hidden" name="layout" value="'.htmlspecialchars($layout).'"  /> ';
		echo '<input type="hidden" name="layout_label" value="'.$layout_info['label'].'"  /> ';
		echo '<span class="layout_color_id" style="background-color:'.$layout_info['color'].';"></span>';
		echo '&nbsp;';
		echo $layout_info['label'];
		echo '</a>';
		echo '</td></tr>';

		echo '<tr><td>';
		echo $langmessage['theme'];
		echo '</td><td>';
		echo $layout_info['theme_name'];
		echo '</td></tr>';

		echo '<tr><td>';
		echo $langmessage['usage'];
		echo '</td><td>';
			if( $config['gpLayout'] == $layout ){
				echo $langmessage['default'];
			}else{
				echo common::Link('Admin_Theme_Content',str_replace(' ','&nbsp;',$langmessage['make_default']),'cmd=makedefault&layout_id='.rawurlencode($layout),' name="creq" title="'.htmlspecialchars($langmessage['make_default']).'" ');
			}
			echo ' &nbsp; ';

			$titles_count = $this->TitlesCount($layout);
			echo sprintf($langmessage['%s Pages'],$titles_count);
		echo '</td></tr>';



		$theme_colors = $this->GetThemeColors($layout_info['dir']);
		echo '<tr><td>';
		echo $langmessage['style'];
		echo '</td><td>';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
		echo '<select name="color">';
		foreach($theme_colors as $color){
			if( $color == $layout_info['theme_color'] ){
				echo '<option value="'.htmlspecialchars($color).'" selected="selected">';
			}else{
				echo '<option value="'.htmlspecialchars($color).'">';
			}
			echo $color;
			echo '</option>';
		}
		echo '</select>';
		echo ' <input type="hidden" name="layout" value="'.htmlspecialchars($layout).'" />';
		echo ' <input type="hidden" name="cmd" value="change_layout_color" />';
		echo ' <input type="submit" name="" value="'.htmlspecialchars($langmessage['save_changes']).'" />';
		echo '</form>';
		echo '</td></tr>';


		echo '<tr><td>';
		echo $langmessage['content_arrangement'];
		echo '</td><td>';
		echo common::Link('Admin_Theme_Content',$langmessage['DRAG-N-DROP-DESC'],'cmd=editlayout&layout='.rawurlencode($layout));
		if( $handlers_count > 0 ){
			//use layout_id so that the display doesn't change
			echo ' - ';
			echo common::Link('Admin_Theme_Content',$langmessage['restore_defaults'],'cmd=restore&layout='.rawurlencode($layout),' name="creq" ');
		}
		echo '</td></tr>';

		$gadget_info = gpOutput::WhichGadgets($this->curr_layout,$GP_GETALLGADGETS);
		echo '<tr><th colspan="2">';
		echo $langmessage['gadgets'];
		echo '</th></tr>';
		if( !isset($config['gadgets']) || count($config['gadgets']) == 0 ){
			echo '<tr><td colspan="2">';
			echo $langmessage['Empty'];
			echo '</td></tr>';
		}else{
			foreach($config['gadgets'] as $gadget => $temp){
				echo '<tr><td>';
				echo str_replace('_',' ',$gadget);
				echo '</td><td>';
				if( isset($gadget_info[$gadget]) ){
					echo common::Link('Admin_Theme_Content',$langmessage['remove'],'cmd=rmgadget&gadget='.urlencode($gadget).'&layout='.rawurlencode($layout),' name="creq" ');
				}else{
					echo $langmessage['disabled'];
				}
				echo '</td></tr>';
			}
		}

		echo '</table>';
		echo '<br/>';

		gpPlugin::Action('edit_layout_mid',array($layout_info));

		$titles_count = $this->TitlesCount($layout);
		echo '<div class="collapsible">';
		echo '<h4 class="head"><a href="#" name="collapsible">';
		echo $langmessage['titles_using_layout'];
		echo ': '.$titles_count;
		echo '</a></h4>';

		if( $titles_count > 0 ){
			echo '<ul class="titles_using">';

			foreach( $this->LayoutArray as $index => $layout_comparison ){
				if( $layout == $layout_comparison ){

					$title = common::IndexToTitle($index);
					if( empty($title) ){
						continue; //may be external link
					}

					echo "\n<li>";
					echo common::Link($title,common::GetLabel($title));
					echo '</li>';
				}
			}

			echo '</ul>';
			echo '<div class="clear"></div>';
		}
		echo '</div>';

		gpPlugin::Action('edit_layout_end',array($layout_info));


		$this->ColorSelector('layout_details_show');

		return true;
	}



	/*
	 * Get the content of the drag and drop window
	 *
	 */
	function DragDropNote($layout, $layout_info, $handlers_count ){
		global $gp_menu, $gp_titles, $langmessage, $page;

		ob_start();

		echo '<div style="display:none" id="gp_drag_n_drop" class="gp_floating_area"><div><div>';

		echo '<h3>'.$langmessage['Arrange Content'].'</h3>';

		echo '<p>';
		echo $langmessage['DRAG-N-DROP-DESC'];
		echo '</p>';

		echo '<p>';

		echo '<a href="#" name="layout_id" title="'.$layout_info['color'].'" rel="'.$layout_info['color'].'">';
		echo '<input type="hidden" name="layout" value="'.htmlspecialchars($layout).'"  /> ';
		echo '<input type="hidden" name="layout_label" value="'.$layout_info['label'].'"  /> ';
		echo '<span class="layout_color_id" style="background-color:'.$layout_info['color'].';"></span>';
		echo '&nbsp;';
		echo $layout_info['label'];
		echo '</a>';


		echo ' &nbsp; ';
		echo common::Link('Admin_Theme_Content',$langmessage['details'],'cmd=showdetails&layout='.rawurlencode($layout));
		echo '</p>';


		echo '<p>';
		if( $handlers_count > 0 ){
			echo common::Link('Admin_Theme_Content',$langmessage['restore_defaults'],'cmd=restore_drag&layout='.rawurlencode($layout),' name="creq" ');
		}
		echo '</p>';




/*
		echo '<ul>';

		echo '<li>'.common::Link('Admin_Theme_Content',$langmessage['layouts']).'</li>';

		reset($gp_menu);
		$homepage_key = key($gp_menu);
		$homepage = $gp_titles[$homepage_key];
		echo '<li>'.common::Link('',$homepage['label']).'</li>';

		echo '</ul>';
*/


		//$referer = $_SERVER['HTTP_REFERER'];
		//echo $referer;

		echo '</div></div></div>';

		$this->ColorSelector('layout_details_drag');

		$page->non_admin_content .= ob_get_clean();
	}







	/**
	 * Change the color variant for $layout
	 */
	function ChangeLayoutColor($layout){
		global $langmessage,$gpLayouts,$page;

		$color =& $_REQUEST['color'];
		$layout_info = common::LayoutInfo($layout);
		$theme_colors = $this->GetThemeColors($layout_info['dir']);

		if( !isset($theme_colors[$color]) ){
			message($langmessage['OOPS'].' (Invalid Color)');
			return false;
		}

		$old_info = $new_info = $gpLayouts[$layout];
		$theme_name = dirname($new_info['theme']);
		$new_info['theme'] = $theme_name.'/'.$color;
		$gpLayouts[$layout] = $new_info;

		if( admin_tools::SavePagesPHP() ){
			$page->SetTheme($layout);
		}else{
			$gpLayouts[$layout] = $old_info;
			message($langmessage['OOPS'].' (Not Saved)');
		}
	}


	/**
	 * Remove a gadget from a layout
	 * @return null
	 */

	function RmGadget(){
		global $page,$langmessage,$gpLayouts;

		$gadget =& $_REQUEST['gadget'];
		$layout =& $_REQUEST['layout'];

		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers,'GetAllGadgets','GetAllGadgets'); //make sure GetAllGadgets is set

		$changed = false;
		foreach($handlers as $container => $container_info){
			foreach($container_info as $key => $gpOutCmd){
				if( $gpOutCmd == $gadget ){
					$changed = true;
					unset($handlers[$container][$key]);
				}
			}
		}

		if( !$changed ){
			message($langmessage['OOPS'].' (Not Changed)');
			return;
		}

		$this->SaveHandlersNew($handlers);
	}


	function GetRandColor(){
		$colors = $this->GetColors();
		$color_key = array_rand($colors);
		return $colors[$color_key];
	}

	function GetColors(){

		//color/layout_id changing
		$colors = array();

		$colors[] = '#ff0000';
		$colors[] = '#ff9900';
		$colors[] = '#ffff00';
		$colors[] = '#00ff00';
		$colors[] = '#00ffff';
		$colors[] = '#0000ff';
		$colors[] = '#9900ff';
		$colors[] = '#ff00ff';

		$colors[] = '#f4cccc';
		$colors[] = '#fce5cd';
		$colors[] = '#fff2cc';
		$colors[] = '#d9ead3';
		$colors[] = '#d0e0e3';
		$colors[] = '#cfe2f3';
		$colors[] = '#d9d2e9';
		$colors[] = '#ead1dc';


		$colors[] = '#ea9999';
		$colors[] = '#f9cb9c';
		$colors[] = '#ffe599';
		$colors[] = '#b6d7a8';
		$colors[] = '#a2c4c9';
		$colors[] = '#9fc5e8';
		$colors[] = '#b4a7d6';
		$colors[] = '#d5a6bd';

		$colors[] = '#e06666';
		$colors[] = '#f6b26b';
		$colors[] = '#ffd966';
		$colors[] = '#93c47d';
		$colors[] = '#76a5af';
		$colors[] = '#6fa8dc';
		$colors[] = '#8e7cc3';
		$colors[] = '#c27ba0';


		$colors[] = '#cc0000';
		$colors[] = '#e69138';
		$colors[] = '#f1c232';
		$colors[] = '#6aa84f';
		$colors[] = '#45818e';
		$colors[] = '#3d85c6';
		$colors[] = '#674ea7';
		$colors[] = '#a64d79';


		$colors[] = '#990000';
		$colors[] = '#b45f06';
		$colors[] = '#bf9000';
		$colors[] = '#38761d';
		$colors[] = '#134f5c';
		$colors[] = '#0b5394';
		$colors[] = '#351c75';
		$colors[] = '#741b47';


/*		Too dark
		$colors[] = '#660000';
		$colors[] = '#783f04';
		$colors[] = '#7f6000';
		$colors[] = '#274e13';
		$colors[] = '#0c343d';
		$colors[] = '#073763';
		$colors[] = '#20124d';
		$colors[] = '#4c1130';
*/

		return $colors;
	}


	function NewLayout(){
		global $gpLayouts,$langmessage,$config,$page;

		$theme =& $_POST['theme'];
		$theme_info = $this->ThemeInfo($theme);
		if( $theme_info === false ){
			message($langmessage['OOPS']);
			return false;
		}


		$newLayout = array();
		$newLayout['theme'] = $theme_info['folder'].'/'.$theme_info['color'];
		$newLayout['color'] = $this->GetRandColor();
		if( $theme_info['is_addon'] ){
			$newLayout['is_addon'] = true;
			$newLayout['theme_label'] = $theme_info['name'].'/'.$theme_info['color'];
			$newLayout['label'] = substr($newLayout['theme_label'],0,15);
		}else{
			$newLayout['label'] = substr($newLayout['theme'],0,15);
		}


		do{
			$layout_id = rand(1000,9999);
		}while( isset($gpLayouts[$layout_id]) );

		$gpLayoutsBefore = $gpLayouts;
		$gpLayouts[$layout_id] = $newLayout;
		if( admin_tools::SavePagesPHP() ){
			message($langmessage['SAVED']);
		}else{
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS']);
		}


		if( !empty($_POST['default']) && $_POST['default'] != 'false' ){
			$config['gpLayout'] = $layout_id;
			admin_tools::SaveConfig();
			$page->SetTheme();
			$this->SetLayoutArray();
		}
	}



	function CopyLayout(){
		global $gpLayouts,$langmessage,$config,$page;

		$copy_id =& $_REQUEST['layout'];

		if( empty($copy_id) || !isset($gpLayouts[$copy_id]) ){
			message($langmessage['OOPS'].'(Invalid Request)');
			return;
		}

		$newLayout = $gpLayouts[$copy_id];
		$newLayout['color'] = $this->GetRandColor();
		$newLayout['label'] = admin_theme_content::NewLabel($newLayout['label']);

		//get new unique layout id
		do{
			$layout_id = rand(1000,9999);
		}while( isset($gpLayouts[$layout_id]) );


		$gpLayoutsBefore = $gpLayouts;
		$gpLayouts[$layout_id] = $newLayout;

		if( !gpFiles::ArrayInsert($copy_id,$layout_id,$newLayout,$gpLayouts,1) ){
			message($langmessage['OOPS'].'(Not Inserted)');
			return;
		}

		if( admin_tools::SavePagesPHP() ){
			message($langmessage['SAVED']);
		}else{
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS'].'(Not Saved)');
		}

	}


	/**
	 * Create a new unique layout label
	 * @static
	 */
	function NewLabel($label){
		global $gpLayouts;
		$labels = array();

		foreach($gpLayouts as $info){
			$labels[$info['label']] = true;
		}

		$len = strlen($label);
		if( $len > 15 ){
			$label = substr($label,0,$len-2);
		}
		if( substr($label,$len-2,1) === '_' && is_numeric(substr($label,$len-1,1)) ){
			$int = substr($label,$len-1,1);
			$label = substr($label,0,$len-2);
		}


		$int = 1;
		do{
			$new_label = $label.'_'.$int;
			$int++;
		}while( isset($labels[$new_label]) );

		return $new_label;
	}


	function PreviewTheme(){
		global $langmessage,$config,$page;

		$theme =& $_GET['theme'];
		$theme_info = $this->ThemeInfo($theme);
		if( $theme_info === false ){
			message($langmessage['OOPS']);
			return false;
		}

		$theme_id = dirname($theme);
		$template = $theme_info['folder'];
		$color = $theme_info['color'];
		$display = htmlspecialchars($theme_info['name'].'/'.$theme_info['color']);

		echo common::Link('Admin_Theme_Content','« '.$langmessage['layouts'],'',' style="float:right" ');

		echo '<h2>';
		echo common::Link('Admin_Theme_Content',$langmessage['layouts']);
		echo ' » ';
		//echo $langmessage['preview'];
		echo str_replace('_',' ',$display);
		echo '</h2>';

		echo '<p>';
		echo $langmessage['other_styles'].': ';
		$comma = '';
		foreach($theme_info['colors'] as $color_temp){
			echo $comma;
			echo common::Link('Admin_Theme_Content',$color_temp,'cmd=preview&theme='.rawurlencode($theme_id.'/'.$color_temp)); //,' name="creq" ');
			$comma = ', ';
		}
		echo '</p>';


		echo '<div class="theme_buttons">';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
		echo '<input type="hidden" name="cmd" value="newlayout" />';
		echo '<input type="hidden" name="default" value="false" />';
		echo '<input type="hidden" name="theme" value="'.htmlspecialchars($theme).'" />';
		echo '<input type="submit" class="theme_buttons" name="" value="'.htmlspecialchars($langmessage['use_this_theme']).'" />';
		echo '<input type="submit" class="theme_buttons" name="default" value="'.htmlspecialchars($langmessage['make_default_layout']).'" />';
		echo '</form>';
		echo '</div>';



		$this->ShowAvailable(false);

		$page->gpLayout = false;
		$page->theme_name = $template;
		$page->theme_color = $color;
		$page->theme_is_addon = $theme_info['is_addon'];
		$page->theme_dir = $theme_info['full_dir'];

		$this->LoremIpsum();

		return true;
	}

	function LoremIpsum(){
		global $page, $langmessage, $gp_titles, $gp_menu;
		ob_start();
		echo '<h2>Lorem Ipsum H2</h2>';
		echo '<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
		quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
		Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. </p>';
		echo '<h3>Lorem Ipsum H3</h3>';
		echo '<p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>';


		echo '<table>';
		echo '<tr><th>Lorem Ipsum Table Heading</th></tr>';
		echo '<tr><td>Lorem Ipsum Table Cell</td></tr>';
		echo '</table>';

		echo '<h4>Lorem Ipsum H4</h4>';
		echo '<blockquote>';
		echo 'Lorem Ipsum Blockquote';
		echo '</blockquote>';


		$page->non_admin_content = ob_get_clean();
	}




	function ThemeInfo($theme){

		$template = dirname($theme);
		$color = basename($theme);

		$themes = $this->GetPossible();
		if( !isset($themes[$template]) || !isset($themes[$template]['colors'][$color]) ){
			return false;
		}


		$theme_info = $themes[$template];
		$theme_info['color'] = $color;

		return $theme_info;
	}

	//possible themes
	function GetPossible(){
		global $dataDir;
		$themes = array();

		//packaged themes
		$dir = $dataDir.'/themes';
		$layouts = gpFiles::readDir($dir,1);
		foreach($layouts as $name){
			$full_dir = $dir.'/'.$name;
			$templateFile = $full_dir.'/template.php';
			if( !file_exists($templateFile) ){
				continue;
			}

			//$InstallData = $this->GetAvailInstall($full_dir);
			//if( isset($InstallData['Addon_Unique_ID']) ){
			//	$themes[$name]['id'] = $InstallData['Addon_Unique_ID'];
			//}

			$index = $name.'(package)';

			$themes[$index]['name'] = $name;
			$themes[$index]['colors'] = $this->GetThemeColors($full_dir);
			$themes[$index]['folder'] = $name;
			$themes[$index]['is_addon'] = false;
			$themes[$index]['full_dir'] = $full_dir;
		}

		//downloaded themes
		$dir = $dataDir.'/data/_themes';
		$layouts = gpFiles::readDir($dir,1);
		asort($layouts);
		foreach($layouts as $folder){
			$full_dir = $dir.'/'.$folder;
			$templateFile = $full_dir.'/template.php';
			if( !file_exists($templateFile) ){
				continue;
			}

			$ini_file = $full_dir.'/Addon.ini';
			$ini_info = gp_ini::ParseFile($ini_file);

			$index = $ini_info['Addon_Name'].'(remote)';
			$themes[$index]['name'] = $ini_info['Addon_Name'];
			$themes[$index]['colors'] = $this->GetThemeColors($full_dir);
			$themes[$index]['folder'] = $folder;
			$themes[$index]['is_addon'] = true;
			$themes[$index]['full_dir'] = $full_dir;
		}

		uksort($themes,'strnatcasecmp');

		return $themes;
	}

	function GetThemeColors($dir){
		$subdirs = gpFiles::readDir($dir,1);
		$colors = array();
		asort($subdirs);
		foreach($subdirs as $subdir){
			if( $subdir == 'images'){
				continue;
			}
			$colors[$subdir] = $subdir;
		}
		return $colors;
	}



	function MakeDefault(){
		global $config,$langmessage,$gpLayouts,$page;

		$layout =& $_GET['layout_id'];
		if( !isset( $gpLayouts[$layout]) ){
			message($langmessage['OOPS']);
			return false;
		}

		$oldConfig = $config;
		$config['gpLayout'] = $layout;

		if( admin_tools::SaveConfig() ){

			$page->SetTheme();
			$this->SetLayoutArray();

			message($langmessage['SAVED']);
		}else{
			$config = $oldConfig;
			message($langmessage['OOPS']);
		}
	}


	function DeleteLayout(){
		global $langmessage,$gpLayouts;

		$layout =& $_GET['layout_id'];
		if( !isset( $gpLayouts[$layout]) ){
			message($langmessage['OOPS']);
			return false;
		}

		$label = $gpLayouts[$layout]['label'];

		echo '<div class="inline_box">';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';

		echo '<input type="hidden" name="cmd" value="deletelayoutconfirmed" />';
		echo '<input type="hidden" name="layout_id" value="'.htmlspecialchars($layout).'" />';
		echo sprintf($langmessage['generic_delete_confirm'], '<i>'.$label.'</i>');
		echo '<p>';
		echo ' <input type="submit" name="" value="'.$langmessage['continue'].'" />';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close" />';
		echo '</p>';
		echo '</form>';
		echo '</div>';
	}

	function DeleteLayoutConfirmed(){
		global $gpLayouts,$langmessage, $gp_titles;

		$gpLayoutsBefore = $gpLayouts;

		$layout =& $_POST['layout_id'];
		if( !isset( $gpLayouts[$layout]) ){
			message($langmessage['OOPS']);
			return false;
		}

		//remove from $gp_titles
		$this->RmLayout($layout);

		//save
		if( admin_tools::SavePagesPHP() ){
			message($langmessage['SAVED']);
		}else{
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS'].' (s1)');
		}
	}

	function LayoutDetails(){
		global $gpLayouts,$langmessage;

		$gpLayoutsBefore = $gpLayouts;

		$layout =& $_POST['layout'];
		if( !isset( $gpLayouts[$layout]) ){
			message($langmessage['OOPS']);
			return false;
		}

		if( !empty($_POST['color']) && (strlen($_POST['color']) == 7) && $_POST['color']{0} == '#' ){
			$gpLayouts[$layout]['color'] = $_POST['color'];
		}

		$gpLayouts[$layout]['label'] = htmlspecialchars($_POST['layout_label']);


		if( admin_tools::SavePagesPHP() ){
			message($langmessage['SAVED']);
		}else{
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS'].' (s1)');
		}
	}



	function Show(){
		global $config,$page,$langmessage,$gpLayouts;

		echo '<h2>'.$langmessage['layouts'].'</h2>';

		echo '<table class="bordered" style="width:100%">';
		echo '<tr>';
			echo '<th>';
			echo $langmessage['layouts'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['usage'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['theme'];
			echo '/';
			echo $langmessage['style'];
			echo '</th>';
			echo '</tr>';

		foreach($gpLayouts as $layout => $info){
			$this->ShowLayout($layout,$info);
		}

		echo '</table>';

		echo '<br/>';

		$this->ShowAvailable();

		echo '<p><a href="'.$GLOBALS['addonBrowsePath'].'/Special_Addon_Themes" name="remote">Browse Additional Themes</a></p>';

		echo '<p class="admin_note">';
		echo $langmessage['see_also'].' '.common::Link('Admin_Menu',$langmessage['file_manager']);
		echo '</p>';

		$this->ColorSelector();

	}

	function ColorSelector($cmd = 'layout_details'){

		$colors = $this->GetColors();
		echo '<div id="layout_ident" class="gp_floating_area">';
		echo '<div>';

		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
		echo '<input type="hidden" name="layout" value="" />';
		echo '<input type="hidden" name="color" value="" />';
		echo '<input type="hidden" name="cmd" value="'.$cmd.'" />';

		echo '<table cellpadding="3">';


		echo '<tr>';
			echo '<td>';
			echo ' <a href="#" class="layout_color_id" id="current_color"></a> ';
			echo '<input type="text" name="layout_label" value="" maxlength="15"/>';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo '<div class="colors">';
			foreach($colors as $color){
				echo '<a href="#" class="color" style="background-color:'.$color.'" title="'.$color.'" rel="'.$color.'"></a>';
			}
			echo '</div>';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo ' <input type="submit" name="" value="Ok" />';
			echo ' <input type="button" class="cancel" name="" value="Cancel" />';
			echo '</td>';
			echo '</tr>';

		echo '</table>';
		echo '</form>';
		echo '</div>';
		echo '</div>';

	}

	function ShowAvailable($show=true){
		global $langmessage;
		$themes = $this->GetPossible();

		//versions available online
		includeFile('install/update_class.php');
		update_class::VersionsAndCheckTime($new_versions);

		$class = $style = '';
		if( !$show ){
			$class = ' hidden';
			$style = ';display:none';
		}
		$avail_count = 0;
		foreach($themes as $theme_id => $info){
			$avail_count += count($info['colors']);
		}

		echo '<div class="collapsible">';
		echo '<h4 class="head'.$class.'"><a href="#" name="collapsible">';
		echo $langmessage['available_themes'];
		echo ': '.$avail_count;
		echo '</a></h4>';

		echo '<table class="bordered" style="width:100%'.$style.'">';

			foreach($themes as $theme_id => $info){
				echo '<tr>';
				echo '<td>';
				echo str_replace('_',' ',$info['name']);
				echo '</td>';
				echo '<td>';
				$comma = '';
				foreach($info['colors'] as $color){
					echo $comma;
					echo common::Link('Admin_Theme_Content',$color,'cmd=preview&theme='.rawurlencode($theme_id.'/'.$color)); //,' name="creq" ');
					$comma = ', ';
				}

				echo '</td>';
				echo '<td>';
				echo common::Link('Admin_Theme_Content',$langmessage['rate'],'cmd=rate&arg='.rawurlencode($info['full_dir']));
				echo ' &nbsp; ';

				if( $info['is_addon'] ){

					if( isset($info['id']) && isset($new_versions[$info['id']]) ){
						echo ' &nbsp; ';
						echo '<a href="'.$GLOBALS['addonBrowsePath'].'/Special_Addon_Themes?id='.$info['id'].'" name="remote">';
						echo $langmessage['upgrade'].' (gpEasy.com)';
						echo '</a>';
					}

					echo common::Link('Admin_Theme_Content',$langmessage['delete'],'cmd=deletetheme&folder='.rawurlencode($info['folder']).'&label='.rawurlencode($theme_id),' name="admin_box"');
				}



				echo '</td>';
				echo '</tr>';
			}

		echo '</table>';

		echo '</div>';

	}




	function ShowLayout($layout,$info){
		global $page, $langmessage, $config;

		echo '<tr class="expand_row">';

		//label
			echo '<td class="nowrap">';
			echo '<a href="#" name="layout_id" title="'.$info['color'].'" rel="'.$info['color'].'">';
			echo '<input type="hidden" name="layout" value="'.htmlspecialchars($layout).'"  /> ';
			echo '<input type="hidden" name="layout_label" value="'.$info['label'].'"  /> ';
			echo '<span class="layout_color_id" style="background-color:'.$info['color'].';"></span>';
			echo '&nbsp;';
			echo $info['label'];
			echo '</a>';


			//options
			echo '<div class="gp_options">';

			echo common::Link('Admin_Theme_Content',$langmessage['rearrange'],'cmd=editlayout&layout='.rawurlencode($layout),' title="'.htmlspecialchars($langmessage['Arrange Content']).'" ');
			echo ' &nbsp; ';

			echo common::Link('Admin_Theme_Content',$langmessage['details'],'cmd=showdetails&layout='.rawurlencode($layout));
			echo ' &nbsp; ';

			echo common::Link('Admin_Theme_Content',$langmessage['Copy'],'cmd=copy&layout='.rawurlencode($layout),' name="creq"');
			echo ' &nbsp; ';

			if( $config['gpLayout'] == $layout ){
				echo '<span>'.$langmessage['delete'].'</span>';
			}else{
				echo common::Link('Admin_Theme_Content',$langmessage['delete'],'cmd=deletelayout&layout_id='.rawurlencode($layout),' name="admin_box"');
			}

			echo '</div>';
			echo '</td>';

		//usage
			echo '<td class="nowrap">';
			if( $config['gpLayout'] == $layout ){
				echo $langmessage['default'];
			}else{
				echo common::Link('Admin_Theme_Content',str_replace(' ','&nbsp;',$langmessage['default']),'cmd=makedefault&layout_id='.rawurlencode($layout),' name="creq" title="'.htmlspecialchars($langmessage['make_default']).'" ');
			}
			echo ' &nbsp; ';

			$titles_count = $this->TitlesCount($layout);
			echo sprintf($langmessage['%s Pages'],$titles_count);

			echo '</td>';

		//theme
			echo '<td class="nowrap">';
			if( isset($info['is_addon']) && $info['is_addon'] ){
				echo htmlspecialchars($info['theme_label']);
			}else{
				echo $info['theme'];
			}

			echo '</td>';


		echo '</tr>';

	}

	function TitlesCount($layout){
		$titles_count = 0;
		foreach( $this->LayoutArray as $layout_comparison ){
			if( $layout == $layout_comparison ){
				$titles_count++;
			}
		}
		return $titles_count;
	}


	function Restore(){
		$this->SaveHandlersNew(array(),$_GET['layout']);
	}

	function SaveHandlersNew($handlers,$layout=false){
		global $config,$page,$langmessage,$gpLayouts;

		//make sure the keys are sequential
		foreach($handlers as $container => $container_info){
			if( is_array($container_info) ){
				$handlers[$container] = array_values($container_info);
			}
		}

		if( $layout == false ){
			$layout = $this->curr_layout;
		}

		if( !isset( $gpLayouts[$layout] )  ){
			message($langmessage['OOPS']);
			return false;
		}

		$gpLayoutsBefore = $gpLayouts;
		if( count($handlers) === 0 ){
			unset($gpLayouts[$layout]['handlers']);
		}else{
			$gpLayouts[$layout]['handlers'] = $handlers;
		}

		if( admin_tools::SavePagesPHP() ){

			message($langmessage['SAVED']);

		}else{
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS'].' (s1)');
		}
	}


	function ParseHandlerInfo($str,&$info){
		global $config,$gpOutConf;

		if( substr_count($str,'|') !== 1 ){
			return false;
		}


		list($container,$fullKey) = explode('|',$str);

		$arg = '';
		$pos = strpos($fullKey,':');
		$key = $fullKey;
		if( $pos > 0 ){
			$arg = substr($fullKey,$pos+1);
			$key = substr($fullKey,0,$pos);
		}

		if( !isset($gpOutConf[$key]) && !isset($config['gadgets'][$key]) ){
			return false;
		}

		$info = array();
		$info['gpOutCmd'] = trim($fullKey,':');
		$info['container'] = $container;
		$info['key'] = $key;
		$info['arg'] = $arg;

		return true;

	}


	function GetAllHandlers($layout=false){
		global $page,$gpLayouts, $config;

		if( $layout === false ){
			$layout = $this->curr_layout;
		}

		$handlers =& $gpLayouts[$layout]['handlers'];

		if( !is_array($handlers) || count($handlers) < 1 ){
			$gpLayouts[$layout]['hander_v'] = '2';
			$handlers = array();
		}

		//clean : characters for backwards compat
		foreach($handlers as $container => $container_info){
			if( is_string($container_info) ){
				$handlers[$container] = trim($container_info,':');
				continue;
			}
			if( !is_array($container_info) ){
				continue;
			}
			foreach($container_info as $key => $gpOutCmd){
				$handlers[$container][$key] = trim($gpOutCmd,':');
			}
		}

		return $handlers;
	}


	//set default values if not set
	function PrepContainerHandlers(&$handlers,$container,$gpOutCmd){
		if( isset($handlers[$container]) && is_array($handlers[$container]) ){
			return;
		}
		$handlers[$container] = $this->GetDefaultList($container,$gpOutCmd);
	}



	function GetDefaultList($container,$gpOutCmd){
		global $config, $gpOutConf;

		if( $container !== 'GetAllGadgets' ){

			//Just a container that doesn't have content by default
			// ex: 		gpOutput::Get('AfterContent');
			if( empty($gpOutCmd) ){
				return array();
			}

			return array($gpOutCmd);
		}

		$result = array();
		if( isset($config['gadgets']) && is_array($config['gadgets']) ){
			foreach($config['gadgets'] as $gadget => $info){
				$result[] = $gadget;
			}
		}
		return $result;
	}

	function GetValues($a,&$container,&$gpOutCmd){
		if( substr_count($a,'|') !== 1 ){
			return false;
		}

		list($container,$gpOutCmd) = explode('|',$a);
		return true;
	}

	function AddToContainer(&$container,$to_gpOutCmd,$new_gpOutCmd,$replace=true,$offset=0){
		global $langmessage;

		//unchanged?
		if( $replace && ($to_gpOutCmd == $new_gpOutCmd) ){
			return true;
		}


		//add to to_container in front of $to_gpOutCmd
		if( !isset($container) || !is_array($container) ){
			message($langmessage['OOPS'].' (a1)');
			return false;
		}

		//can't have two identical outputs in the same container
		$check = array_search($new_gpOutCmd,$container);
		if( ($check !== null) && ($check !== false) ){
			message($langmessage['OOPS']. ' (Area already in container)');
			return false;
		}

		//if empty, just add
		if( count($container) === 0 ){
			$container[] = $new_gpOutCmd;
			return true;
		}

		$length = 1;
		if( $replace === false ){
			$length = 0;
		}

		//insert
		$where = array_search($to_gpOutCmd,$container);
		if( ($where === null) || ($where === false) ){
			message($langmessage['OOPS']. '(a3)');
			return false;
		}
		$where += $offset;

		array_splice($container,$where,$length,$new_gpOutCmd);

		return true;
	}



	function SelectContent(){
		global $langmessage,$config,$gpOutConf;

		if( !isset($_GET['param']) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		$param = $_GET['param'];

		//counts
		$count_gadgets = ( isset($config['gadgets']) && is_array($config['gadgets']) ) ? count($config['gadgets']) : false;
		echo '<div class="inline_box">';

		echo '<div class="layout_links">';
		echo '<a href="#layout_extra_content" class="selected" name="tabs">'. $langmessage['theme_content'] .'</a>';
		if( $count_gadgets > 0 ){
			echo ' <a href="#layout_gadgets" name="tabs">'. $langmessage['gadgets'] .'</a>';
		}
		echo ' <a href="#layout_menus" name="tabs">'. $langmessage['Link_Menus'] .'</a>';

		echo ' <a href="#layout_custom" name="tabs">'. $langmessage['Custom Menu'] .'</a>';

		echo '</div>';

		$this->SelectContent_Areas($param,$count_gadgets);
		echo '</div>';
	}

	function SelectContent_Areas($param,$count_gadgets){
		global $dataDir,$langmessage,$config,$gpOutConf;


		$addQuery = 'cmd=addcontent&layout='.rawurlencode($this->curr_layout).'&where='.rawurlencode($param);
		echo '<div id="area_lists">';

			//extra content
			echo '<div id="layout_extra_content">';
			echo '<table class="bordered">';

				echo '<tr><th colspan="2">&nbsp;</th></tr>';

				$extrasFolder = $dataDir.'/data/_extra';
				$files = gpFiles::ReadDir($extrasFolder);
				asort($files);
				foreach($files as $file){
					$extraName = $file;
					echo '<tr>';
					echo '<td>';
					echo str_replace('_',' ',$extraName);
					echo '</td>';
					echo '<td class="add">';
					echo common::Link('Admin_Theme_Content',$langmessage['add'],$addQuery.'&insert=Extra:'.$extraName,' name="creq" ');
					echo '</td>';
					echo '</tr>';
				}


				//new extra area
				echo '<tr><td>';
				echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
				echo '<input type="hidden" name="cmd" value="addcontent" />';
				echo '<input type="hidden" name="addtype" value="new_extra" />';
				echo '<input type="hidden" name="layout" value="'.htmlspecialchars($this->curr_layout).'" />';
				echo '<input type="hidden" name="where" value="'.htmlspecialchars($param).'" />';

				echo '<input type="text" name="extra_area" value="" size="15" />';
				echo ' <input type="submit" name="" value="'.$langmessage['Add New Area'].'" />';
				echo '</form>';
				echo '</td><td colspan="2" class="add">';
				echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
				echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close" />';
				echo '</form>';
				echo '</td></tr>';
				echo '</table>';

			echo '</div>';

			//gadgets
			if( $count_gadgets > 0){
				echo '<div id="layout_gadgets" style="display:none">';
					echo '<table class="bordered">';
					echo '<tr><th colspan="2">&nbsp;</th></tr>';

					foreach($config['gadgets'] as $gadget => $info){
						echo '<tr>';
							echo '<td>';
							echo str_replace('_',' ',$gadget);
							echo '</td>';
							echo '<td class="add">';
							echo common::Link('Admin_Theme_Content',$langmessage['add'],$addQuery.'&insert='.$gadget,' name="creq" ');
							echo '</td>';
							echo '</tr>';
					}

					echo '<tr><td colspan="2" class="add">';
					echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close" />';
					echo '</td></tr>';

					echo '</table>';
				echo '</div>';
			}

			//menus
			echo '<div id="layout_menus" style="display:none">';


				echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
				echo '<input type="hidden" name="cmd" value="addcontent" />';
				echo '<input type="hidden" name="addtype" value="preset_menu" />';
				echo '<input type="hidden" name="layout" value="'.htmlspecialchars($this->curr_layout).'" />';
				echo '<input type="hidden" name="where" value="'.htmlspecialchars($param).'" />';


				echo '<table class="bordered">';
					$this->PresetMenuForm();

					echo '<tr><td colspan="2" class="add">';
					echo '<input type="submit" name="" value="'.$langmessage['Add New Menu'].'" />';
					echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close" />';
					echo '</td></tr>';
				echo '</table>';
				echo '</form>';


			echo '</div>';


			echo '<div id="layout_custom" style="display:none">';

				//custom area
				echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
				echo '<input type="hidden" name="cmd" value="addcontent" />';
				echo '<input type="hidden" name="addtype" value="custom_menu" />';
				echo '<input type="hidden" name="layout" value="'.htmlspecialchars($this->curr_layout).'" />';
				echo '<input type="hidden" name="where" value="'.htmlspecialchars($param).'" />';

				$this->CustomMenuForm();

					echo '<tr><td colspan="2" class="add">';
					echo '<input type="submit" name="" value="'.$langmessage['Add New Menu'].'" />';
					echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close" />';
					echo '</td></tr>';
				echo '</table>';

				echo '</form>';
			echo '</div>';
		echo '</div>';
	}

	function NewCustomMenu(){

		$upper_bound =& $_POST['upper_bound'];
		$lower_bound =& $_POST['lower_bound'];
		$expand_bound =& $_POST['expand_bound'];
		$expand_all =& $_POST['expand_all'];
		$source_menu =& $_POST['source_menu'];

		$this->CleanBounds($upper_bound,$lower_bound,$expand_bound,$expand_all,$source_menu);

		$arg = $upper_bound.','.$lower_bound.','.$expand_bound.','.$expand_all.','.$source_menu;
		return 'CustomMenu:'.$arg;
	}

	function NewPresetMenu(){
		global $gpOutConf;

		$new_gpOutCmd =& $_POST['new_handle'];
		if( !isset($gpOutConf[$new_gpOutCmd]) || !isset($gpOutConf[$new_gpOutCmd]['link']) ){
			return false;
		}

		return rtrim($new_gpOutCmd.':'.$this->CleanMenu($_POST['source_menu']),':');
	}

	function CleanBounds(&$upper_bound,&$lower_bound,&$expand_bound,&$expand_all,&$source_menu){

		$upper_bound = (int)$upper_bound;
		$upper_bound = max(0,$upper_bound);
		$upper_bound = min(4,$upper_bound);

		$lower_bound = (int)$lower_bound;
		$lower_bound = max(-1,$lower_bound);
		$lower_bound = min(4,$lower_bound);

		$expand_bound = (int)$expand_bound;
		$expand_bound = max(-1,$expand_bound);
		$expand_bound = min(4,$expand_bound);

		if( $expand_all ){
			$expand_all = 1;
		}else{
			$expand_all = 0;
		}

		$source_menu = $this->CleanMenu($source_menu);
	}
	function CleanMenu($menu){
		global $config;

		if( empty($menu) ){
			return '';
		}
		if( !isset($config['menus'][$menu]) ){
			return '';
		}
		return $menu;
	}

	function PresetMenuForm($args = array()){
		global $gpOutConf,$langmessage;

		$current_function =& $args['current_function'];
		$current_menu =& $args['source_menu'];

		$this->MenuSelect($current_menu);


		echo '<tr><th colspan="2">';
			echo $langmessage['Menu Output'];
		echo '</th></tr>';


		$i = 0;
		foreach($gpOutConf as $outKey => $info){

			if( !isset($info['link']) ){
				continue;
			}
			echo '<tr>';
			echo '<td>';
			echo '<label for="new_handle_'.$i.'">';
			if( isset($langmessage[$info['link']]) ){
				echo str_replace(' ','&nbsp;',$langmessage[$info['link']]);
			}else{
				echo str_replace(' ','&nbsp;',$info['link']);
			}
			echo '</label>';
			echo '</td>';
			echo '<td class="add">';

			if( $current_function == $outKey ){
				echo '<input id="new_handle_'.$i.'" type="radio" name="new_handle" value="'.$outKey.'" checked="checked"/>';
			}else{
				echo '<input id="new_handle_'.$i.'" type="radio" name="new_handle" value="'.$outKey.'" />';
			}
			echo '</td>';
			echo '</tr>';
			$i++;
		}
	}


	function MenuArgs($curr_info){

		$menu_args = array();

		if( $curr_info['key'] == 'CustomMenu' ){
			$showCustom = true;

			$args = explode(',',$curr_info['arg']);
			$args += array( 0=>0, 1=>-1, 2=>-1, 3=>0, 4=>'' ); //defaults
			list($upper_bound,$lower_bound,$expand_bound,$expand_all,$source_menu) = $args;

			$this->CleanBounds($upper_bound,$lower_bound,$expand_bound,$expand_all,$source_menu);


			$menu_args['upper_bound'] = $upper_bound;
			$menu_args['lower_bound'] = $lower_bound;
			$menu_args['expand_bound'] = $expand_bound;
			$menu_args['expand_all'] = $expand_all;
			$menu_args['source_menu'] = $source_menu;


		}else{

			$menu_args['current_function'] = $curr_info['key'];
			$menu_args['source_menu'] = $this->CleanMenu($curr_info['arg']);
		}


		return $menu_args;

	}


	function CustomMenuForm($arg = '',$menu_args = array()){
		global $langmessage;

		$upper_bound =& $menu_args['upper_bound'];
		$lower_bound =& $menu_args['lower_bound'];
		$expand_bound =& $menu_args['expand_bound'];
		$expand_all =& $menu_args['expand_all'];
		$source_menu =& $menu_args['source_menu'];


		echo '<table class="bordered">';

		$this->MenuSelect($source_menu);

		echo '<tr><th colspan="2">';
			echo $langmessage['Show Titles...'];
		echo '</th></tr>';

		echo '<tr><td>';
			echo $langmessage['... Below Level'];
			echo '</td><td class="add">';
			echo '<select name="upper_bound">';
			for($i=0;$i<=4;$i++){
				$label = $i;
				if( $i === 0 ){
					$label = '&nbsp;';
				}
				if( $i === $upper_bound ){
					echo '<option value="'.$i.'" selected="selected">'.$label.'</option>';
				}else{
					echo '<option value="'.$i.'">'.$label.'</option>';
				}
			}
			echo '</select>';
			echo '</td></tr>';

		echo '<tr><td>';
			echo $langmessage['... At And Above Level'];
			echo '</td><td class="add">';
			echo '<select name="lower_bound">';
			for($i=0;$i<=4;$i++){
				$label = $i;
				if( $i === 0 ){
					$label = '&nbsp;';
				}
				if( $i === $lower_bound ){
					echo '<option value="'.$i.'" selected="selected">'.$label.'</option>';
				}else{
					echo '<option value="'.$i.'">'.$label.'</option>';
				}
			}


			echo '</select>';
			echo '</td></tr>';

		echo '<tr><th colspan="2">';
			echo $langmessage['Expand Menu...'];
			echo '</th></tr>';

		echo '<tr><td>';
			echo $langmessage['... Below Level'];
			echo '</td><td class="add">';
			echo '<select name="expand_bound">';
			for($i=0;$i<=4;$i++){
				$label = $i;
				if( $i === 0 ){
					$label = '&nbsp;';
				}
				if( $i === $expand_bound ){
					echo '<option value="'.$i.'" selected="selected">'.$label.'</option>';
				}else{
					echo '<option value="'.$i.'">'.$label.'</option>';
				}
			}

			echo '</select>';
			echo '</td></tr>';

		echo '<tr><td>';
			echo $langmessage['... Expand All'];
			echo '</td><td class="add">';
			$attr = '';
			if( $expand_all ){
				$attr = ' checked="checked"';
			}
			echo '<input type="checkbox" name="expand_all" '.$attr.'>';
			echo '</td></tr>';

	}

	function MenuSelect($source_menu){
		global $config, $langmessage;

		echo '<tr><th colspan="2">';
			echo $langmessage['Source Menu'];
		echo '</th>';
		echo '</tr>';
		echo '<tr><td>';
		echo $langmessage['Menu'];
		echo '</td><td class="add">';
		echo '<select name="source_menu">';
		echo '<option value="">'.$langmessage['Main Menu'].'</option>';
		if( isset($config['menus']) && count($config['menus']) > 0 ){
			foreach($config['menus'] as $id => $menu ){
				$attr = '';
				if( $source_menu == $id ){
					$attr = ' selected="selected"';
				}
				echo '<option value="'.htmlspecialchars($id).'" '.$attr.'>'.htmlspecialchars($menu).'</option>';
			}
		}
		echo '</select>';
		echo '</td></tr>';
	}


	function AddContent(){
		global $langmessage,$page;

		//for ajax responses
		$page->ajaxReplace = array();

		if( !isset($_REQUEST['where']) ){
			message($langmessage['OOPS']);
			return false;
		}

		//prep destination
		if( !$this->GetValues($_REQUEST['where'],$to_container,$to_gpOutCmd) ){
			message($langmessage['OOPS'].' (0)');
			return false;
		}
		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers,$to_container,$to_gpOutCmd);


		//figure out what we're inserting
		$addtype =& $_REQUEST['addtype'];
		switch($_REQUEST['addtype']){

			case 'new_extra':
				$extra_name = $this->NewExtraArea();
				if( $extra_name === false ){
					message($langmessage['OOPS'].'(2)');
					return false;
				}
				$insert = 'Extra:'.$extra_name;
			break;

			case 'custom_menu':
				$insert = $this->NewCustomMenu();
			break;

			case 'preset_menu':
				$insert = $this->NewPresetMenu();
			break;


			default:
				$insert = $_REQUEST['insert'];
			break;
		}

		if( !$insert ){
			message($langmessage['OOPS'].' (1)');
			return false;
		}

		//new info
		$new_gpOutInfo = gpOutput::GetgpOutInfo($insert);
		if( !$new_gpOutInfo ){
			message($langmessage['OOPS'].' (1)');
			return false;
		}
		$new_gpOutCmd = rtrim($new_gpOutInfo['key'].':'.$new_gpOutInfo['arg'],':');

		if( !$this->AddToContainer($handlers[$to_container],$to_gpOutCmd,$new_gpOutCmd,false) ){
			return false;
		}

		$this->SaveHandlersNew($handlers);

		return true;
	}

	//return the name of the cleansed extra area name, create file if it doesn't already exist
	function NewExtraArea(){
		global $dataDir,$langmessage;

		if( empty($_POST['extra_area']) ){
			return false;
		}

		$extra_name = gpFiles::CleanTitle($_POST['extra_area']);
		$extra_file = $dataDir.'/data/_extra/'.$extra_name.'.php';

		if( file_exists($extra_file) ){
			return $extra_name;
		}

		$text = '<div>'.htmlspecialchars($_POST['extra_area']).'</div>';
		if( !gpFiles::SaveFile($extra_file,$text) ){
			return false;
		}

		return $extra_name;
	}


	function Drag(){
		global $page,$langmessage;

		if( !$this->GetValues($_GET['dragging'],$from_container,$from_gpOutCmd) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		if( !$this->GetValues($_GET['to'],$to_container,$to_gpOutCmd) ){
			message($langmessage['OOPS'].'(1)');
			return;
		}


		//prep work
		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers,$from_container,$from_gpOutCmd);
		$this->PrepContainerHandlers($handlers,$to_container,$to_gpOutCmd);


		//remove from from_container
		if( !isset($handlers[$from_container]) || !is_array($handlers[$from_container]) ){
			message($langmessage['OOPS'].' (2)');
			return;
		}


		$where = array_search($from_gpOutCmd,$handlers[$from_container]);
		$to = array_search($to_gpOutCmd,$handlers[$from_container]);

		if( ($where === null) || ($where === false) ){
			message($langmessage['OOPS']. '(3)');
			return;
		}


		array_splice($handlers[$from_container],$where,1);

		/**
		 * for moving down
		 * if target is the same container
		 * and target is below dragged element
		 * then $offset = 1
		 *
		 */
		$offset = 0;
		if( ($from_container == $to_container)
			&& ($to !== null)
			&& ($to !== false)
			&& $to > $where ){
				$offset = 1;
		}

		if( !$this->AddToContainer($handlers[$to_container],$to_gpOutCmd,$from_gpOutCmd,false,$offset) ){
			return;
		}

		$this->SaveHandlersNew($handlers);

	}


	function RemoveArea(){
		global $langmessage,$page;

		//for ajax responses
		$page->ajaxReplace = array();

		if( !$this->ParseHandlerInfo($_GET['param'],$curr_info) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		$gpOutCmd = $curr_info['gpOutCmd'];
		$container = $curr_info['container'];


		//prep work
		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers,$container,$gpOutCmd);


		//remove from $handlers[$container]
		$where = array_search($gpOutCmd,$handlers[$container]);

		if( ($where === null) || ($where === false) ){
			message($langmessage['OOPS'].' (2)');
			return;
		}

		array_splice($handlers[$container],$where,1);
		$this->SaveHandlersNew($handlers);

	}


	function SelectLinks(){
		global $langmessage,$gpLayouts,$gpOutConf;

		$layout =& $_REQUEST['layout'];

		if( !isset($gpLayouts[$layout]) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}

		if( !$this->ParseHandlerInfo($_GET['handle'],$curr_info) ){
			message($langmessage['00PS']);
			return;
		}


		$showCustom = false;
		$current_function = false;
		if( $curr_info['key'] == 'CustomMenu' ){
			$showCustom = true;
		}else{
			$current_function = $curr_info['key'];
		}

		$menu_args = $this->MenuArgs($curr_info);


		echo '<div class="inline_box" style="width:30em">';

		echo '<div class="layout_links">';
		if( $showCustom ){
			echo ' <a href="#layout_menus" name="tabs">'. $langmessage['Link_Menus'] .'</a>';
			echo ' <a href="#layout_custom" name="tabs" class="selected">'. $langmessage['Custom Menu'] .'</a>';
		}else{
			echo ' <a href="#layout_menus" name="tabs" class="selected">'. $langmessage['Link_Menus'] .'</a>';
			echo ' <a href="#layout_custom" name="tabs">'. $langmessage['Custom Menu'] .'</a>';
		}
		echo '</div>';

		echo '<br/>';
		echo '<div id="area_lists">';

		//preset menus
			$style = '';
			if( $showCustom ){
				$style = ' style="display:none"';
			}
			echo '<div id="layout_menus" '.$style.'>';
			echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
			echo '<input type="hidden" name="handle" value="'.htmlspecialchars($_GET['handle']).'" />';
			echo '<input type="hidden" name="return" value="" />';
			echo '<input type="hidden" name="layout" value="'.htmlspecialchars($layout).'" />';
			echo '<input type="hidden" name="cmd" value="savelinks" />';

			echo '<table class="bordered">';
			$this->PresetMenuForm($menu_args);

			echo '<tr><td>';
				echo '&nbsp;';
				echo '</td><td class="add">';
				echo '<input type="submit" name="aaa" value="'.$langmessage['save'].'" /> ';
				echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close" />';
				echo '</td></tr>';
			echo '</table>';
			echo '</form>';

			echo '</div>';

		//custom menus
			$style = ' style="display:none"';
			if( $showCustom ){
				$style = '';
			}
			echo '<div id="layout_custom" '.$style.'>';
			echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
			echo '<input type="hidden" name="handle" value="'.htmlspecialchars($_GET['handle']).'" />';
			echo '<input type="hidden" name="return" value="" />';
			echo '<input type="hidden" name="layout" value="'.htmlspecialchars($layout).'" />';
			echo '<input type="hidden" name="cmd" value="savelinks" />';

			$this->CustomMenuForm($curr_info['arg'],$menu_args);

			echo '<tr><td>';
				echo '&nbsp;';
				echo '</td><td class="add">';
				echo '<input type="submit" name="aaa" value="'.$langmessage['save'].'" /> ';
				echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close" />';
				echo '</td></tr>';
			echo '</table>';
			echo '</form>';

			echo '</div>';

			echo '<p class="admin_note">';
			echo $langmessage['see_also'];
			echo ' ';
			echo common::Link('Admin_Menu',$langmessage['file_manager']);
			echo ', ';
			echo common::Link('Admin_Theme_Content',$langmessage['content_arrangement']);
			echo '</p>';

		echo '</div>';
		echo '</div>';

	}


	function SaveLinks(){
		global $config,$langmessage,$gpOutConf,$gpLayouts;


		$layout =& $_POST['layout'];

		if( !isset($gpLayouts[$layout]) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}


		if( !$this->ParseHandlerInfo($_POST['handle'],$curr_info) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}



		if( isset($_POST['new_handle']) ){
			$new_gpOutCmd = $this->NewPresetMenu();
		}else{
			$new_gpOutCmd = $this->NewCustomMenu();
		}

		if( !$new_gpOutCmd ){
			message($langmessage['OOPS'].' (1)');
			return false;
		}


		//prep
		$handlers = $this->GetAllHandlers($layout);
		$container =& $curr_info['container'];
		$this->PrepContainerHandlers($handlers,$container,$curr_info['gpOutCmd']);


		if( !$this->AddToContainer($handlers[$container],$curr_info['gpOutCmd'],$new_gpOutCmd,true) ){
			return;
		}

		$this->SaveHandlersNew($handlers,$layout);


		//message('not forwarding');
		$this->ReturnHeader();
	}


	function ReturnHeader(){

		if( empty($_POST['return']) ){
			return;
		}

		$return = $_POST['return'];
		//$return = str_replace('cmd=','x=',$return); //some dynamic plugins rely on cmd to show specific pages.

		if( strpos($return,'http') == 0 ){
			header('Location: '.$return);
			die();
		}

		header('Location: '.common::GetUrl($_POST['return'],false));
		die();
	}



	function GetAddonTexts($addon){
		global $dataDir,$langmessage,$config;

		$addonDir = $dataDir.'/data/_addoncode/'.$addon;
		if( !is_dir($addonDir) ){
			return false;
		}

		//not set up correctly
		if( !isset($config['addons'][$addon]['editable_text']) ){
			return false;
		}

		$file = $addonDir.'/'.$config['addons'][$addon]['editable_text'];
		if( !file_exists($file) ){
			return false;
		}

		include($file);
		if( !isset($texts) || !is_array($texts) || (count($texts) == 0 ) ){
			return false;
		}

		return $texts;
	}


	function SaveAddonText(){
		global $dataDir,$langmessage,$config;

		$addon = gpFiles::CleanArg($_REQUEST['addon']);
		$texts = $this->GetAddonTexts($addon);
		//not set up correctly
		if( $texts === false ){
			message($langmessage['OOPS'].' (0)');
			return;
		}

		$configBefore = $config;
		foreach($texts as $text){
			if( !isset($_POST['values'][$text]) ){
				continue;
			}


			$default = $text;
			if( isset($langmessage[$text]) ){
				$default = $langmessage[$text];
			}

			$value = htmlspecialchars($_POST['values'][$text]);

			if( ($value === $default) || (htmlspecialchars($default) == $value) ){
				unset($config['customlang'][$text]);
			}else{
				$config['customlang'][$text] = $value;
			}
		}

		if( !admin_tools::SaveConfig() ){
			//these two lines are fairly useless when the ReturnHeader() is used
			$config = $configBefore;
			message($langmessage['OOPS'].' (1)');
		}else{

			$this->UpdateAddon($addon);

			message($langmessage['SAVED']);

		}

		$this->ReturnHeader();
	}

	function UpdateAddon($addon){
		if( !function_exists('OnTextChange') ){
			return;
		}

		gpPlugin::SetDataFolder($addon);

		OnTextChange();

		gpPlugin::ClearDataFolder();
	}

	function AddonText(){
		global $dataDir,$langmessage,$config;

		$addon = gpFiles::CleanArg($_REQUEST['addon']);
		$texts = $this->GetAddonTexts($addon);

		//not set up correctly
		if( $texts === false ){
			$this->EditText();
			return;
		}


		echo '<div class="inline_box" style="text-align:right">';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
		echo '<input type="hidden" name="cmd" value="saveaddontext" />';
		echo '<input type="hidden" name="return" value="" />'; //will be populated by javascript
		echo '<input type="hidden" name="addon" value="'.htmlspecialchars($addon).'" />'; //will be populated by javascript


		$this->AddonTextFields($texts);
		echo ' <input type="submit" name="aaa" value="'.$langmessage['save'].'" />';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close" />';


		echo '</form>';
		echo '</div>';

	}

	function AddonTextFields($array){
		global $langmessage,$config;
		echo '<table class="bordered">';
			echo '<tr>';
			echo '<th>';
			echo $langmessage['default'];
			echo '</th>';
			echo '<th>';
			echo '</th>';
			echo '</tr>';

		$key =& $_GET['key'];
		foreach($array as $text){

			$default = $value = $text;
			if( isset($langmessage[$text]) ){
				$default = $value = $langmessage[$text];
			}
			if( isset($config['customlang'][$text]) ){
				$value = $config['customlang'][$text];
			}

			$style = '';
			if( $text == $key ){
				$style = ' style="background-color:#f5f5f5"';
			}

			echo '<tr'.$style.'>';
			echo '<td>';
			echo $text;
			echo '</td>';
			echo '<td>';
			echo '<input type="text" name="values['.htmlspecialchars($text).']" value="'.$value.'" />'; //value has already been escaped with htmlspecialchars()
			echo '</td>';
			echo '</tr>';

		}
		echo '</table>';
	}





	function EditText(){
		global $config, $langmessage,$page;

		if( !isset($_GET['key']) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}

		$default = $value = $key = $_GET['key'];
		if( isset($langmessage[$key]) ){
			$default = $value = $langmessage[$key];

		}
		if( isset($config['customlang'][$key]) ){
			$value = $config['customlang'][$key];
		}


		echo '<div class="inline_box">';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
		echo '<input type="hidden" name="cmd" value="savetext" />';
		echo '<input type="hidden" name="key" value="'.htmlspecialchars($key).'" />';
		echo '<input type="hidden" name="return" value="" />'; //will be populated by javascript

		echo '<table class="bordered">';
			echo '<tr>';
			echo '<th>';
			echo $langmessage['default'];
			echo '</th>';
			echo '<th>';
			echo '</th>';
			echo '</tr>';
			echo '<tr>';
			echo '<td>';
			echo $default;
			echo '</td>';
			echo '<td>';
			//$value is already escaped using htmlspecialchars()
			echo '<input type="text" name="value" value="'.$value.'" />';
			echo '<br/>';
			echo ' <input type="submit" name="aaa" value="'.$langmessage['save'].'" />';
			echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close" />';
			echo '</td>';
			echo '</tr>';
		echo '</table>';

		echo '</form>';
		echo '</div>';
	}



	function SaveText(){
		global $config, $langmessage,$page;

		if( !isset($_POST['key']) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		if( !isset($_POST['value']) ){
			message($langmessage['OOPS'].' (1)');
			return;
		}

		$default = $key = $_POST['key'];
		if( isset($langmessage[$key]) ){
			$default = $langmessage[$key];
		}

		$config['customlang'][$key] = $value = htmlspecialchars($_POST['value']);
		if( ($value === $default) || (htmlspecialchars($default) == $value) ){
			unset($config['customlang'][$key]);
		}

		if( admin_tools::SaveConfig() ){
			message($langmessage['SAVED']);
		}else{
			message($langmessage['OOPS'].' (s1)');
		}
		$this->ReturnHeader();

	}

	function SetLayoutArray(){
		global $gp_menu, $gp_titles, $gp_index, $config;


		$titleThemes = array();
		$customThemes = array();
		$customThemeLevel = 0;
		$max_level = 5;


		foreach($gp_menu as $id => $info){

			$level = $info['level'];

			//reset theme inheritance
			$max_level = max($max_level,$level);
			for( $i = $level; $i <= $max_level; $i++){
				if( isset($customThemes[$i]) ){
					$customThemes[$i] = false;
				}
			}

			if( !empty($gp_titles[$id]['gpLayout']) ){
				$titleThemes[$id] = $gp_titles[$id]['gpLayout'];
			}else{

				$parent_layout = false;
				$temp_level = $level;
				while( $temp_level >= 0 ){
					if( isset($customThemes[$temp_level]) && ($customThemes[$temp_level] !== false) ){
						$titleThemes[$id] = $parent_layout = $customThemes[$temp_level];
						break;
					}
					$temp_level--;
				}

				if( $parent_layout === false ){
					$titleThemes[$id] = $config['gpLayout'];
				}
			}

			$customThemes[$level] = $titleThemes[$id];
		}


		foreach($gp_index as $title => $id){
			$titleInfo = $gp_titles[$id];

			if( isset($titleThemes[$id]) ){
				continue;
			}

			if( !empty($titleInfo['gpLayout']) ){
				$titleThemes[$id] = $titleInfo['gpLayout'];
			}else{
				$titleThemes[$id] = $config['gpLayout'];
			}

		}


		$this->LayoutArray = $titleThemes;

	}


	/*
	 * Remote Themes
	 *
	 *
	 */



	function DeleteTheme(){
		global $langmessage, $dataDir;

		echo '<div class="inline_box">';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';

		$can_delete = true;
		$theme_folder_name =& $_GET['folder'];
		$theme_folder = $dataDir.'/data/_themes/'.$theme_folder_name;
		if( empty($theme_folder_name) || !ctype_alnum($theme_folder_name) || empty($_GET['label']) ){
			echo $langmessage['OOPS'];
			$can_delete = false;
		}

		if( !$this->CanDeleteTheme($theme_folder_name,$message) ){
			echo $message;
			$can_delete = false;
		}

		if( $can_delete ){
			$label = htmlspecialchars($_GET['label']);
			echo '<input type="hidden" name="cmd" value="delete_theme_confirmed" />';
			echo '<input type="hidden" name="folder" value="'.htmlspecialchars($theme_folder_name).'" />';
			echo sprintf($langmessage['generic_delete_confirm'], '<i>'.$label.'</i>');
		}

		echo '<p>';
		echo ' <input type="submit" name="" value="'.$langmessage['continue'].'" />';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close" />';
		echo '</p>';

		echo '</form>';
		echo '</div>';
	}

	function DeleteThemeConfirmed(){
		global $langmessage, $dataDir, $gpLayouts, $config;

		$gpLayoutsBefore = $gpLayouts;
		$can_delete = true;
		$theme_folder_name =& $_POST['folder'];
		$theme_folder = $dataDir.'/data/_themes/'.$theme_folder_name;
		if( empty($theme_folder_name) || !ctype_alnum($theme_folder_name) || !isset($config['themes'][$theme_folder_name]) ){
			message($langmessage['OOPS']);
			return false;
		}

		if( !$this->CanDeleteTheme($theme_folder_name,$message) ){
			message($message);
			return false;
		}


		//remove layouts
		foreach($gpLayouts as $layout_id => $layout_info){

			if( !isset($layout_info['is_addon']) || !$layout_info['is_addon'] ){
				continue;
			}
			$layout_folder = dirname($layout_info['theme']);
			if( $layout_folder == $theme_folder_name ){
				$this->RmLayout($layout_id);
			}
		}


		//delete the folder
		$dir = $dataDir.'/data/_themes/'.$theme_folder_name;
		$this->RmDir($dir);

		//remove from settings
		unset($config['themes'][$theme_folder_name]);

		if( admin_tools::SaveAllConfig() ){
			message($langmessage['SAVED']);
		}else{
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS'].' (s1)');
		}

	}

	function RmLayout($layout){
		global $gp_titles,$gpLayouts;

		foreach($gp_titles as $title => $titleInfo){
			if( isset($titleThemes[$title]) ){
				continue;
			}
			if( empty($titleInfo['gpLayout']) ){
				continue;
			}

			if( $titleInfo['gpLayout'] == $layout ){
				unset($gp_titles[$title]['gpLayout']);
			}
		}
		unset($gpLayouts[$layout]);
	}


	function CanDeleteTheme($folder,&$message){
		global $gpLayouts, $config, $langmessage;

		foreach($gpLayouts as $layout_id => $layout){

			if( !isset($layout['is_addon']) || !$layout['is_addon'] ){
				continue;
			}
			$layout_folder = dirname($layout['theme']);
			if( $layout_folder == $folder ){
				if( $config['gpLayout'] == $layout_id ){
					$message = $langmessage['delete_default_layout'];
					return false;
				}
			}
		}
		return true;
	}

}
