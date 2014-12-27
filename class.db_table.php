<?php
/*
 * Program do zapisu do konkretnej tabeli danych do przerobienia na klase
 *
 */

//TODO do przerobienia na klase

$title = 'Operacje na tabeli SUPLLIER_LISTS';
include 'header.php';
include ('funkcje/auth.php');
require_once ('funkcje/functions.php');
require_once ('class.db.php');
include("connect.php");
include ('funkcje/auth_header.php');

$l_conn = connectToSchema('INACTTST', 'INACT_1');
if (!countRoles($_SESSION['INACT_USER'], $l_conn)) {
    ?>
    /* @var $_REQUEST type */
    <h1>Wykasowanie znacznika wys³ania materia³u do SAP dla <?php print $_SESSION['INACT_USER'] . " na " . $_REQUEST['database']; ?></h1>
    <div>
        <h1>Nie masz wystarczaj¹cych uprawnieñ do InAcT</h1>
        <a href="password.php">Powrót</a>.
    </div>
    <?php
    return;
}

if (!hasRoles($_SESSION['INACT_USER'], "'INACT_DATA_VIEWER', 'INACT_INV_ROL_STOREKEEPER'", $conn)) {
    print "<h1>Brak uprawnieñ</h1>";
    exit();
}

/*
 * operacje na tabeli oracle
 *
 *
 */

class db_table extends db_edit {

    public function update($param) {

    }

    public function insert($param) {

    }

    public function delete($param) {

    }

}

if (isset($_REQUEST['SUBMIT'])) {
    if (isset($_REQUEST['LIFNR']) AND isset($_REQUEST['SORTL'])) {
        if ($_REQUEST['SUBMIT'] == 'INSERT') {
            $query = "INSERT INTO INACT.SUPPLIERS_LISTS (NAME1, SORTL, LIFNR) VALUES ('" . $_REQUEST['NAME1'] . "', '" . $_REQUEST['SORTL'] . "','" . $_REQUEST['LIFNR'] . "')";
            print $query;
            $cl_db = new db('inacttst.inact_1', 'update supplier', '1');
            $cl_db->set_sql($query);
            $cl_db->execute_sql();
            print "<h2>Poprawny " . $_REQUEST['SUBMIT'] . "</h2>";
        } elseif ($_REQUEST['SUBMIT'] == 'EDIT' AND isset($_REQUEST['ROWIDTOCHAR(ROWID)'])) {
            $query = "UPDATE INACT.SUPPLIERS_LISTS SET    SORTL = '" . $_REQUEST['NAME1'] . "', NAME1 = '" . $_REQUEST['NAME1'] . "', LIFNR = '" . $_REQUEST['LIFNR'] . "' WHERE  LIFNR = '" . $_REQUEST['LIFNR'] . "' and ROWID = CHARTOROWID('" . $_REQUEST['ROWIDTOCHAR(ROWID)'] . "')";
            print $query;
            $cl_db = new db('inacttst.inact_1', 'update supplier', '1');
            $cl_db->set_sql($query);
            $cl_db->execute_sql();
            print "<h2>Poprawna operacja " . $_REQUEST['SUBMIT'] . "</h2>";
        } elseif ($_REQUEST['SUBMIT'] == 'DELETE' AND isset($_REQUEST['ROWIDTOCHAR(ROWID)'])) {
            $query = "DELETE FROM INACT.SUPPLIERS_LISTS WHERE  LIFNR = '" . $_REQUEST['LIFNR'] . "' and ROWID = CHARTOROWID('" . $_REQUEST['ROWIDTOCHAR(ROWID)'] . "')";
            print $query;
            $cl_db = new db('inacttst.inact_1', 'update supplier', '1');
            $cl_db->set_sql($query);
            $cl_db->execute_sql();
            print "<h2>Poprawna operacja " . $_REQUEST['SUBMIT'] . "</h2>";
        } else {
            print "<h2>B³êdna operacja " . $_REQUEST['SUBMIT'] . "</h2>";
        }
    } else {
        print "<h1>Nie ma takiej operacji: " . $_REQUEST['SUBMIT'] . "</h1>";
    }
} else {
    print "<h1>Brak wywo³ania SUBMIT</h1>";
}

print "<a href='javascript:history.go(-1)'>Back...</a>";

include 'footer.php';
?>