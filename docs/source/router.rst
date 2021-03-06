Router
======

A router is a type of middleware_ that organizes the components of a site by associating URI paths with other middleware_. When the router receives a request, it examines the path components of the request's URI, determines which "route" matches, and dispatches the associated middleware_. The dispatched middleware_ is then responsible for reacting to the request and providing a response.


Basic Usage
^^^^^^^^^^^

Typically, you will want to use the ``WellRESTed\Server::createRouter`` method to create a ``Router``.

.. code-block:: php

    $server = new WellRESTed\Server();
    $router = $server->createRouter();

Suppose ``$catHandler`` is a middleware that you want to dispatch whenever a client makes a ``GET`` request to the path ``/cats/``. Use the ``register`` method map it to that path and method.

.. code-block:: php

    $router->register("GET", "/cats/", $catHandler);

The ``register`` method is fluent, so you can add multiple routes in either of these styles:

.. code-block:: php

    $router->register("GET", "/cats/", $catReader);
    $router->register("POST", "/cats/", $catWriter);
    $router->register("GET", "/cats/{id}", $catItemReader);
    $router->register("PUT,DELETE", "/cats/{id}", $catItemWriter);

...Or...

.. code-block:: php

    $router
        ->register("GET", "/cats/", $catReader)
        ->register("POST", "/cats/", $catWriter)
        ->register("GET", "/cats/{id}", $catItemReader)
        ->register("PUT,DELETE", "/cats/{id}", $catItemWriter);

Paths
^^^^^

A router can map middleware to an exact path, or to a pattern of paths.

Static Routes
-------------

The simplest type of route is called a "static route". It maps middleware to an exact path.

.. code-block:: php

    $router->register("GET", "/cats/", $catHandler);

This route will map a request to ``/cats/`` and only ``/cats/``. It will **not** match requests to ``/cats`` or ``/cats/molly``.

Prefix Routes
-------------

The next simplest type of route is a "prefix route". A prefix route matches requests by the beginning of the path.

To create a "prefix handler", include ``*`` at the end of the path. For example, this route will match any request that begins with ``/cats/``.

.. code-block:: php

    $router->register("GET", "/cats/*", $catHandler);

Template Routes
---------------

Template routes allow you to provide patterns for paths with one or more variables (sections surrounded by curly braces) that will be extracted.

For example, this template will match requests to ``/cats/12``, ``/cats/molly``, etc.,

.. code-block:: php

    $router->register("GET", "/cats/{cat}", $catHandler);

When the router dispatches a route matched by a template route, it provides the extracted variables as an associative array. To access a variable, call the request object's ``getAttribute`` method method and pass the variable's name.

For a request to ``/cats/molly``:

.. code-block:: php

    $catHandler = function ($request, $response, $next) {
        $name = $request->getAttribute("cat");
        // molly
        ...
    }

Template routes are very powerful, and this only scratches the surface. See `URI Templates`_ for a full explanation of the syntax supported.

Regex Routes
------------

You can also use regular expressions to describe route paths.

.. code-block:: php

    $router->register("GET", "~cats/(?<name>[a-z]+)-(?<number>[0-9]+)~", $catHandler);

When using regular expression routes, the attributes will contain the captures from preg_match_.

For a request to ``/cats/molly-90``:

.. code-block:: php

    $catHandler = function ($request, $response, $next) {
        $vars = $request->getAttributes();
        /*
        Array
        (
            [0] => cats/molly-12
            [name] => molly
            [1] => molly
            [number] => 12
            [2] => 12
            ... Plus any other attributes that were set ...
        )
        */
        ...
    }

Route Priority
--------------

A router will often contain many routes, and sometimes more than one route will match for a given request. When the router looks for a matching route, it performs these checks:

#. If there is a static route with exact match to path, dispatch it.
#. If one prefix route matches the beginning of the path, dispatch it.
#. If multiple prefix routes match, dispatch the longest matching prefix route.
#. Inspect each pattern route (template and regular expression) in the order added. Dispatch the first route that matches.
#. If no pattern routes match, return a response with a ``404 Not Found`` status.

Static vs. Prefix
~~~~~~~~~~~~~~~~~

Consider these routes:

.. code-block:: php

    $router
        ->register("GET", "/cats/", $static);
        ->register("GET", "/cats/*", $prefix);

The router will dispatch a request for ``/cats/`` to ``$static`` because the static route ``/cats/`` has priority over the prefix route ``/cats/*``.

The router will dispatch a request to ``/cats/maine-coon`` to ``$prefix`` because it is not an exact match for ``/cats/``, but it does begin with ``/cats/``.

Prefix vs. Prefix
~~~~~~~~~~~~~~~~~

Given these routes:

.. code-block:: php

    $router
        ->register("GET", "/dogs/*", $short);
        ->register("GET", "/dogs/sporting/*", $long);

A request to ``/dogs/herding/australian-shepherd`` will be dispatched to ``$short`` because it matches ``/dogs/*``, but does not match ``/dogs/sporting/*``

A request to ``/dogs/sporing/flat-coated-retriever`` will be dispatched to ``$long`` because it matches both routes, but ``/dogs/sporting`` is longer.

Prefix vs. Pattern
~~~~~~~~~~~~~~~~~~

Given these routes:

.. code-block:: php

    $router
        ->register("GET", "/dogs/*", $prefix);
        ->register("GET", "/dogs/{group}/{breed}", $pattern);

``$pattern`` will never be dispatched because any route that matches ``/dogs/{group}/{breed}`` also matches ``/dogs/*``, and prefix routes have priority over pattern routes.

Pattern vs. Pattern
~~~~~~~~~~~~~~~~~~~

When multiple pattern routes match a path, the first one that was added to the router will be the one dispatched. Be careful to add the specific routes before the general routes. For example, say you want to send traffic to two similar looking URIs to different middleware based whether the variables were supplied as numbers or letters—``/dogs/102/132`` should be dispatched to ``$numbers``, while ``/dogs/herding/australian-shepherd`` should be dispatched to ``$letters``.

This will work:

.. code-block:: php

    // Matches only when the variables are digits.
    $router->register("GET", "~/dogs/([0-9]+)/([0-9]+)", $numbers);
    // Matches variables with any unreserved characters.
    $router->register("GET", "/dogs/{group}/{breed}", $letters);

This will **NOT** work:

.. code-block:: php

    // Matches variables with any unreserved characters.
    $router->register("GET", "/dogs/{group}/{breed}", $letters);
    // Matches only when the variables are digits.
    $router->register("GET", "~/dogs/([0-9]+)/([0-9]+)", $numbers);

This is because ``/dogs/{group}/{breed}`` will match both ``/dogs/102/132`` and ``/dogs/herding/australian-shepherd``. If it is added to the router before the route for ``$numbers``, it will be dispatched before the route for ``$numbers`` is ever evaluated.

Methods
^^^^^^^

When you register a route, you can provide a specific method, a list of methods, or a wildcard to indicate any method.

Registering by Method
---------------------

Specify a specific middleware for a path and method by including the method as the first parameter.

.. code-block:: php

    // Dispatch $dogCollectionReader for GET requests to /dogs/
    $router->register("GET", "/dogs/", $dogCollectionReader);

    // Dispatch $dogCollectionWriter for POST requests to /dogs/
    $router->register("POST", "/dogs/", $dogCollectionWriter);

Registering by Method List
--------------------------

Specify the same middleware for multiple methods for a given path by proving a comma-separated list of methods as the first parameter.

.. code-block:: php

    // Dispatch $catCollectionHandler for GET and POST requests to /cats/
    $router->register("GET,POST", "/cats/", $catCollectionHandler);

    // Dispatch $catItemReader for GET requests to /cats/12, /cats/12, etc.
    $router->register("GET", "/cats/{id}", $catItemReader);

    // Dispatch $catItemWriter for PUT, and DELETE requests to /cats/12, /cats/12, etc.
    $router->register("PUT,DELETE", "/cats/{id}", $catItemWriter);

Registering by Wildcard
-----------------------

Specify middleware for all methods for a given path by proving a ``*`` wildcard.

.. code-block:: php

    // Dispatch $guineaPigHandler for all requests to /guinea-pigs/, regardless of method.
    $router->register("*", "/guinea-pigs/", $guineaPigHandler);

    // Use $hamstersHandler by default for requests to /hamsters/
    $router->register("*", "/hamsters/", $hamstersHandler);

    // Provide a specific handler for POST /hamsters/
    $router->register("POST", "/hamsters/", $hamstersPostOnly);

.. note::

    The wildcard ``*`` can be useful, but be aware that the associated middleware will need to manage ``HEAD`` and ``OPTIONS`` requests, whereas this is done automatically for non-wildcard routes.

HEAD
----

Any route that supports ``GET`` requests will automatically support ``HEAD``. You don't need to provide any specific middleware for ``HEAD``, and you usually shouldn't. (Although you can if you want.)

For most cases, just implement ``GET``, and the webserver will manage suppressing the response body for you.

OPTIONS, 405 Responses, and Allow Headers
-----------------------------------------

When you add routes to a router by method, the router automatically provides responses for ``OPTIONS`` requests. For example, given this route:

.. code-block:: php

    // Dispatch $catItemReader for GET requests to /cats/12, /cats/12, etc.
    $router->register("GET", "/cats/{id}", $catItemReader);

    // Dispatch $catItemWriter for PUT, and DELETE requests to /cats/12, /cats/12, etc.
    $router->register("PUT,DELETE", "/cats/{id}", $catItemWriter);

An ``OPTIONS`` request to ``/cats/12`` will provide a response like:

.. code-block:: http

    HTTP/1.1 200 OK
    Allow: GET,PUT,DELETE,HEAD,OPTIONS

Likewise, a request to an unsupported method will return a ``405 Method Not Allowed`` response with a descriptive ``Allow`` header.

A ``POST`` request to ``/cats/12`` will provide:

.. code-block:: http

    HTTP/1.1 405 Method Not Allowed
    Allow: GET,PUT,DELETE,HEAD,OPTIONS


Error Responses
^^^^^^^^^^^^^^^

When a router is unable to dispatch a route because either the path or method does not match a defined route, it will provide an appropriate error response code—either ``404 Not Found`` or ``405 Method Not Allowed``.

The router always checks the path first. If route for that path matches, the router responds ``404 Not Found``.

If the router is able to locate a route that matches the path, but that route doesn't support the request's method, the router will respond ``405 Method Not Allowed``.

Given this router:

.. code-block:: php

    $router
        ->register("GET", "/cats/", $catReader)
        ->register("POST", "/cats/", $catWriter)
        ->register("GET", "/dogs/", $catItemReader)

The following requests wil provide these responses:

====== ========== ========
Method Path       Response
====== ========== ========
GET    /hamsters/ 404 Not Found
PUT    /cats/     405 Method Not Allowed
====== ========== ========

.. note::

    When the router fails to dispatch a route, or when it responds to an ``OPTIONS`` request, is will stop propagation, and any middleware that comes after the router will not be dispatched.

Nested Routers
^^^^^^^^^^^^^^

For large Web services with large numbers of endpoints, a single, monolithic router may not to optimal. To avoid having each request test every pattern-based route, you can break up a router into sub-routers.

This works because a ``Router`` is type of middleware, and can be used wherever middleware can be used.

Here's an example where all of the traffic beginning with ``/cats/`` is sent to one router, and all the traffic for endpoints beginning with ``/dogs/`` is sent to another.

.. code-block:: php

    $server = new Server();

    $catRouter = $server->createRouter()
        ->register("GET", "/cats/", $catReader)
        ->register("POST", "/cats/", $catWriter)
        // ... many more endpoints starting with /cats/
        ->register("POST", "/cats/{cat}/photo/{gallery}/{width}x{height}.{extension}", $catImageHandler);

    $dogRouter = $server->createRouter()
        ->register("GET,POST", "/dogs/", $dogHandler)
        // ... many more endpoints starting with /dogs/
        ->register("POST", "/dogs/{dog}/photo/{gallery}/{width}x{height}.{extension}", $dogImageHandler);

    $server->add($server->createRouter()
        ->register("*", "/cats/*", $catRouter)
        ->register("*", "/dogs/*", $dogRouter)
    );

    $server->respond();

.. _preg_match: http://php.net/manual/en/function.preg-match.php
.. _URI Template: `URI Templates`_s
.. _URI Templates: uri-templates.html
.. _middleware: middleware.html
