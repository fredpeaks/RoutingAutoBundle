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

//use Symfony\Cmf\Bundle\RoutingAutoBundle\Model\AutoRouteTrait;
use Symfony\Cmf\Component\RoutingAuto\Model\AutoRouteInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\Route as BaseRoute;

/**
 * Sub class of Route to enable automatically generated routes
 * to be identified.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class AutoRoute extends BaseRoute implements AutoRouteInterface
{
    //use AutoRouteTrait;

    const DEFAULT_KEY_AUTO_ROUTE_TAG = '_auto_route_tag';

    /**
     * @var AutoRouteInterface
     */
    protected $redirectRoute;

    /**
     * {@inheritdoc}
     */
    public function setAutoRouteTag($autoRouteTag)
    {
        $this->setDefault(self::DEFAULT_KEY_AUTO_ROUTE_TAG, $autoRouteTag);
    }

    /**
     * {@inheritdoc}
     */
    public function getAutoRouteTag()
    {
        return $this->getDefault(self::DEFAULT_KEY_AUTO_ROUTE_TAG);
    }

    /**
     * {@inheritdoc}
     */
    public function setType($type)
    {
        $this->setDefault('type', $type);
    }

    /**
     * {@inheritdoc}
     */
    public function setRedirectTarget($redirectRoute)
    {
        $this->redirectRoute = $redirectRoute;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectTarget()
    {
        return $this->redirectRoute;
    }
}
