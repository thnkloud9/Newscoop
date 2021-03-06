<?php

/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 */

namespace Newscoop\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Publication entity
 * @Entity(repositoryClass="Newscoop\Entity\Repository\PublicationRepository")
 * @Table(name="Publications")
 */
class Publication extends Entity
{
    /**
     * Provides the class name as a constant.
     */
    const NAME = __CLASS__;

    /* --------------------------------------------------------------- */

    /**
     * @id @generatedValue
     * @Column(name="Id", type="integer")
     * @var int
     */
    protected $id;

    /**
     * @Column(name="Name", nullable=True)
     * @var string
     */
    private $name;

    /**
     * @OneToOne(targetEntity="Newscoop\Entity\Language")
     * @JoinColumn(name="IdDefaultLanguage", referencedColumnName="Id")
     * @var Newscoop\Entity\Language
     */
    private $language;

    /**
     * @OneToMany(targetEntity="Newscoop\Entity\Issue", mappedBy="publication")
     * @var array
     */
    private $issues;

    /**
     * @column(name="comments_public_enabled", nullable=True)
     * @var bool
     */
    private $public_enabled;

    /**
     * @Column(name="comments_moderator_to", nullable=True)
     * @var string
     */
    private $moderator_to;

    /**
     * @Column(name="comments_moderator_from", nullable=True)
     * @var string
     */
    private $moderator_from;

    /**
     * @Column(name="TimeUnit", nullable=True)
     * @var string
     */
    private $timeUnit;

    /**
     * @Column(type="decimal", name="UnitCost", nullable=True)
     * @var float
     */
    private $unitCost;

    /**
     * @Column(type="decimal", name="UnitCostAllLang", nullable=True)
     * @var float
     */
    private $unitCostAll;

    /**
     * @Column(name="Currency", nullable=True)
     * @var string
     */
    private $currency;

    /**
     * @Column(type="integer", name="TrialTime", nullable=True)
     * @var int
     */
    private $trialTime;

    /**
     * @Column(type="integer", name="PaidTime", nullable=True)
     * @var int
     */
    private $paidTime;

    /**
     * @Column(type="integer", name="IdDefaultAlias", nullable=True)
     * @var int
     */
    private $defaultAliasId;

    /**
     * @Column(type="integer", name="IdURLType", nullable=True)
     * @var int
     */
    private $urlTypeId;

    /**
     * @Column(type="integer", name="fk_forum_id", nullable=True)
     * @var int
     */
    private $forumId;

    /**
     * @Column(type="boolean", name="comments_enabled", nullable=True)
     * @var bool
     */
    private $commentsEnabled;

    /**
     * @Column(type="boolean", name="comments_article_default_enabled", nullable=True)
     * @var bool
     */
    private $commentsArticleDefaultEnabled;

    /**
     * @Column(type="boolean", name="comments_subscribers_moderated", nullable=True)
     * @var bool
     */
    private $commentsSubscribersModerated;

    /**
     * @Column(type="boolean", name="comments_public_moderated", nullable=True)
     * @var bool
     */
    private $commentsPublicModerated;

    /**
     * @Column(type="boolean", name="comments_captcha_enabled", nullable=True)
     * @var bool
     */
    private $commentsCaptchaEnabled;

    /**
     * @Column(type="boolean", name="comments_spam_blocking_enabled", nullable=True)
     * @var bool
     */
    private $commentsSpamBlockingEnabled;

    /**
     * @Column(type="integer", name="url_error_tpl_id", nullable=True)
     * @var int
     */
    private $urlErrorTemplateId;

    /**
     * @Column(nullable=True)
     * @var int
     */
    private $seo;

    /**
     */
    public function __construct()
    {
        $this->issues = new ArrayCollection;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * Add issue
     *
     * @param Newscoop\Entity\Issue $issue
     * @return void
     */
    public function addIssue(Issue $issue)
    {
        if (!$this->issues->contains($issue)) {
            $this->issues->add($issue);
        }
    }

    /**
     * Get issues
     *
     * @return array
     */
    public function getIssues()
    {
        return $this->issues;
    }

    /**
     * Get languages
     *
     * @return array
     */
    public function getLanguages()
    {
        $languages = array();
        foreach ($this->issues as $issue) {
            $languages[$issue->getLanguage()->getId()] = $issue->getLanguage();
        }

        return array_values($languages);
    }

    /**
     * Set default language
     *
     * @param Newscoop\Entity\Language $language
     * @return void
     */
    public function setDefaultLanguage(Language $language)
    {
        $this->language = $language;
    }

    /**
     * Get default language of the publication
     *
     * @return Newscoop\Entity\Language
     */
    public function getDefaultLanguage()
    {
        return $this->language;
    }

    /**
     * Get default language name of the publication
     *
     * @return string
     */
    public function getDefaultLanguageName()
    {
        return $this->default_language->getName();
    }

    /*
     * Get sections
     *
     * @return array
     */
    public function getSections()
    {
        $added = array();
        $sections = array();
        foreach ($this->issues as $issue) {
            foreach ($issue->getSections() as $section) {
                if (in_array($section->getNumber(), $added)) { // @todo handle within repository
                    continue;
                }

                $sections[] = $section;
                $added[] = $section->getNumber();
            }
        }

        return $sections;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        return $this->id = $id;
    }

    /**
     * Set moderator to email address
     *
     * @param string $p_moderator_to
     * @return Publication
     */
    public function setModeratorTo($p_moderator_to)
    {
        return $this->moderator_to = $p_moderator_to;
    }

    /**
     * Get moderator to email address
     *
     * @return string
     */
    public function getModeratorTo()
    {
        return $this->moderator_to;
    }

    /**
     * Set moderator from email address
     *
     * @param string $p_moderator_from
     * @return Publication
     */
    public function setModeratorFrom($p_moderator_from)
    {
        return $this->moderator_to = $p_moderator_from;
    }

    /**
     * Get moderator from email address
     *
     * @return string
     */
    public function getModeratorFrom()
    {
        return $this->moderator_from;
    }
}

