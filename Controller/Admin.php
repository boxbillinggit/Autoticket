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

namespace Box\Mod\Autoticket\Controller;

class Admin implements \Box\InjectionAwareInterface
{
	
	protected $di;

    /**
     * @param mixed $di
     */
    public function setDi($di)
    {
        $this->di = $di;
    }

    /**
     * @return mixed
     */
    public function getDi()
    {
        return $this->di;
    }
	
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
                    'uri'       => $this->di['url']->adminLink('autoticket'),
                    'class'     => '',
                ),
				array(
                    'location'  => 'autoticket', // place this module in extensions group
                    'label'     => 'Auto Ticket Ustawienia',
                    'index'     => 1501,
                    'uri'       => $this->di['url']->adminLink('autoticket/settings'),
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
    public function register(\Box_App &$app)
    {
        $app->get('/autoticket',             'get_index', array(), get_class($this));
		$app->get('/autoticket/settings',    'get_settings', array(), get_class($this));
    }


	public function _config(\Box_App $app,$name) {
		$api = $this->di['api_admin'];
		
		$result = $this->di['db']->getRow("SELECT `meta_value` FROM `extension_meta` WHERE `extension` ='mod_autoticket'");
       
		$results = json_decode($result['meta_value']);

		return $results->$name;
	}
	
	public function _cron_info() {
			$cron = $this->di['db']->getRow("SELECT `value` FROM `setting` WHERE `param` ='autoticket_last_cron_exec'");
			return $cron['value'];
	}

    public function get_index(\Box_App $app)
    {
        // always call this method to validate if admin is logged in
		$this->di['is_admin_logged'];
        $api = $this->di['api_admin'];
		
		$check = $this->_config($app,"autoticket_host");

		if(empty($check))
		{
			header("Location: /index.php?_url=/bb-admin/autoticket/settings");
		} else {
		if (function_exists('imap_open')) {
			$parametr = array();
			$parametr['sciezka'] = $_SERVER['DOCUMENT_ROOT'];
			$parametr['cron']['cron_url'] = '/bb-cron.php';
			$parametr['cron']['last_cron_exec'] = $this->_cron_info();
			return $app->render('mod_autoticket_index',$parametr);
		} else {
			return $app->render('mod_autoticket_error');
		}
		}
        
    }
	
	public function get_settings(\Box_App $app) {
		$api_admin = $app->getApiAdmin();
		
			$params = array();
			$params['autoticket_host'] = $this->_config($app,"autoticket_host");
			$params['autoticket_email'] = $this->_config($app,"autoticket_email"); 
			$params['autoticket_password'] = $this->_config($app,"autoticket_password");
										
		return $app->render('mod_autoticket_setting',$params);
	}
	
}