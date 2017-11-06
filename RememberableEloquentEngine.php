<?php

namespace App\Datatables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Yajra\Datatables\Request;
use Yajra\Datatables\Engines\EloquentEngine;
use Illuminate\Support\Facades\Log;

/**
 * Class EloquentEngine.
 * S'inspirer de https://github.com/dwightwatson/rememberable/blob/master/src/Query/Builder.php
 * EloquentEngine hérite de QueryBuilderEngine
 * $this->query = Query/Builder.php
 * @package Yajra\Datatables\Engines
 * @author  Arjay Angeles <aqangeles@gmail.com>
 */
class RememberableEloquentEngine extends EloquentEngine
{

    /**
     * The number of minutes to cache the query.
     *
     * @var int
     */
    protected $cacheMinutes = null;

    /**
     * The key that should be used when caching the query.
     *
     * @var string
     */
    protected $cacheKey = null;

    /**
     * A cache prefix.
     *
     * @var string
     */
    protected $cachePrefix = 'rememberabledatatables';

    /**
     * The tags for the query cache.
     *
     * @var array
     */
    protected $cacheTags;

    /**
     * The cache driver to be used.
     *
     * @var string
     */
    protected $cacheDriver;

    /**
     * Get results
     * OVERRIDDEN FROM QueryBuilderEngine
     *
     * @return array|static[]
     */
    public function results()
    {
        if ( ! is_null($this->cacheMinutes)) {
            return $this->getCached();
        }
        return $this->results ?: $this->results = $this->query->get();
    }

    /**
     * Execute the query as a cached "select" statement.
     *
     * @param  array  $columns
     * @return array
     */
    public function getCached($columns = ['*'])
    {
//        if (is_null($this->columns)) {
//            $this->columns = $columns;
//        }
        // If the query is requested to be cached, we will cache it using a unique key
        // for this database connection and query statement, including the bindings
        // that are used on this query, providing great convenience when caching.
        list($key, $minutes) = $this->getCacheInfo();
        $cache = $this->getCache();
        $callback = $this->getCacheCallback($columns);
        Log::debug('getcachecallback '.var_export($callback, true));
        // If we've been given a DateTime instance or a "minutes" value that is
        // greater than zero then we'll pass it on to the remember method.
        // Otherwise we'll cache it indefinitely.
        if ($minutes instanceof DateTime || $minutes > 0) {
            return $cache->remember($key, $minutes, $callback);
        }
        return $cache->rememberForever($key, $callback);
    }

    /**
     * Get the Closure callback used when caching queries.
     *
     * @param  array  $columns
     * @return \Closure
     */
    protected function getCacheCallback($columns)
    {
        return function () use ($columns) {
            $this->cacheMinutes = null;
            Log::debug('getCacheCallback Closure called ');
            return $this->query->get($columns);
        };
    }

    /**
     * Indicate that the query results should be cached.
     *
     * @param  \DateTime|int  $minutes
     * @param  string  $key
     * @return $this
     */
    public function remember($minutes, $key = null)
    {
        list($this->cacheMinutes, $this->cacheKey) = [$minutes, $key];
        return $this;
    }

    /**
     * Indicate that the results, if cached, should use the given cache tags.
     *
     * @param  array|mixed  $cacheTags
     * @return $this
     */
    public function cacheTags($cacheTags)
    {
        $this->cacheTags = $cacheTags;
        return $this;
    }

    /**
     * Generate the unique cache key for the query.
     *
     * @return string
     */
    public function generateCacheKey()
    {
        $name = $this->connection->getName();
        return hash('sha256', $name.$this->query->toSql().serialize($this->query->getBindings()));
    }

    /**
     * Flush the cache for the current model or a given tag name
     *
     * @param  mixed  $cacheTags
     * @return boolean
     */
    public function flushCache($cacheTags = null)
    {
        $store = app('cache')->getStore();
        if ( ! method_exists($store, 'tags')) {
            return false;
        }
        $cacheTags = $cacheTags ?: $this->cacheTags;
        $store->tags($cacheTags)->flush();
        return true;
    }

    /**
     * Indicate that the results, if cached, should use the given cache driver.
     *
     * @param  string  $cacheDriver
     * @return $this
     */
    public function cacheDriver($cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;
        return $this;
    }

    /**
     * Get the cache object with tags assigned, if applicable.
     *
     * @return \Illuminate\Cache\CacheManager
     */
    protected function getCache()
    {
        $cache = app('cache')->driver($this->cacheDriver);
        return $this->cacheTags ? $cache->tags($this->cacheTags) : $cache;
    }

    /**
     * Get the cache key and cache minutes as an array.
     *
     * @return array
     */
    protected function getCacheInfo()
    {
        return [$this->getCacheKey(), $this->cacheMinutes];
    }


    /**
     * Get a unique cache key for the complete query.
     *
     * @return string
     */
    public function getCacheKey()
    {
        return $this->cachePrefix.':'.($this->cacheKey ?: $this->generateCacheKey());
    }

    /**
     * Set the cache prefix.
     *
     * @param string $prefix
     *
     * @return $this
     */
    public function prefix($prefix)
    {
        $this->cachePrefix = $prefix;
        return $this;
    }

}
