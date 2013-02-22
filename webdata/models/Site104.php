<?php

class Site104 extends Pix_Table
{
    public function init()
    {
        $this->_name = 'site_104';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['name'] = array('type' => 'varchar', 'size' => 255);
        $this->_columns['company_no'] = array('type' => 'int');

        $this->addIndex('name', array('name'), 'unique');
    }
    public static function findCompanyByInfo($info)
    {
        // 目前找不到 104 的 id 規則...所以先用公司名稱來搜尋
        if ($siteinfo = Site104::search(array('name' => $info->name))->first()) {
            return $siteinfo->company_no;
        }

        $id = intval(CompanyService::getCompanyByName($info->name));

        // Ex: 和和機械股份有限公司_光電事業部
        if (!$id and false !== strpos($info->name, '_')) {
            $parts = explode('_', $info->name);
            foreach ($parts as $part) {
                $id = intval(CompanyService::getCompanyByName($part));
                if ($id) {
                    break;
                }
            }
        }

        if (!$id and preg_match('#^(.*公司).+$#', $info->name, $matches)) {
            $id = intval(CompanyService::getCompanyByName($matches[1]));
        }

        if ($id) {
            Site104::insert(array(
                'name' => $info->name,
                'company_no' => $id,
            ));
        }

        return $id;
    }
}
