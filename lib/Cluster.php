<?php

require_once 'Entity.php';

class Cluster extends Entity
{
    // initialize a User object
    public function __construct()
    {
		parent::__construct();
    }

	protected function initFields()
	{
        $this->fields = array('name' => '',
                              'description' => '');
    }

    // return if name is valid format
    public static function validateName($name)
    {
        return preg_match('/^[A-Z][A-Za-z0-9 ]{2,50}$/', $name);
    }

	    
    // return an array listing all available clusters
    public static function getAll()
    {
        $clusters = array();

        $query = 'SELECT * FROM CLUSTERS';           
        $result = mysql_query($query, $GLOBALS['DB']);
		
        if (mysql_num_rows($result))
        {
			while ($row = mysql_fetch_assoc($result))
			{	
				$c = new Cluster();
				Cluster::setValuesFromDB($c, $row);
				// add the question to the list, using its id as a key
				array_push($clusters, $c);
			}
		}
		mysql_free_result($result);
        return $clusters;		
    }

    
    // return an object populated based on the record's id
    public static function getById($id)
    {
        $cluster = new Cluster();

        $query = sprintf('SELECT * FROM CLUSTERS WHERE ID = %d', $id);
        $result = mysql_query($query, $GLOBALS['DB']);

        if (mysql_num_rows($result) && $row = mysql_fetch_assoc($result))
        {
			Cluster::setValuesFromDB($cluster, $row);
		}
        mysql_free_result($result);

        return $cluster;
    }

    // return an object populated based on the record's name (should be unique)
    public static function getByName($name)
    {
        $cluster = new Cluster();

        $query = sprintf('SELECT * FROM CLUSTERS WHERE NAME = "%s"',            
            mysql_real_escape_string($name, $GLOBALS['DB']));
        $result = mysql_query($query, $GLOBALS['DB']);

        if (mysql_num_rows($result) && $row = mysql_fetch_assoc($result))
        {
			Cluster::setValuesFromDB($cluster, $row);
		}
        mysql_free_result($result);
        return $cluster;
    }
	
	private static function setValuesFromDB($cluster, $row)
	{
		$cluster->id = $row['ID'];
		$cluster->name = $row['NAME'];
		$cluster->description = $row['DESCRIPTION'];
	}
	
	// validates the internal values
	// returns TRUE if validation passes, FALSE otherwise
	// requirements:
	// name should not be empty
	public function validate()
	{
		return (strlen(trim($this->name)) > 0) &&
			Cluster::validateName($this->name);
	}
	
    // save the record to the database
    public function save()
    {
        if ($this->id)
        {
            $query = sprintf('UPDATE CLUSTERS SET NAME = "%s", DESCRIPTION = "%s" ' .
                'WHERE ID = %d',
                mysql_real_escape_string($this->name, $GLOBALS['DB']),
                mysql_real_escape_string($this->description, $GLOBALS['DB']),
                $this->id);
//			echo sprintf("Cluster->save(): query='%s'", $query);
            mysql_query($query, $GLOBALS['DB']);
        }
        else
        {
            $query = sprintf('INSERT INTO CLUSTERS (NAME, DESCRIPTION) VALUES ("%s", "%s")',
                mysql_real_escape_string($this->name, $GLOBALS['DB']),
                mysql_real_escape_string($this->description, $GLOBALS['DB']));
            mysql_query($query, $GLOBALS['DB']);

            $this->id = mysql_insert_id($GLOBALS['DB']);
        }
    }
}
?>
