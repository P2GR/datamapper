<?php

namespace Tests\Support;

use DataMapper;

class FakeDataMapper extends DataMapper
{
    /**
     * @var array<int, object>
     */
    protected $mockRows = array();

    /**
     * @var array<int, array>
     */
    public $queryLog = array();

    /**
     * @var int
     */
    public $getCalls = 0;

    /**
     * @var int|null
     */
    public $lastLimit = NULL;

    /**
     * @var int|null
     */
    public $lastOffset = NULL;

    /**
     * @var array<int, array>
     */
    public $cacheLog = array();

    /**
     * @var int
     */
    public $clearCacheReturn = 0;

    public function __construct(array $rows = array())
    {
        // Do not call parent constructor to avoid CodeIgniter dependencies
        $this->model = 'fake';
        $this->table = 'fakes';
        $this->fields = $this->detectFields($rows);
        $this->setRows($rows);

        $this->_field_tracking = array('matches' => array());

        // Provide minimal database stub to satisfy DataMapper expectations
        $this->db = (object) array(
            'queries' => array(),
            'query_times' => array(),
        );
    }

    /**
     * Replace the mock dataset used by get().
     *
     * @param array<int, array<string, mixed>> $rows
     * @return void
     */
    public function setRows(array $rows)
    {
        $this->mockRows = array();
        foreach ($rows as $row) {
            $this->mockRows[] = (object) $row;
        }
        $this->all = $this->mockRows;
    }

    /**
     * Simulate DataMapper::get by returning the mock dataset.
     *
     * @param int|null $limit
     * @param int|null $offset
     * @return FakeDataMapper
     */
    public function get($limit = NULL, $offset = NULL)
    {
        $this->getCalls++;
        if ($limit === NULL && $this->lastLimit !== NULL) {
            $limit = $this->lastLimit;
        }

        if ($offset === NULL && $this->lastOffset !== NULL) {
            $offset = $this->lastOffset;
        }

        $this->lastLimit = $limit;
        $this->lastOffset = $offset;

        $results = $this->mockRows;
        if ($offset !== NULL && $offset > 0) {
            $results = array_slice($results, $offset);
        }
        if ($limit !== NULL) {
            $results = array_slice($results, 0, $limit);
        }

        $this->all = $results;
        return $this;
    }

    public function where($field, $value = NULL, $escape_or_operator = TRUE)
    {
        $this->queryLog[] = array('where', $field, $value, $escape_or_operator);
        return $this;
    }

    public function or_where($field, $value = NULL, $escape_or_operator = TRUE)
    {
        $this->queryLog[] = array('or_where', $field, $value, $escape_or_operator);
        return $this;
    }

    public function limit($limit, $offset = '')
    {
        $this->lastLimit = $limit;
        $this->lastOffset = ($offset === '') ? NULL : $offset;
        $this->queryLog[] = array('limit', $limit, $offset);
        return $this;
    }

    public function offset($offset)
    {
        $this->lastOffset = $offset;
        $this->queryLog[] = array('offset', $offset);
        return $this;
    }

    public function order_by($field, $direction = 'asc')
    {
        $this->queryLog[] = array('order_by', $field, $direction);
        return $this;
    }

    public function cache($ttl = 3600, $key = NULL)
    {
        $this->cacheLog[] = array('cache', $ttl, $key);
        return $this;
    }

    public function no_cache()
    {
        $this->cacheLog[] = array('no_cache');
        return $this;
    }

    public function cache_relations($ttl = 3600)
    {
        $this->cacheLog[] = array('cache_relations', $ttl);
        return $this;
    }

    public function clear_cache($pattern = NULL)
    {
        $this->cacheLog[] = array('clear_cache', $pattern);
        return $this->clearCacheReturn;
    }

    public function resetLog()
    {
        $this->queryLog = array();
        $this->cacheLog = array();
        $this->getCalls = 0;
        $this->lastLimit = NULL;
        $this->lastOffset = NULL;
    }

    /**
     * Ensure the cloned instance copies the mock dataset.
     */
    public function __clone()
    {
        $clonedData = array();
        foreach ($this->mockRows as $row) {
            $clonedData[] = clone $row;
        }
        $this->mockRows = $clonedData;
        $this->all = $clonedData;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, string>
     */
    private function detectFields(array $rows)
    {
        if (empty($rows)) {
            return array('id');
        }
        $first = reset($rows);
        return is_array($first) ? array_keys($first) : array('id');
    }
}
