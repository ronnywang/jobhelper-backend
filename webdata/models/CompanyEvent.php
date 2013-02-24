<?php

class CompanyEvent extends Pix_Table
{
    public function init()
    {
        $this->_name = 'company_event';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['company_no'] = array('type' => 'int');
        $this->_columns['time'] = array('type' => 'int');
        $this->_columns['from'] = array('type' => 'varchar', 'size' => 255);
        $this->_columns['snapshot'] = array('type' => 'varchar', 'size' => 255);
        $this->_columns['message'] = array('type' => 'text');

        $this->addIndex('company', array('company_no', 'time'));
    }
}
