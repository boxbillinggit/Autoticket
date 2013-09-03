<?php
/**
 * Example BoxBilling module
 *
 * LICENSE
 *
 * This source file is subject to the license that is bundled
 * with this package in the file LICENSE.txt
 * It is also available through the world-wide-web at this URL:
 * http://www.boxbilling.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@boxbilling.com so we can send you a copy immediately.
 *
 * @copyright Copyright (c) 2010-2012 BoxBilling (http://www.boxbilling.com)
 * @license   http://www.boxbilling.com/LICENSE.txt
 * @version   $Id$
 */

/**
 * This file is a delegate for module. Class does not extend any other class
 * 
 * All methods provided in this example are optional, but function names are
 * still reserved.
 * 
 */
class Box_Mod_Autoticket_Service
{
    /**
     * Method to install module. In most cases you will provide your own
     * database table or tables to store extension related data.
     * 
     * If your extension is not very complicated then extension_meta 
     * database table might be enough.
     *
     * @return bool
     * @throws Box_Exception
     */
    public function install()
    {
        // execute sql script if needed
        $pdo = Box_Db::getPdo();
        $query="SELECT NOW()";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
		
        //throw new Box_Exception("Throw exception to terminate module installation process with a message", array(), 123);
        return true;
    }
    
    /**
     * Method to uninstall module.
     * 
     * @return bool
     * @throws Box_Exception
     */
    public function uninstall()
    {
        //throw new Box_Exception("Throw exception to terminate module uninstallation process with a message", array(), 124);
        return true;
    }
    
    /**
     * Method to update module. When you release new version to 
     * extensions.boxbilling.com then this method will be called
     * after new files are placed.
     * 
     * @param array $manifest - information about new module version
     * @return bool
     * @throws Box_Exception
     */
    public function update($manifest)
    {
        //throw new Box_Exception("Throw exception to terminate module update process with a message", array(), 125);
        return true;
    }
    
    /**
     * Method is used to create search query for paginated list.
     * Usually there is one paginated list per module
     * 
     * @param array $data
     * @return array() = list of 2 parameters: array($sql, $params)
     */
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
    
    /**
     * Methods is a delegate for one database row.
     * 
     * @param array $row - array representing one database row
     * @param string $role - guest|client|admin who is calling this method
     * @param bool $deep - true|false deep or light version of result to return to API
     */
    public function toApiArray($row, $role = 'guest', $deep = true)
    {
        return $row;
    }
    
    /**
     * Example event hook. Any module can hook to any BoxBilling event and perform actions
     * 
     * Make sure extension is enabled before testing this event. 
     * 
     * NOTE: IF you have BB_DEBUG mode set to TRUE then all events with params 
     * are logged to bb-data/log/hook_*.log file. Check this file to see what 
     * kind of parameters are passed to event.
     * 
     * In this example we are going to count how many times client failed 
     * to enter correct login details
     * 
     * @param Box_Event $event
     * @return type 
     */
    public static function onEventClientLoginFailed(Box_Event $event)
    {
        //@note almost in all casesyou will need Admin API
        $api = $event->getApiAdmin();
        
        //sometimes you may need guest API
        //$api_guest = $event->getApiGuest();

        $params = $event->getParameters();

        $values = array(
            'ext'        =>  'autoticket',
            'rel_type'   =>  'ip',
            'rel_id'     =>  $params['ip'],
            'meta_key'   =>  'counter',
        );
        $meta = R::findOne('extension_meta', 'extension = :ext AND rel_type = :rel_type AND rel_id = :rel_id AND meta_key = :meta_key', $values);
        if(!$meta) {
            $meta = R::dispense('extension_meta');
            //$count->client_id = null; // client id is not known in this situation
            $meta->extension = 'mod_autoticket';
            $meta->rel_type = 'ip';
            $meta->rel_id = $params['ip'];
            $meta->meta_key = 'counter';
            $meta->created_at = date('c');
        }
        $meta->meta_value = $meta->meta_value + 1;
        $meta->updated_at = date('c');
        R::store($meta);

        if($meta->meta_value > 2) {
            $api->activity_log(array('m'=>'Client failed to enter correct login details '.$meta->meta_value.' time(s)'));
        }
        
        if($meta->meta_value > 30) {
            throw new Exception('You have failed to login too many times. Contact support.');
        }
    }
    
    /**
     * This event hook is registered in example module client API call
     * @param Box_Event $event 
     */
    public static function onAfterClientCalledExampleModule(Box_Event $event)
    {
        //error_log('Called event from example module');
        
        $api = $event->getApiAdmin();
        $params = $event->getParameters();
        
        $meta = R::dispense('extension_meta');
        $meta->extension = 'mod_autoticket';
        $meta->meta_key = 'event_params';
        $meta->meta_value = json_encode($params);
        $meta->created_at = date('c');
        $meta->updated_at = date('c');
        R::store($meta);
    }
    
    /**
     * Example event hook for public ticket and set event return value
     * @param Box_Event $event 
     */
    public static function onBeforeGuestPublicTicketOpen(Box_Event $event)
    {
        $data = $event->getParameters();
        $data['status'] = 'closed';
        $data['subject'] = 'Altered subject';
        $data['message'] = 'Altered text';
        $event->setReturnValue($data);
    }

    /**
     * Example email sending
     * @param Box_Event $event
     */
    public static function onAfterClientOrderCreate(Box_Event $event)
    {
        $api    = $event->getApiAdmin();
        $params = $event->getParameters();
        
        $email = array();
        $email['to_client'] = $params['client_id'];
        $email['code']      = 'mod_example_email'; //@see bb-modules/mod_example/html_email/mod_example_email.phtml
        
        // these parameters are available in email template
        $email['order']     = $api->order_get(array('id'=>$params['id']));
        $email['other']     = 'any other value';
        
        $api->email_template_send($email);
    }
	
	function decode_imap_text($str){
			$result = '';
			$decode_header = imap_mime_header_decode($str);
			foreach ($decode_header AS $obj) {
				$result .= htmlspecialchars(rtrim($obj->text, "\t"));
			}
			return $result;
		}
	
    public static function onAfterAdminCronRun(Box_Event $event) {
		

		$api_guest = $event->getApiGuest();

		$pdo = Box_Db::getPdo();
        $query="SELECT * FROM extension_meta WHERE extension='mod_autoticket'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
		
		$toArray = $stmt->fetchAll();
		$result = json_decode($toArray[0]['meta_value']);
		
		$mbox = imap_open("{".$result->autoticket_host."/imap/notls}INBOX", $result->autoticket_email, $result->autoticket_password)
			  or die("can't connect: " . imap_last_error());
		
		$check = imap_mailboxmsginfo($mbox);
		
		if ($check) {
	 
		 $emails = imap_search($mbox, 'ALL');

						rsort($emails);
						
						foreach($emails as $email_id){
							
							// Fetch the email's overview and show subject, from and date. 
							$overview = imap_fetch_overview($mbox,$email_id,0);	
							$message['body'] = imap_fetchbody($mbox,$email_id,"1");		
							
							$params = array("email"=>Box_Mod_Autoticket_Service::decode_imap_text($overview[0]->from));
							
							$pdo = Box_Db::getPdo();
							$query="SELECT `id`,`email` FROM `client` WHERE `email` = '".Box_Mod_Autoticket_Service::decode_imap_text($overview[0]->from)."'";
							$stmt = $pdo->prepare($query);
							$stmt->execute();
							
							$email = $stmt->fetchAll();
							
							if(empty($email[0]['email'])) {
								
							} else {
								
		$params = array(
		"name" => $overview[0]->subject,
		"email" => $email[0]['email'],
		"subject" => $email[0]['email']." - ".$overview[0]->subject,
		"message" => $message['body']
		);
		$api_guest->support_ticket_create($params);
								
								/*$pdo = Box_Db::getPdo();
								$query="INSERT INTO `support_ticket`(`support_helpdesk_id`, `client_id`, `priority`, `subject`, `status`, `rel_type`, `rel_id`, `rel_task`, `rel_new_value`, `rel_status`, `created_at`, `updated_at`) VALUES ('1',".$email[0]['id'].",100,'".$email[0]['email']." - ".$overview[0]->subject."','open',NULL,NULL,NULL,NULL,NULL,NOW(),NOW())";
								$stmt = $pdo->prepare($query);
								$stmt->execute();
								
								$pdo = Box_Db::getPdo();
								$query="SHOW TABLE STATUS LIKE 'support_ticket'";
								$stmt = $pdo->prepare($query);
								$stmt->execute();
								$NextId = $stmt->fetchAll();
								$newid = $NextId[0]['Auto_increment']-1;
								
								$pdo = Box_Db::getPdo();
								$query="INSERT INTO `support_ticket_message`(`support_ticket_id`, `client_id`, `admin_id`, `content`, `attachment`, `ip`, `created_at`, `updated_at`) VALUES (".$newid.",".$email[0]['id'].",NULL,'".$message['body']."',NULL,NULL,NOW(),NOW())";
								$stmt = $pdo->prepare($query);
								$stmt->execute(); */

								
								//Usuwanie dodanej wiadomości
								imap_delete($mbox, $overview[0]->msgno);
								imap_expunge($mbox);
								
							}
														
						}
						
		//DATA WYKONANIA CRONA!!			
		$date_update = date("c");
		$pdo = Box_Db::getPdo();
        $query="UPDATE `setting` SET `value`='{$date_update}' WHERE id='40'";
        $stmt = $pdo->prepare($query);
        $stmt->execute(); 

		} else {
			echo "imap_check() failed: " . imap_last_error() . "<br />\n";
		}
		
		imap_close($mbox);	
		
	}
}