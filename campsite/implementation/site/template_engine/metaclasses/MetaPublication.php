<?php
/**
 * @package Campsite
 */

/**
 * Includes
 */
// We indirectly reference the DOCUMENT_ROOT so we can enable
// scripts to use this file from the command line, $_SERVER['DOCUMENT_ROOT']
// is not defined in these cases.
$g_documentRoot = $_SERVER['DOCUMENT_ROOT'];

require_once($g_documentRoot.'/classes/Publication.php');
require_once($g_documentRoot.'/classes/Alias.php');
require_once($g_documentRoot.'/template_engine/metaclasses/MetaDbObject.php');

/**
 * @package Campsite
 */
final class MetaPublication extends MetaDbObject {

    private function InitProperties()
    {
        if (!is_null($this->m_properties)) {
            return;
        }
        $this->m_properties['name'] = 'Name';
        $this->m_properties['identifier'] = 'Id';
        $this->m_properties['subscription_currency'] = 'Currency';
        $this->m_properties['subscription_trial_time'] = 'TrialTime';
        $this->m_properties['subscription_paid_time'] = 'PaidTime';
        $this->m_properties['subscription_unit_cost'] = 'UnitCost';
        $this->m_properties['subscription_unit_cost_all_lang'] = 'UnitCostAllLang';
    }


    public function __construct($p_publicationId = null)
    {
        $this->m_dbObject = new Publication($p_publicationId);
        if (!$this->m_dbObject->exists()) {
            $this->m_dbObject = new Publication();
        }
        
        $this->InitProperties();
        $this->m_customProperties['site'] = 'getDefaultSiteName';
        $this->m_customProperties['defined'] = 'defined';
        $this->m_customProperties['default_language'] = 'getDefaultLanguage';
        $this->m_customProperties['public_comments'] = 'getPublicComments';
        $this->m_customProperties['moderated_comments'] = 'getModeratedComments';
        $this->m_customProperties['captcha_enabled'] = 'getCAPTCHAEnabled';
        $this->m_customProperties['subscription_time_unit'] = 'getSubscriptionTimeUnit';
        $this->m_customProperties['subscription_time'] = 'getSubscriptionTime';
    } // fn __construct


    /**
     * Returns a list of MetaLanguage objects - list of languages in which
     * the issue was translated.
     * 
     * @param boolean $p_excludeCurrent
     * @param array $p_order
     * @param boolean $p_allIssues
     * @return array of MetaLanguage
     */
    public function languages_list($p_excludeCurrent = true,
    array $p_order = array()) {
    	if ($p_excludeCurrent) {
    		$context = CampTemplate::singleton()->context();
    		$languageId = $context->language->number;
    	} else {
    		$languageId = null;
    	}
        $languages = $this->m_dbObject->getLanguages($languageId, $p_order,
        !CampTemplate::singleton()->context()->preview);
        $metaLanguagesList = array();
        foreach ($languages as $language) {
            $metaLanguagesList[] = new MetaLanguage($language->getLanguageId());
        }
        return $metaLanguagesList;
    }


    protected function getDefaultSiteName()
    {
        $defaultAlias = new Alias($this->m_dbObject->getDefaultAliasId());
        if (!$defaultAlias->exists()) {
            return null;
        }
        return $defaultAlias->getName();
    }


    protected function getDefaultLanguage()
    {
        return new MetaLanguage($this->m_dbObject->getDefaultLanguageId());
    }


    protected function getPublicComments() {
        return $this->m_dbObject->publicComments();
    }


    protected function getModeratedComments() {
        if (CampTemplate::singleton()->context()->user->logged_in) {
            return $this->m_dbObject->commentsSubscribersModerated();
        } else {
            return $this->m_dbObject->commentsPublicModerated();
        }
    }


    protected function getCAPTCHAEnabled() {
        return $this->m_dbObject->isCaptchaEnabled();
    }


    protected function getSubscriptionTimeUnit() {
        return $this->m_dbObject->getTimeUnitName(CampTemplate::singleton()->context()->language->number);
    }


    protected function getSubscriptionTime() {
        if (strtolower(CampRequest::GetVar('SubsType')) == 'trial') {
            return $this->subscription_trial_time;
        } elseif (strtolower(CampRequest::GetVar('SubsType')) == 'paid') {
            return $this->subscription_paid_time;
        }
        return null;
    }
} // class MetaPublication

?>