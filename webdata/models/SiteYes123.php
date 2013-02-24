<?php

class SiteYes123 extends Pix_Table
{
    public function init()
    {
        $this->_name = 'site_yes123';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'varchar', 'size' => 32);
        $this->_columns['company_no'] = array('type' => 'int');
    }

    public static function findCompanyByInfo($info)
    {
        if (!preg_match('#^\d+_\d+$#', $info->company_link) and strlen($info->company_link) > 32) {
            return false;
        }

        if ($siteinfo = SiteYes123::find($info->company_link)) {
            return $siteinfo->company_no;
        }

        $id = intval(CompanyService::getCompanyByName(trim($info->name)));
        if (!$id and preg_match('#^\((.*)\)(.*)$#', trim($info->name), $matches)) {
            $id = intval(CompanyService::getCompanyByName($matches[2]));
        }

        if ($id) {
            SiteYes123::insert(array(
                'id' => $info->company_link,
                'company_no' => $id,
            ));
        }

        return $id;
    }
}
