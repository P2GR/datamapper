<?php

namespace Tests\Support;

use DataMapper;

class CacheableModelStub extends DataMapper
{
    public function __construct()
    {
        $this->model = 'cacheable_model_stub';
        $this->table = 'cacheable_model_stubs';
        $this->fields = array('id', 'name');
        $this->has_many = array();
        $this->has_one = array();
        $this->validation = array();
        $this->_field_tracking = array(
            'get_rules' => array(),
            'matches' => array(),
            'intval' => array('id'),
        );
        $this->_instantiations = array();
        $this->stored = new \stdClass();
        $this->db = new FakeQueryState();
    }
}
