<?php

class Site518 extends Pix_Table
{
    public function init()
    {
        $this->_name = 'site_518';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['company_no'] = array('type' => 'int');
    }

    public static function findCompanyByInfo($info)
    {
        if (!preg_match('#job-comp_detail-(.*).html#', $info->company_link, $matches)) {
            return false;
        }

        if ($siteinfo = Site518::find(intval($matches[1]))) {
            return $siteinfo->company_no;
        }

        if (preg_match('#^(.*)認證$#', $info->name, $matches)) {
            $id = intval(CompanyService::getCompanyByName($matches[1]));
        } else {
            $id = intval(CompanyService::getCompanyByName($info->name));
        }

        if ($id) {
            Site518::insert(array(
                'id' => intval($matches[1]),
                'company_no' => $id,
            ));
        }

        return $id;
    }
}
