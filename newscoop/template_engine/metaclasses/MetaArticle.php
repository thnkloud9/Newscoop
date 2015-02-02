<?php
/**
 * @package Newscoop
 */

require_once __DIR__ . '/../../classes/Article.php';
require_once __DIR__ . '/../../classes/ArticleAttachment.php';
require_once __DIR__ . '/../../classes/GeoMap.php';
require_once __DIR__ . '/../../classes/Template.php';
require_once __DIR__ . '/../../classes/Language.php';
require_once __DIR__ . '/MetaDbObject.php';
require_once __DIR__ . '/MetaArticleSlideshowList.php';

/**
 */
final class MetaArticle extends MetaDbObject
{
    private $m_articleData = null;

    private $m_state = null;

    private $m_contentCache = null;

    private static $m_baseProperties = array(
    'name'=>'Name',
    'title'=>'Name',
    'number'=>'Number',
    'keywords'=>'Keywords',
    'type_name'=>'Type',
    'creation_date'=>'UploadDate',
    'publish_date'=>'PublishDate',
    'url_name'=>'ShortName',
    'comments_locked'=>'comments_locked',
    'last_update'=>'time_updated',
    'request_object_id'=>'object_id',
    );

    private static $m_defaultCustomProperties = array(
    'year'=>'getCreationYear',
    'mon'=>'getCreationMonth',
    'wday'=>'getCreationWeekDay',
    'mday'=>'getCreationMonthDay',
    'yday'=>'getCreationYearDay',
    'hour'=>'getCreationHour',
    'min'=>'getCreationMinute',
    'sec'=>'getCreationSecond',
    'mon_name'=>'getCreationMonthName',
    'wday_name'=>'getCreationWeekDayName',
    'template'=>'getTemplate',
    'comments_enabled'=>'getCommentsEnabled',
    'on_front_page'=>'getOnFrontPage',
    'on_section_page'=>'getOnSectionPage',
    'is_published'=>'getIsPublished',
    'is_public'=>'getIsPublic',
    'is_indexed'=>'getIsIndexed',
    'publication'=>'getPublication',
    'issue'=>'getIssue',
    'section'=>'getSection',
    'language'=>'getLanguage',
    'owner'=>'getOwner',
    'author'=>'getAuthor',
    'authors'=>'getAuthors',
    'defined'=>'defined',
    'has_attachments'=>'hasAttachments',
    'has_map'=>'hasMap',
    'map'=>'getMap',
    'image_index'=>'getImageIndex',
    'comment_count'=>'getCommentCount',
    'comment_count_all_lang'=>'getCommentCountAllLang',
    'recommended_comment_count'=>'getRecommendedCommentCount',
    'content_accessible'=>'isContentAccessible',
    'image'=>'getImage',
    'reads'=>'getReads',
    'topics_count'=>'topicsCount',
    'has_topics'=>'hasTopics',
    'topics'=>'getTopics',
    'type_translation'=>'getTypeTranslated',
    'seo_url_end'=>'getSEOURLEnd',
    'url' =>'getUrl',
    'webcode' => 'getWebcode',
    'dates' => 'getDatetime'
    );

    /** @var Article */
    private $bloginfo;

    /** @var array */
    private $renditions;

    /** @var array */
    public $slideshows;

    public function __construct($p_languageId = null, $p_articleId = null)
    {
        $this->m_properties = self::$m_baseProperties;
        $this->m_customProperties = self::$m_defaultCustomProperties;

        $cacheService = \Zend_Registry::get('container')->getService('newscoop.cache');
        $cacheKey = $cacheService->getCacheKey('legacy_article_'.$p_languageId.'_'.$p_articleId, 'article');
        if ($cacheService->contains($cacheKey)) {
            $this->m_dbObject = $cacheService->fetch($cacheKey);
        } else {
            $this->m_dbObject = new Article($p_languageId, $p_articleId);
            if ($p_languageId && $p_articleId) {
                $cacheService->save($cacheKey, $this->m_dbObject);
            }
        }

        if ($this->m_dbObject->exists()) {
            $cacheKeyArticleData = $cacheService->getCacheKey(array('articleData', $this->m_dbObject->getType(),
                $this->m_dbObject->getArticleNumber(), $this->m_dbObject->getLanguageId()), 'article');
            if ($cacheService->contains($cacheKeyArticleData)) {
                $this->m_articleData = $cacheService->fetch($cacheKeyArticleData);
            } else {
                $this->m_articleData = new ArticleData($this->m_dbObject->getType(),
                    $this->m_dbObject->getArticleNumber(),
                    $this->m_dbObject->getLanguageId());
                $cacheService->save($cacheKeyArticleData, $this->m_articleData);
            }

            foreach ($this->m_articleData->m_columnNames as $property) {
                if ($property[0] != 'F') {
                    continue;
                }
                $property = substr($property, 1);
                $tr_property = strtolower($property);
                if (array_key_exists($tr_property, $this->m_customProperties)) {
                    continue;
                }
                $this->m_customProperties[$tr_property] = array($property);
            }
        } else {
            if (!is_null($p_languageId) || !is_null($p_articleId)) {
                $this->m_dbObject = new Article();
            }
            $this->m_articleData = new ArticleData(null, null, null);
        }

        $this->slideshows = new MetaArticleSlideshowList($p_articleId);
    } // fn __construct

    final public function __get($p_property)
    {
        $property = $this->translateProperty($p_property);
        if ($this->m_state == 'type_name_error') {
            $this->m_state = null;

            return null;
        }

        if ($property == 'type' && $this->m_state == null) {
            $this->m_state = 'type';

            return $this;
        }

        if ($this->m_state == 'type') {
            if (strcasecmp($this->m_dbObject->getType(), $property) != 0) {
                $this->m_state = 'type_name_error';
            } else {
                $this->m_state = null;
            }

            return $this;
        }

        if (is_null($this->m_state) && $property != 'image_index'
        && strncasecmp('image', $property, 5) == 0 && strlen($property) > 5) {
            $imageNo = substr($property, 5);
            $cacheService = \Zend_Registry::get('container')->getService('newscoop.cache');
            $cacheKey = $cacheService->getCacheKey(array('MetaImage', $this->m_dbObject->getArticleNumber(), $imageNo), 'article');
            if ($cacheService->contains($cacheKey)) {
                return $cacheService->fetch($cacheKey);
            } else {
                $articleImage = new ArticleImage($this->m_dbObject->getArticleNumber(), null, $imageNo);
                if (!$articleImage->exists()) {
                    $this->trigger_invalid_property_error($p_property);
                    $metaImage = new MetaImage();
                } else {
                    $metaImage = new MetaImage($articleImage->getImageId());
                }
                $cacheService->save($cacheKey, $metaImage);

                return $metaImage;
            }
        }

        try {
            if (array_search($property, $this->m_properties)) {
                $methodName = $this->m_getPropertyMethod;
                $propertyValue = $this->m_dbObject->$methodName($property);
            } elseif (array_key_exists($property, $this->m_customProperties)) {
                return $this->getCustomProperty($property);
            } else { // empty
                //$this->trigger_invalid_property_error($p_property);
                return null;
            }
            if (empty($propertyValue) || !is_string($propertyValue) || is_numeric($propertyValue)) {
                return $propertyValue;
            }
            if (count($this->m_skipFilter) == 0 || !in_array(strtolower($p_property), $this->m_skipFilter)) {
                $propertyValue = self::htmlFilter($propertyValue);
            }

            return $propertyValue;
        } catch (InvalidPropertyException $e) {
            $this->trigger_invalid_property_error($p_property);

            return null;
        }
    } // fn __get

    public function subtitle_url_id($p_fieldName)
    {
        $property = $this->translateProperty($p_fieldName);

        return 'st-'.$property;
    }

    public function current_subtitle_no($p_fieldName)
    {
        $property = $this->translateProperty($p_fieldName);
        if (isset($this->m_customProperties[$property])
        && is_array($this->m_customProperties[$property])) {
            $dbProperty = $this->m_customProperties[$property][0];
            $articleFieldType = new ArticleTypeField($this->type_name, $dbProperty);
            if ($articleFieldType->getType() == ArticleTypeField::TYPE_BODY) {
                $subtitleId = $this->subtitle_url_id($p_fieldName);

                return CampTemplate::singleton()->context()->default_url->get_parameter($subtitleId);
            }
        }

        return null;
    }

    protected function getCustomProperty($p_property)
    {
        $property = $this->translateProperty($p_property);
        if (isset($this->m_customProperties[$property])
        && is_array($this->m_customProperties[$property])) {
            try {
                $dbProperty = $this->m_customProperties[$property][0];
                $fieldValue = $this->m_articleData->getProperty('F'.$dbProperty);

                $cacheService = \Zend_Registry::get('container')->getService('newscoop.cache');
                $cacheKey = $cacheService->getCacheKey(array('ArticleTypeField', $this->type_name, $dbProperty), 'article_type');
                if ($cacheService->contains($cacheKey)) {
                    $articleFieldType = $cacheService->fetch($cacheKey);
                } else {
                    $articleFieldType = new ArticleTypeField($this->type_name, $dbProperty);
                    $cacheService->save($cacheKey, $articleFieldType);
                }
                if ($articleFieldType->getType() == ArticleTypeField::TYPE_BODY) {
                    if (is_null($this->getContentCache($property))) {
                        $context = CampTemplate::singleton()->context();
                        $subtitleId = $this->subtitle_url_id($property);
                        $subtitleNo = $context->default_url->get_parameter($subtitleId);
                        if (is_null($subtitleNo)) {
                            $subtitleNo = 0;
                        } elseif ($subtitleNo === 'all') {
                            $subtitleNo = null;
                        }
                        $bodyField = new MetaArticleBodyField($fieldValue, $this,
                                         $articleFieldType->getPrintName(), $this->name, $subtitleNo,
                                         '<span class="subtitle"><p>', '</p></span>');
                        $this->setContentCache($property, $bodyField);
                    }
                    $fieldValue = $this->getContentCache($property);
                }
                if ($articleFieldType->getType() == ArticleTypeField::TYPE_TOPIC) {
                    $fieldValue = TopicName::GetTopicNames($fieldValue);
                    $fieldValue = $fieldValue[$this->m_dbObject->getProperty('IdLanguage')];
                }

                return $fieldValue;
            } catch (InvalidPropertyException $e) {
                // do nothing; will throw another exception with original property field name
            }
            throw new InvalidPropertyException(get_class($this->m_dbObject), $p_property);
        }

        return parent::getCustomProperty($p_property);
    }

    private function getContentCache($p_property)
    {
        $p_property = $this->translateProperty($p_property);
        if (is_null($this->m_contentCache)
        || !isset($this->m_contentCache[$p_property])) {
            return null;
        }

        return $this->m_contentCache[$p_property];
    }

    private function setContentCache($p_property, $p_value)
    {
        $p_property = $this->translateProperty($p_property);
        $this->m_contentCache[$p_property] = $p_value;
    }

    protected function getCreationYear()
    {
        $creation_timestamp = strtotime($this->m_dbObject->getProperty('UploadDate'));
        $creation_date_time = getdate($creation_timestamp);

        return $creation_date_time['year'];
    }

    protected function getCreationMonth()
    {
        $creation_timestamp = strtotime($this->m_dbObject->getProperty('UploadDate'));
        $creation_date_time = getdate($creation_timestamp);

        return $creation_date_time['mon'];
    }

    protected function getCreationWeekDay()
    {
        $creation_timestamp = strtotime($this->m_dbObject->getProperty('UploadDate'));
        $creation_date_time = getdate($creation_timestamp);

        return $creation_date_time['wday'];
    }

    protected function getCreationMonthDay()
    {
        $creation_timestamp = strtotime($this->m_dbObject->getProperty('UploadDate'));
        $creation_date_time = getdate($creation_timestamp);

        return $creation_date_time['mday'];
    }

    protected function getCreationYearDay()
    {
        $creation_timestamp = strtotime($this->m_dbObject->getProperty('UploadDate'));
        $creation_date_time = getdate($creation_timestamp);

        return $creation_date_time['yday'];
    }

    protected function getCreationHour()
    {
        $creation_timestamp = strtotime($this->m_dbObject->getProperty('UploadDate'));
        $creation_date_time = getdate($creation_timestamp);

        return $creation_date_time['hours'];
    }

    protected function getCreationMinute()
    {
        $creation_timestamp = strtotime($this->m_dbObject->getProperty('UploadDate'));
        $creation_date_time = getdate($creation_timestamp);

        return $creation_date_time['minutes'];
    }

    protected function getCreationSecond()
    {
        $creation_timestamp = strtotime($this->m_dbObject->getProperty('UploadDate'));
        $creation_date_time = getdate($creation_timestamp);

        return $creation_date_time['seconds'];
    }

    protected function getCreationMonthName()
    {
        $dateTime = new MetaDatetime($this->m_dbObject->getProperty('UploadDate'));

        return $dateTime->getMonthName();
    }

    protected function getCreationWeekDayName()
    {
        $dateTime = new MetaDatetime($this->m_dbObject->getProperty('UploadDate'));

        return $dateTime->getWeekDayName();
    }

    protected function getOnFrontPage()
    {
        return (int) ($this->m_dbObject->getProperty('OnFrontPage') == 'Y');
    }

    protected function getOnSectionPage()
    {
        return (int) ($this->m_dbObject->getProperty('OnSection') == 'Y');
    }

    protected function getIsPublished()
    {
        return (int) ($this->m_dbObject->getProperty('Published') == 'Y');
    }

    protected function getIsPublic()
    {
        return (int) ($this->m_dbObject->getProperty('Public') == 'Y');
    }

    protected function getIsIndexed()
    {
        return (int) ($this->m_dbObject->getProperty('IsIndexed') == 'Y');
    }

    protected function getPublication()
    {
        return new MetaPublication($this->m_dbObject->getProperty('IdPublication'));
    }

    protected function getIssue()
    {
        return new MetaIssue($this->m_dbObject->getProperty('IdPublication'),
        $this->m_dbObject->getProperty('IdLanguage'),
        $this->m_dbObject->getProperty('NrIssue'));
    }

    protected function getSection()
    {
        return new MetaSection($this->m_dbObject->getProperty('IdPublication'),
        $this->m_dbObject->getProperty('NrIssue'),
        $this->m_dbObject->getProperty('IdLanguage'),
        $this->m_dbObject->getProperty('NrSection'));
    }

    protected function getLanguage()
    {
        return new MetaLanguage($this->m_dbObject->getProperty('IdLanguage'));
    }

    protected function getOwner()
    {
        $container = Zend_Registry::get('container');
        $userService =  $container->getService('user');

        return new MetaUser($userService->find($this->m_dbObject->getProperty('IdUser')));
    }

    protected function getAuthor()
    {
        $authors = ArticleAuthor::GetAuthorsByArticle($this->m_dbObject->getProperty('Number'),
            $this->m_dbObject->getProperty('IdLanguage'));
        $author = array_shift($authors);
        if (is_null($author)) {
            return new MetaAuthor();
        } else {
            return new MetaAuthor($author->getId(), $author->getAuthorType());
        }
    }

    protected function getAuthors()
    {
        $authors = ArticleAuthor::GetAuthorsByArticle($this->m_dbObject->getProperty('Number'),
            $this->m_dbObject->getProperty('IdLanguage'));
        if (!is_array($authors)) {
            $authors = array();
        }

        $metaAuthors = array();
        foreach ($authors as $author) {
            $metaAuthors[] = new MetaAuthor($author->getId(), $author->getType());
        }

        return $metaAuthors;
    }

    protected function getTemplate()
    {
        $articleSection = new Section($this->m_dbObject->getProperty('IdPublication'),
        $this->m_dbObject->getProperty('NrIssue'),
        $this->m_dbObject->getProperty('IdLanguage'),
        $this->m_dbObject->getProperty('NrSection'));
        if ($articleSection->getArticleTemplateId() > 0) {
            return new MetaTemplate($articleSection->getArticleTemplateId());
        }
        $articleIssue = new Issue($this->m_dbObject->getProperty('IdPublication'),
        $this->m_dbObject->getProperty('IdLanguage'),
        $this->m_dbObject->getProperty('NrIssue'));

        return new MetaTemplate($articleIssue->getArticleTemplateId());
    }

    protected function hasAttachments()
    {
        $cacheService = \Zend_Registry::get('container')->getService('newscoop.cache');
        $cacheKey = $cacheService->getCacheKey(array('hasAttachments', $this->m_dbObject->getProperty('Number')), 'attachments');
        if ($cacheService->contains($cacheKey)) {
            $attachments = $cacheService->fetch($cacheKey);
        } else {
            $attachments = ArticleAttachment::GetAttachmentsByArticleNumber($this->m_dbObject->getProperty('Number'));
            $cacheService->save($cacheKey, $attachments);
        }

        return (int) (sizeof($attachments) > 0);
    }

    /**
     * Has Article a map?
     *
     * @return bool
     */
    protected function hasMap()
    {
        $map = Geo_Map::GetMapByArticle($this->m_dbObject->getProperty('Number'));

        return (bool) $map->exists();
    }

    /**
     * Get Map for this article
     *
     * @return Geo_Map
     */
    protected function getMap()
    {
        $map = Geo_Map::GetMapByArticle($this->m_dbObject->getProperty('Number'));

        return new MetaMap($map);
    }

    protected function getCommentsEnabled()
    {
        $cacheService = \Zend_Registry::get('container')->getService('newscoop.cache');
        $cacheKey = $cacheService->getCacheKey(array('publication', $this->m_dbObject->getProperty('IdPublication')), 'publication');
        if ($cacheService->contains($cacheKey)) {
            $publicationObj = $cacheService->fetch($cacheKey);
        } else {
            $publicationObj = new Publication($this->m_dbObject->getProperty('IdPublication'));
            $cacheService->save($cacheKey, $publicationObj);
        }

        $cacheKeyArticleType = $cacheService->getCacheKey(array('ArticleType', $this->m_dbObject->getProperty('Type')), 'article_type');
        if ($cacheService->contains($cacheKeyArticleType)) {
            $articleTypeObj = $cacheService->fetch($cacheKeyArticleType);
        } else {
            $articleTypeObj = new ArticleType($this->m_dbObject->getProperty('Type'));
            $cacheService->save($cacheKeyArticleType, $articleTypeObj);
        }

        return $publicationObj->commentsEnabled() && $articleTypeObj->commentsEnabled() && $this->m_dbObject->commentsEnabled();
    }

    /**
     * Returns the index of the current image inside the article.
     * If the image doesn't belong to the article returns null.
     *
     * @return int
     */
    protected function getImageIndex()
    {
        $image = CampTemplate::singleton()->context()->image;
        if (!$image->defined) {
            return null;
        }
        $cacheService = \Zend_Registry::get('container')->getService('newscoop.cache');
        $cacheKey = $cacheService->getCacheKey(array('ArticleImageIndex', $this->m_dbObject->getArticleNumber(), $image->number), 'article');
        if ($cacheService->contains($cacheKey)) {
            $index = $cacheService->fetch($cacheKey);
        } else {
            $articleImage = new ArticleImage($this->m_dbObject->getArticleNumber(),
            $image->number);
            if (!$articleImage->exists()) {
                $index = null;
            } else {
                $index = $articleImage->getImageArticleIndex();
            }
            $cacheService->save($cacheKey, $index);
        }

        return $index;
    }


    protected function getCommentCount()
    {
        $cacheService = \Zend_Registry::get('container')->getService('newscoop.cache');
        $cacheKey = $cacheService->getCacheKey(array('getCommentCount', $this->m_dbObject->getArticleNumber(), $this->m_dbObject->getLanguageId()), 'comment');
        if ($cacheService->contains($cacheKey)) {
            $result = $cacheService->fetch($cacheKey);
        } else {
            $container = Zend_Registry::get('container');
            $repository = $container->get('em')->getRepository('Newscoop\Entity\Comment');
            $filter = array(
                'status' => 'approved',
                'thread' => $this->m_dbObject->getArticleNumber(),
                'language' => $this->m_dbObject->getLanguageId(),
            );
            $params = array(
                'sFilter' => $filter
            );
            $result = $repository->getCount($params);
            $cacheService->save($cacheKey, $result);
        }

        return $result;
    }


    protected function getCommentCountAllLang()
    {
        $cacheService = \Zend_Registry::get('container')->getService('newscoop.cache');
        $cacheKey = $cacheService->getCacheKey(array('getCommentCountAllLang', $this->m_dbObject->getArticleNumber()), 'comment');
        if ($cacheService->contains($cacheKey)) {
            $result = $cacheService->fetch($cacheKey);
        } else {
            $container = Zend_Registry::get('container');
            $repository = $container->get('em')->getRepository('Newscoop\Entity\Comment');
            $filter = array(
                'status' => 'approved',
                'thread' => $this->m_dbObject->getArticleNumber(),
            );
            $params = array(
                'sFilter' => $filter
            );
            $result = $repository->getCount($params);
            $cacheService->save($cacheKey, $result);
        }

        return $result;
    }

    protected function getRecommendedCommentCount()
    {
        $cacheService = \Zend_Registry::get('container')->getService('newscoop.cache');
        $cacheKey = $cacheService->getCacheKey(array('getRecommendedCommentCount', $this->m_dbObject->getArticleNumber(), $this->m_dbObject->getLanguageId()), 'comment');
        if ($cacheService->contains($cacheKey)) {
            $result = $cacheService->fetch($cacheKey);
        } else {
            $container = Zend_Registry::get('container');
            $repository = $container->get('em')->getRepository('Newscoop\Entity\Comment');
            $filter = array(
                'status' => 'approved',
                'thread' => $this->m_dbObject->getArticleNumber(),
                'language' => $this->m_dbObject->getLanguageId(),
                'recommended' => '1'
            );
            $params = array(
                'sFilter' => $filter
            );
            $result = $repository->getCount($params);
            $cacheService->save($cacheKey, $result);
        }

        return $result;
    }


    /**
     * Test if content is accessible for current user
     *
     * @return bool
     */
    protected function isContentAccessible()
    {
        if ($this->m_dbObject->isPublic() && $this->getIsPublished()) {
            return (int) true;
        }

        $context = CampTemplate::singleton()->context();
        if ($context->preview) {
            return (int) true;
        }

        $user = $context->user;
        if (!$user->defined) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return (int) ($user->subscription->is_valid && $user->subscription->has_section($context->section->number));
    }


    protected function getImage()
    {
        $context = CampTemplate::singleton()->context();
        if ($context->image->defined) {
            return $context->image;
        }
        $images = ArticleImage::GetImagesByArticleNumber($this->m_dbObject->getArticleNumber());
        if (count($images) == 0) {
            return new MetaImage();
        }

        return new MetaImage($images[0]->getImageId());
    }


    protected function getReads()
    {
        return $this->m_dbObject->getReads();
    }


    protected function topicsCount()
    {
        $articleTopics = $this->getContentCache('article_topics');
        if (is_null($articleTopics)) {
            $articleTopics = ArticleTopic::GetArticleTopics($this->m_dbObject->getArticleNumber());
            $this->setContentCache('article_topics', $articleTopics);
        }

        return count($articleTopics);
    }


    protected function hasTopics()
    {
        return (int) ($this->topicsCount() > 0);
    }


    protected function getTopics()
    {
        $articleTopics = $this->getContentCache('article_topics');
        if (is_null($articleTopics)) {
            $articleTopics = ArticleTopic::GetArticleTopics($this->m_dbObject->getArticleNumber());
            $this->setContentCache('article_topics', $articleTopics);
        }
        $topics = array();
        foreach ($articleTopics as $topic) {
            $topics[] = $topic->getName($this->getLanguage()->number);
        }

        return $topics;
    }

    /**
     * Get SEO Url
     *
     * @return string
     */
    protected function getSEOURLEnd()
    {
        $pubSeo = $this->getPublication()->seo;
        $lanNum = $this->getLanguage()->number;
        if (empty($pubSeo) || empty($lanNum)) {
            return '';
        }

        return $this->m_dbObject->getSEOURLEnd($pubSeo, $lanNum);
    }

    protected function getTypeTranslated()
    {
        return $this->m_dbObject->getTranslateType($this->m_dbObject->getLanguageId());
    }

    protected function getUrl()
    {
        return ShortURL::GetURL($this->m_dbObject->getPublicationId(), $this->m_dbObject->getLanguageId(), null, null, $this->m_dbObject->getArticleNumber());
    }

    protected function getWebcode()
    {
        return $this->m_dbObject->getWebcode();
    }

    private $_datetimeData;
    protected function getDatetime()
    {
        if (is_null($this->_datetimeData)) {
            $em = Zend_Registry::get('container')->getService('em');
            $repo = $em->getRepository('Newscoop\Entity\ArticleDatetime');
            $this->_datetimeData = new MetaArticleDatetime($repo->findBy(array('articleId'=>$this->m_dbObject->getProperty('Number'))));
        }

        return $this->_datetimeData;
    }

    public function has_topic($p_topicName)
    {
        $cacheService = \Zend_Registry::get('container')->getService('newscoop.cache');
        $cacheKey = $cacheService->getCacheKey(array('has_topic', $this->m_dbObject->getArticleNumber(), $p_topicName), 'article');
        if ($cacheService->contains($cacheKey)) {
            $exists = $cacheService->fetch($cacheKey);
        } else {
            $exists = (int) false;
            $em = \Zend_Registry::get('container')->getService('em');
            $repository = $em->getRepository('Newscoop\NewscoopBundle\Entity\Topic');
            $context = CampTemplate::singleton()->context();
            $locale = $context->language->code;
            $topic = $repository->getTopicByIdOrName($p_topicName, $locale)->getOneOrNullResult();
            if (!$topic) {
                $this->trigger_invalid_value_error('has_topic', $p_topicName);

                return null;
            }

            $articleTopics = ArticleTopic::GetArticleTopics($this->m_dbObject->getArticleNumber());
            foreach ($articleTopics as $articleTopic) {
                if ($articleTopic->getTopicId() == $topic->getTopicId()) {
                    $exists = (int) true;
                }
            }

            $cacheService->save($cacheKey, $exists);
        }

        return $exists;
    }


    /**
     * Returns true if the article was translated in to the language
     * identified by the given language code
     *
     * @param  string $p_language - language code
     * @return bool
     */
    public function translated_to($p_language)
    {
        if (is_string($p_language)) {
            $languages = Language::GetLanguages(null, $p_language);
            if (sizeof($languages) == 0) {
                return (int) false;
            }
            $language = $languages[0];
        } else {
            $language = $p_language;
        }
        $article = new Article($language->getLanguageId(),
        $this->m_dbObject->getArticleNumber());

        return (int) $article->exists()
        && ($article->isPublished() || CampTemplate::singleton()->context()->preview);
    }


    /**
     * Returns a list of MetaLanguage objects - list of languages in which
     * the article was translated.
     *
     * @param  boolean $p_excludeCurrent
     * @param  array   $p_order
     * @return array   of MetaLanguage
     */
    public function languages_list($p_excludeCurrent = true,
    array $p_order = array()) {
        $languages = $this->m_dbObject->getLanguages($p_excludeCurrent, $p_order,
        !CampTemplate::singleton()->context()->preview);
        $metaLanguagesList = array();
        foreach ($languages as $language) {
            $metaLanguagesList[] = new MetaLanguage($language->getLanguageId());
        }

        return $metaLanguagesList;
    }


    /**
     * Returns true if the article keywords field had the given keyword.
     *
     * @param  string $p_keyword
     * @return bool
     */
    public function has_keyword($p_keyword)
    {
        $keywords = $this->m_dbObject->getKeywords();

        return (int) (stristr($keywords, $p_keyword) !== false);
    }


    /**
     * Returns the number of the subtitles of the given article field.
     * Returns null if the field name was invalid or it name a non body field.
     *
     * @param  string $p_property - article field name
     * @return int
     */
    public function subtitles_count($p_property)
    {
        try {
            $propertyValue = $this->getCustomProperty($p_property);
            if (!is_a($propertyValue, 'MetaArticleBodyField')) {
                return null;
            }

            return $propertyValue->subtitles_count;
        } catch (InvalidPropertyException $e) {
            // do nothing, return null
        }

        return null;
    }


    /**
     * Returns true if the article had an attached image identified
     * by the given article internal index.
     *
     * @param  int  $p_imageIndex - the index of the image attachment in the article
     * @return bool
     */
    public function has_image($p_imageIndex)
    {
        $cacheService = \Zend_Registry::get('container')->getService('newscoop.cache');

        $cacheKey = $cacheService->getCacheKey(array('has_article_image', $this->m_dbObject->getArticleNumber(), $p_imageIndex), 'article_image');

        if ($cacheService->contains($cacheKey)) {
            $exists = $cacheService->fetch($cacheKey);
        } else {
            $articleImage = new ArticleImage($this->m_dbObject->getArticleNumber(), null, $p_imageIndex);
            $exists = (int) $articleImage->exists();
            $cacheService->save($cacheKey, $exists);
        }

        return $exists;
    }


    /**
     * Returns an image meta object corresponding to the given index
     * of the image attachment inside the article. Returns an empty
     * meta image object if the image did not exist.
     *
     * @param  int       $p_imageIndex - the index of the image attachment in the article
     * @return MetaImage
     */
    public function image($p_imageIndex)
    {
        $cacheService = \Zend_Registry::get('container')->getService('newscoop.cache');

        $cacheKey = $cacheService->getCacheKey(array('ArticleImage', $this->m_dbObject->getArticleNumber(), $p_imageIndex), 'article_image');

        if ($cacheService->contains($cacheKey)) {
            $articleImage = $cacheService->fetch($cacheKey);
        } else {
            $articleImage = new ArticleImage($this->m_dbObject->getArticleNumber(), null, $p_imageIndex);
            $cacheService->save($cacheKey, $articleImage);
        }

        $imageCacheKey = $cacheService->getCacheKey(array('MetaImage', $articleImage->getImageId()), 'image');
        if ($cacheService->contains($imageCacheKey)) {
            $metaImage = $cacheService->fetch($imageCacheKey);
        } else {
            if (!$articleImage->exists()) {
                $metaImage = new MetaImage();
            } else {
                $metaImage = new MetaImage($articleImage->getImageId());
            }
            $cacheService->save($imageCacheKey, $metaImage);
        }

        return $metaImage;
    }

    /**
     * Get related bloginfo
     *
     * @return Article|null
     */
    public function get_bloginfo()
    {
        if (NULL === $this->bloginfo) {
            $infos = \Article::GetArticles($this->m_dbObject->getPublicationId(), $this->m_dbObject->getIssueNumber(), $this->m_dbObject->getSectionNumber(), null, null, false, array(
                "Type = 'bloginfo'",
            ));

            if (!empty($infos)) {
                $this->bloginfo = new self($infos[0]->getLanguageId(), $infos[0]->getArticleNumber());
            }
        }

        return $this->bloginfo;
    }

    /**
     * Get article renditions
     *
     * @return array
     */
    public function getRenditions()
    {
        if ($this->renditions === null) {
            $this->renditions = Zend_Registry::get('container')->getService('image.rendition')->getArticleRenditions($this->m_dbObject->getArticleNumber());
        }

        return $this->renditions;
    }
}
