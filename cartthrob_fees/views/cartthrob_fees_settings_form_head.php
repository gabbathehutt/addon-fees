<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>
<link href="<?=$this->config->item('theme_folder_url')?>third_party/cartthrob/css/cartthrob.css" rel="stylesheet" type="text/css" />

<script type="text/javascript">
	
	jQuery.cartthrob_feesCP = {
		currentSection: function() {
			if (window.location.hash && window.location.hash != '#') {
				return window.location.hash.substring(1);
			} else {
				return $('#cartthrob_settings_content h3:first').attr('data-hash');
			}
		},
		channels: <?=$this->javascript->generate_json($channel_titles)?>,
		fields: <?=$this->javascript->generate_json($fields)?>,
		statuses: <?=$this->javascript->generate_json($status_titles)?>,
		templates: <?=$this->javascript->generate_json($templates)?>,
		states: <?=$this->javascript->generate_json($states)?>,
		countries: <?=$this->javascript->generate_json($countries)?>,
		statesAndCountries: <?=$this->javascript->generate_json($states_and_countries)?>,
		checkSelectedChannel: function (selector, section) {
			if ($(selector).val() !="") {
				$(section).css("display","inline");
			} else {
				$(section).css("display","none");
			}
		},
		updateSelect: function(select, options) {
			var val = $(select).val();
			var attrs = {};
			for (i=0;i<select.attributes.length;i++) {
				if (select.attributes[i].name == 'value') {
					val = select.attributes[i].value;
				} else {
					attrs[select.attributes[i].name] = select.attributes[i].value;
				}
			}
			$(select).replaceWith($.cartthrob_feesCP.createSelect(attrs, options, val));
		},
		createSelect: function(attributes, options, selected) {
			var select = '<select ';
			for (i in attributes) {
				select += i+'="'+attributes[i]+'" ';
			}
			select += '>';
			for (i in options) {
				select += '<option value="'+i+'"';
				if (selected != undefined && selected == i) {
					select += ' selected="selected"';
				}
				select += '>'+options[i]+'</option>';
			}
			select += '</select>';
			return select;
		},
		changeProductChannel: function(select) {
			var channel_id = $(select).val();
			if (channel_id != '')
			{
				$(select).parents('tbody').find('.product_channel_fields option').not('.blank').remove();
				for (i in $.cartthrob_feesCP.fields[channel_id])
				{
					$(select).parents('tbody').find('.product_channel_fields').append('<option value="'+$.cartthrob_feesCP.fields[channel_id][i].field_id+'">'+$.cartthrob_feesCP.fields[channel_id][i].field_label+'</option>');
				}
				$(select).parents('tbody').find('.product_channel_fields.product_price').attr('name', 'product_channel_fields['+channel_id+'][price]');
				$(select).parents('tbody').find('.product_channel_fields.product_shipping').attr('name', 'product_channel_fields['+channel_id+'][shipping]');
				$(select).parents('tbody').find('.product_channel_fields.product_weight').attr('name', 'product_channel_fields['+channel_id+'][weight]');
				$(select).parents('tbody').find('.product_channel_fields.product_price_modifiers').attr('name', 'product_channel_fields['+channel_id+'][price_modifiers][]');
				$(select).parents('tbody').find('.product_channel_fields.product_global_price').attr('name', 'product_channel_fields['+channel_id+'][global_price]');
			}
		}
	}
	
	jQuery(document).ready(function($){
		
 		$('select.states').each(function(){
			$.cartthrob_feesCP.updateSelect(this, $.cartthrob_feesCP.states);
		});
		$('select.states_blank').each(function(){
			var states = {'' : '---'};
			$.extend(states, $.cartthrob_feesCP.states);
			$.cartthrob_feesCP.updateSelect(this, states);
		});
		$('select.templates').each(function(){
			$.cartthrob_feesCP.updateSelect(this, $.cartthrob_feesCP.templates);
		});
		$('select.templates_blank').each(function(){
			var templates = {'' : '---'};
			$.extend(templates, $.cartthrob_feesCP.templates);
			$.cartthrob_feesCP.updateSelect(this, templates);
		});
		$('select.statuses').each(function(){
			$.cartthrob_feesCP.updateSelect(this, $.cartthrob_feesCP.statuses);
		});
		$('select.statuses_blank').each(function(){
			var statuses = {'' : '---', 'ANY' : 'ANY'};
			$.extend(statuses, $.cartthrob_feesCP.statuses);
			$.cartthrob_feesCP.updateSelect(this, statuses);
		});
		
		$('select.countries').each(function(){
			$.cartthrob_feesCP.updateSelect(this, $.cartthrob_feesCP.countries);
		});
		$('select.countries_blank').each(function(){
			var countries = {'' : '---'};
			$.extend(countries, $.cartthrob_feesCP.countries);
			$.cartthrob_feesCP.updateSelect(this, countries);
		});
		$('select.states_and_countries').each(function(){
			$.cartthrob_feesCP.updateSelect(this, $.cartthrob_feesCP.statesAndCountries);
		});
		$('select.all_fields').each(function(){
			var fields = {'':'---'};
			for (i in $.cartthrob_feesCP.fields) {
				for (j in $.cartthrob_feesCP.fields[i]) {
					fields['field_id_'+$.cartthrob_feesCP.fields[i][j].field_id] = $.cartthrob_feesCP.fields[i][j].field_label;
				}
			}
			$.cartthrob_feesCP.updateSelect(this, fields);
		});
		$('select.channels, select.product_channel').each(function(){
			$.cartthrob_feesCP.updateSelect(this, $.cartthrob_feesCP.channels);
		});
		
		
		
		// BEGIN HIDE/SHOW SETTINGS AFTER APPROPRIATE SETTINGS HAVE BEEN CHOSEN
			
		$.cartthrob_feesCP.checkSelectedChannel('#select_orders', ".requires_orders_channel"); 
		$.cartthrob_feesCP.checkSelectedChannel('#select_purchased_items', ".requires_purchased_items_channel"); 
		$.cartthrob_feesCP.checkSelectedChannel('#select_coupon_code', ".requires_coupons_channel"); 
		$.cartthrob_feesCP.checkSelectedChannel('#select_discount', ".requires_discounts_channel"); 
	
 
		// END HIDE/SHOW SETTINGS. 
 
 
		$('#cartthrob_tab').val($.cartthrob_feesCP.currentSection());
		$('select.channels').bind('change', function(){
			var channel_id = Number($(this).val());
			var section = $(this).attr('id').replace('select_', '');
			$('select.field_'+section).children().not('.blank').remove();
			$('select.status_'+section).children().not('.blank').remove();
			if ($(this).val() != "")
			{
				for (i in $.cartthrob_feesCP.fields[channel_id])
				{
					$('select.field_'+section).append('<option value="'+$.cartthrob_feesCP.fields[channel_id][i].field_id+'">'+$.cartthrob_feesCP.fields[channel_id][i].field_label+'</option>');
				}
				for (i in $.cartthrob_feesCP.statuses[channel_id])
				{
					$('select.status_'+section).append('<option value="'+$.cartthrob_feesCP.statuses[channel_id][i].status_id+'">'+$.cartthrob_feesCP.statuses[channel_id][i].status+'</option>');
				}
			}
		});
		$('select.plugins').bind('change', function(){
			var type = $(this).attr('id').replace('select_', '');
			var classname = $(this).val();
			$('.'+type+'_settings').hide();
			$('#'+classname).show();
		}).each(function() {
			if ($(this).val() != '')
			{
				$(this).change();
			}
		});
 
 
		
		$('fieldset.plugin_add_new_setting a').bind('click', function(){
			var view_type = $(this).attr('rel'); 
			
			var name = $(this).attr('id').replace('add_new_', '');
			var count = ($('tr.'+name+'_setting:last').length > 0) ? Number($('tr.'+name+'_setting:last').attr('id').replace(name+'_setting_','')) + 1 : 0;
			var plugin_classname = $('#'+name+'_blank').parent().parent().attr('class').split(" ")[0];
			var setting_short_name = $('#'+name+'_blank').attr('class').split(" ")[0];
			var clone = $('#'+name+'_blank').clone();
			clone.attr({'id':name+'_setting_'+count});
			clone.attr({'class':name+'_setting'});
			clone.attr({'rel': plugin_classname+'_settings['+setting_short_name+']'});
			clone.find(':input').each(function(){

				var matrix_setting_short_name = $(this).parent().attr('class');
				
				if (! $(this).hasClass("plugin_add_new_setting"))
				{
					 matrix_setting_short_name = matrix_setting_short_name.replace("_setting_option", "");
				}
				
				$(this).parent().attr('rel', matrix_setting_short_name);
				if (view_type == "settings_template")
				{
					$(this).attr('name', setting_short_name+'['+count+']['+matrix_setting_short_name+']');	
				}
				else
				{
					$(this).attr('name', plugin_classname+'_settings['+setting_short_name+']['+count+']['+matrix_setting_short_name+']');	
				}
			});
			
			if ($(this).hasClass("plugin_add_new_setting"))
			{
 				clone.children('td').attr('class','');
			}
 
			$(this).parent().prev().find('tbody').append(clone);
			return false;
		});
		
		$('#add_product_channel a').bind('click', function(){
			var clone = $('table#product_channel_blank').clone();
			clone.insertBefore('#add_product_channel').attr({id: ''}).show().find('select.product_channel').attr({name: 'product_channels[]'}); 
			
			return false;
		});
		

		$('select.product_channel').live('change', function(){
			$.cartthrob_feesCP.changeProductChannel(this);
		}).each(function(){
			if ($(this).children('option').length <= 1 && $(this).parents('tbody').find('select.product_channel_fields:first').children().not('.blank').length === 0)
			{
				$.cartthrob_feesCP.changeProductChannel(this);
			}
		});
		
		$('a.remove_matrix_row').live('click', function(){
			if (confirm('Are you sure you want to delete this row?'))
			{
				if ($(this).parent().get(0).tagName.toLowerCase() == 'td')
				{
					$(this).parent().parent().remove();
				}
				else
				{
					$(this).parent().remove();
				}
			}
			return false;
		}).live('mouseover', function(){
			$(this).find('img').animate({opacity:1});
		}).live('mouseout', function(){
			$(this).find('img').animate({opacity:.2});
		}).find('img').css({opacity:.2});

 
		$('.add_matrix_row').bind('click', function(){
			var name = $(this).attr('id').replace('_button', '');
			var index = ($('.'+name+'_row:last').length > 0) ? Number($('.'+name+'_row:last').attr('id').replace(name+'_row_','')) + 1 : 0;
			var clone = $('#'+name+'_row_blank').clone(); 
			
			clone.attr('id', name+'_row_'+index).addClass(name+'_row').show();
			clone.find(':input').bind('each', function(){
				$(this).attr('name', $(this).attr('data-hash').replace('INDEX', index));
			});
			$(this).parent().before(clone);
			return false;
		});
		


		$('a.remove_product_table').live('click', function(){
			if (confirm('Are you sure you want to delete this row?'))
			{
 				$(this).parents('table').remove();
			}
			return false;
		}).live('mouseover', function(){
			$(this).find('img').animate({opacity:1});
		}).live('mouseout', function(){
			$(this).find('img').animate({opacity:.2});
		}).find('img').css({opacity:.2});
		
		// Return a helper with preserved width of cells
		var fixHelper = function(e, ui) {
			ui.children().each(function() {
				$(this).width($(this).width());
			});
			return ui;
		};

		$("div.matrix table tbody").sortable({
			helper: fixHelper,
			stop: function(event, ui) { 
				var count=0; 
				$("div.matrix table tbody tr").each(function(){
					$(this).find(':input').each(function(){
 						$(this).attr('name', $(this).parents('tr').attr('rel')+'['+count+']['+$(this).parent().attr('rel')+']');	
					}); 
					count +=1; 
				});
			}
		});
		
		$('input[name=locales_countries]').change(function(){
			if ($(this).is(':checked')) {
				$('select[name="locales_countries[]"]').attr('disabled', true).children('option').attr('selected', '');
			} else {
				$('select[name="locales_countries[]"]').attr('disabled', false);
			}
		});
		
 	});
<?php
/*
		var channels = new Array();
		var channel_fields = new Array();
		var channel_statuses = new Array();

	<?php foreach ($channel_titles as $channel_id => $blog_title) : ?>
		channels[<?php echo $channel_id; ?>] = "<?php echo str_replace("'", "&#39;", $blog_title); ?>";
	<?php endforeach; ?>

	<?php foreach ($fields as $key => $value) : ?>
		channel_fields[<?php echo $key; ?>] = new Array();

		<?php foreach ($value as $count => $field_data) : ?>
		channel_fields[<?php echo $key; ?>][<?php echo $count; ?>] = ['<?php echo $field_data['field_id']; ?>', '<?php echo $field_data['field_name']; ?>', '<?php echo str_replace("'", '&#39;', $field_data['field_label']); ?>'];
		<?php endforeach; ?>

	<?php endforeach; ?>

	<?php foreach ($statuses as $key => $value) : ?>
		channel_statuses[<?php echo $key; ?>] = new Array();

		<?php foreach ($value as $count => $status) : ?>
		channel_statuses[<?php echo $key; ?>][<?php echo $count; ?>] = ['<?php echo $status['status_id']; ?>', '<?php echo $status['status']; ?>', '<?php echo ucwords(str_replace(array("'", '_'), array('&#39;', ' '), $status['status'])); ?>'];
		<?php endforeach; ?>

	<?php endforeach; ?>
*/
?>

</script>