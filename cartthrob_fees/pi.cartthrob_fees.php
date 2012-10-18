<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
$plugin_info = array(
						'pi_name'			=> 'CartThrob Fees Utilities',
						'pi_version'		=> '1',
						'pi_author'			=> 'Chris Newton',
						'pi_author_url'		=> 'http://www.cartthrob.com',
						'pi_description'	=> 'Outputs data related to global fees.',
						'pi_usage'			=> Cartthrob_fees::usage()
					);

class Cartthrob_fees
{
 
	function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->add_package_path(PATH_THIRD.'cartthrob/');
		$this->EE->load->library('cartthrob_loader');
		$this->EE->load->library('number');
	}
	
	function get_fees()
	{
		$total = $this->EE->cartthrob->cart->total(); 
		
		$fees = $this->EE->cartthrob->cart->custom_data('cartthrob_fees');
		
		if (!$fees)
		{
			return $this->EE->TMPL->no_results; 
		}
 		
 		foreach ($fees as $fee)
		{
			$tagdata = $this->EE->TMPL->tagdata; 
			
 			foreach ($fee as $key => $value)
			{
				 $tagdata= $this->EE->TMPL->swap_var_single($key, $value, $tagdata);
			}
			$this->return_data .= $tagdata;
			
		}
		return $this->return_data;
	}
 	function total()
	{
		$fee_total = 0;
		$fees = (array) $this->EE->cartthrob->cart->custom_data('cartthrob_fees');
		
		foreach ($fees as $fee)
		{
			$fee_total += $fee['fee_total_numeric']; 
		}
 		return $this->EE->number->format($fee_total);		
	}
 	public function usage()
	{
		ob_start();
?>

Docs: 

Gets fee total

{exp:cartthrob_fees:total}


Get fees tag pair: Outputs data about applied fees

{exp:cartthrob_fees:get_fees}

	Fee total with prefix {fee_total}
	Fee total (numeric) {fee_total_numeric}
	Fee percent (if any) {fee_percent}
	Fee descriptive name {fee_name} 

	{if no_results}no fees were added{/if}

{/exp:cartthrob_fees:get_fees}
 
<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	} /* End of usage() function */
	
}