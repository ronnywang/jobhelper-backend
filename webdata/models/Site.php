<?php

class Site
{
    public function findCompanyByInfo($info)
    {
        if ('104' == $info->from) {
            return Site104::findCompanyByInfo($info);
        }

        if ('518' == $info->from) {
            return Site518::findCompanyByInfo($info);
        }

        if ('1111' == $info->from) {
            return Site1111::findCompanyByInfo($info);
        }

        if ('yes123' == $info->from) {
            return SiteYes123::findCompanyByInfo($info);
        }
    }
}
