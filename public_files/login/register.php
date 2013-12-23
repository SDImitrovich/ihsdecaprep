<?php
// include shared code
include '../../lib/common.php';
include '../../lib/db.php';
include '../../lib/functions.php';
include '../../lib/User.php';

// start or continue session so the CAPTCHA text stored in $_SESSION is
// accessible
session_start();
header('Cache-control: private');

// prepare the registration form's HTML
ob_start();
?>
<form method="post"
 action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
 <table>
  <tr>
    <td><label for="firstname">First Name</label></td>
	<td><input type="test" name="firstname" id="firstname" value="" /></td>
  </tr><tr>
    <td><label for="lastname">Last Name</label></td>
	<td><input type="test" name="lastname" id="lastname" value="" /></td>
  </tr><tr>
   <td><label for="username">Username</label></td>
   <td><input type="text" name="username" id="username"
    value="<?php if (isset($_POST['username']))
    echo htmlspecialchars($_POST['username']); ?>"/></td>
  </tr><tr>
   <td><label for="password1">Password</label></td>
   <td><input type="password" name="password1" id="password1"
    value=""/></td>
  </tr><tr>
   <td><label for="password2">Password Again</label></td>
   <td><input type="password" name="password2" id="password2"
    value=""/></td>
  </tr><tr>
   <td><label for="email">Email Address</label></td>
   <td><input type="text" name="email" id="email" 
    value="<?php if (isset($_POST['email']))
    echo htmlspecialchars($_POST['email']); ?>"/></td>
  </tr><tr>
   <td><label for="captcha">Verify</label></td>
   <td>Enter text seen in this image<br/ > 
   <img src="../img/captcha.php?nocache=<?php echo time(); ?>" alt=""/><br />
   <input type="text" name="captcha" id="captcha"/></td>
  </tr><tr>
   <td> </td>
   <td><input type="submit" value="Sign Up"/></td>
   <td><input type="hidden" name="submitted" value="1"/></td>
  </tr><tr>
 </table>
</form>
<?php
$form = ob_get_clean(); 

// show the form if this is the first time the page is viewed
if (!isset($_POST['submitted']))
{
    $GLOBALS['TEMPLATE']['content'] = $form;
}

// otherwise process incoming data
else
{
    // validate password
    $password1 = (isset($_POST['password1'])) ? $_POST['password1'] : 'not set 1';
    $password2 = (isset($_POST['password2'])) ? $_POST['password2'] : 'not set 2';
    $password = ($password1 && $password1 == $password2) ?
        sha1($password1) : '';
		
    // validate CAPTCHA
    $captcha = (isset($_POST['captcha']) && 
        strtoupper($_POST['captcha']) == $_SESSION['captcha']);

    // add the record if all input validates
    if ($password &&
        $captcha &&
        User::validateUsername($_POST['username']) &&
        User::validateEmailAddr($_POST['email']))
    {
        // make sure the user doesn't already exist
        $user = User::getByUsername($_POST['username']);
        if ($user->id)
        {
            $GLOBALS['TEMPLATE']['content'] = '<p><strong>Sorry, that ' .
                'account already exists.</strong></p> <p>Please try a ' .
                'different username.</p>';
            $GLOBALS['TEMPLATE']['content'] .= $form;
        }
        else
        {
            // create an inactive user record
            $u = new User();
            $u->firstName = $_POST['firstname'];
			$u->lastName = $_POST['lastname'];
			$u->username = $_POST['username'];
            $u->password = $password;
            $u->emailAddr = $_POST['email'];
            $token = $u->setInactive();

            $GLOBALS['TEMPLATE']['content'] = '<p><strong>Thank you for ' .
                'registering.</strong></p> <p>Be sure to verify your ' .
                'account by visiting <a href="verify.php?uid=' . 
                $u->id . '&token=' . $token . '">verify.php?uid=' .
                $u->id . '&token=' . $token . '</a></p>';
				
			/*
			// email registration
			$subject = 'Activate your IHS DECA Prep account';
			$plainTextMessage = 'Thank you for signing up for an account!  Before you '.
				' can login you need to verify your account. You can do so ' .
				'by visiting http://www.example.com/verify.php?uid=' .
			$user->id . '&token=' . $token . '.';
			if (send_mixed_mime_email($user->emailAddr, 'Activate your new account', $message))
			{
				$GLOBALS['TEMPLATE']['content'] = '<p><strong>Thank you for ' .
					'registering.</strong></p> <p>You will be receiving an ' .
					'email shortly with instructions on activating your ' .
					'account.</p>';
			}
			else
			{
				$GLOBALS['TEMPLATE']['content'] = '<p><strong>There was an ' .
					'error sending you the activation link.</strong></p> ' .
					'<p>Please contact the site administrator at <a href="' .
					'mailto:admin@example.com">admin@example.com</a> for ' .
					'assistance.</p>';
			}
			*/				
         }
    }
    // there was invalid data
    else
    {
        $GLOBALS['TEMPLATE']['content'] = '<p><strong>You provided some ' .
            'invalid data.</strong></p> <p>Please fill in all fields ' .
            'correctly so we can register your user account.</p>';
        $GLOBALS['TEMPLATE']['content'] .= $form;
    }
}

// display the page
include '../../templates/template-page.php';
?>
