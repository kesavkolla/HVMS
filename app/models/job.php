<?php

class Job extends AppModel {
    public $name = 'Job';
    public $actsAs = array('Containable');
    public $belongsTo = 'User';
    public $hasAndBelongsToMany = array(
        'Module' =>
            array(
                'className'              => 'Module',
                'joinTable'              => 'jobs_skills',
                'foreignKey'             => 'job_id',
                'associationForeignKey'  => 'module_id',
                'unique'                 => true,
            )
    );

    
    public function find($conditions = null, $fields = array(), $order = null, $recursive = null) {
        if ($this->userType == 'admin') {
            $this->contain("{$this->User->alias}.Hospital.code");
        }
        return parent::find($conditions, $fields, $order, $recursive);
    }
    
    public function afterFind($results) {
        foreach ($results as &$job) {
            if (isset($job['User']) && isset($job['User']['Hospital'])) {
                $hospCode = isset($job['User']['Hospital']['code']) ? $job['User']['Hospital']['code'] : '';
                $jobID = isset($job['Job']['jobid']) ? $job['Job']['jobid'] : '';
                $job['Job']['jobid'] = $hospCode . ' ' . $jobID;
            }
        }
        return $results;
    }
    
}
?>