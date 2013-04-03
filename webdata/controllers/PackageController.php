<?php

class PackageController extends Pix_Controller
{
    public function init()
    {
        $this->view->user = ($user_id = Pix_Session::get('user_id')) ? User::find(intval($user_id)) : null;
    }

    public function showAction()
    {
        list(, /*package*/, /*show*/, $id) = explode('/', $this->getURI());
        if (!$package = Package::find($id)) {
            return $this->redirect('/');
        }

        $this->view->package = $package;
    }

    public function downloadcsvAction()
    {
        list(, /*package*/, /*show*/, $id) = explode('/', $this->getURI());
        if (!$package = Package::find($id)) {
            return $this->redirect('/');
        }

        header('Content-Type: text/csv');
        $fp = fopen('php://output', 'w');
        fputcsv($fp, array(
            '事業名稱',
            '時間',
            '違反事由',
            '原始連結',
            '截圖連結',
        ));
        echo $package->content->content;
        return $this->noview();
    }
}
