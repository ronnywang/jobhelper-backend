<?php

class DataSet extends Pix_Table
{
    public function init()
    {
        $this->_name = 'data_set';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['data'] = array('type' => 'text');
    }
}
