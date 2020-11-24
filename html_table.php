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

class html_table {
    private $table_attributes;
    private $caption;
    private $rows;

    public function __construct(array $table_attributes=null) {
        $this->table_attributes = (object) $table_attributes;
        $this->caption = new stdClass();
        $this->rows = array();
    }

    public function set_caption(string $content=null, array $attributes=null) {
        $this->caption->attributes = (object) $attributes;
        $this->caption->html = $content;
    }

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

    public function update_cell(int $row_offset, int $cell_offset, array $new_attributes=null, string $new_content=null, string $new_tag=null) {
        // Do not change html when there is no new content is given.
        if (!is_null($new_content)) $cell->html = $new_content;

        // Do not change tag when a new tag is not given.
        if (!is_null($new_tag)) $cell->tag = $new_tag;

        // Do not change attribute when it is not included in the array.
        if (isset($new_attributes['style']))   $this->rows[$row_offset]->cells[$cell_offset]->attributes->style   = $new_attributes['style'];
        if (isset($new_attributes['class']))   $this->rows[$row_offset]->cells[$cell_offset]->attributes->class   = $new_attributes['class'];
        if (isset($new_attributes['id']))      $this->rows[$row_offset]->cells[$cell_offset]->attributes->id      = $new_attributes['id'];
        if (isset($new_attributes['name']))    $this->rows[$row_offset]->cells[$cell_offset]->attributes->name    = $new_attributes['name'];
        if (isset($new_attributes['colspan'])) $this->rows[$row_offset]->cells[$cell_offset]->attributes->colspan = $new_attributes['colspan'];
        if (isset($new_attributes['rowspan'])) $this->rows[$row_offset]->cells[$cell_offset]->attributes->rowspan = $new_attributes['rowspan'];
    }

    public function get_rows_count() {
        return count($this->rows);
    }

    public function get_cols_count(int $row_offset) {
        return count($this->rows[$row_offset]->cells);
    }

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

    private function check_tag($tag) {
        $tag = strtolower($tag);
        if ($tag !== "td" && $tag !== "th") $tag = "td";
        return $tag;
    }

    private function build_row($row) {
        $attributes = $this->build_attributes($row->attributes);
        $html = "    <tr{$attributes}>\n";
        foreach($row->cells as $cell) {
            $attributes = $this->build_attributes($cell->attributes);
            $html .= "        <{$cell->tag}{$attributes}>{$cell->html}</{$cell->tag}>\n";
        }

        return $html . "    </tr>\n";
    }

    private function build_attributes($attributes) {
        $class   = isset($attributes->class)   ? " class='{$attributes->class}'"     : "";
        $style   = isset($attributes->style)   ? " style='{$attributes->style}'"     : "";
        $id      = isset($attributes->id)      ? " id='{$attributes->id}'"           : "";
        $name    = isset($attributes->name)    ? " name='{$attributes->name}'"       : "";
        $colspan = isset($attributes->colspan) ? " colspan='{$attributes->colspan}'" : "";
        $rowspan = isset($attributes->rowspan) ? " rowspan='{$attributes->rowspan}'" : "";
        return "{$name}{$id}{$class}{$style}{$colspan}{$rowspan}";
    }
}
