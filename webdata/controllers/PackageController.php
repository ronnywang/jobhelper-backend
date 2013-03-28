<?php

class PackageController extends Pix_Controller
{
    public function init()
    {
        $this->view->member = ($member_id = Pix_Session::get('member_id')) ? TeamMember::find(intval($member_id)) : null;
    }

    public function showAction()
    {
        list(, /*package*/, /*show*/, $id) = explode('/', $this->getURI());
        if (!$package = Package::find($id)) {
            return $this->redirect('/');
        }

        $this->view->package = $package;
    }
}
