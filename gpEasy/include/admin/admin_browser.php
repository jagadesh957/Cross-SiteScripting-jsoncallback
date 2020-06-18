<?php
defined("is_running") or die("Not an entry point...");


includeFile('admin/admin_uploaded.php');


class admin_browser extends admin_uploaded{

	var $browseString = 'Admin_Browser';


	function admin_browser(){
		global $page;

		$page->get_theme_css = false;

		$_REQUEST += array('gpreq' => 'body'); //force showing only the body as a complete html document
		$this->Init();
		$this->PrepHead();

		$this->do_admin_uploaded();

	}

	function PrepHead(){
		global $config,$page;
		common::AddColorBox();

		$page->head_js[] = '/include/js/browser.js';

		$page->head .= '<style type="text/css">';
		$page->head .= 'html,body{padding:0;margin:0;background-color:#fff !important;background-image:none !important;border:0 none !important;}';
		$page->head .= '#gp_admin_html{padding:20px !important;}';
		$page->head .= '</style>';
	}

	function Link_File($file,$is_img,$fileUrl){

		$rel = '';
		if( $is_img ){
			$rel = 'image';
		}

		echo '<a href="'.$fileUrl.'" name="select" rel="'.$rel.'">';
		echo '<input type="hidden" name="fileUrl" value="'.htmlspecialchars($fileUrl).'" />';

		if( $is_img ){
			if( !$this->isThumbDir ){
				echo ' <img src="'.common::GetDir('/data/_uploaded/image/thumbnails'.$this->subdir.'/'.$file.'.jpg').'" height="100" width="100" class="icon"  alt=""/>';
			}else{
				echo ' <img src="'.$fileUrl.'" height="100" width="100" class="icon" alt=""/>';
			}
		}else{
			echo '<img src="'.common::GetDir('/include/imgs/files_100.png').'" height="100" width="100" alt="" class="icon" /> ';
		}
		echo '</a>';

		echo '<div>';
		echo '<a href="'.$fileUrl.'" name="select" rel="'.$rel.'">';
		echo '<input type="hidden" name="fileUrl" value="'.htmlspecialchars($fileUrl).'" />';
		echo $file;
		echo '</a>';
		echo '</div>';

	}

	function File_Link_Right($file,$is_img,$img_url=false){
		global $langmessage;

		if( $is_img ){
			echo '<a href="'.$img_url.'" name="gallery" rel="gallery_uploaded" title="'.$file.'">';
			echo '<img src="'.common::GetDir('/include/imgs/page_white_magnify.png').'" alt="" height="16" width="16" /> ';
			echo '<span>'.$langmessage['preview'].'</span>';
			echo '</a>';
		}

		parent::File_Link_Right($file,$is_img,$img_url);
	}


}
