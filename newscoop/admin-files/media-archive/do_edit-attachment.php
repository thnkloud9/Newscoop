<?php
camp_load_translation_strings("media_archive");
require_once($GLOBALS['g_campsiteDir'].'/classes/Input.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Attachment.php');
require_once($GLOBALS['g_campsiteDir'].'/classes/Log.php');

if (!SecurityToken::isValid()) {
    camp_html_display_error(getGS('Invalid security token!'));
    exit;
}

// check input
$f_attachment_id = Input::Get('f_attachment_id', 'int', 0);
$f_description = Input::Get('f_description', 'string', '');
$f_content_disposition = Input::Get('f_content_disposition', 'string', '');
if (!Input::IsValid() || ($f_attachment_id <= 0)) {
	camp_html_goto_page("/$ADMIN/media-archive/index.php#files");
}

$object = new Attachment($f_attachment_id);
$object->setDescription($object->getLanguageId(), $f_description);
$object->setContentDisposition($f_content_disposition);

camp_html_add_msg(getGS('Attachment updated.'), 'ok');
camp_html_goto_page("/$ADMIN/media-archive/edit-attachment.php?f_attachment_id=".$object->getAttachmentId());
