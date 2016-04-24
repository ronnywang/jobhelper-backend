<?php

class DataSetLog extends Pix_Table
{
    public function init()
    {
        $this->_name = 'data_set_log';
        $this->_primary = array('id', 'time');

        $this->_columns['id'] = array('type' => 'int');
        $this->_columns['time'] = array('type' => 'int');
        $this->_columns['changed_by'] = array('type' => 'text');
        $this->_columns['origin_data'] = array('type' => 'text');
    }
}
