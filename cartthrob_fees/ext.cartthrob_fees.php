<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Fees for CartThrob Extension
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Extension
 * @author		Chris Newton (MIghtyBigRobot)
 * @link		http://cartthrob.com
 */

class Cartthrob_fees_ext {
	
	public $settings 		= array();
	public $description		= 'Charges fees per transaction';
	public $docs_url		= 'http://cartthrob.com';
	public $name			= 'CartThrob Fees';
	public $settings_exist	= 'y';
	public $version			= '1.2';
 	private $module_name = "cartthrob_fees"; 
	private $remove_keys = array(
		'name',
		'submit',
		'x',
		'y',
		'templates',
		'XID',
	);
	
	private $EE;
	private $fees; 
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	public function __construct($settings = '')
	{
		$this->EE =& get_instance();
		$this->settings = $settings;
	}
	

	// ----------------------------------------------------------------------
	
	/**
	 * Activate Extension
	 *
	 * This function enters the extension into the exp_extensions table
	 *
	 * @see http://codeigniter.com/user_guide/database/index.html for
	 * more information on the db class.
	 *
	 * @return void
	 */
	public function activate_extension()
	{
		// Setup custom settings in this array.
		$this->settings = array();
		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'ct_add_fees',
			'hook'		=> 'cartthrob_add_to_cart_start',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		$this->EE->db->insert('extensions', $data);		

		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'cartthrob_calculate_total',
			'hook'		=> 'cartthrob_calculate_total',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		$this->EE->db->insert('extensions', $data);
		
		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'ct_add_fees',
			'hook'		=> 'cartthrob_save_customer_info_end',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		$this->EE->db->insert('extensions', $data);		
		
		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'ct_add_fees',
			'hook'		=> 'cartthrob_update_cart_end',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		$this->EE->db->insert('extensions', $data);		
		
		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'ct_add_fees',
			'hook'		=> 'cartthrob_delete_from_cart_end',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);	
		$this->EE->db->insert('extensions', $data);	
		
		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'ct_add_fees',
			'hook'		=> 'cartthrob_pre_process',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
	}	
 	public function calculate_single($fee = array())
	{
 		if (!empty($fee['fee_price']))
		{
			return $fee['fee_price']; 
		}
		elseif (!empty($fee['fee_percent']))
		{
		 	return $fee['fee_percent'] * $this->subtotal_after_fees() / 100;
		}
		return 0; 
	}
	public function subtotal_after_fees()
	{
		$subtotal = $this->EE->cartthrob->cart->subtotal();
 
		return $subtotal;
	}
	public function get_fees()
	{
		$this->EE->load->add_package_path(PATH_THIRD.'cartthrob/');
		$this->EE->load->library('number');

		$fees = array(); 
		$spent_options = array(); 

		if (empty($this->settings['fees']))
		{
			return array(); 
		}
		
		foreach ($this->settings['fees'] as $fee)
		{
			if (empty($fee['fee_price']) && empty($fee['fee_percent']))
			{
				continue; 
			}
			if (!empty($fee['field_name']) && strtolower(trim($fee['field_name'])) == "auto")
			{
 				$numeric =  $this->calculate_single($fee); 
				$fee['fee_total_numeric'] = $numeric;
 				$fee['fee_total'] = $this->EE->number->format( $this->calculate_single($fee));
				$fees[] = $fee;
			}
 			elseif (!empty($fee['custom_data_key']) && $fee['field_name'] == 'custom_data_key')
			{
				if (trim($fee['custom_data']) == "GLOBAL" || trim($fee['custom_data']) ==  $this->EE->cartthrob->cart->custom_data(strtolower(trim($fee['custom_data_key']))))
				{
					$numeric =  $this->calculate_single($fee); 
					$fee['fee_total_numeric'] = $numeric;
					$fee['fee_total'] = $this->EE->number->format($this->calculate_single($fee));
					$fees[] = $fee;
				}
			}
			elseif (!empty($fee['custom_data_key']) && ($fee['field_name'] == 'item_options_key_once' || $fee['field_name'] == 'item_options_key_each') )
			{
				$data_key =strtolower(trim($fee['custom_data_key']));
				$data_value = strtolower(trim($fee['custom_data']));  

				foreach ($this->EE->cartthrob->cart->items() as $item)
				{
					if ($item->item_options($data_key) && ($item->item_options($data_key) == $data_value || $data_value=="GLOBAL"))
					{
						if ($fee['field_name']== "item_options_key_once" && (empty($spent_options[$data_key]) && ! in_array($data_value,$spent_options[$data_key] )))
						{
								$numeric =  $this->calculate_single($fee); 
								$fee['fee_total_numeric'] = $numeric;
								$fee['fee_total'] = $this->EE->number->format($this->calculate_single($fee));
								$fees[] = $fee;
								$spent_options[$data_key][] = $data_value; 
						}
						elseif ($fee['field_name']== "item_options_key_each")
						{
							$numeric =  $this->calculate_single($fee); 
							$fee['fee_total_numeric'] = $numeric;
							$fee['fee_total'] = $this->EE->number->format($this->calculate_single($fee));
							$fees[] = $fee;
 						}
					}
				}
			}
			elseif (!empty($fee['field_name']) && $this->EE->cartthrob->cart->customer_info(strtolower(trim($fee['field_name']))))
			{
  				if (trim($fee['custom_data']) == "GLOBAL"  || trim($fee['custom_data']) == $this->EE->cartthrob->cart->customer_info(strtolower(trim($fee['field_name']))))
				{
					$numeric =  $this->calculate_single($fee); 
					$fee['fee_total_numeric'] = $numeric; 
					$fee['fee_total'] = $this->EE->number->format($numeric);
					$fees[] = $fee;
				}
  				elseif ( $fee['field_name'] == "gateway" )
				{
					$this->EE->load->library('encrypt'); 
					
					$gateway = $this->EE->cartthrob->cart->customer_info(strtolower(trim($fee['field_name'])));
					$decrypted_gateway = $this->EE->encrypt->decode($gateway); 
					if (	   $gateway ==  trim($fee['custom_data']) 
							|| $decrypted_gateway ==  trim($fee['custom_data'])
							|| $gateway ==  "Cartthrob_".trim($fee['custom_data'])  
							|| $decrypted_gateway ==  "Cartthrob_".trim($fee['custom_data'])
						)
					{
						$numeric =  $this->calculate_single($fee); 
						$fee['fee_total_numeric'] = $numeric; 
						$fee['fee_total'] = $this->EE->number->format($numeric);
						$fees[] = $fee;
					}
				}
			}
		}
 
 		if (isset($fees))
		{
 	  		$this->EE->cartthrob->cart->set_custom_data('cartthrob_fees', $fees); 
			$this->fees = $fees; 
		}
		return $fees; 
	}
	public function ct_add_fees()
	{
		$this->get_fees(); 
		
	}
	public function cartthrob_calculate_total()
	{
		
		if (empty($this->settings['fees']))
		{
			return FALSE;
		}
		$total =  $this->EE->cartthrob->cart->subtotal() + $this->EE->cartthrob->cart->shipping() + $this->EE->cartthrob->cart->tax() - $this->EE->cartthrob->cart->discount();
		
		/// remove all fees from cart. 
		foreach ($this->settings['fees'] as $fee)
		{
			$items = $this->EE->cartthrob->cart->filter_items(array('entry_id' => NULL, 'title' => $fee['fee_name'] )); 
			if ($items)
			{
				foreach ($items as $row_id=> $item)
				{
 					$this->EE->cartthrob->cart->remove_item($row_id);					
				}
			}
		}
		if ($this->subtotal_after_fees() > 0)
		{
			$fee_total = 0; 
			if ($fees = $this->get_fees())
			{
				foreach ($fees as $fee)
				{
					if ($fee['taxable'] == "yes")
					{
						$no_tax = FALSE; 
					}
					else
					{
						$no_tax = TRUE; 
					}
					
					$items = $this->EE->cartthrob->cart->filter_items(array('entry_id' => NULL, 'title' => $fee['fee_name'] )); 

					if (! $items)
					{
						$data = array(
							'entry_id'	=> NULL, 
							'product_id'=> NULL,
							'quantity'	=> 1,
							'price'	=> $this->calculate_single($fee),
							'title'	=> $fee['fee_name'],
							'no_shipping'	=> TRUE, 
							'no_tax'	=> $no_tax,
			 			); 
						$fee_total +=$this->calculate_single($fee); 
						$this->EE->cartthrob->cart->add_item($data);
					}
					else
					{
						foreach ($items as $row_id=> $item)
						{
							$data = array(
								'entry_id'	=> NULL, 
								'product_id'=> NULL,
								'quantity'	=> 1,
								'price'	=> $this->calculate_single($fee),
								'title'	=> $fee['fee_name'],
								'no_shipping'	=> TRUE, 
								'no_tax'	=> $no_tax,
				 			); 
				
							$fee_total +=$this->calculate_single($fee); 
				
							$this->EE->cartthrob->cart->update_item($row_id, $data);					
						}
					}
				}
 				return $total + $fee_total;  
			}
		}
		return FALSE; 
	}
	public function calculate_fee_total()
	{
  		$fee_total = 0;
		
		foreach ($this->get_fees() as $fee)
		{
			$fee_total += $this->calculate_single($fee); 
		}
 		return $fee_total;
	}
 
	
	// ----------------------------------------------------------------------

	/**
	 * Disable Extension
	 *
	 * This method removes information from the exp_extensions table
	 *
	 * @return void
	 */
	function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}

	// ----------------------------------------------------------------------

	/**
	 * Update Extension
	 *
	 * This function performs any necessary db updates when the extension
	 * page is visited
	 *
	 * @return 	mixed	void on update / false if none
	 */
	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
	}	
	
	public function settings()
	{
		return array(); 
	}
	
	public function settings_form($current)
	{
		$this->settings = $current; 
 
		$structure['class']	= 'global_fees'; 
		$structure['description']	=''; 
		$structure['caption']	=''; 
		$structure['title']	= "global_fees"; 
	 	$structure['settings'] = array(
			array(
				'name' => 'fees',
				'short_name' => 'fees',
				'type' => 'matrix',
				'settings' => array(
					array(
						'name'=>'fee_name',
						'short_name'=>'fee_name',
						'type'=>'text',
					),
					array(
						'name'=>'fee_price',
						'short_name'=>'fee_price',
						'type'=>'text',
					),
					array(
						'name'=>'fee_percent',
						'short_name'=>'fee_percent',
						'type'=>'text',
					),
					array(
						'name'=>'apply_when',
						'short_name'=>'field_name',
						'type'=>'select',
						'default'	=> 'AUTO',
						'options'	=>  array(
							''			=> '---',
							'AUTO'			=> 'any_field', 
							'custom_data_key'			=> 'use_custom_data_key',
							'item_options_key_each'			=> 'item_options_each',
							'item_options_key_once'			=> 'item_options_once',
							"first_name" 	=> "first_name",
							"last_name" 	=> "last_name",
							"address" 	=> "address",
							"address2" 	=> "address2",
							"city" 	=> "city",
							"state" 	=> "state",
							"zip" 	=> "zip",
							"country_code" 	=> "country_code",
							"company" 	=> "company",
							"phone" 	=> "phone",
							"email_address" 	=> "email_address",
							"ip_address" 	=> "ip_address",
							"description" 	=> "description",
							"use_billing_info" 	=> "use_billing_info",
							"shipping_first_name" 	=> "shipping_first_name",
							"shipping_last_name" 	=> "shipping_last_name",
							"shipping_address" 	=> "shipping_address",
							"shipping_address2" 	=> "shipping_address2",
							"shipping_city" 	=> "shipping_city",
							"shipping_state" 	=> "shipping_state",
							"shipping_zip" 	=> "shipping_zip",
 							"shipping_country_code" 	=> "shipping_country_code",
							"shipping_company" 	=> "shipping_company",
							"card_type" 	=> "card_type",
							"currency_code" 	=> "currency_code",
							"language" 	=> "language",
							"shipping_option" 	=> "shipping_option",
							"region" 	=> "region",
							"username " 	=> "username",
							"screen_name" 	=> "screen_name",
							"group_id" 	=> "group_id", 
							'gateway'	=> 'gateway',
							),
					),
					array(
						'name'=>'or_when',
						'short_name'=>'custom_data_key',
						'type'=>'text',
					),
					array(
						'name'=>'matches',
						'short_name'=>'custom_data',
						'type'=>'text',
						'default'	=> 'GLOBAL',
					),
					array(
						'name'=>'taxable',
						'short_name'=>'taxable',
						'type'=>'select',
						'default'	=> 'no',
						'options' => array('no'=> 'no' , 'yes' => 'yes'),
					),
				)
			),
	 	);
		return $this->load_view(__FUNCTION__, array(), $structure);
	}
	public function save_settings()
	{
		if (empty($_POST))
	    {
	        show_error($this->EE->lang->line('unauthorized_access'));
	    }
	
		$data = array();
		
		foreach (array_keys($_POST) as $key)
		{
			if ( ! in_array($key, $this->remove_keys) && ! preg_match('/^('.ucwords($this->module_name).'_.*?_settings)_.*/', $key))
			{
				$data[$key] = $this->EE->input->post($key, TRUE);
			}
		}
 
  		$this->EE->db->where('class', $this->module_name."_ext");
	    $this->EE->db->update('extensions', array('settings' => serialize($data)));
	    
		$this->EE->session->set_flashdata('message_success', sprintf('%s', lang('settings_saved')));
		
		$return = ($this->EE->input->get('return')) ? AMP.'method='.$this->EE->input->get('return', TRUE) : '';
		
		if ($this->EE->input->post($this->module_name.'_tab'))
		{
			$return .= '#'.$this->EE->input->post($this->module_name.'_tab', TRUE);
		}
		
		$this->EE->functions->redirect(
            BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file='.$this->module_name.$return
        );
	}

	// --------------------------------
	//  Plugin Settings
	// --------------------------------
	/**
	 * Creates setting controls
	 * 
	 * @access private
	 * @param string $type text|textarea|radio The type of control that is being output
	 * @param string $name input name of the control option
	 * @param string $current_value the current value stored for this input option
	 * @param array|bool $options array of options that will be output (for radio, else ignored) 
	 * @return string the control's HTML 
	 * @since 1.0.0
	 * @author Rob Sanchez
	 */
	public function plugin_setting($type, $name, $current_value, $options = array(), $attributes = array())
	{
		$output = '';
		
		if ( ! is_array($options))
		{
			$options = array();
		}
		else
		{
			$new_options = array(); 
 			foreach ($options as $key => $value)
			{
				// optgropus
				if (is_array($value))
				{	
					$key = lang($key); 
					foreach ($value as $sub=> $item)
					{
						$new_options[$key][$sub] = lang($item);
					}
				}
				else
				{
					$new_options[$key] = lang($value);
				}
			}
			$options = $new_options;
		}
		
		if ( ! is_array($attributes))
		{
			$attributes = array();
		}

		switch ($type)
		{
			case 'select':
				if (empty($options)) $attributes['value'] = $current_value;
				$output = form_dropdown($name, $options, $current_value, _attributes_to_string($attributes));
				break;
			case 'multiselect':
				$output = form_multiselect($name, $options, $current_value, _attributes_to_string($attributes));
				break;
			case 'checkbox':
				$output = form_label(form_checkbox($name, 1, ! empty($current_value), isset($options['extra']) ? $options['extra'] : '').'&nbsp;'.(!empty($options['label'])? $options['label'] : $this->EE->lang->line('yes') ), $name);
				break;
			case 'text':
				$attributes['name'] = $name;
				$attributes['value'] = $current_value;
				$output =  form_input($attributes);
				break;
			case 'textarea':
				$attributes['name'] = $name;
				$attributes['value'] = $current_value;
				$output =  form_textarea($attributes);
				break;
			case 'radio':
				if (empty($options))
				{
					$output .= form_label(form_radio($name, 1, (bool) $current_value).'&nbsp;'. $this->EE->lang->line('yes'), $name, array('class' => 'radio'));
					$output .= form_label(form_radio($name, 0, (bool) ! $current_value).'&nbsp;'. $this->EE->lang->line('no'), $name, array('class' => 'radio'));
				}
				else
				{
					//if is index array
					if (array_values($options) === $options)
					{
						foreach($options as $option)
						{
							$output .= form_label(form_radio($name, $option, ($current_value === $option)).'&nbsp;'. $option, $name, array('class' => 'radio'));
						}
					}
					//if associative array
					else
					{
						foreach($options as $option => $option_name)
						{
							$output .= form_label(form_radio($name, $option, ($current_value === $option)).'&nbsp;'. lang($option_name), $name, array('class' => 'radio'));
						}
					}
				}
				break;
			default:
		}
		return $output;
	}
	private function load_view($current_nav, $more = array(), $structure = array())
	{
		$this->EE->load->add_package_path(PATH_THIRD.'cartthrob/');
		
		$this->EE->load->model('field_model'); 
		$this->EE->load->model('channel_model');
		$this->EE->load->library('locales'); 
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line($this->module_name.'_module_name').' - '.$this->EE->lang->line('nav_head_'.$current_nav));
		
		// we need to get CartThrob's settings for this too. DON'T CHANGE THIS. It's not a mistake. 
 		$settings =  array_merge((array) $this->settings,$this->get_cartthrob_settings()); 
		
		$channels = $this->EE->channel_model->get_channels()->result_array();
 		
		$fields = array();
		
		$channel_titles = array();

		$product_channels = $this->EE->cartthrob->store->config('product_channels'); 
		
		foreach ($channels as $channel)
		{
			if (in_array($channel['channel_id'], $product_channels))
			{
				$channel_titles[$channel['channel_id']] = $channel['channel_title'];

	 			$fields[$channel['channel_id']] = $this->EE->field_model->get_fields($channel['field_group'])->result_array();

				$statuses[$channel['channel_id']] = $this->EE->channel_model->get_channel_statuses($channel['status_group'])->result_array();
			}
		}
 		$status_titles = array(); 
		foreach ($statuses as $status)
		{
			foreach ($status as $item)
			{
				$status_titles[$item['status']] = $item['status']; 
			}
		}
		$sections[] = $this->module_name; 

		$data = array(
			'structure'	=> $structure, 
			'channels' => $channels,
			'channel_titles' => $channel_titles,
			'fields' => $fields,
			'statuses' => $statuses,
			'status_titles' => $status_titles,
			'settings_mcp' => $this,
			'channel_titles'	=> $channel_titles,
			'module_name'	=> $this->module_name,
			'settings' => $settings,
			'sections'	=> $sections,
			'states_and_countries' => array_merge(array('global' => 'Global', 'eur'=> 'Europe'), $this->EE->locales->states(), array('0' => '---'), $this->EE->locales->all_countries()),
			'states' => $this->EE->locales->states(),
			'countries' => array_merge(array('global' => 'Global',  'eur'=> 'Europe'), $this->EE->locales->all_countries()),
			'templates' =>  $this->get_templates(),
			'form_open' => form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file='.$this->module_name)
		);
		
		if (!empty($structure))
		{
			$data['html'] = $this->EE->load->view($this->module_name.'_settings_template', $data, TRUE);
		}
		
 		$data = array_merge($data, $more);
		
		$self = $data;
		
		$data['data'] = $self;
		
		unset($self);
		
		$this->EE->cp->add_to_head($this->EE->load->view($this->module_name.'_settings_form_head', $data, TRUE));
		
		return $this->EE->load->view($this->module_name.'_settings_form', $data, TRUE);
	}
	
	public function get_templates()
	{
		static $templates;
		
		if (is_null($templates))
		{
			$templates = array();
			
			$this->EE->load->model('template_model');
			
			$query = $this->EE->template_model->get_templates();
			
			foreach ($query->result() as $row)
			{
				$templates[$row->group_name.'/'.$row->template_name] = $row->group_name.'/'.$row->template_name;
			}
		}
		
		return $templates;
	}
	// ----------------------------------------------------------------------
	
	private function get_cartthrob_settings()
	{
		$this->EE->load->library('get_settings');
		if (class_exists('cartthrob'))
		{
			return $this->EE->get_settings->settings("cartthrob");
			
		}
		return array(); 
		
	}
	// ----------------------------------------------------------------------
}

