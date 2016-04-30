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
                $r['data']->county = ImportLib::normalize_county($r['data']->county);
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
        if (intval($_POST['published_at']) == 0) {
            $record->published_at = 0;
        } elseif (!$record->published_at = strtotime(trim($_POST['published_at']))) {
            return $this->json_error("上架時間不正確");
        }
        $record->origin_url = trim($_POST['origin_url']);
        if (!filter_var($record->origin_url, FILTER_VALIDATE_URL)) {
            return $this->json_error("原始網址不正確");
        }
        if (!$_POST['snapshot_url']) {
            // do nothing
        } elseif (!filter_var($_POST['snapshot_url'], FILTER_VALIDATE_URL)) {
            return $this->json_error("截圖網址不正確");
        } else {
            $record->snapshot_url = $_POST['snapshot_url'];
        }

        if (!$_POST['data_url']) {
            // do nothing
        } elseif (!filter_var($_POST['data_url'], FILTER_VALIDATE_URL)) {
            return $this->json_error("資料網址不正確");
        } else {
            try {
                $records = ImportLib::get_records_from_url($_POST['data_url']);
            } catch (Exception $e) {
                return $this->json_error("資料網址內容解析失敗: " + $e->getMessage());
            }
            $record->data_url = $_POST['data_url'];
        }


        $record->other = trim($_POST['other']);
        $now = time();

        if ($_POST['type'] == 'add') {
            $record->created_at = $now;
            $dataset = DataSet::insert(array(
                'data' => json_encode($record),
            ));
            DataSetLog::insert(array(
                'id' => $dataset->id,
                'time' => $now,
                'changed_by' => $this->view->user->user_name,
                'origin_data' => json_encode(array()),
            ));
            $message = "新增 {$record->county} {$record->data_title} 成功, id={$dataset->id}";
        } elseif ($_POST['type'] == 'modify') {
            $record->updated_at = $now;
            if (!$dataset = DataSet::find($_POST['modify_id'])) {
                return $this->json_error("找不到 {$_POST['modify_id']} 這個資料集");
            }
            $new_record = json_decode($dataset->data);
            $origin_data = new StdClass;

            foreach ($record as $k => $v) {
                if ($v != $new_record->{$k}) {
                    $origin_data->{$k} = $new_record->{$k};
                    $new_record->{$k} = $v;
                }
            }
            $dataset->update(array(
                'data' => json_encode($new_record),
            ));
            DataSetLog::insert(array(
                'id' => $dataset->id,
                'time' => $now,
                'changed_by' => $this->view->user->user_name,
                'origin_data' => json_encode($origin_data),
            ));
            $message = "修改 {$record->county} {$record->data_title} 成功, id={$dataset->id}";
        }

        return $this->json(array(
            'error' => false,
            'added_record' => $record,
            'message' => $message,
            'records' => array_values(array_map(function($r) {
                $r['data'] = json_decode($r['data']);
                $r['data']->county = ImportLib::normalize_county($r['data']->county);
                return $r;
            }, DataSet::search(1)->toArray(array('id', 'data')))),
        ));
    }
}
