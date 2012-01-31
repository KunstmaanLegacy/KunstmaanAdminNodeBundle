<?php

namespace Kunstmaan\AdminNodeBundle\Repository;

use Kunstmaan\AdminNodeBundle\Entity\HasNode;

use Kunstmaan\AdminNodeBundle\Entity\Node;
use Kunstmaan\AdminNodeBundle\Entity\NodeTranslation;
use Kunstmaan\AdminBundle\Entity\PageIFace;
use Kunstmaan\AdminBundle\Modules\ClassLookup;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * NodeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class NodeTranslationRepository extends EntityRepository
{
	public function getChildren(Node $node){
		return $this->findBy(array("parent"=>$node->getId()));
	}

	public function getNodeFor(HasNode $hasNode) {
		return $this->findOneBy(array('refId' => $hasNode->getId(), 'refEntityname' => ClassLookup::getClass($hasNode)));
	}

	public function getNodeTranslationForSlug(NodeTranslation $parentNode = null, $slug){
		$slugparts = explode("/", $slug);
		$result = $parentNode;
		foreach($slugparts as $slugpart){
			$result = $this->getNodeTranslationForSlugPart($result, $slugpart);
		}
		return $result;
	}

	private function getNodeTranslationForSlugPart(NodeTranslation $parentNode = null, $slugpart) {
		if($parentNode != null){
			$qb = $this->createQueryBuilder('b')
				->select('b')
				->innerJoin('b.node', 'n', 'WITH', 'b.node = n.id')
				->where('b.slug = ?1 and n.parent = ?2')
				->addOrderBy('n.sequencenumber', 'DESC')
				->setFirstResult( 0 )
				->setMaxResults( 1 )
				->setParameter(1, $slugpart)
				->setParameter(2, $parentNode->getNode()->getId());
			$result = $qb->getQuery()->getResult();
			if(sizeof($result)==1){
				return $result[0];
			} else if (sizeof($result==0)){
				return null;
			} else {
				//more then one result found
				return $result[0];
			}
		} else {
			if($r = $this->findOneBy(array('slug' => $slugpart))){
				return $r;
			}
		}
	}

	public function createNodeTranslationFor(HasNode $hasNode, $lang, Node $node, $owner){
		$em = $this->getEntityManager();
		$classname = ClassLookup::getClass($hasNode);
		if(!$hasNode->getId()>0){
			throw new \Exception("the entity of class ". $classname . " has no id, maybe you forgot to flush first");
		}
		$entityrepo = $em->getRepository($classname);
		$nodeTranslation = new NodeTranslation();
		$nodeTranslation->setNode($node);
		$nodeTranslation->setLang($lang);
		$nodeTranslation->setTitle($hasNode->__toString());
		$nodeTranslation->setSlug(strtolower(str_replace(" ", "-", $hasNode->__toString())));
		$nodeTranslation->setOnline($hasNode->isOnline());
		$em->persist($nodeTranslation);
		$em->flush();
		$nodeVersion = $em->getRepository('KunstmaanAdminNodeBundle:NodeVersion')->createNodeVersionFor($hasNode, $nodeTranslation, $owner);
		$nodeTranslation->setPublicNodeVersion($nodeVersion);
		$em->persist($nodeTranslation);
		$em->flush();
		return $nodeTranslation;
	}
}