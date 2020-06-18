<?php
defined('is_running') or die('Not an entry point...');

//for output handlers, see admin_theme_content.php for more info
global $GP_ARRANGE,$gpOutConf,$gpOutStarted, $GP_GADGET_CACHE, $gpOutHead;

$gpOutStarted = $gpOutHead = false;
$GP_ARRANGE = true;
$GP_NESTED_EDIT = false;
$gpOutConf = array();
$GP_GADGET_CACHE = array();


//named menus should just be shortcuts to the numbers in custom menu
//	custom menu format: $top_level,$bottom_level,$expand_level

//custom menu: 0,0,0,0
$gpOutConf['FullMenu']['method']		= array('gpOutput','GetFullMenu');
$gpOutConf['FullMenu']['link']			= 'all_links';

//custom menu: 0,0,1,1
$gpOutConf['ExpandMenu']['method']		= array('gpOutput','GetExpandMenu');
$gpOutConf['ExpandMenu']['link']		= 'expanding_links';

//custom menu: 0,0,2,1
$gpOutConf['ExpandLastMenu']['method']	= array('gpOutput','GetExpandLastMenu');
$gpOutConf['ExpandLastMenu']['link']	= 'expanding_bottom_links';

//custom menu: 0,1,0,0
$gpOutConf['Menu']['method']			= array('gpOutput','GetMenu');
$gpOutConf['Menu']['link']				= 'top_level_links';

//custom menu: 1,0,0,0
$gpOutConf['SubMenu']['method']			= array('gpOutput','GetSubMenu');
$gpOutConf['SubMenu']['link']			= 'subgroup_links';

//custom menu: 0,2,0,0
$gpOutConf['TopTwoMenu']['method']		= array('gpOutput','GetTopTwoMenu');
$gpOutConf['TopTwoMenu']['link']		= 'top_two_links';

//custom menu: does not translate, this pays no attention to grouping
$gpOutConf['BottomTwoMenu']['method']	= array('gpOutput','GetBottomTwoMenu');
$gpOutConf['BottomTwoMenu']['link']		= 'bottom_two_links';

//custom menu: 1,2,0,0
$gpOutConf['MiddleSubMenu']['method']	= array('gpOutput','GetSecondSubMenu');
$gpOutConf['MiddleSubMenu']['link']		= 'second_sub_links';

//custom menu: 2,3,0,0
$gpOutConf['BottomSubMenu']['method']	= array('gpOutput','GetThirdSubMenu');
$gpOutConf['BottomSubMenu']['link']		= 'third_sub_links';

$gpOutConf['CustomMenu']['method']		= array('gpOutput','CustomMenu');

$gpOutConf['Extra']['method']			= array('gpOutput','GetExtra');
//$gpOutConf['Text']['method']			= array('gpOutput','GetText'); //use Area() and GetArea() instead

/* The following methods should be used with gpOutput::Fetch() */
$gpOutConf['Gadget']['method']			= array('gpOutput','GetGadget');


class gpOutput{

	/*
	 *
	 * Request Type Functions
	 * functions used in conjuction with $_REQUEST['gpreq']
	 *
	 */

	function Prep(){
		global $page;
		if( !isset($page->rewrite_urls) ){
			return;
		}

		foreach($page->rewrite_urls as $key => $value){
			output_add_rewrite_var($key,$value);
		}

	}

	function Flush(){
		global $page;
		header('Content-Type: text/html; charset=utf-8');
		GetMessages();
		echo $page->contentBuffer;
	}

	function Content(){
		global $page;
		header('Content-Type: text/html; charset=utf-8');
		GetMessages();
		$page->GetGpxContent();
	}

	function BodyAsHTML(){
		global $page,$GP_GETALLGADGETS;

		$GP_GETALLGADGETS = false;
		$page->head_script .= 'var gp_bodyashtml = true;';


		header('Content-Type: text/html; charset=utf-8');
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		echo '<html xml:lang="en" xmlns="http://www.w3.org/1999/xhtml" lang="en">';
		echo '<head>';
		gpOutput::getHead();
		echo '</head>';
		echo '<body class="gpbody">';
		GetMessages();

		if( $page->pagetype == 'admin_display' ){
			echo '<div id="gp_admin_html">';
			$page->GetGpxContent();
			echo '</div>';
		}else{
			$page->GetGpxContent();
		}

		echo '</body>';
		echo '</html>';
	}

	function Template(){
		global $page,$GP_ARRANGE,$GP_STYLES,$GP_GETALLGADGETS;
		gpOutput::TemplateSettings();
		$themePath = $page->theme_dir.'/template.php';
		header('Content-Type: text/html; charset=utf-8');
		require($themePath);
	}

	function TemplateSettings(){
		global $page, $GP_STYLES, $GP_GETALLGADGETS;
		$GP_GETALLGADGETS = true;
		$settings_path = $page->theme_dir.'/settings.php';
		if( file_exists($settings_path) ){
			require($settings_path);
		}
	}


	/**
	 * Add a Header to the response
	 * The header will be discarded if it's an ajax request or similar
	 * @static
	 */
	function AddHeader($header, $replace = true, $code = false){
		if( !empty($_REQUEST['gpreq']) ){
			return false;
		}
		if( $code ){
			common::status_header($code,$header);
		}else{
			header($header,$replace);
		}
		return true;
	}


	/*
	 *
	 * Content Area Functions
	 *
	 */


	/* static */
	function GetContainerID($name){
		static $indices;
		if( !isset($indices[$name]) ){
			$indices[$name] = 0;
		}else{
			$indices[$name]++;
		}
		return $name.'_'.$indices[$name];
	}


	/**
	 * Fetch the output and return as a string
	 *
	 */
	function Fetch($default,$arg=''){
		global $gpOutHead;
		if( $gpOutHead ){
			if( common::LoggedIn() ){
				echo '<p>gpOutput::Fetch() should be called before getHead()</p>';
			}
			return;
		}

		ob_start();
		gpOutput::Get($default,$arg);
		return ob_get_clean();
	}


	function Get($default,$arg=''){
		global $config,$langmessage,$page,$gpLayouts,$gpOutConf;

		$outSet = false;
		$outKeys = false;

		$layout_info =& $gpLayouts[$page->gpLayout];


		// pre 2.2.0.2 container id's
		// if someone is editing their theme, and moves handlers around, then these will get mixed up as well!
		if( is_array($layout_info)
			&& !isset($layout_info['hander_v'])
			&& isset($layout_info['handlers'])
			&& (count($layout_info['handlers']) > 0) ){

			$container_id = gpOutput::GetContainerID($default);

		// container id that includes the argument to prevent mixups when template.php files get edited
		}else{
			$container_id = $default.':'.substr($arg,0,10);
			$container_id = str_replace(array('+','/','='),array('','',''),base64_encode($container_id));
			$container_id = gpOutput::GetContainerID($container_id);
		}


		if( isset($layout_info) && isset($layout_info['handlers']) ){
			$handlers =& $layout_info['handlers'];

			if( isset($handlers[$container_id]) ){
				$outKeys = $handlers[$container_id];
				$outSet = true;
			}
		}

		//default values
		if( !$outSet && isset($gpOutConf[$default]) ){
			$outKeys[] = trim($default.':'.$arg,':');
		}

		gpOutput::ForEachOutput($outKeys,$container_id);

	}

	function ForEachOutput($outKeys,$container_id){

		if( !is_array($outKeys) || (count($outKeys) == 0) ){

			$info = array();
			$info['gpOutCmd'] = '';
			gpOutput::CallOutput($info,$container_id);
			return;
		}

		foreach($outKeys as $gpOutCmd){

			$info = gpOutput::GetgpOutInfo($gpOutCmd);
			if( $info === false ){
				trigger_error('gpOutCmd <i>'.$gpOutCmd.'</i> not set');
				continue;
			}
			$info['gpOutCmd'] = $gpOutCmd;
			gpOutput::CallOutput($info,$container_id);
		}
	}

	/* static */
	function GetgpOutInfo($gpOutCmd){
		global $gpOutConf,$config;

		$key = $gpOutCmd = trim($gpOutCmd,':');
		$info = false;
		$arg = '';
		$pos = strpos($key,':');
		if( $pos > 0 ){
			$arg = substr($key,$pos+1);
			$key = substr($key,0,$pos);
		}


		if( isset($gpOutConf[$key]) ){
			$info = $gpOutConf[$key];
		}elseif( isset($config['gadgets'][$key]) ){
			$info = $config['gadgets'][$key];
			$info['is_gadget'] = true;
		}else{
			return false;
		}
		$info['key'] = $key;
		$info['arg'] = $arg;
		$info['gpOutCmd'] = $gpOutCmd;

		return $info;
	}

	/* static */
	function GpOutLabel($key){
		global $gpOutConf,$langmessage;

		$info = gpOutput::GetgpOutInfo($key);

		$label = $key;
		if( isset($info['link']) && isset($langmessage[$info['link']]) ){
			$label = $langmessage[$info['link']];
		}
		return str_replace(array(' ','_',':'),array('&nbsp;','&nbsp;',':&nbsp;'),$label);
	}


	function CallOutput($info,$container_id){
		global $dataDir,$GP_ARRANGE,$page,$langmessage,$gpOutStarted,$GP_MENU_LINKS,$GP_MENU_CLASS,$gp_current_container;
		$gp_current_container = $container_id;
		$gpOutStarted = true;


		if( isset($info['disabled']) ){
			return;
		}

		//gpOutCmd identifies the output function used, there can only be one
		if( !isset($info['gpOutCmd']) ){
			trigger_error('gpOutCmd not set for $info in CallOutput()');
			return;
		}

		$param = $container_id.'|'.$info['gpOutCmd'];
		$class = 'gpArea_'.str_replace(':','_',trim($info['gpOutCmd'],':'));
		$innerLinks = '';
		$id = '';
		$permission = gpOutput::ShowEditLink('Admin_Theme_Content');


		//for theme content arrangement
		if( $GP_ARRANGE && $permission && isset($GLOBALS['GP_ARRANGE_CONTENT'])  ){
			$empty_container = empty($info['gpOutCmd']); //empty containers can't be removed and don't have labels
			$class .= ' output_area';

			$innerLinks .= '<div class="gplinks" style="display:none">';
			$innerLinks .= common::Link('Admin_Theme_Content',$param,'cmd=drag&layout='.urlencode($page->gpLayout).'&dragging='.urlencode($param).'&to=%s',' style="display:none" name="creq" class="dragdroplink"'); //drag-drop link
			if( !$empty_container ){
				$innerLinks .= '<div class="output_area_label">';
				$innerLinks .= ' '.gpOutput::GpOutLabel($info['gpOutCmd']);
				$innerLinks .= '</div>';
			}
			$innerLinks .= '<div class="output_area_link">';
			if( !$empty_container ){
				$innerLinks .= ' '.common::Link('Admin_Theme_Content','Remove','cmd=rm&layout='.urlencode($page->gpLayout).'&param='.$param,' name="creq"');
			}
			$innerLinks .= ' '.common::Link('Admin_Theme_Content','Insert','cmd=insert&layout='.urlencode($page->gpLayout).'&param='.$param,' name="admin_box"');
			$innerLinks .= '</div>';
			$innerLinks .= '</div>';

		}

		$arrange_links = gpOutput::ArrangeLinks($info);

		//editable links only .. other editable_areas are handled by their output functions
		if( $permission ){
			$menu_marker = false;
			if( isset($info['link']) ){
				$label = $langmessage[$info['link']];
				$class .=  ' editable_area';
				$menu_marker = true;

				$edit_link = gpOutput::EditAreaLink($edit_index,'Admin_Theme_Content',$langmessage['edit'],'cmd=editlinks&layout='.urlencode($page->gpLayout).'&handle='.$param,' rel="links" name="admin_box" title="'.$label.'" ');
				echo '<span style="display:none;" id="ExtraEditLnks'.$edit_index.'">';
				echo $edit_link;
				echo $arrange_links;
				echo common::Link('Admin_Menu',$langmessage['file_manager'],'',' style="display:none"');
				echo '</span>';

				$id = 'id="ExtraEditArea'.$edit_index.'"';

			}elseif( isset($info['key']) && ($info['key'] == 'CustomMenu') ){

				$edit_link = gpOutput::EditAreaLink($edit_index,'Admin_Theme_Content',$langmessage['edit'],'cmd=editcustom&layout='.urlencode($page->gpLayout).'&handle='.$param,' rel="links" name="admin_box" title="'.$langmessage['Links'].'" ');
				echo '<span style="display:none;" id="ExtraEditLnks'.$edit_index.'">';
				echo $edit_link;
				echo $arrange_links;
				echo common::Link('Admin_Menu',$langmessage['file_manager'],'',' style="display:none"');
				echo '</span>';

				$id = 'id="ExtraEditArea'.$edit_index.'"';
				$class .=  ' editable_area';
				$menu_marker = true;
			}

			//for menu arrangement, admin_menu_new.js
			if( $menu_marker ){
				echo '<div class="menu_marker" style="display:none">';
				echo '<input type="hidden" value="'.htmlspecialchars($info['gpOutCmd']).'" />';
				echo '<input type="hidden" value="'.htmlspecialchars($GP_MENU_LINKS).'" />';
				echo '<input type="hidden" value="'.htmlspecialchars($GP_MENU_CLASS).'" />';
				echo '</div>';
			}

		}


		echo '<div class="'.$class.' GPAREA" '.$id.'>';
		echo $innerLinks;

		gpOutput::ExecInfo($info);

		echo '</div>';

		$GP_ARRANGE = true;
		$gp_current_container = false;
	}

	function ExecInfo($info){
		global $dataDir,$GP_GADGET_CACHE;

		//retreive from gadget cache if set
		if( isset($info['gpOutCmd']) && isset($GP_GADGET_CACHE[$info['gpOutCmd']]) ){
			echo $GP_GADGET_CACHE[$info['gpOutCmd']];
			return;
		}

		if( isset($info['addon']) ){
			gpPlugin::SetDataFolder($info['addon']);
		}

		$empty = true;

		//data
		if( isset($info['data']) && file_exists($dataDir.$info['data']) ){
			include($dataDir.$info['data']);
			$empty = false;
		}

		//script
		if( isset($info['script']) && file_exists($dataDir.$info['script']) ){
			include_once($dataDir.$info['script']);
			$empty = false;
		}

		//class & method
		$info += array('arg'=>'');
		if( !empty($info['class']) && class_exists($info['class']) ){
			$object = new $info['class'](); //should $arg and $info be passed to class
			$empty = false;

			if( !empty($info['method']) && method_exists($object,$info['method']) ){
				$args[0] = call_user_func(array($object, $info['method']),$info['arg'], $info);
			}
		}elseif( !empty($info['method']) && is_callable($info['method']) ){
			call_user_func($info['method'],$info['arg'],$info);
			$empty = false;
		}


		if( $empty && common::LoggedIn() ){
			// body.edit_layout .GPAREA{min-height:20px;} css instead
			// echo '&nbsp;';
		}

		gpPlugin::ClearDataFolder();

	}

	function ShowEditLink($permission=false){
		global $GP_NESTED_EDIT;

		if( $permission ){
			return !$GP_NESTED_EDIT && common::LoggedIn() && admin_tools::HasPermission($permission);
		}
		return !$GP_NESTED_EDIT && common::LoggedIn();
	}

	function EditAreaLink(&$index,$href,$label,$query='',$attr=''){
		static $count = 0;
		$count++;
		$index = $count; //since &$index is passed by reference

		$attr .= ' style="display:none" class="ExtraEditLink" id="ExtraEditLink'.$index.'"';
		return common::Link($href,$label,$query,$attr);
	}

	//arrange areas without clicking the "Edit This Layout" link
	function ArrangeLinks($info){
		global $page, $gp_current_container, $GP_ARRANGE;

		if( !$gp_current_container || !$GP_ARRANGE || !isset($info['gpOutCmd']) || !gpOutput::ShowEditLink('Admin_Theme_Content') ){
			return;
		}

		return;

		$param = $gp_current_container.'|'.$info['gpOutCmd'];
		$links = '';

		$links .= common::Link('Admin_Theme_Content','Insert','cmd=insert&layout='.urlencode($page->gpLayout).'&param='.$param,' name="admin_box" style="display:none"');

		if( !empty($info) ){
			$links .= common::Link('Admin_Theme_Content','Remove aaa','cmd=rm&layout='.urlencode($page->gpLayout).'&param='.$param,' name="creq"  style="display:none"');
		}

		return $links;
	}



	/**
	 * Unless the gadget area is customized by the user, this function will output all active gadgets
	 * If the area has been reorganized, it will output the customized areas
	 * This function is not called from gpOutput::Get('GetAllGadgets') so that each individual gadget area can be used as a drag area
	 *
	 */
	function GetAllGadgets(){
		global $config,$page,$gpLayouts,$GP_GETALLGADGETS;

		if( isset($GLOBALS['GP_ARRANGE_CONTENT']) ){
			echo '<div style="display:none" id="gp_getallgadgetscall"></div>';
		}

		if( !$GP_GETALLGADGETS ){
			if( common::LoggedIn() ){
				echo '<p class="gp_notice">$GP_GETALLGADGETS should be set to true when using GetAllGadgets() in the template.php for this theme.</p>';
			}
			return;
		}

		//if we have handler info
		if( isset($gpLayouts[$page->gpLayout]['handlers']['GetAllGadgets']) ){
			gpOutput::ForEachOutput($gpLayouts[$page->gpLayout]['handlers']['GetAllGadgets'],'GetAllGadgets');
			return;
		}

		//show all gadgets if not changes have been made
		if( !empty($config['gadgets']) ){
			foreach($config['gadgets'] as $gadget => $info){
				if( isset($info['addon']) ){
					$info['gpOutCmd'] = $info['key'] = $gadget;
					gpOutput::CallOutput($info,'GetAllGadgets');
				}
			}
			return;
		}

		//Show the area as editable if there isn't anything to show
		$info = array();
		$info['gpOutCmd'] = '';
		gpOutput::CallOutput($info,'GetAllGadgets');
	}


	/**
	 * Get a Single Gadget
	 * This method should be called using gpOutput::Fetch('Gadget',$gadget_name) before the
	 *
	 */
	function GetGadget($id,$info=array()){
		global $config,$gpOutHead;

		if( $gpOutHead ){
			if( common::LoggedIn() ){
				echo '<p>gpOutput::Fetch("Gadget") should be called before getHead()</p>';
			}
			return;
		}

		if( !isset($config['gadgets'][$id]) ){
			return;
		}

		$info = $config['gadgets'][$id];
		if( !isset($info['addon']) ){
			return;
		}

		gpOutput::ExecInfo($info);
	}

	/**
	 * Prepare the gadget content before getting template.php so that gadget functions can add css and js to the head
	 * @return null
	 */
	function PrepGadgetContent(){
		global $gpLayouts, $page, $config, $GP_GADGET_CACHE, $GP_GETALLGADGETS;

		if( isset($GLOBALS['GP_ARRANGE_CONTENT']) ){
			if( $GP_GETALLGADGETS ){
				$page->head_script .= 'var gp_getallgadgets = true;';
			}else{
				$page->head_script .= 'var gp_getallgadgets = false;';
			}
		}

		$gadget_info = gpOutput::WhichGadgets($page->gpLayout,$GP_GETALLGADGETS);

		foreach($gadget_info as $gpOutCmd => $info){
			if( !isset($GP_GADGET_CACHE[$gpOutCmd]) ){
				ob_start();
				gpOutput::ExecInfo($info);
				$GP_GADGET_CACHE[$gpOutCmd] = ob_get_clean();
			}
		}
	}

	/**
	 * Return information about the gadgets being used in the current layout
	 * @return array
	 */
	function WhichGadgets($layout,$GetAllGadgets){
		global $config,$gpLayouts;

		$gadget_info = $temp_info = array();
		if( !isset($config['gadgets']) ){
			return $gadget_info;
		}
		if( isset($gpLayouts[$layout]['handlers']) ){
			foreach($gpLayouts[$layout]['handlers'] as $handler => $out_cmds){

				//don't prep even if GetAllGadgets is set in the layout's config
				if( $handler == 'GetAllGadgets' && !$GetAllGadgets ){
					continue;
				}
				foreach($out_cmds as $gpOutCmd){
					$temp_info[$gpOutCmd] = gpOutput::GetgpOutInfo($gpOutCmd);
				}
			}
		}

		//add all gadgets if $GetAllGadgets is true and the GetAllGadgets handler isn't overwritten
		if( $GetAllGadgets && !isset($gpLayouts[$layout]['handlers']['GetAllGadgets']) ){
			foreach($config['gadgets'] as $gadget => $temp){
				$temp_info[$gadget] = gpOutput::GetgpOutInfo($gadget);
			}
		}

		foreach($temp_info as $gpOutCmd => $info){
			if( isset($info['is_gadget'])
				&& $info['is_gadget']
				&& !isset($info['disabled'])
				){
					$gadget_info[$gpOutCmd] = $info;
			}
		}

		return $gadget_info;
	}


	/*
	 *
	 * @param string $arg comma seperated argument list: $top_level, $bottom_level, $options
	 *		$top_level  (int)  The upper level of the menu to show, if deeper (in this case > ) than 0, only the submenu is shown
	 *		$bottom_level  (int)  The lower level of menu to show
	 *		$expand_level (int)  The upper level from where to start expanding sublinks, if -1 no expansion
	 * 		$expand_all (int)	Whether or not to expand all levels below $expand_level (defaults to 0)
	 * 		$source_menu (string)	Which menu to use
	 *
	 */
	function CustomMenu($arg,$title=false){
		global $page, $config, $dataDir, $gp_index;

		//from output functions
		if( is_array($title) ){
			$title = $page->title;
		}

		$title_index = false;
		if( isset($gp_index[$title]) ){
			$title_index = $gp_index[$title];
		}

		$args = explode(',',$arg);
		$args += array( 0=>0, 1=>3, 2=>-1, 3=>1, 4=>'' ); //defaults
		list($top_level,$bottom_level,$expand_level,$expand_all,$source_menu) = $args;


		//get menu array
		$source_menu_array = gpOutput::GetMenuArray($source_menu);



		//reduce array to $title => $level
		$menu = array();
		foreach($source_menu_array as $temp_key => $titleInfo){
			if( !isset($titleInfo['level']) ){
				break;
			}
			$menu[$temp_key] = $titleInfo['level'];
		}

		//Reduce for expansion
		//first reduction
		//message('expand level: '.$expand_level);
		if( (int)$expand_level >= 1 ){
			if( $expand_all ){
				$menu = gpOutput::MenuReduce_ExpandAll($menu,$expand_level,$title_index,$top_level);
			}else{
				$menu = gpOutput::MenuReduce_Expand($menu,$expand_level,$title_index,$top_level);
			}
		}


		//Reduce if $top_level >= 0
		//second reduction
		if( (int)$top_level > 0 ){
			//echo 'top level: '.$top_level;
			//message('top: '.$top_level);
			$menu = gpOutput::MenuReduce_Top($menu,$top_level,$title_index);
		}else{
			$top_level = 0;
		}

		//Reduce by trimming off titles below $bottom_level
		// last reduction : in case the selected link is below $bottom_level
		if( $bottom_level > 0 ){
			//message('bottom: '.$bottom_level);
			$menu = gpOutput::MenuReduce_Bottom($menu,$bottom_level);
		}

		gpOutput::OutputMenu($menu,$top_level,$source_menu_array);
	}

	function GetMenuArray($id){
		global $dataDir, $gp_menu;

		if( !empty($id) && isset($config['menus'][$id]) ){
			return $gp_menu;
		}

		$menu_file = $dataDir.'/data/_menus/'.$id.'.php';
		if( !file_exists($menu_file) ){
			return $gp_menu;
		}

		$menu = array();
		require($menu_file);
		return $menu;
	}


	function MenuReduce_ExpandAll($menu,$expand_level,$curr_title_key,$top_level){
		global $page;

		$result_menu = array();
		$submenu = array();
		$foundGroup = false;
		foreach($menu as $title_key => $level){

			if( $level < $expand_level ){
				$submenu = array();
				$foundGroup = false;
			}

			if( $title_key == $curr_title_key ){
				$foundGroup = true;
				$result_menu = $result_menu + $submenu; //not using array_merge because of numeric indexes
			}


			if( $foundGroup ){
				$result_menu[$title_key] = $level;
			}elseif( $level < $expand_level ){
				$result_menu[$title_key] = $level;
			}else{
				$submenu[$title_key] = $level;
			}
		}

		return $result_menu;
	}


	//Reduce titles deeper than $expand_level || $current_level
	function MenuReduce_Expand($menu,$expand_level,$curr_title_key,$top_level){
		global $page;
		$result_menu = array();
		$submenu = array();
		$foundGroup = 0;


		//if $top_level is set, we need to take it into consideration
		$expand_level = max( $expand_level, $top_level);

		//titles higher than the $expand_level
		$good_titles = array();
		foreach($menu as $title_key => $level){
			if( $level < $expand_level ){
				$good_titles[$title_key] = $level;
			}
		}


		if( isset($menu[$curr_title_key]) ){
			$curr_level = $menu[$curr_title_key];
			$good_titles[$curr_title_key] = $menu[$curr_title_key];


			//titles below selected
			// cannot use $submenu because $foundTitle may require titles above the $submenu threshold
			$foundTitle = false;
			foreach($menu as $title_key => $level){

				if( $title_key == $curr_title_key ){
					$foundTitle = true;
					continue;
				}

				if( !$foundTitle ){
					continue;
				}

					if( ($curr_level+1) == $level ){
						$good_titles[$title_key] = $level;
					}elseif( $curr_level < $level ){
						continue;
					}else{
						break;
					}
			}



			//$start_time = microtime();
			//reduce the menu to the current group
			$submenu = gpOutput::MenuReduce_Group($menu,$curr_title_key,$expand_level,$curr_level);
			//message('group: ('.count($submenu).') '.showArray($submenu));


			// titles even-with selected title within group
			$even_temp = array();
			$even_group = false;
			foreach($submenu as $title_key => $level){

				if( $title_key == $curr_title_key ){
					$even_group = true;
					$good_titles = $good_titles + $even_temp;
					continue;
				}

				if( $level < $curr_level ){
					if( $even_group ){
						$even_group = false; //done
					}else{
						$even_temp = array(); //reset
					}
				}

				if( $level == $curr_level ){
					if( $even_group ){
						$good_titles[$title_key] = $level;
					}else{
						$even_temp[$title_key] = $level;
					}
				}
			}


			// titles above selected title, deeper than $expand_level, and within the group
			gpOutput::MenuReduce_Sub($good_titles,$submenu,$curr_title_key,$expand_level,$curr_level);
			gpOutput::MenuReduce_Sub($good_titles,array_reverse($submenu),$curr_title_key,$expand_level,$curr_level);

			//message('time: '.microtime_diff($start_time,microtime()));

		}



		//rebuild $good_titles in order
		// array_intersect_assoc() would be useful here, it's php4.3+ and there's no indication if the order of the first argument is preserved
		foreach($menu as $title => $level){
			if( isset($good_titles[$title]) ){
				$result_menu[$title] = $level;
			}
		}

		return $result_menu;

	}

	// reduce the menu to the group
	function MenuReduce_Group($menu,$curr_title_key,$expand_level,$curr_level){
		$result = array();
		$group_temp = array();
		$found_title = false;

		foreach($menu as $title_key => $level){

			//back at the top
			if( $level < $expand_level ){
				$group_temp = array();
				$found_title = false;
			}


			if( $title_key == $curr_title_key ){
				$found_title = true;
				$result = $group_temp;
			}

			if( $level >= $expand_level ){
				if( $found_title ){
					$result[$title_key] = $level;
				}else{
					$group_temp[$title_key] = $level;
				}
			}
		}

		return $result;
	}

	// titles above selected title, deeper than $expand_level, and within the group
	function MenuReduce_Sub(&$good_titles,$menu,$curr_title_key,$expand_level,$curr_level){
		$found_title = false;
		$test_level = $curr_level;
		foreach($menu as $title_key => $level){

			if( $title_key == $curr_title_key ){
				$found_title = true;
				$test_level = $curr_level;
				continue;
			}

			//after the title is found
			if( !$found_title ){
				continue;
			}
			if( $level < $expand_level ){
				break;
			}
			if( ($level >= $expand_level) && ($level < $test_level ) ){
				$test_level = $level+1; //prevent showing an adjacent menu trees
				$good_titles[$title_key] = $level;
			}
		}
	}

	//Reduce the menu to titles deeper than ($show_level-1)
	function MenuReduce_Top($menu,$show_level,$curr_title_key){
		$result_menu = array();
		$foundGroup = false;

		//current title not in menu, so there won't be a submenu
		if( !isset($menu[$curr_title_key]) ){
			return $result_menu;
		}

		$top_level = $show_level-1;

		foreach($menu as $title_key => $level){

			//no longer in subgroup, we can stop now
			if( $foundGroup && ($level <= $top_level) ){
				//message('no long in subgroup: '.$title_key);
				break;
			}

			if( $title_key == $curr_title_key ){
				//message('found: '.$title_key);
				$foundGroup = true;
			}

			//we're back at the $top_level, start over
			if( $level <= $top_level ){
				$result_menu = array();
				//message('start over: '.$title_key);
				//message('start over: '.showArray($result_menu));
				continue;
			}

			//we're at the correct level, put titles in $result_menu in case $page->title is found
			if( $level > $top_level ){
				$result_menu[$title_key] = $level;
			}
		}

		if( !$foundGroup ){
			return array();
		}

		return $result_menu;
	}


	//Reduce the menu to titles above $bottom_level value
	function MenuReduce_Bottom($menu,$bottom_level){
		$result_menu = array();

		foreach($menu as $title => $level){
			if( $level < $bottom_level ){
				$result_menu[$title] = $level;
			}
		}
		return $result_menu;
	}


	function GetExtra($name='Side_Menu',$info=array()){
		global $dataDir,$langmessage;


		$name = str_replace(' ','_',$name);

		$extra_content = '';
		$file = $dataDir.'/data/_extra/'.$name.'.php';
		if( file_exists($file) ){
			ob_start();
			include($file);
			$extra_content = ob_get_clean();
		}

		$extra_content = gpPlugin::Filter('GetExtra',array($extra_content,$name));

		$wrap = gpOutput::ShowEditLink('Admin_Extra');
		if( $wrap ){

			$edit_link = gpOutput::EditAreaLink($edit_index,'Admin_Extra',$langmessage['edit'],'cmd=edit&file='.$name,' title="'.$name.'" name="inline_edit_generic" ');
			echo '<span style="display:none;" id="ExtraEditLnks'.$edit_index.'">';
			echo $edit_link;
			echo gpOutput::ArrangeLinks($info);
			echo '</span>';

			echo '<div class="editable_area" id="ExtraEditArea'.$edit_index.'">'; // class="edit_area" added by javascript
			echo $extra_content;
			echo '</div>';
		}else{
			echo $extra_content;
		}

	}



	function GetFullMenu($arg=''){
		$source_menu_array = gpOutput::GetMenuArray($arg);
		gpOutput::OutputMenu($source_menu_array,0,$source_menu_array);
	}

	function GetMenu($arg=''){
		$source_menu_array = gpOutput::GetMenuArray($arg);

		$sendMenu = array();
		foreach($source_menu_array as $key => $info){
			if( (int)$info['level'] !== 0 ){
				continue;
			}
			$sendMenu[$key] = true;
		}

		gpOutput::OutputMenu($sendMenu,0,$source_menu_array);
	}

	function GetSecondSubMenu($arg,$info){
		gpOutput::GetSubMenu($arg,$info,1);
	}
	function GetThirdSubMenu($arg,$info){
		gpOutput::GetSubMenu($arg,$info,2);
	}

	function GetSubMenu($arg='',$info=false,$search_level=false){
		global $page;
		$source_menu_array = gpOutput::GetMenuArray($arg);

		$reset_level = 0;
		if( !empty($search_level) ){
			$reset_level = max(0,$search_level-1);
		}


		$menu = array();
		$foundGroup = false;
		foreach($source_menu_array as $key => $titleInfo){
			if( !isset($titleInfo['level']) ){
				break;
			}
			$level = $titleInfo['level'];

			if( $foundGroup ){
				if( $level <= $reset_level ){
					break;
				}
			}

			if( $key == $page->gp_index ){
				$foundGroup = true;
			}

			if( $level <= $reset_level ){
				$menu = array();
				continue;
			}

			if( empty($search_level) ){
				$menu[$key] = $level;
			}elseif( $level == $search_level ){
				$menu[$key] = $level;
			}

		}

		if( !$foundGroup ){
			gpOutput::OutputMenu(array(),$reset_level+1,$source_menu_array);
		}else{
			gpOutput::OutputMenu($menu,$reset_level+1,$source_menu_array);
		}
	}

	function GetTopTwoMenu($arg=''){
		$source_menu_array = gpOutput::GetMenuArray($arg);

		$sendMenu = array();
		foreach($source_menu_array as $key => $titleInfo){
			if( $titleInfo['level'] >= 2 ){
				continue;
			}
			$sendMenu[$key] = true;
		}
		gpOutput::OutputMenu($sendMenu,0,$source_menu_array);
	}

	function GetBottomTwoMenu($arg=''){
		$source_menu_array = gpOutput::GetMenuArray($arg);

		$sendMenu = array();
		foreach($source_menu_array as $key => $titleInfo){
			$level = $titleInfo['level'];

			if( ($level == 1) || ($level == 2) ){
				$sendMenu[$key] = true;
			}
		}
		gpOutput::OutputMenu($sendMenu,1,$source_menu_array);
	}

	function GetExpandLastMenu($arg=''){
		global $page;
		$source_menu_array = gpOutput::GetMenuArray($arg);

		$menu = array();
		$submenu = array();
		$foundGroup = false;
		foreach($source_menu_array as $key => $titleInfo){
			$level = $titleInfo['level'];

			if( ($level == 0) || ($level == 1) ){
				$submenu = array();
				$foundGroup = false;
			}

			if( $key == $page->gp_index ){
				$foundGroup = true;
				$menu = $menu + $submenu; //not using array_merge because of numeric indexes
			}


			if( $foundGroup ){
				$menu[$key] = $level;
			}elseif( ($level == 0) || ($level == 1) ){
				$menu[$key] = $level;
			}else{
				$submenu[$key] = $level;
			}
		}

		gpOutput::OutputMenu($menu,0,$source_menu_array);
	}


	function GetExpandMenu($arg=''){
		global $page;
		$source_menu_array = gpOutput::GetMenuArray($arg);

		$menu = array();
		$submenu = array();
		$foundGroup = false;
		foreach($source_menu_array as $key => $info){
			$level = $info['level'];

			if( $level == 0 ){
				$submenu = array();
				$foundGroup = false;
			}

			if( $key == $page->gp_index ){
				$foundGroup = true;
				$menu = $menu + $submenu; //not using array_merge because of numeric indexes
			}

			if( $foundGroup ){
				$menu[$key] = $level;
			}elseif( $level == 0 ){
				$menu[$key] = $level;
			}else{
				$submenu[$key] = $level;
			}

		}
		gpOutput::OutputMenu($menu,0,$source_menu_array);

	}


	function OutputMenu($menu,$startLevel,$source_menu=false){
		global $langmessage,$page,$GP_MENU_LINKS,$GP_MENU_CLASS,$gp_menu,$gp_titles;

		if( $source_menu === false ){
			$source_menu =& $gp_menu;
		}

		$search = array('{$href_text}','{$attr}','{$label}','{$title}');
		$replace = array();

		if( count($menu) == 0 ){
			echo '<div class="emtpy_menu"></div>'; //an empty <ul> is not valid xhtml
			gpOutput::ResetMenuGlobals();
			return;
		}

		$rmenu = array_reverse( $source_menu, true );
		$childselected = false;
		$selectedLevel = false;
		$result = array();
		$prevLevel = $startLevel;
		$open = false;
		$page_title_full = common::GetUrl($page->title);

		$result[] = "\n\n";
		$result[] = '</ul>';
		foreach($rmenu as $rmenu_key => $menu_info){

			$attr = $class = '';
			//$class_li = '';
			$thisLevel = $menu_info['level'];
			$title = common::IndexToTitle($rmenu_key);


			//create link if in $menu
			if( isset($menu[$rmenu_key]) ){


				//classes
				if( $thisLevel < $prevLevel){
					$class .= 'haschildren ';
					//$class_li .= 'haschildren_li ';
				}

				$selected = false;
				if( isset($menu_info['url']) && ($menu_info['url'] == $page->title || $menu_info['url'] == $page_title_full) ){
					$selected = true;
				}elseif( $title == $page->title ){
					$selected = true;
				}

				if( $selected ){
					$class .= 'selected ';
					//$class_li .= 'selected_li ';
					$childselected = true;
					$selectedLevel = $thisLevel;
				}elseif( $childselected && ($thisLevel < $selectedLevel) ){
					$class .= 'childselected ';
					//$class_li .= 'childselected_li ';
					$selectedLevel = $thisLevel;
				}

				if( !$open ){
					$result[] = '</li>';
				}

				if( $thisLevel < $prevLevel ){

					while( $thisLevel < $prevLevel ){
						$result[] = '<ul><li>';
						$prevLevel--;
					}

				}elseif( $thisLevel > $prevLevel ){

					if( $open ){
						$result[] = '</li><li>';
					}

					while( $thisLevel > $prevLevel ){
						$result[] = '</li></ul>';
						$prevLevel++;
					}

				}elseif( $open ){
					$result[] = '</li><li>';
				}

				if( !empty($class) ){
					$attr = 'class="'.trim($class).'"';
				}



				//external
				if( isset($menu_info['url']) ){
					if( empty($menu_info['label']) ){
						$menu_info['label'] = $menu_info['url'];
					}
					if( empty($menu_info['title_attr']) ){
						$menu_info['title_attr'] = $menu_info['label'];
					}
					if( isset($menu_info['new_win']) ){
						$attr .= ' target="_blank"';
					}

					$result[] = '<a href="'.htmlspecialchars($menu_info['url']).'" '.$attr.' title="'.htmlspecialchars($menu_info['title_attr']).'">'.htmlspecialchars($menu_info['label']).'</a>';

				}else{

					$label = common::GetLabel($title,false);
					$title_info =& $gp_titles[$rmenu_key];
					if( isset($title_info['browser_title']) ){
						$browser_title = $gp_titles[$rmenu_key]['browser_title'];
					}else{
						$browser_title = htmlspecialchars($label); //for ampersands
					}
					if( !empty($title_info['rel']) ){
						$attr .= ' rel="'.$title_info['rel'].'"';
					}


					if( !empty($GP_MENU_LINKS) ){
						$replace = array();
						$replace[] = common::GetUrl($title);
						$replace[] = $attr;
						$replace[] = $label;
						$replace[] = $browser_title;;

						$result[] = str_replace($search,$replace,$GP_MENU_LINKS);
					}else{
						$result[] = common::Link($title,$label,'',$attr.' title="'.$browser_title.'" ');
					}
				}

				$prevLevel = $thisLevel;
				$open = true;
			}


			if( $thisLevel == 0 ){
				$childselected = false;
			}
		}

		//finish it off
		while( (int)$prevLevel >= (int)$startLevel ){
			$result[] = '<ul><li>';
			$prevLevel--;
		}

		//make sure the top is labeled
		if( count($result) > 1 ){
			if( !empty($GP_MENU_CLASS) ){
				$result[count($result)-1] = '<ul class="'.$GP_MENU_CLASS.'"><li>';
			}else{
				$result[count($result)-1] = '<ul class="menu_top"><li>';
			}
		}

		$result[] = "\n";

		$result = array_reverse( $result);

		//don't separate by spaces so css inline can be more functional
		echo implode('',$result);

		gpOutput::ResetMenuGlobals();
	}

	function ResetMenuGlobals(){
		global $GP_MENU_LINKS,$GP_MENU_CLASS;
		$GP_MENU_LINKS = '';
		$GP_MENU_CLASS = '';
		unset($GP_MENU_LINKS);
		unset($GP_MENU_CLASS);
	}




	/*
	 *
	 * Output Additional Areas
	 *
	 */

	/* draggable html and editable text */
	function Area($name,$html){
		global $gpOutConf,$gpOutStarted;
		if( $gpOutStarted ){
			trigger_error('gpOutput::Area() must be called before all other output functions');
			return;
		}
		$name = '[text]'.$name;
		$gpOutConf[$name] = array();
		$gpOutConf[$name]['method'] = array('gpOutput','GetAreaOut');
		$gpOutConf[$name]['html'] = $html;
	}

	function GetArea($name,$text){
		$name = '[text]'.$name;
		gpOutput::Get($name,$text);
	}

	function GetAreaOut($text,$info){
		global $config,$langmessage,$page;
		$name = substr($info['key'],5); //remove the "text:"
		$html =& $info['html'];

		$wrap = gpOutput::ShowEditLink('Admin_Theme_Content');
		if( $wrap ){
			echo gpOutput::EditAreaLink($edit_index,'Admin_Theme_Content',$langmessage['edit'],'cmd=edittext&key='.urlencode($text).'&return='.$page->title,' title="'.urlencode($text).'" name="admin_box" ');
			echo '<div class="editable_area" id="ExtraEditArea'.$edit_index.'">'; // class="edit_area" added by javascript
		}

		if( isset($config['customlang'][$text]) ){
			$text = $config['customlang'][$text];

		}elseif( isset($langmessage[$text]) ){
			$text =  $langmessage[$text];
		}

		echo str_replace('%s',$text,$html); //in case there's more than one %s

		if( $wrap ){
			echo '</div>';
		}
	}

	/*
	 *
	 * editable text, not draggable
	 *
	 */

	/* similar to ReturnText() but links to script for editing all addon texts */
	// the $html parameter should primarily be used when the text is to be placed inside of a link or other element that cannot have a link and/or span as a child node
	function GetAddonText($key,$html='%s'){
		global $addonFolderName;

		if( !$addonFolderName ){
			return gpOutput::ReturnText($key,$html);
		}

		$query = 'cmd=addontext&addon='.urlencode($addonFolderName).'&key='.urlencode($key);
		return gpOutput::ReturnTextWorker($key,$html,$query);
	}

	/* deprecated, use ReturnText() */
	function GetText($key,$html='%s'){
		echo gpOutput::ReturnText($key,$html);
	}

	function ReturnText($key,$html='%s'){
		$query = 'cmd=edittext&key='.urlencode($key);
		return gpOutput::ReturnTextWorker($key,$html,$query);
	}

	function ReturnTextWorker($key,$html,$query){
		global $langmessage;

		$result = '';
		$wrap = gpOutput::ShowEditLink('Admin_Theme_Content');
		if( $wrap ){

			$title = htmlspecialchars(strip_tags($key));
			if( strlen($title) > 20 ){
				$title = substr($title,0,20).'...'; //javscript may shorten it as well
			}

			echo gpOutput::EditAreaLink($edit_index,'Admin_Theme_Content',$langmessage['edit'],$query,' title="'.$title.'" name="admin_box" ');
			$result .= '<span class="editable_area" id="ExtraEditArea'.$edit_index.'">'; // class="edit_area" added by javascript
		}

		$text = gpOutput::SelectText($key);
		$result .= str_replace('%s',$text,$html); //in case there's more than one %s

		if( $wrap ){
			$result .= '</span>';
		}

		return $result;

	}



	/**
	 * Returns the user translated string if it exists or $key (the untranslated string) if a translation doesn't exist
	 *
	 */
	function SelectText($key){
		global $config,$langmessage;

		$text = $key;
		if( isset($config['customlang'][$key]) ){
			$text = $config['customlang'][$key];

		}elseif( isset($langmessage[$key]) ){
			$text = $langmessage[$key];
		}
		return $text;
	}


	/**
	 * Generate and output the <head> portion of the html document
	 *
	 */
	function GetHead() {
		global $config, $page, $gpOutHead;
		$gpOutHead = true;

		echo "\n<!-- section start -->";
		gpOutput::DoctypeMeta();
		gpOutput::PrepGadgetContent();

		gpPlugin::Action('GetHead');

		if( common::LoggedIn() ){
			common::AddColorBox();
		}

		gpOutput::GetHead_TKD();
		gpOutput::GetHead_CSS(); //css before js so it's available to scripts
		gpOutput::GetHead_Lang();
		gpOutput::GetHead_JS();
		gpOutput::GetHead_InlineJS();
		echo "\n<meta name=\"generator\" content=\"gpEasy CMS\" />";

		//gadget info
		if( !empty($config['addons']) ){
			foreach($config['addons'] as $addon_info){
				if( !empty($addon_info['html_head']) ){
					echo "\n";
					echo $addon_info['html_head'];
				}
			}
		}

		if( !empty($page->head) ){
			echo $page->head;
		}


		echo "\n<!-- section end -->\n";
	}

	/**
	 * Output the title and keywords for the current html document
	 */
	function GetHead_TKD(){
		global $config, $page;

		//start keywords;
		$keywords = array();
		if( !empty($page->TitleInfo['keywords']) ){
			$keywords += explode(',',$page->TitleInfo['keywords']);
		}


		//title
		echo "\n<title>";
		if( !empty($page->TitleInfo['browser_title']) ){

			$page_title = $page->TitleInfo['browser_title'];
			$keywords[] = $page->TitleInfo['browser_title'];
		}else{
			$page_title = htmlspecialchars($page->label);
		}
		echo $page_title;
		if( !empty($page_title) && !empty($config['title']) ){
			echo ' - ';
		}
		echo $config['title'].'</title>';

		if( !empty($page->TitleInfo['rel']) ){
			echo "\n".'<meta name="robots" content="'.$page->TitleInfo['rel'].'" />';
		}

		//keywords
		$keywords[] = htmlspecialchars($page->label);
		$site_keywords = explode(',',$config['keywords']);
		$keywords = array_merge($keywords,$site_keywords);
		$keywords = array_unique($keywords);
		$keywords = array_diff($keywords,array(''));
		echo "\n<meta name=\"keywords\" content=\"".implode(', ',$keywords)."\" />";


		//description
		$description = '';
		if( !empty($page->TitleInfo['description']) ){
			$description .= $page->TitleInfo['description'].' ';
		}
		if( !empty($config['desc']) ){
			$description .= htmlspecialchars($config['desc']);
		}
		if( !empty($description) ){
			echo "\n<meta name=\"description\" content=\"".$description."\" />";
		}
	}


	function GetHead_InlineJS(){
		global $page, $linkPrefix, $config;

		/* The IE7 variable is deprecated as of 2.0b1 and will be removed from a future release */
		echo "\n<!--[if IE 7]><script type=\"text/javascript\">IE7=true;</script><![endif]-->";

		ob_start();
		if( gpdebugjs ){
			echo 'var debugjs=true;';
		}

		if( common::LoggedIn() ){
			echo 'var isadmin=true';
			echo ',gpBLink="'.str_replace(' ','%20',$linkPrefix).'"'; //here because of index.php
			gpsession::GPUIVars();
			if( !admin_tools::CanRemoteInstall() ){
				echo ',gpRem=false';
			}
			echo ',post_nonce="'.common::new_nonce('post',true).'"';
			echo ';';
			gpOutput::GP_STYLES();
		}

		echo $page->head_script;

		if( !empty($page->jQueryCode) ){
			echo '$(function(){';
			echo $page->jQueryCode;
			echo '});';
		}

		if( !$config['combinejs'] ){
			common::JsEnd();
		}

		$inline = ob_get_clean();


		if( !empty($inline) ){
			echo "\n<script type=\"text/javascript\">/* <![CDATA[ */\n";
			echo $inline;
			echo "\n/* ]]> */</script>";
		}
	}


	function GetHead_Lang(){
		global $langmessage;
		if( !common::LoggedIn() ){
			return;
		}

		echo "\n<script type=\"text/javascript\">/* <![CDATA[ */";
		$lang_values = array('cancel'=>'ca','update'=>'up','caption'=>'cp');
		echo 'var gplang = {';
		$comma = '';
		foreach($lang_values as $from_key => $to_key){
			echo $comma;
			echo $to_key.':"'.str_replace(array('\\','"'),array('\\\\','\"'),$langmessage[$from_key]).'"';
			$comma = ',';
		}
		echo "}; /* ]]> */</script>";
	}


	function GetHead_JS(){
		global $page, $config, $wbMessageBuffer;

		$js_files = array();

		//use local copy unless specified otherwise
		if( isset($config['jquery']) && $config['jquery'] == 'google' ){
			echo "\n<script src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js\" type=\"text/javascript\"></script>";

		}else{
			$js_files[] = '/include/thirdparty/js/jquery.1.6.4.js'; //see also jquery in install.php
		}

		//always include javascript when there are messages
		if( $page->admin_js || !empty($page->jQueryCode) || !empty($wbMessageBuffer) ){
			$js_files[] = '/include/js/main.js';
		}

		if( isset($page->head_js) && is_array($page->head_js) ){
			$js_files = array_merge($js_files,$page->head_js);
		}

		if( common::LoggedIn() ){
			$js_files[] = '/include/js/admin.js';
		}

		if( count($js_files) == 0 ){
			return;
		}

		if( !$config['combinejs'] || $page->head_force_inline ){
			echo "\n<script type=\"text/javascript\">";
			common::jsStart();
			echo '</script>';
		}

		gpOutput::CombineFiles($js_files,'js',$config['combinejs']);
	}


	function GetHead_CSS(){
		global $page, $config;


		$css_files = array();
		$css_files[] = '/include/css/additional.css';

		if( isset($page->css_user) && is_array($page->css_user) ){
			$css_files = array_merge($css_files,$page->css_user);
		}


		//after other styles, so themes can overwrite defaults
		$theme_stylesheet = false;
		if( !empty($page->theme_name) && $page->get_theme_css === true ){
			if($page->theme_is_addon ){
				$css_path = '/data/_themes/';
			}else{
				$css_path = '/themes/';
			}
			$css_files[] = $theme_stylesheet = $css_path.str_replace('&','&amp;',$page->theme_name.'/'.$page->theme_color).'/style.css';
		}


		//important admin css that shouldn't be overwritten by themes
		if( common::LoggedIn() ){
			$css_files[] = '/include/css/admin.css';
		}


		//styles that need to override admin.css should be added to $page->css_admin;
		if( isset($page->css_admin) && is_array($page->css_admin) ){
			$css_files = array_merge($css_files,$page->css_admin);
		}

		gpOutput::CombineFiles($css_files,'css',$config['combinecss'],$theme_stylesheet);
	}


	/**
	 * Add appropriate meta charset if it hasn't already been set
	 * Look at the beginning of the document to see what kind of doctype the current template is using
	 * See http://www.w3schools.com/tags/tag_doctype.asp for description of different doctypes
	 *
	 */
	function DoctypeMeta(){
		$doc_start = strtolower(ob_get_contents());

		//charset already set
		if( strpos($doc_start,'charset=') !== false ){
			return;
		}

		echo "\n";

		// html5
		// spec states this should be "the first element child of the head element"
		if( strpos($doc_start,'<!doctype html>') !== false ){
			echo '<meta charset="UTF-8" />';
			return 'html5';
		}

		// loose
		// <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
		if( strpos($doc_start,'loose.dtd') !== false ){
			echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
			return 'loose';
		}


		// strict
		// <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
		// <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
		// <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		if( strpos($doc_start,'strict.dtd') !== false
			|| strpos($doc_start,'xhtml11.dtd') !== false
			){
			echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
			return 'strict';
		}

		// else transitional
		// <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		// <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
		// <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		return 'transitional';
	}


	/**
	 * Combine the files in $files into a combine.php request
	 * If $page->head_force_inline is true, resources will be included inline in the document
	 *
	 * @param array $files Array of files relative to $dataDir
	 * @param string $type The type of resource being combined
	 * @param string $theme_stylesheet The current theme identifier
	 *
	 */
	function CombineFiles($files,$type,$combine,$theme_stylesheet=false){
		global $config,$page;

		includeFile('combine.php');

		$html = "\n".'<script type="text/javascript" src="%s" %s></script>';
		if( $type == 'css' ){
			$html = "\n".'<link rel="stylesheet" type="text/css" href="%s" %s/>';
		}

		$combine_request = array();
		foreach($files as $i => $file){


			// Force resources to be included inline
			// CheckFile will fix the $file path if needed
			if( $page->head_force_inline ){

				$full_path = gp_combine::CheckFile($file,false);
				if( !$full_path ) continue;

				echo "\n";
				if( $type == 'css' ){
					echo '<style type="text/css">';
					readfile($full_path);
					echo '</style>';
				}else{
					echo '<script type="text/javascript">/* <![CDATA[ *';
					readfile($full_path);
					echo '/* ]]> */</script>';
				}
				continue;
			}

			// Combine multiple resources into one
			// CheckFile will be
			if( $combine ){
				$combine_request[] = $type.'[]='.rawurlencode($file);
				continue;
			}

			// Include resources individually
			// CheckFile will fix the $file path if needed
			gp_combine::CheckFile($file,false);
			$id = ( $file == $theme_stylesheet ? 'id="theme_stylesheet"' : '' );
			echo sprintf($html,common::GetDir($file),$id);
		}

		if( count($combine_request) == 0 ) return;

		$id = ( $type == 'css' ? 'id="theme_stylesheet"' : '' );

		$combine_request[] = 'etag='.gp_combine::GenerateEtag($files);
		$combine_request = implode('&amp;',$combine_request);


		//message('<a href="'.common::GetDir($combine_request).'">combined files: '.$type.'</a>');
		echo sprintf($html,common::GetDir('/include/combine.php?'.$combine_request),$id);
	}


	function GP_STYLES(){
		global $GP_STYLES;

		//http://www.w3.org/TR/html4/types.html#type-name
		// excluding the period character because of it's use for css classes
		$name_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_:';

		echo "\nvar gp_styles = [";
		if( is_array($GP_STYLES) && count($GP_STYLES) > 0 ){

			$comma = '';

			foreach($GP_STYLES as $selector){
				$full_selector = $selector = trim($selector);
				$id = '';
				$class = '';

				if( strlen($selector) < 1 ){
					continue;
				}
				if( strpos($selector,'"') !== false ){
					continue;
				}

				//get the id
				$pos = strpos($selector,'#'); //should be 0
				if( $pos !== false ){
					$start = $pos+1;
					$len = strspn($selector,$name_chars,$start);
					$id = substr($selector,$start,$len);
					$selector = substr($selector,$start+$len);
				}

				do{
					$continue = false;
					$pos = strpos($selector,'.');
					if( $pos !== false ){
						$continue = true;
						$start = $pos+1;
						$len = strspn($selector,$name_chars,$start);
						if( $len > 0 ){
							$class = substr($selector,$start,$len).' ';
						}
						$selector = substr($selector,$start+$len);
					}

				}while($continue);

				echo $comma;
				echo '{';
				echo 'selector:"'.$full_selector.'",';
				echo 'bodyId:"'.$id.'",';
				echo 'bodyClass:"'.trim($class).'"';
				echo '}';
				$comma = ',';
			}
		}
		echo '];';
	}

	function DetectBot(){
		$tests[] = 'googlebot';
		$tests[] = 'yahoo! slurp';
		$tests[] = 'msnbot';
		$tests[] = 'ask jeeves';
		$tests[] = 'ia_archiver';
		$tests[] = 'bot';
		$tests[] = 'spider';

		$agent =& $_SERVER['HTTP_USER_AGENT'];
		$agent = strtolower($agent);
		$agent = str_replace($tests,'GP_FOUND_SPIDER',$agent);
		if( strpos($agent,'GP_FOUND_SPIDER') === false ){
			return false;
		}
		return true;
	}


	// displays the login/logout link
	function GetAdminLink(){
		global $config, $out, $langmessage,$page;

		echo ' <span class="sitemap_link">';
		echo common::Link('Special_Site_Map',$langmessage['site_map']);
		echo '</span>';

		echo ' <span class="login_link">';
			if( common::LoggedIn() ){
				echo common::Link($page->title,$langmessage['logout'],'cmd=logout',' name="creq" rel="nofollow,noindex" ');
			}else{
				echo common::Link('Admin_Main',$langmessage['login'],'file='.$page->title,' rel="nofollow,noindex" name="login"');
			}
		echo '</span>';


		$class =& $config['hidegplink'];
		echo ' <span id="powered_by_link" class="'.$class.'">';
		echo 'Powered by <a href="http://gpEasy.com" title="An Easy and Free CMS in PHP">gp|Easy CMS</a>';
		echo '</span>';

		if( common::LoggedIn() ){
			admin_tools::AdminHtml();
		}

		GetMessages();
	}

}
