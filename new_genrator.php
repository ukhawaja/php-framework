<?php
function startsWith($haystack, $needle)
{
	return !strncmp($haystack, $needle, strlen($needle));
}

$databaseName = "tepal";
$dbhost = "localhost";
$dbUser = "root";
$dbPass = "root";
DEFINE ("DB_USER", $dbUser);
DEFINE ("DB_PASSWORD",$dbPass);
DEFINE ("DB_HOST", $dbhost);
DEFINE ("DB_NAME", $databaseName );
$LINK = mysql_connect (DB_HOST, DB_USER, DB_PASSWORD);
mysql_select_db(DB_NAME, $LINK);

$dir = dirname(__FILE__);
if(!(file_exists($dir."/".$databaseName)))
	mkdir($dir."/".$databaseName);
if(!(file_exists($dir."/".$databaseName."/classes")))
	mkdir($dir."/".$databaseName."/classes");
if(!(file_exists($dir."/".$databaseName."/classes/generated")))
	mkdir($dir."/".$databaseName."/classes/generated");
if(!(file_exists($dir."/".$databaseName."/classes/custom")))
	mkdir($dir."/".$databaseName."/classes/custom");

$rs = mysql_query("SHOW TABLES") or die(mysql_error());

$tables = array();
$cnt =0;
while ($row = mysql_fetch_array($rs))
	$tables[$cnt++] = $row[0];

$display ="";
$require = '$PARTIAL_FOLDER_PATH = $_SERVER["DOCUMENT_ROOT"]."/classes/custom";'."\r\n";
$require .= "require_once('mysql.php');\r\n";

foreach($tables as $table)
{
	$tab = '	';
	$org_table = $table;
	if(intval(startsWith($table, "tbl_")))
		$table = str_replace("tbl_", "", $table);
	else if(intval(startsWith($table, "tbl")))
		$table = str_replace("tbl", "", $table);

	$className = ucfirst($table);

	$filename = $dir . "/$databaseName/classes/generated/" . "class." . strtolower($className) . ".php";
	$partial_filename = $dir . "/$databaseName/classes/custom/" . "class.partial." . strtolower($className) . ".php";
	//exit;
	// if file exists, then delete it
	if(file_exists($filename))
	{
		unlink($filename);
	}
	if(file_exists($partial_filename))
	{
		unlink($partial_filename);
	}
	
	// open file in insert mode
	$file = fopen($filename, "w+");
	$partial_file = fopen($partial_filename, "w+");
	
	$filedate = date("d.m.Y");
	$display = "
<?php
/*
*
* -------------------------------------------------------
* CLASSNAME:        $className
* GENERATION DATE:  $filedate
* CLASS FILE:       $filename
* FOR MYSQL TABLE:  $table
* -------------------------------------------------------
*
*/
\r\nrequire_once(\$PARTIAL_FOLDER_PATH.'/class.partial.".strtolower($className).".php');\r\n\r\n";

	$partial_display = "
<?php
/*
*
* -------------------------------------------------------
* CLASSNAME:        Partial_$className
* GENERATION DATE:  $filedate
* CLASS FILE:       $filename
* FOR MYSQL TABLE:  $table
* -------------------------------------------------------
*
*/
\r\n";


	$display .= "class ".$className."Class\r\n{ \r\n";
	$partial_display .= "class Partial_".$className."Class\r\n{ \r\n";
	
	//echo "DESCRIBE ".$table."<br />";
	$rs = mysql_query("DESCRIBE ".$org_table) or die("Table issues:".mysql_error());
	
	
	$variables = array();
	$v=0;
	while($row = mysql_fetch_array($rs))
	{
		$display .= $tab."private $".$row[0].";\r\n";
		$variables[$v++] = $row[0];
	}
	$display .="\r\n$tab//partial variable\r\n";
	$display .= $tab.'private $partial;'."\r\n";
	
	$display .="\r\n$tab//Constructor\r\n";
	$display .= $tab.'public function __construct()'."\r\n$tab{\r\n";
	$display .= $tab.$tab.'if(class_exists("Partial_".__CLASS__))'."\r\n$tab$tab{\r\n";
	$display .= $tab.$tab.$tab.'$partial = "Partial_".__CLASS__;'."\r\n";
	$display .= $tab.$tab.$tab.'$this->partial = new $partial($this);'."\r\n";
	$display .= "$tab$tab}\r\n";
	$display .= "$tab}\r\n";
	
	$display .="\r\n$tab//Call Functions\r\n";
	$display .= $tab.'public function __call($method, $args)'."\r\n$tab{\r\n";
	$display .= $tab.$tab.'return call_user_func_array(array($this->partial, $method), $args);'."\r\n";
	$display .= "$tab}\r\n";
	
	$display .="\r\n$tab//get value\r\n";
	$display .= $tab.'public function __get($key)'."\r\n$tab{\r\n";
	$display .= $tab.$tab.'if($this->partial->$key != null)'."\r\n";
	$display .= $tab.$tab.$tab.'return $this->partial->$key;'."\r\n";
	$display .= $tab.$tab.'else if($this->partial->$key == null);'."\r\n";
	$display .= $tab.$tab.$tab.'return $this->$key;'."\r\n";
	$display .= "$tab}\r\n";
	
	$display .="\r\n$tab//Set value\r\n";
	$display .= $tab.'public function __set($key, $value)'."\r\n$tab{\r\n";
	$display .= $tab.$tab.'if($this->partial->$key != null)'."\r\n";
	$display .= $tab.$tab.$tab.'$this->partial->$key = $value;'."\r\n";
	$display .= $tab.$tab.'else if($this->partial->$key == null);'."\r\n";
	$display .= $tab.$tab.$tab.'$this->$key = $value;'."\r\n";
	$display .= "$tab}\r\n";
	
	$partial_display .="\r\n$tab//parent variable\r\n";
	$partial_display .= $tab.'private $_parent;'."\r\n";
	
	$partial_display .="\r\n$tab//Constructor\r\n";
	$partial_display .= $tab.'public function __construct(&$parent)'."\r\n$tab{\r\n";
	$partial_display .= $tab.$tab.'$this->_parent = $parent;'."\r\n";
	$partial_display .= "$tab}\r\n";
	
	$partial_display .="\r\n$tab//Call Functions\r\n";
	$partial_display .= $tab.'public function __call($method, $args)'."\r\n$tab{\r\n";
	$partial_display .= $tab.$tab.'if(function_exists( array($this->_parent, $method)))'."\r\n";
	$partial_display .= $tab.$tab.$tab.'return call_user_func_array(array($this->_parent, $method), $args );'."\r\n";
	$partial_display .= $tab.$tab.'else'."\r\n";
	$partial_display .= $tab.$tab.$tab.'trigger_error("Call to undefined method " . get_class($this->_parent) . "::" . $method, E_USER_ERROR);'."\r\n";
	$partial_display .= "$tab}\r\n";
	
	$partial_display .="\r\n$tab//Get value\r\n";
	$partial_display .= $tab.'public function __get($key)'."\r\n$tab{\r\n";
	$partial_display .= $tab.$tab.'if(isset($this->_parent->$key))'."\r\n";
	$partial_display .= $tab.$tab.$tab.'return $this->_parent->$key;'."\r\n";
	$partial_display .= $tab.$tab.'else'."\r\n";
	$partial_display .= $tab.$tab.$tab.'return $this->$key;'."\r\n";
	$partial_display .= "$tab}\r\n";
	
	$partial_display .="\r\n$tab//Set value\r\n";
	$partial_display .= $tab.'public function __set($key, $value)'."\r\n$tab{\r\n";
	$partial_display .= $tab.$tab.'if(isset($this->_parent->$key))'."\r\n";
	$partial_display .= $tab.$tab.$tab.'$this->_parent->$key = $value;'."\r\n";
	$partial_display .= $tab.$tab.'else'."\r\n";
	$partial_display .= $tab.$tab.$tab.'$this->$key = $value;'."\r\n";
	$partial_display .= "$tab}\r\n";
	
	$partial_display .="\r\n$tab//Below you can define your custom functions in it.\r\n";


	//Start CopyPostData function*********************************************************************
	$display .= "\r\n"."$tab//copyPostData(): This function copies the values from the web form by using POST method and populate the relavant variables.\r\n";
	$display .= "$tab"."function copyPostData() \r\n$tab"."{\r\n";
	for($i=1; $i<count($variables); $i++)
		$display .= $tab.$tab.'$this->'.$variables[$i].' = $_POST["'.$variables[$i].'"];'."\r\n";
	$display .= "$tab}\r\n";
	//END CopyPostData function***********************************************************************

	//Start copyGetData function**********************************************************************
	$display .= "\r\n"."$tab//copyGetData(): This function copies the values from the web form by using Get method and populate the relavant variables.\r\n";
	$display .= "$tab"."function copyGetData() \r\n$tab"."{\r\n";
	for($i=1; $i<count($variables); $i++)
		$display .= $tab.$tab.'$this->'.$variables[$i].' = $_GET["'.$variables[$i].'"];'."\r\n";
	$display .= "$tab}\r\n";
	//END copyGetData function***********************************************************************

	//Start Insert function**************************************************************************
	$display .= "\r\n"."$tab//insert():This function insert the data into table ($org_table).\r\n";
	$display .= "$tab"."function insert()\r\n $tab{\r\n";
	$display .= $tab.$tab.'$query="';
	$display .= "Insert into ".$org_table."\r\n$tab$tab$tab$tab$tab$tab$tab$tab(";
	for($i=1; $i<count($variables); $i++)
		$display .= "\r\n $tab$tab$tab$tab$tab$tab$tab$tab$tab".$variables[$i].",";
	$display = substr($display,0,strlen($display)-1);
	$display .= "\r\n $tab$tab$tab$tab$tab$tab$tab$tab)\r\n$tab$tab$tab$tab$tab$tab$tab$tab"."values\r\n$tab$tab$tab$tab$tab$tab$tab$tab(";
	for($i=1; $i<count($variables); $i++)
		$display .= "\r\n$tab$tab$tab$tab$tab$tab$tab$tab$tab".'\'".$this->'.$variables[$i].'."\',';
	$display = substr($display,0,strlen($display)-1);
	$display .= "\r\n$tab$tab$tab$tab$tab$tab$tab$tab".')";'."\r\n";
	$display .= $tab.$tab.'$obj_db = new DB();'."\r\n";
	$display .= $tab.$tab.'$obj_db->executeNonQuery($query);'."\r\n";
	$display .= $tab.$tab.'$this->'.$variables[0].' = $obj_db->last_insert_id();'."\r\n";
	$display .= $tab.$tab.'$obj_db->db_close();'."\r\n";
	$display .= $tab.$tab.'return $this->'.$variables[0].';'."\r\n";
	$display .= "$tab}\r\n";
	//END Insert Function***********************************************************************
	
	
	//Start delete Function***********************************************************************
	$display .= "\r\n"."$tab//delete():This function delete the data from the table ($org_table).\r\n";
	$display .= "$tab"."function delete(\$id)\r\n$tab{ \r\n";
	$display .= $tab.$tab.'$query="';
	$display .= "Delete from ".$org_table." WHERE ".$variables[0]."='\$id'\"; \r\n";
	$display .= $tab.$tab.'$obj_db = new DB();'."\r\n";
	$display .= $tab.$tab.'$obj_db->executeNonQuery($query);'."\r\n";
	$display .= $tab.$tab.'$obj_db->db_close();'."\r\n";
	$display .= "$tab}\r\n";
	//END Update Function***********************************************************************
	
	
	//Start Update Function***********************************************************************
	$display .= "\r\n"."$tab//update():This function update the data into table ($org_table).\r\n";
	$display .= "$tab"."function update(\$id)\r\n $tab"."{\r\n";
	$display .= $tab.$tab.'$query="';
	$display .= "UPDATE ".$org_table." SET ";
	for($i=1; $i<count($variables); $i++)
		$display .= "\r\n$tab$tab$tab$tab$tab$tab$tab$tab".$variables[$i]." = '\".\$this->$variables[$i].\"',";
	
	$display = substr($display,0,strlen($display)-1);		
	$display .= "\r\n$tab$tab$tab$tab$tab$tab$tab$tab"."WHERE\r\n$tab$tab$tab$tab$tab$tab$tab$tab$tab$variables[0]='\$id'\";\r\n";
	$display .= $tab.$tab.'$obj_db = new DB();'."\r\n";
	$display .= $tab.$tab.'$obj_db->executeNonQuery($query);'."\r\n";
	$display .= $tab.$tab.'$obj_db->db_close();'."\r\n";
	$display .= "$tab}\r\n";
	//END Update Function***********************************************************************

	//Start of select function***********************************************************************
	$res = "$" . "result = $" . "this->database->result;";
	$thisdbquery = "$" . "this->" . "database->query($" . "sql" . ")";
	$result = "$" . "result = ";
	$result1 = "$" . "result";
		
	$display .= "\r\n"."$tab//select():This function select the data from the table ($org_table).\r\n";
	$display.= $tab."function select(\$id)\r\n$tab{\r\n";
	$display.= $tab.$tab.'$sql = "SELECT * FROM '.$org_table.' WHERE '.$variables[0].' = \'$id\';";'."\r\n";
	$display.= $tab.$tab.'$obj_db = new DB();'."\r\n";
	$display.= $tab.$tab.'$obj_db->query($sql);'."\r\n"."\r\n";
	$display.= $tab.$tab.'$row = $obj_db->rsset();'."\r\n"."\r\n";
	
	for($i=0; $i<count($variables); $i++)
		$display .= $tab.$tab.'$this->'.$variables[$i].'= $row['.$variables[$i].'];'."\r\n";
	$display .= $tab.$tab.'$obj_db->db_close();'."\r\n";
	$display.="$tab}"."\r\n"."\r\n";
	//END select Function***********************************************************************

	//Start of GenrateGrid function***********************************************************************
	
	$display .= "\r\n"."$tab//GenrateGrid():This function select the data from the table ($org_table).\r\n";
	$display.= $tab.'function GenerateGrid($pageNo, $footerPage, $page, $primaryKey, $columns, $add=false, $edit=false, $delete=false, $view=false, $viewPage="", $status=false, $statusField="", $statusFormat="")'."\r\n$tab{\r\n";
	$display.= $tab.$tab.'$sql = "SELECT * FROM '.$org_table.'";'."\r\n";


	$display.= $tab.$tab.'$MyGrid = new Grid();'."\r\n";
	$display.= $tab.$tab.'$MyGrid->GenrateGrid($sql, $pageNo, $footerPage, $page, $primaryKey, $columns, $add, $edit, $delete, $view, $viewPage, $status, $statusField, $statusFormat);'."\r\n"."\r\n";

	$display.="$tab}"."\r\n"."\r\n";
	//END select Function***********************************************************************
	
	//Start of GenrateGrid function***********************************************************************
	
	$display .= "\r\n"."$tab//GenrateCombo():This function genrate the combo from the table ($org_table).\r\n";
	$display.= $tab.'function GenrateCombo($name, $value, $displayText,$valueText, $title= NULL, $startText= NULL, $onblur= NULL, $onchange= NULL)'."\r\n$tab{\r\n";
	$display.= $tab.$tab.'$sql = "SELECT * FROM '.$org_table.'";'."\r\n";

	$display.= $tab.$tab.'$obj_db = new DB();'."\r\n";
	$display.= $tab.$tab.'echo $obj_db->genrateCombo($sql, $name, $value, $displayText,$valueText, $title, $startText, $onblur, $onchange);'."\r\n"."\r\n";

	$display.="$tab}"."\r\n"."\r\n";
	//END select Function***********************************************************************
	
	//End of Class
	$display .= "}\r\n"."?>\r\n";
	$partial_display .= "}\r\n"."?>\r\n";
	
	//echo $display;

	fwrite($file, $display);
	fwrite($partial_file, $partial_display);
	
	echo "File Created: ".$filename."<br />";
	$require .= "require_once('generated/class." . strtolower($className) . ".php');\r\n";
	$mainClass .= '$'. $className . " = new ".$className."Class();\r\n";
	fclose($file);

}
//$dir = dirname(__FILE__);

$mysql = file_get_contents($dir."/mysql.php", 'rb'); 
$mysql = str_replace("#databasename#","$databaseName",$mysql);
$mysql = str_replace("#databaseuser#","$dbUser",$mysql);
$mysql = str_replace("#databasepassword#","$dbPass",$mysql);

$filename = $dir . "/$databaseName/classes/" . "class.".$databaseName.".php";
$file = fopen($filename, "w+");
fwrite($file, "<?php\r\n$require\r\n".$mainClass."\r\n?>");

$filename = $dir . "/$databaseName/classes/mysql.php";
$file = fopen($filename, "w+");
fwrite($file, $mysql);

fclose($file);

/*
	Genrate combo
*/
?>
