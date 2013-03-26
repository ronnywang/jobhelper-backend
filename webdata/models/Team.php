<?php

class Team extends Pix_Table
{
    public function init()
    {
        $this->_name = 'team';

        $this->_primary = 'team_id';

        $this->_columns['team_id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['name'] = array('type' => 'varchar', 'size' => 64);
        $this->_columns['note'] = array('type' => 'text');

    }
}
