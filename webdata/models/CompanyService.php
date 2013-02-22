<?php

class CompanyService
{
    protected static function http($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $ret = curl_exec($curl);
        $info = (curl_getinfo($curl));
        if ($info['http_code'] != 200) {
            throw new Exception('http not 200');
        }

        if (!$data = json_decode($ret)) {
            throw new Exception('not valid json');
        }
        return $data;
    }

    public static function getCompanyByName($name)
    {
        $result = self::http('http://company.g0v.ronny.tw/api/search?q=' . urlencode($name) . '&alive_only=1');
        if ($result->found == 1) {
            return $result->data[0]->{'統一編號'};
        }

        return false;
    }
}
