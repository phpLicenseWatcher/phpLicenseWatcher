<?php

/* ------------------------------------------------------------------------- **
QUICK DOCS

$content = array of cell content left to right
$attributes = array (html_attribute => value)
              html_attribute may be style, class, id, name
$offset starts at 0.

$my_table = new html_table($table_attributes);
$my_table->add_row($content, $row_attributes, $tag);
$my_table->update_cell($row_offset, $col_offset, $attributes, $content, $tag);
$num_rows = $my_table->get_rows_count();
$num_cols = $my_table->get_cols_count($row_offset);
$html = $my_table->get_html();

** ------------------------------------------------------------------------- **
DATA STRUCTURES
Data is stored in stdClass() objects.

Only attributes that are used get set.
e.g. <table name='my_table' style='width:100%;'>
     Only $attributes->name and $attributes->style are set.
$attributes->name
           ->id
           ->class
           ->style
           ->colspan
           ->rowspan

This is information to create the table's <caption> tag.
$caption->attributes  (see above)
        ->html        (browser content)

One html table can have many rows and one row can have many cells.
$rows and $rows->cells are both numerically indexed.
$rows[index]->attributes  (Applied to <tr>.  See above.)
            ->cells[index]->attributes  (Applied to <td> or <th>.  See above.)
                          ->tag         ("td" or "th")
                          ->html        (browser content)

HTML code to be printed or concat'd to an output string is constructed from
set data when html_table::get_html() is called.
** ------------------------------------------------------------------------- */

/**
 * Replaces PEAR/HTML_Table class for phpLicenseWatcher.
 *
 * @author Peter Bailie (RPI Research Computing)
 */
class html_table {
    private $table_attributes;
    private $caption;
    private $rows;

    /**
     * Constructor inits object properties.
     *
     * @access public
     */
    public function __construct(array $table_attributes=null) {
        $this->table_attributes = (object) $table_attributes;
        $this->caption = new stdClass();
        $this->rows = array();
    }

    /**
     * Set table caption properties.
     *
     * $attributes are an array of [attribute => value] where <caption attribute='value'>
     * e.g. array('id'=>"my_id", 'class'=>"my_class") will create <caption id='my_id' class='my_class'>
     *
     * @access public
     * @param string $content table caption to be displayed.
     * @param array $attributes <caption> tag attributes in an array of ['attribute' => $value]
     */
    public function set_caption(string $content=null, array $attributes=null) {
        $this->caption->attributes = (object) $attributes;
        $this->caption->html = $content;
    }

    /**
     * Add a row to table.
     *
     * $row_attributes are an array of [attribute => value] where <tr attribute='value'>
     * e.g. array('id'=>"my_id", 'class'=>"my_class") will create <tr id='my_id' class='my_class'>
     * $tag determines whether <td> or <th> is encapsulated with <tr>.
     *
     * @access public
     * @param array $content row content given as an array
     * @param array $row_attributes <tr> tag attributes in an array of ['attribute' => $value]
     * @param string $tag set to either "td" or "th" whether this is a data row or header row, respectively.
     */
    public function add_row(array $content, array $row_attributes=null, string $tag="td") {
        $tag = $this->check_tag($tag);
        $this->rows[] = new stdClass();
        $row_index = array_key_last($this->rows);
        $this->rows[$row_index]->attributes = (object) $row_attributes;
        $this->rows[$row_index]->cells = array();
        foreach ($content as $cell_content) {
            $this->rows[$row_index]->cells[] = new stdClass();
            $cell_index = array_key_last($this->rows[$row_index]->cells);
            $this->rows[$row_index]->cells[$cell_index]->html = $cell_content;
            $this->rows[$row_index]->cells[$cell_index]->attributes = new stdClass();
            $this->rows[$row_index]->cells[$cell_index]->tag = $tag;
        }
    }

    /**
     * Update a specific cell located by row and column offset.
     *
     * Specific cell attributes are set using this method.
     * Method html_table::get_rows_count() can be used to locate the most recent row added.
     * $new_attributes are an array of [attribute => value] where <td attribute='value'> or <th attribute='value'>
     * e.g. array('id'=>"my_id", 'class'=>"my_class") will create <td id='my_id' class='my_class'>
     *
     * @access public
     * @param int $row_offset zero based.
     * @param int $col_offset zero based.
     * @param array $new_attributes <td> or <th> tag attributes in an array of ['attribute' => $value]
     * @param string $new_content cell content for display.
     * @param string $new_tag set to "td" to update cell to be data or "th" to update cell to be a header.
     */
    public function update_cell(int $row_offset, int $cell_offset, array $new_attributes=null, string $new_content=null, string $new_tag=null) {
        // Do not change html when there is no new content is given.
        if (!is_null($new_content)) $this->rows[$row_offset]->cells[$cell_offset]->html = $new_content;

        // Do not change tag when a new tag is not given.
        if (!is_null($new_tag)) $this->rows[$row_offset]->cells[$cell_offset]->tag = $this->check_tag($new_tag);

        // Only update attributes given in $new_attributes.
        if (is_array($new_attributes)) {
            foreach ($new_attributes as $key=>$new_attribute) {
                $this->rows[$row_offset]->cells[$cell_offset]->attributes->$key = $new_attribute;
            }
        }

        // if (isset($new_attributes['style']))   $this->rows[$row_offset]->cells[$cell_offset]->attributes->style   = $new_attributes['style'];
        // if (isset($new_attributes['class']))   $this->rows[$row_offset]->cells[$cell_offset]->attributes->class   = $new_attributes['class'];
        // if (isset($new_attributes['id']))      $this->rows[$row_offset]->cells[$cell_offset]->attributes->id      = $new_attributes['id'];
        // if (isset($new_attributes['name']))    $this->rows[$row_offset]->cells[$cell_offset]->attributes->name    = $new_attributes['name'];
        // if (isset($new_attributes['colspan'])) $this->rows[$row_offset]->cells[$cell_offset]->attributes->colspan = $new_attributes['colspan'];
        // if (isset($new_attributes['rowspan'])) $this->rows[$row_offset]->cells[$cell_offset]->attributes->rowspan = $new_attributes['rowspan'];
    }

    /**
     * Returns the total number of rows currently in the table.
     *
     * This is useful to help locate the most recent row added so to update a particular cell.
     * Note that this is one-based, so use `html_table::get_rows_count() - 1` to locate the most recent row added.
     *
     * @access public
     * @return int
     */
    public function get_rows_count() {
        return count($this->rows);
    }

    /**
     * Returns the total number of columns in a specific row.  Return is one-based.
     *
     * @access public
     * @param int $row_offset zero based.
     * @return int
     */
    public function get_cols_count(int $row_offset) {
        return count($this->rows[$row_offset]->cells);
    }

    /**
     * Construct and return the table's HTML as a string.
     *
     * @access public
     * @return string
     */
    public function get_html() {
        $attributes = $this->build_attributes($this->table_attributes);
        $html = "<table{$attributes}>\n";

        if (isset($this->caption->html)) {
            $attributes = $this->build_attributes($this->caption->attributes);
            $html .= "<caption{$attributes}>{$this->caption->html}</caption>\n";
        }

        foreach($this->rows as $row) {
            $html .= $this->build_row($row);
        }

        return "{$html}</table>\n";
    }

    /**
     * Make sure tag is either "th" or "td", lowercase.  "td" is the default.
     *
     * @access private
     * @param $tag
     * @return string
     */
    private function check_tag($tag) {
        $tag = strtolower($tag);
        if ($tag !== "td" && $tag !== "th") $tag = "td";
        return $tag;
    }

    /**
     * Build HTML for an entire row
     *
     * @access private
     * @param int $row row index to build
     * @return string row HTML
     */
    private function build_row($row) {
        $attributes = $this->build_attributes($row->attributes);
        $html = "    <tr{$attributes}>\n";
        foreach($row->cells as $cell) {
            $attributes = $this->build_attributes($cell->attributes);
            $html .= "        <{$cell->tag}{$attributes}>{$cell->html}</{$cell->tag}>\n";
        }

        return $html . "    </tr>\n";
    }

    /**
     * Build string of attributes to concat into HTML tag
     *
     * @access private
     * @param array $attributes
     * @return string
     */
    private function build_attributes($attributes) {
        $attr_string = "";
        foreach($attributes as $key=>$attribute) {
            $attr_string .= " {$key}='{$attribute}'";
        }

        return $attr_string;

        // $class   = isset($attributes->class)   ? " class='{$attributes->class}'"     : "";
        // $style   = isset($attributes->style)   ? " style='{$attributes->style}'"     : "";
        // $id      = isset($attributes->id)      ? " id='{$attributes->id}'"           : "";
        // $name    = isset($attributes->name)    ? " name='{$attributes->name}'"       : "";
        // $colspan = isset($attributes->colspan) ? " colspan='{$attributes->colspan}'" : "";
        // $rowspan = isset($attributes->rowspan) ? " rowspan='{$attributes->rowspan}'" : "";
        // return "{$name}{$id}{$class}{$style}{$colspan}{$rowspan}";
    }
}
