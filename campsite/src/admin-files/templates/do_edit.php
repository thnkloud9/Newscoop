<?php
require_once($GLOBALS['g_campsiteDir']. "/$ADMIN_DIR/templates/template_common.php");

if (!$g_user->hasPermission('ManageTempl')) {
	camp_html_display_error(getGS("You do not have the right to modify templates."));
	exit;
}

$Path = Input::Get('Path', 'string', '');
$Name = Input::Get('Name', 'string', '');
if (!Template::IsValidPath($Path.DIR_SEP.$Name)) {
	camp_html_goto_page("/$ADMIN/templates/");
}
$cField = Input::Get('cField', 'string', '');
$nField = str_replace("\\r", "\r", $cField);
$nField = str_replace("\\n", "\n", $nField);

$filename = Template::GetFullPath($Path, $Name);

$result = false;
if (file_exists($filename)) {
	if (is_writable($filename)) {
		if (@$handle = fopen($filename, 'w')) {
			$result = fwrite($handle, $nField);
			fclose($handle);
		}
	} else {
		camp_html_add_msg(camp_get_error_message(CAMP_ERROR_WRITE_FILE, $filename));
		camp_html_goto_page("/$ADMIN/templates/edit_template.php?"
			."f_path=".urlencode($Path)
			."&f_name=".urlencode($Name)
			."&f_content=".urlencode($cField));
	}
} else {
	camp_html_add_msg(getGS("Template $1 no longer exists!", "&quot;".$filename."&quot;"));
	camp_html_goto_page("/$ADMIN/templates/?Path=".urlencode($Path));
}

if ($result !== false) {
	$logtext = getGS('Template $1 was changed', $Path."/".$Name);
	Log::Message($logtext, $g_user->getUserId(), 113);
	camp_html_add_msg(getGS("The template '$1' was saved successfully.", $Name), "ok");
}
camp_html_goto_page("/$ADMIN/templates/edit_template.php?"
	."f_path=".urlencode($Path)
	."&f_name=".urlencode($Name));
?>