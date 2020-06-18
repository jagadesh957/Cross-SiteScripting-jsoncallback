<?php
defined('is_running') or die('Not an entry point...');

/*
 * Page/Menu Manager
 *
 * Uses the following other files
 * 		admin_menu_tools.php
 * 		admin_trash.php
 *
 *
 *
 *
 */



defined('gp_max_menu_level') OR define('gp_max_menu_level',6);

includeFile('admin/admin_menu_tools.php');

class admin_menu_new extends admin_menu_tools{

	var $cookie_settings = array();
	var $hidden_levels = array();
	var $search_page = 0;
	var $search_max_per_page = 20;
	var $query_string;

	var $avail_menus = array();
	var $curr_menu_id;
	var $curr_menu_array = false;
	var $is_alt_menu = false;
	var $max_level_index = 3;

	var $main_menu_count;
	var $list_displays = array('search'=>true, 'all'=>true, 'hidden'=>true, 'nomenus'=>true );


	function admin_menu_new(){
		global $langmessage,$page,$config;

		$page->ajaxReplace = array();

		common::LoadJqueryUI();

		$page->css_admin[] = '/include/css/admin_menu_new.css';

		$page->head_js[] = '/include/thirdparty/js/nestedSortable.js';
		$page->head_js[] = '/include/thirdparty/js/jquery_cookie.js';
		$page->head_js[] = '/include/js/admin_menu_new.js';

		$this->max_level_index = max(3,gp_max_menu_level-1);

		$cmd = common::GetCommand();

		$this->avail_menus['gpmenu'] = $langmessage['Main Menu'];
		$this->avail_menus['all'] = $langmessage['All Pages'];
		$this->avail_menus['hidden'] = $langmessage['Not In Main Menu'];
		$this->avail_menus['nomenus'] = $langmessage['Not In Any Menus'];
		$this->avail_menus['search'] = $langmessage['search pages'];

		if( isset($config['menus']) ){
			foreach($config['menus'] as $id => $menu_label){
				$this->avail_menus[$id] = $menu_label;
			}
		}

		//early commands
		switch($cmd){
			case 'altmenu_create':
				$this->AltMenu_Create();
			break;

			case 'rm_menu':
				$this->AltMenu_Remove();
			break;
			case 'alt_menu_rename':
				$this->AltMenu_Rename();
			break;

		}


		//read cookie settings
		if( isset($_COOKIE['gp_menu_prefs']) ){
			parse_str( $_COOKIE['gp_menu_prefs'] , $this->cookie_settings );
		}

		$this->SetMenuID();
		$this->SetMenuArray();
		$this->SetCollapseSettings();
		$this->SetQueryInfo();


		switch($cmd){

			case 'rename_menu_prompt':
				$this->RenameMenuPrompt();
			return;

			//menu creation
			case 'newmenu':
				$this->NewMenu();
			return;

			//rename
			case 'renameform':
				$this->RenameForm(); //will die()
			return;

			case 'renameit':
				$this->RenameFile();
			break;

			case 'hide':
				$this->Hide();
			break;

			case 'drag':
				$this->SaveDrag();
			break;

			case 'trash':
				$this->MoveToTrash();
			break;

			case 'add_hidden':
				$this->AddHidden();
			return;
			case 'new_hidden':
				$this->NewHiddenFile();
			break;
			case 'new_redir':
				$this->NewHiddenFile_Redir();
			return;

			// Page Insertion
			case 'insert_before':
			case 'insert_after':
			case 'insert_child':
				$this->InsertDialog($cmd);
			return;

			case 'restore':
				$this->RestoreFromTrash();
			break;

			case 'insert_from_hidden';
				$this->InsertFromHidden();
			break;

			case 'new_file':
				$this->NewFile();
			break;

			//layout
			case 'layout':
			case 'uselayout':
			case 'restorelayout':
				includeFile('tool/Page_Layout.php');
				$title =& $_GET['title'];
				$page_layout = new page_layout($cmd,$title,'Admin_Menu',$this->query_string);
				if( $page_layout->result() ){
					return;
				}
			break;


			//external links
			case 'new_external':
				$this->NewExternal();
			break;
			case 'edit_external':
				$this->EditExternal();
			return;
			case 'save_external':
				$this->SaveExternal();
			break;


		}

		$this->ShowForm($cmd);

	}

	function Link($href,$label,$query='',$attr='',$nonce_action=false){
		$query = $this->MenuQuery($query);
		return common::Link($href,$label,$query,$attr,$nonce_action);
	}

	function GetUrl($href,$query='',$ampersands=true){
		$query = $this->MenuQuery($query);
		return common::GetUrl($href,$query,$ampersands);
	}

	function MenuQuery($query=''){
		if( !empty($query) ){
			$query .= '&';
		}
		$query .= 'menu='.$this->curr_menu_id;
		if( strpos($query,'page=') !== false ){
			//do nothing
		}elseif( $this->search_page > 0 ){
			$query .= '&page='.$this->search_page;
		}

		//for searches
		if( !empty($_REQUEST['q']) ){
			$query .= '&q='.urlencode($_REQUEST['q']);
		}

		return $query;
	}

	function SetQueryInfo(){

		//search page
		if( isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) ){
			$this->search_page = (int)$_REQUEST['page'];
		}

		//browse query string
		$this->query_string = $this->MenuQuery();
	}

	function SetCollapseSettings(){
		$gp_menu_collapse =& $_COOKIE['gp_menu_hide'];

		$search = '#'.$this->curr_menu_id.'=[';
		$pos = strpos($gp_menu_collapse,$search);
		if( $pos === false ){
			return;
		}

		$gp_menu_collapse = substr($gp_menu_collapse,$pos+strlen($search));
		$pos = strpos($gp_menu_collapse,']');
		if( $pos === false ){
			return;
		}
		$gp_menu_collapse = substr($gp_menu_collapse,0,$pos);
		$gp_menu_collapse = trim($gp_menu_collapse,',');
		$this->hidden_levels = explode(',',$gp_menu_collapse);
		$this->hidden_levels = array_flip($this->hidden_levels);
	}



	//which menu, not the same order as used for $_REQUEST
	function SetMenuID(){

		if( isset($this->curr_menu_id) ){
			return;
		}

		if( isset($_POST['menu']) ){
			$this->curr_menu_id = $_POST['menu'];
		}elseif( isset($_GET['menu']) ){
			$this->curr_menu_id = $_GET['menu'];
		}elseif( isset($this->cookie_settings['gp_menu_select']) ){
			$this->curr_menu_id = $this->cookie_settings['gp_menu_select'];
		}

		if( !isset($this->curr_menu_id) || !isset($this->avail_menus[$this->curr_menu_id]) ){
			$this->curr_menu_id = 'gpmenu';
		}

	}

	function SetMenuArray(){
		global $gp_menu;

		if( isset($this->list_displays[$this->curr_menu_id]) ){
			return;
		}

		//set curr_menu_array
		if( $this->curr_menu_id == 'gpmenu' ){
			$this->curr_menu_array =& $gp_menu;
			$this->is_main_menu = true;
			return;
		}

		$this->curr_menu_array = gpOutput::GetMenuArray($this->curr_menu_id);
		$this->is_alt_menu = true;
	}


	function SaveMenu($menu_and_pages=false){
		global $dataDir;

		if( $this->is_main_menu ){
			return admin_tools::SavePagesPHP();
		}

		if( $this->curr_menu_array === false ){
			return false;
		}

		if( $menu_and_pages && !admin_tools::SavePagesPHP() ){
			return false;
		}

		$menu_file = $dataDir.'/data/_menus/'.$this->curr_menu_id.'.php';
		return gpFiles::SaveArray($menu_file,'menu',$this->curr_menu_array);
	}




	/*
	 * Primary Display
	 *
	 *
	 */
	function ShowForm(){
		global $langmessage,$page;


		$replace_id = '';
		ob_start();

		if( isset($this->list_displays[$this->curr_menu_id]) ){
			$this->SearchDisplay();
			$replace_id = '#gp_menu_available';
			$wrap_start = '<div id="gp_menu_available">';
			$wrap_end = '</div>';
		}else{
			$this->OutputMenu();
			$replace_id = '#admin_menu';
			$wrap_start = '<ul id="admin_menu" class="sortable_menu">';
			$wrap_end = '</ul><div id="admin_menu_tools" ></div>';
		}

		$content = ob_get_clean();


		// json response
		if( isset($_REQUEST['gpreq']) && ($_REQUEST['gpreq'] == 'json') ){

			if( isset($_REQUEST['menus']) ){
				$this->GetMenus();
			}

			$page->ajaxReplace[] = array('inner',$replace_id,$content);
			$page->ajaxReplace[] = array('gp_menu_refresh','','');
			return;
		}


		// search form
		echo '<form action="'.common::GetUrl('Admin_Menu').'" method="post" id="page_search">';
		$_REQUEST += array('q'=>'');
		echo '<input type="text" name="q" value="'.htmlspecialchars($_REQUEST['q']).'" class="gptext" />';
		echo '<input type="submit" name="cmd" value="'.$langmessage['search pages'].'" />';
		echo '<input type="hidden" name="menu" value="search" />';
		echo '</form>';


		$menus = $this->GetAvailMenus('menu');
		$lists = $this->GetAvailMenus('display');


		//heading
		echo '<form action="'.common::GetUrl('Admin_Menu').'" method="post" id="gp_menu_select_form">';
		echo '<input type="hidden" name="curr_menu" id="gp_curr_menu" value="'.$this->curr_menu_id.'" />';

		echo '<h2>';
		echo $langmessage['file_manager'].' »  ';
		echo '<select id="gp_menu_select" name="gp_menu_select">';

		echo '<optgroup label="'.$langmessage['Menus'].'">';
			foreach($menus as $menu_id => $menu_label){
				if( $menu_id == $this->curr_menu_id ){
					echo '<option value="'.$menu_id.'" selected="selected">';
				}else{
					echo '<option value="'.$menu_id.'">';
				}
				echo $menu_label.'</option>';
			}
		echo '</optgroup>';
		echo '<optgroup label="'.$langmessage['Lists'].'">';
			foreach($lists as $menu_id => $menu_label){
				if( $menu_id == $this->curr_menu_id ){
					echo '<option value="'.$menu_id.'" selected="selected">';
				}else{
					echo '<option value="'.$menu_id.'">';
				}
				echo $menu_label.'</option>';
			}
		echo '</optgroup>';
		echo '</select>';
		echo '</h2>';

		echo '</form>';


		echo '<div id="admin_menu_div">';

		echo $wrap_start;
		echo $content;
		echo $wrap_end;

		echo '</div>';


		echo '<div class="admin_footnote">';

		echo '<div>';
		echo '<b>'.$langmessage['Menus'].'</b>';
		foreach($menus as $menu_id => $menu_label){
			if( $menu_id == $this->curr_menu_id ){
				echo '<span>'.$menu_label.'</span>';
			}else{
				echo '<span>'.common::Link('Admin_Menu',$menu_label,'menu='.$menu_id,' name="cnreq"').'</span>';
			}

		}
		echo '<span>'.common::Link('Admin_Menu','+ '.$langmessage['Add New Menu'],'cmd=newmenu',' name="admin_box"').'</span>';
		echo '</div>';

		echo '<div>';
		echo '<b>'.$langmessage['Lists'].'</b>';
		foreach($lists as $menu_id => $menu_label){
			if( $menu_id == $this->curr_menu_id ){
			}else{
			}
			echo '<span>'.common::Link('Admin_Menu',$menu_label,'menu='.$menu_id,' name="creq"').'</span>';
		}
		echo '</div>';


		//options for alternate menu
		if( $this->is_alt_menu ){
			echo '<div>';
			$label = $menus[$this->curr_menu_id];
			echo '<b>'.$label.'</b>';
			echo '<span>'.common::Link('Admin_Menu',$langmessage['rename'],'cmd=rename_menu_prompt&id='.$this->curr_menu_id,' name="admin_box"').'</span>';
			$title_attr = sprintf($langmessage['generic_delete_confirm'],'&quot;'.$label.'&quot;');
			echo '<span>'.common::Link('Admin_Menu',$langmessage['delete'],'cmd=rm_menu&id='.$this->curr_menu_id,' name="creq" class="gpconfirm" title="'.$title_attr.'"').'</span>';

			echo '</div>';
		}


		echo '</div>';

		echo '<div class="gpclear"></div>';


	}

	function GetAvailMenus($get_type='menu'){

		$result = array();
		foreach($this->avail_menus as $menu_id => $menu_label){

			$menu_type = 'menu';
			if( isset($this->list_displays[$menu_id]) ){
				$menu_type = 'display';
			}

			if( $menu_type == $get_type ){
				$result[$menu_id] = $menu_label;
			}
		}
		return $result;
	}


	//we do the json here because we're replacing more than just the content
	function GetMenus(){
		global $page,$GP_MENU_LINKS,$GP_MENU_CLASS;

		foreach($_REQUEST['menus'] as $id => $menu){

			$info = gpOutput::GetgpOutInfo($menu);

			if( !isset($info['method']) ){
				continue;
			}

			$array = array();
			$array[0] = 'replacemenu';
			$array[1] = '#'.$id;

			if( !empty($_REQUEST['menuh'][$id]) ){
				$GP_MENU_LINKS = rawurldecode($_REQUEST['menuh'][$id]);
			}
			if( !empty($_REQUEST['menuc'][$id]) ){
				$GP_MENU_CLASS = rawurldecode($_REQUEST['menuc'][$id]);
			}

			ob_start();
			call_user_func($info['method'],$info['arg'],$info);
			$array[2] = ob_get_clean();

			$page->ajaxReplace[] = $array;

		}
	}



	function OutputMenu(){
		global $langmessage, $gp_titles;
		$menu_adjustments_made = false;

		if( $this->curr_menu_array === false ){
			message($langmessage['OOPS']);
			return;
		}


		//get array of titles and levels
		$menu_keys = array();
		$menu_values = array();
		foreach($this->curr_menu_array as $key => $info){
			if( !isset($info['level']) ){
				break;
			}

			//remove deleted titles
			if( !isset($gp_titles[$key]) && !isset($info['url']) ){
				unset($this->curr_menu_array[$key]);
				$menu_adjustments_made = true;
				continue;
			}


			$menu_keys[] = $key;
			$menu_values[] = $info;
		}

		//if the menu is empty (because all the files in it were deleted elsewhere), recreate it with the home page
		if( count($menu_values) == 0 ){
			$this->curr_menu_array = $this->AltMenu_New();
			$menu_keys[] = key($this->curr_menu_array);
			$menu_values[] = current($this->curr_menu_array);
			$menu_adjustments_made = true;
		}


		$prev_layout = false;
		$curr_key = 0;

		$curr_level = $menu_values[$curr_key]['level'];
		$prev_level = 0;


		//for sites that don't start with level 0
		if( $curr_level > $prev_level ){
			$piece = '<li><div>&nbsp;</div><ul>';
			while( $curr_level > $prev_level ){
				echo $piece;
				$prev_level++;
			}
		}



		do{

			echo "\n";

			$class = '';
			$menu_value = $menu_values[$curr_key];
			$menu_key = $menu_keys[$curr_key];
			$curr_level = $menu_value['level'];


			$next_level = 0;
			if( isset($menu_values[$curr_key+1]) ){
				$next_level = $menu_values[$curr_key+1]['level'];
			}

			if( $next_level > $curr_level ){
				$class = 'haschildren';
			}
			if( isset($this->hidden_levels[$menu_key]) ){
				$class .= ' hidechildren';
			}
			if( $curr_level >= $this->max_level_index){
				$class .= ' no-nest';
			}

			echo '<li class="'.$class.'">';

			if( $curr_level == 0 ){
				$prev_layout = false;
			}

			if( !$this->ShowLevel($menu_key,$menu_value,$prev_layout) ){
				$menu_adjustments_made = true;
			}

			if( !empty($gp_titles[$menu_key]['gpLayout']) ){
				$prev_layout = $gp_titles[$menu_key]['gpLayout'];
			}

			if( $next_level > $curr_level ){

				$piece = '<ul>';
				while( $next_level > $curr_level ){
					echo $piece;
					$curr_level++;
					$piece = '<li><div>&nbsp;</div><ul>';
				}

			}elseif( $next_level < $curr_level ){

				while( $next_level < $curr_level ){
					echo '</li></ul>';
					$curr_level--;
				}
				echo '</li>';
			}elseif( $next_level == $curr_level ){
				echo '</li>';
			}

			$prev_level = $curr_level;

		}while( ++$curr_key && ($curr_key < count($menu_keys) ) );

		if( $menu_adjustments_made ){
			$this->SaveMenu(false);
		}
	}

	function ShowLevel($menu_key,$menu_value,$prev_layout){
		global $gp_titles, $gpLayouts;
		$level_shown_accurately = true;

		$layout = admin_menu_tools::CurrentLayout($menu_key);
		$layout_info = $gpLayouts[$layout];

		echo '<div id="gp_menu_key_'.$menu_key.'">';

		$style = '';
		$class = 'expand_img';
		if( !empty($gp_titles[$menu_key]['gpLayout']) ){
			$style = 'style="background-color:'.$layout_info['color'].';"';
			$class .= ' haslayout';
		}elseif( $prev_layout ){
			//doesn't work correctly for alternate layouts
			//$temp = $gpLayouts[$layout];
			//$style = 'style="background-color:'.$temp['color'].';"';
			//$class .= ' haslayout';
		}


		$link = '<a href="#" class="'.$class.'" name="expand_img" '.$style.'></a>';

		if( isset($gp_titles[$menu_key]) ){
			echo $link;
			$this->ShowLevel_Title($menu_key,$menu_value,$layout_info);
		}elseif( isset($menu_value['url']) ){
			echo $link;
			$this->ShowLevel_External($menu_key,$menu_value);
		}
		echo '</div>';

		return $level_shown_accurately;
	}


	function ShowLevel_External($menu_key,$menu_value){
		global $langmessage;


		if( empty($menu_value['label']) ){
			$menu_value['label'] = $menu_value['url'];
		}
		if( empty($menu_value['title_attr']) ){
			$menu_value['title_attr'] = $menu_value['label'];
		}

		echo '<a href="#" class="label" name="menu_info" rel="'.str_replace('&','&amp;',$menu_key).'">';
		echo $menu_value['label'];
		echo '</a>';

		echo '<p>';


		echo '<b>'.$langmessage['Target URL'].'</b>';
		echo '<span>';
		$img = '<img class="icon_page" src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" />';
		echo '<a href="'.$menu_value['url'].'" target="_blank">';
		$show = $menu_value['url'];
		if( strlen($show) > 30 ){
			$show = substr($show,0,30).'...';
		}
		echo $show;
		echo '</a>';
		echo '</span>';


		echo '<b>'.$langmessage['options'].'</b>';
		echo '<span>';

		$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" height="16" width="16" class="page_edit_icon"/>';
		echo $this->Link('Admin_Menu',$img.$langmessage['edit'],'cmd=edit_external&key='.urlencode($menu_key),' title="'.$langmessage['edit'].'" name="admin_box"');


		$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" height="16" width="16" class="cut_list_icon"/>';
		echo $this->Link('Admin_Menu',$img.$langmessage['rm_from_menu'],'cmd=hide&key='.urlencode($menu_key),' title="'.$langmessage['rm_from_menu'].'" name="menupost" class="gpconfirm"');

		echo '</span>';

		$this->InsertLinks($menu_key,$menu_value['level']);

		echo '</p>';

	}


	function ShowLevel_Title($menu_key,$menu_value,$layout_info){
		global $langmessage, $gp_titles;

		$title = common::IndexToTitle($menu_key);
		$label = common::GetLabel($title);
		$isSpecialLink = common::SpecialOrAdmin($title);

		echo '<a href="#" class="label" name="menu_info" rel="'.str_replace('&','&amp;',$menu_key).'">';
		echo $label;
		echo '</a>';
		echo '<p>';


		/*
		 * page options
		 */
		echo '<b>'.$langmessage['page_options'].'</b>';

		echo '<span>';
		$img = '';

		$img = '<img class="icon_page" src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" />';
		echo common::Link($title,$img.$langmessage['view/edit_page']);

		$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" height="16" width="16" class="page_edit_icon"/>';
		echo $this->Link('Admin_Menu',$img.$langmessage['rename/details'],'cmd=renameform&title='.urlencode($title),' title="'.$langmessage['rename/details'].'" name="gpajax" ');

		$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" height="16" width="16" class="cut_list_icon"/>';
		echo $this->Link('Admin_Menu',$img.$langmessage['rm_from_menu'],'cmd=hide&key='.urlencode($menu_key),' title="'.$langmessage['rm_from_menu'].'" name="menupost" class="gpconfirm"'); // gpajax

		if( !$isSpecialLink ){
			$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" height="16" width="16" class="bin_icon"/>';
			echo $this->Link('Admin_Menu',$img.$langmessage['delete'],'cmd=trash&title='.urlencode($title),' title="'.$langmessage['delete_page'].'" name="menupost" class="gpconfirm" ');
		}
		echo '</span>';



		/*
		 * layout
		 */

		if( $this->is_main_menu ){
			echo '<b>'.$langmessage['layout'].'</b>';
			echo '<span>';

			if( !empty($gp_titles[$menu_key]['gpLayout']) ){

				$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" style="background-color:'.$layout_info['color'].';" height="16" width="16" class="layout_icon"  alt=""/>';
				echo $this->Link('Admin_Menu',$img.$layout_info['label'],'cmd=layout&title='.urlencode($title),' title="'.$langmessage['layout'].'" name="admin_box"');

				$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" height="16" width="16" class="undo_icon"  alt=""/>';
				echo $this->Link('Admin_Menu',$img.$langmessage['restore'],'cmd=restorelayout&title='.urlencode($title),' title="'.$langmessage['restore'].'" name="gpajax" ','restore');

			}else{
				$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" style="background-color:'.$layout_info['color'].';" height="16" width="16" class="layout_icon"  alt=""/>';
				echo $this->Link('Admin_Menu',$img.$layout_info['label'],'cmd=layout&title='.urlencode($title),' title="'.$langmessage['layout'].'" name="admin_box"');
			}
			echo '</span>';
		}

		$this->InsertLinks($menu_key,$menu_value['level']);

		echo '</p>';

	}

	/*
	 * insert
	 */
	function InsertLinks($menu_key,$level){
		global $langmessage;

		echo '<b>'.$langmessage['insert_into_menu'].'</b>';
		echo '<span>';

		$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" height="16" width="16" class="insert_before_icon"/>';
		$query = 'cmd=insert_before&insert_where='.urlencode($menu_key);
		echo $this->Link('Admin_Menu',$img.$langmessage['insert_before'],$query,' title="'.$langmessage['insert_before'].'" name="admin_box" ');


		$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" height="16" width="16" class="insert_after_icon"/>';
		$query = 'cmd=insert_after&insert_where='.urlencode($menu_key);
		echo $this->Link('Admin_Menu',$img.$langmessage['insert_after'],$query,' title="'.$langmessage['insert_after'].'" name="admin_box" ');


		if( $level < $this->max_level_index ){
			$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" height="16" width="16" class="insert_after_icon"/>';
			$query = 'cmd=insert_child&insert_where='.urlencode($menu_key);
			echo $this->Link('Admin_Menu',$img.$langmessage['insert_child'],$query,' title="'.$langmessage['insert_child'].'" name="admin_box" ');
		}

		echo '</span>';

	}


	function id($title){
		return strtr(base64_encode($title), '+/=', '-_-');
	}


	function GetSearchList(){
		global $gp_index;


		$key =& $_REQUEST['q'];

		if( empty($key) ){
			return array();
		}

		$key = strtolower($key);
		$show_list = array();
		foreach($gp_index as $title => $index ){

			if( strpos(strtolower($title),$key) !== false ){
				$show_list[] = $title;
				continue;
			}

			$label = common::GetLabelIndex($index);
			if( strpos(strtolower($label),$key) !== false ){
				$show_list[] = $title;
				continue;
			}

		}
		return $show_list;
	}

	function SearchDisplay(){
		global $langmessage, $gpLayouts, $gp_index, $gp_menu;

		$Inherit_Info = admin_menu_tools::Inheritance_Info();

		switch($this->curr_menu_id){
			case 'search':
				$show_list = $this->GetSearchList();
			break;
			case 'all':
				$show_list = array_keys($gp_index);
			break;
			case 'hidden':
				$show_list = $this->GetAvailable();
				$show_list = array_values($show_list); //to reset the keys
			break;
			case 'nomenus':
				$show_list = $this->GetNoMenus();
				$show_list = array_values($show_list); //to reset the keys
			break;

		}

		$show_list = array_reverse($show_list); //show newest first
		$max = count($show_list);
		while( ($this->search_page * $this->search_max_per_page) > $max ){
			$this->search_page--;
		}
		$start = $this->search_page*$this->search_max_per_page;
		$stop = min( ($this->search_page+1)*$this->search_max_per_page, $max);


		ob_start();
		echo '<div class="gp_search_links">';
		echo '<span class="showing">';
		echo sprintf($langmessage['SHOWING'],($start+1),$stop,$max);
		echo '</span>';

		if( ($start !== 0) || ($stop < $max) ){
			for( $i = 0; ($i*$this->search_max_per_page) < $max; $i++ ){
				$class = '';
				if( $i == $this->search_page ){
					$class = ' class="current"';
				}
				echo $this->Link('Admin_Menu',($i+1),'page='.$i,'name="gpajax"'.$class);
			}
		}

		echo $this->Link('Admin_Menu',$langmessage['create_new_file'],'cmd=add_hidden',' title="'.$langmessage['create_new_file'].'" name="gpajax"');
		echo '</div>';
		$links = ob_get_clean();

		echo $links;

		echo '<table class="bordered">';
		echo '<thead>';
		echo '<tr><th>';
		echo $langmessage['file_name'];
		echo '</th><th>';
		echo $langmessage['Child Pages'];
		echo '</th>';
		echo '</tr>';
		echo '</thead>';


		echo '<tbody>';

		if( count($show_list) > 0 ){
			for( $i = $start; $i < $stop; $i++ ){
				$title = $show_list[$i];
				$title_index = $gp_index[$title];

				echo '<tr><td>';
				echo common::Link($title,common::GetLabel($title));

				echo '<div>';

				$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" height="16" width="16" class="page_edit_icon"/>';
				echo $this->Link('Admin_Menu',$img.$langmessage['rename/details'],'cmd=renameform&title='.urlencode($title),' title="'.$langmessage['rename/details'].'" name="gpajax" ');


				$layout = admin_menu_tools::CurrentLayout($title_index);
				$layout_info = $gpLayouts[$layout];

				$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" style="background-color:'.$layout_info['color'].';" height="16" width="16" class="layout_icon"  alt=""/>';
				echo $this->Link('Admin_Menu',$img.$layout_info['label'],'cmd=layout&title='.urlencode($title),' title="'.$langmessage['layout'].'" name="admin_box"');

				if( !common::SpecialOrAdmin($title) ){
					$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" height="16" width="16" class="bin_icon"/>';
					echo $this->Link('Admin_Menu',$img.$langmessage['delete'],'cmd=trash&title='.urlencode($title),' title="'.$langmessage['delete_page'].'" name="menupost" class="gpconfirm" ');
				}


				echo '</div>';

				echo '</td><td>';

				if( isset($Inherit_Info[$title_index]) && isset($Inherit_Info[$title_index]['children']) ){
					echo $Inherit_Info[$title_index]['children'];
				}elseif( isset($gp_menu[$title_index]) ){
					echo '0';
				}else{
					echo $langmessage['Not In Main Menu'];
				}

				echo '</td></tr>';
			}
		}
		echo '</tbody>';
		echo '</table>';

		if( count($show_list) == 0 ){
			echo '<p>';
			echo $langmessage['Empty'];
			echo '</p>';
		}

		echo '<br/>';
		echo $links;


	}


	/**
	 * Get an array of titles that is not represented in any of the menus
	 *
	 */
	function GetNoMenus(){
		global $gp_index;


		//first get all titles in a menu
		$menus = $this->GetAvailMenus('menu');
		$all_keys = array();
		foreach($menus as $menu_id => $label){
			$menu_array = gpOutput::GetMenuArray($menu_id);
			$keys = array_keys($menu_array);
			$all_keys = array_merge($all_keys,$keys);
		}
		$all_keys = array_unique($all_keys);

		//then check $gp_index agains $all_keys
		foreach( $gp_index as $title => $index ){
			if( in_array($index, $all_keys) ){
				continue;
			}
			$avail[] = $title;
		}
		return $avail;
	}

	function GetAvailable(){
		global $gp_index, $gp_menu;

		foreach( $gp_index as $title => $index ){
			if( !isset($gp_menu[$index]) ){
				$avail[] = $title;
			}
		}
		return $avail;
	}

	function GetAvail_Current(){
		global $gp_index;

		foreach( $gp_index as $title => $index ){
			if( !isset($this->curr_menu_array[$index]) ){
				$avail[] = $title;
			}
		}
		return $avail;
	}


	/*
	 * SaveDrag
	 *
	 */
	function SaveDrag(){
		global $langmessage;

		$this->CacheSettings();
		if( $this->curr_menu_array === false ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}

		$key = $_POST['drag_key'];
		if( !isset($this->curr_menu_array[$key]) ){
			message($langmessage['OOPS'].'(2)');
			return false;
		}


		$moved = $this->RmMoved($key);
		if( !$moved ){
			message($langmessage['OOPS'].'(3)');
			return false;
		}


		// if prev (sibling) set
		$inserted = true;
		if( !empty($_POST['prev']) ){

			$inserted = $this->MenuInsert_After( $moved, $_POST['prev']);

		// if parent is set
		}elseif( !empty($_POST['parent']) ){

			$inserted = $this->MenuInsert_Child( $moved, $_POST['parent']);

		// if no siblings, no parent then it's the root
		}else{
			$inserted = $this->MenuInsert_Before( $moved, false);

		}

		if( !$inserted ){
			$this->RestoreSettings();
			message($langmessage['OOPS'].'(4)');
			return;
		}

		if( !$this->SaveMenu(false) ){
			$this->RestoreSettings();
			common::AjaxWarning();
			return false;
		}

	}


	function GetTitle($exists = true){
		global $langmessage;

		//not using request so that it's compat with creq
		if( isset($_POST['title']) ){
			$title = $_POST['title'];
		}elseif( isset($_GET['title']) ){
			$title = $_GET['title'];
		}else{
			message($langmessage['OOPS'].'(0)');
			return false;
		}

		$title = $this->CheckTitle($title,$exists);
		if( !$title ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}
		return $title;
	}

	function CheckTitle($title,$exists = true){
		global $gp_index;

		$title = gpFiles::CleanTitle($title);

		if( $exists && !isset($gp_index[$title]) ){
			return false;
		}
		return $title;

	}

	/*
	 * Get portion of menu that was moved
	 */
	function RmMoved($key){
		if( !isset($this->curr_menu_array[$key]) ){
			return false;
		}

		$old_level = false;
		$moved = array();

		foreach($this->curr_menu_array as $menu_key => $info){

			if( !isset($info['level']) ){
				break;
			}
			$level = $info['level'];

			if( $old_level === false ){

				if( $menu_key != $key ){
					continue;
				}

				$old_level = $level;
				$moved[$menu_key] = $info;
				unset($this->curr_menu_array[$menu_key]);
				continue;
			}

			if( $level <= $old_level ){
				break;
			}

			$moved[$menu_key] = $info;
			unset($this->curr_menu_array[$menu_key]);
		}
		return $moved;
	}



	/**
	 * Move To Trash
	 * Hide special pages
	 *
	 */
	function MoveToTrash(){
		global $gp_titles, $gp_index, $langmessage, $gp_menu;


		includeFile('admin/admin_trash.php');
		admin_trash::PrepFolder();
		$this->CacheSettings();

		$title = $this->GetTitle();
		if( !$title ){
			return false;
		}

		$id = $gp_index[$title];

		if( isset($gp_menu[$id]) ){
			if( count($gp_menu) == 1 ){
				message($langmessage['OOPS'].' (The main menu cannot be empty)');
				return;
			}

			if( !$this->RmFromMenu($id,false) ){
				message($langmessage['OOPS']);
				$this->RestoreSettings();
				return false;
			}
		}

		if( !admin_trash::MoveToTrash_File($title) ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}

		$trash_data = array();
		$trash_data[$title] = $gp_titles[$id];

		unset($gp_titles[$id]);
		unset($gp_index[$title]);

		if( !admin_trash::ModTrashData($trash_data,null) ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}

		if( !admin_tools::SavePagesPHP() ){
			$this->RestoreSettings();
			return false;
		}

		admin_trash::AfterToTrash($trash_data);

		return true;
	}


	/*
	 *	Remove key from curr_menu_array
	 * 	Adjust children levels if necessary
	 */
	function RmFromMenu($search_key,$curr_menu=true){
		global $gp_menu;

		if( $curr_menu ){
			$keys = array_keys($this->curr_menu_array);
			$values = array_values($this->curr_menu_array);
		}else{
			$keys = array_keys($gp_menu);
			$values = array_values($gp_menu);
		}

		$insert_key = array_search($search_key,$keys);
		if( ($insert_key === null) || ($insert_key === false) ){
			return false;
		}

		$curr_info = $values[$insert_key];
		$curr_level = $curr_info['level'];

		unset($keys[$insert_key]);
		$keys = array_values($keys);

		unset($values[$insert_key]);
		$values = array_values($values);


		//adjust levels of children
		$prev_level = -1;
		if( isset($values[$insert_key-1]) ){
			$prev_level = $values[$insert_key-1]['level'];
		}
		$moved_one = true;
		do{
			$moved_one = false;
			if( isset($values[$insert_key]) ){
				$curr_level = $values[$insert_key]['level'];
				if( ($prev_level+1) < $curr_level ){
					$values[$insert_key]['level']--;
					$prev_level = $values[$insert_key]['level'];
					$moved_one = true;
					$insert_key++;
				}
			}
		}while($moved_one);

		//shouldn't happen
		if( count($keys) == 0 ){
			return false;
		}

		//rebuild
		if( $curr_menu ){
			$this->curr_menu_array = array_combine($keys, $values);
		}else{
			$gp_menu = array_combine($keys, $values);
		}

		return true;
	}



	/*
	 * Rename
	 *
	 */
	function RenameForm(){
		global $langmessage, $gp_index;

		includeFile('tool/Page_Rename.php');

		//prepare variables
		$title =& $_REQUEST['title'];

		if( !isset($gp_index[$title]) ){
			echo $langmessage['OOPS'];
			return;
		}

		$action = $this->GetUrl('Admin_Menu');
		rename_details::RenameForm($title,$action);
	}

	function RenameFile(){
		global $langmessage, $gp_index;

		includeFile('tool/Page_Rename.php');


		//prepare variables
		$title =& $_REQUEST['title'];
		if( !isset($gp_index[$title]) ){
			message($langmessage['OOPS'].' (R0)');
			return false;
		}

		rename_details::RenameFile($title);
	}

	function Hide(){
		global $langmessage;

		if( $this->curr_menu_array === false ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}
		if( count($this->curr_menu_array) == 1 ){
			message($langmessage['OOPS'].' (The menu cannot be empty)');
			return false;
		}

		$this->CacheSettings();
		$key = $_POST['key']; //using gplinks.menupost()
		if( !isset($this->curr_menu_array[$key]) ){
			message($langmessage['OOPS'].'(3)');
			return false;
		}

		if( !$this->RmFromMenu($key) ){
			message($langmessage['OOPS'].'(4)');
			$this->RestoreSettings();
			return false;
		}

		if( $this->SaveMenu(false) ){
			return true;
		}

		message($langmessage['OOPS'].'(5)');
		$this->RestoreSettings();
		return false;
	}

	function AddHidden(){
		global $langmessage,$page;

		ob_start();

		$title = '';
		if( isset($_REQUEST['title']) ){
			$title = $_REQUEST['title'];
		}
		echo '<div class="inline_box">';

		echo '<form action="'.$this->GetUrl('Admin_Menu').'" method="post">';
		echo '<table class="bordered" style="width:100%">';

		echo '<tr>';
			echo '<th>'.$langmessage['new_file'].'</th>';
			echo '<th>&nbsp;</th>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>'.$langmessage['file_name'].'</td>';
			echo '<td>';
			echo '<input type="text" name="title" maxlength="60" value="'.htmlspecialchars($title).'" />';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo $langmessage['Content Type'];
			echo '</td>';
			echo '<td>';

			includeFile('tool/editing_page.php');
			editing_page::SectionTypes();

			echo '</td>';
			echo '</tr>';

		echo '</table>';

			echo '<p>';

			if( isset($_GET['redir']) ){
				echo '<input type="hidden" name="cmd" value="new_redir" />';
			}else{
				echo '<input type="hidden" name="cmd" value="new_hidden" />';
			}
			echo '<input type="submit" name="aaa" value="'.$langmessage['create_new_file'].'" class="gppost"/> '; //class="menupost" is not needed because we're adding hidden files
			echo '<input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close" /> ';
			echo '</p>';

		echo '</form>';
		echo '</div>';

		$content = ob_get_clean();
		$page->ajaxReplace[] = array('admin_box_data','',$content);
		$page->ajaxReplace[] = array('eval','','$("input[name=title]").focus();');


	}


	function InsertDialog($cmd){
		global $langmessage;

		includeFile('admin/admin_trash.php');

		echo '<div class="inline_box">';

			echo '<div class="layout_links">';
				echo ' <a href="#gp_Insert_New" name="tabs" class="selected">'. $langmessage['new_file'] .'</a>';
				echo ' <a href="#gp_Insert_Hidden" name="tabs">'. $langmessage['Available Pages'] .'</a>';
				echo ' <a href="#gp_Insert_Deleted" name="tabs">'. $langmessage['restore_from_trash'] .'</a>';
				echo ' <a href="#gp_Insert_External" name="tabs">'. $langmessage['External Link'] .'</a>';
			echo '</div>';

			// Insert New
			echo '<div id="gp_Insert_New">';

				echo '<form action="'.$this->GetUrl('Admin_Menu').'" method="post">';
				echo '<table class="bordered" style="width:100%">';

				echo '<tr>';
					echo '<th>&nbsp;</th>';
					echo '<th>&nbsp;</th>';
					echo '</tr>';

				echo '<tr>';
					echo '<td>'.$langmessage['file_name'].'</td>';
					echo '<td>';
					echo '<input type="text" name="title" maxlength="60" value="" />';
					echo '</td>';
					echo '</tr>';

				echo '<tr>';
					echo '<td>';
					echo $langmessage['Content Type'];
					echo '</td>';
					echo '<td>';

					includeFile('tool/editing_page.php');
					editing_page::SectionTypes();

					echo '</td>';
					echo '</tr>';

				echo '</table>';

					echo '<p>';
					echo '<input type="hidden" name="insert_how" value="'.htmlspecialchars($cmd).'" />';
					echo '<input type="hidden" name="insert_where" value="'.htmlspecialchars($_GET['insert_where']).'" />';

					echo '<input type="hidden" name="cmd" value="new_file" />';
					echo '<input type="submit" name="aaa" value="'.$langmessage['create_new_file'].'" class="menupost"/> ';
					echo '<input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close" /> ';
					echo '</p>';

				echo '</form>';
			echo '</div>';

			// Insert Hidden
			echo '<div id="gp_Insert_Hidden" style="display:none">';
			if( $this->is_main_menu ){
				$avail = $this->GetAvailable();
			}else{
				$avail = $this->GetAvail_Current();
			}

			if( count($avail) == 0 ){
				echo '<p>';
				echo $langmessage['Empty'];
				echo '</p>';
			}else{

				echo '<form action="'.common::GetUrl('Admin_Menu').'" method="post">';

				echo '<table class="bordered" style="width:100%">';
				echo '<thead>';
				echo '<tr>';
				echo '<th>'.$langmessage['title'].'</th>';
				echo '<th class="right">'.$langmessage['insert_into_menu'].'</th>';
				echo '</tr>';
				echo '</thead>';
				echo '</table>';
				echo '<ul class="gpui-scrolllist ui-menu ui-widget ui-widget-content ui-corner-all">';

				//sort by label
				$sort_avail = array();
				foreach($avail as $title){
					$sort_avail[$title] = common::GetLabel($title);
				}
				natcasesort($sort_avail);

				foreach($sort_avail as $title => $label){
					echo '<li class="ui-menu-item">';
					echo '<label class="ui-corner-all">';
					echo '<input type="checkbox" name="titles[]" value="'.htmlspecialchars($title).'" />';

					echo $label;
					echo '<span class="slug">';
					echo '/'.$title;
					echo '</span>';
					echo '</label>';
					echo '</li>';
				}

				echo '</ul>';


				echo '<p>';
				echo '<input type="hidden" name="insert_how" value="'.htmlspecialchars($cmd).'" />';
				echo '<input type="hidden" name="insert_where" value="'.htmlspecialchars($_GET['insert_where']).'" />';
				echo '<input type="hidden" name="cmd" value="insert_from_hidden"  />';
				echo '<input type="submit" name="" value="'.$langmessage['insert_into_menu'].'" class="menupost" />';
				echo ' <input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close" /> ';
				echo '</p>';

				echo '</form>';
			}



			echo '</div>';

			// Insert Deleted / Restore from trash
			echo '<div id="gp_Insert_Deleted" style="display:none">';

			$trashtitles = admin_trash::TrashFiles();
			if( count($trashtitles) == 0 ){
				echo '<p>'.$langmessage['TRASH_IS_EMPTY'].'</p>';
			}else{

				echo '<form action="'.common::GetUrl('Admin_Menu').'" method="post">';
				echo '<table class="bordered" style="width:100%">';
				echo '<thead>';
				echo '<tr>';
				echo '<th>'.$langmessage['title'].'</th>';
				echo '<th class="right">'.$langmessage['restore'].'</th>';
				echo '</tr>';
				echo '</thead>';
				echo '</table>';


				echo '<ul class="gpui-scrolllist ui-menu ui-widget ui-widget-content ui-corner-all">';
				foreach($trashtitles as $title => $info){
					echo '<li class="ui-menu-item">';
					echo '<label class="ui-corner-all">';
					echo '<input type="checkbox" name="titles[]" value="'.htmlspecialchars($title).'" />';

					echo $info['label'];
					echo '<span class="slug">';
					echo '/'.$title;
					echo '</span>';

					echo '</label>';
					echo '</li>';
				}

				echo '</ul>';


				echo '<p>';
				echo '<input type="hidden" name="insert_how" value="'.htmlspecialchars($cmd).'" />';
				echo '<input type="hidden" name="insert_where" value="'.htmlspecialchars($_GET['insert_where']).'" />';
				echo '<input type="hidden" name="cmd" value="restore"  />';
				echo '<input type="submit" name="" value="'.$langmessage['restore'].'" class="menupost" />';
				echo ' <input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close" /> ';
				echo '</p>';

				echo '</form>';
			}
			echo '</div>';


			//Insert External
			echo '<div id="gp_Insert_External" style="display:none">';


				$args['insert_how'] = $cmd;
				$args['insert_where'] = $_GET['insert_where'];
				$this->ExternalForm('new_external',$langmessage['insert_into_menu'],$args);

			echo '</div>';


		echo '</div>';

	}

	function InsertFromHidden(){
		global $langmessage, $gp_index;

		if( $this->curr_menu_array === false ){
			message($langmessage['OOPS']);
			return false;
		}

		$this->CacheSettings();

		// get list of titles
		$titles = array();
		if( isset($_POST['titles']) ){
			foreach($_POST['titles'] as $title){
				$title = $this->CheckTitle($title,true);
				if( !$title ){
					continue;
				}
				$index = $gp_index[$title];
				$titles[$index]['level'] = 0;
			}
		}

		if( count($titles) == 0 ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}

		if( !$this->MenuInsert($titles,$_POST['insert_where'],$_POST['insert_how']) ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}

		if( !$this->SaveMenu(false) ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}

	}

	function RestoreFromTrash(){
		global $langmessage, $gp_index;


		if( $this->curr_menu_array === false ){
			message($langmessage['OOPS']);
			return false;
		}

		if( !isset($_POST['titles']) ){
			message($langmessage['OOPS'].' (Nothing Selected)');
			return false;
		}

		$this->CacheSettings();
		includeFile('admin/admin_trash.php');

		$titles_lower = array_change_key_case($gp_index,CASE_LOWER);
		$titles = array();
		$exists = array();

		foreach($_POST['titles'] as $title){

			$title = $this->CheckTitle($title,false);
			if( !$title ){
				continue;
			}

			$title_lower = strtolower($title);
			if( isset($titles_lower[$title_lower]) ){
				$exists[] = $title;
				continue;
			}

			$titles[$title] = array();
			$titles_lower[strtolower($title)] = true;

		}

		$menu = admin_trash::RestoreTitles($titles);

		if( count($exists) > 0 ){
			message($langmessage['TITLES_EXIST'].implode(', ',$exists));

			if( count($menu) == 0 ){
				return false; //prevent multiple messages
			}
		}

		if( count($menu) == 0 ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}


		if( !$this->MenuInsert($menu,$_POST['insert_where'],$_POST['insert_how']) ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}

		if( !$this->SaveMenu(true) ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}

		admin_trash::ModTrashData(null,$titles);
	}

	function NewHiddenFile_Redir(){
		global $page;

		$new_index = $this->NewHiddenFile();
		if( $new_index === false ){
			return;
		}

		$title = common::IndexToTitle($new_index);

		//redirect to title
		$url = common::AbsoluteUrl($title,'');
		$page->ajaxReplace[] = array('eval','','window.location="'.$url.'";');
	}


	function NewHiddenFile(){
		global $langmessage;

		$this->CacheSettings();

		$new_index = $this->CreateNew();
		if( $new_index === false ){
			return false;
		}


		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}
		message($langmessage['SAVED']);
		$this->search_page = 0; //take user back to first page where the new page will be displayed
		return $new_index;
	}

	function NewFile(){
		global $langmessage;
		$this->CacheSettings();


		if( $this->curr_menu_array === false ){
			message($langmessage['OOPS'].'(0)');
			return false;
		}

		$neighbor = $_POST['insert_where'];
		if( !isset($this->curr_menu_array[$neighbor]) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}


		$new_index = $this->CreateNew();
		if( $new_index === false ){
			return false;
		}

		$insert = array();
		$insert[$new_index] = array();

		if( !$this->MenuInsert($insert,$neighbor,$_POST['insert_how']) ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}

		if( !$this->SaveMenu(true) ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}
	}

	function CreateNew(){
		global $gp_index, $gp_titles, $langmessage;
		includeFile('tool/editing_page.php');

		$title = admin_tools::CheckPostedNewPage();
		if( $title === false ){
			return false;
		}

		$type = $_POST['content_type'];
		$content = editing_page::GetDefaultContent($type);

		if( $content === false ){
			return false;
		}

		if( $type == 'text' ){
			$content = '<h2>'.htmlspecialchars($_POST['title']).'</h2>'.$content;
		}

		if( !gpFiles::NewTitle($title,$content,$type) ){
			message($langmessage['OOPS']);
			return false;
		}

		//add to $gp_index & $gp_titles
		$index = common::NewFileIndex();
		$gp_index[$title] = $index;


		$new_titles = array();
		$new_titles[$index]['label'] = gpFiles::CleanLabel($_POST['title']);
		$new_titles[$index]['type'] = $type;
		$gp_titles += $new_titles;

		return $index;
	}


	function MenuInsert($titles,$neighbor,$insert_how){

		switch($insert_how){

			case 'insert_before':
			return $this->MenuInsert_Before($titles,$neighbor);

			case 'insert_after':
			return $this->MenuInsert_After($titles,$neighbor);

			case 'insert_child':
			return $this->MenuInsert_After($titles,$neighbor,1);
		}

		return false;
	}



	/*
	 * Insert titles into menu
	 */
	function MenuInsert_Before($titles,$sibling){

		$old_level = $this->GetRootLevel($titles);

		//root install
		if( $sibling === false ){
			$level_adjustment = 0 - $old_level;
			$titles = $this->AdjustMovedLevel($titles,$level_adjustment);
			$this->curr_menu_array = $titles + $this->curr_menu_array;
			return true;
		}


		//before sibling
		if( !isset($this->curr_menu_array[$sibling]) || !isset($this->curr_menu_array[$sibling]['level']) ){
			return false;
		}

		$sibling_level = $this->curr_menu_array[$sibling]['level'];
		$level_adjustment = $sibling_level - $old_level;
		$titles = $this->AdjustMovedLevel($titles,$level_adjustment);

		$new_menu = array();
		foreach($this->curr_menu_array as $menu_key => $menu_info ){

			if( $menu_key == $sibling ){
				foreach($titles as $titles_key => $titles_info){
					$new_menu[$titles_key] = $titles_info;
				}
			}
			$new_menu[$menu_key] = $menu_info;
		}
		$this->curr_menu_array = $new_menu;
		return true;
	}

	/*
	 * Insert $titles into $menu as siblings of $sibling
	 * Place
	 *
	 */
	function MenuInsert_After($titles,$sibling,$level_adjustment=0){

		if( !isset($this->curr_menu_array[$sibling]) || !isset($this->curr_menu_array[$sibling]['level']) ){
			return false;
		}

		$sibling_level = $this->curr_menu_array[$sibling]['level'];

		//level adjustment
		$old_level = $this->GetRootLevel($titles);
		$level_adjustment += $sibling_level - $old_level;
		$titles = $this->AdjustMovedLevel($titles,$level_adjustment);


		// rebuild menu
		//	insert $titles after sibling and it's children
		$new_menu = array();
		$found_sibling = false;
		foreach($this->curr_menu_array as $menu_key => $menu_info){

			$menu_level = 0;
			if( isset($menu_info['level']) ){
				$menu_level = $menu_info['level'];
			}

			if( $found_sibling && ($menu_level <= $sibling_level) ){
				foreach($titles as $titles_key => $titles_info){
					$new_menu[$titles_key] = $titles_info;
				}
				$found_sibling = false; //prevent multiple insertions
			}

			$new_menu[$menu_key] = $menu_info;

			if( $menu_key == $sibling ){
				$found_sibling = true;
			}
		}

		//if it's added to the end
		if( $found_sibling ){
			foreach($titles as $titles_key => $titles_info){
				$new_menu[$titles_key] = $titles_info;
			}
		}
		$this->curr_menu_array = $new_menu;

		return true;
	}

	/*
	 * Insert $titles into $menu as children of $parent
	 *
	 */
	function MenuInsert_Child($titles,$parent){

		if( !isset($this->curr_menu_array[$parent]) || !isset($this->curr_menu_array[$parent]['level']) ){
			return false;
		}

		$parent_level = $this->curr_menu_array[$parent]['level'];


		//level adjustment
		$old_level = $this->GetRootLevel($titles);
		$level_adjustment = $parent_level - $old_level + 1;
		$titles = $this->AdjustMovedLevel($titles,$level_adjustment);

		//rebuild menu
		//	insert $titles after parent
		$new_menu = array();
		foreach($this->curr_menu_array as $menu_title => $menu_info){
			$new_menu[$menu_title] = $menu_info;

			if( $menu_title == $parent ){
				foreach($titles as $titles_title => $titles_info){
					$new_menu[$titles_title] = $titles_info;
				}
			}
		}

		$this->curr_menu_array = $new_menu;
		return true;
	}

	function AdjustMovedLevel($titles,$level_adjustment){

		foreach($titles as $title => $info){
			$level = 0;
			if( isset($info['level']) ){
				$level = $info['level'];
			}
			$titles[$title]['level'] = min($this->max_level_index,$level + $level_adjustment);
		}
		return $titles;
	}

	function GetRootLevel($menu){
		reset($menu);
		$info = current($menu);
		if( isset($info['level']) ){
			return $info['level'];
		}
		return 0;
	}




	/*
	 * Alternate Menus
	 *
	 *
	 *
	 */

	function IsAltMenu($id){
		global $config;
		return isset($config['menus'][$id]);
	}

	function AltMenu_Rename(){
		global $langmessage,$config;

		$menu_id =& $_POST['id'];

		if( !$this->IsAltMenu($menu_id) ){
			message($langmessage['OOPS']);
			return;
		}

		$menu_name = $this->AltMenu_NewName();
		if( !$menu_name ){
			return;
		}

		$config['menus'][$menu_id] = $menu_name;
		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS']);
		}else{
			$this->avail_menus[$menu_id] = $menu_name;
		}


	}


	function RenameMenuPrompt(){
		global $langmessage;

		$menu_id =& $_GET['id'];

		if( !$this->IsAltMenu($menu_id) ){
			echo '<div class="inline_box">';
			echo $langmessage['OOPS'];
			echo '</div>';
			return;
		}

		$menu_name = $this->avail_menus[$menu_id];

		echo '<div class="inline_box">';
		echo '<form action="'.common::GetUrl('Admin_Menu').'" method="post">';
		echo '<input type="hidden" name="cmd" value="alt_menu_rename" />';
		echo '<input type="hidden" name="id" value="'.htmlspecialchars($menu_id).'" />';

		echo '<h3>';
		echo $langmessage['rename'];
		echo '</h3>';

		echo '<p>';
		echo $langmessage['label'];
		echo ' &nbsp; ';
		echo '<input type="text" name="menu_name" class="gptext" value="'.htmlspecialchars($menu_name).'" />';
		echo '</p>';


		echo '<p>';
		echo '<input type="submit" name="aa" value="'.htmlspecialchars($langmessage['continue']).'" />';
		echo ' <input type="submit" value="'.htmlspecialchars($langmessage['cancel']).'" class="admin_box_close" /> ';
		echo '</p>';

		echo '</form>';
		echo '</div>';

	}

	function NewMenu(){
		global $langmessage;

		echo '<div class="inline_box">';
		echo '<form action="'.common::GetUrl('Admin_Menu').'" method="post">';
		echo '<input type="hidden" name="cmd" value="altmenu_create" />';

		echo '<h3>';
		echo $langmessage['Add New Menu'];
		echo '</h3>';

		echo '<p>';
		echo $langmessage['label'];
		echo ' &nbsp; ';
		echo '<input type="text" name="menu_name" class="gptext" />';
		echo '</p>';

		echo '<p>';

		echo '<input type="submit" name="aa" value="'.htmlspecialchars($langmessage['continue']).'" />';
		echo ' <input type="submit" value="'.htmlspecialchars($langmessage['cancel']).'" class="admin_box_close" /> ';
		echo '</p>';

		echo '</form>';
		echo '</div>';

	}


	function AltMenu_Create(){
		global $config, $langmessage, $dataDir;

		$menu_name = $this->AltMenu_NewName();
		if( !$menu_name ){
			return;
		}

		$new_menu = $this->AltMenu_New();

		//get next index
		$index = 0;
		if( isset($config['menus']) && is_array($config['menus']) ){
			foreach($config['menus'] as $id => $label){
				$id = substr($id,1);
				$index = max($index,$id);
			}
		}
		$index++;
		$id = 'm'.$index;

		$menu_file = $dataDir.'/data/_menus/'.$id.'.php';
		if( !gpFiles::SaveArray($menu_file,'menu',$new_menu) ){
			message($langmessage['OOPS']);
			return false;
		}

		$config['menus'][$id] = $menu_name;
		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS']);
		}else{
			$this->avail_menus[$id] = $menu_name;
			$this->curr_menu_id = $id;
		}
	}

	//create a menu with one file
	function AltMenu_New(){
		global $gp_menu;
		reset($gp_menu);
		$first_index = key($gp_menu);
		$new_menu[$first_index] = array('level'=>0);
		return $new_menu;
	}

	function AltMenu_NewName(){
		global $langmessage;

		$menu_name = gpFiles::CleanTitle($_POST['menu_name'],' ');
		if( empty($menu_name) ){
			message($langmessage['OOPS'].' (Empty Name)');
			return false;
		}

		if( array_search($menu_name,$this->avail_menus) !== false ){
			message($langmessage['OOPS'].' (Name Exists)');
			return false;
		}

		return $menu_name;
	}



	function AltMenu_Remove(){
		global $langmessage,$config,$dataDir;

		$menu_id =& $_POST['id'];
		if( !$this->IsAltMenu($menu_id) ){
			message($langmessage['OOPS']);
			return;
		}

		$menu_file = $dataDir.'/data/_menus/'.$menu_id.'.php';

		unset($config['menus'][$menu_id]);
		unset($this->avail_menus[$menu_id]);
		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS']);
		}

		message($langmessage['SAVED']);

		//delete menu file
		$menu_file = $dataDir.'/data/_menus/'.$menu_id.'.php';
		if( file_exists($menu_file) ){
			unlink($menu_file);
		}


	}






	/*
	 * External Links
	 *
	 *
	 */

	function ExternalForm($cmd,$submit,$args){
		global $langmessage;

		//these aren't all required for each usage of ExternalForm()
		$args += array(
					'url'=>'http://',
					'label'=>'',
					'title_attr'=>'',
					'insert_how'=>'',
					'insert_where'=>'',
					'key'=>''
					);


		echo '<form action="'.$this->GetUrl('Admin_Menu').'" method="post">';
		echo '<input type="hidden" name="insert_how" value="'.htmlspecialchars($args['insert_how']).'" />';
		echo '<input type="hidden" name="insert_where" value="'.htmlspecialchars($args['insert_where']).'" />';
		echo '<input type="hidden" name="key" value="'.htmlspecialchars($args['key']).'" />';

		echo '<table class="bordered" style="width:100%">';

		echo '<tr>';
			echo '<th>&nbsp;</th>';
			echo '<th>&nbsp;</th>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>'.$langmessage['Target URL'].'</td>';
			echo '<td>';
			echo '<input type="text" name="url" value="'.htmlspecialchars($args['url']).'" />';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>'.$langmessage['label'].'</td>';
			echo '<td>';
			echo '<input type="text" name="label" value="'.htmlspecialchars($args['label']).'" />';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>'.$langmessage['title attribute'].'</td>';
			echo '<td>';
			echo '<input type="text" name="title_attr" value="'.htmlspecialchars($args['title_attr']).'" />';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>'.$langmessage['New_Window'].'</td>';
			echo '<td>';
			if( isset($args['new_win']) ){
				echo '<input type="checkbox" name="new_win" value="new_win" checked="checked" />';
			}else{
				echo '<input type="checkbox" name="new_win" value="new_win" />';
			}
			echo '</td>';
			echo '</tr>';


		echo '</table>';

		echo '<p>';

		echo '<input type="hidden" name="cmd" value="'.htmlspecialchars($cmd).'" />';
		echo '<input type="submit" name="" value="'.$submit.'" class="menupost" /> ';
		echo '<input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close" /> ';
		echo '</p>';

		echo '</form>';
	}


	function EditExternal(){
		global $langmessage;

		$key =& $_GET['key'];
		if( !isset($this->curr_menu_array[$key]) ){
			message($langmessage['OOPS']);
			return false;
		}

		$info = $this->curr_menu_array[$key];
		$info['key'] = $key;

		echo '<div class="inline_box">';

		echo '<h3>'.$langmessage['External Link'].'</h3>';

		$this->ExternalForm('save_external',$langmessage['save'],$info);

		echo '</div>';
	}


	function SaveExternal(){
		global $langmessage;

		$key =& $_POST['key'];
		if( !isset($this->curr_menu_array[$key]) ){
			message($langmessage['OOPS']);
			return false;
		}
		$level = $this->curr_menu_array[$key]['level'];

		$array = $this->ExternalPost();
		if( !$array ){
			message($langmessage['OOPS']);
			return;
		}

		$this->CacheSettings();

		$array['level'] = $level;
		$this->curr_menu_array[$key] = $array;

		if( !$this->SaveMenu(false) ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}

	}


	function NewExternal(){
		global $langmessage;

		$this->CacheSettings();
		$array = $this->ExternalPost();

		if( !$array ){
			message($langmessage['OOPS']);
			return;
		}

		$key = $this->NewExternalKey();
		$insert[$key] = $array;

		if( !$this->MenuInsert($insert,$_POST['insert_where'],$_POST['insert_how']) ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}

		if( !$this->SaveMenu(false) ){
			message($langmessage['OOPS']);
			$this->RestoreSettings();
			return false;
		}

	}

	function ExternalPost(){

		$array = array();
		if( empty($_POST['url']) || $_POST['url'] == 'http://' ){
			return false;
		}
		$array['url'] = $_POST['url'];

		if( !empty($_POST['label']) ){
			$array['label'] = $_POST['label'];
		}
		if( !empty($_POST['title_attr']) ){
			$array['title_attr'] = $_POST['title_attr'];
		}
		if( isset($_POST['new_win']) && $_POST['new_win'] == 'new_win' ){
			$array['new_win'] = true;
		}
		return $array;
	}

	function NewExternalKey(){

		$num_index = 0;
		do{
			$new_key = '_'.base_convert($num_index,10,36);
			$num_index++;
		}while( isset($this->curr_menu_array[$new_key]) );

		return $new_key;
	}



}
