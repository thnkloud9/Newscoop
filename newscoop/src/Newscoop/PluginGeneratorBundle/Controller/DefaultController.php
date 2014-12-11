<?php

namespace Newscoop\PluginGeneratorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('NewscoopPluginGeneratorBundle:Default:index.html.twig', array('name' => $name));
    }
}
