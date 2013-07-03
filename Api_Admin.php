<?php
/**
 * Example module API
 * This api can be access only for admins
 */
class Box_Mod_Autoticket_Api_Admin extends Api_Abstract
{
    /**
     * Return list of example objects
     * 
     * @return array
     */
	 
	public function config_save($data) {

		$data = array();
		
		$return = json_encode($data + $_POST);
		
		$pdo = Box_Db::getPdo();
        $query="UPDATE `extension_meta` SET `meta_value`='{$return}' WHERE extension='mod_autoticket'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
		
		return $return;

	}
	 
    public function get_something($data)
    {
        $result = array(
            'apple',
            'google',
        );

        if(isset($data['microsoft'])) {
            $result[] = 'microsoft';
        }
        
        return $result;
    }
	
	public function getSearchQuery($data)
    {
        $params = array();
        $sql="SELECT meta_key, meta_value
            FROM extension_meta
            WHERE extension = 'autoticket' ";
        
        $client_id = isset($data['client_id']) ? $data['client_id'] : NULL;
        
        if(NULL !== $client_id) {
            $sql .= ' AND client_id = :client_id';
            $params['client_id'] = $client_id;
        }
        
        $sql .= ' ORDER BY created_at DESC';
        return array($sql, $params);
    }
	
}