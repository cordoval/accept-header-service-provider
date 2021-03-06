<?php

namespace Pff\ServiceProvider\AcceptHeaderServiceProvider;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpFoundation\Request;
use Silex\RedirectableUrlMatcher;

class UrlMatcher extends RedirectableUrlMatcher
{
    private $request;

    public function __construct(RouteCollection $routes, RequestContext $context, Request $request)
    {
        parent::__construct($routes, $context);
        $this->request = $request;
    }


    protected function handleRouteRequirements($pathinfo, $name, Route $route)
    {
        $ret = parent::handleRouteRequirements($pathinfo, $name, $route);
        if ($ret[0] == self::REQUIREMENT_MISMATCH)
            return $ret;

        foreach($this->request->request->get('_accept') as $accept)
        {
            if (preg_match('/^('.$route->getRequirement('_accept').')$/', $accept))
            {
                $route->setDefault('accept_header', $accept);
                return array(self::REQUIREMENT_MATCH, null);
            }
        }

        return array(self::REQUIREMENT_MISMATCH, null);
    }
}
