<?php

/**
 * class.autenticate.php
 * ---------------------
 * autentykacja użytkowników poprzez LDAP oraz sprawdzenie ról jakie mają na bazie.
 *
 * @author Karol Preiskorn
 *
 * @version 1.0 2010-05-06 Create item
 * @version 2.0 2010-08-17 Wykorzystanie klasy do autentykacji tmo_reporting
 *
 * @todo sprawdzanie uprawnień do plików
 */
//require_once 'PHP/Compat.php';
//PHP_Compat::loadFunction('file_get_contents');

require_once "class.db.php";

class autenticate extends db {

    private $domain;
    private $userldap;
    private $roles;
    private $corponet;
    private $granted = FALSE;

    public function __construct($db = 'inactprd.inact_1') {
        parent::__construct($db);
        if (!isset($_SERVER['REMOTE_USER']))
            $_SERVER['REMOTE_USER'] = '';
        $cred = explode('\\', $_SERVER['REMOTE_USER']);
        if (count($cred) == 1)
            array_unshift($cred, "(no domain info - perhaps SSPIOmitDomain is On)");
        list($this->domain, $this->userldap) = $cred;

        if (isset($this->domain) and isset($this->userldap))
            $this->corponet = TRUE;
        else
            $this->corponet = FALSE;

        if ($this->corponet)
            print "Autenticate in " . strtoupper($this->domain) . "/" . strtoupper($this->userldap) . ". ";
    }

    public function set_domian($p_domain) {
        $this->domain = $p_domain;
    }

    public function set_userldap($p_userldap) {
        $this->userldap = $p_user_ldap;
    }

    public function get_domian() {
        return $this->domain;
    }

    public function get_granted() {
        return $this->granted;
    }

    public function get_userldap() {
        return $this->userldap;
    }

    public function check_role($p_roles) {
        /**
         * @var sprawdznie czy parametr jest wypełniony.
         */
        if (!isset($p_roles)) {
            throw new Exception('<b>Warning: Brak pdania roli</b><br>');
        }


        $this->set_sql("select count(*) count "
                . " from inact.v_all_role_privs  "
                . " where upper(grantee) = upper('" . $this->userldap . "') "
                . " and GRANTED_ROLE IN (" . $p_roles . ")");


        $this->execute_sql();
        $this->fetch_array();

        $l_rows = $this->get_rows();

        if ($l_rows['COUNT'] > 0) {
            //echo "User: " . $this->userldap . " ROLES: (" . $p_roles . "), ";
            $this->granted = TRUE;
            return TRUE;
        } else {
//echo "User: " . $this->domain . "/" . $this->userldap . " have NOT required roles (" . $p_roles . ") on " . $this->get_db() . ": " . $p_roles . ", coprponet " . $this->corponet . ", roles: " . $l_rows['COUNT'];
//echo "<pre>";
//print $this->get_sql();
//PRINT "<BR>";
//print_r($this->get_rows());
//echo "</pre>";
            $this->granted = FALSE;

            return FALSE;
        }
    }

}

if (TRUE)
    try {
        $autenticate = new autenticate();
        $autenticate->check_role("'INACT_DATA_VIEWER'");
    } catch (exception $e) {
        die('Error: ' . $e->getmessage());
    }
?>