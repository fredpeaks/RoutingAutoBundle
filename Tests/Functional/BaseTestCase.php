<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2015 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingAutoBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\Resolver\ContentCodeResolver;
use Symfony\Cmf\Component\Testing\Functional\BaseTestCase as TestingBaseTestCase;

class BaseTestCase extends TestingBaseTestCase
{
    /**
     * @var bool
     */
    protected $isORM = false;

    public function getApplication()
    {
        $application = new Application(self::$kernel);

        return $application;
    }

    public function setUp(array $options = array(), $routebase = null)
    {
        if(!$this->isORM) {
        $session = $this->getContainer()->get('doctrine_phpcr.session');

        if ($session->nodeExists('/test')) {
            $session->getNode('/test')->remove();
        }

        if (!$session->nodeExists('/test')) {
            $session->getRootNode()->addNode('test', 'nt:unstructured');
            $session->getNode('/test')->addNode('auto-route');
        }

            $session->save();
        }
    }

    public function getOm()
    {
        if(!$this->isORM) {
            return $this->db('PHPCR')->getOm();
        } else {
            return $this->db('ORM')->getOm();
        }
    }

    public function getRoute($path)
    {
        if(!$this->isORM) {
            $this->getOm()->find(null, '/test/auto-route/' . $path);
        } else {
            return $this->getOm()->getRepository('Symfony\Cmf\Bundle\RoutingAutoBundle\Doctrine\Orm\AutoRoute')->findOneBy(array(
                'staticPrefix' => $path
            ));
        }
    }

    public function getRoutesForObject($object)
    {
        if(!$this->isORM) {
            return $this->getOm()->getReferrers($object);
        } else {
            $contentResolver = new ContentCodeResolver();
            return $this->getOm()->getRepository('Symfony\Cmf\Bundle\RoutingAutoBundle\Doctrine\Orm\AutoRoute')->findBy(array(
                'contentCode' => $contentResolver->getContentCode($object)
            ));
        }
    }

    public function getBlog($title)
    {
        if(!$this->isORM) {
            return $this->getOm()->find('Symfony\Cmf\Bundle\RoutingAutoBundle\Tests\Resources\Document\Blog', '/test/' . $title);
        } else {
            return $this->getOm()->getRepository('Symfony\Cmf\Bundle\RoutingAutoBundle\Tests\Resources\Entity\Blog')->findOneBy(array(
                'title' => $postTitle
            ));
        }
    }

    public function getPost($blogName, $postTitle)
    {
        if(!$this->isORM) {
            return $this->getOm()->getRepository('Symfony\Cmf\Bundle\RoutingAutoBundle\Tests\Resources\Document\Post')->findOneBy(array(
                'title' => $postTitle
            ));
        } else {
            return $this->getOm()->getRepository('Symfony\Cmf\Bundle\RoutingAutoBundle\Tests\Resources\Entity\Post')->findOneBy(array(
                'title' => $postTitle
            ));
        }
    }

    public function persist($object, $lang = null)
    {
        if(!$this->isORM) {
            $this->getOm()->persist($object);

            if ($lang) {
                $this->getOm()->bindTranslation($object, $lang);
            }
        } else {
            if($lang) {
                $object->setTranslatableLocale($lang);
            }
            $this->getOm()->persist($object);
        }
    }

    public function flush()
    {
        $this->getOm()->flush();
    }

    public function clear()
    {
        $this->getOm()->clear();
    }

    public function refresh($object)
    {
        $this->getOm()->refresh($object);
    }

    public function remove($object)
    {
        $this->getOm()->remove($object);
    }


    public function find($class, $id, $lang = null)
    {
        if(!$this->isORM) {
            $object = $this->getOm()->find($class, $id);
            if ($lang) {
                $object = $this->getOm()->findTranslation($class, $id, $lang);
            }
        } else {
            $object = $this->getOm()->getRepository($class)->find($id);
            if ($lang) {
                $object->setLocale($lang);
                $this->getOm()->refresh($object);
            }
        }

        return $object;
    }
}
