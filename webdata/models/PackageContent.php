<?php

class PackageContent extends Pix_Table
{
    public function init()
    {
        $this->_name = 'package_content';

        $this->_primary = 'package_id';

        $this->_columns['package_id'] = array('type' => 'int');
        $this->_columns['content'] = array('type' => 'text');
    }
}
