<?php $confirmLink = $html->url('/users/confirm/'. $username . '/' . $hash, true); ?>


Dear <?php echo $username ?>,
<br/><br/>
Thank you for registering with HealthVMS! Please click the following link to confirm your registration.
<br/>
<?php echo $html->link($confirmLink, $confirmLink); ?>
<br/><br/>
or copy and paste the following link in your browser:
<br/>
<?php echo $confirmLink ?>