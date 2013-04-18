<?php

class ApiController extends Pix_Controller
{
    public function init()
    {
    }

    public function getpackagesAction()
    {
        $ret = new StdClass;
        $packages = array();
        $search_cond = array('status' => 0);
        if ($_GET['test'] == 1) {
            $search_cond = 1;
        }
        foreach (Package::search($search_cond) as $package) {
            $package_info = new StdClass;
            $package_info->id = intval($package->package_id);
            $package_info->name = $package->name;
            $package_info->url = 'http://' . $_SERVER['SERVER_NAME'] . '/package/show/' . $package->package_id;
            $package_info->updated_at = intval($package->updated_at);
            $package_info->package_time = intval($package->package_time);
            $package_info->default = true;
            $packages[] = $package_info;
        }
        $ret->packages = $packages;
        $ret->error = false;

        return $this->json($ret);
    }

    public function getpackageAction()
    {
        $ret = new StdClass;

        if (!$package = Package::find(intval($_GET['id']))) {
            $ret->error = true;
            $ret->message = '找不到這個資料包';
            return $this->json($ret);
        }

        $ret->package_time = intval($package->package_time);
        $content = $package->content->content;
        $rows = array();
        foreach (explode("\n", trim($content)) as $line) {
            $rows[] = str_getcsv($line);
        }
        $ret->content = $rows;
        return $this->json($ret);
    }

    public function searchAction()
    {
        $name = strval($_GET['name']);
        if (mb_strlen($name, 'UTF-8') < 3) {
            return $this->json(array('error' => true, 'message' => '最少要三個字'));
        }
        // url 備用
        // $url = strval($_GET['url']);
        $packages = array_unique(array_map('intval', explode(',', strval($_GET['packages']))));

        $terms = array();
        // 處理 "宏達電 HTC Corporation_宏達國際電子股份有限公司"
        foreach (explode('_', $name) as $parted_name) {
            $terms[] = '(name:"' . $parted_name . '")';
        }
        $q = urlencode(implode(' OR ', $terms));
        $url = 'http://search-1.hisoku.ronny.tw:9200/jobhelper/_search?q=' . $q;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($curl);
        $info = curl_getinfo($curl);
        if ($info['http_code'] != 200) {
            return $this->json(array('error' => true, 'message' => '搜尋出問題'));
        }
        $search_result = json_decode($content);
        $result = array('error' => false, 'data' => array());
        $data = array();
        foreach ($search_result->hits->hits as $hit) {
            if (!in_array($hit->_source->package_id, $packages)) {
                continue;
            }
            $data[] = $hit->_source;
        }
        $result['data'] = $data;
        return $this->json($result);
    }
}
