<?php

class UpdateController extends Pix_Controller
{
    public function init()
    {
        $this->view->user = ($user_id = Pix_Session::get('user_id')) ? User::find(intval($user_id)) : null;
        if (!$sToken = Pix_Session::get('sToken')) {
            $sToken = crc32(uniqid());
            Pix_Session::set('sToken', $sToken);
        }
        $this->view->sToken = $sToken;
    }

    public function indexAction()
    {
    }

    public function listAction()
    {
        return $this->json(
            array_values(array_map(function($r) {
                $r['data'] = json_decode($r['data']);
                $r['data']->county = str_replace('台', '臺', $r['data']->county);
                return $r;
            }, DataSet::search(1)->toArray(array('id', 'data'))))
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

    public function json_error($str)
    {
        return $this->json(array(
            'error' => true,
            'message' => $str,
        ));
    }

    public function adddatasetAction()
    {
        if ($_POST['sToken'] != $this->view->sToken) {
            return $this->json_error("sToken error");
        }

        $record = new STdClass;
        if (!$_POST['county'] or !$_POST['title']) {
            return $this->json_error("未輸入縣市及標題");
        }
        $record->county = trim($_POST['county']);
        $record->data_title = trim($_POST['title']);
        if (!$record->published_at = strtotime(trim($_POST['published_at']))) {
            return $this->json_error("上架時間不正確");
        }
        $record->url = trim($_POST['url']);
        if (!filter_var($record->url, FILTER_VALIDATE_URL)) {
            return $this->json_error("網址不正確");
        }
        $record->other = trim($_POST['other']);
        $record->created_at = $now = time();

        $dataset = DataSet::insert(array(
            'data' => json_encode($record),
        ));
        DataSetLog::insert(array(
            'id' => $dataset->id,
            'time' => $now,
            'changed_by' => $this->view->user->user_name,
            'origin_data' => json_encode(array()),
        ));
        return $this->json($dataset->toArray());
    }
}
