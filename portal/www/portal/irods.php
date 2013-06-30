<?php
//----------------------------------------------------------------------
// Copyright (c) 2013 Raytheon BBN Technologies
//
// Permission is hereby granted, free of charge, to any person obtaining
// a copy of this software and/or hardware specification (the "Work") to
// deal in the Work without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Work, and to permit persons to whom the Work
// is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Work.
//
// THE WORK IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
// IN THE WORK.
//----------------------------------------------------------------------

// Search for an irods account for this user under their portal username. Display it if found.
// Otherwise, Create an account for the user at iRods with a temporary password

/* TODO:
 * Get irods from SR
 * Get U/P from settings only
 * S/MIME
 * On fatal error from GET don't try PUT
 */

require_once('user.php');
require_once('ma_client.php');
require_once('header.php');
require_once('smime.php');
require_once('portal.php');
require_once('settings.php');
require_once('sr_client.php');
include_once('/etc/geni-ch/settings.php');
//require_once('PestJSON.php');
//require_once('PestXML.php');

class PermFailException extends Exception{}

// Do a BasicAuth protected get of the given URL
function doGET($url, $user, $password) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $password);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  $result = curl_exec($ch);

  $meta = curl_getinfo($ch);
  $error = curl_error($ch);
  curl_close($ch);
  if ($result === false) {
    error_log("GET failed (no result): " . $error);
    $code = "";
    $perm = false;
    if (is_array($meta) && array_key_exists("http_code", $meta)) {
      $code = "HTTP error " . $meta['http_code'] . ": ";
      //      if ($meta['http_code'] < 200 || $meta['http_code'] > 299)
      if ($meta['http_code'] == 0) 
	$perm = true;
    }
    if ($perm)
      throw new PermFailException("GET " . $url . " failed: " . $code . $error);

    throw new Exception("GET " . $url . " failed: " . $code . $error);
  } else {
    error_log("GET result: " . print_r($result, true));
  }
  if ($meta === false) {
    error_log("GET error (no meta): " . $error);
    $code = "";
    throw new Exception("GET " . $url . " failed: " . $code . $error);
  } else {
    error_log("GET meta: " . print_r($meta, true));
    if (is_array($meta) && array_key_exists("http_code", $meta)) {
      error_log("GET got error return code " . $meta["http_code"]);
      if ($meta["http_code"] != 200) {
	// code ??? means user not found - raise a different exception?
	// then if I don't get that and don't get the real result I show the error 
	// and don't try to do the PUT?
	$codestr = "HTTP Error " . $meta["http_code"];
	if (is_null($error) || $error === "") {
	  $error = $codestr . ": \"" . $result . '"';
	} else {
	  $error = $codestr . ": \"" . $error . '"';
	} 
	throw new Exception($error);
	//	throw new PermFailException($error);
      }
    }
  }
  if (! is_null($error) && $error != '')
    error_log("GET error: " . print_r($error, true));
  return $result;
}

function doPUT($url, $user, $password, $data, $content_type="application/json") {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  $headers = array();
  $headers[] = "Content-Type: " . $content_type;
  $headers[] = "Content-Length: " . strlen($data);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $password);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  $result = curl_exec($ch);
  $meta = curl_getinfo($ch);
  $error = curl_error($ch);
  curl_close($ch);
  if ($result === false) {
    error_log("PUT failed (no result): " . $error);
    $code = "";
    if (is_array($meta) && array_key_exists("http_code", $meta)) {
      $code = "HTTP error " . $meta['http_code'] . ": ";
    }
    throw new Exception("PUT to " . $url . " failed: " . $code . $error);
  } else {
    error_log("PUT result: " . print_r($result, true));
  }
  if ($meta === false) {
    error_log("PUT error (no meta): " . $error);
    $code = "";
    throw new Exception("PUT to " . $url . " failed: " . $code . $error);
  } else {
    error_log("PUT meta: " . print_r($meta, true));
    if (is_array($meta) && array_key_exists("http_code", $meta)) {
      error_log("PUT got error return code " . $meta["http_code"]);
      if ($meta["http_code"] != 200) {
	$codestr = "HTTP Error " . $meta["http_code"];
	if (is_null($error) || $error === "") {
	  $error = $codestr . ": \"" . $result . '"';
	} else {
	  $error = $codestr . ": \"" . $error . '"';
	}
	throw new Exception($error);
      }
    }
  }
  if (! is_null($error) && $error != '')
    error_log("PUT error: " . print_r($error, true));
  return $result;
}

/* iRods Constants */
const IRODS_USER_NAME = 'userName';
const IRODS_USER_PASSWORD = 'tempPassword';
const IRODS_USER_DN = 'distinguishedName';
const IRODS_ADD_RESPONSE_DESCRIPTION = 'userAddActionResponse';
const IRODS_ADD_RESPONSE_CODE = 'userAddActionResponseNumericCode';
const IRODS_MESSAGE = 'message';
const IRODS_GET_USER_URI = '/user/';
const IRODS_PUT_USER_URI = '/user';
const IRODS_SEND_JSON = '?contentType=application/json';
const IRODS_CREATE_TIME = 'createTime';

/*
0 - Success (user can log in with that username/password)
1 - Username is taken
2 - Temporary error, try again
3 - S/MIME signature invalid
4 - Failed to decrypt S/MIME message
5 - Failed to parse message
6 - Attributes missing
7 - Internal Error
*/

/* iRods error codes */
class IRODS_ERROR
{
  const SUCCESS = 0;
  const USERNAME_TAKEN = 1;
  const TRY_AGAIN = 2;
  const SMIME_SIG = 3;
  const SMIME_DECRYPT = 4;
  const PARSE = 5;
  const ATTRIBUTE_MISSING = 6;
  const INTERNAL_ERROR = 7;
}

$IRODS_ERROR_NAMES = array("Success",
			   "Username taken / exists",
			   "Temporary error - try again",
			   "S/MIME Signature invalid",
			   "Failed to decrypt S/MIME message",
			   "Failed to parse message",
			   "Attribute(s) missing",
			   "Internal error");

if (! isset($ma_url)) {
  $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
  if (! isset($ma_url) || is_null($ma_url) || $ma_url == '') {
    error_log("Found no MA in SR!");
  }
}

/* TODO put this in the service registry */
$irods_url = 'http://iren-web.renci.org:8080/irods-rest-0.0.1-SNAPSHOT/rest';

// Get the irods server cert for smime purposes
$irods_cert = null;
$irods_svrs = get_services_of_type(SR_SERVICE_TYPE::IRODS);
if (isset($irods_svrs) && ! is_null($irods_svrs) && is_array($irods_svrs) && count($irods_svrs) > 0) {
  $irod = $irods_svrs[0];
  $irods_url = $irod[SR_TABLE_FIELDNAME::SERVICE_URL];
  $irods_cert = $irod[SR_TABLE_FIELDNAME::SERVICE_CERT];
}

if (! isset($irods_url) || is_null($irods_url) || $irods_url == '') {
  error_log("Found no iRODS server in SR!");
}

/* Get this from /etc/geni-ch/settings.php */
// FIXME: Get the right values from settings.php
if (! isset($portal_irods_user) || isnull($portal_irods_user)) {
  $portal_irods_user = 'rods';
  $portal_irods_pw = 'rods';
}

if (!isset($user)) {
  $user = geni_loadUser();
}
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$username = $user->username . "2";
$uid = $user->account_id;
$email = $user->email();
$certStruct = openssl_x509_parse($user->certificate());
$subjectDN = $certStruct['name'];
//$userdn = "CN=" . $uid . "/emailAddress=" . $email;
//$userurn = $user->urn();

///* Sign the outbound message with the portal cert/key */
//$portal = Portal::getInstance();
//$portal_cert = $portal->certificate();
//$portal_key = $portal->privateKey();

$didCreate = False;
$userExisted = False;
$irodsError = "";
$tempPassword = "";
$createTime = "";

// FIXME: Replace with something homegrown?
//$pestget = new PestXML($irods_url);
//$pestget->setupAuth($portal_irods_user, $portal_irods_pw);
//error_log("pestget curlopts" . print_r($pestget->curl_opts, TRUE));

$userinfo = array();

// Need util function to parse the userinfo
// if it is an array and has error code and it is 0 then get result. Else construct error message.

// Did GET fail in a way that we shouldn't try the PUT
$permError = false;

try {
  $userxml = doGET($irods_url . IRODS_GET_USER_URI . $username, $portal_irods_user, $portal_irods_pw);
//  error_log(print_r($pestget->last_response, TRUE));
  error_log("Got userxml: " . $userxml);
  if (! isset($userxml) || strncmp($userxml, "<?xml", 5)) {
    throw new Exception("Error looking up " . $username . " at iRODS: " . $userxml);
  } 
  $xml = simplexml_load_string($userxml);
  if (!$xml) {
    error_log("Failed to parse XML");
  } else {
    $userxml = $xml;
    if (! is_null($userxml) && $userxml->getName() == "user") {
      $userExisted = True;
      //      foreach ($userxml->attributes() as $a=>$b) {
      //	error_log($a . '=' . $b);
      //      }
      foreach ($userxml->children() as $child) {
	$name = $child->getName();
	//	error_log($child->getName() . '=' . $child);
	if ($name == IRODS_CREATE_TIME) {
	  $createTime = strval($child);
	  break;
	}
      }
      // Show more of what iRODS has for them?
      /*
      $userDN = $userxml->xpath('//userDN');
      if (! is_null($userDN)) {
	$userExisted = True;
	$comment = $userxml->xpath('//comment');
	$createTime = $userxml->xpath('//createTime');
	$info = $userxml->xpath('//info');
	$modifyTime = $userxml->xpath('//modifyTime');
	$userType = $userxml->xpath('//userType');
      }
      */
    } 
  } 
}
catch (PermFailException $e) 
{
  error_log("Error checking if iRODS account $username exists: " . $e->getMessage());
  $irodsError = htmlentities($e->getMessage());
  $permError = true;
}
catch (Exception $e) 
{
  error_log("Error checking if iRODS account $username exists: " . $e->getMessage());
  $irodsError = htmlentities($e->getMessage());
  // FIXME: Errors that are not 'not found' we should not do PUT
}

if (! $permError && ! $userExisted) {

  // Create a temp password of 10 characters
  $hash = strtoupper(md5(rand()));
  $tempPassword = substr($hash, rand(0, 22), 10);

  // Construct the array of stuff to send iRODS
  $irods_info = array();
  $irods_info[IRODS_USER_NAME] = $username;
  $irods_info[IRODS_USER_PASSWORD] = $tempPassword;
  $irods_info[IRODS_USER_DN] = $subjectDN;
  
  // Note: in PHP 5.4, use JSON_UNESCAPED_SLASHES.
  //   we have PHP 5.3, so we have to remove those manually.
  $irods_json = json_encode($irods_info);
  $irods_json = str_replace('\\/','/', $irods_json);

  error_log("Doing put of irods_json: " . $irods_json);

  ///* Sign the data with the portal certificate (Is that correct?) */
  //$irods_signed = smime_sign_message($irods_json, $portal_cert, $portal_key);
  
  ///* Encrypt the signed data for the iRODS SSL certificate */
  //$irods_blob = smime_encrypt($irods_signed, $irods_cert);
  
// FIXME!!!!
// REST HTTP PUT this stuff
  try {
    //    $pestput = new PestJSON($irods_url);
    //    $pestput->setupAuth($portal_irods_user, $portal_irods_pw);
    //    $addstruct = $pestput->put(IRODS_PUT_USER_URI, $irods_json);
    $addstruct = doPUT($irods_url . IRODS_PUT_USER_URI . IRODS_SEND_JSON, $portal_irods_user, $portal_irods_pw, $irods_json);
    //error_log("PUT raw result: " . print_r($addstruct, true));

    // look for (\r or \n or \r\n){2} and move past that
    preg_match("/(\r|\n|\r\n){2}([^\r\n].+)$/", $addstruct, $m);
    if (! array_key_exists(2, $m)) {
      error_log("Malformed PUT result - error?");
      throw new Exception("Failed to create iRODS account - server error: " . $addstruct);
    }
    error_log("PUT result content: " . $m[2]);
    $addjson = json_decode($m[2], true);
    // Parse the result. If code 0, show username and password. Else show the error for now.
    // Later if username taken, find another.
    if (! is_null($addjson) && is_array($addjson) && array_key_exists(IRODS_ADD_RESPONSE_CODE, $addjson)) {
      if ($addjson[IRODS_ADD_RESPONSE_CODE] == IRODS_ERROR::SUCCESS) {
	$didCreate = True;
      } else {
	// Get the various messages

	// Which error description do we use? The one they sent? Or ours?
	$irodsError = $IRODS_ERROR_NAMES[$addjson[IRODS_ADD_RESPONSE_CODE]] . ": " . $addjson[IRODS_MESSAGE];
	//	$irodsError = $addjson[IRODS_ADD_RESPONSE_DESCRIPTION] . ": " . $addjson[IRODS_MESSAGE];
	error_log("iRODS returned an error creating account for " . $username . ": " . $irodsError);
      }
    } else {
      // malformed return struct
      error_log("Malformed return from irods put to create iRODS account for " . $username . ": " . $addjson);
    }
  } catch (Exception $e) {
    error_log("Error doing irods put to create iRODS account for " . $username . ": " . $e->getMessage());
    $irodsError = htmlentities($e->getMessage());
  }
}

// Now show a page with the result

show_header('GENI Portal: Profile', $TAB_PROFILE);
include("tool-breadcrumbs.php");
include("tool-showmessage.php");
// FIXME
?>
<h1>iRODS Account</h1>
<p>iRODS is a server for storing data about your experiments. It is used by the GIMI and GEMINI Instrumentation and Measurement systems.</p>
<?php
// FIXME: URL for iRODS? Other stuff?
if ($didCreate) {
  print "<p>Your GENI iRODS account has been created.</p>";
  print "<table><tr><td class='label'><b>Username</b></td><td>$username</td></tr>\n";
  print "<tr><td class='label'><b>Temporary Password</b></td><td>$tempPassword</td></tr></table>\n";
  print "<p><b>WARNING: Write down your password. It is not recorded anywhere. You will need to change it after accessing iRods.</b></p>\n";
} elseif ($userExisted) {
  print "<p>Your GENI iRODS account already exists.</p>";
  print "<table><tr><td class='label'><b>Username</b></td><td>$username</td></tr>";
  print "<tr><td class='label'><b>Created</b></td><td>$createTime</td></tr></table>\n";
  print "<p><b>WARNING: You must find your iRODS password, or contact XXX to have it reset.</b></p>\n";
} else {
  // Some kind of error
  print "<div id=\"error-message\">";
  print "<p class='warn'>There was an error talking to iRODS.<br/><br/>";
  print "$irodsError</p>\n";
  print "</div>\n";
  // FIXME: Do more?
}
include("footer.php");
?>
