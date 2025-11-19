<?php

namespace Tests\Support;

class FakeQueryState
{
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
}
