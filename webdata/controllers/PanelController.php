<?php

class PanelController extends Pix_Controller
{
    public function init()
    {
        $this->view->user = ($user_id= Pix_Session::get('user_id')) ? User::find(intval($user_id)) : null;
        if (!$this->view->user) {
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
        list(, /*panel*/, /*package*/, $team_id) = explode('/', $this->getURI());
        $this->view->team = $this->view->user->user_teams->search(array('team_id' => $team_id))->first()->team;
        if (!$this->view->team) {
            return $this->redirect('/');
        }
    }

    public function teamAction()
    {
        list(, /*panel*/, /*team*/, $team_id) = explode('/', $this->getURI());
        $this->view->team = $this->view->user->user_teams->search(array('team_id' => $team_id))->first()->team;
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

        if (!$package = Package::find($id) or !$package->canEdit($this->view->user)) {
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

        if (!$package = Package::find($id) or !$package->canEdit($this->view->user)) {
            return $this->redirect('/panel/package');
        }
        if ($_GET['action'] == 'updatecontent') {
            $ret = $this->updateContent($package, $_POST['content']);
            if (!$ret['error']) {
                return $this->alert('OK', '/panel/showpackage/' . $id);
            }
            $this->view->content = $_POST['content'];
            $this->view->error_message = $ret['message'];

        }
        $this->view->package = $package;
    }

    protected function updateContent($package, $content)
    {
        // 檢查 content
        foreach (explode("\n", trim($content)) as $no => $line) {
            $no = $no + 1;
            $terms = str_getcsv($line);

            if (count($terms) < 5) {
                return array('error' => true, 'message' => "每一行至少要有 5 列，第 {$no} 行只有 " . count($terms));
            }

            if (false === strtotime($terms[1])) {
                return array('error' => true, 'message' => "第 {$no} 行是無法判別的日期格式: " . $terms[1]);
            }

            if (!filter_var($terms[3], FILTER_VALIDATE_URL)) {
                return array('error' => true, 'message' => "第四列是必需是原始連結，第 {$no} 行的連結不是正確的網址格式");
            }
        }

        try {
            $package->create_content(array(
                'content' => $content,
            ));
        } catch (Pix_Table_DuplicateException $e) {
            $package->content->update(array(
                'content' => $content,
            ));
        }

        $package->update(array('package_time' => time()));

        return array('error' => false);
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
