<?php

class UpdateController extends Pix_Controller
{
    public function indexAction()
    {
    }

    public function listAction()
    {
        return $this->json(
            array_values(array_map(function($r) { $r['data'] = json_decode($r['data']); return $r; }, DataSet::search(1)->toArray(array('id', 'data'))))
        );
    }

    public function checkurlAction()
    {
        try {
            $records = ImportLib::get_records_from_url($_REQUEST['url']);
        } catch (Exception $e) {
            return $this->json(array(
                'error' => true,
                'message' => $e->getMessage(),
            ));
        }
        return $this->json(array(
            'error' => false,
            'records' => $records,
        ));
    }
}
