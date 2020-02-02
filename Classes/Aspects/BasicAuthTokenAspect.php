<?php
namespace Mireo\FrontendAFXConnector\Aspects;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\RequestHandler;
use Neos\Flow\Security\Authentication\Token\UsernamePasswordHttpBasic;
use Neos\Flow\Security\Authentication\TokenAndProviderFactoryInterface;
use Neos\Flow\Security\Authentication\TokenInterface;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class BasicAuthTokenAspect
{
//    protected $httpRequest;
    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @Flow\Around("method(Neos\Flow\Security\Authentication\TokenAndProviderFactory->getTokens())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return mixed
     */
    public function getTokens(JoinPointInterface $joinPoint)
    {
//        if(  )
        /** @var RequestHandler $request */
        $request = $this->bootstrap->getActiveRequestHandler();
        $httpRequest = $request->getHttpRequest();
        $authorization = $httpRequest->getHeaderLine('Authorization');
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);
        if( !(strpos($httpRequest->getUri()->getPath(), '/afxConnector') === 0 || strpos($authorization, 'Basic') === 0) ){
            return $result;
        }

        $token = new UsernamePasswordHttpBasic();
        $token->setAuthenticationProviderName('Neos.Neos:Backend');
        $token->setAuthenticationStatus(TokenInterface::AUTHENTICATION_NEEDED);
        $result = [$token];
        return $result;
    }
}
