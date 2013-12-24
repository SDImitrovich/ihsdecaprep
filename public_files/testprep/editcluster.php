<?php
// include shared code
include '../../lib/common.php';
include '../../lib/functions.php';
require_once '../../lib/Cluster.php';

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

// start with a new Cluster
$headline = "Add new cluster";
$cluster = new Cluster();

// if question id is specified - we are in Edit mode;
// load the question and set the form values accordingly
if (isset($_GET['id']) )
{
	$cluster = Cluster::getById($_GET['id']); // id in URL
	// if the ID is set, we must've found the cluster, otherwise the ID was bogus, and we'll default to the Add mode
	if ($cluster->id) {	$headline = "Edit cluster"; }
}

// if this page is called from some other place and the referer wants us to come back
// after the Edit is done, the 'url' parameter will be passed on query string
// we'll save it into a hidden form field so that it is available to us after POST
$refererUrl = htmlspecialchars($_SERVER['PHP_SELF']);
if (isset($_GET['url']) )
{
	$refererUrl = getGetVarTrimmedOrEmpty('url');
}

// init the form
$GLOBALS['TEMPLATE']['content'] = '';

// if this "flag" is set - we have some incoming data as a result of POST (i.e. user input)
// let's process it before filling out the form
if (isset($_POST['submitted']))
{
	if (isset($_POST['id']))
	{
		$cluster = Cluster::getById($_POST['id']); // id in the form - updated values are coming...
	}
	
	$errMsg = '';

    // validate CAPTCHA
    $captcha = (isset($_POST['captcha']) && 
        strtoupper($_POST['captcha']) == $_SESSION['captcha']);
	if (! $captcha) { $errMsg .= "<p>Pay attention to captcha! Yeah, it may be annoying, but we don't want any bots creating bogus clusters over here... ;-)</p>"; }

	$cluster->name = getPostVarTrimmedOrEmpty('clustername');
	$cluster->description = getPostVarTrimmedOrEmpty('description');
	if(!$cluster->validate())	{ $errMsg .= "<p>Something's wrong with your cluster.  Make sure the name isn't blank and contains only English letter and numbers, starting with a Capital letter.</p>"; }
	
	// if this is a newly added cluster, make sure the name is unique
	if (!$cluster->id)
	{
		$c2 = Cluster::getByName($cluster->name);
		if ($c2->id) { $errMsg .= "<p><strong>This cluster already exists.</strong></p>"; }
	}
	
    // add the record if all input validates
    if (strlen($errMsg) == 0)
    {
		$cluster->save();

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
			$GLOBALS['TEMPLATE']['content'] .= '<p><strong>Cluster saved.</strong></p>';
		}
    }
    // there was invalid data
    else
    {
        $GLOBALS['TEMPLATE']['content'] .= $errMsg;
        $GLOBALS['TEMPLATE']['content'] .= '<p>Please fill in all fields ' .
            'correctly so we can add this cluster.</p>';
    }
}

// prepare the form's HTML
ob_start();

/*
echo 'POST: <br/>';
var_dump($_POST);
echo 'GET: <br/>';
var_dump($_GET);
*/

?>
<form method="post" id="clusterForm"
 action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
 <h1><?php echo htmlspecialchars($headline);?></h1>
 <table>
  <tr>
    <td><label for="clustername">Name</label></td>
	<td><input type="text" name="clustername" id="clustername" value="<?php echo $cluster->name; ?>" /></td>
  </tr><tr>
    <td><label for="description">Description</label></td>
	<td><input type="text" name="description" id="description" value="<?php echo $cluster->description; ?>" /></td>
  </tr><tr>
   <td><label for="captcha">Verify</label></td>
   <td>Enter text seen in this image<br/ > 
   <img src="../img/captcha.php?nocache=<?php echo time(); ?>" alt=""/><br />
   <input type="text" name="captcha" id="captcha"/></td>
  </tr><tr>
   <td> </td>
   <td><input type="submit" value="Save Cluster"/></td>
   <td><input type="hidden" name="submitted" value="1"/></td>   
   <td><input type="hidden" name="id" value="<?php echo $cluster->id; ?>"/></td>   
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
