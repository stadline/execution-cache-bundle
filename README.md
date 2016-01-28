Installation
============

This bundle provides a caching mechanism at application level. It's very similar to HTTP caching at reverse-proxy level, but execution
cache is done later. This way, the request has already been checked by the security layer but we can running the controller twice if
the result should be the same.

The cache storage will provide different result based on Request headers and body. If the exact same request is sent twice and the
response is cached, the controller is not run and the response is sent.

Step 1: Install the AdapterBundle
---------------------------------

This bundle uses a PSR-6 cache implementation provided by https://github.com/php-cache/adapter-bundle. It can be Memcached, Redis,
Filesystem, Void or even your own implementation. The choice is yours!

Please check their docs before installing this bundle.

Step 2: Install the Bundle
--------------------------

Open a command console, go to your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require stadline/execution-cache-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 3: Enable the Bundle
-------------------------

Then, enable the bundle by adding the following line in the `app/AppKernel.php`
file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new Stadline\ExecutionCacheBundle\StadlineExecutionCacheBundle(),
        );

        // ...
    }

    // ...
}
```

Step 4: Add the ExecutionCache annotation
-----------------------------------------

To enable the cache, you must add an annotation on the controller method you want to be cached.

```php
<?php

namespace AcmeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Stadline\ExecutionCacheBundle\Annotation\ExecutionCache;

class BrandController extends Controller
{
    /**
     * @ExecutionCache(lifetime=3600)
     */
    public function indexAction(Request $request)
    {
        // do some heavy stuff
    }
}
```

You can set a cache TTL if you want, but it's optional.

_That's all!_

Configuration
-------------

You can override the configuration easily

```
# Default configuration for extension with alias: "stadline_execution_cache"
stadline_execution_cache:
    storage:
        prefix:               exc_
        default_ttl:          300
        pool_adapter:         cache
```

You can set the key prefix and the default TTL.

The pool_adapter can be any CachePool implementation, check http://www.php-cache.com/en/latest/symfony/adapter-bundle/#configuration

For example, if you defined a provider called ```my_filesystem``` you can use ```cache.provider.my_file_system``` (or just ```cache```
if it's the default provider)
