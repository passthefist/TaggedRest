Restful routing/controllers
=========

Tagged/Rest is a small framework for creating restful api's in object oriented controllers
rather than callbacks. It comes with powerful validation from the json schema project, and
api methods both respond to http requests and can be called internally as normal object
methods. There's also a mechanism to dynamically generate documentation for all api endpoints
for free, just hit the url with an OPTIONS request.

The framework is focused only on handling http requests, leaving the rest of the
project for other frameworks or existing code. 

Really, this is just a wrapper around [Klein](https://github.com/chriso/klein.php) that allows for 
easy construction of restful urls and controllers.

A url maps to a controller based on a routes mapping, and the controller handles the
http request/response.

Routes
-

A route looks something like **/owners/[a:name]/dogs**.

The value in square brackets is matched and passed to the controller as query parameters.

A request to **/owners/george/dogs?color=brown** to the route above becomes
```
name = george
color = brown
```

A route with a parameter as the last part of the url path is considered a resource route, and a route with
a static name as the last part is a collection. Above, the dogs route would be a collection and photos a resource.

Routes support many features such as optional values and validation. See [https://github.com/chriso/klein.php#routing]() for more about route syntax.

----

The router takes a parameter that is an array of routes mapped to a controller, like
```php
$routes => array(
            "/login" => "login",
            #  a namespace URL. Children of this are appended.
            "/users/[i:userid]" => array(
                "/loginhistory" => "loginhistory",
                "/messages" => "messages",
                "/photos" => "photos",
                "/photos/[i:photoid]" => "photos"
            )
        )
```
where the key is the route path and the value a namespace. If instead of a string, the value is an array, then the routes in that array are nested under the parent path.
The router also takes a parameter defining a route namespace and another one defining the controller namespace.

Here's an example using the routing array above.
If the route namespace **/api/v1**, the controller namespace **/app/controllers**, then routes generated by the config above are:

```
/api/v1/login                                => /app/controllers/login.php
/api/v1/users/[i:userid]/loginhistory        => /app/controllers/loginhistory.php
/api/v1/users/[i:userid]/messages            => /app/controllers/messages.php
/api/v1/users/[i:userid]/photos              => /app/controllers/photos.php
/api/v1/users/[i:userid]/photos/[i:photoid]  => /app/controllers/photos.php
```

The http methods supported by each route are determined by looking at the controller. 
Routes are either handled as a collection or a resource.
A resource acts on a single record (database row), and a collection acts on many records (database table).
In the example above, requests to **/api/v1/users/[i:userid]/photos** would be handled by the controller as a collection,
and **/api/v1/users/[i:userid]/photos/[i:photoid]** as a resource.
This is determined by the convention of resource routes ending in an id/paramter (*/[i:photoid]*) 
and collections ending in the name of the collection (*/photos*).


Validation
-

Validation is done using a json schema validator. The query/form parameters are treated as a nested
json structure. This structure comes from php's built in query deserialization. 
Query params like '?item[key]=value' are serialized as an array like ```array( 'item' => array('key'=>'value'))```.

This is fed into a validator that ensures the query parameters are structured how the API expects. For more info
on json schema syntax and options, check http://json-schema.org/.

The schemas for each controller function are registered in the constructor.

Controllers
-
Controllers handle the http request. A handler method expects to be passed a stdClass object.
The value returned by the method becomes the body of the http request.

The default mapping is below, but can be overridden. Methods not implemented become HTTP 405 errors when requested.

Every controller method is passed a response and request object. Check https://github.com/chriso/klein.php/wiki/Api for documentation.

| HTTP method | Controller method | Expected action |
| ----------- | ----------------- | --------------- |
| GET (resource) | fetch() | get the requested item (no side effects) |
| PUT (resource) | update() | update the requested item (idempotent) |
| DELETE (resource) | delete() | delete the requested item (idempotent) |
| GET (collection) | index() | used as an index function for simple urls |
| GET (collection) | find() | list all items matching the query (no side effects) |
| POST (collection) | create() | create a new item |
| PUT (collection) | bulkUpdate() | update a set of items (idempotent) |
| DELETE (collection) | deleteAll() | delete all items (idempotent) |

Note that there are two handlers for GET requests on a collection. This is just so that controller
methods can have better names. Use find when the method is going to be returning a collection of
data. Index is better used for responding to basic url's, like a health check or html page.

A controller may register as many methods as it likes to respond to the same http action,
which is usesful for custom actions. Custom methods can be registered in the constructor.
Also, multiple controllers may respond to the same route.

Controller example:

```php
<?php
class api_v2_photos extends \Tagged\Rest\Api\Base {
    public function __construct() {
        $this->_registerInputSchema( "find", array(
            "title" => "Query for user's uploaded photos by date, paginated",
            "type" => "object",
            "properties" => array(
                "dateRange" => \Tagged\Rest\Schema::DateRange(),
                "pagination" => \Tagged\Rest\Schema::Pagination(array(
                        "defaultPage"=>1,
                        "defaultSize"=>2,
                )),
            ),
            "required"=>array('pageNumber','pageSize')
        ));
        
        $this->_registerInputSchema( "fetch", array(
            "title" => "Query for a specific uploaded photo",
            "type" => "object",
            "properties" => array(
                "userid"=> array(
                    "type"=>"number",
                ),
                "photoid"=> array(
                    "type"=>"number",
                    "description"=>"The id of the image to retreive"
                ),
            ),
            "required"=>array('userid','imageid')
        ));
    }
    
    public function find($request) {
        $dao = new tag_dao_photo();
        list($start,$end) = \Kleinbottle\Schema\DateRange::getDates($request->dateRange);

        $photoset = $dao->getPhotosByDate(
            $start,
            $end,
            $request->pageNumber,
            $request->pageSize
        );
        
        return array(
            "size" => count($photoset),
            "photos" => $photoset,
        )
    }
    
    public function fetch($request) {
        $uid = $request->userid;
        $pid = $request->photoid;
        
        $dao = new tag_dao_photo();
        
        return $dao->getPhoto(
            $uid,
            $pid
        );
    }
}
```

An example url for this controller is 
**domain.com/api/v1/users/5/photos?pagination\[page\]=2&pagination\[size\]=25**

Note the form params, this is the structure that the validator will validate against. See validation.

Controllers can add a post-response method to format the output. A controller implementing _formatOutput will be
able to serve different format output. The format is taken from the extension of the url.
A url like **www.mysite.com/page.html** would add the query parameter ```$format=>'html'```,
and **www.mysite.com/page.html** would add the query parameter ```$format=>'json'```. If no format is
given then the value is null.

```php
<?php
protected function _formatOutput($method, $data, $format) {
  if($method === 'fetch') {
    switch($format) {
      case 'html':
        return tag_page::render('some/template/path',$data);
      default:
        return json_encode($data);
    }
  }
}
```

Usage
-

Put this in an index.php file:


```php
<?php
$urlRoot = '/api/v1';
$routes = array(
    "/login" => "login",
    "/users/[i:userid]" => array(
        "/loginhistory" => "loginhistory",
        "/messages" => "messages",
        "/photos" => "photos",
        "/photos/[i:photoid]" => "photos"
    )
);
$controllerPrefix = "tag_admin_api_v1";

$router = new tag_routing_core(
    $urlRoot,
    $routes,
    $controllerPrefix
);

//it's probably a good idea to cache this.
//building the routes is long
$router->buildRoutes();

//defualt uses $_SERVER vars
$router->routeRequest();

//Or specify directly (useful for manual driving and tests)
$router->routeRequest($_SERVER['REQUEST_URI'], $_SERVER['HTTP_METHOD]);
```

It's probably better to put all that in a config file and load them in the index file.

Calling api methods internally
-

All the api methods can be called without making an http request. Two methods to do this are directly
and wrapped. When wrapped the filters and schema validation are applied, and methods can be called with
arrays instead of stdClass objects.

Wrapped, this will go through the same filtering as a web request.

```php
$photoApi = api_v2_photos::raw();

// use the same structure as an api call
$photoObj = $photoApi->fetch(array(
  'userid'=>5,
  'photoid'=>4216123
));
```

Of course, the other method is to instantiate the object and call the methods as a normal object.
Calling a method directly kinda sucks since all the controllers expect stdClass objects.

Auto API Documentaion
-
Documentation is important for an API, and this framework provides a mechanism to automatically
produce documentation for free.

Hitting any api with an OPTIONS request will return an html page with the documentation for that
api. The documentation comes from both doc comments on the class and from the schema specified
for the api methods.

There are plans to add a single endpoint that acts as a collection of this documentation, so that
all apis are easily discoverable.

Because the documentation is generated dynamically it will always match the code, rather than requiring
a job to be run to keep the documentation and api in sync.

Notes
-

This project has not been performance tuned, but should run similar to Klein.
Most of the big work is done mapping the routes to controller callbacks and
building the router, after that it's basically a Klein project.

Currently, for some production APIs, we're just building the routes on every
request for about 60ms overhead. For a high scale service, that's something
you'd want to cache. Caching is not built in, but the router is built to be
easily cached if need be.

Dependencies
-

Klein (for routing) https://github.com/chriso/klein.php
geraintluff's coercive json validator https://github.com/geraintluff/jsv4-php

For testing requires Phockito and php-unit.
