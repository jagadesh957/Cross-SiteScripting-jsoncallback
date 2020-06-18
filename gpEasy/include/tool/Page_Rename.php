<?php
defined('is_running') or die('Not an entry point...');


class rename_details{

	function RenameForm($title,$action){
		global $langmessage,$page,$gp_index,$gp_titles;


		$id = $gp_index[$title];
		$label = common::GetLabel($title);
		$title_info = $gp_titles[$id];

		if( empty($_REQUEST['new_title']) ){
			$new_title = $label;
		}else{
			$new_title = htmlspecialchars($_REQUEST['new_title']);
		}
		$new_title = str_replace('_',' ',$new_title);


		//show more options?
		$hidden_rows = false;

		ob_start();
		echo '<div class="inline_box">';
		echo '<form action="'.$action.'" method="post" id="gp_rename_form">';
		echo '<input type="hidden" name="old_title" value="'.htmlspecialchars(str_replace('_',' ',$title)).'" />';

		echo '<h2>'.$langmessage['rename/details'].'</h2>';

		echo '<input type="hidden" name="title" value="'.htmlspecialchars($title).'" />';

		echo '<table class="bordered" style="width:100%" id="gp_rename_table">';
		echo '<thead>';
		echo '<tr>';
			echo '<th colspan="2">';
			echo $langmessage['options'];
			echo '</th>';
			echo '</tr>';
			echo '</thead>';

		echo '<tbody>';
		echo '<tr>';
			echo '<td class="formlabel">';
			echo $langmessage['label'];
			echo '</td>';
			echo '<td>';
			echo '<input type="text" class="title_label" name="new_label" maxlength="60" size="30" value="'.$new_title.'" />';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td class="formlabel">';
			echo $langmessage['Slug/URL'];
			echo '</td>';
			echo '<td>';


			//edited slug?
			$attr = '';
			$class = 'new_title';
			$editable = true;
			if( common::SpecialOrAdmin($title) ){
				$attr = 'disabled="disabled" ';
				$editable = false;

			}elseif( htmlspecialchars($title) == gpFiles::CleanTitle($label) ){
				$attr = 'disabled="disabled" ';
				$class .= ' sync_label';
			}
			echo '<input type="text" class="'.$class.'" name="new_title" maxlength="60" size="30" value="'.$title.'" '.$attr.'/>';
			if( $editable ){
				echo ' <div class="admin_note label_synchronize">';
				if( empty( $attr ) ){
					echo '<a href="#">'.$langmessage['sync_with_label'].'</a>';
					echo '<a href="#" class="slug_edit" style="display:none">'.$langmessage['edit'].'</a>';
				}else{
					echo '<a href="#" style="display:none">'.$langmessage['sync_with_label'].'</a>';
					echo '<a href="#" class="slug_edit">'.$langmessage['edit'].'</a>';
				}
				echo '</div>';

			}

			echo '</td>';
			echo '</tr>';



		//browser title defaults to label
			$attr = '';
			$class = '';
			if( isset($title_info['browser_title']) ){
				echo '<tr>';
				$browser_title = $title_info['browser_title'];
			}else{
				echo '<tr style="display:none">';
				$hidden_rows = true;
				$browser_title = $label;
				$attr = 'disabled="disabled" ';
				$class .= ' sync_label';
			}
			echo '<td class="formlabel">';
			echo $langmessage['browser_title'];
			echo '</td>';
			echo '<td>';
			echo '<input type="text" class="'.$class.'" size="30" name="browser_title" value="'.$browser_title.'" '.$attr.'/>';
			echo ' <div class="admin_note label_synchronize">';
			if( empty( $attr ) ){
				echo '<a href="#">'.$langmessage['sync_with_label'].'</a>';
				echo '<a href="#" class="slug_edit" style="display:none">'.$langmessage['edit'].'</a>';
			}else{
				echo '<a href="#" style="display:none">'.$langmessage['sync_with_label'].'</a>';
				echo '<a href="#" class="slug_edit">'.$langmessage['edit'].'</a>';
			}
			echo '</div>';
			echo '</td>';
			echo '</tr>';

		//meta keywords
			$keywords = '';
			if( isset($title_info['keywords']) ){
				echo '<tr>';
				$keywords = $title_info['keywords'];
			}else{
				echo '<tr style="display:none">';
				$hidden_rows = true;
			}
			echo '<td class="formlabel">';
			echo $langmessage['keywords'];
			echo '</td>';
			echo '<td>';
			echo '<input type="text" size="30" name="keywords" value="'.$keywords.'" />';
			echo '</td>';
			echo '</tr>';

		//meta description
			$description = '';
			if( isset($title_info['description']) ){
				echo '<tr>';
				$description = $title_info['description'];
			}else{
				echo '<tr style="display:none">';
				$hidden_rows = true;
			}
			echo '<td class="formlabel">';
			echo $langmessage['description'];
			echo '</td>';
			echo '<td>';
			echo '<input type="text" size="30" name="description" value="'.$description.'" />';
			echo '</td>';
			echo '</tr>';

		//robots
			$rel = '';
			if( isset($title_info['rel']) ){
				echo '<tr>';
				$rel = $title_info['rel'];
			}else{
				echo '<tr style="display:none">';
				$hidden_rows = true;
			}
			echo '<td class="formlabel">';
			echo $langmessage['robots'];
			echo '</td>';
			echo '<td>';

			echo '<label>';
			$checked = (strpos($rel,'nofollow') !== false) ? 'checked="checked"' : '';
			echo '<input type="checkbox" name="nofollow" value="nofollow" '.$checked.'/> ';
			echo '  Nofollow ';
			echo '</label>';

			echo '<label>';
			$checked = (strpos($rel,'noindex') !== false) ? 'checked="checked"' : '';
			echo '<input type="checkbox" name="noindex" value="noindex" '.$checked.'/> ';
			echo ' Noindex';
			echo '</label>';

			echo '</td>';
			echo '</tr>';

		echo '</tbody>';
		echo '</table>';


		//redirection
		echo '<p id="gp_rename_redirect" style="display:none">';
		echo '<label>';
		echo '<input type="checkbox" name="add_redirect" value="add" /> ';
		echo sprintf($langmessage['Auto Redirect'],'"'.$title.'"');
		echo '</label>';
		echo '</p>';


		echo '<p>';
			echo '<input type="hidden" name="cmd" value="renameit"/> ';
			echo '<input type="submit" name="" value="'.$langmessage['save_changes'].'" class="menupost gpsubmit"/>';
			echo '<input type="button" class="admin_box_close gpcancel" name="" value="'.$langmessage['cancel'].'" />';

			if( $hidden_rows )  echo ' &nbsp; <a href="" name="showmore" >+ '.$langmessage['more_options'].'</a>';

			echo '</p>';

		echo '</form>';
		echo '</div>';

		$content = ob_get_clean();

		$page->ajaxReplace = array();


		$array = array();
		$array[0] = 'admin_box_data';
		$array[1] = '';
		$array[2] = $content;
		$page->ajaxReplace[] = $array;



		//call renameprep function after admin_box
		$array = array();
		$array[0] = 'renameprep';
		$array[1] = '';
		$array[2] = '';
		$page->ajaxReplace[] = $array;


	}


	function RenameFile($title){
		global $langmessage, $page, $gp_index, $gp_titles;



		//change the title
		$title = rename_details::RenameFileWorker($title);
		if( $title === false ){
			return false;
		}


		if( !isset($gp_index[$title]) ){
			message($langmessage['OOPS']);
			return false;
		}

		$id = $gp_index[$title];
		$title_info = &$gp_titles[$id];

		//change the label
		$title_info['label'] = gpFiles::CleanLabel($_POST['new_label']);
		if( isset($title_info['lang_index']) ){
			unset($title_info['lang_index']);
		}



		//change the browser title
		$custom_browser_title = false;
		if( isset($_POST['browser_title']) ){
			$browser_title = $_POST['browser_title'];
			$browser_title = htmlspecialchars($browser_title);

			if( $browser_title != $title_info['label'] ){
				$title_info['browser_title'] = trim($browser_title);
				$custom_browser_title = true;
			}
		}
		if( !$custom_browser_title ){
			unset($title_info['browser_title']);
		}

		//keywords
		if( isset($_POST['keywords']) ){
			$title_info['keywords'] = htmlspecialchars($_POST['keywords']);
			if( empty($title_info['keywords']) ){
				unset($title_info['keywords']);
			}
		}


		//description
		if( isset($_POST['description']) ){
			$title_info['description'] = htmlspecialchars($_POST['description']);
			if( empty($title_info['description']) ){
				unset($title_info['description']);
			}
		}


		//robots
		$title_info['rel'] = '';
		if( isset($_POST['nofollow']) ){
			$title_info['rel'] = 'nofollow';
		}
		if( isset($_POST['noindex']) ){
			$title_info['rel'] .= ',noindex';
		}
		$title_info['rel'] = trim($title_info['rel'],',');
		if( empty($title_info['rel']) ) unset($title_info['rel']);


		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].' (R1)');
			return false;
		}

		message($langmessage['SAVED']);
		return $title;
	}



	function RenameFileWorker($title){
		global $langmessage,$dataDir,$gp_index;

		if( common::SpecialOrAdmin($title) ){
			return $title;
		}

		//use new_label or new_title
		if( isset($_POST['new_title']) ){
			$new_title = gpFiles::CleanTitle($_POST['new_title']);
		}else{
			$new_title = gpFiles::CleanTitle($_POST['new_label']);
		}

		//title unchanged
		if( $new_title == $title ){
			return $title;
		}

		if( isset($gp_index[$new_title]) ){
			message($langmessage['TITLE_EXISTS']);
			return false;
		}
		if( empty($title) ){
			message($langmessage['TITLE_REQUIRED']);
			return $title;
		}

		if( strlen($title) > 60 ){
			message($langmessage['LONG_TITLE']);
			return $title;
		}

		$old_gp_index = $gp_index;

		//re-index
		$id = $gp_index[$title];
		unset($gp_index[$title]);
		$gp_index[$new_title] = $id;


		//rename the php file
		$new_file = gpFiles::PageFile($new_title);
		$old_file = gpFiles::PageFile($title);

		if( !rename($old_file,$new_file) ){
			message($langmessage['OOPS'].' (N3)');
			$gp_index = $old_gp_index;
			return false;
		}


		//gallery rename
		includeFile('special/special_galleries.php');
		special_galleries::RenamedGallery($title,$new_title);


		//create a 301 redirect
		if( isset($_POST['add_redirect']) && $_POST['add_redirect'] == 'add' ){
			includeFile('admin/admin_missing.php');
			admin_missing::AddRedirect($title,$new_title);
		}

		return $new_title;
	}

}
