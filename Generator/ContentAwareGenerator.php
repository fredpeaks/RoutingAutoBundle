<?php
namespace Symfony\Cmf\Bundle\RoutingAutoBundle\Generator;

use Doctrine\Common\Collections\Collection;
use Symfony\Cmf\Component\Routing\ContentAwareGenerator as BaseGenerator;
use Symfony\Cmf\Component\Routing\RouteReferrersReadInterface;
use Symfony\Cmf\Component\RoutingAuto\Model\AutoRouteInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class ContentAwareGenerator extends BaseGenerator
{
    protected $adapter;

    /**
     * @param $adapter
     * @return ContentAwareGenerator
     */
    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;
    }

    protected function getRouteByContent($name, &$parameters)
    {
        if(null != $this->adapter && is_object($name) && !$name instanceof RouteReferrersReadInterface && !isset($parameters['content_id'])) {
            $routes = $this->adapter->getActiveReferringAutoRoutes($name);
        } else {
            if ($name instanceof RouteReferrersReadInterface) {
                $content = $name;
            } elseif (isset($parameters['content_id'])) {
                if (null !== $this->contentRepository) {
                    $content = $this->contentRepository->findById($parameters['content_id']);
                    if (empty($content)) {
                        throw new RouteNotFoundException(
                            'The content repository found nothing at id '.$parameters['content_id']
                        );
                    }
                    if (!$content instanceof RouteReferrersReadInterface) {
                        if (null != $this->adapter) {
                            $routes = $this->adapter->getActiveReferringAutoRoutes($content);
                        }
                        if(!isset($routes) || empty($routes)) {
                            throw new RouteNotFoundException(
                                'Content repository did not return a RouteReferrersReadInterface or AutoRouteInterface instance for id '.$parameters['content_id']
                            );
                        }
                    }
                }
            } else {
                $hint = is_object($name) ? get_class($name) : gettype($name);
                throw new RouteNotFoundException(
                    "The route name argument '$hint' is not RouteReferrersReadInterface instance and there is no 'content_id' parameter"
                );
            }
        }


        if (!isset($routes) && $content instanceof RouteReferrersReadInterface) {
            $routes = $content->getRoutes();
        }
        if (empty($routes)) {
            $hint = ($this->contentRepository && $this->contentRepository->getContentId($content))
                ? $this->contentRepository->getContentId($content)
                : get_class($content);
            throw new RouteNotFoundException('Content document has no route: '.$hint);
        }

        unset($parameters['content_id']);

        $route = $this->getRouteByLocale($routes, $this->getLocale($parameters));
        if ($route) {
            return $route;
        }

        // if none matched, randomly return the first one
        if ($routes instanceof Collection) {
            return $routes->first();
        }

        return reset($routes);
    }

    /**
     * We support string, empty or object as name
     */
    public function supports($name)
    {
        return is_string($name) || $name === null || $name === '' || is_object($name);
    }
}