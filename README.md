pdoServiceManipulation

Class of manipulating objects for inclusion in the database, validating the data type. 
This class allows the insertion of data into a database through a type stdClass object. Already with the data type validation , preparing the data to be entered into the database. 
Example: 

include_once "DBPDO.class.php";
$con = new DBPDO("host","database_name","user","password");
$data = new stdClass();
$data->coluna = "value";
$return = $con->insert("table",$data);
