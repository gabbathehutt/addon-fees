<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>

<!-- begin right column -->

<div class="clear_left shun"></div>
<?php if ($this->session->flashdata('cartthrob_system_error')) : ?>
	<div id="ct_system_error">
		<h4><?=$this->session->flashdata('cartthrob_system_error')?></h4>
	</div>
<?php endif; ?>
<?php if ($this->session->flashdata('cartthrob_system_message')) : ?>
	<div id="ct_system_error">
		<h4><?=$this->session->flashdata('cartthrob_system_message')?></h4>
	</div>
<?php endif; ?>

 
 
<?=$form_open?>
 <div id="cartthrob_settings_content">
 	
	<?php foreach ($sections as $section) : ?>
	
	<?=$this->load->view($section, $data, TRUE)?>
		
	<?php endforeach; ?>
 <p><input type="submit" name="submit" value="Submit" class="submit" /></p>
</form>
 
