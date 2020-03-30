<?php

if (is_readable(__DIR__ . '/vendor/autoload.php')) {
    require_once(__DIR__ . '/vendor/autoload.php');
} else {
    print <<< MSG
<h1>Missing Components</h1>
<p>Cannot find composer packages.  Please notify your system administrator.
MSG;
    print footer();
    exit;
}

if(!is_readable(__DIR__.'/config.php')){
	print("");
	print("<h2>Error: Configuration file config.php does not exist. Please notify your system administrator.</h2>");
	print("<?php echo footer(); ?>\n");
	exit;
}else{
	require_once(__DIR__.'/config.php');
}

function print_header($title)
{


        print file_get_contents(__DIR__.'/header.html');

}

function footer(){
    print file_get_contents(__DIR__.'/footer.html');
}

function print_sql ($sql) {
	print "<font color=red>Executing SQL: </font> <font color=blue>" . $sql . "</font><br>\n";
}

?>
