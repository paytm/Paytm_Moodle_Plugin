<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Listens for Instant Payment Notification from Paytm
 *
 * This script waits for Payment notification from Paytm,
 * then double checks that data by sending it back to Paytm.
 * If Paytm verifies this then it sets up the enrolment for that
 * user.
 *
 * @package    enrol_paytm
 * @copyright 2010 Eugene Venter
 * @author     Eugene Venter - based on code by others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
define('NO_DEBUG_DISPLAY', true);

require("../../config.php");
require_once("lib.php");
// require_once($CFG->libdir.'/eventslib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->libdir . '/filelib.php');
require_once( "paytm_common.inc" );
require_once( "encdec_paytm.php" );

// Paytm does not like when we return error messages here,
// the custom handler just logs exceptions and stops.
set_exception_handler('enrol_paytm_itn_exception_handler');

/// Keep out casual intruders
if ( empty( $_POST ) or !empty( $_GET ) )
{
    print_error("Sorry, you can not use the script that way.");
}
$tld = 'co.za';
$plugin = enrol_get_plugin('paytm');
define( 'PF_DEBUG', $plugin->get_config( 'paytm_debug' ) );

$pfError = false;
$pfErrMsg = '';
$pfDone = false;
$pfData = array();
$pfParamString = '';

//pflog( 'Paytm ITN call received' );
$data = new stdClass();

foreach ( $_POST as $key => $value)
{
    $data->$key = $value;
}
$custom = explode( '-', $data->MERC_UNQ_REF );
$data->userid           = (int)$custom[0];
$data->courseid         = (int)$custom[1];
$data->instanceid       = (int)$custom[2];
$data->payment_currency = 'INR';
$data->timeupdated      = time();

/// get the user and course records

if (! $user = $DB->get_record( "user", array( "id" => $data->userid ) ) )
{
    $pfError = true;
    $pfErrMsg .= "Not a valid user id \n";
}

if (! $course = $DB->get_record( "course", array( "id" => $data->courseid ) ) )
{
    $pfError = true;
    $pfErrMsg .= "Not a valid course id \n";
}

if (! $context = context_course::instance( $course->id, IGNORE_MISSING ) )
{
    $pfError = true;
    $pfErrMsg .= "Not a valid context id \n";
}

if (! $plugin_instance = $DB->get_record( "enrol", array( "id" => $data->instanceid, "status"=>0 ) ) )
{
    $pfError = true;
    $pfErrMsg .= "Not a valid instance id \n";
}


//// Notify Paytm that information has been received
if( !$pfError && !$pfDone )
{
    header( 'HTTP/1.0 200 OK' );
    flush();
}


//// Get data sent by Paytm
if( !$pfError && !$pfDone )
{
  //  pflog( 'Get posted data' );

    // Posted variables from ITN
    $pfData = pfGetData();
    //$pfData['item_name'] = html_entity_decode( $pfData['item_name'] );
    //$pfData['item_description'] = html_entity_decode( $pfData['item_description'] );
    //pflog( 'Paytm Data: '. print_r( $pfData, true ) );

    if( $pfData === false )
    {
        $pfError = true;
        $pfErrMsg = PF_ERR_BAD_ACCESS;
    }
	
}


//// Check data against internal order
if( !$pfError && !$pfDone )
{
    //pflog( 'Check data against internal order' );

    if ( (float) $plugin_instance->cost <= 0 ) {
        $cost = (float) $plugin->get_config('cost');
    } else {
        $cost = (float) $plugin_instance->cost;
    }

    $cost = format_float( $cost, 2, false );
    // Check order amount
   
}

if( !$pfError && !$pfDone )
{
    if ( $existing = $DB->get_record( "enrol_paytm", array( "pf_payment_id" => $data->TXNID ) ) )
    {   // Make sure this transaction doesn't exist already
        $pfErrMsg .= "Transaction $data->TXNID is being repeated! \n" ;
        $pfError = true;
    }
    if ( $data->payment_currency != $plugin_instance->currency )
    {
        $pfErrMsg .= "Currency does not match course settings, received: " . $data->mc_currency . "\n";
        $pfError = true;
    }

    if ( !$user = $DB->get_record( 'user', array( 'id' => $data->userid ) ) )
    {   // Check that user exists
        $pfErrMsg .= "User $data->userid doesn't exist \n";
        $pfError = true;
    }

    if ( !$course = $DB->get_record( 'course', array( 'id'=> $data->courseid ) ) )
    { // Check that course exists
        $pfErrMsg .= "Course $data->courseid doesn't exist \n";
        $pfError = true;
    }
}


//// Check status and update order
if( !$pfError && !$pfDone )
{
   //pflog( 'Check status and update order' );
	$merchant_key = $plugin->get_config( 'merchant_key' ); 
	$merchant_id = $plugin->get_config( 'merchant_id' ); 	
	// $paytm_mode = $plugin->get_config( 'paytm_mode' ); 	
	$transaction_status_url = $plugin->get_config( 'transaction_status_url' ); 	
	$paramList = $pfData;
	//echo "<pre>"; print_r($paramList); die;
	$paytmChecksum = isset($paramList["CHECKSUMHASH"]) ? $paramList["CHECKSUMHASH"] : "";
	$isValidChecksum = verifychecksum_e($paramList, $merchant_key, $paytmChecksum); 
	
    $transaction_id = $pfData['TXNID'];
	if($isValidChecksum == "1" || $isValidChecksum == "TRUE") 
	{
		switch( $pfData['STATUS'] )
		{
			case 'TXN_SUCCESS':
			   // pflog( '- Complete' );
				
				// Create an array having all required parameters for status query.
				$requestParamList = array("MID" => $merchant_id , "ORDERID" => $paramList['ORDERID']);
				
				$StatusCheckSum = getChecksumFromArray($requestParamList, $merchant_key);
							
				$requestParamList['CHECKSUMHASH'] = $StatusCheckSum;
				
				// Call the PG's getTxnStatus() function for verifying the transaction status.
				/*	19751/17Jan2018	*/
					/*if($paytm_mode=='test') {
						$check_status_url = 'https://pguat.paytm.com/oltp/HANDLER_INTERNAL/getTxnStatus';
					} else {
						$check_status_url = 'https://secure.paytm.in/oltp/HANDLER_INTERNAL/getTxnStatus';
					}*/

					/*if($paytm_mode=='test') {
						$check_status_url = 'https://securegw-stage.paytm.in/merchant-status/getTxnStatus';
					} else {
						$check_status_url = 'https://securegw.paytm.in/merchant-status/getTxnStatus';
					}*/
					$check_status_url = $transaction_status_url;
				/*	19751/17Jan2018 end	*/
				$responseParamList = callNewAPI($check_status_url, $requestParamList);				
				if($responseParamList['STATUS']=='TXN_SUCCESS' && $responseParamList['TXNAMOUNT']==$paramList["TXNAMOUNT"])
				{
					$coursecontext = context_course::instance($course->id, IGNORE_MISSING);


					if ($plugin_instance->enrolperiod) {
						$timestart = time();
						$timeend   = $timestart + $plugin_instance->enrolperiod;
					} else {
						$timestart = 0;
						$timeend   = 0;
					}

					// Enrol user
					$plugin->enrol_user($plugin_instance, $user->id, $plugin_instance->roleid, $timestart, $timeend);

					// Pass $view=true to filter hidden caps if the user cannot see them
					if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
						'', '', '', '', false, true)) {
						$users = sort_by_roleassignment_authority($users, $context);
						$teacher = array_shift($users);
					} else {
						$teacher = false;
					}

					$mailstudents = $plugin->get_config('mailstudents');
					$mailteachers = $plugin->get_config('mailteachers');
					$mailadmins   = $plugin->get_config('mailadmins');
					$shortname = format_string($course->shortname, true, array('context' => $context));


					if (!empty($mailstudents)) {
						$a = new stdClass();
						$a->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
						$a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id";

						$eventdata 					  = new \core\message\message();
            			$eventdata->courseid          = $course->id;
						$eventdata->modulename        = 'moodle';
						$eventdata->component         = 'enrol_paytm';
						$eventdata->name              = 'paytm_enrolment';
						$eventdata->userfrom          = empty($teacher) ? get_admin() : $teacher;
						$eventdata->userto            = $user;
						$eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
						$eventdata->fullmessage       = get_string('welcometocoursetext', '', $a);
						$eventdata->fullmessageformat = FORMAT_PLAIN;
						$eventdata->fullmessagehtml   = '';
						$eventdata->smallmessage      = '';
						message_send($eventdata);

					}

					if (!empty($mailteachers) && !empty($teacher)) {
						$a->course = format_string($course->fullname, true, array('context' => $coursecontext));
						$a->user = fullname($user);

						$eventdata 					  = new \core\message\message();
						$eventdata->modulename        = 'moodle';
						$eventdata->component         = 'enrol_paytm';
						$eventdata->name              = 'paytm_enrolment';
						$eventdata->userfrom          = $user;
						$eventdata->userto            = $teacher;
						$eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
						$eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
						$eventdata->fullmessageformat = FORMAT_PLAIN;
						$eventdata->fullmessagehtml   = '';
						$eventdata->smallmessage      = '';
						message_send($eventdata);
					}

					if ( !empty( $mailadmins ) )
					{
						$a->course = format_string($course->fullname, true, array('context' => $coursecontext));
						$a->user = fullname($user);
						$admins = get_admins();
						foreach ($admins as $admin) {
							$eventdata 					  = new \core\message\message();
							$eventdata->modulename        = 'moodle';
							$eventdata->component         = 'enrol_paytm';
							$eventdata->name              = 'paytm_enrolment';
							$eventdata->userfrom          = $user;
							$eventdata->userto            = $admin;
							$eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
							$eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
							$eventdata->fullmessageformat = FORMAT_PLAIN;
							$eventdata->fullmessagehtml   = '';
							$eventdata->smallmessage      = '';
							message_send($eventdata);
						}
					}
					$fullname = format_string($course->fullname, true, array('context' => $context));
				   // $DB->insert_record("enrol_paytm", $data );
					$destination = "$CFG->wwwroot/course/view.php?id=$course->id";
					redirect($destination, get_string('paymentthanks', '', $fullname));
				}
				else{
					echo "<b>It seems some issue in server to server communication. Kindly connect with administrator.</b>";
					exit;
				}
				break;

			case 'TXN_FAILURE':
				//pflog( '- Failed' );
				$a = new stdClass();
				$a->teacher = get_string('defaultcourseteacher');
				$a->fullname = $fullname;
				$destination = "$CFG->wwwroot/course/view.php?id=$course->id";
				notice(get_string('paymentsorry', '', $a), $destination);
				break;

			case 'OPEN':
				//pflog( '- Pending' );

				$eventdata 					  = new \core\message\message();
				$eventdata->modulename        = 'moodle';
				$eventdata->component         = 'enrol_paytm';
				$eventdata->name              = 'paytm_enrolment';
				$eventdata->userfrom          = get_admin();
				$eventdata->userto            = $user;
				$eventdata->subject           = "Moodle: Paytm payment";
				$eventdata->fullmessage       = "Your Paytm payment is pending.";
				$eventdata->fullmessageformat = FORMAT_PLAIN;
				$eventdata->fullmessagehtml   = '';
				$eventdata->smallmessage      = '';
				message_send($eventdata);

				message_paytm_error_to_admin("Payment pending", $data );

				break;

			default:
				// If unknown status, do nothing (safest course of action)
				break;
		}
	}
	else
	{
		echo "<b>Checksum mismatched.</b>";
		exit;
	}

}
else
{	
    $DB->insert_record( "enrol_paytm", $data, false);	
    message_paytm_error_to_admin( "Received an invalid payment notification!! (Fake payment?)\n" . $pfErrMsg, $data);
    die( 'ERROR encountered, view the logs to debug.' );
}

exit;


//--- HELPER FUNCTIONS --------------------------------------------------------------------------------------


function message_paytm_error_to_admin($subject, $data) {
    echo $subject;
    $admin = get_admin();
    $site = get_site();

    $message = "$site->fullname:  Transaction failed.\n\n$subject\n\n";

    foreach ($data as $key => $value) {
        $message .= "$key => $value\n";
    }

    $eventdata 					  = new \core\message\message();
    $eventdata->modulename        = 'moodle';
    $eventdata->component         = 'enrol_paytm';
    $eventdata->name              = 'paytm_enrolment';
    $eventdata->userfrom          = $admin;
    $eventdata->userto            = $admin;
    $eventdata->subject           = "PAYTM ERROR: ".$subject;
    $eventdata->fullmessage       = $message;
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';
    $eventdata->smallmessage      = '';
   // pflog( 'Error To Admin: ' . print_r( $eventdata, true ) );
    message_send($eventdata);

}

/**
 * Silent exception handler.
 *
 * @param Exception $ex
 * @return void - does not return. Terminates execution!
 */
function enrol_paytm_itn_exception_handler($ex) {
    $info = get_exception_info($ex);

    $logerrmsg = "enrol_paytm ITN exception handler: ".$info->message;
    $logerrmsg .= ' Debug: '.$info->debuginfo."\n".format_backtrace($info->backtrace, true);

    error_log($logerrmsg);

    exit(0);
}
