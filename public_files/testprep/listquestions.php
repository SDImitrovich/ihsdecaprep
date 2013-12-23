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

$cluster = new Cluster();
// if id is specified in URL - this takes precedence over everything else
if (isset($_GET['id']) )
{
	// if the ID is real, we'll get a Cluster instance,
	// otherwise we'll get an empty new instance
	$cluster = Cluster::getById($_GET['id']);
}

// if this is after the POST call, we have some input data - let's process it!
if (isset($_POST['submitted']))
{
	// if a cluster hasn't bee set yet (i.e. no valid cluster ID was passed in URL),
	// get its id from the dropdown
	if (! $cluster->id)
	{
		$clusterId = (isset($_POST['cluster'])) ? trim($_POST['cluster']) : 0;
		$cluster = Cluster::getById($clusterId);
	}
}

// buffer the output and prepare the HTML
ob_start();
?>
<form method="post" id="questionForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
 <h1>OK, let's edit some questions!</h1>
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
   <td><input type="submit" value="List Questions"/></td>
   <td><input type="hidden" name="submitted" value="1"/></td>
  </tr>
 </table>
</form>

<?php

// now, list all the questions for this cluster, if any is selected
echo '<table>';
$questions = Question::getByClusterId($cluster->id);
foreach ($questions as $q)
{
	$q->loadAnswers();
	echo '<tr><td colspan="3"> </td></tr>';
	echo sprintf('<tr><td colspan="2"><strong>Content:</strong></td><td>%s</td></tr>', $q->content);
	// list all answers 
	$i = 1;
	foreach ($q->answers as $a)
	{
		echo sprintf('<tr><td width="20"></td><td><strong>Answer #%d%s:</strong></td><td>%s</td>', $i, ($a->isCorrect) ? ' (correct)' : '', $a->content);
		$i++;
	}
	echo '<tr><td colspan="3"><strong>Additional Information:</strong></td></tr>';
	echo sprintf('<tr><td colspan="3">%s</td></tr>', $q->additionalInfo);
	// refer back to this page, saving cluster ID in the URL as well
	$refererUrl = htmlspecialchars(sprintf('%s?id=%d', $_SERVER['PHP_SELF'], $cluster->id) );
	echo sprintf('<tr><td colspan="2"><a href="editquestion.php?id=%d&url=%s">Edit Question</a></td></tr>', $q->id, $refererUrl);
	echo '<tr><td colspan="3"><hr/></td></tr>';
}
echo '</table>';

$GLOBALS['TEMPLATE']['content'] = ob_get_clean();

// display the page
include '../../templates/template-page.php';
?>
