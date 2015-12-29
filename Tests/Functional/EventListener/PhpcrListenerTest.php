<?php

namespace Symfony\Cmf\Bundle\RoutingAutoBundle\Tests\Functional\EventListener;

use Symfony\Cmf\Bundle\RoutingAutoBundle\Tests\Functional\EventListener\ListenerTestCase;

class PhpcrListenerTest extends ListenerTestCase
{
    public function setUp(array $options = array(), $routebase = null)
    {
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

    public function getDm()
    {
        return $this->db('PHPCR')->getOm();
    }

    public function getRoute($path)
    {
        return $this->getDm()->find(null, '/test/auto-route/' . $path);
    }

    public function getRoutesForObject($object)
    {
        return $this->getDm()->getReferrers($object);
    }

    public function getBlog($title)
    {
        return $this->getDm()->find('Symfony\Cmf\Bundle\RoutingAutoBundle\Tests\Resources\Document\Blog', '/test/' . $title);
    }

    public function getPost($blogName, $postTitle)
    {
        return $this->getDm()->getRepository('Symfony\Cmf\Bundle\RoutingAutoBundle\Tests\Resources\Document\Post')->findOneBy(array(
            'title' => $postTitle
        ));
    }

    public function persist($object, $lang = null)
    {
        $this->getDm()->persist($object);

        if ($lang) {
            $this->getDm()->bindTranslation($object, $lang);
        }
    }

    public function find($class, $id, $lang = null)
    {
        $object = $this->getDm()->find($class, $id);

        if ($lang) {
            $object = $this->getDm()->findTranslation($class, $id, $lang);
        }

        return $object;
    }
}
