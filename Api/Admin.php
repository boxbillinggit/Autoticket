<?php
/**
 * Example module API 
 * This api can be access only for admins
 */
namespace Box\Mod\Autoticket\Api;

class Admin extends \Api_Abstract
{
    /**
     * Return list of example objects
     * 
     * @return array
     */
	 
	public function config_save($data) {

		$data = array();
		
		$return = json_encode($data + $_POST);
		
		$this->di['db']->exec('UPDATE extension_meta SET meta_value = :cat WHERE extension = :old_cat', 
                        array('cat'=>$return, 'old_cat'=>'mod_autoticket'));
		
		return $return;

	}
	
}