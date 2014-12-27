<?php

require_once ('class.db.php');

/**
 *
 * Wyświetlanie raportów bazodanowych
 *
 * @author      Karol Preiskorn <kpreiskorn@era.pl>
 * @version     1.2
 * @link        http://ips/reports.php
 * @param       string pc_name nazwa raportu
 *
 * @todo wywalic phpmanual z katalogu głównego
 *
 */
function issetor(&$variable, $or = NULL) {
    return $variable === NULL ? $or : $variable;
}

class html_select {
    public $array;
    public function __construct() {
        echo "construct::db(" . $this->db_name . ") \n";
        echo '<form method="get" action="#">
			<select name="day">
			<option value="1">Monday/Wednesday
			<option value="2">Tuesday/Thursday
			<option value="3">Friday/Sunday
			<option value="4">Saturday
			</select>
			<input type="submit" value="Send">
			</form>';
    }
}

class format {
    public function size_readable($size, $max = null, $system = 'si', $retstring = '%01.2f %s') {
        // Pick units
        $systems['si']['prefix'] = array('B', 'K', 'MB', 'GB', 'TB', 'PB');
        $systems['si']['size'] = 1000;
        $systems['bi']['prefix'] = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
        $systems['bi']['size'] = 1024;
        $sys = isset($systems[$system]) ? $systems[$system] : $systems['si'];

        // Max unit to display
        $depth = count($sys['prefix']) - 1;
        if ($max && false !== $d = array_search($max, $sys['prefix'])) {
            $depth = $d;
        }

        // Loop
        $i = 0;
        while ($size >= $sys['size'] && $i < $depth) {
            $size /= $sys['size'];
            $i++;
        }
        return sprintf($retstring, $size, $sys['prefix'][$i]);
    }
}

class error {
    private $log;
    public function set($p_log) { 
    }
}

class raports extends db {
    public $result;
    public $title;
    public $subtitle;
    public $name;
    public $column;
    public $primary_key = array();

    public function __construct($p_raports_name = 'tf_', $p_db = 'inacttst.inact') {
        parent::__construct($p_db);
        $this->name = $p_raports_name;
        echo "construct::reports ".$this->name." (".parent::get_db_name().") \n";
    }

    public function set_primary_key($p_key) {
        $this->primary_key[] = $p_key;
    }

    public function set_title($p_title) {
        $this->title = $p_title;
    }

    public function set_subtitle($p_subtitle) {
        $this->subtitle = $p_subtitle;
    }

    public function get_title() {
        return $this->title;
    }

    public function get_subtitle($p_subtitle) {
        return $this->subtitle;
    }

    public function set_name($p_name) {
        $this->name = $p_name;
    }

    public function get_name() {
        return $this->name;
    }

    public function show($p_link_details = FALSE, $p_hide = 'show', $p_show_sql = TRUE) {
        $ncols = oci_num_fields(parent::get_stid());
        echo "\n<h3>" . $this->title . "</h3>\n";
        echo issetor($this->subtitle, "\n<p>" . $this->subtitle . "</p>\n");
        // show - pernametnie pokazuje tabele bez linku
        // hide - link do ukrywania i odkrywania widoczy tabela schowana
        // unhide - link do ukrywania i odkrywania widoczy tabela widoczna
        if ($p_hide <> 'show')
            echo "[<a href=\"javascript:unhide('" . $this->name . "_tab');\">Show table data</a>]
                <div id='" . $this->name . "_tab' class='" . $p_hide . "'>";

        echo "\n<table class='tr' id='" . $this->name . "' cellpadding='0' cellspacing='0'>\n";
        echo "<tr class='tf'>\n";

        for ($i = 1; $i <= $ncols; $i++) {
            $this->column['name'][$i] = oci_field_name(parent::get_stid(), $i);
            $this->column['type'][$i] = oci_field_type(parent::get_stid(), $i);
            $this->column['size'][$i] = oci_field_size(parent::get_stid(), $i);
            echo "<th>" . $this->column['name'][$i] . "</th>\n";
        }

        echo "</tr>\n";

        $l_row = 0;
        while (($row = oci_fetch_array(parent::get_stid(), OCI_ASSOC + OCI_RETURN_NULLS))) {

            $this->result[$l_row++] = $row;

            if ($p_link_details == TRUE)
                echo "<tr class='tf' onclick=\"window.location='details.php?p1=" . $row[$this->primary_key[0]] . "&p2=" . $row[$this->primary_key[1]] . "&p3=" . $row[$this->primary_key[2]] . "'\">";
            else
                echo "<tr class='tf'>";

            foreach ($row as $val_row) {
                echo "<td calss='tf'>" . $val_row . "</td>";
            }
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
        print "<script language=\"javascript\" type=\"text/javascript\">
                  //<![CDATA[
                    var props_" . $this->name . " = {
		sort: true,
		filters_row_index:1,
		remember_grid_values: true,
		alternate_rows: true,
		rows_counter: true,
		rows_counter_text: \"Displayed rows: \",
		btn_reset: true,
		btn_reset_text: \"Clear\",
		loader: true,
		loader_html: '<img src=\"../../images/load_icon.gif\" alt=\"\" style=\"vertical-align:middle; margin:0 5px 0 5px\" /><span>Loading...</span>',
		loader_css_class: 'myLoader',
		status_bar: true,
		display_all_text: \"< Show all >\",
		extensions: {
                    name:['ColumnsResizer'],
                    src:['TFExt_ColsResizer/TFExt_ColsResizer.js'],
                    description:['Columns Resizing'],
                    initialize:[function(o){o.SetColsResizer();}]
                },
		col_resizer_all_cells: true
                    }
                    var tf_" . $this->name . " = setFilterGrid(\"" . $this->name . "\", props_" . $this->name . ");
                  //]]>
               </script>";
        // show hide SQL
        if ($p_show_sql == TRUE)
            echo "[<a href=\"javascript:unhide('" . $this->name . "_sql');\">Show SQL</a>]<div id='" . $this->name . "_sql' class='hidden'><pre>" . $this->get_sql() . "</pre></div>";
        echo "</div>";
    }

    public function __destruct() {
        //echo "destruct::reports ".$this->name."  (" . $this->db_name . ") \n";
    }
}

?>