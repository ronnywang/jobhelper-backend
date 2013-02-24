<?php

class CompanyService
{
    public static function http($url)
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

        $id = null;
        // 有多個的話，看看是不是只有一個 prefix
        foreach ($result->data as $data) {
            if ($data->{'公司名稱'}) {
                if (0 !== strpos($data->{'公司名稱'}, $name)) {
                    continue;
                }
            } elseif ($data->{'商業名稱'}) {
                if (0 !== strpos($data->{'商業名稱'}, $name)) {
                    continue;
                }
            } else {
                // 找不到名稱，就直接找不到
                return false;
            }
            // 如果已經有找到過了，那就不要了
            if (!is_null($id)) {
                return false;
            }
            $id = $data->{'統一編號'};
        }

        if (!is_null($id)) {
            return $id;
        }
        return false;
    }
}
