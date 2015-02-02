<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 */

namespace Newscoop\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityNotFoundException;
use ArticleData;
use Newscoop\View\ArticleView;
use Newscoop\Search\DocumentInterface;

/**
 * Article entity
 *
 * @ORM\Entity(repositoryClass="Newscoop\Entity\Repository\ArticleRepository")
 * @ORM\Table(name="Articles")
 */
class Article implements DocumentInterface
{
    const STATUS_PUBLISHED = 'Y';
    const STATUS_NOT_PUBLISHED = 'N';
    const STATUS_SUBMITTED = 'S';

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Language")
     * @ORM\JoinColumn(name="IdLanguage", referencedColumnName="Id")
     * @var Newscoop\Entity\Language
     */
    protected $language;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Publication")
     * @ORM\JoinColumn(name="IdPublication", referencedColumnName="Id", nullable=true)
     * @var Newscoop\Entity\Publication
     */
    protected $publication;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Issue")
     * @ORM\JoinColumn(name="issue_id", referencedColumnName="id")
     * @var Newscoop\Entity\Issue
     */
    protected $issue;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Section")
     * @ORM\JoinColumn(name="section_id", referencedColumnName="id")
     * @var Newscoop\Entity\Section
     */
    protected $section;

    /**
     * TODO: Fix this bug. It's not section Id - it's sectionNumber!
     * @ORM\Column(name="NrSection", nullable=true)
     * @var int
     */
    protected $sectionId;

    /**
     * TODO: Fix this bug. It's not Issue Id - it's issueNumber!
     * @ORM\Column(name="NrIssue", nullable=true)
     * @var int
     */
    protected $issueId;

    /**
     * @ORM\OneToOne(targetEntity="Newscoop\Entity\User")
     * @ORM\JoinColumn(name="IdUser", referencedColumnName="Id")
     * @var Newscoop\Entity\User
     */
    protected $creator;

    /**
     * Article fields used by Newscoop API
     * @var array
     */
    protected $fields;

    /**
     * Article Authors for Newscoop\Gimme
     * @var object
     */
    protected $articleAuthors;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="Number")
     * @var int
     */
    protected $number;

    /**
     * @ORM\Column(name="Name", nullable=True)
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(name="ShortName", nullable=True)
     * @var string
     */
    protected $shortName;

    /**
     * @ORM\Column(type="datetime", name="time_updated", nullable=true)
     * @var DateTime
     */
    protected $updated;

    /**
     * @ORM\Column(type="datetime", name="indexed", nullable=true)
     * @var DateTime
     */
    protected $indexed;

    /**
     * @ORM\Column(name="comments_enabled", nullable=True)
     * @var int
     */
    protected $comments_enabled;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="thread", indexBy="language")
     * @var Newscoop\Entity\Comments
     */
    protected $comments;

    /**
     * @var int
     */
    protected $comments_count;

    /**
     * @var int
     */
    protected $recommended_comments_count;

    /**
     * @var string
     */
    protected $comments_link;

    /**
     * @ORM\Column(name="Type", nullable=True)
     * @var string
     */
    protected $type;

    /**
     * @ORM\Column(type="datetime", name="PublishDate", nullable=true)
     * @var DateTime
     */
    protected $published;

    /**
     * @ORM\Column(name="Published", nullable=true)
     * @var string
     */
    protected $workflowStatus;

    /**
     * @ORM\Column(type="integer", name="ArticleOrder", nullable=True)
     * @var int
     */
    protected $articleOrder;

    /**
     * @ORM\Column(name="Public", nullable=True)
     * @var string
     */
    protected $public;

    /**
     * @ORM\Column(name="OnFrontPage", nullable=True)
     * @var string
     */
    protected $onFrontPage;

    /**
     * @ORM\Column(name="OnSection", nullable=True)
     * @var string
     */
    protected $onSection;

    /**
     * @ORM\Column(type="datetime", name="UploadDate", nullable=True)
     * @var DateTime
     */
    protected $uploaded;

    /**
     * @ORM\Column(name="Keywords", nullable=True)
     * @var string
     */
    protected $keywords;

    /**
     * @ORM\Column(name="IsIndexed", nullable=True)
     * @var string
     */
    protected $isIndexed;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\User")
     * @ORM\JoinColumn(name="LockUser", referencedColumnName="Id", nullable=true)
     * @var Newscoop\Entity\User
     */
    protected $lockUser;

    /**
     * @ORM\ManyToMany(targetEntity="Newscoop\NewscoopBundle\Entity\Topic", inversedBy="articles")
     * @ORM\JoinTable(name="ArticleTopics",
     *      joinColumns={
     *          @ORM\JoinColumn(name="NrArticle", referencedColumnName="Number")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="TopicId", referencedColumnName="id")
     *      }
     *  )
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    protected $topics;

    /**
     * @ORM\ManyToMany(targetEntity="Newscoop\Entity\Playlist")
     * @ORM\JoinTable(name="playlist_article",
     *      joinColumns={
     *          @ORM\JoinColumn(name="article_no", referencedColumnName="Number")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="id_playlist", referencedColumnName="id_playlist")
     *      }
     *  )
     * @var Newscoop\Entity\Playlist
     */
    protected $playlists;

    /**
     * @ORM\Column(type="datetime", name="LockTime", nullable=true)
     * @var DateTime
     */
    protected $lockTime;

    /**
     * @ORM\Column(type="integer", name="comments_locked", nullable=True)
     * @var int
     */
    protected $commentsLocked;

    /**
     * @ORM\Column(type="integer", name="object_id", nullable=True)
     * @var int
     */
    protected $objectId;

    /**
     * @ORM\ManyToMany(targetEntity="Newscoop\Package\Package")
     * @ORM\JoinTable(name="package_article_package",
     *      joinColumns={
     *          @ORM\JoinColumn(name="article_id", referencedColumnName="Number")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="package_id", referencedColumnName="id")
     *      }
     *  )
     * @var Newscoop\Package\Package
     */
    protected $packages;

    /**
     * Article renditions used by Newscoop API
     * @var array
     */
    protected $renditions;

    /**
     * Article translations used by Newscoop API
     * @var array
     */
    protected $translations;

    /**
     * @ORM\OneToOne(targetEntity="Newscoop\Entity\Webcode")
     * @ORM\JoinColumn(name="webcode", referencedColumnName="webcode")
     * @var Newscoop\Entity\Webcode
     */
    protected $webcode;

    /**
     * Article reads number used by Newscoop API
     * @var int
     */
    protected $reads;

    /**
     * @ORM\ManyToMany(targetEntity="Newscoop\Entity\Author")
     * @ORM\JoinTable(name="ArticleAuthors",
     *      joinColumns={
     *          @ORM\JoinColumn(name="fk_article_number", referencedColumnName="Number"),
     *          @ORM\JoinColumn(name="fk_language_id", referencedColumnName="IdLanguage")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="fk_author_id", referencedColumnName="id")
     *      }
     * )
     * @var Doctrine\Common\Collections\Collection
     */
    protected $authors;

    /**
     * @ORM\ManyToMany(targetEntity="Newscoop\Entity\Attachment")
     * @ORM\JoinTable(name="ArticleAttachments",
     *      joinColumns={
     *          @ORM\JoinColumn(name="fk_article_number", referencedColumnName="Number"),
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="fk_attachment_id", referencedColumnName="id")
     *      }
     *  )
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    protected $attachments;

    /**
     * @ORM\ManyToMany(targetEntity="Newscoop\Image\LocalImage")
     * @ORM\JoinTable(name="ArticleImages",
     *      joinColumns={@ORM\JoinColumn(name="NrArticle", referencedColumnName="Number")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="IdImage", referencedColumnName="Id")}
     * )
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    protected $images;

    /**
     * @ORM\ManyToMany(targetEntity="Newscoop\Entity\Snippet", inversedBy="articles")
     * @ORM\JoinTable(name="ArticleSnippets",
     *      joinColumns={@ORM\JoinColumn(name="ArticleNr", referencedColumnName="Number")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="SnippetId", referencedColumnName="Id")}
     *  )
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    protected $snippets;

    /**
     * @var ArticleData
     */
    protected $data;

    /**
     * @param int                      $number
     * @param Newscoop\Entity\Language $language
     */
    public function __construct($number, Language $language)
    {
        $this->number = (int) $number;
        $this->language = $language;
        $this->updated = new DateTime();
        $this->authors = new ArrayCollection();
        $this->topics = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->snippets = new ArrayCollection();
    }

    /**
     * Set article id
     *
     * @param  int     $p_id
     * @return Article
     */
    public function setId($p_id)
    {
        $this->number = $p_id;
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get article id
     *
     * @return int
     */
    public function getId()
    {
        return $this->number;
    }

    /**
     * Get article name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the value of name.
     *
     * @param string $name the name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set publication
     *
     * @param  Publication $p_publication
     * @return Article
     */
    public function setPublication(Publication $p_publication)
    {
        $this->publication = $p_publication;
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get publication
     *
     * @return Newscoop\Entity\Publication
     */
    public function getPublication()
    {
        return $this->publication;
    }

    /**
     * Get publication id
     *
     * @return int
     */
    public function getPublicationId()
    {
        return ($this->publication) ? $this->publication->getId() : null;
    }

    /**
     * Getter for issue
     *
     * @return \Newscoop\Entity\Issue
     */
    public function getIssue()
    {
        return $this->issue;
    }

    /**
     * Setter for issue
     *
     * @param \Newscoop\Entity\Issue $issue Value to set
     *
     * @return self
     */
    public function setIssue(\Newscoop\Entity\Issue $issue)
    {
        $this->issue = $issue;

        return $this;
    }

    /**
     * Get section
     *
     * @return \Newscoop\Entity\Section
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * Setter for section
     *
     * @param Newscoop\Entity\Section $section
     *
     * @return self
     */
    public function setSection(\Newscoop\Entity\Section $section)
    {
        $this->section = $section;

        return $this;
    }

    /**
     * Set workflowStatus
     *
     * @param  string $status
     * @return void
     */
    public function setWorkflowStatus($workflowStatus)
    {
        $this->workflowStatus = (string) $workflowStatus;

        return $this;
    }

    /**
     * Get workflowStatus
     *
     * @return string
     */
    public function getWorkflowStatus($readable = false)
    {
        $translator = \Zend_Registry::get('container')->getService('translator');
        $readableStatus = array(
            self::STATUS_PUBLISHED => $translator->trans('published'),
            self::STATUS_NOT_PUBLISHED => $translator->trans('unpublished'),
            self::STATUS_SUBMITTED => $translator->trans('submitted'),
        );

        if ($readable) {
            return $readableStatus[$this->workflowStatus];
        }

        return $this->workflowStatus;
    }

    /**
     * Set language
     *
     * @param  Newscoop\Entity\Language $p_language
     * @return Newscoop\Entity\Article
     */
    public function setLanguage(Language $p_language)
    {
        $this->language = $p_language;
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get language
     *
     * @return Newscoop\Entity\Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Get language id
     *
     * @return int
     */
    public function getLanguageId()
    {
        return ($this->language) ? $this->language->getId() : null;
    }

    /**
     * Get language code
     *
     * @return int
     */
    public function getLanguageCode()
    {
        return ($this->language) ? $this->language->getCode() : null;
    }

    /**
     * Get number
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set number
     *
     * @return int
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->name;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->name = $title;

        return $this;
    }

    /**
     * Get date
     *
     * @return string
     */
    public function getDate()
    {
        return $this->getUpdated();
    }

    /**
     * Set date
     *
     * @param  DateTime $updated
     * @return void
     */
    public function setDate(DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * Set data
     *
     * @param  array $data
     * @return void
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get article type field data
     *
     * @param  string $field
     * @return mixed
     */
    public function getData($field)
    {
        if ($this->data === null) {
            $this->data = new \ArticleData($this->type, $this->number, $this->getLanguageId());
        }

        if ($field == null) {
            return $this->data;
        }

        if (is_array($this->data)) {
            return array_key_exists($field, $this->data) ? $this->data[$field] : null;
        } else {
            return $this->data->getFieldValue($field);
        }
    }

    /**
     * Set article type field data
     *
     * @param  string $field
     * @param  string $value
     * @return mixed
     */
    public function setFieldData($field, $value)
    {
        if ($this->data === null) {
            $this->data = new \ArticleData($this->type, $this->number, $this->getLanguageId());
            $this->data->create();
        }

        return $this->data->setProperty('F'.$field, $value);
    }

    /**
     * Get all field names for this article type
     *
     * @return mixed Returns array with field names or null
     */
    public function getFieldNames()
    {
        if ($this->data === null) {
            return null;
        }

        if (is_array($this->data)) {
            return array_keys($this->data);
        } else {
            return $this->data->getUserDefinedColumns(true);
        }
    }

    /**
     * Get whether commenting is enabled
     *
     * @return int
     */
    public function commentsEnabled()
    {
        return $this->getCommentsEnabled();
    }

    /**
     * Get whether commenting is enabled
     *
     * @return int
     */
    public function getCommentsEnabled()
    {
        return (int) $this->comments_enabled;
    }

    /**
     * Set commenting en/disabled
     *
     * @param int $comments_enabled
     */
    public function setCommentsEnabled($comments_enabled)
    {
        $this->comments_enabled = (int) $comments_enabled;

        return $this;
    }

    /**
     * Getter for commentsLocked
     *
     * @return mixed
     */
    public function commentsLocked()
    {
        return $this->getCommentsLocked();
    }

    /**
     * Getter for commentsLocked
     *
     * @return mixed
     */
    public function getCommentsLocked()
    {
        return $this->commentsLocked;
    }

    /**
     * Setter for commentsLocked
     *
     * @param mixed $commentsLocked Value to set
     *
     * @return self
     */
    public function setCommentsLocked($commentsLocked)
    {
        $this->commentsLocked = (int) $commentsLocked;

        return $this;
    }

    /**
     * Set comments_link
     * @param string $link uri for comments resource in Newscoop API
     */
    public function setCommentsLink($link)
    {
        $this->comments_link = $link;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Getter for updated
     *
     * @return mixed
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Setter for updated
     *
     * @param DateTime $updated Value to set
     *
     * @return self
     */
    public function setUpdated(DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get publishDate
     *
     * @return string
     */
    public function getPublishDate()
    {
        return $this->getPublished();
    }

    /**
     * Get published
     *
     * @return string
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * Set published
     *
     * @param \Datetime|null $published
     *
     * @return self
     */
    public function setPublished($published)
    {
        $this->published = $published;

        return $this;
    }

    /**
     * Test if article is published
     *
     * @return bool
     */
    public function isPublished()
    {
        return $this->workflowStatus === self::STATUS_PUBLISHED;
    }

    /**
     * Set creator
     *
     * @param  User    $p_user
     * @return Article
     */
    public function setCreator(User $p_user)
    {
        $this->creator = $p_user;
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get creator
     *
     * @return Newscoop\Entity\User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set webcode
     *
     * @param Newscoop\Entity\Webcode $webcode
     *
     * @return void
     */
    public function setWebcode($webcode)
    {
        $this->webcode = $webcode;
    }

    /**
     * Get webcode
     *
     * @return string
     */
    public function getWebcode()
    {
        if (!$this->webcode) {
            return null;
        }

        return $this->webcode->getWebcode();
    }

    /**
     * Test if article has webcode
     *
     * @return bool
     */
    public function hasWebcode()
    {
        return isset($this->webcode);
    }

    /**
     * Get keywords
     *
     * @return string
     */
    public function getKeywords()
    {
        if (!$this->keywords) {
            return null;
        }

        return (string) $this->keywords;
    }

    /**
     * Set Keywords
     *
     * $keywords
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * Set articleAuthors
     * $articleAuthors
     */
    public function setArticleAuthors($articleAuthors)
    {
        $this->articleAuthors = $articleAuthors;
    }

    /**
     * Get articleAuthors
     *
     * @return object
     */
    public function getArticleAuthors()
    {
        if (!$this->articleAuthors) {
            $this->articleAuthors = $this->authors;
        }

        return $this->articleAuthors;
    }

    /**
     * Set Packages
     * $packages
     */
    public function setPackages($packages)
    {
        $this->packages = $packages;
    }

    /**
     * Get packages
     *
     * @return object
     */
    public function getPackages()
    {
        if (count($this->packages) == 0) {
            return null;
        }

        return $this->packages;
    }

    /**
     * Set Topics
     * $topics
     */
    public function setTopics($topics)
    {
        $this->topics = $topics;
    }

    /**
     * Get topics
     *
     * @return object
     */
    public function getTopics()
    {
        if (count($this->topics) == 0) {
            return null;
        }

        return $this->topics;
    }

    /**
     * Add Topic to the Article
     *
     * @param Topic $topic the Topic to attach
     *
     * @return boolean
     */
    public function addTopic(\Newscoop\NewscoopBundle\Entity\Topic $topic)
    {

        if (!$this->topics->contains($topic)) {
            $this->topics->add($topic);
            $topic->addArticleTopic($this);

            return true;
        }

        return false;
    }

    /**
     * Remove a Topic from the Article
     *
     * @param Topic $topic the Topic to remove
     *
     * @return boolean
     */
    public function removeTopic(\Newscoop\NewscoopBundle\Entity\Topic $topic)
    {
        if ($this->topics->contains($topic)) {
            $this->topics->removeElement($topic);
            $topic->removeArticleTopic($this);

            return true;
        }

        return false;
    }

    /**
     * Get topic names
     *
     * @return array
     */
    public function getTopicNames()
    {
        $names = array();
        foreach ($this->topics as $topic) {
            $names[$topic->getTopicId()] = $topic->getName($this->getLanguage());
        }

        return array_filter($names);
    }

    /**
     * Set Fields
     * $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * Get fields
     *
     * @return object
     */
    public function getFields()
    {
        if (count($this->fields) == 0) {
            return null;
        }

        return $this->fields;
    }

    /**
     * Set translations
     * $translations
     */
    public function setTranslations($translations)
    {
        $this->translations = $translations;
    }

    /**
     * Get translations
     *
     * @return object
     */
    public function getTranslations()
    {
        if (count($this->translations) == 0) {
            return null;
        }

        return $this->translations;
    }

    /**
     * Set renditions
     * $renditions
     */
    public function setRenditions($renditions)
    {
        $this->renditions = $renditions;
    }

    /**
     * Get renditions
     *
     * @return object
     */
    public function getRenditions()
    {
        if (count($this->renditions) == 0) {
            return null;
        }

        return $this->renditions;
    }

    /**
     * Get reads
     * @param int $reads
     */
    public function setReads($reads)
    {
        $this->reads = $reads;

        return $this;
    }

    /**
     * Set reads
     * @return int
     */
    public function getReads()
    {
        $requestObject = new \RequestObject($this->objectId);

        return $requestObject->getRequestCount();
    }

    /*
     * Publish article
     *
     * @return void
     */
    public function publish()
    {
        $this->workflowStatus = self::STATUS_PUBLISHED;
        $this->published = new DateTime();
    }

    /**
     * Get indexed
     *
     * @return DateTime
     */
    public function getIndexed()
    {
        return $this->indexed;
    }

    /**
     * Set indexed
     *
     * @param DateTime $indexed
     *
     * @return self
     */
    public function setIndexed(DateTime $indexed = null)
    {
        $this->indexed = $indexed;

        return $this;
    }

    /**
     * Author article
     *
     * @param  string $title
     * @param  array  $fields
     * @return void
     */
    public function author($title, array $fields)
    {
        $this->name = (string) $title;

        foreach ($fields as $key => $val) {
            $this->setFieldValue($key, $val);
        }
    }

    /**
     * Getter for attachments
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getAttachments()
    {
        if (count($this->attachments) == 0) {
            return null;
        }

        return $this->attachments;
    }

    /**
     * Setter for attachments
     *
     * @param Doctrine\Common\Collections\ArrayCollection|null $attachments Value to set
     *
     * @return self
     */
    public function setAttachments($attachments)
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * Setter for attachments
     *
     * @param Attachment $attachment
     *
     * @return self
     */
    public function addAttachment(Attachment $attachment)
    {
        $this->attachments->add($attachment);

        return $this;
    }

    /**
     * Getter for images
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * Get article first image
     *
     * @return Newscoop\Image\LocalImage
     */
    public function getFirstImage()
    {
        return ($this->getImages()->isEmpty()) ? null : $this->getImages()->first();
    }

    /**
     * Setter for images
     *
     * @param Doctrine\Common\Collections\ArrayCollection $images Value to set
     *
     * @return self
     */
    public function setImages(\Doctrine\Common\Collections\ArrayCollection $images)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * Get view
     *
     * @return object
     */
    public function getView()
    {
        try {
            $view = new ArticleView(array(
                'number' => $this->number,
                'language' => $this->language->getCode(),
                'languageId' => $this->language->getId(),
                'title' => $this->name,
                'updated' => $this->updated,
                'published' => $this->published,
                'indexed' => $this->indexed,
                'onFrontPage' => $this->onFrontPage,
                'onSection' => $this->onSection,
                'type' => $this->type,
                'webcode' => $this->getWebcode(),
                'publication_number' => $this->publication ? $this->publication->getId() : null,
                'issue_number' => $this->issue ? $this->issue->getNumber() : null,
                'section_number' => $this->section ? $this->section->getNumber() : null,
                'keywords' => array_filter(explode(',', $this->keywords)),
            ));
        } catch (EntityNotFoundException $e) {
            return new ArticleView();
        }

        $view->authors = $this->authors->map(function ($author) { return $author->getView()->name; })
            ->toArray();

        $view->topics = $this->topics->map(function ($topic) { return $topic->getView()->name; })
            ->toArray();

        $this->addFields($view);

        return $view;
    }

    /**
     * Set article type field value
     *
     * @param  string $field
     * @param  string $value
     * @return void
     */
    private function setFieldValue($field, $value)
    {
        $this->initArticleData();
        $this->data->setProperty('F' . $field, $value);
    }

    /**
     * Add field properties to view
     *
     * @return array
     */
    private function addFields(ArticleView $view)
    {
        $this->initArticleData();
        foreach ($this->data->getUserDefinedColumns(true) as $column) {
            $columnName = $column->getPrintName();
            if (!property_exists($view, $columnName)) {
                $view->$columnName = $this->data->getFieldValue($columnName);
            }
        }
    }

    /**
     * Init ArticleData
     *
     * @return ArticleData
     */
    private function initArticleData()
    {
        if ($this->data === null) {
            $this->data = new ArticleData($this->type, $this->number, $this->getLanguageId());
            $this->data->create();
        }

        return $this->data;
    }

    public function getObject()
    {
        return clone $this;
    }

    /**
     * Get Article Snippets
     *
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    public function getSnippets()
    {
        return $this->snippets;
    }

    /**
     * Add a Snippet to the Article
     *
     * @param Snippet $snippet the Snippet to attach
     *
     * @return Newscoop\Entity\Article
     */
    public function addSnippet(Snippet $snippet)
    {
        if (!$this->snippets->contains($snippet)) {
            $this->snippets->add($snippet);
            $snippet->addArticle($this);
        }

        return $this;
    }

    /**
     * Remove a Snippet from the Article
     *
     * @param Snippet $snippet the Snippet to remove
     *
     * @return Newscoop\Entity\Article
     */
    public function removeSnippet(Snippet $snippet)
    {
        if ($this->snippets->contains($snippet)) {
            $this->snippets->removeElement($snippet);
            $snippet->removeArticle($this);
        }

        return $this;
    }

    /**
     * Get language object
     *
     * @return Newscoop\Entity\Language
     */
    public function getLanguageObject()
    {
        return $this->getLanguage();
    }

    /**
     * Checks if article is locked or not
     *
     * @return boolean
     */
    public function isLocked()
    {
        if ((null === $this->getLockUser()) && ($this->getLockTime() === null)) {
            return false;
        }

        return true;
    }

    /**
     * Gets the value of lockUser.
     *
     * @return Newscoop\Entity\User
     */
    public function getLockUser()
    {
        return $this->lockUser;
    }

    /**
     * Sets the value of lockUser.
     *
     * @param Newscoop\Entity\User $lockUser the lock user
     *
     * @return self
     */
    public function setLockUser(User $lockUser = null)
    {
        $this->lockUser = $lockUser;

        return $this;
    }

    /**
     * Gets the value of lockTime.
     *
     * @return DateTime
     */
    public function getLockTime()
    {
        if (null === $this->lockTime) {
            return null;
        }

        return $this->lockTime;
    }

    /**
     * Sets the value of lockTime.
     *
     * @param DateTime $lockTime the lock time
     *
     * @return self
     */
    public function setLockTime(DateTime $lockTime = null)
    {
        $this->lockTime = $lockTime;

        return $this;
    }

    /**
     * Gets the time difference between current and article lock time
     *
     * @return array
     */
    public function getLockTimeDiffrence()
    {
        $time1 = $this->getLockTime();
        $sinceStart = $time1->diff(new DateTime());

        return array(
            'days' => $sinceStart->d,
            'hours' => $sinceStart->h,
            'minutes' => $sinceStart->i,
            'seconds' => $sinceStart->s
        );
    }

    /**
     * Gets the value of articleOrder.
     *
     * @return int
     */
    public function getArticleOrder()
    {
        return $this->articleOrder;
    }

    /**
     * Sets the value of articleOrder.
     *
     * @param int $articleOrder the article order
     *
     * @return self
     */
    public function setArticleOrder($articleOrder)
    {
        $this->articleOrder = $articleOrder;

        return $this;
    }

    /**
     * Gets the value of shortName.
     *
     * @return string
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * Sets the value of shortName.
     *
     * @param string $shortName the short name
     *
     * @return self
     */
    public function setShortName($shortName)
    {
        $this->shortName = $shortName;

        return $this;
    }

    /**
     * Sets the value of type.
     *
     * @param string $type the type
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets the value of public.
     *
     * @return string
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * Sets the value of public.
     *
     * @param boolean $public the public
     *
     * @return self
     */
    public function setPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Gets the value of onFrontPage.
     *
     * @return string
     */
    public function getOnFrontPage()
    {
        return $this->onFrontPage;
    }

    /**
     * Sets the value of onFrontPage.
     *
     * @param string $onFrontPage the on front page
     *
     * @return self
     */
    public function setOnFrontPage($onFrontPage = false)
    {
        if (is_bool($onFrontPage) || is_int($onFrontPage)) {
            if ($onFrontPage) {
                $this->onFrontPage = 'Y';
            } else {
                $this->onFrontPage = 'N';
            }

            return $this;
        }

        $this->onFrontPage = $onFrontPage;

        return $this;
    }

    /**
     * Gets the value of onSection.
     *
     * @return string
     */
    public function getOnSection()
    {
        return $this->onSection;
    }

    /**
     * Sets the value of onSection.
     *
     * @param string $onSection the on section
     *
     * @return self
     */
    public function setOnSection($onSection = false)
    {
        if (is_bool($onSection) || is_int($onSection)) {
            if ($onSection) {
                $this->onSection = 'Y';
            } else {
                $this->onSection = 'N';
            }

            return $this;
        }

        $this->onSection = $onSection;

        return $this;
    }

    /**
     * Gets the value of uploaded.
     *
     * @return DateTime
     */
    public function getUploaded()
    {
        return $this->uploaded;
    }

    /**
     * Sets the value of uploaded.
     *
     * @param DateTime $uploaded the uploaded
     *
     * @return self
     */
    public function setUploaded(DateTime $uploaded)
    {
        $this->uploaded = $uploaded;

        return $this;
    }

    /**
     * Gets the value of isIndexed.
     *
     * @return string
     */
    public function getIsIndexed()
    {
        return $this->isIndexed;
    }

    /**
     * Sets the value of isIndexed.
     *
     * @param boolean $isIndexed the is indexed
     *
     * @return self
     */
    public function setIsIndexed($isIndexed)
    {
        $this->isIndexed = $isIndexed;

        return $this;
    }

    /**
     * Gets the value of issueId.
     *
     * @return int
     */
    public function getIssueId()
    {
        return $this->issueId;
    }

    /**
     * Sets the value of issueId.
     *
     * @param int $issueId the issue id
     *
     * @return self
     */
    public function setIssueId($issueId)
    {
        $this->issueId = $issueId;

        return $this;
    }

    /**
     * Gets the value of sectionId.
     *
     * @return int
     */
    public function getSectionId()
    {
        return $this->sectionId;
    }

    /**
     * Sets the value of sectionId.
     *
     * @param int $sectionId the section id
     *
     * @return self
     */
    public function setSectionId($sectionId)
    {
        $this->sectionId = $sectionId;

        return $this;
    }
}
