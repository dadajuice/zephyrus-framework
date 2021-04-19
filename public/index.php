<?php

use Zephyrus\Application\Bootstrap;
use Zephyrus\Exceptions\RouteMethodUnsupportedException;
use Zephyrus\Exceptions\RouteNotAcceptedException;
use Zephyrus\Exceptions\RouteNotFoundException;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Network\ResponseFactory;

/**
 * All application route calls are redirected here by the .htaccess file (excepts /public exclusion paths) to start the
 * Zephyrus router engine.
 */
Bootstrap::initializeRoutableControllers($router);

/**
 * Will attempt to execute the request sent by the client. If the url matches a defined route within the project
 * controllers, it will execute the middleware chain and the route processing. If an error arise (route wasn't found,
 * requested HTTP_ACCEPT is not supported or any other exception that wasn't caught by the application can be handled
 * here. By default, it only send HTTP code corresponding the error. Consider doing customized pages or redirects for
 * visual applications. For APIs, this is a proper way to handle errors.
 */
try {
    $router->run(RequestFactory::read());
} catch (RouteMethodUnsupportedException $e) {
    ResponseFactory::getInstance()->abortMethodNotAllowed()->send();
} catch (RouteNotAcceptedException $e) {
    ResponseFactory::getInstance()->abortNotAcceptable()->send();
} catch (RouteNotFoundException $e) {
    ResponseFactory::getInstance()->abortNotFound()->send();
}
