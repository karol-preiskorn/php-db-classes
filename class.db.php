<?php

/**
 *
 * Klasa opracje na bazie danych Oracle 10.2
 *
 * autor: Karol Preiskorn
 *
 * 2010-08-13 +num_rows
 * 2012-07-29 poprawki i robudowa o fetch all
 * 2012-11-12 przebudowa i upgrade do PHP 5.3
 * 2013-03-11 zmina adresu bazy testowej inacttst
 * 2013-03-28 doadanie metody set_rows
 * 2014-09-15 KP usuniêcie fields_names
 *
 */
class db {

    private $db;      // schemat
    private $db_name; // schemat i nazwa bazy
    private $conn;    // po³¹czenie
    private $stid;
    private $sql;                     // sql
    private $rows = array();          // result fetch all liczone w wierszach
    private $rows_num;                // liczba zwracanych w zapytaniu wierszy
    private $fields_num;              // liczba pól w wyniku zapytania
    private $columns = array();       // name, type, size
    public $title;                   // subject sql
    public $description;             // description what sql do
    private $sql_name;

    public function __construct($db_name = 'inacttst.inact_1', $sql_name = 'sql', $title = 'sql_title') {
// clear
        $columns = '';
        $args = func_get_args();
        $args_no = func_num_args();
        //print_r($args);
        foreach (array("db_name", "sql_name", "title") as $i) {
            //if ($args_no < 3) {
            //    print("<div class='error'>Error: No arguments <i>db_name</i>, <i>sql_name</i>, <i>title</i> in class constructor " . __CLASS__ . "</div>");
            //}
            $this->$i = array_shift($args);
        }

        $this->db_name = $db_name;

        // exaple rest in 'class.passwords.php'
        switch ($this->db_name) {
            case 'dbname':
                $this->db = "(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=adress)(PORT=1521)))(CONNECT_DATA=(SID=siddb)(SERVER=DEDICATED)))";
                $this->conn = oci_connect("user", "pass", $this->db);
                break;

            default:
                exit("<div id='error'>Class DB error: brak bazy o nazwie " . $this->db_name . "</div>");
        }
        include_once 'class.passwords.php';

        if (!$this->conn) {
            $e = oci_error();
            trigger_error(htmlentities('<h2><b>Error calss.db->_constuctor</b> @ logon to ' . $p_db . 'oracle: ' . $e['message'] . '</h2>', ENT_QUOTES), E_USER_ERROR);
        }
    }

    public function __destruct() {
        //print "desctuct!";
        if (isset($this->stid))
            oci_free_statement($this->stid);
        if (isset($this->conn))
            oci_close($this->conn);
    }

    public function parse() {

        if (!isset($this->sql) or ! isset($this->conn))
            exit('class DB->parse: not set sql or conn: ');

        $l_stid = oci_parse($this->conn, $this->sql);

        if (!$l_stid) {
            $e = oci_error($this->conn);  // For oci_parse errors pass the connection handle
            trigger_error('<b>Error class.db->parse</b> Could not parse <pre>' . htmlentities($e['message']), E_USER_ERROR);
            die(oci_error());
        }

        $this->stid = $l_stid;
        return $l_stid;
    }

    /*
     * ograniczenie wyniku zapytania
     * @package classes
     */

    public function sql_overlap($p_n_rows = 10) {
        IF (isset($_REQUEST["value_" . $this->get_sql_name()])) {
            if ($_REQUEST["value_" . $this->get_sql_name()] <> '') {

                $this->sql = "SELECT * FROM (" . $this->sql . ")
WHERE
ROWNUM <= 100 AND " .
                        $_REQUEST["select_" . $this->get_sql_name()] . " " .
                        $_REQUEST ["relation_" . $this->get_sql_name()] . " " .
                        $_REQUEST["value_" . $this->get_sql_name()];
            } else {
                $this->sql = "SELECT * FROM (" . $this->sql . ") WHERE ROWNUM <= " . $p_n_rows;
            }
        } else {
            $this->sql = "SELECT * FROM (" . $this->sql . ") WHERE ROWNUM <= " . $p_n_rows;
        }
    }

    public function execute_sql($p_mode = OCI_DEFAULT) {
        if (!isset($this->sql)) {
            exit('class DB: execute_sql: SQL: <pre>' . $this->sql . '</pre> not set.' . print_r(error_get_last(), TRUE));
        }
        $this->parse();
        $r = oci_execute($this->stid, $p_mode);
        if (!$r) {
            $e = oci_error($this->stid);  // For oci_execute errors pass the statement handle
            print htmlentities($e['message']);
            print "\n<pre>\n";
            print htmlentities($e['sqltext']);
            printf("\n%" . ($e['offset'] + 1) . "s", "^");
            print "\n</pre>\n";
        }

        $this->rows_num = oci_num_rows($this->stid);
        $this->set_name_fields();
        $r = oci_commit($this->conn);
        if (!$r) {
            $e = oci_error($this->conn);
            trigger_error(htmlentities($e['message']), E_USER_ERROR);
            print "Class DB->execute_sql: " . htmlentities($e['message']);
            exit();
        }
    }

    public function execute($p_sql) {
        if (!isset($p_sql) or $p_sql == '')
            exit('class DB: execute_sql: SQL: <pre>' . $this->sql . '</pre> not set.' . print_r(error_get_last(), TRUE));
        $this->set_sql($p_sql);
        $this->parse();

        $r = oci_execute($this->stid);
        if (!$r) {
            $e = oci_error($this->stid);  // For oci_execute errors pass the statement handle
            print htmlentities($e['message']);
            print "\n<pre>\n";
            print htmlentities($e['sqltext']);
            printf("\n%" . ($e['offset'] + 1) . "s", "^");
            print "\n</pre>\n";
        }

        $this->rows_num = oci_num_rows($this->get_stid());
        $this->set_name_fields();
        $r = oci_commit($this->conn);
        if (!$r) {
            $e = oci_error($this->conn);
            trigger_error(htmlentities($e['message']), E_USER_ERROR);
        }
    }

    public function execute_stid($p_stid) {
        $this->set_stid($p_stid);
        oci_execute($this->stid) or die('DB ERROR: Could not oci_execute (SQL)  <pre>' . $this->get_sql() . '</pre> : ' . ocierror());
        $this->rows_num = oci_num_rows($this->get_stid());
        $this->set_name_fields();
    }

    public function get_result($p_field) {
        return oci_result($this->stid, $p_field);
    }

    public function get_db() {
        return $this->db_name;
    }

    public function set_sql($p_sql) {
        $this->sql = $p_sql;
    }

    public function set_stid($p_stid) {
        $this->stid = $p_stid;
    }

    public function get_sql() {
        return $this->sql;
    }

    public function get_rows_num() {
        return $this->rows_num;
    }

    public function get_fields_num() {
        return $this->fields_num;
    }

    public function fetch_array() {
        $this->rows = oci_fetch_array($this->stid, OCI_ASSOC +
                OCI_RETURN_NULLS);
    }

    public function set_name_fields() {
        for ($i = 1; $i <= $this->fields_num; $i++) {
            $this->columns['name'][$i] = oci_field_name($this->stid, $i);
            $this->columns['type'][$i] = oci_field_type($this->stid, $i);
            $this->columns['size'][$i] = oci_field_size($this->stid, $i);
        }
    }

    public function get_primary_keys() {
        $sql = "SELECT cols.table_name,
                        cols.column_name,
                        cols.position,
                        cons.status,
                        cons.owner
                    FROM
                        all_constraints cons, all_cons_columns cols
                   WHERE
                        cols.table_name = 'PURCHASE_ORDERS'
                        AND cons.constraint_type = 'P'
                        AND cons.constraint_name = cols.constraint_name
                        AND cons.owner = cols.owner
                   ORDER BY
                        cols.table_name, cols.position";
    }

    /*     * *
     * Run a query and return all rows.
     * @param string $sql A query to run and return all rows
     * @param string $action Action text for End-to-End Application Tracing
     * @param array $bindvars Binds. An array of (bv_name, php_variable, length)
     * @return array An array of rows
     * * */

    public function execFetchAll($p_sql) {
        $this->execute($p_sql);
        $this->rows_num = oci_fetch_all($this->stid, $this->rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);
        $this->fields_num = oci_num_fields($this->stid);
        $this->set_name_fields();

        return($this->rows);
    }

    /*
     * Run a query and return all rows with parse paramteres
     * @param string $p_sql A query to run and return all rows
     * @param array  $p_bindvars Binds. An array of (bv_name, php_variable, length)
     * @return array An array of rows
     */

    public function FetchAll() {
        if (!isset($this->sql) or ! isset($this->conn))
            exit('class.db->fetchall: not set sql or conn: ');

        $this->execute_sql();
        $this->rows_num = oci_fetch_all($this->stid, $this->rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);
        //$this->rows_num = oci_fetch_all($this->stid, $this->rows);
        $this->fields_num = oci_num_fields($this->stid);
        $this->set_name_fields();

        return($this->rows);
    }

    public function get_stid() {
        return $this->stid;
    }

    public function get_rows() {
        return $this->rows;
    }

    public function set_rows($p_rows) {
        $this->rows = $p_rows;
    }

    public function get_db_name() {
        return $this->db_name;
    }

    public function get_conn() {
        return $this->conn;
    }

    /*
     * title and description
     */

    public function get_title() {
        return $this->title;
    }

    public function set_title($p_title) {
        $this->title = $p_title;
    }

    public function get_description() {
        return $this->description;
    }

    public function set_description($p_description) {
        $this->description = $p_description;
    }

    public function get_sql_name() {
        return $this->sql_name;
    }

    public function get_columns() {
        return $this->columns;
    }

    /*
     * Output
     */

    public function output_cvs($p_name) {
        $filename = $p_name . "_" . date('Y-m-d_Hi');

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check = 0, pre-check = 0");
        header("Cache-Control: private", false);
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment;filename = \"$filename.csv\";");
        header("Content-Transfer-Encoding: binary");

        $outstream = fopen(
                "php://output", "w+");

        function __outputCSV(&$vals, $key, $filehandler) {
            fputcsv($filehandler, $vals, ';'); // add parameters if you want
        }

        // error (*)

        print_r($this->columns['names']);
        fputcsv($outstream, $this->columns['name'], ';');
        array_walk($this->rows, "__outputCSV", $outstream);
        fclose($outstream);
    }

    public function add_select_where() {

        if (!isset($_REQUEST[('select_' . $this->get_sql_name())])) {
            $_REQUEST[('select_' . $this->get_sql_name())] = $this->columns['name'][1];
        }

        if (!isset($_REQUEST[('value_' . $this->get_sql_name())])) {
            $_REQUEST[('value_' . $this->get_sql_name())] = '';
        }

        if (!isset($_REQUEST[('relation_' . $this->get_sql_name())])) {
            $_REQUEST [('relation_' . $this->get_sql_name())] = '';
        }

        print '<form action="#' . $this->title . '" method="post" name="' . 'form_' . $this->get_sql_name() . '">' . "\n";

// KPe opcja onchange_="javascript: document.form_' . $this->get_sql_name() . '.submit()
        print '<select name="' . "select_" . $this->get_sql_name() . '">' . "\n";

        for ($i = 1; $i <= $this->fields_num; $i++) {
            if ($this->columns['name'][$i] <> 'ROWIDTOCHAR(ROWID)') {
                print '<option value = "' . $this->columns['name'][$i] . '" ';
                if ($_REQUEST[('select_' . $this->get_sql_name())] == $this->columns['name'][$i]) {
                    print "SELECTED";
                }
                print '>' . $this->columns ['name'][$i] . ' ' . $this->columns ['type'][$i] . ' (' . $this->columns['size'] [$i] . ')</option>' . "\n";
            }
        }

        print '</select>' . "\n" . "\n";

        print '<select name = "' . "relation_" . $this->get_sql_name() . '">' . "\n";
        print ' <option value = "=">=</option>' . "\n";
        print ' <option value = "<">></option>' . "\n";
        print ' <option value = ">"><</option>' . "\n";
        print ' <option value = "LIKE">LIKE</option>' . "\n";
        print '</select>' . "\n";
        print '<input type = "text" name = "value_' . $this->get_sql_name() . '" value = "' . $_REQUEST[('value_' . $this->get_sql_name() )] . '" size = "20" />' . "\n";
        print "<input type = \"submit\" />" . "\n";
        print '</form>' .
                "\n" . "\n";
    }

    public function output_html_table() {
        print "<h2 id=" . $this->sql_name . ">" . $this->title . " (" . $this->rows_num . " rows)</h1>\n";
//print_pre($this->rows);
        $this->add_select_where();
        print "<table cellspacing=0 cellpadding=1 onMouseOut = \"javascript:highlightTableRowVersionA(0);\">";
        print "<tr onclick=\"miniTable(this)\">";
        for ($i = 1; $i <= $this->fields_num; $i++) {
            print "<th>
              <a href='#' TITLE='" .
                    $this->columns ['type'][$i] .
                    " (" . $this->columns ['size'][$i] . ")'>" .
                    $this->columns['name'][$i] . "</a>
             </th>";
        }
        print "</tr>";

        if ($this->get_rows_num() > 0) {
            for ($i = 0; $i <= $this->get_rows_num(); $i++) {
                if ((bool) ($i & 1)) {
                    $l_bg = " bgcolor='#FFF7DC' ";
                } else {
                    $l_bg = '';
                }

                print "<tr " . $l_bg . " onMouseOver=\"javascript:highlightTableRowVersionA(this, '#F9EFFF');\">";
                for ($j = 1; $j <= $this->fields_num; $j++) {
                    print "<td>" . $this->rows[$i][($this->columns['name'][$j])] . "</td>";
                }
                print "</tr>";
            }
            print "</table>";
        } else {
            print "Table is empty";
        }
    }

    public function get_info() {
        $l_ret = "<table cellpading=1><tr><td>Connection</td><td>";
        $l_ret .= $this->sql_name;
        $l_ret .= "</td></tr><tr><td>Database</td><td>";
        $l_ret .= $this->db_name;
        $l_ret .= "</td></tr><tr><td>Title</td><td>";
        $l_ret .= $this->title;
        $l_ret .= "</td></tr><tr><td>SQL</td><td>";
        $l_ret .= "<pre>" . $this->sql . "</pre>";       // sql
        $l_ret .= "</td></tr><tr><td>Num rows</td><td>";
        $l_ret .= $this->rows_num;  // liczba zwracanych w zapytaniu wierszy
        $l_ret .= "<tr><td>Columns</td><td>";
        $l_ret .= "<pre>" . print_r($this->columns, TRUE) . "</pre>";      // liczba wierszy
        $l_ret .= "</td></tr>";
        $l_ret .= "</table>";
        print $l_ret;
    }

}

?>