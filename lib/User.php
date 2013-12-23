<?php

include 'Entity.php';

class User extends Entity
{
    // initialize a User object
    public function __construct()
    {
		parent::__construct();
    }

	protected function initFields()
	{
        $this->fields = array('username' => '',
                              'password' => '',
                              'emailAddr' => '',
							  'firstName' => '',
							  'lastName' => '',
                              'isActive' => false);
	}

    // return if username is valid format
    public static function validateUsername($username)
    {
        return preg_match('/^[A-Z0-9]{2,20}$/i', $username);
    }
    
    // return if email address is valid format
    public static function validateEmailAddr($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    // return an object populated based on the record's user id
    public static function getById($id)
    {
        $u = new User();

        $query = sprintf('SELECT * FROM USERS WHERE USER_ID = %d', $id);
        $result = mysql_query($query, $GLOBALS['DB']);

		User::setValuesFromDB($u, $result);
        mysql_free_result($result);

        return $u;
    }

    // return an object populated based on the record's username
    public static function getByUsername($username)
    {
        $u = new User();

        $query = sprintf('SELECT * FROM USERS WHERE USERNAME = "%s"',            
            mysql_real_escape_string($username, $GLOBALS['DB']));
        $result = mysql_query($query, $GLOBALS['DB']);

		User::setValuesFromDB($u, $result);
        mysql_free_result($result);
        return $u;
    }
	
	private static function setValuesFromDB($u, $result)
	{
        if (mysql_num_rows($result))
        {
            $row = mysql_fetch_assoc($result);
            $u->id = $row['USER_ID'];
            $u->username = $row['USERNAME'];
            $u->password = $row['PASSWORD'];
            $u->emailAddr = $row['EMAIL_ADDR'];
			$u->firstName = $row['FIRST_NAME'];
			$u->lastName = $row['LAST_NAME'];
            $u->isActive = $row['IS_ACTIVE'];
        }
	}
		
	// validates the internal values
	// returns TRUE if validation passes, FALSE otherwise
	// requirements:
	// username should not be empty
	// first name should not be empty
	// last name should not be empty
	// email should not be empty
	// password should not be empty
	public function validate()
	{
		return (strlen(trim($this->username)) > 0) &&
			(strlen(trim($this->password)) > 0) &&
			(strlen(trim($this->emailAddr)) > 0) &&
			(strlen(trim($this->firstName)) > 0) &&
			(strlen(trim($this->lastName)) > 0);
	}

    // save the record to the database
    public function save()
    {
        if ($this->id)
        {
            $query = sprintf('UPDATE USERS SET USERNAME = "%s", ' .
                'PASSWORD = "%s", EMAIL_ADDR = "%s", FIRST_NAME = "%s", LAST_NAME = "%s", IS_ACTIVE = %d ' .
                'WHERE USER_ID = %d',
                mysql_real_escape_string($this->username, $GLOBALS['DB']),
                mysql_real_escape_string($this->password, $GLOBALS['DB']),
                mysql_real_escape_string($this->emailAddr, $GLOBALS['DB']),
                mysql_real_escape_string($this->firstName, $GLOBALS['DB']),
                mysql_real_escape_string($this->lastName, $GLOBALS['DB']),
                $this->isActive,
                $this->id);
            mysql_query($query, $GLOBALS['DB']);
        }
        else
        {
            $query = sprintf('INSERT INTO USERS (USERNAME, PASSWORD, ' .
                'EMAIL_ADDR, FIRST_NAME, LAST_NAME, IS_ACTIVE) VALUES ("%s", "%s", "%s", "%s", "%s", %d)',
                mysql_real_escape_string($this->username, $GLOBALS['DB']),
                mysql_real_escape_string($this->password, $GLOBALS['DB']),
                mysql_real_escape_string($this->emailAddr, $GLOBALS['DB']),
                mysql_real_escape_string($this->firstName, $GLOBALS['DB']),
                mysql_real_escape_string($this->lastName, $GLOBALS['DB']),
                $this->isActive);
            mysql_query($query, $GLOBALS['DB']);

            $this->id = mysql_insert_id($GLOBALS['DB']);
        }
    }

    // set the record as inactive and return an activation token
    public function setInactive()
    {
        $this->isActive = false;
        $this->save(); // make sure the record is saved

        $token = random_text(5);
        $query = sprintf('INSERT INTO PENDING_CONFIRMATIONS (USER_ID, TOKEN) ' . 
            'VALUES (%d, "%s")',
            $this->id,
            $token);
        mysql_query($query, $GLOBALS['DB']);

        return $token;
    }

    // clear the user's pending status and set the record as active
    public function setActive($token)
    {
        $query = sprintf('SELECT TOKEN FROM PENDING_CONFIRMATIONS WHERE USER_ID = %d ' . 
            'AND TOKEN = "%s"',
            $this->id,
            mysql_real_escape_string($token, $GLOBALS['DB']));
        $result = mysql_query($query, $GLOBALS['DB']);

        if (!mysql_num_rows($result))
        {
            mysql_free_result($result);
            return false;
        }
        else
        {
            mysql_free_result($result);
            $query = sprintf('DELETE FROM PENDING_CONFIRMATIONS WHERE USER_ID = %d ' .
                'AND TOKEN = "%s"', 
                $this->id,
                mysql_real_escape_string($token, $GLOBALS['DB']));
            mysql_query($query, $GLOBALS['DB']);

            $this->isActive = true;
            $this->save();
            return true;
        }
    }
}
?>
