<?php

class Cache extends Pix_Table
{
    public function init()
    {
        $this->_name = 'cache';
        $this->_primary = 'key';

        $this->_columns['key'] = array('type' => 'varchar', 'size' => 32);
        $this->_columns['value'] = array('type' => 'text');
        $this->_columns['updated_at'] = array('type' => 'int');
    }
}
