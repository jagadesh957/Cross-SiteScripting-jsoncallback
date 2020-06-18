<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php gpOutput::GetHead(); ?>

<script type="text/javascript">/* <![CDATA[ */
$(function(){
	$('#menuwrap li').live('mouseenter',function(){
		$(this).addClass('hover');
	}).live('mouseleave',function(){
		$(this).removeClass('hover');
	});
});
/* ]]> */</script>
</head>
<body>


	<div id="headerwrap"><div>


		<div id="menuwrap"><div><div>
		<?php
			$GP_ARRANGE = false;
			gpOutput::Get('TopTwoMenu');
		?>
		</div></div></div>

		<div id="header">
		<?php
		global $config;
		$default_value = $config['title'];
		$GP_ARRANGE = false;
		gpOutput::GetArea('header',$default_value);
		//gpOutput::Get('Extra','Header');
		?>
		</div>


		<div class="clear"></div>
	</div></div>


	<div id="wrapper">
		<div id="bodywrapper">
			<div id="content">
				<?php
				$page->GetContent();
				?>
			</div>
		</div>
	</div>

	<div id="fadearea"></div>


	<div id="footerwrap">


		<div id="footertop">
		<?php
		//gpOutput::Get('Menu');
		?>
		</div>

		<div id="footareas"><div>
			<div class="footarea">
			<?php
			gpOutput::Get('Extra','Side_Menu');
			?>
			</div>
			<div class="footarea">
			<?php
			gpOutput::Get('Extra','Footer');
			?>
			</div>
			<div class="footarea">
			<?php
			gpOutput::GetAllGadgets();
			//gpOutput::Get('Menu');
			//gpOutput::Get('Extra','Footer 3');
			?>
			</div>
			<div class="clear"></div>
		</div></div>

		<div id="footerbottom">
		<?php
		gpOutput::GetAdminLink();
		?>
		</div>

	</div>

</body>
</html>
