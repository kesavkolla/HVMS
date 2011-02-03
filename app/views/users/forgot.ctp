<?php
    if (isset($success))     {
        echo $success;
    }
    else {
        echo $form->create('User', array('action' => 'forgot'));
        echo $form->input('username', array('label' => 'Email'));   
        echo $form->end('Reset my password');
    }
?>