<?php

namespace Newscoop\Entity\Proxy;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class NewscoopEntityArticleTypeFieldProxy extends \Newscoop\Entity\ArticleTypeField implements \Doctrine\ORM\Proxy\Proxy
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
    
    
    public function getArticleType()
    {
        $this->__load();
        return parent::getArticleType();
    }

    public function setArticleType(\Newscoop\Entity\ArticleType $type)
    {
        $this->__load();
        return parent::setArticleType($type);
    }

    public function setArticleTypeHack(\Newscoop\Entity\ArticleType $type)
    {
        $this->__load();
        return parent::setArticleTypeHack($type);
    }

    public function setName($name)
    {
        $this->__load();
        return parent::setName($name);
    }

    public function getName()
    {
        $this->__load();
        return parent::getName();
    }

    public function getLength()
    {
        $this->__load();
        return parent::getLength();
    }

    public function setLength($val)
    {
        $this->__load();
        return parent::setLength($val);
    }

    public function getType()
    {
        $this->__load();
        return parent::getType();
    }

    public function setType($val)
    {
        $this->__load();
        return parent::setType($val);
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'name', 'typeHack', 'length', 'type', 'fieldWeight', 'isHidden', 'commentsEnabled', 'phraseId', 'fieldTypeParam', 'isContentField');
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