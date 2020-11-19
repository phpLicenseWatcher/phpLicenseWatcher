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
        $this->$table_attrributes = new StdClass();
        $this->set_table_attributes($table_attributes);
        $this->header = array();
        $this->rows = array();
    }

    public function set_table_attributes(array $attributes=null) {
        $this->table_attrributes->style = isset($attributes['style']) ? $attrributes['style'] : null;
        $this->table_attrributes->class = isset($attributes['class']) ? $attrributes['class'] : null;
        $this->table_attrributes->id    = isset($attributes['id'])    ? $attrributes['id']    : null;
        $this->table_attrributes->name  = isset($attributes['name'])  ? $attrributes['name']  : null;
    }

    public function set_header(array $content, string $caption, array $header_attributes) {
        $this->header = new stdClass();
        $this->header->cells = array();
        foreach ($content as $cell_content) {
            $this->header->cells[] = new stdClass();
            $cell_index = array_key_last($this->rows[$row_index]->cells);
            $this->header->cells[$cell_index]->html = $cell_content;
            $this->header->cells[$cell_index]->attributes = new stdClass();
            $this->header->cells[$cell_index]->attributes = null;
            $this->header->cells[$cell_index]->attributes = null;
            $this->header->cells[$cell_index]->attributes = null;
            $this->header->cells[$cell_index]->attributes = null;
        }

        $this->header->caption = $caption;
        $this->header->attributes = new stdClass();
        $this->header->attributes->style = isset($header_attributes['style']) ? $header_attributes['style'] : null;
        $this->header->attributes->class = isset($header_attributes['class']) ? $header_attributes['class'] : null;
        $this->header->attributes->id    = isset($header_attributes['id'])    ? $header_attributes['id']    : null;
        $this->header->attributes->name  = isset($header_attributes['name'])  ? $header_attributes['name']  : null;
    }

    public function set_header_cell(integer $col_offset=null, array $attributes=null, string $content=null) {
        switch (true) {
        case is_null($col_offset):
        case $col_offset < 0:
        case $col_offset > (count($this->header->cells) - 1):
            $col_offset = count($this->header->cells) - 1;
        }

        // Do not change html when $content is null
        if (!is_null($content)) $this->header->cells[$col_offset]->html = $content;

        // Do not change attribute when not set
        if (isset($attributes['style'])) $this->header->cells[$col_offset]->attributes->style = $attributes['style']
        if (isset($attributes['class'])) $this->header->cells[$col_offset]->attributes->class = $attributes['class']
        if (isset($attributes['id']))    $this->header->cells[$col_offset]->attributes->id    = $attributes['id']
        if (isset($attributes['name']))  $this->header->cells[$col_offset]->attributes->name  = $attributes['name']
    }

    public function add_row(array $content, array $row_attributes) {
        $this->rows[] = new stdClass();
        $row_index = array_key_last($this->rows);
        $this->rows[$row_index]->cells = array();
        foreach ($content as $cell_content) {
            $this->rows[$row_index]->cells[] = new stdClass();
            $cell_index = array_key_last($this->rows[$row_index]->cells);
            $this->rows[$row_index]->cells[$cell_index]->html = $cell_content;
            $this->rows[$row_index]->cells[$cell_index]->attributes = new stdClass();
            $this->rows[$row_index]->cells[$cell_index]->attributes = null;
            $this->rows[$row_index]->cells[$cell_index]->attributes = null;
            $this->rows[$row_index]->cells[$cell_index]->attributes = null;
            $this->rows[$row_index]->cells[$cell_index]->attributes = null;
        }

        $this->rows[$row_index]->attributes = new stdClass();
        $this->rows[$row_index]->attributes->style = isset($row_attributes['style']) ? $row_attrributes['style'] : null;
        $this->rows[$row_index]->attributes->class = isset($row_attributes['class']) ? $row_attrributes['class'] : null;
        $this->rows[$row_index]->attributes->id    = isset($row_attributes['id'])    ? $row_attrributes['id']    : null;
        $this->rows[$row_index]->attributes->name  = isset($row_attributes['name'])  ? $row_attrributes['name']  : null;
    }

    public function set_row_cell($row_offset, $col_offset, $attributes, $content) {
        switch (true) {
        case is_null($row_offset):
        case $row_offset < 0:
        case $row_offset > (count($this->rows) - 1):
            $row_offset = count($this->rows) - 1;
        }

        switch (true) {
        case is_null($col_offset):
        case $col_offset < 0:
        case $col_offset > (count($this->rows[$row_offset]->cells) - 1):
            $col_offset = count($this->rows[$row_offset]->cells) - 1;
        }

        // Do not change html when $content is null
        if (!is_null($content)) $this->rows[$row_offset]->cells[$col_offset]->html = $content;

        // Do not change attribute when not set
        if (isset($attributes['style'])) $this->rows[$row_offset]->cells[$col_offset]->attributes->style = $attributes['style']
        if (isset($attributes['class'])) $this->rows[$row_offset]->cells[$col_offset]->attributes->class = $attributes['class']
        if (isset($attributes['id']))    $this->rows[$row_offset]->cells[$col_offset]->attributes->id    = $attributes['id']
        if (isset($attributes['name']))  $this->rows[$row_offset]->cells[$col_offset]->attributes->name  = $attributes['name']
    }

    public function get_row_count() {
        return count($this->rows);
    }

    public function get_html() {
    }

    private function build_header() {
        $header_style = is_null($this->header->attributes->style) ? "" : " style='{$this->header->attributes->style}'";
        $header_class = is_null($this->header->attributes->class) ? "" : " class='{$this->header->attributes->class}'";
        $header_id    = is_null($this->header->attributes->id)    ? "" : " id='{$this->header->attributes->id}'";
        $header_name  = is_null($this->header->attributes->name)  ? "" : " name='{$this->header->attributes->name}'";

        $html = "<tr{$header_name}{$header_id}{$header_class}{$header_style}>\n";
        foreach($this->header->cells as $cell) {
            $col_style = is_null($cell->attribute->style) ? "" : " style='{$cell->attributes->style}'";
            $col_class = is_null($cell->attribute->class) ? "" : " class='{$cell->attributes->class}'";
            $col_id    = is_null($cell->attributes->id)   ? "" : " id='{$cell->attributes->id}'";
            $col_name  = is_null($cell->attributes->name) ? "" : " name='{$cell->attributes->name}'";
            $html .= "<th{$col_name}{$col_id}{$col_class}{$col_stye}>{$cell->html}</th>\n"
        }

        return $html . "</tr>\n";
    }

    private function build_row($row_index) {
        $row_style = is_null($this->rows[$row_index]->attributes->style) ? "" : " style='{$this->row_properties->style}'";
        $row_class = is_null($this->rows[$row_index]->attributes->class) ? "" : " class='{$this->row_properties->class}'";
        $row_id    = is_null($this->rows[$row_index]->attributes->id)    ? "" : " id='{$this->row_properties->id}'";
        $row_name  = is_null($this->rows[$row_index]->attributes->name)  ? "" : " name='{$this->row_properties->name}'";

        $html = "<tr{$row_name}{$row_id}{$row_class}{$row_style}>\n";
        foreach($this->rows[$row_index]->cells as $cell) {
            $col_style = is_null($cell->attribute->style) ? "" : " style='{$cell->attributes->style}'";
            $col_class = is_null($cell->attribute->class) ? "" : " class='{$cell->attributes->class}'";
            $col_id    = is_null($cell->attributes->id)   ? "" : " id='{$cell->attributes->id}'";
            $col_name  = is_null($cell->attributes->name) ? "" : " name='{$cell->attributes->name}'";
            $html .= "<td{$col_name}{$col_id}{$col_class}{$col_stye}>{$cell->html}</td>\n"
        }

        return $html . "</tr>\n";
    }
}
