<?php

require_once 'Entity.php';

class Answer extends Entity
{
    // initialize a User object
    public function __construct()
    {
		parent::__construct();
    }

	protected function initFields()
	{
        $this->fields = array('questionId' => 0,
                              'content' => '',
							  'isCorrect' => false);
    }

    
    // return an object populated based on the record's id
    public static function getById($id)
    {
        $answer = new Answer();

        $query = sprintf('SELECT * FROM ANSWERS WHERE ID = %d', $id);
        $result = mysql_query($query, $GLOBALS['DB']);

        if (mysql_num_rows($result))
        {
			$row = mysql_fetch_assoc($result);
			Answer::setValuesFromDB($answer, $row);
        }
		
        mysql_free_result($result);
        return $answer;
    }

    // return an array of Answer objects populated based on the question id
    public static function getByQuestionId($questionId)
    {
        $answers = array();

        $query = sprintf('SELECT * FROM ANSWERS WHERE QUESTION_ID = %d', $questionId);           
        $result = mysql_query($query, $GLOBALS['DB']);
		
        if (mysql_num_rows($result))
        {
			while ($row = mysql_fetch_assoc($result))
			{	
				$a = new Answer();
				Answer::setValuesFromDB($a, $row);
				// add the answer to the list, using its id as a key
				array_push($answers, $a);
			}
		}
		mysql_free_result($result);
        return $answers;
    }
	
	private static function setValuesFromDB($answer, $row)
	{
		$answer->id = $row['ID'];
		$answer->questionId = $row['QUESTION_ID'];
		$answer->content = $row['CONTENT'];
		$answer->isCorrect = $row['IS_CORRECT'];
	}

	// validates the internal values
	// returns TRUE if validation passes, FALSE otherwise
	// requirements:
	// content should not be empty
	public function validate()
	{
		return strlen(trim($this->content));
	}
	
    // save the record to the database
    public function save()
    {
        if ($this->id)
        {
            $query = sprintf('UPDATE ANSWERS SET QUESTION_ID = %d, CONTENT = "%s", IS_CORRECT = %d ' .
                'WHERE ID = %d',
				$this->questionId,
                mysql_real_escape_string($this->content, $GLOBALS['DB']),
                $this->isCorrect,
                $this->id);
//			echo sprintf("Answer.save(): query='%s'", $query);
            mysql_query($query, $GLOBALS['DB']);
        }
        else
        {
            $query = sprintf('INSERT INTO ANSWERS (QUESTION_ID, CONTENT, IS_CORRECT) VALUES (%d, "%s", %d)',
                $this->questionId,
                mysql_real_escape_string($this->content, $GLOBALS['DB']),
                $this->isCorrect);
//			echo sprintf("Answer.save(): query='%s'", $query);
            mysql_query($query, $GLOBALS['DB']);

            $this->id = mysql_insert_id($GLOBALS['DB']);
        }
    }
}
?>
