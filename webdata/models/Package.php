<?php

class PackageRow extends Pix_Table_Row
{
    public function preInsert()
    {
        $this->created_at = time();
    }

    public function preSave()
    {
        $this->updated_at = time();
    }
}

class Package extends Pix_Table
{
    public function init()
    {
        $this->_name = 'package';

        $this->_primary = 'package_id';
        $this->_rowClass = 'PackageRow';

        $this->_columns['package_id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['name'] = array('type' => 'varchar', 'size' => 64);
        $this->_columns['updated_at'] = array('type' => 'int', 'default' => 0);
        $this->_columns['created_at'] = array('type' => 'int', 'default' => 0);
        $this->_columns['team_id'] = array('type' => 'int');
        $this->_columns['note'] = array('type' => 'text');
    }
}
