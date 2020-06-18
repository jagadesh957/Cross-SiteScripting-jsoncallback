<?php
defined('is_running') or die('Not an entry point...');



class admin_uploaded{

	var $baseDir;
	var $subdir = false;
	var $MaxUpload;
	var $thumbFolder;
	var $isThumbDir = false;
	var $queryString = '';
	var	$imgTypes;
	var $errorMessages = array();

	var $odd_even_count = 0;
	var $odd_even_classes = array(0=>'list_even',1=>'list_odd');

	var $browseString = 'Admin_Uploaded';


	function admin_uploaded(){
		$this->Init();
		$this->do_admin_uploaded();
	}

	function do_admin_uploaded(){
		global $page;

		$show_inline = false;
		if( isset($_REQUEST['show']) && $_REQUEST['show'] == 'inline' ){
			$page->ajaxReplace = array();
			$show_inline = true;
		}

		$file_cmd = common::GetCommand('file_cmd');

		switch($file_cmd){
			case 'delete':
				$this->DeleteConfirmed();
			break;

			case 'view':
				$this->View();
			break;
			case 'upload':
				$this->UploadFiles();
			break;

			case 'new_dir':
				$this->NewDirPrompt();
			return;

			case 'createdir':
				$this->CreateDir();
			break;

			case 'inline_upload':
				$this->InlineUpload();
				//dies
		}

		if( $show_inline ){
			admin_uploaded::InlineList($this->currentDir,$this->subdir);
		}else{
			$this->ShowPanel();
			$this->ShowFolder();
		}

	}

	function Init(){
		global $langmessage, $dataDir,$page, $upload_extensions_allow, $upload_extensions_deny;


		$this->baseDir = $dataDir.'/data/_uploaded';
		$this->thumbFolder = $dataDir.'/data/_uploaded/image/thumbnails';
		$this->currentDir = $this->baseDir;

		$page->css_admin[] = '/include/css/browser.css';

		$page->head_js[] = '/include/js/browser_prefs.js';
		$page->head_js[] = '/include/js/jquery.auto_upload.js';


		$this->AllowedExtensions = array('7z', 'aiff', 'asf', 'avi', 'bmp', 'bz', 'csv', 'doc', 'fla', 'flv', 'gif', 'gz', 'gzip', 'jpeg', 'jpg', 'mid', 'mov', 'mp3', 'mp4', 'mpc', 'mpeg', 'mpg', 'ods', 'odt', 'pdf', 'png', 'ppt', 'pxd', 'qt', 'ram', 'rar', 'rm', 'rmi', 'rmvb', 'rtf', 'sdc', 'sitd', 'swf', 'sxc', 'sxw', 'tar', 'tgz', 'tif', 'tiff', 'txt', 'vsd', 'wav', 'wma', 'wmv', 'xls', 'xml', 'zip');
		if( is_array($upload_extensions_allow) ){
			$this->AllowedExtensions = array_merge($this->AllowedExtensions,$upload_extensions_allow);
		}
		if( is_array($upload_extensions_deny) ){
			$this->AllowedExtensions = array_diff($this->AllowedExtensions,$upload_extensions_deny);
		}
		$this->imgTypes = array('bmp'=>1,'png'=>1,'jpg'=>1,'jpeg'=>1,'gif'=>1,'tiff'=>1,'tif'=>1);


		//get the current path
		if( !empty($_REQUEST['dir']) ){
			$this->subdir = gpFiles::CleanArg($_REQUEST['dir']);
			$this->currentDir .= $this->subdir;

			if( !file_exists($this->currentDir) ){

				$action = common::GetUrl($this->browseString,'dir='.rawurlencode($this->subdir));
				$mess = '<form action="'.$action.'" method="post">';
				$mess .= sprintf($langmessage['create_dir_mess'],$this->subdir);
				$mess .= '<input type="submit" name="aaa" value="'.$langmessage['create_dir'].'" class="gppost" />';
				$mess .= ' <input type="hidden" name="newdir" value="'.htmlspecialchars($this->subdir).'" />';
				$mess .= ' <input type="hidden" name="file_cmd" value="createdir" />';
				$mess .= ' <input type="submit" name="file_cmd" value="'.$langmessage['cancel'].'" />';
				$mess .= '</form>';
				message($mess);

				$this->CheckDirectory();

			}
		}

		if( $this->subdir == '/' ){
			$this->subdir = false;
		}



		//is in thumbnail directory?
		if( strpos($this->currentDir,$this->thumbFolder) !== false ){
			$this->isThumbDir = true;
		}
		$this->currentDir_Thumb = $this->thumbFolder.$this->subdir;

	}

	function CheckDirectory(){

		do{
			if( file_exists($this->currentDir) ){
				return;
			}

			$oldSub = $this->subdir;
			$this->subdir = dirname($this->subdir);
			$this->currentDir = $this->baseDir.$this->subdir;

		}while( $oldSub != $this->subdir );
	}

	function CreateDir(){
		global $langmessage;

		$newDir = $_POST['newdir'];
		$newDir = str_replace('//','/',$newDir);
		if( empty($newDir) ){
			message($langmessage['OOPS'],'6');
			return;
		}

		$newDir = $this->currentDir .'/'. gpFiles::CleanArg($newDir);

		if( gpFiles::CheckDir($newDir) ){
			$temp = dirname($newDir);
			$len = strlen($this->baseDir);
			$this->subdir = substr($temp,$len);
			$this->CheckDirectory();
			message($langmessage['dir_created']);
		}else{
			message($langmessage['OOPS'],'7');
		}
	}


	function UploadPrep(){

		// not available till 4.3.1 and 5.0.3
		if( !defined('UPLOAD_ERR_NO_TMP_DIR') ){
			define('UPLOAD_ERR_NO_TMP_DIR',6);
		}

		// not available till 5.1.0
		if( !defined('UPLOAD_ERR_CANT_WRITE') ){
			define('UPLOAD_ERR_CANT_WRITE',7);
		}

		// not available till 5.2.0
		if( !defined('UPLOAD_ERR_EXTENSION') ){
			define('UPLOAD_ERR_EXTENSION',8);
		}
	}
	function ReadableMax(){
		$value = ini_get('upload_max_filesize');

		if( empty($value) ){
			return '2 Megabytes';//php default
		}
		return $value;
	}


	function Max_File_Size(){
		$max = admin_uploaded::getByteValue();
		if( $max !== false ){
			echo '<input type="hidden" name="MAX_FILE_SIZE" value="'.$max.'" />';
		}
	}

	function getByteValue($value=false){

		if( $value === false ){
			$value = ini_get('upload_max_filesize');
		}

		if( empty($value) ){
			return false;
			//$value = '2M';
		}

		if( is_numeric($value) ){
			return (int)$value;
		}


		$lastChar = $value{strlen($value)-1};
		$num = (int)substr($value,0,-1);

		switch(strtolower($lastChar)){

			case 'g':
				$num *= 1024;
			case 'm':
				$num *= 1024;
			case 'k':
				$num *= 1024;
			break;
		}
		return $num;

	}


	function InlineUpload(){

		if( count($_FILES['userfiles']['name']) != 1 ){
			$this->InlineResponse('failed','Empty Array');
		}

		$name = $_FILES['userfiles']['name'][0];
		if( empty($name) ){
			$this->InlineResponse('failed','Empty Name');
		}

		$uploaded = $this->UploadFile(0);
		$this->CleanTemporary();
		if( $uploaded === false ){
			reset($this->errorMessages);
			$this->InlineResponse('failed',current($this->errorMessages));
		}
		gpPlugin::Action('FileUploaded',$uploaded);

		$output =& $_POST['output'];
		switch($output){
			case 'gallery';
				$return_content = admin_uploaded::ShowFile_Gallery($this->subdir,$uploaded,$this->isThumbDir);
			break;

			default:
				ob_start();
				$this->ShowFile($uploaded);
				$return_content = ob_get_clean();
			break;
		}



		if( $return_content === false ){
			$this->InlineResponse('notimage','');
		}else{
			$this->InlineResponse('success',$return_content);
		}

	}

	/**
	 * Output a list a images in a director for use in inline editing
	 * @static
	 */
	function InlineList($dir,$dir_piece){
		global $page,$langmessage,$dataDir;

		ob_start();
		$isThumbDir = false;
		$thumbFolder = $dataDir.'/data/_uploaded/image/thumbnails';

		if( strpos($dir,$thumbFolder) !== false ){
			$isThumbDir = true;
		}


		$folders = $files = array();
		$allFiles = gpFiles::ReadFolderAndFiles($dir);
		list($folders,$files) = $allFiles;




		//available images
		$avail_imgs = '<div id="gp_gallery_avail_imgs">';
		$image_count = 0;
		foreach($files as $file){
			$img = admin_uploaded::ShowFile_Gallery($dir_piece,$file,$isThumbDir);
			if( $img ){
				$avail_imgs .= $img;
				$image_count++;
			}
		}
		$avail_imgs .= '</div>';


		//folder select
		echo '<div class="option_area">';
		echo '<div class="gp_edit_select">';
		echo '<a href="#" class="gp_gallery_folder ckeditor_control" name="gp_show_select"><span class="folder"></span><span class="down"></span>';
		if( strlen($dir_piece) > 23 ){
			echo '...'.substr($dir_piece,-20);
		}else{
			echo $dir_piece;
		}
		echo '</a>';

		echo '<div class="gp_edit_select_options">';
		if( $dir_piece != '/' ){
			$temp = dirname($dir_piece);
			echo '<a href="?cmd=new_dir&dir='.rawurlencode($dir_piece).'" class="gp_gallery_folder" name="admin_box"><span class="add"></span>'.$langmessage['create_dir'].'</a>';
			echo '<a href="#" class="gp_gallery_folder" name="gp_gallery_folder" rel="'.htmlspecialchars($temp).'"><span class="folder"></span>../</a>';
		}

		foreach($folders as $folder){
			if( $dir_piece == '/' ){
				$new_dir = '/'.$folder;
			}else{
				$new_dir = $dir_piece.'/'.$folder;
			}
			echo '<a href="#" class="gp_gallery_folder" name="gp_gallery_folder" rel="'.htmlspecialchars($new_dir).'"><span class="folder"></span>'.$folder.'</a>';
		}
		echo '</div>';
		echo '</div>';


		//add all images
		if( $image_count > 0 ){
			echo '<a href="#" name="gp_gallery_add_all" class="ckeditor_control full_width">'.$langmessage['Add All Images'].'</a>';
		}

		if( $dir_piece != '/' ){

			echo '<form action="'.common::GetUrl('Admin_Uploaded').'" method="post"  enctype="multipart/form-data" class="gp_upload_form" id="gp_upload_form">';
			admin_uploaded::Max_File_Size();
			echo '<a href="#" class="ckeditor_control full_width">'.$langmessage['upload_files'].'</a>';
			echo '<div class="gp_object_wrapper">';
			echo '<input type="file" name="userfiles[]" class="file" />';

			echo '<input type="hidden" name="file_cmd" value="inline_upload" />';
			echo '<input type="hidden" name="output" value="gallery" />';
			echo '<input type="hidden" name="dir" value="'.$dir_piece.'" />';
			echo '</div>';
			echo '</form>';
		}

		echo '</div>';

		echo $avail_imgs;

		$content = ob_get_clean();

		$page->ajaxReplace[] = array('gp_gallery_images','',$content);

	}

	/**
	 * @static
	 */
	function ShowFile_Gallery($dir_piece,$file,$isThumbDir){
		global $langmessage;

		if( !admin_uploaded::IsImg($file) ){
			return false;
		}

		//for gallery editing
		$fileUrl = common::GetDir('/data/_uploaded'.$dir_piece.'/'.$file);

		if( $isThumbDir ){
			$thumb = ' <img src="'.$fileUrl.'" alt="" />';
		}else{
			$thumb = ' <img src="'.common::GetDir('/data/_uploaded/image/thumbnails'.$dir_piece.'/'.$file.'.jpg').'" alt="" />';
		}

		$query_string = 'file_cmd=delete&show=inline&file='.urlencode($file).'&dir='.urlencode($dir_piece);

		return '<span class="expand_child">'
				. '<a href="'.$fileUrl.'" name="gp_gallery_add" rel="'.$fileUrl.'">'
				. $thumb
				. '</a>'
				. common::Link('Admin_Uploaded','',$query_string,' class="delete gpconfirm" name="gpajax" title="'.$langmessage['delete_confirm'].'"','delete')
				. '</span>';
	}



	function InlineResponse($status,$message){
		echo '<div>';
		echo '<textarea class="status">';
		echo htmlspecialchars($status);
		echo '</textarea>';
		echo '<textarea class="message">';
		echo htmlspecialchars($message);
		echo '</textarea>';
		echo '</div>';
		die();
	}

	function UploadFiles(){
		global $langmessage;

		$uploadedList = array();
		$failedList = array();

		if( !isset($_FILES['userfiles']) ){
			message($langmessage['OOPS']);
			return;
		}

		foreach($_FILES['userfiles']['name'] as $key => $name){
			if( empty($name) ){
				continue;
			}

			$uploaded = $this->UploadFile($key);
			$this->CleanTemporary();

			if( $uploaded !== false ){
				$uploadedList[] = $uploaded;
				gpPlugin::Action('FileUploaded',$uploaded);
			}
		}

		if( count($uploadedList) ){
			message($langmessage['file_uploaded'], implode(', ',$uploadedList) );
		}

		if( count($this->errorMessages) > 0 ){
			foreach($this->errorMessages as $message ){
				message($message);
			}
		}

	}

	function UploadFile($key){
		global $langmessage,$config;

		$this->UploadPrep();

		$fName = $_FILES['userfiles']['name'][$key];

		switch( (int)$_FILES['userfiles']['error'][$key]){

			case UPLOAD_ERR_OK:
			break;

			case UPLOAD_ERR_FORM_SIZE:
			case UPLOAD_ERR_INI_SIZE:
				$this->errorMessages[] = sprintf($langmessage['upload_error_size'],$this->ReadableMax() );
			return false;

			case UPLOAD_ERR_NO_FILE:
			case UPLOAD_ERR_PARTIAL:
				$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR_PARTIAL'], $fName);
			return false;

			case UPLOAD_ERR_NO_TMP_DIR:
				$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'].' (1)', $fName);
				//trigger_error('Missing a temporary folder for file uploads.');
			return false;

			case UPLOAD_ERR_CANT_WRITE:
				$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'].' (2)', $fName);
				//trigger_error('PHP couldn\'t write to the temporary directory: '.$fName);
			return false;

			case UPLOAD_ERR_EXTENSION:
				$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'].' (3)', $fName);
				//trigger_error('File upload stopped by extension: '.$fName);
			return false;
		}


		$upload_moved = false;
		$fName = $this->SanitizeName($fName);
		$from = $_FILES['userfiles']['tmp_name'][$key];

		if( !$this->UploadCompressed( $from, $fName, $upload_moved ) ){
			return false;
		}

		$fName = $this->WindowsName($fName);

		$to = $this->FixRepeatNames($fName);

		$thumbPath = $this->currentDir_Thumb.'/'.$fName.'.jpg';

		if( $upload_moved ){
			if( !rename($from,$to) ){
				$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'].' (Rename Failed from '.$to.')', $fName);
				return false;
			}
		}elseif( !move_uploaded_file($from,$to) ){
			$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'].' (Move Upload Failed)', $fName);
			return false;
		}

		@chmod( $to, 0666 );

		//for images
		$file_type = admin_uploaded::GetFileType($fName);
		if( isset($this->imgTypes[$file_type]) && function_exists('imagetypes') ){
			includeFile('admin/tool_thumbnails.php');

			//check the image size
			thumbnail::CheckArea($to,$config['maximgarea']);

			//create thumbnail
			$thumbPath = $this->currentDir_Thumb.'/'.$fName.'.jpg';
			gpFiles::CheckDir($this->currentDir_Thumb);
			thumbnail::createSquare($to,$thumbPath,100);
		}


		return $fName;
	}

	function FixRepeatNames(&$name){

		$name_parts = explode('.',$name);
		$file_type = array_pop($name_parts);
		$temp_name = implode('.',$name_parts);

		$num = 0;
		$name = $temp_name.'.'.$file_type;
		$to = $this->currentDir.'/'.$name;
		while( file_exists($to) ){
			$name = $temp_name.'_'.$num.'.'.$file_type;
			$to = $this->currentDir.'/'.$name;
			$num++;
		}

		return $to;
	}


	/**
	 * Try to fix file uploads for Windows
	 * Windows systems don't like long names: MAX_PATH of 260 http://msdn.microsoft.com/en-us/library/aa365247.aspx
	 */
	function WindowsName($name){

		$name_parts = explode('.',$name);
		$file_type = array_pop($name_parts);
		$temp_name = implode('.',$name_parts);

		$server_software =& $_SERVER['SERVER_SOFTWARE'];
		$server_software = strtolower($server_software);
		if( strpos($server_software,'win') === false ){
			return $name;
		}

		if( isset($this->imgTypes[$file_type]) && function_exists('imagetypes') ){
			$max_len = 260 - strlen($this->currentDir_Thumb);
		}else{
			$max_len = 260 - strlen($this->currentDir);
		}

		// adjust a minimum of 8 for _#.jpg postfix, / and . characters
		$max_len -= (strlen($file_type) + 20);

		if( strlen($temp_name) > $max_len ){
			$temp_name = substr($temp_name,0,$max_len);
		}

		return $temp_name.'.'.$file_type;
	}




	/**
	 * Save a compressed copy of the uploaded file
	 *
	 */
	function UploadCompressed( &$from, &$fName, &$upload_moved ){
		global $config, $dataDir, $langmessage;


		//check file type
		$file_type = admin_uploaded::GetFileType($fName);

		if( isset($config['check_uploads']) && $config['check_uploads'] === false ){
			return true;
		}

		if( in_array( $file_type, $this->AllowedExtensions ) ){
			return true;
		}

		$upload_moved = true;
		@ini_set('memory_limit', '256M');
		includeFile('thirdparty/ArchiveTar/Tar.php');


		//first move the file to a temporary folder
		//some installations don't like working with files in the default tmp folder
		do{
			$this->temp_folder = $dataDir.'/data/_temp/'.rand(1000,9000);
		}while( file_exists($this->temp_folder) );

		gpFiles::CheckDir($this->temp_folder,false);
		$temp_file = $this->temp_folder.'/'.$fName;
		$this->temp_files[] = $temp_file;

		if( !move_uploaded_file($from,$temp_file) ){
			$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'].' (UC1)', $fName);
			return false;
		}

		//prepare file names that may be used
		//replace . with underscore for security
		$fName = str_replace('.','_',$fName);
		$tar_name = $fName.'.tar';
		$tgz_name = $fName.'.tgz';
		$tbz_name = $fName.'.tar.bz';

		//create a .tar archive of the file in the same folder
		$tar_path = $temp_file.'.tar';

		$this->temp_files[] = $tar_path;
		$tar_object = new Archive_Tar($tar_path);
		$files = array($temp_file);

		if( !$tar_object->createModify($files, '', $this->temp_folder) ){
			$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'].' (CM1)', $fName);
			return false;
		}

		$fName = $tar_name;
		$from = $tar_path;

		//compress if available, try gz first
		if( function_exists('gzopen') ){

			$compress_path = $temp_file.'.tgz';
			$this->temp_files[] = $compress_path;

			//gz compress the tar
			$gz_handle = @gzopen($compress_path, 'wb9');
			if( $gz_handle ){
				if( @gzwrite( $gz_handle, file_get_contents($tar_path)) ){
					@gzclose($gz_handle);
					$fName = $tgz_name;
					$from = $compress_path;
					//return true;
				}
			}
		}

		//if gz isn't available or doesn't work, try bz
		if( function_exists('bzopen') ){

			$compress_path = $temp_file.'.tbz';
			$this->temp_files[] = $compress_path;

			//gz compress the tar
			$bz_handle = @bzopen($compress_path, 'w');
			if( $bz_handle ){
				if( @bzwrite( $bz_handle, file_get_contents($tar_path)) ){
					@bzclose($bz_handle);
					$fName = $tbz_name;
					$from = $compress_path;
					return true;
				}
			}
		}

		return true;
	}

	/**
	 * Clean up temporary file and folder if they exist
	 * Should be called after every instance of UploadFile()
	 */
	function CleanTemporary(){

		if( empty($this->temp_folder) || !file_exists($this->temp_folder) ){
			return;
		}

		if( count($this->temp_files) > 0 ){
			foreach($this->temp_files as $file){
				if( file_exists($file) ){
					unlink($file);
				}
			}
		}
		rmdir($this->temp_folder);
	}



	// Do a cleanup of the file name to avoid possible problems
	function SanitizeName( $sname ){
		global $config;

		$sname = stripslashes( $sname ) ;

		// Replace dots in the name with underscores (only one dot can be there... security issue).
		if( $config['check_uploads'] ){
			$sname = preg_replace( '/\\.(?![^.]*$)/', '_', $sname );
		}

		// Remove \ / | : ? * " < >
		return preg_replace( '/\\\\|\\/|\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]/u', '_', $sname ) ;
	}

	function DeleteConfirmed(){
		global $langmessage,$page;

		if( $this->isThumbDir ){
			return false;
		}

		if( !common::verify_nonce('delete') ){
			message($langmessage['OOPS'].' (Invalid Nonce)');
			return;
		}

		$file = $this->CheckFile();
		if( !$file ){
			return;
		}
		$fullPath = $this->currentDir.'/'.$file;
		$rel_path = common::GetDir('/data/_uploaded'.$this->subdir.'/'.$file);

		if( !$fullPath ){
			//check file messages
			return;
		}

		if( is_dir($fullPath) ){

			$files = gpFiles::ReadDir($fullPath,false);
			if( count($files) > 0 ){
				message($langmessage['dir_not_empty']);
				return false;
			}

			if( !gpFiles::RmDir($fullPath) ){
				message($langmessage['OOPS'].' (1)');
				return;
			}

		}else{
			if( !unlink($fullPath) ){
				message($langmessage['OOPS'].' (2)');
				return;
			}

			$thumb = $this->thumbFolder.$this->subdir.'/'.$file.'.jpg';
			if( file_exists($thumb) ){
				unlink($thumb);
			}

			$page->ajaxReplace[] = array('img_deleted','',$rel_path);
		}


		//message($langmessage['file_deleted']);
	}

	function CheckFile(){
		global $langmessage;

		if( empty($_REQUEST['file']) ){
			message($langmessage['OOPS'].'(2)');
			return false;
		}

		$file = $_REQUEST['file'];
		if( (strpos($file,'/') !== false ) || (strpos($file,'\\') !== false) ){
			message($langmessage['OOPS'].'(3)');
			return false;
		}
		$fullPath = $this->currentDir.'/'.$file;
		if( !file_exists($fullPath) ){
			message($langmessage['OOPS'].'(4)');
			return false;
		}

		if( strpos($fullPath,$this->baseDir) === false ){
			message($langmessage['OOPS'].' (5)');
			return false;
		}
		return $file;
		//return $fullPath;
	}


	//using because editing requires the file_cmd=edit for each link
	function BrowseLink($label,$folder=false,$args='',$attrs='',$nonce_action=false){

		$queryString = '';

		if( $folder !== false ){
			$queryString .= 'dir='.rawurlencode($folder);
		}
		if( !empty($args) ){
			$queryString .= '&'.$args;
		}

		return common::Link($this->browseString,$label,$queryString,$attrs,$nonce_action);
	}


	function ShowLocation(){
		global $langmessage;

		echo '<span class="location">';


		if( !empty($this->subdir) ){
			echo '<span>/</span>';
			echo $this->BrowseLink($langmessage['uploaded_files'],'','',' class="left" ');


			$dirs = str_replace('\\','/',$this->subdir);
			$dirs = trim($dirs,'/');
			$dirs = explode('/',$dirs);
			$current = '';
			foreach($dirs as $dir){
				$current .= '/'.$dir;
				echo '<span>/</span>';
				echo $this->BrowseLink($dir,$current);
			}
		}else{
			echo '<span>/</span>';
			echo $this->BrowseLink($langmessage['uploaded_files'],'');
		}

		echo '</span>';

	}


	function ShowPanel(){
		global $langmessage;

		if( !file_exists($this->currentDir) ){
			return;
		}

		echo '<div id="gp_file_browser_nav" class="browser_bar cf">';

			echo ' <div class="actions">';

				if( !empty($this->subdir) && !$this->isThumbDir ){

					//inline upload
					echo '<form action="'.common::GetUrl($this->browseString).'" method="post"  enctype="multipart/form-data" class="gp_upload_form" id="gp_upload_form">';
					admin_uploaded::Max_File_Size();
					echo '<input type="hidden" name="file_cmd" value="inline_upload" />';
					echo '<input type="hidden" name="dir" value="'.htmlspecialchars($this->subdir).'" />';

					$img = '<img src="'.common::GetDir('/include/imgs/add.png').'" height="16" width="16" alt=""/> ';
					echo '<a href="#">'.$img.$langmessage['upload_files'].'...</a>';

					echo '<div class="gp_object_wrapper">';
					echo '<input type="file" name="userfiles[]" class="file" />';
					echo '</div>';

					echo '</form>';

				}

				//create dir
				$img = '<img src="'.common::GetDir('/include/imgs/add.png').'" height="16" width="16" alt=""/> ';
				echo $this->BrowseLink($img.$langmessage['create_dir'].'...',$this->subdir,'file_cmd=new_dir',' name="admin_box" ');
			echo '</div>';

			$this->ShowLocation();

		echo '</div>';
		echo '<div id="gp_upload_queue"></div>';

		echo '<div class="display_options">';

		$options['browser_list'] = 'list';
		$options['browser_icons_small'] = 'icons';
		$options['browser_icons'] = 'tile';

		foreach($options as $option => $img){
			$class = $img;
			if( $GLOBALS['gpAdmin']['gpui_brdis'] == $option ){
				$class .= ' selected';
			}
			echo '<a href="#" name="browser_pref" rel="'.$option.'" class="'.$class.'"></a> ';
		}
		echo '</div>';

	}



	function ShowFolder(){
		global $langmessage;

		if( !file_exists($this->currentDir) ){
			return;
		}

		echo '<div class="'.$GLOBALS['gpAdmin']['gpui_brdis'].' browser_class">';

			//parent directory link
			if( !empty($this->subdir) ){
				echo "\n";
				$this->ListItemDiv();
				echo '<div class="gen_links">';
					$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" class="icon folder_up" /> ';
					echo $this->BrowseLink($img,dirname($this->subdir));
					echo '<div>';
					echo $this->BrowseLink('..',dirname($this->subdir));
					echo '</div>';
				echo '</div>';
				echo '</div>';
				$this->odd_even_count++;
			}

			$this->ShowFiles();
		echo '</div>';


		echo '<a id="allowed_types" class="browser_bar">';
		echo $langmessage['some_file_types'];
		echo '<span>';
		echo $langmessage['allowed_extension'];
		echo '<span>';
		natsort($this->AllowedExtensions);
		echo implode(', ',$this->AllowedExtensions);
		echo '</span>';
		echo '</span>';
		echo '</a>';

	}

	function ShowFiles(){

		$allFiles = gpFiles::ReadFolderAndFiles($this->currentDir);
		if( $allFiles === false ){
			return;
		}

		list($folders,$files) = $allFiles;

		foreach($folders as $folder){
			$this->ListItemDiv();
			echo '<div class="gen_links">';
				$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" class="icon folder" /> ';
				echo $this->BrowseLink($img,$this->subdir.'/'.$folder);
				echo '<div>';
				echo $this->BrowseLink($folder,$this->subdir.'/'.$folder);
				echo '</div>';

			echo '</div>';
			echo '<div class="more_links">';
				$this->File_Link_Right($folder,false); //delete
			echo '</div>';
			echo '</div>';
		}

		echo '<div id="gp_browser_files" class="cf">';
		$has_index = false;
		foreach($files as $file){
			if( $file == 'index.html'){
				$has_index = true;
				continue;
			}
			$this->ShowFile($file);
		}

		if( $has_index ){
			$this->ShowFile('index.html');
		}

		echo '</div>';

	}

	function ShowFile($file){

		$fileUrl = common::GetDir('/data/_uploaded'.$this->subdir.'/'.$file);
		$type = admin_uploaded::GetFileType($file);

		$is_img = false;
		if( isset($this->imgTypes[$type]) ){
			$is_img = true;
		}

		$this->ListItemDiv();

		echo '<input type="hidden" name="fileUrl" value="'.htmlspecialchars($fileUrl).'" />'; //for admin_browser.php/.js


		echo '<div class="gen_links">';

			$this->Link_File($file,$is_img,$fileUrl);

		echo '</div>';

		echo '<div class="more_links">';
			$this->File_Link_Right($file,$is_img,$fileUrl); //delete
		echo '</div>';

		echo '</div>';

	}

	function ListItemDiv(){
		static $i = 0;
		static $class = array(0=>'list_even',1=>'list_odd');
		echo '<div class="list_item expand_child '.$class[$i%2].'">';
		$i++;
	}


	function NewDirPrompt(){
		admin_uploaded::NewDirForm($this->browseString,$this->queryString,$this->subdir);
	}

	function NewDirForm($browse_string,$query_string,$subdir,$submit_class=''){
		global $langmessage;

		echo '<div class="inline_box">';
		$img = '<img src="'.common::GetDir('/include/imgs/folder.png').'" height="16" width="16" alt=""/> ';
		echo '<h2>'.$img.$langmessage['create_dir'].'</h2>';
		echo '<form action="'.common::GetUrl($browse_string,$query_string.'dir='.rawurlencode($subdir)).'" method="post"  >';
			echo '<p>';
			echo ' <input type="text" class="input" name="newdir" size="30" />';
			echo '</p>';
			echo '<p>';
			echo ' <input type="hidden" name="file_cmd" value="createdir" />';
			echo ' <input type="hidden" name="dir" value="'.htmlspecialchars($subdir).'" />';
			echo '<input type="submit" name="aaa" value="'.$langmessage['create_dir'].'" class="'.$submit_class.'"/>';
			echo ' <input type="submit" class="admin_box_close" name="" value="'.$langmessage['cancel'].'" />';
			echo '</p>';
		echo '</form>';
		echo '</div>';
	}

	function Link_File($file,$is_img,$fileUrl){

		if( !$is_img ){
			echo '<img src="'.common::GetDir('/include/imgs/files_100.png').'" height="100" width="100" alt="" class="icon" /> ';
			echo '<div>';
			echo '<a href="'.$fileUrl.'" target="_blank" title="'.$file.'">';
			echo $file;
			echo '</a>';
			echo '</div>';
			return;
		}


		echo '<a href="'.$fileUrl.'" name="gallery" rel="gallery_uploaded" title="'.$file.'">';
		if( !$this->isThumbDir ){
			echo ' <img src="'.common::GetDir('/data/_uploaded/image/thumbnails'.$this->subdir.'/'.$file.'.jpg').'" height="100" width="100" alt="" class="icon" />';
		}else{
			echo ' <img src="'.$fileUrl.'" height="100" width="100" alt="" class="icon" />';
		}
		echo '</a>';

		echo '<div>';
		echo '<a href="'.$fileUrl.'" name="gallery" rel="gallery_uploaded_2" title="'.$file.'">';
		echo $file;
		echo '</a>';
		echo '</div>';


	}




	function File_Link_Left($file,$is_img){

		if( $is_img ){
			echo '<a href="'.common::GetDir('/data/_uploaded'.$this->subdir.'/'.$file).'" name="gallery" rel="gallery_uploaded" title="'.$file.'">';
		}else{
			echo '<a href="'.common::GetDir('/data/_uploaded'.$this->subdir.'/'.$file).'" >';
		}
		echo $file;
		echo '</a>';
	}

	function File_Link_Right($file,$is_img,$img_url=false){
		global $langmessage;

		if( $is_img ){
			//
		}

		if( !$this->isThumbDir ){
			$label = '<img src="'.common::GetDir('/include/imgs/delete.png').'" alt="" height="16" width="16" /> ';
			$label .= '<span>'.$langmessage['delete'].'</span>';
			echo $this->BrowseLink($label,$this->subdir,'file_cmd=delete&file='.urlencode($file),' name="creq" class="gpconfirm" title="'.$langmessage['delete_confirm'].'"','delete');

		}else{
			echo '<div>&nbsp;</div>'; //for display
		}
	}


	/**
	 * Get the file extension for $file
	 * @static
	 * @param string $file The $file name or path
	 * @return string The extenstion of $file
	 */
	function GetFileType($file){
		$name_parts = explode('.',$file);
		$file_type = array_pop($name_parts);
		return strtolower($file_type);
	}

	/**
	 * Determines if the $file is an image based on the file extension
	 * @return bool
	 */
	function IsImg($file){
		$img_types = array('bmp'=>1,'png'=>1,'jpg'=>1,'jpeg'=>1,'gif'=>1,'tiff'=>1,'tif'=>1);

		$type = admin_uploaded::GetFileType($file);

		return isset($img_types[$type]);
	}


}

