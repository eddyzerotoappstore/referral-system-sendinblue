<?php

/* Declare the file path for the SendinBlue API */	
require_once('./APIv3-php-library-master/autoload.php');
/* Add the list ID that corresponds to the list you want your invited to contacts to be added to in your SendinBlue account */	
$list_id = "";
/* Declare your SendinBlue Account API Key */	
$API_key = "";

/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                                     DON'T MODIFY ANY CODE BELOW THIS LINE
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////*/

/* Connect to Sendinblue */
SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $API_key);
$api_instance = new SendinBlue\Client\Api\ContactsApi();

/* The SendinBlue API is generated by version 2.2.3 of swagger-codegen, which returns a protected object from the function getContactsFromList by error.
The function below allows us to duplicate this object and make a public copy that we can access.
The error has been addresses in version 2.3 of swagger-codegen, cf. GitHub : https://github.com/swagger-api/swagger-codegen/issues/6777 */ 
function _r($o){
	$obj  = new ReflectionObject($o);
	$r_obj = $obj->getProperty('container');
	$r_obj->setAccessible(TRUE);
	return $r_obj->getValue($o);
}

//////////////////////////////////////////////////////* THE ACTUAL SCRIPT //////////////////////////////////////////////////////*/

/* If a referring email address has been properly specified */
if( isset($_POST['referrer']) ){


	/* We request the information from the contacts in the list */
	$modifiedSince = new \DateTime("2013-10-20T19:20:30+01:00"); // We choose a minimum registration date that is very old
	$limit = 500; // We set the maximum number of users returned at 500
	$offset = 0; // We set the number of the first contact returned at 0
	try { $list_contacts = $api_instance->getContactsFromList($list_id, $modifiedSince, $limit, $offset); } catch (Exception $e) { echo "Error requesting contacts from the list"; die(); }
	
	/* We create an array containing the emails in this list */
	$list_emails = array();
	foreach(_r($list_contacts)["contacts"] as $c => $p){ $list_emails[] = $p["email"]; }

	/* We collect the data from the referrer */
	$referrer_email = $_POST['referrer'];	
	
	/* Verify that the referrer exists in the SendinBlue list */
	if( in_array($referrer_email, $list_emails) ){
		
		/* If the address of an invited contact has been specified... */
		if( isset($_POST['invite']) ){
			/* We collect the data from the invitee */
			$invite_email = $_POST['invite'];

			/* 
			If the address already exists in our SendinBlue list, 
			that means th contact has already been invited,
			We stop the process.
			*/
			if( in_array($invite_email, $list_emails) ){ echo "This contact has already been invited"; die(); }
					
			/* We collect the attributes of the referrer */		
			try {
				$referrer_data = $api_instance->getContactInfo($referrer_email);
			
				/* ...We add the invited contact to the list along with the email address that referred them */
				$invite_create = array( 
					"email" => $invite_email,
					"attributes" => array("REF"=>$referrer_email), 				
					"listIds" => array($list_id)  
				);				
				try { $api_instance->createContact($invite_create); } catch (Exception $e) { echo "Contact was not able to be added to the list : ", $e->getMessage(), PHP_EOL; die(); }
		
				/* We check the number of referrals the referrer has already made... */
				$referrer_data_INV = _r($referrer_data)["attributes"]["INV"];			
				if( !is_numeric($referrer_data_INV) ) { $referrer_data_INV = 0; }
				
				/*... and we increase it by 1 */		
				$referrer_update = array( "attributes" => array("INV"=>$referrer_data_INV+1) );
				try { $api_instance->updateContact($referrer_email, $referrer_update); } catch (Exception $e) { echo "Unable to update the number of referrals for referrer : ", $e->getMessage(), PHP_EOL; die(); }
							
			/*
			If we failed to get the referrer's data from SendinBlue...
			*/		
			} catch (Exception $e) { echo "Error retrieving data of referring contact : ", $e->getMessage(), PHP_EOL; die(); }
		}
		else { echo "No email address for invited contact specified."; die(); }
	}
	else { echo "This referral email address does not exist."; die(); }
}
else { echo "No email address for referrer specified."; die(); }

echo $invite_email." was successfully invited!" 

?>
