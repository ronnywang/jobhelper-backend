<?php

class PackageRow extends Pix_Table_Row
{
    public function updateContent($content, $updated_at = null)
    {
        // 檢查 content
        foreach (explode("\n", trim($content)) as $no => $line) {
            $no = $no + 1;
            $terms = str_getcsv($line);

            if (count($terms) < 5) {
                throw new Exception("每一行至少要有 5 列，第 {$no} 行只有 " . count($terms));
            }

            if (false === strtotime($terms[1])) {
                throw new Exception("第 {$no} 行是無法判別的日期格式: " . $terms[1]);
            }

            if (!filter_var($terms[3], FILTER_VALIDATE_URL)) {
                throw new Exception("第四列是必需是原始連結，第 {$no} 行的連結不是正確的網址格式");
            }
        }

        try {
            $this->create_content(array(
                'content' => $content,
            ));
        } catch (Pix_Table_DuplicateException $e) {
            $this->content->update(array(
                'content' => $content,
            ));
        }

        if (is_null($updated_at)) {
            $updated_at = time();
        }
        $this->update(array('package_time' => $updated_at));
        $this->updateToSearch();
    }

    public function getEAVs()
    {
        return EAV::search(array('table' => 'Package', 'id' => $this->package_id));
    }

    public function updateToSearch()
    {
        // 先刪舊資料
        $curl = curl_init();
        $url = getenv('SEARCH_URL') . '/jobhelper/packages/_query?q=package_id:' . $this->package_id;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $requests = array();

        $content = $this->content->content;
        foreach (explode("\n", trim($content)) as $id => $line) {
            if ($line == '') {
                continue;
            }
            $id_info = new STdClass;
            $id_info->index = new StdClass;
            $id_info->index->_index = 'jobhelper';
            $id_info->index->_type = 'packages';
            $id_info->index->_id = $this->package_id . '-' . $id;
            $requests[] = json_encode($id_info);

            $rows = str_getcsv($line);
            $row = new StdClass;
            $row->package_id = $this->package_id;
            $row->name = $rows[0];
            $row->date = str_replace('/', '-', $rows[1]);
            $row->reason = $rows[2];
            $row->link = $rows[3];
            $row->snapshot = $rows[4];
            $requests[] = json_encode($row);
        }

        if (count($requests)) {
            $curl = curl_init();
            $url = getenv('SEARCH_URL') . '/jobhelper/_bulk?refresh=true';
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curl, CURLOPT_POSTFIELDS, implode("\n", $requests) . "\n");
            $ret = curl_exec($curl);
        }
    }

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
        // 0-一切正常, 1-測試中
        $this->_columns['status'] = array('type' => 'tinyint');
        $this->_columns['package_time'] = array('type' => 'int', 'default' => 0);

        $this->_relations['content'] = array('rel' => 'has_one', 'type' => 'PackageContent', 'foreign_key' => 'package_id', 'delete' => true);
        $this->_relations['team'] = array('rel' => 'has_one', 'type' => 'Team', 'foreign_key' => 'team_id');
        $this->_relations['package_teams'] = array('rel' => 'has_many', 'type' => 'TeamPackage', 'foreign_key' => 'package_id', 'delete' => true);

        $this->_hooks['eavs'] = array('get' => 'getEAVs');

        $this->addRowHelper('Pix_Table_Helper_EAV', array('getEAV', 'setEAV'));
    }
}
