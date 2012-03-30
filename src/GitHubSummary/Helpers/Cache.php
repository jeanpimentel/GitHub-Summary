<?php

namespace GitHubSummary\Helpers;

class Cache
{

    private $path;

    function __construct($path = NULL)
    {
        if (is_null($path))
            $this->path = __DIR__ . '/../Resources/Cache';
        else
            $this->path = $path;
    }

    public function set($key, $value)
    {
        return file_put_contents($this->buildKey($key), $value);
    }

    public function get($key)
    {
        if ($this->has($key))
            return file_get_contents($this->buildKey($key));

        return FALSE;
    }

    public function has($key)
    {
        return file_exists($this->buildKey($key)) && (time() - (20 * 60) < filemtime($this->buildKey($key)));
    }

    public function remove($key)
    {
        if ($this->has($key))
            return unlink($this->buildKey($key));

        return FALSE;
    }

    public function removeAll()
    {
        foreach (glob($this->path . '/*') as $cache)
            unlink($cache);
    }

    private function buildKey($key)
    {
        return $this->path . '/' . md5($key);
    }

}
