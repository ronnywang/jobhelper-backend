<?php

class CompanyInfo extends Pix_Table
{
    public function init()
    {
        $this->_primary = 'id';
        $this->_name = 'companyinfo';

        $this->_columns['id'] = array('type' => 'int');
        $this->_columns['info'] = array('type' => 'text');
    }

    public function get($id)
    {
        if (!$info = CompanyInfo::find($id)) {
            $info = CompanyInfo::insert(array(
                'id' => $id,
                'info' => json_encode(CompanyService::getCompanyInfoById($id)),
            ));
        }

        return json_decode($info->info);
    }
}
