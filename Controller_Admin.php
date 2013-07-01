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
 * This file connects BoxBilling amin area interface and API
 */
class Box_Mod_Autoticket_Controller_Admin
{
    /**
     * This method registers menu items in admin area navigation block
     * This navigation is cached in bb-data/cache/{hash}. To see changes please
     * remove the file
     * 
     * @return array
     */
    public function fetchNavigation()
    {
        return array(
            'group'  =>  array(
                'index'     => 1500,                // menu sort order
                'location'  =>  'autoticket',          // menu group identificator for subitems
                'label'     => 'Auto Ticket',    // menu group title
                'class'     => 'support',           // used for css styling menu item
            ),
            'subpages'=> array(
                array(
                    'location'  => 'autoticket', // place this module in extensions group
                    'label'     => 'Auto Ticket',
                    'index'     => 1500,
                    'uri'       => 'autoticket',
                    'class'     => '',
                ),
				array(
                    'location'  => 'autoticket', // place this module in extensions group
                    'label'     => 'Auto Ticket Ustawienia',
                    'index'     => 1501,
                    'uri'       => 'autoticket/settings',
                    'class'     => '',
                ),
            ),
        );
    }

    /**
     * Method to install module
     *
     * @return bool
     */
    public function install()
    {
        // execute sql script if needed
        $pdo = Box_Db::getPdo();
        $query="SELECT NOW()";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        throw new Box_Exception("Throw exception to terminate module installation process with a message", array(), 123);
        return true;
    }
    
    /**
     * Method to uninstall module
     * 
     * @return bool
     */
    public function uninstall()
    {
        //throw new Box_Exception("Throw exception to terminate module uninstallation process with a message", array(), 124);
        return true;
    }

    /**
     * Methods maps admin areas urls to corresponding methods
     * Always use your module prefix to avoid conflicts with other modules
     * in future
     *
     *
     * @example $app->get('/example/test',      'get_test', null, get_class($this)); // calls get_test method on this class
     * @example $app->get('/example/:id',        'get_index', array('id'=>'[0-9]+'), get_class($this));
     * @param Box_App $app
     */
    public function register(Box_App &$app)
    {
        $app->get('/autoticket',             'get_index', array(), get_class($this));
        $app->get('/autoticket/pobierz',     'get_email', array(), get_class($this));
		$app->get('/autoticket/settings',    'get_settings', array(), get_class($this));
    }
	
	/**
	*
	*   POBIERANIE KONFIGURACJI
	*
	*/
	
	public function install_sql() {
		
		$pdo = Box_Db::getPdo();
        $query="SELECT `param` FROM `setting` WHERE `id` ='40'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
		$checkIfExits = $stmt->fetchAll();
		
		if(empty($checkIfExits)) {
			
			$created_at = date("c");
			$pdo = Box_Db::getPdo();
			$query="INSERT INTO `setting`(`id`, `param`, `value`, `public`, `category`, `hash`, `created_at`, `updated_at`) VALUES (40,'autoticket_last_cron_exec','',0,NULL,NULL,'{$created_at}','')";
			$stmt = $pdo->prepare($query);
			$stmt->execute();
			
		} else {
			return true;
		}
		
	}
	
	public function _config(Box_App $app,$name) {
		$api = $app->getApiAdmin();
		
			$this->install_sql();
		
			$pdo = Box_Db::getPdo();
			$query="SELECT `meta_value` FROM `extension_meta` WHERE `extension` ='mod_autoticket'";
			$stmt = $pdo->prepare($query);
			$stmt->execute();
		
				
		$toArray = $stmt->fetchAll();
		$result = json_decode($toArray[0]['meta_value']);
		
		return $result->$name;
	}
	
	public function _cron_info() {
			$pdo = Box_Db::getPdo();
			$query="SELECT `value` FROM `setting` WHERE `id` ='40'";
			$stmt = $pdo->prepare($query);
			$stmt->execute();	
			$toArray = $stmt->fetchAll();
			return $toArray[0]['value'];
	}

    public function get_index(Box_App $app)
    {
        // always call this method to validate if admin is logged in
        $api = $app->getApiAdmin();
		
		$check = $this->_config($app,"autoticket_host");
		
		if(empty($check))
		{
			header("Location: /bb-admin.php/autoticket/settings");
		} else {
		if (function_exists('imap_open')) {
			$parametr = array();
			$parametr['sciezka'] = $_SERVER['DOCUMENT_ROOT'];
			$parametr['cron']['cron_url'] = 'http://'.$_SERVER['HTTP_HOST'].'/bb-modules/mod_autoticket/Cron.php';
			$parametr['cron']['last_cron_exec'] = $this->_cron_info();
			return $app->render('mod_autoticket_index',$parametr);
		} else {
			return $app->render('mod_autoticket_error');
		}
		}
        
    }
	
	public function decode_imap_text($str){
    $result = '';
    $decode_header = imap_mime_header_decode($str);
    foreach ($decode_header AS $obj) {
        $result .= htmlspecialchars(rtrim($obj->text, "\t"));
    }
    return $result;
	}
	
	public function get_email(Box_App $app) {
	
		$api_admin = $app->getApiAdmin();
	
		$mbox = imap_open("{".$this->_config($app,"autoticket_host")."/imap/notls}INBOX", $this->_config($app,"autoticket_email"), $this->_config($app,"autoticket_password"))
			  or die("can't connect: " . imap_last_error());
		
		$check = imap_mailboxmsginfo($mbox);
		
		if ($check) {
			$obiekty = array();
			
			$obiekty['Date']     = $check->Date;
			$obiekty['Driver']   = $check->Driver;
			$obiekty['Mailbox']  = $check->Mailbox;
			$obiekty['Messages'] = $check->Nmsgs;
			$obiekty['Recent']   = $check->Recent;
			$obiekty['Unread']   = $check->Unread;
			$obiekty['Deleted']  = $check->Deleted;
			$obiekty['Size']     = $check->Size;
		
		
	 
		 $emails = imap_search($mbox, 'ALL');

						rsort($emails);
						
						foreach($emails as $email_id){
							
							// Fetch the email's overview and show subject, from and date. 
							$overview = imap_fetch_overview($mbox,$email_id,0);	
							$message['body'] = imap_fetchbody($mbox,$email_id,"1");		

							$params = array("email"=>$this->decode_imap_text($overview[0]->from));
							
							$client = $api_admin->client_get($params);
							if(empty($client)) {
								
							} else {
								
								$params_ticket = array(
								"client_id" => $client['id'],
								"content" => $message['body'],
								"subject" => $this->decode_imap_text($overview[0]->from).' - '. $overview[0]->subject,
								"support_helpdesk_id" => 1,
								"status" => "open",
								);
								
								$ticket_create = $api_admin->support_ticket_create($params_ticket);
								
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

		
		return $app->render('mod_autoticket_get', $obiekty);
	}
	
	public function get_settings(Box_App $app) {
		$api_admin = $app->getApiAdmin();
		
			$params = array();
			$params['autoticket_host'] = $this->_config($app,"autoticket_host");
			$params['autoticket_email'] = $this->_config($app,"autoticket_email"); 
			$params['autoticket_password'] = $this->_config($app,"autoticket_password");
										
		return $app->render('mod_autoticket_setting',$params);
	}
	
}