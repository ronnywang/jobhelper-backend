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

    public function canEdit($obj)
    {
        if (Pix_Table::is_a($obj, 'Team')) {
            return TeamPackage::search(array('team_id' => $obj->team_id, 'package_id' => $this->package_id))->count();
        }

        if (Pix_Table::is_a($obj, 'TeamMember')) {
            return TeamPackage::search(array('team_id' => $obj->team_id, 'package_id' => $this->package_id))->count();
        }

        if (Pix_Table::is_a($obj, 'User')) {
            foreach (TeamMember::search(array('user_id' => $obj->user_id)) as $team_member) {
                if (TeamPackage::search(array('team_id' => $team_member->team_id, 'package_id' => $this->package_id))->count()) {
                    return true;
                }
            }
            return false;
        }

        return false;
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
        $this->_columns['package_time'] = array('type' => 'int', 'default' => 0);

        $this->_relations['content'] = array('rel' => 'has_one', 'type' => 'PackageContent', 'foreign_key' => 'package_id', 'delete' => true);
        $this->_relations['team'] = array('rel' => 'has_one', 'type' => 'Team', 'foreign_key' => 'team_id');
        $this->_relations['package_teams'] = array('rel' => 'has_many', 'type' => 'TeamPackage', 'foreign_key' => 'package_id', 'delete' => true);
    }
}
