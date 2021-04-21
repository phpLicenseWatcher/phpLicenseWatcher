<?php
print("<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n");

$configfile="../config.php" ; // must be writeable by the webserver (in configuration mode)

// read configfile variables (defaultvalues for that form)
include_once($configfile); // TODO: Check for errors

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head><link rel="stylesheet" href="style.css" type="text/css" />
<title>Phplicensewatcher - configuration</title>
<meta http-equiv="Content-Script-Type" content="text/javascript" />

</head>
<body>
<h1>Phplicensewatcher - configuration</h1>
<form method="post" action="writeconfig.php" enctype="application/x-www-form-urlencoded">


<fieldset><legend>Flexlm configuration</legend>
<label for="lmutil_loc">Flexlm lmutil binary:</label><input type="text" name="lmutil_loc" value=<?php print("\"$lmutil_loc\"") ; ?> id="lmutil_loc" title="absolute path to lmutil binary" />
<button type="button" name="setlmutildefault" onclick="this.form.lmutil_loc.value = '/usr/local/bin/lmutil'">default value</button>
<a href="http://www.macrovision.com/support/by_catagory/fnp_utilities_download.shtml#unixdownload">download lmutil</a><br /><br />
<!-- 
TODO: Currently the number of flexlm-licenses that this PHP-Application can configure is limited. How can we solve that? Javascript? Does someone have a solution?
TODO: improve Layout 
-->
<?php for ($i = 0; $i <= 9; $i++) {
?>
<div id=<? print "\"flexlm$i\""; ?>> 
Server: 
<input type="text" name="servers[]" size="35" title="Location of the license server ie. 27000@hostname.domain.com" value="<?php print $servers[$i] ; ?>" /> -- 
Description: <input type="text" name="description[]" title="Description of what kind of licenses this server handles" value="<?php print $description[$i] ; ?>" /> -- 
Log File: <input type="text" name="log_file[]" value="<?php print $log_file[$i] ; ?>" title="Log file for that license server" />
 <input type="checkbox" name="active[]" value="1" />Deactivate
<br />
</div>
<?php } ?>
</fieldset>





<fieldset><legend>IBM License use management</legend>
<label for="i4blt_loc">i4blt binary:</label><input type="text" name="i4blt_loc" value=<?php print("\"$i4blt_loc\"") ; ?> title="absolute path to i4blt binary" id="i4blt_loc" />
<button type="button" name="seti4bltdefault" onclick="this.form.i4blt_loc.value = '/opt/lum/ls/bin/i4blt'">default value</button> <a href="http://www-306.ibm.com/software/awdtools/lum/component.html">Download LUM</a><br /><br />
<label for="LUM_timeout">i4blt timeout:</label><input type="text" name="LUM_timeout" value=<?php print("\"$LUM_timeout\"") ; ?> id="LUM_timeout" /><br /></fieldset>




<fieldset><legend>Expire notification</legend>
<label for="notify_address">notify_address:</label><input type="text"  size="50" name="notify_address" value=<?php print("\"$notify_address\"") ; ?> title="E-Mail address to send notification of license expiration" id="notify_address" /><br /><br />
<label for="lead_time">Lead time:</label><input type="text" name="lead_time" value=<?php print("\"$lead_time\"") ; ?> id="lead_time" title="How far ahead should I warn before license expires" /> days<br /></fieldset>

<fieldset><legend>Extended configuration</legend>
<label for="disable_autorefresh">Disable autorefresh (0/1):</label><input type="text" name="disable_autorefresh" value=<?php print("\"$disable_autorefresh\"") ; ?> id="disable_autorefresh" /><br /><br />
<label for="disable_license_removal">Disable license removal (0/1):</label><input type="text" name="disable_license_removal" value=<?php print("\"$disable_license_removal\"") ; ?> id="disable_license_removal" /><br /></fieldset>





<fieldset><legend>Database for utilization statistics</legend>
<label for="collection_interval">Collection interval:</label><input type="text" name="collection_interval" value=<?php print("\"$collection_interval\"") ; ?> id="collection_interval" /><br /><br />
<label for="db_hostname">Database hostname:</label><input type="text" name="db_hostname" value=<?php print("\"$db_hostname\"") ; ?> id="db_hostname" />
<button type="button" name="setlocalhost" onclick="this.form.db_hostname.value = 'localhost'">localhost</button>
<br /><br />
<label for="db_username">Database username:</label><input type="text" name="db_username" value=<?php print("\"$db_username\"") ; ?> id="db_username" /><br /><br />
<label for="db_hostname">Database password:</label><input type="password" name="db_password" value=<?php print("\"$db_password\"") ; ?> id="db_password" /><br /><br />
<label for="db_database">Database name:</label><input type="text" name="db_database" value=<?php print("\"$db_database\"") ; ?> id="db_database" /><br /><br />
<label for="db_database">Database type:</label>
<select name="db_type" id="db_type">
<option value="mysql" <?php if ($db_type=="mysql") { print("selected=\"selected\"") ; } ; ?> >MySQL</option>
<option value="pgsql" <?php if ($db_type=="pgsql") { print("selected=\"selected\"") ; } ; ?> >Postgresql</option>
</select><br /><br />
<label for="create_tables">Should I create the required tables?</label><select name="create_tables" id="create_tables"><option value="no">no</option><option value="yes">yes</option></select> (works only for Mysql)<br /><br />  <!-- default always no - you only want to create the tables once... -->
<br />
<hr />
If I should create the database/user for you, enter the DB Adminuser and password. (works only for mysql)<br /><br />
<label for="create_database">Create database</label><select name="create_database" id="create_database"><option value="no">no</option><option value="yes">yes</option></select><br /><br /> <!-- default always no - you only want to create the tables once... -->

<label for="db_adminusername">Database admin username:</label><input type="text" name="db_adminusername" value="" id="db_adminusername" /><br /><br /> <!-- default always empty - not saved in config file for security reasons -->
<label for="db_adminpassword">Database admin password:</label><input type="password" name="db_adminpassword" value="" id="db_adminpassword" /><br /><br /> <!-- default always empty - not saved in config file for security reasons -->
</fieldset>



<fieldset><legend>Graphics and design</legend>
Different colors that can be used as backgrounds<br /><br />
<label for="colors">Colors:</label><input type="text" name="colors" value=<?php print("\"$colors\"") ; ?> id="colors" />
<br />  <!-- TODO: split and descripe these fields. -->
Graph sizes in pixels (x,y) user for license utilization graphs.<br /><br />
<label for="smallgraph">Small graph:</label><input type="text" name="smallgraph" value=<?php print("\"$smallgraph\"") ; ?> id="smallgraph" /><br /><br />
<label for="largegraph">Large graph:</label><input type="text" name="largegraph" value=<?php print("\"$largegraph\"") ; ?> id="largegraph" /><br /><br />
How many point on the X axis you want for a 24 hr period ie. show legend every for every 2 hours = 12 so you will see 2,4,6,8,10 etc. <br /><br />
<label for="legendpoints">Legend points:</label><input type="text" name="legendpoints" value=<?php print("\"$legendpoints\"") ; ?> id="legendpoints" />
</fieldset>

<p>
<input type="submit" name="submit" />
</p>
</form></body></html>
