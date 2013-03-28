<?php

class TeamPackage extends Pix_Table
{
    public function init()
    {
        $this->_name = 'team_package';
        $this->_primary = array('team_id', 'package_id');

        $this->_columns['team_id'] = array('type' => 'int');
        $this->_columns['package_id'] = array('type' => 'int');

        $this->_relations['team'] = array('rel' => 'has_one', 'type' => 'Team', 'foreign_key' => 'team_id');
        $this->_relations['package'] = array('rel' => 'has_one', 'type' => 'Package', 'foreign_key' => 'package_id');

        $this->addIndex('package_id', array('package_id', 'team_id'), 'unique');
    }
}
