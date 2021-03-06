<?php
defined('is_running') or die('Not an entry point...');

class admin_extra{

	function admin_extra(){
		global $langmessage;

		$cmd = common::GetCommand();

		$show = true;
		switch($cmd){

			case 'delete_confirmed';
				$this->DeleteArea_Confirmed();
			break;
			case 'delete';
				$this->DeleteArea();
				$show = false;
			break;

			case 'save':
				if( $this->SaveExtra() ){
					break;
				}
			case 'edit':
				if( $this->EditExtra() ){
					$show = false;
				}
			break;

			case 'rawcontent':
				$this->RawContent();
			break;

			case $langmessage['cancel']:
				$this->Redirect();
			break;


			case 'inlineedit':
				$this->InlineEdit();
			die();

		}

		if( $show ){
			$this->ShowExtras();
		}
	}

	function InlineEdit(){
		global $dataDir;

		$title = gpFiles::CleanTitle($_REQUEST['file']);
		if( empty($title) ){
			echo 'false';
			return false;
		}

		$data = array();
		$data['type'] = 'text';
		$data['content'] = '';

		$file = $dataDir.'/data/_extra/'.$title.'.php';
		$content = '';

		if( file_exists($file) ){
			ob_start();
			include($file);
			$data['content'] = ob_get_clean();
		}

		includeFile('tool/ajax.php');
		gpAjax::InlineEdit($data);

	}

	function RawContent(){
		global $page,$langmessage,$dataDir;

		//for ajax responses
		$page->ajaxReplace = array();


		$title = gpFiles::CleanTitle($_REQUEST['file']);
		if( empty($title) ){
			message($langmessage['OOPS']);
			return false;
		}

		$file = $dataDir.'/data/_extra/'.$title.'.php';
		$content = '';

		if( file_exists($file) ){
			ob_start();
			include($file);
			$content = ob_get_clean();
		}

		$page->ajaxReplace[] = array('rawcontent','',$content);
	}

	function DeleteArea(){
		global $langmessage,$page;
		$page->ajaxReplace = array();

		$file =& $_GET['file'];
		if( !$this->ExtraExists($file) ){
			message($langmessage['OOPS']);
			return;
		}

		ob_start();
		echo '<div class="inline_box">';
		echo '<form action="'.common::GetUrl('Admin_Extra').'" method="post">';
		echo '<input type="hidden" name="cmd" value="delete_confirmed" />';
		echo sprintf($langmessage['generic_delete_confirm'],'<i>'.htmlspecialchars($file).'</i>');
		echo ' <input type="submit" name="" value="'.$langmessage['continue'].'" />';
		echo ' <input type="hidden" name="file" value="'.htmlspecialchars($file).'" />';
		echo ' <input type="submit" value="'.$langmessage['cancel'].'" class="admin_box_close" /> ';

		echo '</form>';
		echo '</div>';
		$content = ob_get_clean();

		$page->ajaxReplace[] = array('admin_box_data','',$content);

	}

	function DeleteArea_Confirmed(){
		global $langmessage;


		$title =& $_POST['file'];
		$file = $this->ExtraExists($title);
		if( !$file ){
			message($langmessage['OOPS']);
			return;
		}

		if( unlink($file) ){
			message($langmessage['SAVED']);
		}else{
			message($langmessage['OOPS']);
		}
	}


	function ExtraExists($file){
		global $dataDir;

		$path = $dataDir.'/data/_extra/'.$file.'.php';
		if( !file_exists($path) ){
			return false;
		}
		return $path;
	}


	function ShowExtras(){
		global $dataDir,$langmessage;

		$extrasFolder = $dataDir.'/data/_extra';
		$files = gpFiles::ReadDir($extrasFolder);
		asort($files);

		echo '<h2>'.$langmessage['theme_content'].'</h2>';
		echo '<table class="bordered" style="width:100%">';
		echo '<tr>';
			echo '<th>';
			echo 'Area';
			echo '</th>';
			echo '<th>';
			echo '&nbsp;';
			echo '</th>';
			echo '<th>';
			echo $langmessage['options'];
			echo '</th>';
			echo '</tr>';

		foreach($files as $file){
			$extraName = $file;
			echo '<tr>';
				echo '<td style="white-space:nowrap">';
				echo str_replace('_',' ',$extraName);
				echo '</td>';
				echo '<td>"<span class="admin_note">';
				$full_path = $dataDir.'/data/_extra/'.$file.'.php';
				$contents = file_get_contents($full_path);
				$contents = strip_tags($contents);
				echo substr($contents,0,50);
				echo '</span>..."</td>';
				echo '<td style="white-space:nowrap">';
				echo common::Link('Admin_Extra',$langmessage['edit'],'cmd=edit&file='.$file);
				echo ' &nbsp; ';
				echo common::Link('Admin_Extra',$langmessage['delete'],'cmd=delete&file='.$file,'name="gpajax"');
				echo '</td>';
				echo '</tr>';
		}

		echo '</table>';

		echo '<p>';
		echo '<form action="'.common::GetUrl('Admin_Extra').'" method="post">';
		echo '<input type="hidden" name="cmd" value="edit" />';
		echo '<input type="text" name="file" value="" size="15" />';
		echo '<input type="submit" name="" value="'.$langmessage['Add New Area'].'" />';
		echo '</form>';
		echo '</p>';


	}


	function EditExtra(){
		global $langmessage,$dataDir;

		$title = gpFiles::CleanTitle($_REQUEST['file']);
		if( empty($title) ){
			message($langmessage['OOPS']);
			return false;
		}

		$file = $dataDir.'/data/_extra/'.$title.'.php';
		$content = '';

		if( file_exists($file) ){
			ob_start();
			include($file);
			$content = ob_get_clean();
		}

		echo '<form action="'.common::GetUrl('Admin_Extra','file='.$title).'" method="post">';
		echo '<h2>';
		echo common::Link('Admin_Extra',$langmessage['theme_content']);
		echo ' &gt; '.str_replace('_',' ',$title).'</h2>';
		echo '<input type="hidden" name="cmd" value="save" />';
		if( !empty($_REQUEST['return']) ){
			echo '<input type="hidden" name="return" value="'.htmlspecialchars($_REQUEST['return']).'" />';
		}

		common::UseCK($content);

		echo '<input type="submit" name="" value="'.$langmessage['save'].'" />';
		echo '<input type="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
		echo '</form>';
		return true;
	}

	function SaveExtra(){
		global $langmessage, $dataDir,$page;

		//for ajax responses
		$page->ajaxReplace = array();


		if( empty($_REQUEST['file']) ){
			message($langmessage['OOPS']);
			return false;
		}

		$title = gpFiles::CleanTitle($_REQUEST['file']);
		$file = $dataDir.'/data/_extra/'.$title.'.php';
		$text =& $_POST['gpcontent'];
		gpFiles::cleanText($text);


		if( !gpFiles::SaveFile($file,$text) ){
			message($langmessage['OOPS']);
			$this->EditExtra();
			return false;
		}

		$this->Redirect();

		$page->ajaxReplace[] = array('ck_saved','','');
		message($langmessage['SAVED']);
		return true;
	}

	function Redirect(){
		if( !empty($_POST['return']) ){
			$return = $_POST['return'];
			$return = str_replace('cmd=','x=',$return);
			header('Location: '.common::GetUrl($_POST['return'],false));
			die();
		}
		return false;
	}
}
