<?php
class JobsController extends AppController {

	public $name = 'Jobs';
        public $uses = array ('Job', 'Vendor');
        public $components = array('Auth');
        public $helpers = array('Inputs', 'Number', 'Time');
        
        function beforeFilter() {
            // all actions except searching are restricted.
            $action = $this->params['action'];
            if ($action != 'search') {
                // the user should be a hospital user
                if ($this->Session->read('Auth.User.type') !== 'hosp') {
                    $this->Session->setFlash('You are not authorized to view this page.');
                    $this->redirect('/');
                }
                
                if ($action == 'edit') {
                    
                }
            }

        }
        
	function index() {
                $jobs = $this->Job->find('all',
                                        array('conditions' => array(
                                                 'Job.user_id' => $this->Session->read('Auth.User.id')
                                                 ),
                                                'contain' => array(
                                                    'Version.versionname' => array (
                                                        'Module.modulename' => array (
                                                            'Vendor.vendorname'
                                                        )
                                                    ),
                                                    'User' => array (
                                                        'Hospital.name'
                                                    )
                                                )
                                              )
                                              );
                $this->set(compact(array('jobs')));

	}

	function add() {
		if (!empty($this->data)) {
                        $jobData = $this->prepareJobForDB($this->data);
			$this->Job->create();
			if ($this->Job->save($jobData)) {
				$this->Session->setFlash(__('The job has been saved', true));
				//$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The job could not be saved. Please, try again.', true));
			}
		}
                $this->set('skills', $this->Vendor->getChainedSkills());
	}

	function edit($id = null) {
            $selectedSkills = array();
            if (!$id && empty($this->data)) {
                    $this->Session->setFlash(__('Invalid job', true));
                    $this->redirect(array('action' => 'index'));
            }
            if (!empty($this->data)) {
                    $jobData = $this->prepareJobForDB($this->data);
                    if ($this->Job->save($jobData)) {
                            $this->Session->setFlash(__('The job has been saved', true));
                            $this->redirect(array('action' => 'index'));
                    } else {
                            $this->Session->setFlash(__('The job could not be saved. Please, try again.', true));
                    }
            }
            if (empty($this->data)) {
                    $dbData = $this->Job->read(null, $id);
                    if ($dbData['Job'] ['user_id'] != $this->Session->read('Auth.User.id')) {
                        $this->Session->seFlash('You cannot edit this job. Please edit jobs that you own');
                        $this->redirect(array('action' => 'index'));
                    }
                    $this->data = $this->prepareJobForDisplay($dbData);                        
                    $selectedSkills = $this->getVersionIds($this->data['Version']);
            }
            $this->set('selectedSkills', $selectedSkills);
            $this->set('skills', $this->Vendor->getChainedSkills());         
	}

	function publish($id = null) {
		if (!$id) {
			$this->Session->setFlash(__('Invalid id for job', true));
			$this->redirect(array('action'=>'index'));
		}
                $this->Job->updateAll(array ('published' => 1),
                                      array ('Job.id' => $id));
		$this->redirect(array('action' => 'index'));
	}
        
        function unpublish($id = null) {
            if (!$id) {
                    $this->Session->setFlash(__('Invalid id for job', true));
                    $this->redirect(array('action'=>'index'));
            }
            $this->Job->updateAll(array ('published' => 0),
                                  array ('Job.id' => $id));
            $this->redirect(array('action' => 'index'));
	}
        

    
    private function prepareJobforDB($data) {
        // add user id
        $data['Job']['user_id'] = $this->Session->read('Auth.User.id');

        $jobFormData = &$data['Job'];
        
        // schedule
        if (isset($jobFormData['schedule1'])) {
            $jobFormData['schedule'] = $jobFormData['schedule1'];
            if (isset($jobFormData['schedule2'])) {
                $jobFormData['schedule']  .= " :  {$jobFormData['schedule2']}";
                unset($jobFormData['schedule2']);
            }
            unset($jobFormData['schedule1']); 
        }
        
        // role
         if (isset($jobFormData['role-other']) && $jobFormData['role'] == 'Other') {
            $jobFormData['role'] .= ': ' . $jobFormData['role-other'];
            unset($jobFormData['role-other']); 
        }
        
        return $data;
    }
    
    private function prepareJobForDisplay ($data) {
        $jobDataFromDB = &$data['Job'];

        // schedule
        if (isset($jobDataFromDB['schedule'])) {
            $scheduleInfo = explode (':', $jobDataFromDB['schedule']);
            $jobDataFromDB['schedule1'] = trim($scheduleInfo[0]);
            if (isset($scheduleInfo[1])) {
                $jobDataFromDB['schedule2'] = trim($scheduleInfo[1]);
            }            
        }
        
        // role
        if (isset($jobDataFromDB['role'])) {
            $roleInfo = explode (':', $jobDataFromDB['role']);
            $jobDataFromDB['role'] = trim($roleInfo[0]);
            if (isset($roleInfo[1])) {
                $jobDataFromDB['role-other'] = trim($roleInfo[1]);
            }    
        }
        return $data;
    }
    
    private function getVersionIds($versionsArray) {
        $versionIds = array();
        foreach ($versionsArray as $version) {
            $versionIds[] = $version['id'];
        }
        return $versionIds;
    }

    /*
	function admin_index() {
		$this->Job->recursive = 0;
		$this->set('jobs', $this->paginate());
	}

	function admin_view($id = null) {
		if (!$id) {
			$this->Session->setFlash(__('Invalid job', true));
			$this->redirect(array('action' => 'index'));
		}
		$this->set('job', $this->Job->read(null, $id));
	}

	function admin_add() {
		if (!empty($this->data)) {
			$this->Job->create();
			if ($this->Job->save($this->data)) {
				$this->Session->setFlash(__('The job has been saved', true));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The job could not be saved. Please, try again.', true));
			}
		}
		$users = $this->Job->User->find('list');
		$this->set(compact('users'));
	}

	function admin_edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(__('Invalid job', true));
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->Job->save($this->data)) {
				$this->Session->setFlash(__('The job has been saved', true));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The job could not be saved. Please, try again.', true));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->Job->read(null, $id);
		}
		$users = $this->Job->User->find('list');
		$this->set(compact('users'));
	}

	function admin_delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(__('Invalid id for job', true));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->Job->delete($id)) {
			$this->Session->setFlash(__('Job deleted', true));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(__('Job was not deleted', true));
		$this->redirect(array('action' => 'index'));
	}
        */


}
?>