<?php
class InterestsController extends AppController {
	public $name = 'Interests';
	public $helpers = array('Inputs', 'Number', 'Time');
	
	public $components = array('Session', 'Email');

    function beforeFilter() {
		$this->Email->smtpOptions = array( 
										'port' => '465', 
										'timeout' => '30', 
										'host' => 'ssl://smtp.gmail.com', 
										'username' => 'hvmstest@healthvms.com', 
										'password' => 'healthvms');
	}
	
    function flag() {
        $this->autoRender = false;

        if (isset($this->params['form']) && isset($this->params['form']['interestId'])) {
			$interestData = $this->getInterestData($this->params['form']['interestId']);
			
			$this->Interest->recursive = -1;
			
			if ($this->Interest->save($interestData)) {
			   echo 1;
			   $this->sendInterestEmail($interestData);
			}
			echo '';
        }     
    }
	
    function unflag() {
        $this->autoRender = false;
        if (isset($this->params['form']) && isset($this->params['form']['interestId'])) {
			$interestData = $this->getInterestData($this->params['form']['interestId']);
			
			$this->Interest->recursive = -1;
			$interest = $this->Interest->find('first', array(
														   'conditions' =>$interestData['Interest']));
			if ($this->Interest->delete($interest['Interest']['id'], false)) {
				echo 1;
			}
            echo '';
        }     
    }
	
	private function getInterestData ($interestId) {
		$interestedUser = $this->Session->read('Auth.User.id');
		$interestType = $this->Session->read('Auth.User.type') == 'cand' ?
						  'job' :
						  'cand';
						  
		$interestData = array();
		$interestData['Interest'] = array(
									  'user_id' => $interestedUser,
									  'interest_id' => $interestId,
									  'interest_type' => $interestType,
									  );
		return $interestData;
	}
	
	private function sendInterestEmail($interestData) {
        $this->set('userID', $interestData['Interest']['user_id']);

		$interest_type = $interestData['Interest']['interest_type'];
		if ($interest_type == 'cand') {
            $this->set('interestType', 'candidate');
            $this->set('interestText', 'See all interests in this profile');
			$interestQueryString = array('profile_id' => $interestData['Interest']['interest_id']);
			$this->set('interestQueryString', $interestQueryString);
		}
		else if ($interest_type == 'job') {
            $this->set('interestType', 'job');
            $this->set('interestText', 'See all interests in this job');
			$interestQueryString = array('job_id' => $interestData['Interest']['interest_id']);
			$this->set('interestQueryString', $interestQueryString);
		}
		
		$this->Email->to = 'hvmstest@hvms.com';
		$this->Email->from = 'HealthVMS <noreply@healthvms.com>';
		$this->Email->subject = 'Interest Indicated';
		$this->Email->template = 'new_interest';
		
		$this->Email->delivery = 'smtp';
		$this->Email->sendAs = 'html';
		$this->Email->send();
	}
	
	function index() {
		$interestedUser = $this->Session->read('Auth.User.id');
		$userType = $this->Session->read('Auth.User.type');
		if ($userType == 'cand') {
			$interestType = 'job';
			$containArray = array('Job' =>
								  array (
                                            'Module.modulename' => array(
                                                'Vendor.vendorname',
                                        )));								  
		}
		else if ($userType == 'hosp'){
			$interestType = 'cand';
			$containArray = array('Profile' =>
								  array (
                                            'Module.modulename' => array(
                                                'Vendor.vendorname',
                                        )));
		}

		$interestData = array();
		$interestItems = $this->Interest->find ( 'all',
								array(			
										'conditions' => array (
										  'Interest.user_id' => $interestedUser,
										  'Interest.interest_type' => $interestType,										
										),
										'order' => 'Interest.interest_id DESC',
										'contain' => $containArray
									)
									  );
		 
		$this->set('interestItems' , $interestItems);
	}
	
	function admin_index() {
		$searchParams = $this->params['url'];
		if (!empty($this->params)) {
			$conditions = array();
			$contain = array('User');
			$contain['Profile'] = array(
											'Module.modulename' => array (
													'Vendor.vendorname'
											),
											'User.id'
										   );
			$contain['Job'] = array(
												'Module.modulename' => array (
													'Vendor.vendorname'
											)
										   );
			if (isset($searchParams['user_id'])) {
				$conditions['Interest.user_id'] = $searchParams['user_id'];

			}
			if (isset($searchParams['profile_id'])) {
				$conditions['Interest.interest_id'] = $searchParams['profile_id'];
				$conditions['Interest.interest_type'] = 'cand';
				unset($contain['Job']);
			}			
			if (isset($searchParams['job_id'])) {
				$conditions['Interest.interest_id'] = $searchParams['job_id'];
				$conditions['Interest.interest_type'] = 'job';
				unset($contain['Profile']);
			}
			
			$this->paginate = array (
								'conditions' => $conditions,
								'contain' => $contain
			);
			$interests = $this->paginate();
			
			$this->set('interests', $interests);
		}
	}
	
	function admin_edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(__('Invalid interest', true));
			$this->redirect(array('action' => 'index'));
			
		}
		if (!empty($this->data)) {
			if ($this->Interest->save($this->data)) {
				$this->Session->setFlash(__('The Interest has been saved', true));
				$this->redirect(array('action' => 'index'));
				
			} else {
				$this->Session->setFlash(__('The Interest could not be saved. Please, try again.', true));
			}
		}
		if (empty($this->data)) {
			$contain = array('User');
			$contain['Profile'] = array(
											'Module.modulename' => array (
													'Vendor.vendorname'
											),
											'User.id'
										   );
			$contain['Job'] = array(
												'Module.modulename' => array (
													'Vendor.vendorname'
											)
										   );
			$this->data = $this->Interest->find('first',
												array(
													  'conditions' => array('Interest.id' => $id),
													  'contain' => $contain
													 ));
		}
	}
}
   
?>