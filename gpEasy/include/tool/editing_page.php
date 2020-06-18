<?php
defined('is_running') or die('Not an entry point...');

class editing_page extends display{


	function editing_page($title,$type){
		global $langmessage;

		parent::display($title,$type);
	}

	function RunScript(){
		global $dataDir,$langmessage,$page;
		$cmd = common::GetCommand();

		if( !$this->SetVars() ){
			return;
		}

		$this->GetFile();

		//original alpha versions of 1.8 didn't maintain the file_type
		if( !isset($this->meta_data['file_type'])  ){
			$this->ResetFileTypes();
		}


		//admin main links and actions
		if( admin_tools::HasPermission('Admin_Menu') ){
			$page->admin_links[] = common::Link($this->title,$langmessage['rename/details'],'cmd=renameform',' name="gpajax" ');

			// Having the layout link here complicates things.. would need layout link for special pages
			//$page->admin_links[] = common::Link($this->title,$langmessage['current_layout'],'cmd=layout',' title="'.$langmessage['current_layout'].'" name="admin_box"');
			$page->admin_links[] = common::Link('Admin_Menu',$langmessage['current_layout'],'cmd=layout&from=page&title='.urlencode($this->title),' title="'.$langmessage['current_layout'].'" name="admin_box"');

			switch($cmd){
				// rename & details
				case 'renameform':
					$this->RenameForm();
				return;
				case 'renameit':
					if( $this->RenameFile() ){
						return;
					}
				break;
			}
		}


		//file editing actions
		if( admin_tools::HasPermission('file_editing') ){

			switch($cmd){
				/* functions used for editing */

				case 'new_dir':
					$this->NewDirForm();//dies

				//section editing
				case 'move_up':
					$this->MoveUp();
				break;

				case 'new_section':
					$this->NewSectionPrompt();
				return;

				case 'add_section':
					$this->AddNewSection();
				break;

				case 'rm_section':
					$this->RmSection();
				break;

				case 'save':
					$this->SaveSection();
				return;

				case 'rawcontent':
					$this->RawContent();
				break;


				/* gallery editing */
				case 'gallery_images':
					$this->GalleryImages();
				return;

				/* include editing */
				case 'preview':
					$this->PreviewSection();
				return;
				case 'include_dialog':
					$this->IncludeDialog();
				return;

				/* inline editing */
				case 'inlineedit':
					$this->InlineEdit();
				die();

			}
		}

		$this->contentBuffer = editing_page::GenerateContent_Admin();
	}

	function InlineEdit(){
		global $langmessage,$dataDir;

		$section = $_REQUEST['section'];
		if( !is_numeric($section) || !isset($this->file_sections[$section])){
			echo 'false';
			return false;
		}

		includeFile('tool/ajax.php');
		gpAjax::InlineEdit($this->file_sections[$section]);
	}

	/*
	 * Send the raw content of the section to the gpResponse handler
	 *
	 */
	function RawContent(){
		global $page,$langmessage;

		//for ajax responses
		$page->ajaxReplace = array();

		$section = $_REQUEST['section'];
		if( !is_numeric($section) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}

		if( !isset($this->file_sections[$section]) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}

		$page->ajaxReplace[] = array('rawcontent','',$this->file_sections[$section]['content']);
	}


	/**
	 * Recalculate the file_type string for this file
	 * Used by AddNewSection(), RmSection()
	 * Updates $this->meta_data and $gp_titles
	 *
	 */
	function ResetFileTypes(){
		global $gp_titles;

		$original_types = array();
		if( isset($this->meta_data['file_type']) ){
			$original_types = explode(',',$this->meta_data['file_type']);
		}

		$new_types = array();
		foreach($this->file_sections as $section){
			$new_types[] = $section['type'];
		}
		$new_types = array_unique($new_types);
		$new_types = array_diff($new_types,array(''));
		sort($new_types);

		$new_types = implode(',',$new_types);
		$this->meta_data['file_type'] = $new_types;

		if( !isset($gp_titles[$this->gp_index]) ){
			return;
		}

		$gp_titles[$this->gp_index]['type'] = $new_types;
		admin_tools::SavePagesPHP();
		$this->SaveThis();
	}

	function RenameFile(){
		global $langmessage, $gp_index, $page;

		includeFile('tool/Page_Rename.php');
		$new_title = rename_details::RenameFile($this->title);
		if( ($new_title !== false) && $new_title != $this->title ){
			message(sprintf($langmessage['will_redirect'],common::Link($new_title,$new_title)));
			$page->head .= '<meta http-equiv="refresh" content="15;url='.common::GetUrl($new_title).'">';
			return true;
		}
		return false;
	}


	function RenameForm(){
		global $langmessage,$page,$gp_index;

		includeFile('tool/Page_Rename.php');
		$action = common::GetUrl($this->title);
		rename_details::RenameForm($this->title,$action);
	}


	function MoveUp(){
		global $langmessage;


		$move_key =& $_REQUEST['section'];
		if( !isset($this->file_sections[$move_key]) ){
			message($langmessage['OOPS']);
			return false;
		}

		if( !common::verify_nonce('move_up'.$move_key) ){
			message($langmessage['OOPS']);
			return false;
		}


		$move_content = $this->file_sections[$move_key];

		$file_keys = array_keys($this->file_sections);
		$file_values = array_values($this->file_sections);
		$insert_key = array_search($move_key,$file_keys);
		if( ($insert_key === null) || ($insert_key === false) || ($insert_key === 0) ){
			message($langmessage['OOPS']);
			return false;
		}

		$prev_key = $insert_key-1;

		if( !isset($file_keys[$prev_key]) ){
			message($langmessage['OOPS']);
			return false;
		}

		$old_sections = $this->file_sections;

		//rebuild
		$new_sections = array();
		foreach($file_values as $temp_key => $file_value){

			if( $temp_key === $prev_key ){
				$new_sections[] = $move_content;
			}elseif( $temp_key === $insert_key ){
				//moved section
				continue;
			}
			$new_sections[] = $file_value;
		}

		$this->file_sections = $new_sections;

		if( !$this->SaveThis() ){
			$this->file_sections = $old_sections;
			message($langmessage['OOPS'].'(4)');
			return;
		}
	}

	function RmSection(){
		global $langmessage,$page;

		if( !isset($_POST['total']) || $_POST['total'] != count($this->file_sections) ){
			message($langmessage['OOPS']);
			return false;
		}

		if( !isset($_POST['section']) ){
			message($langmessage['OOPS'].'(1)');
			return;
		}

		$section = $_POST['section'];

		if( !isset($this->file_sections[$section]) ){
			message($langmessage['OOPS'].'(2)');
			return;
		}

		$section_data = $this->file_sections[$section];

		array_splice( $this->file_sections , $section , 1 );

		$this->ResetFileTypes();

		if( !$this->SaveThis() ){
			message($langmessage['OOPS'].'(4)');
			return;
		}

		if( $section_data['type'] == 'gallery' ){
			$this->GalleryEdited();
		}

		message($langmessage['SAVED']);
	}


	function AddNewSection(){
		global $langmessage;

		if( $_POST['last_mod'] != $this->fileModTime ){
			message($langmessage['OOPS']);
			return false;
		}

		if( !isset($_POST['section']) ){
			message($langmessage['OOPS'].'(1)');
			return;
		}

		$section = $_POST['section'];

		if( !isset($this->file_sections[$section]) ){
			message($langmessage['OOPS'].'(2)');
			return;
		}


		$start_content['type'] = $_POST['content_type'];
		$start_content['content'] = editing_page::GetDefaultContent($start_content['type']);
		if( $start_content['content'] === false ){
			message($langmessage['OOPS'].'(3)');
			return;
		}

		if( $_POST['insert'] == 'before' ){
			array_splice( $this->file_sections , $section , 0, 'temporary' );
			$new_section = $section;
		}else{
			array_splice( $this->file_sections , $section+1 , 0, 'temporary' );
			$new_section = $section+1;
		}

		if( $this->file_sections[$new_section] != 'temporary' ){
			message($langmessage['OOPS'].'(4)');
			return;
		}


		$this->file_sections[$new_section] = $start_content;

		$this->ResetFileTypes();

		if( !$this->SaveThis() ){
			message($langmessage['OOPS'].'(4)');
			return;
		}


		message($langmessage['SAVED']);
	}


	/**
	 * Get the default content for the specified content type
	 *
	 * @static
	 *
	 */
	function GetDefaultContent($type){
		global $langmessage;

		switch($type){
			case 'include':
				$default_content = '';
			break;

			case 'gallery':
				$default_content = '<ul class="gp_gallery"><li class="gp_to_remove"></li></ul>';
			break;

			case 'text':
			default:
				$default_content = '<p>'.$langmessage['New Section'].'</p>';
			break;
		}

		$default_content = gpPlugin::Filter('GetDefaultContent',array($default_content,$type));

		return $default_content;
	}


	function NewSectionPrompt(){
		global $langmessage;


		ob_start();
		echo '<div class="inline_box">';
		echo '<form method="post" action="'.common::GetUrl($this->title).'">';
		echo '<h2>'.$langmessage['New Section'].'</h2>';
		echo '<div>';
		echo $langmessage['new_section_about'];
		echo '</div>';

		echo '<table class="bordered" style="width:100%">';

		echo '<tr>';
			//echo '<td class="formlabel">';
			echo '<td>';
			echo $langmessage['Content Type'];
			echo '</td>';
			echo '<td>';

			editing_page::SectionTypes();

			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			//echo '<td class="formlabel">';
			echo '<td>';
			echo $langmessage['Insert Location'];
			echo '</td>';
			echo '<td>';
			echo '<label>';
			echo '<input type="radio" name="insert" value="before" /> ';
			echo $langmessage['insert_before'];
			echo '</label>';
			echo '<input type="radio" name="insert" value="after" checked="checked" /> ';
			echo $langmessage['insert_after'];
			echo '</label>';
			echo '</td>';
			echo '</tr>';

		echo '</table>';

		echo '<p>';
		echo '<input type="hidden" name="last_mod" value="'.$this->fileModTime.'" />';
		echo '<input type="hidden" name="section" value="'.$_GET['section'].'" />';
		echo '<input type="hidden" name="cmd" value="add_section" />';
		echo '<input type="submit" name="" value="'.$langmessage['save'].'"/>';
		echo ' <input type="button" name="" value="'.$langmessage['cancel'].'" class="admin_box_close" />';
		echo '</p>';


		echo '</form>';
		echo '</div>';
		$this->contentBuffer = ob_get_clean();

	}

	/*
	 * @static
	 */
	function SectionTypes(){
		global $langmessage;
		$section_types['text']['label']		= $langmessage['Editable Text'];
		$section_types['gallery']['label']	= $langmessage['Image Gallery'];
		$section_types['include']['label']	= $langmessage['File Include'];

		$section_types = gpPlugin::Filter('SectionTypes',array($section_types));

		$checked = 'checked="checked"';
		foreach($section_types as $type => $type_info){
			echo '<label>';
			echo '<input type="radio" name="content_type" value="'.htmlspecialchars($type).'" '.$checked.'/> ';
			echo htmlspecialchars($type_info['label']);
			echo '</label>';
			$checked = '';
		}
	}


	function PreviewSection(){
		global $page,$langmessage;

		//for ajax responses
		$page->ajaxReplace = array();

		$section = $_POST['section'];
		if( !is_numeric($section) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}

		if( !isset($this->file_sections[$section]) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}

		$type = $this->file_sections[$section]['type'];



		switch($type){
			case 'include':
				if( !empty($_POST['gadget_include']) ){
					$data = array();
					$data['include_type'] = 'gadget';
					$data['content'] = $_POST['gadget_include'];
				}else{
					$data = array();
					$data['content'] = gpFiles::CleanTitle($_POST['file_include']);
				}


				$content = $this->IncludeContent($data);
				$page->ajaxReplace[] = array('gp_include_content','',$content);
			break;
			default:
				message($langmessage['OOPS'].'(2)');
			return false;
		}
	}

	function SaveSection(){
		global $page,$langmessage;

		//for ajax responses
		$page->ajaxReplace = array();


		//check
		$section =& $_POST['section'];
		if( !is_numeric($section) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}

		if( !isset($this->file_sections[$section]) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}


		$type = $this->file_sections[$section]['type'];

		$save_this = false;
		switch($type){
			case 'text':
				$save_this = true;
				$this->SaveSection_Text($section);
			break;
			case 'gallery':
				$save_this = true;
				$this->SaveSection_Text($section);
				$this->GalleryEdited();
			break;
			case 'include':
				$save_this = $this->SaveSection_Include($section);
			break;
		}

		$save_this = gpPlugin::Filter('SaveSection',array($save_this,$section,$type));
		if( $save_this !== true ){
			message($langmessage['OOPS'].'(2)');
			return false;
		}

		//save to _pages
		if( !$this->SaveThis() ){
			message($langmessage['OOPS'].'(3)');
			return false;
		}
		$page->ajaxReplace[] = array('ck_saved','','');
		message($langmessage['SAVED']);
		return true;
	}

	function SaveThis(){

		if( !is_array($this->meta_data) || !is_array($this->file_sections) ){
			return false;
		}

		//file count
		if( !isset($this->meta_data['file_number']) ){
			$this->meta_data['file_number'] = gpFiles::NewFileNumber();
		}

		return gpFiles::SaveArray($this->file,'meta_data',$this->meta_data,'file_sections',$this->file_sections);
	}

	function SaveSection_Include($section){
		global $page, $langmessage, $gp_index;


		$section_data = $this->file_sections[$section];
		unset($section_data['index']);

		if( !empty($_POST['gadget_include']) ){
			$section_data['include_type'] = 'gadget';
			$section_data['content'] = gpFiles::CleanTitle($_POST['gadget_include']);
		}else{
			$cleaned = gpFiles::CleanTitle($_POST['file_include']);
			$section_data['include_type'] = common::SpecialOrAdmin($cleaned);
			if( isset($gp_index[$cleaned]) ){
				$section_data['index'] = $gp_index[$cleaned];
			}
			$section_data['content'] = $cleaned;
		}

		$this->file_sections[$section] = $section_data;

		//send replacement content
		$content = $this->IncludeContent($section_data);
		$page->ajaxReplace[] = array('gp_include_content','',$content);
		return true;
	}


	function SaveSection_Text($section){
		$content =& $_POST['gpcontent'];
		gpFiles::cleanText($content);
		$this->file_sections[$section]['content'] = $content;
		return true;
	}


	/**
	 * Extract information about the gallery from it's html: img_count, icon_src
	 * Call GalleryEdited when a gallery section is removed, edited
	 *
	 */
	function GalleryEdited(){
		includeFile('special/special_galleries.php');
		special_galleries::UpdateGalleryInfo($this->title,$this->file_sections);
	}

	function GenerateContent_Admin(){
		global $langmessage,$GP_NESTED_EDIT;

		//add to all pages in case a user adds a gallery
		gpPlugin::Action('GenerateContent_Admin');
		common::ShowingGallery();

		$content = '';
		$section_num = 0;
		foreach($this->file_sections as $section_key => $section_data){
			$content .= "\n";
			$type = isset($section_data['type']) ? $section_data['type'] : 'text';

			if( gpOutput::ShowEditLink('file_editing') ){
				$link_name = 'inline_edit_generic';
				$link_rel = $type.'_inline_edit';


				$title_attr = htmlspecialchars($this->title).': '.sprintf($langmessage['Section %s'],$section_key+1);
				$link = gpOutput::EditAreaLink($edit_index,$this->title,$langmessage['edit'],'section='.$section_key,' title="'.$title_attr.'" name="'.$link_name.'" rel="'.$link_rel.'"');

				//section control links
				$content .= '<span style="display:none;" id="ExtraEditLnks'.$edit_index.'">';
				$content .= $link;

				if( $section_num > 0 ){
					$content .= common::Link($this->title,$langmessage['move_up'],'cmd=move_up&section='.$section_key,' name="creq" style="display:none"','move_up'.$section_key);
				}

				$content .= common::Link($this->title,$langmessage['New Section'],'cmd=new_section&section='.$section_key,' name="admin_box" style="display:none"');

				//remove section link
				if( count($this->file_sections) > 1 ){
					$title_attr = $langmessage['rm_section_confirm'];
					if( $type != 'include' ){
						$title_attr .= "\n\n".$langmessage['rm_section_confirm_deleting'];
					}

					$content .= common::Link($this->title,$langmessage['Remove Section'],'cmd=rm_section&section='.$section_key.'&total='.count($this->file_sections),' title="'.$title_attr.'" name="creq" class="gpconfirm" style="display:none"');
				}
				$content .= '</span>';
				$content .= '<div class="editable_area GPAREA filetype-'.$type.'" id="ExtraEditArea'.$edit_index.'">'; // class="edit_area" added by javascript
			}else{
				$content .= '<div class="GPAREA filetype-'.$type.'">';
			}

			$GP_NESTED_EDIT = true;
			$content .= $this->SectionToContent($section_data);
			$GP_NESTED_EDIT = false;

			$content .= '<div class="gpclear"></div>';
			$content .= '</div>';
			$section_num++;
		}
		return $content;
	}


	/*
	 * sends image information to gallery editor
	 *
	 *
	 * gallery editor uses this html to create the new gallery html
		<li>
			<a href="'.$imgPath.'" name="gallery" rel="gallery_gallery" title="'.htmlspecialchars($caption).'">
			<img src="'.$thumbPath.'" height="100" width="100"  alt=""/>
			</a>
			<div class="caption">
			$caption
			</div>
		</li>
	 *
	 */

	function GalleryImages(){
		global $page,$dataDir,$langmessage;
		includeFile('admin/admin_uploaded.php');

		$page->ajaxReplace = array();

		if( isset($_GET['dir']) ){
			$dir_piece = $_GET['dir'];
		}elseif( isset($this->meta_data['gallery_dir']) ){
			$dir_piece = $this->meta_data['gallery_dir'];
		}else{
			$dir_piece = '/image';
		}
		$dir = $dataDir.'/data/_uploaded'.$dir_piece;

		$prev_piece = false;

		while( ($dir_piece != '/') && !file_exists($dir) ){
			$prev_piece = $dir_piece;
			$dir = dirname($dir);
			$dir_piece = dirname($dir_piece);
		}

		//remember browse directory
		$this->meta_data['gallery_dir'] = $dir_piece;
		$this->SaveThis();


		//new directory?
		if( $prev_piece ){
			$prev_piece = gpFiles::CleanArg($prev_piece);
			$dir_piece = $prev_piece;
			$dir = $dataDir.'/data/_uploaded'.$prev_piece;

			if( !gpFiles::CheckDir($dir) ){
				message($langmessage['OOPS']);
				$dir = dirname($dir);
				$dir_piece = dirname($prev_piece);
			}
		}

		admin_uploaded::InlineList($dir,$dir_piece);
	}


	function NewDirForm(){
		includeFile('admin/admin_uploaded.php');
		$url = common::GetUrl($this->title);
		admin_uploaded::NewDirForm($url,'',$_GET['dir'],'gp_gallery_folder_add');
		die();
	}

	/*
	 * Include Editing
	 */
	function IncludeDialog(){
		global $page,$langmessage,$config;

		$page->ajaxReplace = array();

		$section =& $_GET['section'];
		if( !isset($this->file_sections[$section]) ){
			message($langmessage['OOPS']);
			return;
		}

		$include_type =& $this->file_sections[$section]['include_type'];

		$gadget_content = '';
		$file_content = '';
		switch($include_type){
			case 'gadget':
				$gadget_content =& $this->file_sections[$section]['content'];
			break;
			default:
				$file_content =& $this->file_sections[$section]['content'];
			break;
		}

		ob_start();

		echo '<form id="gp_include_form">';

		echo '<div class="gp_inlude_edit">';
		echo '<span class="label">';
		echo $langmessage['File Include'];
		echo '</span>';
		echo '<input type="text" size="" id="gp_file_include" name="file_include" class="autocomplete" value="'.htmlspecialchars($file_content).'" />';
		echo '</div>';

		echo '<div class="gp_inlude_edit">';
		echo '<span class="label">';
		echo $langmessage['gadgets'];
		echo '</span>';
		echo '<input type="text" size="" id="gp_gadget_include" name="gadget_include" class="autocomplete" value="'.htmlspecialchars($gadget_content).'" />';
		echo '</div>';

		echo '<div class="option_area">';
		echo '<a href="#" name="gp_include_preview" class="ckeditor_control full_width">Preview</a>';
		echo '</div>';

		echo '</form>';


		$content = ob_get_clean();
		$page->ajaxReplace[] = array('gp_include_dialog','',$content);


		//file include autocomplete
		$options['admin_vals'] = false;
		$options['var_name'] = 'source';
		$file_includes = common::AutoCompleteValues(false,$options);
		$page->ajaxReplace[] = array('gp_autocomplete_include','file',$file_includes);


		//gadget include autocomplete
		$code = 'var source=[';
		if( isset($config['gadgets']) ){
			foreach($config['gadgets'] as $uniq => $info){
				$code .= '["'.addslashes($uniq).'","'.addslashes($uniq).'"],';
			}
		}
		$code .= ']';
		$page->ajaxReplace[] = array('gp_autocomplete_include','gadget',$code);



	}


}

