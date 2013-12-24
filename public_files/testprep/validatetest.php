<?php
// include shared code
include '../../lib/common.php';
include '../../lib/functions.php';
require_once '../../lib/Question.php';
require_once '../../lib/Answer.php';

// 401 file included because user should be logged in to access this page
include '../login/401.php';

/* I'll deal with permissions a bit later
// user must have appropriate permissions to use this page
$cluster = User::getById($_SESSION['id']);
if (~$user->permission & User::CREATE_FORUM)
{
    die('<p>Sorry, you do not have sufficient privileges to create new ' .
        'forums.</p>');
}
*/

header('Cache-control: private');

/*
echo '<p>POST:</p>';
var_dump($_POST);
*/

// will contain all questions that were part of the test
$questions = array();
// will contain all answers that were checked
$answers = array();

// if submitted is present - this is a result of posting from the practicetest.php
if (isset($_POST['submitted']))
{
	// list all questions that were in the practice test
	// as well as all answers that were checked
	// group answers by question id
	foreach($_POST as $key=>$value)
	{
		if (substr($key, 0, 8) == "question")
		{
			$qid = intval($value);
			$q = Question::getById($qid);
			array_push($questions, $q);
		}
		else if (substr($key, 0, 6) == "answer")
		{
			$aid = intval($value);
			$a = Answer::getById($aid);
			if (! isset($answers[$a->questionId]) ) { $answers[$a->questionId] = array(); }
			$answers[$a->questionId][$a->id] = $a;
		}
	}

/*
echo '<p>Questions:</p>';
var_dump($questions);
echo '<p>Answers:</p>';
var_dump($answers);
*/
}

// buffer the output and prepare the HTML
ob_start();

?>
<h1>Let's see how you have done...</h1>
<table>
	<tr><td colspan="4"><hr/></td></tr>
	<?php
		// for every question, list all answers with disabled checkboxes signifying user selection
		// and provide validation (correct/incorrect)
		// calculate total score, by awarding a point for every question that is correctly answered
		$totalCorrect = 0;
		$answerLetters = ['A', 'B', 'C', 'D'];
		foreach ($questions as $q)
		{
			$qCorrect = true;
			echo '<tr><td colspan="4"> </td></tr>';
			echo sprintf('<tr><td colspan="2"><strong>Question:</strong></td><td colspan="2">%s</td></tr>', $q->content);
			// list all answers 
			$q->loadAnswers();
			$i = 0;
			foreach ($q->answers as $a)
			{
				$aSelected = isset($answers[$a->questionId][$a->id]);
				// evaluate whether it should have been selected				
				$aCorrect = ($aSelected && $a->isCorrect) || (!$aSelected && !$a->isCorrect);
				$qCorrect = $qCorrect && $aCorrect;
				echo '<tr>';
				echo sprintf('<td><label>%s:</label></td>', $answerLetters[$i]);
				echo sprintf('<td><label>%s</label></td>', $a->content);	
				// make it checked if it was selected
				echo sprintf('<td><input type="checkbox" name="answer" disabled %s /></td>', ($aSelected) ? 'checked' : '' ); 
				echo sprintf('<td><label>%s</label></td>', ($aCorrect) ? '<font color="green">correct</font>' : '<font color="red">incorrect</font>');
				echo '</tr>';
				$i++;
			}
			if ($qCorrect) { $totalCorrect++; }
			echo '<tr><td colspan="4"><strong>Additional Information:</strong></td></tr>';
			echo sprintf('<tr><td colspan="4">%s</td></tr>', $q->additionalInfo);			
			echo '<tr><td colspan="4"> </td></tr>';
			echo '<tr><td colspan="4"><hr/></td></tr>';
		}		
		echo sprintf('<tr><td colspan="4"><strong>Questions answered correctly: %d out of total %d</strong></td></tr>', $totalCorrect, count($questions) );
	?>
</table>
<?php

$GLOBALS['TEMPLATE']['content'] = ob_get_clean();

// display the page
include '../../templates/template-page.php';
?>
