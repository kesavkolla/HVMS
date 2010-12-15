<?php
class Vendor extends AppModel {
	public $name = 'Vendor';
	public $displayField = 'vendorname';
	public $hasMany = array('Module');
	
	function getChainedSkills () {
		$result = array();
		$vendorList = $this->find('all',
								  array(
										'contain' => array(
													 'Module.modulename' =>  'Version.versionname'),
										'recursive' => 2
										
										)							  
								 );
		return ($vendorList);
	}
}
?>