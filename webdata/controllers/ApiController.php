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

        return $this->jsonp($ret, $_GET['callback']);
    }

    public function getpackageAction()
    {
        $ret = new StdClass;

        if (!$package = Package::find(intval($_GET['id']))) {
            $ret->error = true;
            $ret->message = '找不到這個資料包';
            return $this->jsonp($ret, $_GET['callback']);
        }

        $ret->package_time = intval($package->package_time);

        $rows = $package->getRecords();
        $ret->content = $rows;
        return $this->jsonp($ret, $_GET['callback']);
    }

    public function searchAction()
    {
        /*$m = new MemcacheSASL();
        $m->addServer(getenv('MEMCACHE_SERVER'), getenv('MEMCACHE_PORT'));
        $m->setSaslAuthData(getenv('MEMCACHE_USERNAME'), getenv('MEMCACHE_PASSWORD'));*/

        $name = strval(trim($_GET['name']));
        $start = microtime(true);
        if (mb_strlen($name, 'UTF-8') < 2) {
            return $this->jsonp(array('error' => true, 'message' => '最少要兩個字'), $_GET['callback']);
        }
        // url 備用
        // $url = strval($_GET['url']);
        if ($_GET['packages'] == 'cookie') {
            $online_package_status = Package::search(1)->toArray('status');
            $has_notices = array();
            foreach (EAV::search(array('table' => 'Package', 'key' => 'notice'))->searchIn('id', $online_package_ids) as $eav) {
                $has_notices[$eav->id] = $eav->value;
            };

            if (!$_COOKIE['choosed_packages']) {
                $cookie_settings = array();
            } elseif (!$json = json_decode($_COOKIE['choosed_packages'])) {
                $cookie_settings = array();
            } elseif (!is_array($json)) {
                $cookie_settings = array();
            } else {
                $cookie_settings = $json;
            }

            $packages = array();
            foreach ($online_package_status as $id => $status) {
                if (array_key_exists($id, $cookie_settings) and !is_null($cookie_settings[$id])) { // 如果 cookie 有指定以 cookie 最優先
                    if ($cookie_settings[$id]) {
                        $packages[] = $id;
                    }
                } elseif (array_key_exists($id, $has_notices) and $has_notices[$id]) { // 有 notice 就不要預設
                } elseif ($status == 0) {
                    $packages[] = $id;
                }
            }
        } else {
            $packages = array_unique(array_map('intval', explode(',', strval($_GET['packages']))));
        }

        $terms = array();
        // 處理 "宏達電 HTC Corporation_宏達國際電子股份有限公司"
        $name = str_replace('＿', '_', $name);
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
        $v = '2013042601';
        $cache_key = 'SearchCache:' . crc32($q) . ':' . md5($q) . ':' . crc32(implode(',', $packages)) . ':' . $v;
        if (!$_GET['force'] and $row = Cache::find(md5($cache_key)) and $row->updated_at > time() - 3600) {
            $result = array('error' => false, 'data' => json_decode($row->value));
            $result['took'] = microtime(true) - $start;
            $result['from_cache'] = true;
            $result['query'] = urldecode($q);
            return $this->jsonp($result, $_GET['callback']);
        }

        try {
            $search_result = Elastic::dbQuery("/_search?q={$q}&size=200");
        } catch (Exception $e) {
            header('HTTP/1.1 ' . $info['http_code']);
            return $this->jsonp(array('error' => true, 'message' => '搜尋出問題'), $_GET['callback']);
        }
        $result = array('error' => false, 'data' => array());
        $result['query'] = urldecode($q);
        $data = array();
        foreach ($search_result->hits->hits as $hit) {
            if (!in_array($hit->_source->package_id, $packages)) {
                continue;
            }
            $data[] = $hit->_source;
        }
        try {
            Cache::insert(array(
                'key' => md5($cache_key),
                'value' => json_encode($data),
                'updated_at' => time(),
            ));
        } catch (Pix_Table_DuplicateException $e) {
            Cache::search(array(
                'key' => md5($cache_key),
            ))->update(array(
                'value' => json_encode($data),
                'updated_at' => time(),
            ));
        }
        $result['data'] = $data;
        $result['took'] = microtime(true) - $start;
        return $this->jsonp($result, $_GET['callback']);
    }
}
