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
            $ret->body = $this->view->partial('/index/company.phtml', array('no' => $id, 'name' => $info->name));
        } else {
            $ret->body = '找不到這家公司資料';
        }

        return $this->json($ret);
    }

    public function showAction()
    {
        list(, /*index*/, /*show*/, $id) = explode('/', $this->getURI());

        $this->view->no = intval($id);
        return $this->redraw('/index/company.phtml');

    }
}
