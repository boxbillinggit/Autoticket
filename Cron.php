<?php
require_once( dirname( __FILE__ )."/../../bb-load.php" );
define( "BB_MODE_CRON", TRUE );

		$service = new Box_Event();
		
		$app = new Box_App($di);
		$ext = new Box_EventDispatcher();
		$dbs = new Box_Db();

		$api = $service->getApiGuest();
		$params = $service->getParameters();

		$pdo = Box_Db::getPdo();
        $query="SELECT * FROM extension_meta WHERE extension='mod_autoticket'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
		
		$toArray = $stmt->fetchAll();
		$result = json_decode($toArray[0]['meta_value']);
					
		function decode_imap_text($str){
			$result = '';
			$decode_header = imap_mime_header_decode($str);
			foreach ($decode_header AS $obj) {
				$result .= htmlspecialchars(rtrim($obj->text, "\t"));
			}
			return $result;
		}
		
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
							
							$params = array("email"=>decode_imap_text($overview[0]->from));
							
							$pdo = Box_Db::getPdo();
							$query="SELECT `id`,`email` FROM `client` WHERE `email` = '".decode_imap_text($overview[0]->from)."'";
							$stmt = $pdo->prepare($query);
							$stmt->execute();
							
							$email = $stmt->fetchAll();
							
							if(empty($email[0]['email'])) {
								
							} else {
								
								$pdo = Box_Db::getPdo();
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
								$stmt->execute(); 

								
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


unset( $service );
unset( $api );
?>