<?php
defined('is_running') or die('Not an entry point...');

includeFile('admin/admin_tools.php');

class admin_display extends display{
	var $pagetype = 'admin_display';
	var $requested = false;

	var $editable_content = false;
	var $editable_details = false;

	var $show_admin_content = true;
	var $non_admin_content = '';

	function admin_display($title){

		$this->requested = $title;
		$this->title = $title;

		$scripts = admin_tools::AdminScripts();
		if( isset($scripts[$title]) && isset($scripts[$title]['label']) ){
			$this->label = $scripts[$title]['label'];
		}else{
			$this->label = str_replace('_',' ',$title);
		}

		$this->head .= "\n".'<meta name="robots" content="noindex,nofollow" />';
	}

	function RunScript(){
		global $page;

		$this->SetTheme();

		ob_start();
		$this->RunAdminScript();
		$this->contentBuffer = ob_get_clean();
	}


	//called by templates
	function GetContent(){

		$this->GetGpxContent();

		if( !empty($this->non_admin_content) ){
			echo '<div class="filetype-text cf">';
			//echo '<div id="gpx_content" class="filetype-text">'; //id="gpx_content" conflicts with admin content
			echo $this->non_admin_content;
			echo '</div>';
		}

		echo '<div id="gpAfterContent">';
		gpOutput::Get('AfterContent');
		gpPlugin::Action('GetContent_After');
		echo '</div>';
	}

	function GetGpxContent(){

		if( !empty($this->show_admin_content) ){
			echo '<div id="gpx_content">';
			echo '<div id="admincontent">';
			admin_tools::AdminContentPanel();

			if( common::LoggedIn() ){
				echo '<div id="admincontent_inner">';
				echo $this->contentBuffer;
				echo '</div>';
			}else{
				echo $this->contentBuffer;
			}

			echo '</div>';
			echo '</div>';
		}

	}


	function RunAdminScript(){
		global $dataDir,$langmessage;


		if( !common::LoggedIn() ){
			$cmd = common::GetCommand();
			switch($cmd){
				case 'send_password';
					if( $this->SendPassword() ){
						$this->LoginForm();
					}else{
						$this->FogottenPassword();
					}
				break;

				case 'forgotten':
					$this->FogottenPassword();
				break;
				default:
					$this->LoginForm();
				break;
			}

			return;
		}


		$scriptinfo = false;
		$scripts = admin_tools::AdminScripts();
		if( isset($scripts[$this->requested]) ){
			$scriptinfo = $scripts[$this->requested];

			if( admin_tools::HasPermission($this->requested) ){
				if( isset($scriptinfo['addon']) ){
					gpPlugin::SetDataFolder($scriptinfo['addon']);
				}
				admin_display::OrganizeFrequentScripts($this->requested);


				if( isset($scriptinfo['script']) ){
					require($dataDir.$scriptinfo['script']);
				}
				if( isset($scriptinfo['class']) ){
					new $scriptinfo['class']();
				}

				gpPlugin::ClearDataFolder();

				return;
			}else{
				message($langmessage['not_permitted']);
			}
		}elseif( count($scripts) > 0 ){

			//check case
			$case_check = array_keys($scripts);
			$case_check = array_combine($case_check, $case_check);
			$case_check = array_change_key_case( $case_check, CASE_LOWER );

			$lower = strtolower($this->requested);
			if( isset($case_check[$lower]) ){
				$location = common::GetUrl($case_check[$lower]);
				message($location);
				common::status_header(301,'Moved Permanently');
				header('Location: '.$location);
				die();
			}
		}


		//these are here because they should be available to everyone
		switch($this->requested){
			case 'Admin_Browser':
				require($dataDir.'/include/admin/admin_browser.php');
				new admin_browser();
			return;

			case 'Admin_Preferences':
				require($dataDir.'/include/admin/admin_preferences.php');
				new admin_preferences();
			return;

			case 'Admin_About':
				require($dataDir.'/include/admin/admin_about.php');
				new admin_about();
			return;
		}


		$this->AdminPanel();
	}



	function AdminPanel(){
		global $langmessage;


		$cmd = common::GetCommand();
		switch($cmd){
			case 'embededcheck':
				$this->EmbededCheck();
			return;
		}


		echo '<div id="adminlinks2" class="cf">';
		admin_tools::AdminPanelLinks(false);

		//resources
		echo '<div class="panelgroup">';
		echo '<span class="icon_page_gear"><span>'.$langmessage['resources'].' (gpEasy.com)</span></span>';
		echo '<ul>';
		echo '<li><a href="'.$GLOBALS['addonBrowsePath'].'/Special_Addon_Plugins" name="remote">Download Plugins</a></li>';
		echo '<li><a href="'.$GLOBALS['addonBrowsePath'].'/Special_Addon_Themes" name="remote">Download Themes</a></li>';
		echo '<li><a href="http://gpeasy.com">Support Forum</a></li>';
		echo '<li><a href="http://gpeasy.com/Special_Services">Service Providers</a></li>';
		echo '<li><a href="http://gpeasy.com">Official gpEasy Site</a></li>';
		echo '<li><a href="https://sourceforge.net/tracker/?group_id=264307&amp;atid=1127698">Report A Bug (sf.net)</a></li>';
		echo '</ul>';
		echo '</div>';

		
		echo '</div>';


		echo '<div id="adminfooter">';
		echo '<ul>';
		echo '<li>';
		echo 'WYSIWYG editor by  <a href="http://ckeditor.com/">CKEditor.net</a>';
		echo '</li>';
		echo '<li>';
		echo 'Galleries made possible by <a href="http://colorpowered.com/colorbox/">ColorBox</a>';
		echo '</li>';
		echo '<li>';
		echo 'Icons by <a href="http://www.famfamfam.com/">famfamfam.com</a>';
		echo '</li>';
		echo '</ul>';
		echo '</div>';
	}


	function EmbededCheck(){
		includeFile('install/update_class.php');
		new update_class('embededcheck');
	}


	function SendPassword(){
		global $langmessage,$dataDir,$gp_mailer;

		includeFile('tool/email_mailer.php');
		include($dataDir.'/data/_site/users.php');

		$username = $_POST['username'];

		if( !isset($users[$username]) ){
			message($langmessage['OOPS']);
			return false;
		}

		$userinfo = $users[$username];



		if( empty($userinfo['email']) ){
			message($langmessage['no_email_provided']);
			return false;
		}

		$passwordChars = str_repeat('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',3);
		$newpass = str_shuffle($passwordChars);
		$newpass = substr($newpass,0,8);


		$users[$username]['newpass'] = common::hash(trim($newpass));
		if( !gpFiles::SaveArray($dataDir.'/data/_site/users.php','users',$users) ){
			message($langmessage['OOPS']);
			return false;
		}

		if( isset($_SERVER['HTTP_HOST']) ){
			$server = $_SERVER['HTTP_HOST'];
		}else{
			$server = $_SERVER['SERVER_NAME'];
		}

		$link = common::AbsoluteLink('Admin_Main',$langmessage['login']);
		$message = sprintf($langmessage['passwordremindertext'],$server,$link,$username,$newpass);

		if( $gp_mailer->SendEmail($userinfo['email'], $langmessage['new_password'], $message) ){
			list($namepart,$sitepart) = explode('@',$userinfo['email']);
			$showemail = substr($namepart,0,3).'...@'.$sitepart;
			message(sprintf($langmessage['password_sent'],$username,$showemail));
			return true;
		}


		message($langmessage['OOPS'].' (Email not sent)');

		return false;
	}


	function FogottenPassword(){
		global $langmessage;
		$_POST += array('username'=>'');

		echo '<form class="loginform" action="'.common::GetUrl('Admin_Main').'" method="post">';
		echo '<h2>'.$langmessage['send_password'].'</h2>';
		echo '<p>';
			echo '<label>'.$langmessage['username'].'</label>';
			echo '<input type="text" name="username" value="'.htmlspecialchars($_POST['username']).'" />';
			echo '</p>';

			echo '<p>';
			echo '<input type="hidden" name="cmd" value="send_password" />';
			echo '<input type="submit" name="aa" value="'.$langmessage['send_password'].'" />';
			echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
			echo '</p>';
	}

	function LoginForm(){
		global $langmessage,$gp_index,$page;


		$page->head .= "\n<script type=\"text/javascript\">var IE_LT_8 = false;</script><!--[if lt IE 8]>\n<script type=\"text/javascript\">IE_LT_8=true;</script>\n<![endif]-->";
		$page->head_js[] = '/include/js/login.js';
		$page->head_js[] = '/include/js/md5_sha.js';

		$page->css_admin[] = '/include/css/login.css';


		$_POST += array('username'=>'');
		$page->admin_js = true;
		includeFile('tool/sessions.php');
		gpsession::cookie('g',2);


		$action = 'Admin_Main';
		if( isset($_REQUEST['file']) && isset($gp_index[$_REQUEST['file']]) ){
			$action = $_REQUEST['file'];
		}


		echo '<div style="display:none;" class="req_script" id="login_container">';

		echo '<div id="browser_warning" style="display:none">';
		echo '<div><b>'.$langmessage['Browser Warning'].'</b></div>';
		echo '<p>';
		echo $langmessage['Browser !Supported'];
		echo '</p>';
		echo '<p>';
		echo '<a href="http://www.mozilla.com/">Firefox</a>';
		echo '<a href="http://www.google.com/chrome">Chrome</a>';
		echo '<a href="http://www.apple.com/safari">Safari</a>';
		echo '<a href="http://www.microsoft.com/windows/internet-explorer/default.aspx">Explorer</a>';

		echo '</p>';
		echo'</div>';

		echo '<div id="loginform">';
		echo '<b>'.$langmessage['LOGIN_REQUIRED'].'</b>';

			echo '<form action="'.common::GetUrl($action).'" method="post" id="login_form">';

			echo '<div>';
			echo '<input type="hidden" name="cmd" value="login" />';
			if( isset($_REQUEST['file']) && isset($gp_index[$_REQUEST['file']]) ){
				echo '<input type="hidden" name="file" value="'.htmlspecialchars($_REQUEST['file']).'" />';
			}
			echo '<input type="hidden" name="login_nonce" value="'.htmlspecialchars(common::new_nonce('login_nonce',true,300)).'" />';
			echo '</div>';

			echo '<p>';
			echo '<label>'.$langmessage['username'].'</label>';
			echo '<input type="text" class="login_text" name="username" value="'.htmlspecialchars($_POST['username']).'" />';
			echo '<input type="hidden" name="user_sha" value="" />';
			echo '</p>';

			echo '<p>';
			echo '<label>'.$langmessage['password'].'</label>';
			echo '<input type="password" class="login_text password" name="password" value="" />';
			echo '<input type="hidden" name="pass_md5" value="" />';
			echo '<input type="hidden" name="pass_sha" value="" />';
			echo '</p>';

			echo '<p>';
			echo '<input type="submit" class="login_submit" name="aa" value="'.$langmessage['login'].'" />';
			echo '</p>';

			echo '<p>';
			echo '<label>';
			echo '<input type="checkbox" name="remember" '.$this->checked('remember').'/> ';
			echo '<span>'.$langmessage['remember_me'].'</span>';
			echo '</label> ';

			echo '<label>';
			echo '<input type="checkbox" name="encrypted" '.$this->checked('encrypted').'/> ';
			echo '<span>'.$langmessage['send_encrypted'].'</span>';
			echo '</label>';
			echo '</p>';

			echo '</form>';
		echo '</div>';

		echo '</div>';

		echo '<div class="without_script" id="javascript_warning">';
		echo '<p><b>'.$langmessage['JAVASCRIPT_REQ'].'</b></p>';
		echo '<p>';
		echo $langmessage['INCOMPAT_BROWSER'];
		echo ' ';
		echo $langmessage['MODERN_BROWSER'];
		echo '</p>';
		echo '</div>';


		


	}

	function Checked($name){

		if( strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST' )
			return ' checked="checked" ';

		if( !isset($_POST[$name]) )
			return '';

		return ' checked="checked" ';
	}






	function OrganizeFrequentScripts($page){
		global $gpAdmin;

		if( !isset($gpAdmin['freq_scripts']) ){
			$gpAdmin['freq_scripts'] = array();
		}
		if( !isset($gpAdmin['freq_scripts'][$page]) ){
			$gpAdmin['freq_scripts'][$page] = 0;
		}else{
			$gpAdmin['freq_scripts'][$page]++;
			if( $gpAdmin['freq_scripts'][$page] >= 10 ){
				admin_display::CleanFrequentScripts();
			}
		}

		arsort($gpAdmin['freq_scripts']);
	}

	function CleanFrequentScripts(){
		global $gpAdmin;

		//reduce to length of 5;
		$count = count($gpAdmin['freq_scripts']);
		if( $count > 3 ){
			for($i=0;$i < ($count - 5);$i++){
				array_pop($gpAdmin['freq_scripts']);
			}
		}

		//reduce the hit count on each of the top five
		$min_value = end($gpAdmin['freq_scripts']);
		foreach($gpAdmin['freq_scripts'] as $page => $hits){
			$gpAdmin['freq_scripts'][$page] = $hits - $min_value;
		}
	}


}
