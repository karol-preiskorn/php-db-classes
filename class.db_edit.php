<?php

//require_once ('funkcje/auth.php');
//require_once ('funkcje/functions.php');
require_once ('class.db.php');

//require_once ('header.php');
/**
 *
 * Klasa opracje edycji na bazie danych Oracle 10.2
 * 2014-08-27 init
 *
 */
class db_edit extends db {

    public $name = __CLASS__;
    public $pk;
    public $operations;
    public $rights;
    public $no_rows;
    public $operations_run;
    public $action;

    public function get_pk() {
        return $this->pk;
    }

    /*
     * set and check exist promary key of table
     */

    public function set_pk($l_pk) {
//if (in_array($l_pk, $this->get_name_fields())) {
        $this->pk = $l_pk;
// } else {
//     throw new Exception('Nie prwid³owy klucz primary key.');
//}
    }

    public function get_operations() {
        return $this->operations;
    }

    public function set_operations($l_operations) {
        $this->operations = $l_operations;
    }

    public function set_action($l_action) {
        $this->action = $l_action;
    }

    public function get_action() {
        return $this->action;
    }

    public function get_rights() {
        return $this->rights;
    }

    public function set_rights($l_rights) {
        $this->rights = $l_rights;
    }

    function __construct() {
        $l_args = func_get_args();
        call_user_func_array(array($this, 'parent::__construct'), $l_args);
        //print "In " . $this->name . " constructor\n";
        //print "In " . self::pk . " constructor\n";
        //print_pre($l_args, 'l_args');
        //print_pre($this, 'this');
        //print "arguments " . func_num_args();

        if (func_num_args() >= 4) {
            $this->set_pk(func_get_arg(3));
        }
        if (func_num_args() >= 5) {
            $this->set_operations(func_get_arg(4));
        }
        if (func_num_args() == 6) {
            $this->set_rights(func_get_arg(5));
        }
    }

    /*
     * Print PK in EDIT url
     */

    public function print_pk_in_edit_url($p_i) {
        $l_return = '';
        $l_rows = $this->get_rows();
        $l_pk = $this->get_pk();
        $l_return .= "OPERATION=" . $this->operations_run;
        for ($i = 0; $i <= count($this->pk) - 1; $i++) {
            if ($i >= 0) {
                $l_return .= "&";
            }
//print_pre($l_rows, 'variable: l_rows');
            $l_return .= "P_" . $this->pk[$i] . "=" . $l_rows[$p_i][$this->pk[$i]];
        }
        return $l_return;
    }

    public function edit_table() {

        $l_rows = $this->get_rows();
        $l_columns = $this->get_columns();
        $l_pk = $this->get_pk();
        //print_pre($l_columns['name'], 'variable: l_columns[name]');
        //print_pre($this->get_fields_num(), 'field_num');
        //print_pre($l_rows, 'variable: l_rows');
        //print_pre($this->pk, 'variable: l_pk');
        //print_pre($l_columns, 'variable: l_columns');
        //print $this->print_pk_in_edit_url(1);
        print "<h1 id=" . $this->get_sql_name() . ">" . $this->get_title() . " (Last: " . $this->get_rows_num() . " rows)</h1>\n";
        print "<p>" . $this->get_description() . "</p>\n";
        // print " (action " . $this->get_action() . ")</p>";
        $this->add_select_where();
        print "<table cellspacing=0 cellpadding=1 onMouseOut = 'javascript:highlightTableRowVersionA(0);'>\n";
        print "<tr onclick='miniTable(this);'>\n";
        print "<th><a href='#' TITLE='Row number'>#</a></th>\n";
        for ($i = 1; $i <= $this->get_fields_num(); $i++) {
            if (!strstr($l_columns['name'][$i], 'ROWIDTOCHAR(ROWID)')) {
                print "<th><a href='#' TITLE='" . $l_columns['type'][$i] . " (" . $l_columns['size'][$i] . ")'>" . $l_columns['name'][$i] . "</a></th>\n";
            } else {
                print "<th></th>\n";
            }
        }
        print "<th>Operations</th>\n";
        print "</tr>\n";

        if ($this->get_rows_num() > 0) {
            for ($i = 0; $i <= $this->get_rows_num() - 1; $i++) {
                if ((bool) ($i & 1)) {
                    $l_bg = " bgcolor='#FFF7DC' ";
                } else {
                    $l_bg = '';
                }
                print "<tr " . $l_bg . " onMouseOver=\"javascript:highlightTableRowVersionA(this, '#F9EFFF');\">\n";

                /*
                 * druk kolumn do edycji
                 */
                if (in_array('EDIT', $this->operations) or in_array('DELETE', $this->operations)) {
                    print "<form name='form_edit_" . $this->get_sql_name() . "_" . $i . "' action='" . $this->get_action() . $this->get_sql_name() . "_" . $i . "' method='POST'>\n";
                    print "<td>" . ($i + 1) . "</td>\n";
                    for ($j = 1; $j <= $this->get_fields_num(); $j++) {
                        //$l_rows[$i][($l_columns['name'][$j])];
                        //print_pre($l_columns['name'][$j], 'l_columns[name][j]');
                        //print_pre($l_fields[$j - 1], 'l_fields[j-1]');
                        //print_pre($l_columns['size'][$j], 'l_columns[size][j]');

                        if (!in_array($l_columns['name'][$j], $l_pk) and ( trim($l_columns['name'][$j]) <> 'ROWIDTOCHAR(ROWID)')) {

                            if ($l_columns['name'][$j] == 'S_NAME') {
                                $c1 = connectToDB('INACTTST');
                                $sqlQry = "select short_name FROM INACT.REC_SUPPLIERS order by short_name";
                                $qry = oci_parse($c1, $sqlQry);
                                oci_execute($qry);
                                print "<td><select name='S_NAME'>\n";
                                while (oci_fetch($qry)) {
                                    print "<option ";
                                    if ($l_rows[$i][$l_columns['name'][$j]] == oci_result($qry, 1))
                                        print "SELECTED";
                                    print " value='" . oci_result($qry, 1) . "'>" . oci_result($qry, 1) . "</option>\n";
                                }
                                print "</select>\n";
                                print "</td>\n";
                            } else {
                                print "<td><input type='text' name='" . $l_columns['name'][$j] . "' value='" . $l_rows[$i][$l_columns['name'][$j]] . "' size='" . $l_columns['size'][$j] . "' maxlength='" . $l_columns['size'][$j] . "'  /></td>\n";
                            }
                        } else {
                            print "<td><input type='hidden' readonly style='background: #eeeeee' name='" . $l_columns['name'][$j] . "' value=" . $l_rows[$i][($l_columns['name'][$j])] . " size='" . ($l_columns['size'][$j] + 6) . "' maxlength='" . $l_columns['size'][$j] . "'  /></td>\n";
                        }
                    }
                    print "<td>\n";
                    print "<input type='hidden' name='ROW' value='" . $i . "'>\n";
                    if (in_array('EDIT', $this->operations)) {
                        print '<input type = "submit" value = "EDIT" name = "SUBMIT" />' . "\n";
                    }
                    if (in_array('DELETE', $this->operations)) {
                        print '<input type = "submit" value = "DELETE" name = "SUBMIT" />' . "\n";
                    }
                    print "</td>\n";
                    print '</form>' . "\n";
                }

                if (in_array('SEND', $this->operations)) {
                    $this->operations_run = 'SEND';
                    print "<td align = 'right'>"
                            . "<a class = 'css_btn_class' "
                            . "href = 'http://inactsrv/inact/TIBCO/sap_send_clear_m.php?" . $this->print_pk_in_edit_url($i) . "'>SEND to SAP</a>"
                            . "</td>\n";
                }
                print "</tr>\n";
            }
        } else {
            print "<h4>Table is empty</h4>\n";
        }

        if (in_array('INSERT', $this->operations)) {
            print "<tr>\n";
            print "<td></td>\n";
            print "<td></td>\n";
            $this->operations_run = 'INSERT';
            //print_pre($l_columns['name'], 'variable: l_columns[name]');
            //print_pre($l_pk, 'variable: get_pk');
            //print_pre(trim($l_columns['name'][1]) == 'ROWIDTOCHAR(ROWID)', 'PK');
            print "<form name='form_insert_" . $this->get_sql_name() . "' action='" . $this->get_action() . $this->get_sql_name() . "' method='POST'>" . "\n";
            for ($j = 1; $j <= $this->get_fields_num(); $j++) {
                if (!in_array($l_columns['name'][$j], $l_pk) and ( trim($l_columns['name'][$j]) <> 'ROWIDTOCHAR(ROWID)')) {
                    if ($l_columns['name'][$j] == 'S_NAME') {
                        $c1 = connectToDB('INACTTST');
                        $sqlQry = "select short_name FROM INACT.REC_SUPPLIERS order by short_name";
                        $qry = oci_parse($c1, $sqlQry);
                        oci_execute($qry);
                        print "<td><select name='S_NAME'>\n";
                        while (oci_fetch($qry)) {
                            print "<option ";
                            //if ($l_rows[$i][$l_columns['name'][$j]] == oci_result($qry, 1))
                            //print "SELECTED";
                            print " value='" . oci_result($qry, 1) . "'>" . oci_result($qry, 1) . "</option>\n";
                        }
                        print "</select>\n";
                        print "</td>\n";
                    } else {
                        print "<td><input type='text' name='" . $l_columns['name'][$j] . "' value='' size='" . $l_columns['size'][$j] . "' maxlength='" . $l_columns['size'][$j] . "' required /></td>" . "\n";
                    }
                } else {
                    if (trim($l_columns['name'][$j]) <> 'ROWIDTOCHAR(ROWID)') {
                        print "<td><input type='text' readonly style='background: #abcdef' name='" . $l_columns['name'][$j] . "' value='' size='" . $l_columns['size'][$j] . "' maxlength='" . $l_columns['size'][$j] . "'  /></td>" . "\n";
                    }
                }
            }
            print "";
            print "<td>\n" . "<input type = 'submit' value = 'INSERT' name = 'SUBMIT' />" . "</td>\n";
            print "</form>\n";
            print "</tr>\n";
        }

        print "</table>\n";
    }

    public function get_info() {
        parent::get_info();
        $l_ret = "<table><tr><td>Primary Key</td><td>";
        $l_ret .= "<pre>" . print_r($this->pk, TRUE) . "</pre>";      // liczba wierszy
        $l_ret .= "</td></tr>";
        $l_ret .= "<tr><td>Operations</td><td>";
        $l_ret .= "<pre>" . print_r($this->operations, TRUE) . "</pre>";      // liczba wierszy
        $l_ret .= "</td></tr>";
        $l_ret .= "</table>";
        print $l_ret;
    }

}

IF (FALSE) {
//
// Dismantle Orders
// 2014-08-18 add button to update
//
    if (FALSE) {
        $cl_do_err = new db_edit('inacttst.inact', 'do_errors', 'DISMANTLE ORDERS errors INACTTST', array('M_ID', 'ADB_SEQUENCE'), array('SEND', 'EDIT', 'INSERT'));
        $cl_do_err->set_sql("SELECT
                            ROWIDTOCHAR(MOE.ROWID),
                            MOE.M_ID,
                            MOE.MO_ID,
                            MO.DESCRIPTION,
                            to_char(MOE.OPER_DATE,'YYYY-MM-DD HH24:MI:SS') as OPER_DATE,
                            MOE.REFERENCE,
                            MOE.ADB_L_DELIVERY_STATUS,
                            to_char(moe.adb_timestamp,'YYYY-MM-DD HH24:MI:SS') as adb_timestamp,
                            ADB_SEQUENCE
                        FROM
                            inact.material_operations_export moe,
                            inact.material_operations mo
                        WHERE
                            moe.ADB_L_DELIVERY_STATUS <> 'C' and
                            MO.ID = MOE.MO_ID
                        ORDER BY
                            oper_date desc");
        //$cl_do_err->sql_overlap();
        $cl_do_err->FetchAll();
        //$cl_do_err->get_info();
        $cl_do_err->edit_table();


        //TODO dodaæ operacjê show-table

        $cl_supplier = new db_edit('inacttst.inact', 'supplier_lists', 'SUPPLIER_LISTS [db: INACTTST]', array(), array('EDIT', 'INSERT', 'DELETE'));
        $cl_supplier->set_description("Edycja tabeli SUPPLIERS_LIST");
        $cl_supplier->set_sql("select ROWIDTOCHAR(ROWID), S.LIFNR, S.SORTL, S.NAME1 FROM INACT.SUPPLIERS_LISTS s order by S.SORTL");
        //$cl_supplier->sql_overlap();
        $cl_supplier->FetchAll();
        //$cl_do_err->get_info();
        $cl_supplier->edit_table();
    }
}
//include ('footer.php');
?>