<?php

namespace Tests\Support;

class FakeQueryState
{
    public $db_debug = FALSE;
    public $queries = array();
    public $query_times = array();
    /**
     * @var array<string, mixed>
     */
    private $state;

    public function __construct(array $state = array())
    {
        $defaults = array(
            'qb_where' => array(),
            'qb_select' => array(),
            'qb_join' => array(),
            'qb_orderby' => array(),
            'qb_groupby' => array(),
            'qb_limit' => NULL,
            'qb_offset' => NULL,
        );

        $this->state = array_merge($defaults, $state);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function dm_get($key)
    {
        return array_key_exists($key, $this->state) ? $this->state[$key] : NULL;
    }
    public function dm_set($key, $value) { $this->state[$key] = $value; }
    public function dm_call_method($method, ...$args)
    {
        return $method === '_reset_select' ? $this->reset_query() : NULL;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setState($key, $value)
    {
        $this->state[$key] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getState()
    {
        return $this->state;
    }

    public function reset_query()
    {
        $this->__construct();
        return $this;
    }
    public function stop_cache() { return $this; }
    public function flush_cache() { return $this; }
}
