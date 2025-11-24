<?php

namespace Tests\Support;

class FillableFakeModel extends FakeDataMapper
{
    /**
     * @var array<string, mixed>
     */
    public $savedPayload = array();

    public function __construct()
    {
        parent::__construct(array(
            array(
                'id' => NULL,
                'name' => NULL,
                'email' => NULL,
                'is_admin' => NULL,
                'secret' => NULL,
            )
        ));

        $this->setRows(array());
    }

    public function save($object = '', $related_field = '')
    {
        if (empty($this->id))
        {
            $this->id = 1;
        }

        $this->savedPayload = $this->_to_array();
        return TRUE;
    }
}
