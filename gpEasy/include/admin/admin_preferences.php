<?php
defined("is_running") or die("Not an entry point...");

require_once($GLOBALS['rootDir'].'/include/admin/admin_users.php');


class admin_preferences extends admin_users{
	var $username;


	function admin_preferences(){
		global $gpAdmin,$langmessage,$page;

		//only need to return messages if it's ajax request
		$page->ajaxReplace = array();


		$this->GetUsers();
		$this->username = $gpAdmin['username'];
		if( !isset($this->users[$this->username]) ){
			message($langmessage['OOPS']);
			return;
		}
		$this->user_info =  $this->users[$this->username];
		$cmd = common::GetCommand();

		switch($cmd){
			case 'changeprefs':
				$this->DoChange();
			break;
		}

		$this->Form();

	}

	function DoChange(){
		global $gpAdmin;

		$this->ChangeEmail();
		$this->ChangePass();

		gpsession::SetGPUI();

		$this->SaveUserFile();

	}

	function ChangeEmail(){
		global $langmessage;

		if( empty($_POST['email']) ){
			$this->users[$this->username]['email'] = '';
			return;
		}

		if( $this->ValidEmail($_POST['email']) ){
			$this->users[$this->username]['email'] = $_POST['email'];
		}else{
			message($langmessage['invalid_email']);
		}

	}

	function ValidEmail($email){
		return (bool)preg_match('/^[^@]+@[^@]+\.[^@]+$/', $email);
	}

	function ChangePass(){
		global $langmessage;


		$fields = 0;
		if( !empty($_POST['oldpassword']) ){
			$fields++;
		}
		if( !empty($_POST['password']) ){
			$fields++;
		}
		if( !empty($_POST['password1']) ){
			$fields++;
		}
		if( $fields < 2 ){
			return; //assume user didn't try to reset password
		}


		//see also admin_users for password checking
		if( !$this->CheckPasswords() ){
			return false;
		}

		$oldpass = common::hash(trim($_POST['oldpassword']));
		if( $this->user_info['password'] != $oldpass ){
			message($langmessage['couldnt_reset_pass']);
			return false;
		}

		$this->users[$this->username]['password'] = common::hash(trim($_POST['password']));
	}


	function Form(){
		global $langmessage, $gpAdmin;

		if( $_SERVER['REQUEST_METHOD'] == 'POST'){
			$array = $_POST;
		}else{
			$array = $this->user_info;
		}
		$array += array('email'=>'');


		echo '<h2>'.$langmessage['Preferences'].'</h2>';

		echo '<form action="'.common::GetUrl('Admin_Preferences').'" method="post">';
		echo '<div class="collapsible">';

		echo '<h4 class="head"><a href="#" name="collapsible">'.$langmessage['general_settings'].'</a></h4>';
		echo '<div>';
		echo '<table class="bordered">';

		echo '<tr>';
			echo '<td>';
			echo $langmessage['email_address'];
			echo '</td>';
			echo '<td>';
			echo '<input type="text" name="email" value="'.htmlspecialchars($array['email']).'" />';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo $langmessage['editable_bar'];
			echo '</td>';
			echo '<td>';
			echo '<input type="hidden" name="gpui_edb" value="false" />';
			if( isset($gpAdmin['gpui_edb']) && $gpAdmin['gpui_edb'] === true ){
				echo '<input type="checkbox" name="gpui_edb" value="true" checked="checked" />';
			}else{
				echo '<input type="checkbox" name="gpui_edb" value="true" />';
			}
			echo '</td>';
			echo '</tr>';

		echo '</table>';
		echo '</div>';


		echo '<h4 class="head hidden"><a href="#" name="collapsible">'.$langmessage['change_password'].'</a></h4>';

		echo '<div style="display:none">';
		echo '<table class="bordered">';
		echo '<tr>';
			echo '<td>';
			echo $langmessage['old_password'];
			echo '</td>';
			echo '<td>';
			echo '<input type="password" name="oldpassword" value="" />';
			echo '</td>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			echo $langmessage['new_password'];
			echo '</td>';
			echo '<td>';
			echo '<input type="password" name="password" value="" />';
			echo '</td>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			echo $langmessage['repeat_password'];
			echo '</td>';
			echo '<td>';
			echo '<input type="password" name="password1" value="" />';
			echo '</td>';
			echo '</tr>';
		echo '</table>';
		echo '</div>';

		echo '<p>';
		echo '<input type="hidden" name="cmd" value="changeprefs" />';
		echo ' <input type="submit" class="gppost gpsubmit" name="aaa" value="'.$langmessage['save'].'" />';
		echo ' <input type="button" class="admin_box_close gpcancel" name="" value="'.$langmessage['cancel'].'" />';
		echo '</p>';

		echo '<p class="admin_note">';
		echo '<b>';
		echo $langmessage['see_also'];
		echo '</b> ';
		echo common::Link('Admin_Configuration',$langmessage['configuration'],'','name="admin_box"');
		echo '</p>';

		echo '</div>';
		echo '</form>';

	}

}

