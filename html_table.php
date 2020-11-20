<?php

/* ------------------------------------------------------------------------- **
Quick docs

$content = array of cell content left to right
$attributes = array (html_attribute => value)
              html_attribute may be style, class, id, name
$offset starts at 0.
$offset set null refers to most recent row or column added.

$my_table = new html_table($table_attributes);
$my_table->set_header($content, $caption, $header_attributes);
$my_table->set_header_cell($col_offset, $attributes, $content);

$my_table->add_row($content, $row_attributes);
$my_table->set_row_cell($row_offset, $col_offset, $attributes, $content)

$html = $my_table->get_html();
** ------------------------------------------------------------------------- */

// Not implemented: colspan

class html_table {
    private $table_attrributes;
    private $header;
    private $rows;

    public function __construct(array $table_attributes=null) {
        $this->table_attributes = $this->set_attributes($table_attributes);
        $this->header = new stdClass();
        $this->rows = array();
    }

    public function set_header(array $content, string $caption=null, array $header_attributes=null, array $caption_attributes=null) {
        $this->header->attributes = $this->set_attributes($header_attributes);
        $this->header->cells = array();
        foreach ($content as $cell_content) {
            $this->header->cells[] = new stdClass();
            $cell_index = array_key_last($this->header->cells);
            $this->header->cells[$cell_index]->attributes = $this->set_attributes(null);
            $this->header->cells[$cell_index]->html = $cell_content;
        }

        $this->header->caption->attributes = $this->set_attributes($caption_attributes);
        $this->header->caption->html = $caption;
    }

    public function set_header_cell(integer $col_offset=null, array $new_attributes=null, string $new_content=null) {
        $this->check_bounds($col_offset, $this->header->cells);
        $this->change_cell($this->header->cells[$col_offset]), $new_attributes, $new_content);
    }

    public function add_row(array $content, array $row_attributes) {
        $this->rows[] = new stdClass();
        $row_index = array_key_last($this->rows);
        $this->rows[$row_index]->attributes = $this->set_new_attributes($row_attributes);
        $this->rows[$row_index]->cells = array();
        foreach ($content as $cell_content) {
            $this->rows[$row_index]->cells[] = new stdClass();
            $cell_index = array_key_last($this->rows[$row_index]->cells);
            $this->rows[$row_index]->cells[$cell_index]->html = $cell_content;
            $this->rows[$row_index]->cells[$cell_index]->attributes = $this->set_new_attributes(null);
        }
    }

    public function set_row_cell(integer $row_offset, integer $col_offset, array $new_attributes, string $new_content) {
        $this->check_bounds($row_offset, $this->rows);
        $this->check_bounds($col_offset, $this->rows[$row_offset]->cells);
        $this->change_cell($this->rows[$row_offset]->cells[$coll_offset], $new_attributes, $new_content);
    }

    public function get_rows_count() {
        return count($this->rows);
    }

    public function get_html() {
    }

    private function check_bounds(integer &$offset, array $countable) {
        switch (true) {
        case is_null($offset):
        case $offset < 0:
        case $offset > (count($countable) - 1):
            $offset = count($countable) - 1;
        }
    }

    private function set_new_attributes(array $attributes=null) {
        $new_attributes = new stdClass();
        $new_attributes->style = isset($attributes['style']) ? $attributes['style'] : null;
        $new_attributes->class = isset($attributes['class']) ? $attributes['class'] : null;
        $new_attributes->id    = isset($attributes['id'])    ? $attributes['id']    : null;
        $new_attributes->name  = isset($attributes['name'])  ? $attributes['name']  : null;
        return $new_attributes;
    }

    private function change_cell(stdClass &$cell, array $new_attributes=null, string $new_content=null) {
        // Do not change html when $content is null
        if (!is_null($new_content)) $cell->html = $new_content;

        // Do not change attribute when not set
        if (isset($new_attributes['style'])) $cell->attributes->style = $new_attributes['style']
        if (isset($new_attributes['class'])) $cell->attributes->class = $new_attributes['class']
        if (isset($new_attributes['id']))    $cell->attributes->id    = $new_attributes['id']
        if (isset($new_attributes['name']))  $cell->attributes->name  = $new_attributes['name']
    }


    private function build_table() {
    }


    private function build_header() {
        $attributes = $this->build_attributes($this->header->attributes);
        $html = "<tr{$attributes}>\n";
        foreach($this->header->cells as $cell) {
            $attributes = $this->build_attributes($cell->attributes);
            $html .= "<th{$attributes}>{$cell->html}</th>\n"
        }

        return $html . "</tr>\n";
    }

    private function build_row(integer $row_index) {
        $attributes = $this->build_attributes($this->rows[$row_index]->attributes);
        $html = "<tr{$attributes}>\n";
        foreach($this->rows[$row_index]->cells as $cell) {
            $attributes = $this->build_attributes($cell->attributes);
            $html .= "<td{$attributes}>{$cell->html}</td>\n"
        }

        return $html . "</tr>\n";
    }

    private function build_attributes(array $attributes) {
        $style = is_null($attributes->style) ? "" : " style='{$attributes->style}'";
        $class = is_null($attributes->class) ? "" : " class='{$attributes->class}'";
        $id    = is_null($attributes->id)    ? "" : " id='{$attributes->id}'";
        $name  = is_null($attributes->name)  ? "" : " name='{$attributes->name}'";
        return "{$name}{$id}{$class}{$style}";
    }

}
