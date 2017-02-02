<?php

if(!is_readable('./config.php')){
	print("");
	print("<h2>Error: Configuration file config.php does not exist. Please notify your system administrator.</h2>");
	print("<?php echo footer(); ?>\n");
	exit;
}else{
	include_once('./config.php');
}

function print_header($title)
{

        
        print file_get_contents('header.html');
        
}

function footer(){
    print file_get_contents('footer.html');
}

function print_sql ($sql) {
	print "<font color=red>Executing SQL: </font> <font color=blue>" . $sql . "</font><br>\n";
}

?>
