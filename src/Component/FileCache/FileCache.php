<?php

namespace WP\Console\Component\FileCache;

/**
 * Allows to cache data based on file modification dates.
 */
class FileCache implements FileCacheInterface
{

    /**
     * Prefix that is used for cache entries.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Static cache that contains already loaded cache entries.
     *
     * @var array
     */
    protected static $cached = [];

    /**
     * The collection identifier of this cache.
     *
     * @var string
     */
    protected $collection;

    /**
     * The cache backend backing this FileCache object.
     *
     * @var \WP\Console\Component\FileCache\FileCacheBackendInterface
     */
    protected $cache;

    /**
     * Constructs a FileCache object.
     *
     * @param string      $prefix
     *   The cache prefix.
     * @param string      $collection
     *   A collection identifier to ensure that the same files could be cached for
     *   different purposes without clashing.
     * @param string|null $cache_backend_class
     *   (optional) The class that should be used as cache backend.
     * @param array       $cache_backend_configuration
     *   (optional) The configuration for the backend class.
     */
    public function __construct($prefix, $collection, $cache_backend_class = null, array $cache_backend_configuration = [])
    {
        if (empty($prefix)) {
            throw new \InvalidArgumentException('Required prefix configuration is missing');
        }

        $this->prefix = $prefix;
        $this->collection = $collection;

        if (isset($cache_backend_class)) {
            $this->cache = new $cache_backend_class($cache_backend_configuration);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($type)
    {
        $types = [$type];
        $cached = $this->getMultiple($types);
        return isset($cached[$type]) ? $cached[$type] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(array $types)
    {
        $data = [];
        $remaining_cids = [];

        // First load from the static cache what we can.
        foreach ($types as $type) {
            $cid = $this->prefix . ':' . $this->collection . ':' . $type;
            if (isset(static::$cached[$cid])) {
                $data[$type] = static::$cached[$cid]['data'];
            } else {
                // Collect a list of cache IDs that we still need to fetch from cache
                // backend.
                $remaining_cids[$cid] = $type;
            }
        }

        // If there are any cache IDs left to fetch from the cache backend.
        if ($remaining_cids && $this->cache) {
            $cache_results = $this->cache->fetch(array_keys($remaining_cids)) ?: [];
            foreach ($cache_results as $cid => $cached) {
                $data[$cached['type']] = $cached['data'];
                static::$cached[$cid] = $cached;
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function set($type, $data)
    {
        $cached = [
            'type' => $type,
            'data' => $data,
        ];

        $cid = $this->prefix . ':' . $this->collection . ':' . $type;
        static::$cached[$cid] = $cached;
        if ($this->cache) {
            $this->cache->store($cid, $cached);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($filepath)
    {
        $realpath = realpath($filepath);
        $cid = $this->prefix . ':' . $this->collection . ':' . $realpath;

        unset(static::$cached[$cid]);
        if ($this->cache) {
            $this->cache->delete($cid);
        }
    }

    /**
     * Resets the static cache.
     */
    public static function reset()
    {
        static::$cached = [];
    }
}
