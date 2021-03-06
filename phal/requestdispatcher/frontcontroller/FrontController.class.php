<?php


abstract class __FrontController implements __IFrontController {

    protected static $_instance = null;
    protected $_request  = null;
    protected $_response = null;
    protected $_cache_groups = array();
    
    public function &getRequest() {
        return $this->_request;
    }

    public function &getResponse() {
        return $this->_response;
    }
    
    public function addCacheGroup($cache_group) {
    	$this->_cache_groups[$cache_group] = true;
    }
    
    public function getCacheGroups() {
    	return array_keys($this->_cache_groups);
    }
    
    /**
     * Singleton method to retrieve the __FrontController instance
     *
     * @return __FrontController The singleton __FrontController instance
     */
    final public static function &getInstance() {
        if(self::$_instance == null) {
            self::$_instance = self::_createFrontController();
        }
        return self::$_instance;
    }
    
    /**
     * Factory method for creating a __FrontController to dispatch the client request
     *
     * @return __IFrontController
     */
    final private static function _createFrontController() {
        $client_request = __Client::getInstance()->getRequest();
        //if the client request has been initialized successfully, will ask for correspondent front controller:
        if($client_request != null) {
            $front_controller_class = $client_request->getFrontControllerClass();
        }
        //otherwise will get the most appropriate fron controller depending on the client type (http, commandline, ...):
        else {
            $front_controller_class = __Client::getInstance()->getDefaultFrontControllerClass();
        }
        $front_controller = new $front_controller_class();
        if(! $front_controller instanceof __IFrontController ) {
            throw __ExceptionFactory::getInstance()->createException('ERR_WRONG_FRONT_CONTROLLER_CLASS', array($front_controller_class));
        }
        return $front_controller;
    }

    public function dispatchClientRequest() {
        //map the request/response with the client
        $request  = __Client::getInstance()->getRequest();
        $response = __Client::getInstance()->getResponse();
        //call to dispatch the request
        $this->dispatch($request, $response);
        //output whatever pending (buffered) content in the response
        $response->flush();
    }
        
    protected function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
}
    
    public function dispatch(__IRequest &$request, __IResponse &$response) {
        //set the current request and response:
        $this->_request  =& $request;
        $this->_response =& $response;
        //dispatch the request:
        if($request->hasFilterChain()) {
            $filter_chain = $request->getFilterChain();
            $filter_chain->reset();
            $filter_chain->setFrontControllerCallback($this, 'processRequest');
            $filter_chain->execute($request, $response);
        }
        else {
            $this->processRequest($request, $response);
        }
    }
    
    public function getRequestType() {
        if($this->_request != null) {
            return $this->_request->getRequestType();
        }
        else {
            return __Client::getInstance()->getRequestType();
        }
    }
    
    abstract public function processRequest(__IRequest &$request, __IResponse &$response);
    
}
