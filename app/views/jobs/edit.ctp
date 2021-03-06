<?php echo $html->script('job', array('inline' => false)); ?>

<div class="jobs form">
<?php echo $this->Form->create('Job', array('id'=>'JobAddForm'));?>
	<fieldset>
	<?php
            echo $this->Form->input('title');
            echo $this->Form->input('jobid',array('label' => 'Job Id'));
            echo $this->Form->input('startdate', array('label' => 'Start Date'));
            echo $this->Form->input('enddate', array('label' => 'End Date'));
            echo $this->Form->input('description', array('rows' => '5', 'cols' => '10'));
            echo $this->Form->input('location');
		
            echo '<div class="input text">';
            echo $this->Form->label('state');
            echo $this->Form->select('state', $this->Inputs->getStatesList(), null, array('label' => 'State'));
            echo '</div>';
                    
            echo $this->Form->input('jobtype', array(
                                                     'type' => 'select',
                                                     'label' => 'Job Type',
                                                     'options' => $this->Inputs->getJobTypes(),
                                                     'empty' => true));
            echo ($this->element('job/schedule'));	
            echo $this->Form->input('comments', array('rows' => '3', 'cols' => '10'));
            echo $this->Form->input('ratemin', array('label' => 'Minimum Rate',
                                                     'after' => '<span class="hint">per hour</span>'));
            echo $this->Form->input('ratemax', array('label' => 'Maximum Rate',
                                                     'after' => '<span class="hint">per hour</span>'));
            echo $this->Form->input('expensespaid', array(
                                                            'type' => 'select',			
                                                            'label' => 'Expenses Paid',
                                                            'selected' => 1,
                                                            'options' => $this->Inputs->getJobExpenses()));
            echo ($this->element('select_with_other',
                                 array('label' => 'Role',
                                       'options' => $this->Inputs->getJobRoles(),
                                       'fieldName' => 'role')));
            echo $this->Form->input('openings', array('label' => 'Number of openings'));
	?>
	<div class="input">
		<label>Skills Required</label>
		<div class="skillbox">
		<?php echo $this->element('skills', array('data' => $skills, 'selectedSkills' => $selectedSkills)) ?>
		</div>
	</div>


	<?php
			echo '<div class="input text">';
			echo $this->Form->Input('published',
									array(
										'options' => $this->Inputs->getPublishedStatuses(),
										'type' => 'select',
										'showEmpty' => false,
										'after' => '<span class="hint">Your job will be visible to candidates once you publish it</span>',
									)
									 );
			echo '</div>';
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit', true));?>
</div>