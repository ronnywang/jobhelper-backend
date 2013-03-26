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
        foreach (Package::search(1) as $package) {
            $package_info = new StdClass;
            $package_info->id = intval($package->package_id);
            $package_info->name = $package->name;
            $package_info->url = 'http://' . $_SERVER['SERVER_NAME'] . '/package/show/' . $package->package_id;
            $package_info->updated_at = intval($package->updated_at);
            $package_info->package_time = intval($package->package_time);
            $packages[] = $package_info;
        }
        $ret->packages = $packages;

        return $this->json($ret);
    }
}
