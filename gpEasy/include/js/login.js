$(function(){IE_LT_8&&$("#browser_warning").show();$("#loginform .login_text:first").focus();$("#login_form").submit(function(){if(this.encrypted.checked){var b=this.password.value,a=this.login_nonce.value;this.pass_md5.value=hex_sha1(a+hex_md5(b));this.pass_sha.value=hex_sha1(a+hex_sha1(b));this.password.value="";this.user_sha.value=hex_sha1(a+this.username.value);this.username.value=""}})});