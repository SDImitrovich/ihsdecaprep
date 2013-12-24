<?php
// include shared code
include '../../lib/common.php';
include '../../lib/functions.php';
include '../../lib/Cluster.php';

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

$clusters = Cluster::getAll();

// prepare the HTML
ob_start();

echo '<h1>Here are the clusters we have in our system:</h1>';
echo '<table>';
echo '<tr><td colspan="2"> </td></tr>';
echo sprintf('<tr><td><a href="editcluster.php?url=%s">Add New Cluster</a></td></tr>', $_SERVER['PHP_SELF']);
foreach ($clusters as $c)
{
	echo '<tr><td colspan="2"> </td></tr>';
	echo sprintf('<tr><td><strong>Name:</strong></td><td>%s</td></tr>', $c->name);
	echo sprintf('<tr><td><strong>Description:</strong></td><td><em>%s</em></td></tr>', $c->description);
	echo sprintf('<tr><td><a href="editcluster.php?id=%d&url=%s">Edit Cluster</a></td></tr>', $c->id, $_SERVER['PHP_SELF']);
	echo '<tr><td colspan="2"><hr/></td></tr>';
}
echo '</table>';

$page = ob_get_clean(); 
$GLOBALS['TEMPLATE']['content'] = $page;

// display the page
include '../../templates/template-page.php';
?>
