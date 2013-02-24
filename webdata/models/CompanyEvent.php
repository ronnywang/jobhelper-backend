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
        // 資料來源: 1-台北市勞工局
        $this->_columns['type'] = array('type' => 'int');
        $this->_columns['data'] = array('type' => 'text');

        $this->addIndex('company', array('company_no', 'time'));
    }
}
