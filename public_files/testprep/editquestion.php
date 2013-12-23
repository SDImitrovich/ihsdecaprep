<?php
// include shared code
include '../../lib/common.php';
include '../../lib/db.php';
include '../../lib/functions.php';
require_once '../../lib/Cluster.php';
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

//echo var_dump($_POST);

// start with a new Question
$headline = "Add new question";
$question = new Question();
$question->initAnswers(4);

// if question id is specified - we are in Edit mode;
// load the question and set the form values accordingly
if (isset($_GET['id']) || isset($_POST['id']))
{
	$headline = "Edit question";
	if (isset($_GET['id'])) { $question = Question::getById($_GET['id']); } // id in URL
	else { $question = Question::getById($_POST['id']); } // id in the form - updated values are coming...
	$question->loadAnswers();
}

// if this page is called from some other place and the referer wants us to come back
// after the Edit is done, the 'url' parameter will be passed on query string
// we'll save it into a hidden form field so that it is available to us after POST
$refererUrl = '';
if (isset($_GET['url']) )
{
	$refererUrl = $_GET['url'];
}

// init the output
$GLOBALS['TEMPLATE']['content'] = '';

// if this is after the POST call, we have some input data - let's process it!
if (isset($_POST['submitted']))
{
	$errMsg = '';
	$question->content = getPostVarTrimmedOrEmpty('content');
	if (strlen($question->content) == 0) { $errMsg .= "<p>We'll need some content for this question :-)</p>"; }
	
	$question->clusterId = (isset($_POST['cluster'])) ? trim($_POST['cluster']) : 0;
	if ($question->clusterId == 0) { $errMsg .= "<p>Make sure you select a cluster.  Question's no good without a cluster, you know...</p>"; }

	$i = 1;
	foreach ($question->answers as $a) 
	{
		$a->content = getPostVarTrimmedOrEmpty('answer'.$i);
		$a->isCorrect = isset($_POST['isCorrect'.$i]);
		$i++;
	}

//	var_dump($question);
//	var_dump($question->answers);

	if(!$question->validate())	{ $errMsg .= "<p>Something's wrong with your answers.  Make sure all of them have content and at least one of them is marked as 'correct'.</p>"; }
	
    // add the record if all input validates
    if (strlen($errMsg) == 0)
    {
		$question->additionalInfo = getPostVarTrimmedOrEmpty('additionalInfo');
		$question->save();

		// here we need to determine where to go after successful Save
		// if referer's URL was passed to us, we'd want to go back to that URL
		$url = getPostVarTrimmedOrEmpty('referer');
		if (strlen($url))
		{
			header(sprintf('Location: %s', $url));
		}
		// otherwise, display confirmation and go back to the Edit mode on this page
		else
		{
			$GLOBALS['TEMPLATE']['content'] .= '<p><strong>Question saved.</strong></p>';
		}
    }
    // there was invalid data
    else
    {
        $GLOBALS['TEMPLATE']['content'] .= $errMsg;
        $GLOBALS['TEMPLATE']['content'] .= '<p>Please fill in all fields ' .
            'correctly so we can add this question.</p>';
    }
}

// buffer the output and prepare the form's HTML
ob_start();

/*
echo 'POST: <br/>';
var_dump($_POST);
echo 'GET: <br/>';
var_dump($_GET);
*/

?>
<form method="post" id="questionForm"
 action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
 <h1><?php echo htmlspecialchars($headline);?></h1>
 <table>
  <tr>
    <td><label for="clustername">Select Cluster</label></td>
	<td>
		<select name="cluster" id="cluster">
			<?php
				// list all clusters in a dropdown and select the one that matches the question's current cluster
				$clusters = Cluster::getAll();
				foreach ($clusters as $c)
				{
					if ($question->clusterId == $c->id) { echo sprintf('<option value="%s" selected="1">%s</option>', $c->id, $c->name); }
					else { echo sprintf('<option value="%s">%s</option>', $c->id, $c->name); }
				}
			?>
		</select>
	</td>
  </tr><tr>
    <td><label for="content">Content</label></td>
	<td><textarea name="content" id="content" form="questionForm"><?php echo htmlspecialchars($question->content); ?></textarea></td>
  </tr><tr>
    <td><label for="additionalInfo">Additional Information</label></td>
	<td><textarea name="additionalInfo" id="additionalInfo" form="questionForm"><?php echo htmlspecialchars($question->additionalInfo); ?></textarea></td>
  </tr>
  
  <?php
	$i = 1;
	foreach ($question->answers as $a)
	{
		echo '<tr>';
		echo sprintf('<td><label for="answer%d">Answer #%d:</label></td>', $i, $i);
		echo sprintf('<td><input type="text" name="answer%d" id="answer%d" value="%s" /></td>', $i, $i, $a->content);
		echo sprintf('<td><input type="checkbox" name="isCorrect%d" id="isCorrect%d" value="Correct Answer" %s /></td>', $i, $i, ($a->isCorrect ? "checked" : "") ); 
		echo '</tr>';
		$i++;
	}
  ?>
  
  <tr>
   <td> </td>
   <td><input type="submit" value="Save Question"/></td>
   <td><input type="hidden" name="submitted" value="1"/></td>
   <td><input type="hidden" name="id" value="<?php echo $question->id; ?>"/></td>   
   <td><input type="hidden" name="referer" value="<?php echo $refererUrl; ?>"/></td>   
  </tr>
 </table>
</form>
<?php
$form = ob_get_clean(); 

$GLOBALS['TEMPLATE']['content'] .= $form;

// display the page
include '../../templates/template-page.php';
?>