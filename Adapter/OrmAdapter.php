<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Symfony\Cmf\Bundle\RoutingAutoBundle\Adapter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Gedmo\Translatable\Translatable;
use Symfony\Cmf\Bundle\RoutingBundle\Resolver\OrmContentCodeResolver;
use Symfony\Cmf\Component\RoutingAuto\Model\AutoRouteInterface;
use Symfony\Cmf\Component\RoutingAuto\UriContext;
use Symfony\Cmf\Bundle\RoutingBundle\Model\RedirectRoute;
use Symfony\Cmf\Component\RoutingAuto\AdapterInterface;
use Symfony\Cmf\Bundle\RoutingAutoBundle\Doctrine\Orm\AutoRoute;

/**
 * Adapter for ORM
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class OrmAdapter implements AdapterInterface
{
    const TAG_NO_MULTILANG = 'no-multilang';

    /** @var ObjectManager  */
    protected $em;
    /** @var OrmContentCodeResolver */
    protected $contentResolver;
    /** @var string */
    protected $autoRouteFqcn;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param string $managerName
     * @param OrmContentCodeResolver $contentResolver
     * @internal param string $autoRouteFqcn The FQCN of the AutoRoute document to use
     */
    public function __construct(ManagerRegistry $managerRegistry, $managerName, OrmContentCodeResolver $contentResolver, $autoRouteFqcn = 'Symfony\Cmf\Bundle\RoutingAutoBundle\Doctrine\Orm\AutoRoute')
    {
        $this->em = $managerRegistry->getManager($managerName);

        $reflection = new \ReflectionClass($autoRouteFqcn);
        if (!$reflection->isSubclassOf('Symfony\Cmf\Component\RoutingAuto\Model\AutoRouteInterface')) {
            throw new \InvalidArgumentException(sprintf('AutoRoute documents have to implement the AutoRouteInterface, "%s" does not.', $autoRouteFqcn));
        }

        $this->contentResolver = $contentResolver;
        $this->contentResolver->setEntityManager($this->em);
        $this->autoRouteFqcn = $autoRouteFqcn;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocales($contentDocument)
    {
        if ($contentDocument instanceof Translatable) {
            $repository = $this->em->getRepository('Gedmo\Translatable\Entity\Translation');
            $translations = $repository->findTranslations($contentDocument);

            return empty($translations) ? array() : array_keys($translations);
        }

        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function translateObject($contentDocument, $locale)
    {
        $contentDocument->setLocale($locale);
        $this->em->refresh($contentDocument);

        return $contentDocument;
    }

    /**
     * {@inheritDoc}
     */
    public function generateAutoRouteTag(UriContext $uriContext)
    {
        return $uriContext->getLocale() ? : self::TAG_NO_MULTILANG;
    }

    /**
     * {@inheritDoc}
     */
    public function migrateAutoRouteChildren(AutoRouteInterface $srcAutoRoute, AutoRouteInterface $destAutoRoute)
    {
        /*$session = $this->em->getPhpcrSession();
        $srcAutoRouteNode = $this->em->getNodeForDocument($srcAutoRoute);
        $destAutoRouteNode = $this->em->getNodeForDocument($destAutoRoute);

        $srcAutoRouteChildren = $srcAutoRouteNode->getNodes();

        foreach ($srcAutoRouteChildren as $srcAutoRouteChild) {
            $session->move($srcAutoRouteChild->getPath(), $destAutoRouteNode->getPath() . '/' . $srcAutoRouteChild->getName());
        }*/
    }

    /**
     * {@inheritDoc}
     */
    public function removeAutoRoute(AutoRouteInterface $autoRoute)
    {
        $this->em->remove($autoRoute);
        $this->em->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function createAutoRoute(UriContext $uri, $contentDocument, $autoRouteTag)
    {
        $segments = preg_split('#/#', $uriContext->getUri(), null, PREG_SPLIT_NO_EMPTY);
        $headName = array_pop($segments);
        /** @var AutoRoute $headRoute */
        $headRoute = new $this->autoRouteFqcn();
        $headRoute->setResolver($this->contentResolver);
        $headRoute->setStaticPrefix($uri);
        $headRoute->setContent($contentDocument);
        $headRoute->setName($headName);
        $headRoute->setAutoRouteTag($autoRouteTag);
        $headRoute->setType(AutoRouteInterface::TYPE_PRIMARY);

        return $headRoute;
    }

    /**
     * {@inheritDoc}
     */
    public function createRedirectRoute(AutoRouteInterface $referringAutoRoute, AutoRouteInterface $newRoute)
    {
        $referringAutoRoute->setRedirectTarget($newRoute);
        $referringAutoRoute->setType(AutoRouteInterface::TYPE_REDIRECT);
    }

    /**
     * {@inheritDoc}
     */
    public function getRealClassName($className)
    {
        return ClassUtils::getRealClass($className);
    }

    /**
     * {@inheritDoc}
     */
    public function compareAutoRouteContent(AutoRouteInterface $autoRoute, $contentDocument)
    {
        if ($autoRoute->getContent() === $contentDocument) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getReferringAutoRoutes($contentDocument, $field = 'id')
    {
        return $this->em->getRepository('Symfony\Cmf\Bundle\RoutingAutoBundle\Doctrine\Orm\AutoRoute')->findBy(array(
            'contentCode' => $this->contentResolver->getContentCode($contentDocument, $field)
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function findRouteForUri($uri, UriContext $uriContext)
    {
        return $this->em->getRepository('Symfony\Cmf\Bundle\RoutingAutoBundle\Doctrine\Orm\AutoRoute')->findOneBy(array(
            'staticPrefix' => $uri
        ));
    }
}
