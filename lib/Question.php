<?php

require_once 'Entity.php';

class Question extends Entity
{
    // initialize a User object
    public function __construct()
    {
		parent::__construct();
    }

	protected function initFields()
	{
        $this->fields = array('clusterId' => 0,
                              'content' => '',
							  'additionalInfo' => '',
							  'answers' => null);
    }

    
    // return an object populated based on the record's id
    public static function getById($id)
    {
        $question = new Question();

        $query = sprintf('SELECT * FROM QUESTIONS WHERE ID = %d', $id);
        $result = mysql_query($query, $GLOBALS['DB']);

        if (mysql_num_rows($result))
        {
			$row = mysql_fetch_assoc($result);
			Question::setValuesFromDB($question, $row);
        }
        mysql_free_result($result);

        return $question;
    }

    // return an array of Question objects associated with the specified Cluster ID
    public static function getByClusterId($clusterId)
    {
        $query = sprintf('SELECT * FROM QUESTIONS WHERE CLUSTER_ID = %d', $clusterId);           
		return Question::getListBasedOnQuery($query);
    }
	

    // return an array of Question objects that are NOT associated with the specified Cluster ID
    public static function getNotClusterId($clusterId)
    {
        $query = sprintf('SELECT * FROM QUESTIONS WHERE CLUSTER_ID != %d', $clusterId);           
		return Question::getListBasedOnQuery($query);
    }
	
	private static function getListBasedOnQuery($query)
	{
        $questions = array();
        $result = mysql_query($query, $GLOBALS['DB']);
		
        if (mysql_num_rows($result))
        {
			while ($row = mysql_fetch_assoc($result))
			{	
				$q = new Question();
				Question::setValuesFromDB($q, $row);
				array_push($questions, $q);				
			}
		}
		mysql_free_result($result);
        return $questions;
	}
	
	private static function setValuesFromDB($question, $row)
	{
		$question->id = $row['ID'];
		$question->clusterId = $row['CLUSTER_ID'];
		$question->content = $row['CONTENT'];
		$question->additionalInfo = $row['ADDITIONAL_INFO'];
	}
	
	// creates an initial array of answers of the specified size
	public function initAnswers($size)
	{
		$arr = array();
		for ($i = 0; $i < $size; $i++)
		{
			array_push($arr, new Answer() );
		}
		$this->answers = $arr;
	}
	
	// loads answers associated with this question
	public function loadAnswers()
	{
		$this->answers = Answer::getByQuestionId($this->id);
	}
	
	// validates the internal values
	// returns TRUE if validation passes, FALSE otherwise
	// requirements:
	// content should not be empty
	// answers should be all valid
	// at least one answer should be marked as correct
	public function validate()
	{
		if ( (! $this->answers) || ! strlen(trim($this->content)) ) return false;
		
		$allValid = true;
		$atLeastOneCorrect = false;
		foreach ($this->answers as $a)
		{
			$allValid = $allValid && $a->validate();
			$atLeastOneCorrect = ($atLeastOneCorrect || $a->isCorrect);
		}
		return $allValid && $atLeastOneCorrect;
	}

    // save the record to the database
    public function save()
    {
        if ($this->id)
        {
            $query = sprintf('UPDATE QUESTIONS SET CLUSTER_ID = %d, CONTENT = "%s", ADDITIONAL_INFO = "%s" ' .
                'WHERE ID = %d',
				$this->clusterId,
                mysql_real_escape_string($this->content, $GLOBALS['DB']),
                mysql_real_escape_string($this->additionalInfo, $GLOBALS['DB']),
                $this->id);
//			echo sprintf("Question.save(): query='%s'", $query);
            mysql_query($query, $GLOBALS['DB']);
        }
        else
        {
            $query = sprintf('INSERT INTO QUESTIONS (CLUSTER_ID, CONTENT, ADDITIONAL_INFO) VALUES (%d, "%s", "%s")',
                $this->clusterId,
                mysql_real_escape_string($this->content, $GLOBALS['DB']),
                mysql_real_escape_string($this->additionalInfo, $GLOBALS['DB']));
//			echo sprintf("Question.save(): query='%s'", $query);
            mysql_query($query, $GLOBALS['DB']);

            $this->id = mysql_insert_id($GLOBALS['DB']);

			// assuming new record - if there are any answers - set their question id
			if ($this->answers)
			{
				foreach ($this->answers as $a)
				{
					$a->questionId = $this->id;
				}		
			}
		}

		// save all the answers as well (if any)
		if ($this->answers)
		{
			foreach ($this->answers as $a)
			{
				$a->save();
			}		
		}
    }
}
?>
