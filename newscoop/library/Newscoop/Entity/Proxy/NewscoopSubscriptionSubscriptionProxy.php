<?php

namespace Newscoop\Entity\Proxy;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class NewscoopSubscriptionSubscriptionProxy extends \Newscoop\Subscription\Subscription implements \Doctrine\ORM\Proxy\Proxy
{
    private $_entityPersister;
    private $_identifier;
    public $__isInitialized__ = false;
    public function __construct($entityPersister, $identifier)
    {
        $this->_entityPersister = $entityPersister;
        $this->_identifier = $identifier;
    }
    /** @private */
    public function __load()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;

            if (method_exists($this, "__wakeup")) {
                // call this after __isInitialized__to avoid infinite recursion
                // but before loading to emulate what ClassMetadata::newInstance()
                // provides.
                $this->__wakeup();
            }

            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister, $this->_identifier);
        }
    }
    
    
    public function getId()
    {
        $this->__load();
        return parent::getId();
    }

    public function setUser(\Newscoop\Entity\User $user)
    {
        $this->__load();
        return parent::setUser($user);
    }

    public function getUser()
    {
        $this->__load();
        return parent::getUser();
    }

    public function setPublication(\Newscoop\Entity\Publication $publication)
    {
        $this->__load();
        return parent::setPublication($publication);
    }

    public function getPublication()
    {
        $this->__load();
        return parent::getPublication();
    }

    public function getPublicationName()
    {
        $this->__load();
        return parent::getPublicationName();
    }

    public function getPublicationId()
    {
        $this->__load();
        return parent::getPublicationId();
    }

    public function setToPay($toPay)
    {
        $this->__load();
        return parent::setToPay($toPay);
    }

    public function getToPay()
    {
        $this->__load();
        return parent::getToPay();
    }

    public function setType($type)
    {
        $this->__load();
        return parent::setType($type);
    }

    public function getType()
    {
        $this->__load();
        return parent::getType();
    }

    public function isTrial()
    {
        $this->__load();
        return parent::isTrial();
    }

    public function setActive($active)
    {
        $this->__load();
        return parent::setActive($active);
    }

    public function isActive()
    {
        $this->__load();
        return parent::isActive();
    }

    public function addSections(array $values, \Newscoop\Entity\Publication $publication)
    {
        $this->__load();
        return parent::addSections($values, $publication);
    }

    public function setSections(array $values)
    {
        $this->__load();
        return parent::setSections($values);
    }

    public function addSection(\Newscoop\Subscription\Section $section)
    {
        $this->__load();
        return parent::addSection($section);
    }

    public function getSections()
    {
        $this->__load();
        return parent::getSections();
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'id', 'user', 'publication', 'toPay', 'type', 'currency', 'active', 'sections');
    }

    public function __clone()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            $class = $this->_entityPersister->getClassMetadata();
            $original = $this->_entityPersister->load($this->_identifier);
            if ($original === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            foreach ($class->reflFields AS $field => $reflProperty) {
                $reflProperty->setValue($this, $reflProperty->getValue($original));
            }
            unset($this->_entityPersister, $this->_identifier);
        }
        
    }
}