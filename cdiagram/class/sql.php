<?

#
# Ankopplung von cdiagram an einen sql-Server
#

class sql
{

var $m_SQLServerType;	# Type des SQL-Server
var $m_SQLServer;	# SQL-Server
var $m_UserName;	# ServerUser
var $m_Password;	# ServerPassword
var $m_SQLDatabase; 	# SQL-Datenbank
var $m_SQLXValueField; 	# Fieldname für X-Wert (Tabelle.Feld)
var $m_SQLYValueField; 	# Fieldname für Y-Wert (Tabelle.Feld)
var $m_WHEREClause;	# Optionale WHERE-Clause
var $m_ORDERClause;	# Order-Variable

function sql($ServerType,$Server,$Database,$SQLXField,$SQLYField)
{
	$this->m_SQLServerType=$ServerType;
	$this->m_SQLServer=$Server;
	$this->m_SQLDatabase=$Database;
	$this->m_SQLXValueField=$SQLXField;
	$this->m_SQLYValueField=$SQLYField;
}

function auth($UserName,$Password)
{
	$this->m_UserName=$UserName;
	$this->m_Password=$Password;
}

function where($WhereClause)
{
	$this->m_WHEREClause=$WhereClause;
}

function order($OrderClause)
{
	$this->m_ORDERClause=$OrderClause;
}

function generateDataSQLStatement()
{
 list($table1,$dummy)=split("\.",$this->m_SQLXValueField,2);
 list($table2,$dummy)=split("\.",$this->m_SQLYValueField,2);
 $tables=$table1;
 if ($tables != $table2)
 {
 	$tables=$tables.",".$table2;
 }
 $SQLStatement="SELECT ".$this->m_SQLXValueField." as sqlx,".$this->m_SQLYValueField." as sqly FROM ".$tables;
 if ($this->m_WHEREClause!="")
 {
 	$SQLStatement=$SQLStatement." WHERE ".$this->m_WHEREClause;
 }
 $SQLStatement=$SQLStatement." ".$this->m_ORDERClause;
 return $SQLStatement;
}

function getDataField()
{
 $DataArray=array();
 switch($this->m_SQLServerType)
 {
 	case "1":
		$DataArray=$this->getMySQLData();
	break;;
 }

return $DataArray;
}

function getMySQLData()
{
 $DataArray=array();
 $link=mysql_connect($this->m_SQLServer,$this->m_UserName,$this->m_Password);
 if (!$link)
 {
  echo "Sorry no SQL-Server Link<BR>";
  echo "Your SQL-Server: ".$this->m_SQLServer."<BR>";
  exit;
 }
 if (!mysql_query("USE ".$this->m_SQLDatabase,$link))
 {
  echo "Could not Connect to Database<BR>";
  echo "Your Database: ".$this->m_SQLDatabase;
  exit;
 }
 $result=mysql_query($this->generateDataSQLStatement(),$link);
 if (!$result)
 {
  echo "Sorry could not read the datas <BR>";
  echo "Your SQL-Statement: ".$this->generateDataSQLStatement()."<BR>";
  echo "Your SQL-Server: ".$this->m_SQLServer."<BR>";
  echo "Your SQL-Database: ".$this->m_SQLDatabase."<BR>";
  exit;
 }
 while ($row=mysql_fetch_array($result))
 {
	$DataArray[]=array($row[0],$row[1]);
 }
 return $DataArray;

}
}

?>
