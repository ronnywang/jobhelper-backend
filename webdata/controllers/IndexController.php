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
        $this->view->user = ($user_id = Pix_Session::get('user_id')) ? User::find(intval($user_id)) : null;
    }

    public function showAction()
    {
        list(, /*index*/, /*show*/, $id) = explode('/', $this->getURI());

        $this->view->no = intval($id);
        return $this->redraw('/index/company.phtml');

    }

    public function allcsvAction()
    {
    }
}
