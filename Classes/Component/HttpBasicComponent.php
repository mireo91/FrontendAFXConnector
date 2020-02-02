<?php
namespace Mireo\FrontendAFXConnector\Component;

use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;
use Neos\Utility\Arrays;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class HttpBasicComponent implements ComponentInterface
{

    /**
     * @inheritDoc
     */
    public function handle(ComponentContext $componentContext)
    {
        $httpRequest = $componentContext->getHttpRequest();

        if( $httpRequest->hasHeader('Authorization') ) {
            $authorizationHeader = $httpRequest->getHeaderLine('Authorization');
            if (strpos($authorizationHeader, 'Basic ') !== 0) {
                return;
            }

            $credentials = base64_decode(substr($authorizationHeader, 6));
            list($username, $password) = explode(':', $credentials, 2);
            $queryParams = $httpRequest->getQueryParams();
            $queryParams = Arrays::setValueByPath($queryParams, '__authentication.Neos.Flow.Security.Authentication.Token.UsernamePassword.username', $username);
            $queryParams = Arrays::setValueByPath($queryParams, '__authentication.Neos.Flow.Security.Authentication.Token.UsernamePassword.password', $password);
//            $queryParams = Arrays::setValueByPath($queryParams, '', $password);
            $httpRequest = $httpRequest->withQueryParams($queryParams);
//            \Neos\Flow\var_dump($httpRequest->getQueryParams());exit;
            $componentContext->replaceHttpRequest($httpRequest);
//            \Neos\Flow\var_dump($username);\Neos\Flow\var_dump($password);
//            $this->credentials['username'] = $username;
//            $this->credentials['password'] = $password;
        }

//        \Neos\Flow\var_dump($httpRequest->withQueryParams());exit;
//        \Neos\Flow\var_dump($httpRequest->getParsedBody());
//        exit;
//        $componentContext->replaceHttpRequest($httpRequest);
//        $componentContext->replaceHttpResponse($possibleResponse);
    }
}
