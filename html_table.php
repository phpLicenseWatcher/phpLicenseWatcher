<?php

/* ------------------------------------------------------------------------- **
Quick Example:

require_once "html_table.php";

$my_table = new html_table();
$my_table->set_table_properties($style, $class, $id, $name);

$my_table->set_row_properties($style, $class, $id, $name);
$my_table->add_cell("Animals");
$my_table->add_cell("Vegetables");
$my_table->add_cell("Minerals");
$my_table->set_header();

$my_table->set_row_properties($style, $class, $id, $name);
$my_table->add_cell("dog");
$my_table->add_cell("tree");
$my_table->add_cell("rock");
$my_table->add_row();

$my_table->set_row_properties($style, $class, $id, $name);
$my_table->add_cell("cat");
$my_table->add_cell("grass");
$my_table->add_cell("sand");
$my_table->add_row();

$html = $my_table->build_html();
** ------------------------------------------------------------------------- */

class html_table {
    private $table_properties;
    private $row_properties;
    private $header;
    private $rows;
    private $cells;

    public function __construct() {
        $this->table_properties = new StdClass();
        $this->set_table_properties(null, null, null, null);

        $this->row_properties = new stdClass();
        $this->set_row_properties(null, null, null, null);

        $this->header = array();
        $this->rows = array();
        $this->cells = array();
    }


    public function set_table_properties(string $style=null, string $class=null, string $id=null, string $name=null) {
        $this->table_properties->style = $style;
        $this->table_properties->class = $class;
        $this->table_properties->id = $id;
        $this->table_properties->name = $name;
    }

    public function set_row_properties(string $style=null, string $class=null, string $id=null, string $name=null) {
        $this->row_properties->style = $style;
        $this->row_properties->class = $class;
        $this->row_properties->id = $id;
        $this->row_properties->name = $name;
    }

    public function set_header() {
        $this->header = new stdClass();
        $this->header->properties = $this->row_properties;
        $this->header->cells = $this->cells;
        $this->new_row();
    }

    public function add_row() {
        $this->row[] = new stdClass();
        $index = array_key_last($this->row);
        $this->row[$index]->properties = $this->row_properties;
        $this->row[$index]->cells = $this->cells;
        $this->new_row();
    }

    public function add_cell(string $html_content, $style=null, $class=null, $id=null, $name=null) {
        $this->cells[] = new stdClass();
        $index = array_key_last($this->cells);
        $this->cells[$index]->html = $html_content;
        $this->cells[$index]->style = $style;
        $this->cells[$index]->class = $class;
        $this->cells[$index]->id = $id;
        $this->cells[$index]->name = $name;
    }

    public function build_html() {
    }

    private function build_row($row_index) {
        $row_tag = $row_index === "header" ? "th" : "td";

        $row_style = is_null($this->row_properties->style) ? "" : " style='{$this->row_properties->style}'";
        $row_class = is_null($this->row_properties->class) ? "" : " class='{$this->row_properties->class}'";
        $row_id = is_null($this->row_properties->id) ? "" : " id='{$this->row_properties->id}'";
        $row_name = is_null($this->row_properties->name) ? "" : " name='{$this->row_properties->name}'";

        $html = "<tr{$row_name}{$row_id}{$row_class}{$row_style}>\n";
        foreach($this->rows[$row_index] as $cell) {
        }
    }

    private function new_row() {
        $this->set_row_properties(null, null, null, null);
        $this->cells = array();
    }
}
