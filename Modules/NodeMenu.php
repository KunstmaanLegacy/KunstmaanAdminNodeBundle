<?php

namespace Kunstmaan\AdminNodeBundle\Modules;

use Kunstmaan\AdminNodeBundle\Entity\Node;
use Kunstmaan\AdminNodeBundle\Entity\NodeTranslation;
use Symfony\Component\Translation\Translator;
use Knp\Menu\FactoryInterface;

class NodeMenu {
    private $em;
    private $lang;
    private $topNodeMenuItems = array();
    private $breadCrumb = array();
    private $container = null;
    private $includeoffline = false;

    /**
     * @param FactoryInterface $factory
     */
    public function __construct($container, $lang, Node $currentNode = null, $permission = 'read', $includeoffline = false)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine.orm.entity_manager');
        $this->lang = $lang;
        $this->includeoffline = $includeoffline;
        $tempNode = $currentNode;

        //Breadcrumb
        $nodeBreadCrumb = array();
        while($tempNode){
        	array_unshift($nodeBreadCrumb, $tempNode);
        	$tempNode = $tempNode->getParent();
        }
        $parentNodeMenuItem = null;
        foreach($nodeBreadCrumb as $nodeBreadCrumbItem){
        	$nodeTranslation = $nodeBreadCrumbItem->getNodeTranslation($lang, $this->includeoffline);
        	if(!is_null($nodeTranslation)){
        		$nodeMenuItem = new NodeMenuItem($this->em, $nodeBreadCrumbItem, $nodeTranslation, $lang, $parentNodeMenuItem, $this);
        		$this->breadCrumb[] = $nodeMenuItem;
        		$parentNodeMenuItem = $nodeMenuItem;
        	}
        }

        $permissionManager = $container->get('kunstmaan_admin.permissionmanager');
        $user = $this->container->get('security.context')->getToken()->getUser();

        $user = $permissionManager->getCurrentUser($user, $this->em);

        //topNodes
        $topNodes = $this->em->getRepository('KunstmaanAdminNodeBundle:Node')->getTopNodes($user, $permission);
        foreach($topNodes as $topNode){
        	$nodeTranslation = $topNode->getNodeTranslation($lang, $this->includeoffline);
        	if(!is_null($nodeTranslation)){
	        	if(sizeof($this->breadCrumb)>0 && $this->breadCrumb[0]->getNode()->getId() == $topNode->getId()){
	        		$this->topNodeMenuItems[] = $this->breadCrumb[0];
	        	} else {
	        		$this->topNodeMenuItems[] = new NodeMenuItem($this->em, $topNode, $nodeTranslation, $lang, null, $this);
	        	}
        	}
        }
    }

    public function getTopNodes(){
        return $this->topNodeMenuItems;
    }

    public function getCurrent(){
    	if(sizeof($this->breadCrumb)>0){
    		return $this->breadCrumb[sizeof($this->breadCrumb)-1];
    	}
    	return null;
    }

    public function getActiveForDepth($depth){
    	if(sizeof($this->breadCrumb)>=$depth){
    		return $this->breadCrumb[$depth-1];
    	}
    	return null;
    }

    public function getBreadCrumb(){
    	return $this->breadCrumb;
    }

    public function getNodeBySlug(NodeTranslation $parentNode, $slug){
    	return $this->em->getRepository('KunstmaanAdminNodeBundle:NodeTranslation')->getNodeTranslationForSlug($parentNode, $slug);
    }

    public function getNodeByInternalName($internalName) {
    	$node = $this->em->getRepository('KunstmaanAdminNodeBundle:Node')->findOneBy(array('internalName' => $internalName));
    	if(!is_null($node)){
    		return $node->getNodeTranslation($this->lang);
    	}
    	return null;
    }

    public function isIncludeOffline(){
    	return $this->includeoffline;
    }

}