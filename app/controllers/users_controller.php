<?php

class UsersController extends AppController {
    public $scaffold;
    public $uses = array('User', 'Hospital');
    public $components = array('Auth', 'Security', 'Session', 'Email', 'Cookie', 'RequestHandler');
    
    function beforeFilter() {
            $this->Auth->allow('forgot', 'index', 'register',  'confirm', 'auto_reset_pw', 'checkEmail');
            $this->Auth->autoRedirect = false;
            $this->Email->smtpOptions = array( 
                                            'port' => '465', 
                                            'timeout' => '30', 
                                            'host' => 'ssl://smtp.gmail.com', 
                                            'username' => 'hvmstest@healthvms.com', 
                                            'password' => 'healthvms'); 
            $this->Security->blackHoleCallback = 'forceSsl';
            $this->Security->requireSecure('login', 'register', 'checkEmail');
             parent::beforeFilter();
    }

 
    function login() {
        if ($this->Auth->user()) {
            if ($this->data &&
                isset($this->data['User']) &&
                isset($this->data['User']['remember_me']) &&
                $this->data['User']['remember_me'])  {
                $cookie = array();
                $cookie['username'] = $this->data['User']['username'];
                $cookie['password'] = $this->data['User']['password'];
                $this->Cookie->write('Auth.User', $cookie, true, '+1 week');
                unset($this->data['User']['remember_me']);
            }
            $this->redirect($this->Auth->redirect());
        }
        if (empty($this->data)) {
            $cookie = $this->Cookie->read('Auth.User');
            if (!is_null($cookie)) {
                if ($this->Auth->login($cookie)) {
                    $this->redirect($this->Auth->redirect());
                } else { // Delete invalid Cookie
                    $this->Cookie->delete('Auth.User');
                }
            }
        }
    }
    

    function logout() {
        $cookie = $this->Cookie->read('Auth.User');
        if (!is_null($cookie)) {
            $this->Cookie->delete('Auth.User');    
        }
        
       $this->redirect($this->Auth->logout());
    }

    
    function register() {
        if ($this->Auth->user()) {
            $this->redirect($this->Auth->redirect());
        }
        if ($this->data) {
            $this->User->create($this->data);
            if ($this->User->validates()) {
                // check hospital domain
                if ($this->validUserDomain()) {
                    if ($this->User->save($this->data, array('validate' => true))) {
                        $this->sendVerifyEmail();
                        $message = 'One more step to go! We have sent a confirmation email to you at ' . $this->data['User']['username']  . '.';
                        $this->Session->setFlash($message);
                    }
                    else {
                        if ($invalidFields = $this->User->invalidFields()){
                            // invalid fields, let the framework take care of it
                        }
                        else {
                            $db =& ConnectionManager::getDataSource($this->User->useDbConfig);
                            $error = $db->lastError();
                            if (strstr($error, 'Duplicate entry'))  {
                                $error = 'This email is already registered. Please login to proceed.';
                                $this->User->invalidate('username', $error); // invalidate username 
                            }
                            
                            $this->set('errors', $error);
                        }
                    }
                }
                else {
                        $error = 'Please enter an email that is associated with the hospital you work at.';
                        $this->User->invalidate('username', $error); // invalidate username 

                }
            }
        }
    }

    
    function resetpw() {
        if ($this->data) {
            $user = $this->Auth->user();
            $currentpw = $this->User->findById($user['User']['id'], array('fields' => 'User.password'));
            if ($currentpw['User']['password'] == $this->Auth->password($this->data['User']['old_password'])) {
                $updatedData = array_merge_recursive($user, $this->data);
                if ($this->User->save($updatedData, array('validate' => true))) {
                    $message = 'Password successfully changed';
                    $this->Session->setFlash($message);
                }
            }
            else {
                $this->User->invalidate('old_password', 'Incorrect password for ' . $user['User']['username']);
            }
        }
        
    }
    
    function confirm($email, $hash) {
        $userData = $this->User->findByUsername($email);
        $this->User->create($userData);        
        if ($this->User->getConfirmationHash() == $hash) {
            $this->User->set('status', Configure::read('user.STATUS_VERIFIED'));
            if ($this->User->save(null, false)) {
                $this->Session->setFlash('Thank you for confirming your account. Please login to start using HealthVMS.');
                $this->redirect(array('controller' => 'users', 'action' => 'login'));
                $this->Auth->loginRedirect = array('controller' => 'profiles', 'action' => 'edit');
            }
            else {
                $this->Session->setFlash('There was an error confirming your account. Please contact HeealthVMS for help.');
            }
        }
    }
    
    // TODO: change echo's to flashes and actually generate the email
    function forgot () {
        if ($resetInfo = $this->generateResetPwLink()) {
            $user = $this->data['User'];
            $this->set('username', $user['username']);
            $this->set('resetLink', $resetInfo['confirm_reset_link']);
            $this->set('otp', $resetInfo['otp']);

            $this->Email->to = $user['username'];
            $this->Email->from = 'HealthVMS <noreply@healthvms.com>'; 
            $this->Email->subject = 'Password reset request';
            $this->Email->template = 'reset_pw';
            
            $this->Email->delivery = 'smtp';
            $this->Email->sendAs = 'html';
            $this->Email->send();
            $this->Session->setFlash('We\'ve sent  you a link at ' . $user['username'] . ', please click on it to reset your password');
        }
        else if ($this->data) {
            $this->Session->setFlash('We were unable to find your email in our system. Please enter the email that you used to register with us.');
        }
    }

    function auto_reset_pw($OTP, $expiry, $userID)  {
        if (time() > $expiry) {
            $this->Session->setFlash('This password link has expired. Please generate a new one.');
            $this->redirect('/users/forgot');
        }
        $this->User->recursive = -1;
        $user = $this->User->findById($userID);
        
        if ($OTP == $this->generateOTP($expiry, $userID, $user['User']['password'])) {
            $user['User']['tmp_password'] = $OTP;
            $user['User']['confirm_password'] = $OTP;
            if ($this->User->save($user)) {
                $user['User']['password'] = Security::hash($user['User']['tmp_password'], null, true);
                $this->Auth->login($user);
                $this->Session->setFlash('Your password has been changed. Please change it to a password of your choice below.');
                $this->redirect('/users/resetpw');    
            }
            else {
                $this->Session->setFlash('There was an error resetting your password. Please re-request a new password.');
                $this->redirect('/users/forgot');
            }
        }
        else {
            $this->Session->setFlash('There was an error resetting your password. Please re-request a new password.');
            $this->redirect('/users/forgot');    
        }
    }
    
    function checkEmail() {
        $this->autoRender = false;
        if (isset($this->params['form']) && isset($this->params['form']['email'])) {
            echo json_encode($this->getHospitalFromEmail($this->params['form']['email']));
        }
    }

    
    private function generateResetPwLink() {
        if ($this->data && isset($this->data['User']['username'])) {
            $user = $this->User->findByUsername($this->data['User']['username']);
            if (!$user)
                return null;
            $expiry = time() + 86400; // 24 hours from now
            $userID = $user['User']['id'];
            $OTP = $this->generateOTP($expiry, $userID, $user['User']['password']);
            $pwLink = "$OTP/$expiry/$userID";
            
            $newPw = array();
            $newPw['confirm_reset_link'] = $pwLink;
            $newPw['otp'] = $OTP;
            return $newPw;
        }
        return null;
    }
    
    private function generateOTP ($expiry, $userID, $currentpw) {
        $midOTP = Security::hash($currentpw, 'sha1', true);
        $OTP = Security::hash($midOTP . $expiry . $userID, 'sha1', true);
        return $OTP;
    }
    
    private  function sendVerifyEmail() {
        $hash = $this->User->getConfirmationHash();
        $user = $this->data['User'];
        $this->set('username', $user['username']);
        $this->set('hash', $hash);
        $this->Email->to = $user['username'];
        $this->Email->from = 'HealthVMS <noreply@healthvms.com>'; // TODO - put in config
        $this->Email->subject = 'Please confirm your registration with HealthVMS';
        $this->Email->template = 'reg_confirm';
        
        $this->Email->delivery = 'smtp';
        $this->Email->sendAs = 'html';
        $this->Email->send();
    }
    
    private function validUserDomain() {
        if ($this->data['User']['type'] == 'hosp') {
            if ($hospId = $this->data['User']['hospital_id']) {
                
                $hospital = $this->Hospital->findById($hospId);
                if ($hospital) {
                    $uname = $this->data['User']['username'];
                    $allowedDomains = explode(',', $hospital['Hospital']['domainsallowed']);
                    foreach ($allowedDomains as $domain) {
                        $domainCheck = trim($domain); // email must end with @domainname
                        if (endswith($uname, $domainCheck)) {
                            return true;
                        }
                    }
                }
            }
            return false;
        }
        else {
            return true;
        }
    }
    
    private function getHospitalFromEmail($email) {
        $hospitals = $this->Hospital->find('all');
        foreach ($hospitals as $hospital) {
            $allowedDomains = explode(',', $hospital['Hospital']['domainsallowed']);
            foreach ($allowedDomains as $domain) {
                if (endswith($email, trim($domain))) {
                    return array ('id' => $hospital['Hospital']['id'],
                                  'name' => $hospital['Hospital']['name']);
                }
            }
        }
        return;
    }
    
	function admin_view($id = null) {
		if (!$id) {
			$this->Session->setFlash(__('Invalid user', true));
			$this->redirect(array('action' => 'index'));
		}
		$this->set('user', $this->User->read(null, $id));
	}
        

}

?>
