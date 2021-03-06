<?php

namespace HD\Api\Client;

use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManager;
use Zend\Filter\Word\UnderscoreToCamelCase;

use HD\Api\Client\Http\ClientInterface;

class Client implements ServiceManagerAwareInterface, EventManagerAwareInterface
{
    /*
     * EventManager
     */
    protected $events;

    /**
     * Http\Client
     *
     * @var Client
     */
    private $httpClient;

    /**
     * Api Namespace
     */

    private $apiNamespace;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * Set Api Namespace
     * @param string $nnamespace
     */
    public function setApiNamespace($namespace)
    {
        $this->apiNamespace = $namespace;
    }

    public function getApiNamespace()
    {
        return $this->apiNamespace;
    }

    public function api($resource)
    {
        $filter = new UnderscoreToCamelCase();
        $resource = $filter->filter($resource);

        $em = $this->getEventManager();
        $em->trigger('api', $this);

        $service = $this->getServiceManager()->get($this->getApiNamespace() . '\Api\\' . $resource);
        $service->setClient($this);
        return $service;
    }

    /**
     * Authenticate a user for all next requests
     *
     * @param string      $tokenOrLogin  GitHub private token/username/client ID
     * @param null|string $password      GitHub password/secret
     * @param string $authMethod
     */
    public function authenticate($authMethod, $options)
    {
        $sm = $this->getServiceManager();
        $authListener = $sm->get($authMethod);
        $authListener->setOptions($options);

        $this->getHttpClient()->getEventManager()->attachAggregate($authListener);
    }

    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = $this->getServiceManager()->get('HD\Api\Client\HttpClient');
            $errorListener = $this->getServiceManager()->get('HD\Api\Client\Listener\Error');
            $eventManager = $this->httpClient->getEventManager();
            $eventManager->attachAggregate($errorListener);
        }
        return $this->httpClient;
    }

    /**
     * Set HttpClient
     * @param HttpClientInterface $httpClient
     */
    public function setHttpClient($httpClient)
    {
        $this->httpClient = $httpClient;
        return $this;
    }

    /**
     * Set Event Manager
     *
     * @param EventManagerInterface $events
     * @return Client
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers(array(
            __CLASS__,
            get_called_class(),
        ));
        $this->events = $events;
        return $this;
    }

    /**
     * Get Event Manager
     * @return EventManager
     */
    public function getEventManager()
    {
        if (null === $this->events) {
            $this->setEventManager(new EventManager());
        }
        return $this->events;
    }
}
