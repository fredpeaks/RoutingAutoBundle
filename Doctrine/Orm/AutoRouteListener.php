<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Symfony\Cmf\Bundle\RoutingAutoBundle\Doctrine\Orm;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Symfony\Cmf\Bundle\RoutingBundle\Resolver\OrmContentCodeResolver;
use Symfony\Cmf\Component\RoutingAuto\AutoRouteManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Cmf\Bundle\RoutingAutoBundle\Doctrine\Orm\AutoRoute;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Cmf\Component\RoutingAuto\UriContextCollection;
use Symfony\Cmf\Component\RoutingAuto\Mapping\Exception\ClassNotMappedException;

/**
 * Doctrine ORM listener for maintaining automatic routes.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class AutoRouteListener
{
    /** @var OrmContentCodeResolver */
    protected $contentResolver;
    protected $postFlushDone = false;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->contentResolver = $container->get('cmf_routing.orm_content_code_resolver');
    }

    /**
     * @return AutoRouteManager
     */
    protected function getAutoRouteManager()
    {
        // lazy load the auto_route_manager service to prevent a cirular-reference
        // to the document manager.
        return $this->container->get('cmf_routing_auto.auto_route_manager');
    }

    protected function getMetadataFactory()
    {
        return $this->container->get('cmf_routing_auto.metadata.factory');
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        /** @var $om EntityManager */
        $om = $args->getEntityManager();
        $uow = $om->getUnitOfWork();
        $arm = $this->getAutoRouteManager();
        $this->contentResolver->setEntityManager($om);
        $scheduledInserts = $uow->getScheduledEntityInsertions();
        $scheduledUpdates = $uow->getScheduledEntityUpdates();
        $updates = array_merge($scheduledInserts, $scheduledUpdates);

        $autoRoute = null;
        foreach ($updates as $document) {
            if ($this->isAutoRouteable($document)) {
                $locale = null;
                if(method_exists($document, 'getLocale')) {
                    $locale = $document->getLocale();
                }

                $uriContextCollection = new UriContextCollection($document);
                $arm->buildUriContextCollection($uriContextCollection);

                // refactor this.
                foreach ($uriContextCollection->getUriContexts() as $uriContext) {
                    $autoRoute = $uriContext->getAutoRoute();
                    $om->persist($autoRoute);
                    $uow->computeChangeSets();
                }

                // reset locale to the original locale
                if (null !== $locale) {
                    $document->setLocale($locale);
                    $om->refresh($document);
                }
            }
        }

        $removes = $uow->getScheduledCollectionDeletions();
        foreach ($removes as $document) {
            if ($this->isAutoRouteable($document)) {
                $referrers = $this->em->getRepository('Symfony\Cmf\Bundle\RoutingAutoBundle\Doctrine\Orm\AutoRoute')->findBy(array(
                    'contentCode' => $this->contentResolver->getContentCode($document)
                ));
                if($referrers) {
                    foreach ($referrers as $autoRoute) {
                        $uow->scheduleForDelete($autoRoute);
                    }
                }
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        $om = $args->getEntityManager();
        $arm = $this->getAutoRouteManager();
        $arm->handleDefunctRoutes();

        if (!$this->postFlushDone) {
            $this->postFlushDone = true;
            $om->flush();
        }

        $this->postFlushDone = false;
    }

    private function isAutoRouteable($document)
    {
        try {
            return (boolean) $this->getMetadataFactory()->getMetadataForClass(get_class($document));
        } catch (ClassNotMappedException $e) {
            return false;
        }
    }
}
