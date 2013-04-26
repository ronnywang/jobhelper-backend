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
            if ($package->getEAV('notice')) {
                $package_info->default = false;
                $package_info->notice = strval($package->getEAV('notice'));
            } else {
                $package_info->default = true;
                $package_info->notice = '';
            }
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
        $m = new MemcacheSASL();
        $m->addServer(getenv('MEMCACHE_SERVER'), getenv('MEMCACHE_PORT'));
        $m->setSaslAuthData(getenv('MEMCACHE_USERNAME'), getenv('MEMCACHE_PASSWORD'));

        $name = strval(trim($_GET['name']));
        $start = microtime(true);
        if (mb_strlen($name, 'UTF-8') < 3) {
            return $this->json(array('error' => true, 'message' => '最少要三個字'));
        }
        // url 備用
        // $url = strval($_GET['url']);
        $packages = array_unique(array_map('intval', explode(',', strval($_GET['packages']))));

        $terms = array();
        // 處理 "宏達電 HTC Corporation_宏達國際電子股份有限公司"
        foreach (explode('_', $name) as $parted_name) {
            // 包含 (xxxxx), Ex: 台灣保全股份有限公司(總公司)
            if (preg_match('#\(([^)]*)\)#', $parted_name, $matches)) {
                if (preg_match('#有限公司$#', $matches[1])) {
                    $terms[] = '(name:"' . addslashes($matches[1]) . '")';
                }
                $parted_name = preg_replace('#\([^)]*\)#', '', $parted_name);
            }
            // 名稱假如包含公司但不是公司結尾, Ex: xxx公司xx廠
            if (preg_match('#公司.+#', $parted_name)) {
                preg_match('#(.*公司).+#', $parted_name, $matches);
                $terms[] = '(name:"' . addslashes($matches[1]) . '")';

            } else {
                $terms[] = '(name:"' . addslashes($parted_name) . '")';
            }
        }
        $q = urlencode(implode(' OR ', $terms));
        $v = '2013042600';
        $cache_key = 'SearchCache:' . crc32($q) . ':' . md5($q) . ':' . crc32(implode(',', $packages)) . ':' . $v;
        if (!$_GET['force'] and $data = $m->get($cache_key)) {
            $result = array('error' => false, 'data' => json_decode($data));
            $result['took'] = microtime(true) - $start;
            $result['from_cache'] = true;
            $result['query'] = urldecode($q);
            return $this->json($result);
        }

        $url = 'http://search-1.hisoku.ronny.tw:9200/jobhelper/_search?q=' . $q . '&size=100';

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
        $result['query'] = urldecode($q);
        $data = array();
        foreach ($search_result->hits->hits as $hit) {
            if (!in_array($hit->_source->package_id, $packages)) {
                continue;
            }
            $data[] = $hit->_source;
        }
        $m->set($cache_key, json_encode($data), 3600);
        $result['data'] = $data;
        $result['took'] = microtime(true) - $start;
        return $this->json($result);
    }
}
