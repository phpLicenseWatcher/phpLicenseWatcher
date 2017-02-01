<?php

if(!is_readable('./config.php')){
	print("</head><body>");
	print("<h2>Error: Configuration file config.php does not exist. Please notify your system administrator.</h2>");
	print("</body></html>\n");
	exit;
}else{
	include_once('./config.php');
}

function print_header($title)
{
	print("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"DTD/xhtml1-strict.dtd\">\n");
	print("<html><head><link rel=\"stylesheet\" href=\"style.css\" type=\"text/css\"/><title>".htmlspecialchars($title)."</title>\n");
}

function print_sql ($sql) {
	print "<font color=red>Executing SQL: </font> <font color=blue>" . $sql . "</font><br>\n";
}

?>
