<?php

/**
 * Listens for Instant Payment Notification from Paytm
 *
 * This script waits for Payment notification from Paytm,
 * then double checks that data by sending it back to Paytm.
 * If Paytm verifies this then it sets up the enrolment for that
 * user.
 *
 * @package    enrol_paytm
 * @copyright 2020 Abhishek Awasthi
 * @author     Abhishek Awasthi - based on code by others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
define('NO_DEBUG_DISPLAY', false);

require("../../config.php");
require_once("lib.php");
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/filelib.php');
require_once( "paytm_common.inc" );
require_once( "PaytmChecksum.php" );
require_once("PaytmHelper.php");

// Paytm does not like when we return error messages here,
// the custom handler just logs exceptions and stops.
set_exception_handler('enrol_paytm_itn_exception_handler');

/// Keep out casual intruders
if (empty($_POST) or!empty($_GET)) {
    echo IMPROPER_MERCHANT_CONFIG;

}

$tld = 'co.za';
$plugin = enrol_get_plugin('paytm');
define('PF_DEBUG', $plugin->get_config('paytm_debug'));

$pfError = false;
$pfErrMsg = '';
$pfDone = false;
$pfData = array();
$pfParamString = '';

//pflog( 'Paytm ITN call received' );
$data = new stdClass();

foreach ($_POST as $key => $value) {
    $data->$key = $value;
}
//$custom = explode( '-', $data->MERC_UNQ_REF );
$custom = explode('-', $_POST['MERC_UNQ_REF']);
$data->userid = (int) $custom[0];
$data->courseid = (int) $custom[1];
$data->instanceid = (int) $custom[2];
$data->payment_currency = 'INR';
$data->timeupdated = time();


/// get the user and course records
if (!$user = $DB->get_record("user", array("id" => $data->userid))) {
    $pfError = true;
    $pfErrMsg .=PaytmConstants::NOT_VALID_USER_ID;
}
//print_r($_POST);  exit;
if (!$course = $DB->get_record("course", array("id" => $data->courseid))) {
    $pfError = true;
    $pfErrMsg .=PaytmConstants::NOT_VALID_COURSE_ID;
}

if (!$context = context_course::instance($course->id, IGNORE_MISSING)) {
    $pfError = true;
    $pfErrMsg .=PaytmConstants::NOT_VALID_CONTEXT_ID;
}

if (!$plugin_instance = $DB->get_record("enrol", array("id" => $data->instanceid, "status" => 0))) {
    $pfError = true;
    $pfErrMsg .=PaytmConstants::NOT_VALID_INSTANCE_ID;
}

//// Notify Paytm that information has been received
if (!$pfError && !$pfDone) {
    header('HTTP/1.0 200 OK');
    flush();
}

//// Get data sent by Paytm
if (!$pfError && !$pfDone) {
    // Posted variables from ITN
    $pfData = $_POST;
    if ($pfData === false) {
        $pfError = true;
        $pfErrMsg = PF_ERR_BAD_ACCESS;
    }
}

//// Check data against internal order
if (!$pfError && !$pfDone) {
    if ((float) $plugin_instance->cost <= 0) {
        $cost = (float) $plugin->get_config('cost');
    } else {
        $cost = (float) $plugin_instance->cost;
    }
    $cost = format_float($cost, 2, false);
    // Check order amount
}




if (!$pfError && !$pfDone) {
    if ($data->payment_currency != $plugin_instance->currency) {
        $pfErrMsg .= PaytmConstants::CURRENCY_MIS_MATCH. $data->mc_currency . "\n";
        $pfError = true;
    }
    if (!$user = $DB->get_record('user', array('id' => $data->userid))) {   // Check that user exists
        $pfErrMsg .= "User $data->userid doesn't exist \n";
        $pfError = true;
    }
    if (!$course = $DB->get_record('course', array('id' => $data->courseid))) { // Check that course exists
        $pfErrMsg .= "Course $data->courseid doesn't exist \n";
        $pfError = true;
    }
}



//// Check status and update order
if (!$pfError && !$pfDone) {
  
    $merchant_key = $plugin->get_config('merchant_key');
    $merchant_id = $plugin->get_config('merchant_id');
    $transaction_status_url = $plugin->get_config('transaction_status_url');
    $paramList = $_POST;
    $paytmChecksum = isset($paramList["CHECKSUMHASH"]) ? $paramList["CHECKSUMHASH"] : "";

    $v = PaytmChecksum::verifySignature($_POST, $merchant_key, $paytmChecksum);
    $transaction_id = $pfData['TXNID'];
    if ($v == '1' || $v == 'true') {
        
        switch ($pfData['STATUS']) {
            case 'TXN_SUCCESS':
                // Create an array having all required parameters for status query.
                $requestParamList = array("MID" => $merchant_id, "ORDERID" => $paramList['ORDERID']);
                $check_status_url = $transaction_status_url;
                /* initialize an array */
                $paytmParamsStatus = array();
                /* body parameters */
                $paytmParamsStatus["body"] = array(
                    /* Find your MID in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys */
                    "mid" => $merchant_id,
                    /* Enter your order id which needs to be check status for */
                    "orderId" => $paramList['ORDERID'],
                );

                $checksumStatus = PaytmChecksum::generateSignature(json_encode($paytmParamsStatus["body"], JSON_UNESCAPED_SLASHES), $merchant_key);
                /* head parameters */
                $paytmParamsStatus["head"] = array(
                    "signature" => $checksumStatus
                );
                if ($plugin->get_config('paytm_mode') == 'live') {
                    $PAYTM_ENV = 1;
                } else {
                     $PAYTM_ENV = 0;
                }
                $responseStatusArray = PaytmHelper::executecUrl(PaytmHelper::getPaytmURL(PaytmConstants::ORDER_STATUS_URL,$PAYTM_ENV), $paytmParamsStatus);
                		
                if ($responseStatusArray['body']['resultInfo']['resultStatus'] == 'TXN_SUCCESS' && $responseStatusArray['body']['txnAmount'] == $pfData["TXNAMOUNT"]) {
                    $coursecontext = context_course::instance($course->id, IGNORE_MISSING);
                    if ($plugin_instance->enrolperiod) {
                        $timestart = time();
                        $timeend = $timestart + $plugin_instance->enrolperiod;
                    } else {
                        $timestart = 0;
                        $timeend = 0;
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
                    $mailadmins = $plugin->get_config('mailadmins');
                    $shortname = format_string($course->shortname, true, array('context' => $context));

                    if (!empty($mailstudents)) {
                        $a = new stdClass();
                        $a->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
                        $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id";
                        $eventdata = new \core\message\message();
                        $eventdata->courseid = $course->id;
                        $eventdata->modulename = 'moodle';
                        $eventdata->component = 'enrol_paytm';
                        $eventdata->name = 'paytm_enrolment';
                        $eventdata->userfrom = empty($teacher) ? get_admin() : $teacher;
                        $eventdata->userto = $user;
                        $eventdata->subject = get_string("enrolmentnew", 'enrol', $shortname);
                        $eventdata->fullmessage = get_string('welcometocoursetext', '', $a);
                        $eventdata->fullmessageformat = FORMAT_PLAIN;
                        $eventdata->fullmessagehtml = '';
                        $eventdata->smallmessage = '';
                        message_send($eventdata);
                    }
                    if (!empty($mailteachers) && !empty($teacher)) {
                        $a = new stdClass();
                        $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
                        $a->user = fullname($user);
                        $eventdata = new \core\message\message();
                        $eventdata->modulename = 'moodle';
                        $eventdata->component = 'enrol_paytm';
                        $eventdata->name = 'paytm_enrolment';
                        $eventdata->userfrom = $user;
                        $eventdata->userto = $teacher;
                        $eventdata->subject = get_string("enrolmentnew", 'enrol', $shortname);
                        $eventdata->fullmessage = get_string('enrolmentnewuser', 'enrol', $a);
                        $eventdata->fullmessageformat = FORMAT_PLAIN;
                        $eventdata->fullmessagehtml = '';
                        $eventdata->smallmessage = '';
                        message_send($eventdata);
                    }
                    
                    if (!empty($mailadmins)) {
                        $a = new stdClass();
                        $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
                        $a->user = fullname($user);
                        $admins = get_admins();
                        foreach ($admins as $admin) {
                            $eventdata = new \core\message\message();
                            $eventdata->modulename = 'moodle';
                            $eventdata->component = 'enrol_paytm';
                            $eventdata->name = 'paytm_enrolment';
                            $eventdata->userfrom = $user;
                            $eventdata->userto = $admin;
                            $eventdata->subject = get_string("enrolmentnew", 'enrol', $shortname);
                            $eventdata->fullmessage = get_string('enrolmentnewuser', 'enrol', $a);
                            $eventdata->fullmessageformat = FORMAT_PLAIN;
                            $eventdata->fullmessagehtml = '';
                            $eventdata->smallmessage = '';
                            message_send($eventdata);
                        }
                    }

                   
                    $fullname = format_string($course->fullname, true, array('context' => $context));
                    $destination = "$CFG->wwwroot/course/view.php?id=$course->id";
                    redirect($destination, get_string('paymentthanks', '', $fullname));
                } else {
                    echo "<b>".PaytmConstants::SERVER_COMM_ERROR."</b>";

                    exit;
                }
                break;
            case 'TXN_FAILURE':
                $a = new stdClass();
                $a->teacher = get_string('defaultcourseteacher');
                $a->fullname = $fullname;
                $destination = "$CFG->wwwroot/course/view.php?id=$course->id";
                notice(get_string('paymentsorry', '', $a), $destination);
                break;
            case 'OPEN':
                $eventdata = new \core\message\message();
                $eventdata->modulename = 'moodle';
                $eventdata->component = 'enrol_paytm';
                $eventdata->name = 'paytm_enrolment';
                $eventdata->userfrom = get_admin();
                $eventdata->userto = $user;
                $eventdata->subject = "Moodle: Paytm payment";
                $eventdata->fullmessage = "Your Paytm payment is pending.";
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml = '';
                $eventdata->smallmessage = '';
                message_send($eventdata);
                message_paytm_error_to_admin("Payment pending", $data);
                break;
            default:
                // If unknown status, do nothing (safest course of action)
                break;
        }
    } else {
        echo "<b>Checksum mismatched.</b>";
        exit;
    }
} else {
    $DB->insert_record("enrol_paytm", $data, false);
    message_paytm_error_to_admin("Received an invalid payment notification!! (Fake payment?)\n" . $pfErrMsg, $data);
    die('ERROR encountered, view the logs to debug.');
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
    $eventdata = new \core\message\message();
    $eventdata->modulename = 'moodle';
    $eventdata->component = 'enrol_paytm';
    $eventdata->name = 'paytm_enrolment';
    $eventdata->userfrom = $admin;
    $eventdata->userto = $admin;
    $eventdata->subject = "PAYTM ERROR: " . $subject;
    $eventdata->fullmessage = $message;
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml = '';
    $eventdata->smallmessage = '';
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
    $logerrmsg = "enrol_paytm ITN exception handler: " . $info->message;
    $logerrmsg .= ' Debug: ' . $info->debuginfo . "\n" . format_backtrace($info->backtrace, true);
    error_log($logerrmsg);
    exit(0);
}
