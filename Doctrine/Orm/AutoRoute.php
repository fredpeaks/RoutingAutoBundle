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

use Symfony\Cmf\Bundle\RoutingAutoBundle\Model\AutoRouteTrait;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\AbstractRoute;
use Symfony\Cmf\Component\RoutingAuto\Model\AutoRouteInterface;

/**
 * Sub class of Route to enable automatically generated routes
 * to be identified.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class AutoRoute extends AbstractRoute implements AutoRouteInterface
{
    use AutoRouteTrait;
}
