<?php

class Site1111 extends Pix_Table
{
    public function init()
    {
        $this->_name = 'site_1111';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['company_no'] = array('type' => 'int');
    }

    public static function findCompanyByInfo($info)
    {
        if (!preg_match('#找工作機會-(.*).htm$#', $info->company_link, $matches)) {
            return false;
        }

        if ($siteinfo = Site1111::find(intval($matches[1]))) {
            return $siteinfo->company_no;
        }

        $id = intval(CompanyService::getCompanyByName($info->name));
        // Ex: 揚博科技股份有限公司(揚博科技)
        if (!$id and preg_match('#(.*)\((.*)\)#', $info->name, $matches)) {
            $id = intval(CompanyService::getCompanyByName($matches[1]));
        }

        if ($id) {
            Site1111::insert(array(
                'id' => intval($matches[1]),
                'company_no' => $id,
            ));
        }

        return $id;
    }
}
