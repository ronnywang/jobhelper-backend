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
}
