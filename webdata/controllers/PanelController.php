<?php

class PanelController extends Pix_Controller
{
    public function init()
    {
        $this->view->member = ($member_id = Pix_Session::get('member_id')) ? TeamMember::find(intval($member_id)) : null;
        if (!$this->view->member) {
            return $this->redirect('/');
        }
        if (!$sToken = Pix_Session::get('sToken')) {
            $sToken = crc32(uniqid());
            Pix_Session::set('sToken', $sToken);
        }
        $this->view->sToken = $sToken;
    }

    public function packageAction()
    {
        $this->view->team = $this->view->member->team;
        if (!$this->view->team) {
            return $this->redirect('/');
        }
    }

    public function teamAction()
    {
        $this->view->team = $this->view->member->team;
        if (!$this->view->team) {
            return $this->redirect('/');
        }

        if ($_POST['sToken']) {
            if ($this->view->sToken != $_POST['sToken']) {
                return $this->alert('wrong sToken', '/panel/team');
            }

            $this->view->team->update(array(
                'name' => $_POST['name'],
                'note' => $_POST['note'],
            ));

            return $this->alert('OK', '/panel/team');
        }
    }

    public function editpackageAction()
    {
        list(, /*panel*/, /*editpackage*/, $id) = explode('/', $this->getURI());

        if (!$_POST['sToken']) {
            return $this->alert('wrong Stoken', '/panel/package');
        }
        if ($_POST['sToken'] != $this->view->sToken) {
            return $this->alert('wrong Stoken', '/panel/package');
        }

        if (!$package = Package::find($id) or !$package->canEdit($this->view->member)) {
            return $this->redirect('/panel/package');
        }
        $package->update(array(
            'name' => strval($_POST['name']),
            'note' => strval($_POST['note']),
        ));
        return $this->alert('OK', '/panel/showpackage/' . $package->package_id);
    }

    public function showpackageAction()
    {
        list(, /*panel*/, /*showpackage*/, $id) = explode('/', $this->getURI());

        if (!$package = Package::find($id) or !$package->canEdit($this->view->member)) {
            return $this->redirect('/panel/package');
        }
        $this->view->package = $package;
    }

    public function newpackageAction()
    {
        if (!$_POST['sToken']) {
            return $this->alert('wrong Stoken', '/panel/package');
        }
        if ($_POST['sToken'] != $this->view->sToken) {
            return $this->alert('wrong Stoken', '/panel/package');
        }
        $this->view->team = $this->view->member->team;
        if (!$this->view->team) {
            return $this->redirect('/');
        }

        Package::insert(array(
            'name' => strval($_POST['name']),
            'team_id' => $this->view->team->team_id,
            'note' => strval($_POST['note']),
        ));

        return $this->alert('OK', '/panel/package');
    }
}
