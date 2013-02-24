<?php

class IndexController extends Pix_Controller
{
    protected function errorJson($message)
    {
        return $this->json(array(
            'error' => true,
            'message' => $message,
        ));
    }

    public function indexAction()
    {
    }

    public function infoAction()
    {
        if (!is_scalar($_GET['info'])) {
            return $this->errorJson('wrong info');
        }
        if (!$info = @json_decode($_GET['info'])) {
            return $this->errorJson('wrong info');
        }

        $ret = new StdClass;
        $ret->error = false;
        $id = Site::findCompanyByInfo($info);
        if ($id) {
            $ret->body = '<b>' . $info->name . '(' . $id . ')' . '</b>';
        } else {
            $ret->body = '<b>' . json_encode($info, JSON_UNESCAPED_UNICODE) . '</b>';
        }

        return $this->json($ret);
    }
}
