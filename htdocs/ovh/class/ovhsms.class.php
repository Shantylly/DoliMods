<?php
/* Copyright (C) 2007-2011 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Jean-François FERRY <jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       ovh/class/ovhsms.class.php
 *      \ingroup    ovh
 *      \brief      This file allow to send sms with an OVH account
 *		\author		Jean-François FERRY
 */


require_once(NUSOAP_PATH.'/nusoap.php');

/**
 *      \class      OvhSms
 *      \brief      Use an OVH account to send SMS with Dolibarr
 */
class OvhSms  extends CommonObject
{
    var $db;							//!< To store db handler
    var $error;							//!< To return error code (or message)
    var $errors=array();				//!< To return several error codes (or messages)
    var $element='ovhsms';			//!< Id that identify managed object

    var $id;
    var $account;
    var $fk_soc;
    var $expe;
    var $dest;
    var $message;
    var $validity;
    var $class;
    var $deferred;
    var $priority;


    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$DB      Database handler
     */
    function OvhSms($DB)
    {
        global $conf, $langs;
        $this->db = $DB;

        // Réglages par défaut
        $this->validity = '10';
        $this->class = '2';
        $this->priority = '3';
        $this->deferred = '60';
        // Set the WebService URL
        dol_syslog(get_class($this)."::OvhSms URL=".$conf->global->OVHSMS_SOAPURL);

        if (! empty($conf->global->OVHSMS_SOAPURL))
        {
            require_once(DOL_DOCUMENT_ROOT.'/lib/functions2.lib.php');
            $params=getSoapParams();
            ini_set('default_socket_timeout', $params['response_timeout']);

            //if ($params['proxy_use']) print $langs->trans("TryToUseProxy").': '.$params['proxy_host'].':'.$params['proxy_port'].($params['proxy_login']?(' - '.$params['proxy_login'].':'.$params['proxy_password']):'').'<br>';
            //print 'URL: '.$WS_DOL_URL.'<br>';
            //print $langs->trans("ConnectionTimeout").': '.$params['connection_timeout'].'<br>';
            //print $langs->trans("ResponseTimeout").': '.$params['response_timeout'].'<br>';

    		$err=error_reporting();
    		error_reporting(E_ALL);     // Enable all errors

            try {
                $this->soap = new SoapClient($conf->global->OVHSMS_SOAPURL,$params);
                // https://www.ovh.com/soapi/soapi-re-1.8.wsdl

                $language = "en";
                $multisession = false;

                $this->session = $this->soap->login($conf->global->OVHSMS_NICK, $conf->global->OVHSMS_PASS,$language,$multisession);
                //if ($this->session) print '<div class="ok">'.$langs->trans("OvhSmsLoginSuccessFull").'</div><br>';
                //else print '<div class="error">Error login did not return a session id</div><br>';

                // On mémorise le compe sms associé
                $this->account = empty($conf->global->OVHSMS_ACCOUNT)?'ErrorNotDefined':$conf->global->OVHSMS_ACCOUNT;

                return 1;

            }
            catch(SoapFault $se) {
                error_reporting($err);     // Restore default errors
                dol_syslog(get_class($this).'::SoapFault: '.$se);
                //var_dump('eeeeeeee');exit;
                return 0;
            }
            catch (Exception $ex) {
                error_reporting($err);     // Restore default errors
                dol_syslog(get_class($this).'::SoapFault: '.$ex);
                //var_dump('eeeeeeee');exit;
                return 0;
            }
            catch (Error $e) {
                error_reporting($err);     // Restore default errors
                dol_syslog(get_class($this).'::SoapFault: '.$e);
                //var_dump('eeeeeeee');exit;
                return 0;
            }
            error_reporting($err);     // Restore default errors

            return 1;
        }
        else return 0;
    }

    /**
     * Logout
     */
    function logout()
    {
        $this->soap->logout($this->session);
        return 1;
    }


    /*
     * Envoi d'un sms
     */
    function SmsSend() {
        //telephonySmsSend
        try
        {
            $resultsend = $this->soap->telephonySmsSend($this->session, $this->account, $this->expe, $this->dest, $this->message, $this->validity, $this->class, $this->deferred, $this->priority);
            return $resultsend;
        }
        catch(SoapFault $fault)
        {
            $errmsg="Error ".$fault->faultstring;
            dol_syslog(get_class($this)."::SmsSend ".$errmsg, LOG_ERR);

            $this->error.=($this->error?', '.$errmsg:$errmsg);
        }
        return -1;
    }


    function printListAccount() {
        $resultaccount = $this->getSmsListAccount();
        print '<select name="ovh_account" id="ovh_account">';
        foreach ($resultaccount as $accountlisted) {
            print '<option value="'.$accountlisted.'">'.$accountlisted.'</option>';
        }
        print '</select>';
    }


    function getSmsListAccount()
    {
        //telephonySmsAccountList
        return $this->soap->telephonySmsAccountList($this->session);
    }

    function CreditLeft()
    {
        return $this->soap->telephonySmsCreditLeft($this->session, $this->account);
    }

    function SmsHistory()
    {
        return $this->soap->telephonySmsHistory($this->session, $this->account, "");
    }

    function SmsSenderList()
    {
        try {
            $telephonySmsSenderList = $this->soap->telephonySmsSenderList($this->session, $this->account);
            return $telephonySmsSenderList;
        }
        catch(SoapFault $fault) {
            $errmsg="Error ".$fault->faultstring;
            dol_syslog(get_class($this)."::SmsSenderList ".$errmsg, LOG_ERR);
            $this->error.=($this->error?', '.$errmsg:$errmsg);
            return -1;
        }
        return -1;
    }

}
?>