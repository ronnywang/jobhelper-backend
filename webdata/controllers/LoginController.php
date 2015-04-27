<?php

class LoginController extends Pix_Controller
{
    public function googledoneAction()
    {
        $return_to = 'https://' . $_SERVER['HTTP_HOST'] . '/login/googledone';

        $params = array();
        $params[] = 'code=' . urlencode($_GET['code']);
        $params[] = 'client_id=' . urlencode(getenv('GOOGLE_CLIENT_ID'));
        $params[] = 'client_secret=' . urlencode(getenv('GOOGLE_CLIENT_SECRET'));
        $params[] = 'redirect_uri=' . urlencode($return_to);
        $params[] = 'grant_type=authorization_code';
        $curl = curl_init('https://www.googleapis.com/oauth2/v3/token');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, implode('&', $params));
        $obj = json_decode(curl_exec($curl));
        if (!$obj->id_token) {
            return $this->alert('login failed', '/');
        }
        $tokens = explode('.', $obj->id_token);
        $login_info = json_decode(base64_decode($tokens[1]));
        if (!$login_info->email or !$login_info->email_verified) {
            return $this->alert('login failed', '/');
        }
        $email = $login_info->email;

        if (!$user = User::search(array('user_name' => 'google://' . $email))->first()) {
            return $this->alert('您不在管理名單中', '/');
        }
        Pix_Session::set('user_id', $user->user_id);
        return $this->redirect('/');
    }

    public function googleAction()
    {
        $return_to = 'https://' . $_SERVER['HTTP_HOST'] . '/login/googledone';
        $url = 'https://accounts.google.com/o/oauth2/auth?'
            . '&state='
            . '&scope=email'
            . '&redirect_uri=' . urlencode($return_to)
            . '&response_type=code'
            . '&client_id=' . getenv('GOOGLE_CLIENT_ID')
            . '&access_type=offline';
        return $this->redirect($url);
    }

    public function logoutAction()
    {
        Pix_Session::delete('user_id');
        return $this->redirect('/');
    }
}
