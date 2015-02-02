<?php
header('Content-Type: application/json');
define("STATUS_APPROVED","approved");
define("STATUS_HIDDEN","hidden");

$translator = \Zend_Registry::get('container')->getService('translator');

require_once($GLOBALS['g_campsiteDir']. "/$ADMIN_DIR/articles/article_common.php");

if (!SecurityToken::isValid()) {
	$data = new stdclass();
	$data->Results = new stdclass();
	$data->Results->f_message = $translator->trans('Invalid security token!');
	echo json_encode($data);
	exit;
}

$f_publication_id = Input::Get('f_publication_id', 'int', 0, true);
$f_issue_number = Input::Get('f_issue_number', 'int', 0, true);
$f_section_number = Input::Get('f_section_number', 'int', 0, true);
$f_language_id = Input::Get('f_language_id', 'int', 0, true);
$f_language_selected = Input::Get('f_language_selected', 'int', 0);
$f_article_number = Input::Get('f_article_number', 'int', 0);
$f_article_author = Input::Get('f_article_author', 'array', array(), true);
$f_article_author_type = Input::Get('f_article_author_type', 'array', array(), true);
$f_article_title = Input::Get('f_article_title');
$f_message = Input::Get('f_message', 'string', '', true);
$f_creation_date = Input::Get('f_creation_date');
$f_publish_date = Input::Get('f_publish_date', 'string', '', true);
$f_comment_status = Input::Get('f_comment_status', 'string', '', true);
$data = new stdclass();
$data->Results = new stdclass();
$data->Results->f_publication_id = $f_publication_id;
$data->Results->f_issue_number = $f_issue_number;
$data->Results->f_section_number = $f_section_number;
$data->Results->f_language_id = $f_language_id;
$data->Results->f_language_selected = $f_language_selected;
$data->Results->f_article_number = $f_article_number;
$data->Results->f_article_author = $f_article_author;
$data->Results->f_article_author_type = $f_article_author_type;
$data->Results->f_article_title = $f_article_title;
$data->Results->f_message = $f_message;
$data->Results->f_creation_date = $f_creation_date;
$data->Results->f_publish_date = $f_publish_date;
$data->Results->f_comment_status = $f_comment_status;

if (!Input::IsValid()) {
	camp_html_display_error($translator->trans('Invalid input: $1', array('$1' => Input::GetErrorString())), isset($BackLink) ? $Backlink : null);
	exit;
}

// Fetch article
$articleObj = new Article($f_language_selected, $f_article_number);
if (!$articleObj->exists()) {
	camp_html_display_error($translator->trans('No such article.', array(), 'articles'), $BackLink);
	exit;
}

$articleTypeObj = $articleObj->getArticleData();
$dbColumns = $articleTypeObj->getUserDefinedColumns(false, true);

$articleFields = array();
foreach ($dbColumns as $dbColumn) {
    if ($dbColumn->getType() == ArticleTypeField::TYPE_BODY) {
        $dbColumnParam = $dbColumn->getName() . '_' . $f_article_number;
    } else {
        $dbColumnParam = $dbColumn->getName();
    }
    if (isset($_REQUEST[$dbColumnParam])) {
        if($dbColumn->getType() == ArticleTypeField::TYPE_TEXT
            && $dbColumn->getMaxSize()!=0
            && $dbColumn->getMaxSize()!='') {
                $fieldValue = trim(Input::Get($dbColumnParam));
                $articleFields[$dbColumn->getName()] = mb_strlen($fieldValue, 'utf8') > $dbColumn->getMaxSize()
                    ? substr($fieldValue, 0, $dbColumn->getMaxSize())
                    : $fieldValue;
        } else {
            $articleFields[$dbColumn->getName()] = trim(Input::Get($dbColumnParam));
        }
    } else {
        unset($articleFields[$dbColumn->getName()]); // ignore if not set
    }
}

if (!empty($f_message)) {
	camp_html_add_msg($f_message, "ok");
}

if (!$articleObj->userCanModify($g_user)) {
	camp_html_add_msg($translator->trans("You do not have the right to change this article.  You may only edit your own articles and once submitted an article can only be changed by authorized users.", array(), 'articles'));
	camp_html_goto_page($BackLink);
	exit;
}
// Only users with a lock on the article can change it.
if ($articleObj->isLocked() && ($g_user->getUserId() != $articleObj->getLockedByUser())) {
	$diffSeconds = time() - strtotime($articleObj->getLockTime());
	$hours = floor($diffSeconds/3600);
	$diffSeconds -= $hours * 3600;
	$minutes = floor($diffSeconds/60);
	$lockUser = new User($articleObj->getLockedByUser());
	camp_html_add_msg($translator->trans('Could not save the article. It has been locked by $1 $2 hours and $3 minutes ago.', array('$1' => $lockUser->getRealName(), '$2' => $hours, '$3' => $minutes), 'articles'));
	camp_html_goto_page($BackLink);
	exit;
}

// Update the article author
    $blogService = Zend_Registry::get('container')->getService('blog');
    $blogInfo = $blogService->getBlogInfo($g_user);
    if (!empty($f_article_author)) {
        $em = Zend_Registry::get('container')->getService('em');
        $dispatcher = Zend_Registry::get('container')->getService('dispatcher');
        $language = $em->getRepository('Newscoop\Entity\Language')->findOneById($articleObj->getLanguageId());
        $authors = $em->getRepository('Newscoop\Entity\ArticleAuthor')->getArticleAuthors($articleObj->getArticleNumber(), $language->getCode())->getArrayResult();

        ArticleAuthor::OnArticleLanguageDelete($articleObj->getArticleNumber(), $articleObj->getLanguageId());
        foreach ($authors as $author) {
            $dispatcher->dispatch("user.set_points", new \Newscoop\EventDispatcher\Events\GenericEvent($this, array('authorId' => $author['fk_author_id'])));
        }

        $i = 0;
        foreach ($f_article_author as $author) {
            $authorObj = new Author($author);
            $author = trim($author);
            if (!$authorObj->exists() && isset($author[0])) {
                if ($blogService->isBlogger($g_user)) { // blogger can't create authors
                    continue;
                }

                $authorData = Author::ReadName($author);
                $authorObj->create($authorData);
            } elseif ($blogService->isBlogger($g_user)) { // test if using authors from blog
                if (!$blogService->isBlogAuthor($authorObj, $blogInfo)) {
                    continue;
                }
            }

            // Sets the author type selected
            $author_type = $f_article_author_type[$i];
            $authorObj->setType($author_type);
            // Links the author to the article
            if ($authorObj->getId() != 0) {
                $articleAuthorObj = new ArticleAuthor($articleObj->getArticleNumber(),
                                                  $articleObj->getLanguageId(),
                                                  $authorObj->getId(), $author_type, $i + 1);
            }

            if (isset($articleAuthorObj) && !$articleAuthorObj->exists()) {
                $articleAuthorObj->create();
                $dispatcher->dispatch("user.set_points", new \Newscoop\EventDispatcher\Events\GenericEvent($this, array('authorId' => $articleAuthorObj->getAuthorId())));
            }

            $i++;
        }
    }

// Update the article.
$articleObj->setTitle($f_article_title);
$articleObj->setIsIndexed(false);

if (!empty($f_comment_status)) {
    if ($f_comment_status == "enabled" || $f_comment_status == "locked") {
        $commentsEnabled = true;
    } else {
        $commentsEnabled = false;
    }
    // If status has changed, then you need to show/hide all the comments
    // as appropriate.
    if ($articleObj->commentsEnabled() != $commentsEnabled) {
	    $articleObj->setCommentsEnabled($commentsEnabled);
        global $controller;
        $repository = $controller->getHelper('entity')->getRepository('Newscoop\Entity\Comment');
	    $repository->setArticleStatus($f_article_number, $f_language_selected, $commentsEnabled?STATUS_APPROVED:STATUS_HIDDEN);
	    $repository->flush();
    }
    $articleObj->setCommentsLocked($f_comment_status == "locked");
}

// Make sure that the time stamp is updated.
$articleObj->setProperty('time_updated', 'NOW()', true, true);

// Verify creation date is in the correct format.
// If not, dont change it.
if (preg_match("/\d{4}-\d{2}-\d{2}/", $f_creation_date)) {
	$articleObj->setCreationDate($f_creation_date);
}

// Verify publish date is in the correct format.
// If not, dont change it.
if (preg_match("/\d{4}-\d{2}-\d{2}/", $f_publish_date)) {
	$articleObj->setPublishDate($f_publish_date);
}

foreach ($articleFields as $dbColumnName => $text) {
    $articleTypeObj->setProperty($dbColumnName, $text);
}

Log::ArticleMessage($articleObj, $translator->trans('Content edited', array(), 'articles'), $g_user->getUserId(), 37);
ArticleIndex::RunIndexer(3, 10, true);

if (CampTemplateCache::factory()) {
    CampTemplateCache::factory()->update(array(
        'language' => $articleObj->getLanguageId(),
        'publication' => $articleObj->getPublicationId(),
        'issue' => $articleObj->getIssueNumber(),
        'section' => $articleObj->getSectionNumber(),
        'article' => $articleObj->getArticleNumber(),
    ), !($articleObj->isPublished() || $articleObj->m_published));
}

$cacheService = \Zend_Registry::get('container')->getService('newscoop.cache');
$cacheService->clearNamespace('authors');
$cacheService->clearNamespace('article');
$cacheService->clearNamespace('article_type');
$cacheService->clearNamespace('boxarticles');

echo json_encode($data);
exit;
