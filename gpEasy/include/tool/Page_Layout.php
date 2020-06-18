<?php
defined('is_running') or die('Not an entry point...');

includeFile('admin/admin_menu_tools.php');

class page_layout{

	var $from_page = false;
	var $show_popup = false;
	var $title;

	function page_layout($cmd,$title,$url,$query_string=''){

		$this->title = $title;

		//if the request is made from the page, we want to remember that and send an appropriate response
		if( isset($_REQUEST['from']) && $_REQUEST['from'] == 'page' ){
			$query_string .= '&from=page';
			$this->from_page = true;
		}
		$query_string .= '&';
		$query_string = ltrim($query_string,'&');

		switch($cmd){
			case 'layout':
				page_layout::SelectLayout($title,$url,$query_string);
				$this->show_popup = true;
			return;
			case 'uselayout':
				page_layout::SetLayout($title);
			return;
			case 'restorelayout':
				page_layout::RestoreLayout($title);
			return;
		}
	}

	function Result(){
		global $page;


		if( $this->from_page ){
			if( !$this->show_popup ){
				$url = common::AbsoluteUrl($this->title,'');
				$page->ajaxReplace[] = array('eval','','window.location="'.$url.'";');
			}
			return true;
		}

		return $this->show_popup;
	}


	function RestoreLayout($title){
		global $gp_titles,$gp_index,$langmessage;

		if( !common::verify_nonce('restore') ){
			message($langmessage['OOPS']);
			return;
		}

		if( !isset($gp_index[$title]) ){
			message($langmessage['OOPS']);
			return;
		}
		$id = $gp_index[$title];


		unset($gp_titles[$id]['gpLayout']);
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			return false;
		}

		//reset the layout array
		message($langmessage['SAVED']);
	}


	function SetLayout($title){
		global $gp_index, $gp_titles, $langmessage, $gpLayouts;

		if( !isset($gp_index[$title]) ){
			message($langmessage['OOPS']);
			return;
		}
		$id = $gp_index[$title];

		$layout = $_GET['layout'];
		if( !isset($gpLayouts[$layout]) ){
			message($langmessage['OOPS']);
			return;
		}

		if( !common::verify_nonce('use_'.$layout) ){
			message($langmessage['OOPS']);
			return;
		}


		//unset, then reset if needed
		unset($gp_titles[$id]['gpLayout']);
		$currentLayout = display::OrConfig($id,'gpLayout');
		if( $currentLayout != $layout ){
			$gp_titles[$id]['gpLayout'] = $layout;
		}

		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].'(3)');
			return false;
		}

		message($langmessage['SAVED']);
	}



	function SelectLayout($title,$url,$query_string){
		global $gp_titles, $gpLayouts, $langmessage, $config, $gp_index;

		if( !isset($gp_index[$title]) ){
			echo $langmessage['OOPS'];
			return;
		}

		$index = $gp_index[$title];

		$Inherit_Info = admin_menu_tools::Inheritance_Info();
		$curr_layout = admin_menu_tools::CurrentLayout($index);
		$curr_info = $gpLayouts[$curr_layout];


		echo '<div class="inline_box">';

		echo '<p>';
		echo $langmessage['current_layout'].': ';
		echo '<span class="layout_color_id" style="background-color:'.$curr_info['color'].';" title="'.$curr_info['color'].'"></span> ';
		echo str_replace('_',' ',$curr_info['label']);
		echo '</p>';

		if( !empty($gp_titles[$index]['gpLayout']) ){
			echo '<p>';

			if( isset($Inherit_Info[$index]['parent_layout']) ){
				$parent_layout = $Inherit_Info[$index]['parent_layout'];
			}else{
				$parent_layout = $config['gpLayout'];
			}
			$parent_info = $gpLayouts[$parent_layout];

			echo $langmessage['restore'].': ';
			$span = '<span class="layout_color_id" style="background-color:'.$parent_info['color'].';" title="'.$parent_info['color'].'"></span> ';
			echo common::Link($url,$span.$parent_info['label'],$query_string.'cmd=restorelayout&title='.urlencode($title),' title="'.$langmessage['restore'].'" name="gpajax" ','restore');
			echo '</p>';
		}


		echo '<table class="bordered" style="width:100%">';

		echo '<tr>';
			echo '<th>';

			echo $langmessage['available_layouts'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['theme'];
			echo '</th>';
			echo '</tr>';

		if( count($gpLayouts) < 2 ){
			echo '<tr><td colspan="2">';
			echo $langmessage['Empty'];
			echo '</td></tr>';
			echo '</table>';
			echo common::Link('Admin_Theme_Content',$langmessage['new_layout']);
			echo '</div>';
			return;
		}

		foreach($gpLayouts as $layout => $info){
			if( $layout == $curr_layout ){
				continue;
			}
			echo '<tr>';
			echo '<td>';
			echo '<span class="layout_color_id" style="background-color:'.$info['color'].';" title="'.$info['color'].'">';
			echo '</span> ';
			if( $layout != $curr_layout ){
				echo common::Link($url,$info['label'],$query_string.'cmd=uselayout&title='.urlencode($title).'&layout='.urlencode($layout),'name="gpajax"','use_'.$layout);
			}
			echo '</td>';
			echo '<td>';
			echo $info['theme'];
			echo '</td>';
			echo '</tr>';

		}
		echo '</table>';

		$affected = page_layout::GetAffectedFiles($index);

		echo '<br/>';
		echo '<h3>'.$langmessage['affected_files'].'</h3>';
		echo '<p class="sm">';
		echo $langmessage['about_layout_change'];
		echo '</p>';
		echo '<p class="admin_note" style="width:35em">';
		echo str_replace('_',' ',$title);
		$i = 0;
		foreach($affected as $affected_label){
			$i++;
			echo ', '.$affected_label;
		}
		echo '</p>';

		echo '<p>';
		echo ' <input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close" /> ';
		echo '</p>';

		echo '<p class="admin_note">';
		echo '<b>';
		echo $langmessage['see_also'];
		echo '</b> ';
		echo common::Link('Admin_Theme_Content',$langmessage['layouts']);
		echo '</p>';

		echo '</div>';
	}

	function GetAffectedFiles($index){
		global $gp_titles, $gp_menu;

		$temp = $gp_menu;
		reset($temp);
		$result = array();

		$i = 0;
		do{
			$menu_key = key($temp);
			$info = current($temp);
			if( !isset($info['level']) ){
				break;
			}
			$level = $info['level'];

			unset($temp[$menu_key]);
			if( $index === $menu_key ){
				page_layout::InheritingLayout($level+1,$temp,$result);
			}
			$i++;
		}while( (count($temp) > 0) );
		return $result;

	}

	function InheritingLayout($searchLevel,&$menu,&$result){
		global $gp_titles;

		$children = true;
		do{
			$menu_key = key($menu);
			$info = current($menu);
			if( !isset($info['level']) ){
				break;
			}
			$level = $info['level'];

			if( $level < $searchLevel ){
				return;
			}
			if( $level > $searchLevel ){
				if( $children ){
					page_layout::InheritingLayout($level,$menu,$result);
				}else{
					unset($menu[$menu_key]);
				}
				continue;
			}

			unset($menu[$menu_key]);
			if( !empty($gp_titles[$menu_key]['gpLayout']) ){
				$children = false;
				continue;
			}
			$children = true;
			$result[] = common::GetLabelIndex($menu_key);
		}while( count($menu) > 0 );

	}



}
