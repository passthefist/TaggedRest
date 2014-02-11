<?php
namespace Tagged\Rest\Api;

class Base {
    private $inputSchemas = array();
    private $outputSchemas = array();

    // Defines the mapping between HTTP methods
    // and the controller actions
    protected $resourceMapping = array(
        'fetch' => 'GET',
        'update' => 'PUT',
        'delete' => 'DELETE',
    );

    protected $collectionMapping = array(
        'find' => 'GET',
        'index' => 'GET',
        'create' => 'POST',
        'bulkUpdate' => 'PUT',
        'deleteAll' => 'DELETE',
    );

    /*
     * Get a web enabled version of the controller.
     * Right now it's not actually wrapped.
     */
    public static function api() {
        return new static();
    }

    /*
     * Get a version of the controller for raw access,
     * like from code. Used when you want to hit the
     * api locally without an http request.
     */
    public static function raw() {
        return new RawWrapper(new static());
    }

    /*
     * Register a schema for a method.
     */
    protected function _registerInputSchema($method, $schema) {
        $this->inputSchemas[$method] = new \Tagged\Rest\Schema\Validator($schema);
    }

    protected function _registerOutputSchema($method, $schema) {
        $this->ouputSchemas[$method] = new \Tagged\Rest\Schema\Validator($schema);
    }

    /*
     * Hook a method as a custom handler for an http action.
     * Multiple callbacks can be specified for the same action,
     * so 3 methods could respond to GET requests
     */
    protected function _customResourceHandler($action, $method) {
        $this->resourceMapping[$method] = $action;
    }

    protected function _customCollectionHandler($action, $method) {
        $this->collectionMapping[$method] = $action;
    }

    // alias for _customCollectionHandler
    protected function _customHandler($action, $method) {
        $this->_customCollectionHandler($action, $method);
    }

    protected function _validateInputFor($method, $args) {
        return $this->inputSchemaFor($method)->validate($args);
    }

    /*
     * Returns whether a method by that name exists, and
     * responds to http requests. Methods that would not
     * respond to an http request return false even if
     * they exist on the object.
     */
    public function respondsTo($method) {
        $routableMethods = array_merge(
            $this->getCollectionMethods(),
            $this->getResourceMethods()
        );
        return in_array($method, $routableMethods);
    }

    /*
     * Return the http action this method responds to
     */
    public function actionFor($method) {
        $map = $this->methodMapping();
        return $map[$method];
    }

    /*
     * Get a full mapping of all methods and http actions
     */
    public function methodMapping() {
        return array_merge(
            $this->resourceMapping,
            $this->collectionMapping
        );
    }

    public function getCollectionMethods() {
        return array_intersect(
            get_class_methods($this),
            array_keys($this->collectionMapping)
        );
    }

    public function getResourceMethods() {
        return array_intersect(
            get_class_methods($this),
            array_keys($this->resourceMapping)
        );
    }

    public function inputSchemaFor($method) {
        if (!isset($this->inputSchemas[$method])) {
            return new \Tagged\Rest\Schema\Validator(array());
        }
        return $this->inputSchemas[$method];
    }

    public function outputSchemaFor($method) {
        if (!isset($this->outputSchemas[$method])) {
            return array();
        }
        return $this->outputSchemas[$method];
    }

    /*
     * This is the function that actually invokes the method.
     * Call this if you want to filter the input using the
     * schema registered for the method
     */
    public function invoke($method, $params) {
        $params = $this->_validateInputFor($method, $params);

        $params = json_decode(json_encode($params));

        return $this->$method($params);
    }

    public function invokeWithRequest($action, $request, $response) {
        $params = $request->params();

        $result = $this->invoke($action,$params);
        $result = $this->_formatResponse($action,$result, $request->format);

        $response->body($result);
    }

    /*
     * Override in the subclass to have different formats
     * for an http request
     */
    protected function _formatResponse($action,$result,$format) {
        return json_encode($result);
    }

    // Stub. Will add documentation later
    public function getDocumentation() {
        return new ApiDocumentor($this);
    }

    public function __call($action, $args) {
        return $this->invoke($action, $request, $response);
    }
}
