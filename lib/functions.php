<?php
// return a string of random text of a desired length
function random_text($count, $rm_similar = false)
{
    // create list of characters
    $chars = array_flip(array_merge(range(0, 9), range('A', 'Z')));

    // remove similar looking characters that might cause confusion
    if ($rm_similar)
    {
        unset($chars[0], $chars[1], $chars[2], $chars[5], $chars[8],
            $chars['B'], $chars['I'], $chars['O'], $chars['Q'],
            $chars['S'], $chars['U'], $chars['V'], $chars['Z']);
    }

    // generate the string of random text
    for ($i = 0, $text = ''; $i < $count; $i++)
    {
        $text .= array_rand($chars);
    }

    return $text;
}

function getPostVarTrimmedOrEmpty($var)
{
	return (isset($_POST[$var])) ? trim($_POST[$var]) : '';
}

function getGetVarTrimmedOrEmpty($var)
{
	return (isset($_GET[$var])) ? trim($_GET[$var]) : '';
}

// generates a set of random numbers within specified array,
// without duplication
// they keys will not be preserved
function random_range($list, $count)
{
/*
echo '<h3>In random_range()</h3>';
echo '<p>Original array:</p>';
var_dump($list);
echo sprintf('<p>Count: %d</p>', $count);
*/
	// if count > to range, we can't generate $count of unique values
	if ($count > count($list) ) { return $list; }
	
	$retval = array();
	if (shuffle($list) )
	{
		// these are keys into the $keys array, so we'll need to do
		// double-dereferencing to get to actual values
		$randKeys = array_rand($list, $count);
		foreach ($randKeys as $key)
		{
			array_push($retval, $list[$key]);
		}
	}
/*
echo "<p>Subset array:</p>";
var_dump($retval);
*/
	return $retval;
}

function send_mixed_mime_email($emailAddr, $subject, $plainText, $html)
{
	// mixed-format email requires some boundary that could be any text that 
	// is unlikely to show up in the message itself
	$boundary = "==A.BC_123_XYZ_678.9";

	// formatted mail requires a MIME and Content-Type header
	$headers = array('MIME-Version: 1.0',
        sprintf('Content-Type: multipart/alternative; boundary="%s"', $boundary));
	
	// multi-part message is made of two parts, each with its own Content-Type
	// these parts are separated by boundary
	// and there should be two dashes at the beginning and the end of the message
	$fullBoundary = sprintf('--%s\n', $boundary);
	$message = $fullBoundary.'Content-Type: text/plain; charset="iso-8859-1"\n\n'
		.$plainText.'\n\n'
		.$fullBoundary.'Content-Type: text/html; charset="iso-8859-1"\n\n'
		.$fullBoundary.'--\n';
	
	return @mail($emailAddr, $subject, $message, join("\n", $headers));
}
?>
