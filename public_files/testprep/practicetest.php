<?php
// include shared code
include '../../lib/common.php';
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

// start with defaults
$cluster = new Cluster();
$inClusterQsPercentage = 60; // by default, get 60% of questions from the selected cluster
$totalQsCount = 100; // each practice test will be made of 100 questions

// parameters passed via URL take precedence over everything else
// we expect at least one parameter: cluster ID
if (isset($_GET['id']) )
{
	// if the ID is real, we'll get a Cluster instance,
	// otherwise we'll get an empty new instance
	$cluster = Cluster::getById(getGetVarTrimmedOrEmpty('id'));
}
// if this is after the POST call, we have cluster selected from the dropdown
else if (isset($_POST['submitted']))
{
	$clusterId = (isset($_POST['cluster'])) ? getPostVarTrimmedOrEmpty('cluster') : 0;
	$cluster = Cluster::getById($clusterId);
}

// additionally, there can be one optional parameter: 
// incluster (0-100), identifying the ratio of questions 
// in the generated practice test that are from selected cluster
if (isset($_GET['incluster']) )
{
	// if count is passed = get its value
	$inClusterQsPercentage = intval(getGetVarTrimmedOrEmpty('incluster') );	
}
// the percentage of in-cluster questions cannot exceed 100%
if ($inClusterQsPercentage > 100) { $inClusterQsPercentage = 100; }

// buffer the output and prepare the HTML
ob_start();
?>
<form method="post" id="selectClusterForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
 <h1>OK, let's take that test!</h1>
 <table>
  <tr>
    <td><label for="clustername">Select Cluster</label></td>
	<td>
		<select name="cluster" id="cluster">
			<?php
				// list all clusters in a dropdown
				// if the ID was passed in URL, select the appropriate cluster
				$clusters = Cluster::getAll();
				foreach ($clusters as $c)
				{
					// if the id's match - select this line in the dropdown
					if ($cluster->id == $c->id) { echo sprintf('<option value="%s" selected="1">%s</option>', $c->id, $c->name); }
					else { echo sprintf('<option value="%s">%s</option>', $c->id, $c->name); }
				}
			?>
		</select>
	</td>
  </tr>
  <tr>
   <td> </td>
   <td><input type="submit" value="List Practice Questions"/></td>
   <td><input type="hidden" name="submitted" value="1"/></td>
  </tr>
 </table>
</form>

<?php

// now, list the questions for this cluster, if any is selected
$questions = array();
if ($cluster->id)
{
	// first, get all questions for the specific cluster and select a subset of them
	// according to the specified percentage
	$inClusterQs = Question::getByClusterId($cluster->id);
	$qCount = round(count($inClusterQs) * $inClusterQsPercentage/$totalQsCount);
	$inClusterQsSelected = random_range($inClusterQs, $qCount);
	$questions = $inClusterQsSelected;
//echo sprintf('<p>Total questions for this cluster: %d</p>', count($inClusterQs) );
//echo sprintf('<p>Selected questions for this cluster: %d</p>', count($inClusterQsSelected) );
	// if some non-cluster questions are required - get them
	if ($inClusterQsPercentage < 100)
	{
		$outClusterQs = Question::getNotClusterId($cluster->id);
		$qCount = 100 - $qCount;
		$outClusterQsSelected = random_range($outClusterQs, $qCount);
//echo sprintf('<p>Total questions outside of this cluster: %d</p>', count($outClusterQs) );
//echo sprintf('<p>Selected questions outside of this cluster: %d</p>', count($outClusterQsSelected) );
		$questions = array_merge($inClusterQsSelected, $outClusterQsSelected);
		shuffle($questions);
	}

//echo sprintf('<p>Total questions for this test: %d</p>', count($questions) );
}
?>

<form method="post" id="questionsForm" action="validatetest.php">
 <h2>Here are your questions:</h2>
 <table>
<?php
	// list questions
	$answerLetters = ['A', 'B', 'C', 'D'];
	foreach ($questions as $q)
	{
		// record the question id, so that it is passed to the validation page
		echo sprintf('<tr><td colspan="3"><input type="hidden" name="question%d" value="%d"/></td></tr>', $q->id, $q->id);
		echo sprintf('<tr><td colspan="2"><strong>Content:</strong></td><td>%s</td></tr>', $q->content);
		// list all answers 
		$q->loadAnswers();
		$i = 0;
		foreach ($q->answers as $a)
		{
			echo '<tr>';
			echo sprintf('<td><label>%s:</label></td>', $answerLetters[$i]);
			echo sprintf('<td><label>%s</label></td>', $a->content);
			echo sprintf('<td><input type="checkbox" name="answer%d" id="answer%d" value="%d"/></td>', $a->id, $a->id, $a->id ); 
			echo '</tr>';
			$i++;
		}
		echo '<tr><td colspan="3"><hr/></td></tr>';
	}
?>
  <tr>
   <td> </td>
   <td><input type="submit" value="Evaluate"/></td>
   <td><input type="hidden" name="submitted" value="1"/></td>
  </tr>
 </table>
</form>
<?php

$GLOBALS['TEMPLATE']['content'] = ob_get_clean();

// display the page
include '../../templates/template-page.php';
?>
