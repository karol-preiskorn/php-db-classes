<?php

/*
 * TMPL All Rights Reserved 2014 (c)
 */

/**
 * Authoryzacja dostêpu do apliacji webowej
 *
 * @author KPreiskorn
 */
class auth extends db {

    public function hasRoles($p_usr, $p_role, $p_conn) {
        $sql = "select count(*) cnt
            from
               inact.v_all_role_privs v, inact.users u
            where
               u.DOMAIN_USERNAME = '" . $p_usr . "'
               AND v.GRANTEE = u.USERNAME
               AND v.GRANTED_ROLE IN (" . $p_role . ")";

        //debug('SQL', $sql);

        $qry = oci_parse($p_conn, $sql);
        oci_execute($qry);
        oci_fetch($qry);

        //debug('Roles counter', oci_result($qry, 'CNT'));

        if (oci_result($qry, 'CNT') > 0) {
            return true;
        }
        return false;
    }

    public function countRoles($p_usr, $p_conn) {
        $sql = "select count(*) cnt
            from
               inact.v_all_role_privs v,
               inact.users u
            where
               u.DOMAIN_USERNAME = '" . $p_usr . "'
               AND v.GRANTEE = u.USERNAME";

        //debug('', $sql);

        $qry = oci_parse($p_conn, $sql);
        oci_execute($qry);
        oci_fetch($qry);

        //debug("Test count:", oci_result($qry, 'CNT'));

        if (oci_result($qry, 'CNT') > 0) {
            return true;
        }
        return false;
    }

    public function listRoles($p_usr, $p_conn) {
        $ret = 'Role InAcT: ';
        $sql = "select *
            from
               inact.v_all_role_privs v,
               inact.users u
            where
               u.DOMAIN_USERNAME = '" . $p_usr . "'
               AND v.GRANTEE = u.USERNAME";

        //debug('', $sql);

        $stid = oci_parse($p_conn, $sql);
        oci_execute($stid);
        while (($row = oci_fetch_array($stid, OCI_BOTH))) {
            $ret = $ret . $row[1] . ", ";
        }
        oci_free_statement($stid);
        return $ret;
    }

}
