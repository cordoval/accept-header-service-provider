<?php

use Silex\WebTestCase;
use Symfony\Component\HttpKernel\HttpKernel;
use Silex\Controller;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Silex\RedirectableUrlMatcher;
use Pff\Provider\AcceptHeaderProvider as AcceptHeader;

class AcceptHeaderRoutingTest extends WebTestCase
{
    /**
     * Creates the application.
     *
     * @return HttpKernel
     */
    public function createApplication()
    {
        $app = new \Silex\Application();

        $app['route_class'] = '\\Pff\\Provider\\AcceptHeaderProvider\\Route';

        $app['dispatcher']->addSubscriber(new AcceptHeader\KernelListener());

        $app['url_matcher'] = $app->share(function () use ($app) {
            return new AcceptHeader\UrlMatcher($app['routes'], $app['request_context'], $app['request']);
        });


        /** @var $controllers1 VersionedRestControllerCollection */
        $controllers1 = $app['controllers_factory'];

        $controllers1->get('/test', function($accept_header) use ($app) {
            if ($accept_header == 'application/ven.test.v1+json')
                $cont = json_encode(array('content' => 'hello'));
            else
                $cont = '<content>hello</content>';

            return new Response($cont, 200, array('Content-Type' => $accept_header));
        })->accept(array('application/ven.test.v1+json', 'application/ven.test.v1+xml'));


        /** @var $controllers1 VersionedRestControllerCollection */
        $controllers2 = $app['controllers_factory'];

        $controllers1->get('/test', function($accept_header) use ($app) {
            if ($accept_header == 'application/ven.test.v2+json')
                $cont = json_encode(array('content' => 'hiya'));
            else
                $cont = '<content>hiya</content>';

            return new Response($cont, 200, array('Content-Type' => $accept_header));
        })->accept(array('application/ven.test.v2+json', 'application/ven.test.v2+xml'));


        $app->mount('/', $controllers1);
        $app->mount('/', $controllers2);


        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    public function testValidV1Call()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/test', array(), array(), array('HTTP_ACCEPT' => 'application/ven.test.v1+xml'));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $result = $client->getResponse()->getContent();

        $this->assertEquals('<content>hello</content>', $result, 'response is correct');
    }

    public function testValidV2Call()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/test', array(), array(), array('HTTP_ACCEPT' => 'application/ven.test.v2+xml'));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $result = $client->getResponse()->getContent();

        $this->assertEquals('<content>hiya</content>', $result, 'response is correct');
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testInvalidV3Call()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/test', array(), array(), array('HTTP_ACCEPT' => 'application/ven.test.v3+xml'));

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
}
