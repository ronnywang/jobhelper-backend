<?php

class TeamMember extends Pix_Table
{
    public function init()
    {
        $this->_name = 'team_member';

        $this->_primary = 'user_id';

        $this->_columns['user_id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['team_id'] = array('type' => 'int');
        $this->_columns['user_name'] = array('type' => 'varchar', 'size' => 64);

        $this->_relations['team'] = array('rel' => 'has_one', 'type' => 'Team', 'foreign_key' => 'team_id');

        $this->addIndex('team_id', array('team_id', 'user_id'), 'unique');
        $this->addIndex('user_name', array('user_name'), 'unique');
    }
}
