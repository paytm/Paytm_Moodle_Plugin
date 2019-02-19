<?php
// This file is part of Moodle - http://moodle.org/
//
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
 * Paypal enrolments plugin settings and presets.
 *
 * @package    enrol_paytm
 * @copyright  2010 Eugene Venter
 * @author     Eugene Venter - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    //--- settings ------------------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_paytm_settings', '', get_string('pluginname_desc', 'enrol_paytm')));

    $settings->add(new admin_setting_configtext('enrol_paytm/merchant_id', get_string( 'merchant_id', 'enrol_paytm'), get_string('merchant_id_desc', 'enrol_paytm'), '', PARAM_ALPHANUM));

    // $settings->add(new admin_setting_configtext('enrol_paytm/merchant_key', get_string( 'merchant_key', 'enrol_paytm'), get_string('merchant_key_desc', 'enrol_paytm'), '', '/^[a-zA-Z0-9-\(\)@.,_:#\/ ]*$/'));
    $settings->add(new admin_setting_configtext('enrol_paytm/merchant_key', get_string( 'merchant_key', 'enrol_paytm'), get_string('merchant_key_desc', 'enrol_paytm'), '', PARAM_RAW_TRIMMED));
    
	$settings->add(new admin_setting_configtext('enrol_paytm/merchant_website', get_string( 'merchant_website', 'enrol_paytm'), get_string('merchant_website_desc', 'enrol_paytm'), '', PARAM_ALPHANUM));
	
	$settings->add(new admin_setting_configtext('enrol_paytm/merchant_industrytype', get_string( 'merchant_industrytype', 'enrol_paytm'), get_string('merchant_industrytype_desc', 'enrol_paytm'), '', PARAM_ALPHANUM));
	
	$settings->add(new admin_setting_configtext('enrol_paytm/merchant_channelid', get_string( 'merchant_channelid', 'enrol_paytm'), get_string('merchant_channelid_desc', 'enrol_paytm'), '', PARAM_ALPHANUM));
	
    //$settings->add(new admin_setting_configtext('enrol_paytm/merchant_passphrase', get_string('merchant_passphrase', 'enrol_payfast'), get_string('merchant_passphrase_desc', 'enrol_payfast'), '', '/^[a-zA-Z0-9-\(\)@.,_:#\/ ]*$/'));

    /*$options = array(
        'test'  => get_string('paytm_test', 'enrol_paytm'),
        'live'  => get_string('paytm_live', 'enrol_paytm')
    );
    $settings->add(new admin_setting_configselect('enrol_paytm/paytm_mode', get_string('paytm_mode', 'enrol_paytm'), get_string('paytm_mode_desc', 'enrol_paytm'), 'test', $options));*/

    $settings->add(new admin_setting_configtext('enrol_paytm/transaction_url', get_string( 'transaction_url', 'enrol_paytm'), get_string('transaction_url_desc', 'enrol_paytm'), '', 0));
    $settings->add(new admin_setting_configtext('enrol_paytm/transaction_status_url', get_string( 'transaction_status_url', 'enrol_paytm'), get_string('transaction_status_url_desc', 'enrol_paytm'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_paytm/paytm_callback', get_string('paytm_callback', 'enrol_paytm'),  get_string('paytm_callback_desc', 'enrol_paytm'), 1));

    $settings->add(new admin_setting_configcheckbox('enrol_paytm/mailstudents', get_string('mailstudents', 'enrol_paytm'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_paytm/mailteachers', get_string('mailteachers', 'enrol_paytm'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_paytm/mailadmins', get_string('mailadmins', 'enrol_paytm'), '', 0));

    // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
    //       it describes what should happen when users are not supposed to be enrolled any more.
    $options = array(
        ENROL_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'enrol'),
        ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'),
        ENROL_EXT_REMOVED_UNENROL        => get_string('extremovedunenrol', 'enrol'),
    );
    $settings->add(new admin_setting_configselect('enrol_paytm/expiredaction', get_string('expiredaction', 'enrol_paytm'), get_string('expiredaction_help', 'enrol_paytm'), ENROL_EXT_REMOVED_SUSPENDNOROLES, $options));

    //--- enrol instance defaults ----------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_paytm_defaults',
        get_string('enrolinstancedefaults', 'admin'), get_string('enrolinstancedefaults_desc', 'admin')));

    $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                     ENROL_INSTANCE_DISABLED => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_paytm/status',
        get_string('status', 'enrol_paytm'), get_string('status_desc', 'enrol_paytm'), ENROL_INSTANCE_DISABLED, $options));

    $settings->add(new admin_setting_configtext('enrol_paytm/cost', get_string('cost', 'enrol_paytm'), '', 0, PARAM_FLOAT, 4));

    $paytmcurrencies = enrol_get_plugin('paytm')->get_currencies();
    $settings->add(new admin_setting_configselect('enrol_paytm/currency', get_string('currency', 'enrol_paytm'), '', 'INR', $paytmcurrencies));

    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_paytm/roleid',
            get_string('defaultrole', 'enrol_paytm'), get_string('defaultrole_desc', 'enrol_paytm'), $student->id, $options));
    }

    $settings->add(new admin_setting_configduration('enrol_paytm/enrolperiod',
        get_string('enrolperiod', 'enrol_paytm'), get_string('enrolperiod_desc', 'enrol_paytm'), 0));
}
