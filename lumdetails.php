<?php

require_once("common.php");
print_header("LUM Licenses in Detail");

##############################################################
# We are using PHP Pear stuff ie. pear.php.net
##############################################################
require_once ("HTML/Table.php");

if ( isset($_GET['refresh']) && $_GET['refresh'] > 0 && ! $disable_autorefresh){
	echo('<meta http-equiv="refresh" content="' . intval($_GET['refresh']) . '"/>');
}
?>
</head>
<body>

<h1>LUM Licenses in Detail</h1>
<p class="a_centre"><a href="index.php"><img src="back.jpg" alt="up page"/></a></p>
<hr/>
<?php 
$tableStyle = "border=\"1\" cellpadding=\"1\" cellspacing=\"2\" ";

# Create a new table object
$table = new HTML_Table($tableStyle);

$table->setColAttributes(1,"align=\"center\"");

# Define a table header
$headerStyle = "";
$colHeaders = array("Product Name","Product Version", "Number of licenses", "In Use Licenses", "Expire Date");
$table->addRow($colHeaders, $headerStyle, "TH");


$fp = popen("TIMEOUT_FACTOR=2 ; export TIMEOUT_FACTOR ; " . $i4blt_loc . " -lp -i ", "r"); // FIXME: shell dependent code!
##################################
###### i4blt -lp -i outputs something like
// [...]
// ===========================================================================
// ===                            P r o d u c t s                          ===
// ===========================================================================
// 
//   Vendor Name:                  LicensePower/iFOR Test Vendor
//   Vendor ID:                    4ca0fd5cf000.0d.00.02.1a.9a.00.00.00
//   Product Name:                 LicensePower/iFOR Test Product
//   Product Version:              1.0  
//   Product ID:                   4     
//   Bundle Component:             No     
//   Licenses:                     10000
//   In Use Licenses:              0.00  
// 
//  --------------------------- License Information --------------------------
//   10000  Concurrent Access License(s)
//   Server:      ip:linux.site
//   Serial Number:       
//   Capacity Type: None       
//   Start Date:    Jan 02 1970 01:00:00         Exp. Date: Jan 18 2038 04:14:07
//   TimeStamp:     1124104374
//   Annotation:     4.6.8 LINUX; 608cf8f8
//   Multi-Use Rules: None                                                  
//   Security Level:   Vendor-Managed Use
//   Threshold:        80      
//   Soft Stop:        Disabled by Vendor
// 
//                          ===========================
//                          === End of Product List ===
//                          ===========================

while ( !feof ($fp) ) {
  
  $line = fgets ($fp, 1024);


  if ( eregi ("Vendor Name:", $line) ) {
    $vendorname = eregi_replace(".*Vendor Name:\ *", "", $line) ;
    $vendorid = fgets ($fp, 1024);  // Next line: vendorid
    $productname = eregi_replace(".*Product Name:\ *", "", fgets ($fp, 1024));  // Next line: product name
    $productversion = eregi_replace(".*Product Version:\ *", "", fgets ($fp, 1024));  // Next line: product version
    $productid = eregi_replace(".*Product ID:\ *", "", fgets ($fp, 1024));  // Next line: product version
    $bundlecomponent = eregi_replace(".*Bundle Component:\ *", "", fgets ($fp, 1024));  // Next line: product version
    $numberlicenses = eregi_replace(".*Licenses:\ *", "", fgets ($fp, 1024));  // Next line: product version
    $inuselicenses = eregi_replace(".*In Use Licenses:\ *", "", fgets ($fp, 1024));  // Next line: product version
    fgets ($fp, 1024);  // unused line
    fgets ($fp, 1024);  // unused line
    fgets ($fp, 1024);  // unused line
    fgets ($fp, 1024);  // unused line
    fgets ($fp, 1024);  // unused line
    fgets ($fp, 1024);  // unused line
    $expiretime = date('Y-m-d',strtotime(eregi_replace(".*Exp. Date:\ *", "", fgets ($fp, 1024))));  // Next line: expire time
    

    $table->AddRow(array($productname,$productversion,$numberlicenses,$inuselicenses, $expiretime));


  }




}  
pclose($fp);


# Display the table
$table->display();


?>


<?php




include_once('./version.php');

?>
</body>
</html>
