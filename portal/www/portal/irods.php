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
 * Should username really be the HRN? Or how do we deal with the possibility of duplicate usernames?
 * Should the page provide a download of the irods config file?
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
function doGET($url, $user, $password, $serverroot=null) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $password);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // iren-web is using a self signed cert at the moment
  if (! is_null($serverroot)) {
    curl_setopt($ch, CURLOPT_CAINFO, $serverroot);
  }
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // The iRODS cert says just 'iRODS' so can't ensure we are talking to the right host
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  $result = curl_exec($ch);

  $meta = curl_getinfo($ch);
  $error = curl_error($ch);
  curl_close($ch);
  if ($result === false) {
    error_log("GET of " . $url . " failed (no result): " . $error);
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
    // FIXME: Comment this out when ready
    error_log("GET of " . $url . " result: " . print_r($result, true));
  }
  if ($meta === false) {
    error_log("GET of " . $url . " error (no meta): " . $error);
    $code = "";
    throw new Exception("GET " . $url . " failed: " . $code . $error);
  } else {
    error_log("GET meta: " . print_r($meta, true));
    if (is_array($meta) && array_key_exists("http_code", $meta)) {
      error_log("GET of " . $url . " got error return code " . $meta["http_code"]);
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
    error_log("GET of " . $url . " error: " . print_r($error, true));
  return $result;
}

function doPUT($url, $user, $password, $data, $content_type="application/json", $serverroot=null) {
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
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // iren-web is using a self signed cert at the moment
  if (! is_null($serverroot)) {
    curl_setopt($ch, CURLOPT_CAINFO, $serverroot);
  }
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // The iRODS cert says just 'iRODS' so can't ensure we are talking to the right host
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  $result = curl_exec($ch);
  $meta = curl_getinfo($ch);
  $error = curl_error($ch);
  curl_close($ch);
  if ($result === false) {
    error_log("PUT to " . $url . " failed (no result): " . $error);
    $code = "";
    if (is_array($meta) && array_key_exists("http_code", $meta)) {
      $code = "HTTP error " . $meta['http_code'] . ": ";
    }
    throw new Exception("PUT to " . $url . " failed: " . $code . $error);
  } else {
    // FIXME: Comment this out when ready
    error_log("PUT to " . $url . " result: " . print_r($result, true));
  }
  if ($meta === false) {
    error_log("PUT to " . $url . " error (no meta): " . $error);
    $code = "";
    throw new Exception("PUT to " . $url . " failed: " . $code . $error);
  } else {
    error_log("PUT to " . $url . " meta: " . print_r($meta, true));
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
    error_log("PUT to " . $url . " error: " . print_r($error, true));
  return $result;
}

function derive_username($baseusername, $latestname=null) {
  $ind = 1;
  if (! is_null($latestname)) {
    $ind = intval(substr($latestname, strlen($baseusername)));
    $ind = $ind + 1;
  }
  return $baseusername . $ind;
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
const IRODS_ZONE = "zone";
const IRODS_USERDN = "userDN";

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
$irods_url = 'https://iren-web.renci.org:8443/irods-rest-0.0.1-SNAPSHOT/rest';
$irods_host = "irods_hostname";
$irods_port = 1247; // FIXME: Always right?
$irods_resource = "demoResc"; // FIXME: Always right?
$default_zone = "tempZone";

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

$username = $user->username;
$baseusername = $username;
$certStruct = openssl_x509_parse($user->certificate());
$subjectDN = $certStruct['name'];
//$userurn = $user->urn();

///* Sign the outbound message with the portal cert/key */
//$portal = Portal::getInstance();
//$portal_cert = $portal->certificate();
//$portal_key = $portal->privateKey();

$didCreate = False;
$userExisted = False;
$usernameTaken = False;
$irodsError = "";
$tempPassword = "";
$createTime = "";
$zone = "";
$userDN = "";
$irodsUsername = "";

// FIXME: Replace with something homegrown?
//$pestget = new PestXML($irods_url);
//$pestget->setupAuth($portal_irods_user, $portal_irods_pw);
//error_log("pestget curlopts" . print_r($pestget->curl_opts, TRUE));

$userinfo = array();

// Did GET fail in a way that we shouldn't try the PUT
$permError = false;

// First we try to GET the username: if there, the user already has an account. Remind them.
// FIXME: Or could someone non portal have claimed this username?
try {
  while(True) {
    $userxml = doGET($irods_url . IRODS_GET_USER_URI . $username, $portal_irods_user, $portal_irods_pw, $irods_cert);
    //  error_log(print_r($pestget->last_response, TRUE));
    //  error_log("Got userxml: " . $userxml);
    if (! isset($userxml) || strncmp($userxml, "<?xml", 5)) {
      throw new Exception("Error looking up " . $username . " at iRODS: " . $userxml);
    } 
    $xml = simplexml_load_string($userxml);
    if (!$xml) {
      error_log("Failed to parse XML from GET of iRODS user " . $username);
    } else {
      $userxml = $xml;
      if (! is_null($userxml) && $userxml->getName() == "user") {
	foreach ($userxml->attributes() as $a=>$b) {
	  if ($a == "name") {
	    $irodsUsername = $b;
	    if ($irodsUsername != $username) {
	      error_log("GET for iRODS username " . $username . " got a different username " . $irodsUsername);
	    }
	    break;
	  }
	  //      	error_log($a . '=' . $b);
	}
	$userExisted = True; // since it is always empty at the moment. Take this out if userDN consistently there
	foreach ($userxml->children() as $child) {
	  $name = $child->getName();
	  //	error_log($child->getName() . '=' . $child);
	  if ($name == IRODS_CREATE_TIME) {
	    $createTime = strval($child);
	  } elseif ($name == IRODS_ZONE) {
	    $zone = strval($child);
	  } elseif ($name == IRODS_USERDN) {
	    $userDN = strval($child);
	    if ($userDN === "") {
	      error_log("userDN empty from iRODS for user " . $username);
	    } elseif ($userDN !== $subjectDN) {
	      error_log("GET for username " . $username . " got DN from iRODS " . $userDN . " != user's: " . $subjectDN);
	      $usernameTaken = True;
	    } else {
	      $userExisted = True;
	    }
	  }
	}
      } // End of block where we got a valid GET result
      if ($usernameTaken) {
	$username = foo($baseusername, $username);
	$usernameTaken = False;
      }
      if ($userExisted) 
	break;
    } // End of block to parse GET result 
  } // end of while to either find the user or raise an exception (IE found a free username)
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

// If the iRODS server is working but the user didn't exist, create them
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

  // FIXME: Take this out when ready
  error_log("Doing put of irods_json: " . $irods_json);

  ///* Sign the data with the portal certificate (Is that correct?) */
  //$irods_signed = smime_sign_message($irods_json, $portal_cert, $portal_key);
  
  ///* Encrypt the signed data for the iRODS SSL certificate */
  //$irods_blob = smime_encrypt($irods_signed, $irods_cert);
  
  // Now do the REST PUT
  try {
    //    $pestput = new PestJSON($irods_url);
    //    $pestput->setupAuth($portal_irods_user, $portal_irods_pw);
    //    $addstruct = $pestput->put(IRODS_PUT_USER_URI, $irods_json);
    $addstruct = doPUT($irods_url . IRODS_PUT_USER_URI . IRODS_SEND_JSON, $portal_irods_user, $portal_irods_pw, $irods_json, "application/json", $irods_cert);
    //error_log("PUT raw result: " . print_r($addstruct, true));

    // look for (\r or \n or \r\n){2} and move past that
    preg_match("/(\r|\n|\r\n){2}([^\r\n].+)$/", $addstruct, $m);
    if (! array_key_exists(2, $m)) {
      error_log("Malformed PUT result to iRODS - error? Got: " . $addstruct);
      throw new Exception("Failed to create iRODS account - server error: " . $addstruct);
    }

    // FIXME: Comment this out when ready
    error_log("PUT result content: " . $m[2]);

    $addjson = json_decode($m[2], true);
    // Parse the result. If code 0, show username and password. Else show the error for now.
    // Later if username taken, find another.
    if (! is_null($addjson) && is_array($addjson) && array_key_exists(IRODS_ADD_RESPONSE_CODE, $addjson)) {
      if ($addjson[IRODS_ADD_RESPONSE_CODE] == IRODS_ERROR::SUCCESS) {
	$didCreate = True;
	// FIXME: Redo the GET so I can show the zone et al?
      } else {
	// Get the various messages

	// Which error description do we use? The one they sent? Or ours?
	$irodsError = $IRODS_ERROR_NAMES[$addjson[IRODS_ADD_RESPONSE_CODE]] . ": " . $addjson[IRODS_MESSAGE];
	//	$irodsError = $addjson[IRODS_ADD_RESPONSE_DESCRIPTION] . ": " . $addjson[IRODS_MESSAGE];
	error_log("iRODS returned an error creating account for " . $username . ": " . $irodsError);
      }
    } else {
      // malformed return struct
      error_log("Malformed return from iRODS PUT to create iRODS account for " . $username . ": " . $addjson);
    }
  } catch (Exception $e) {
    error_log("Error doing iRODS put to create iRODS account for " . $username . ": " . $e->getMessage());
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
  if ($username != $baseusername) 
    print "<p><b>NOTE</b>: Your username is not the same as your portal username (which was taken). Write it down</p>\n";
  print "<br/>\n";
  print "To use iRODS commandline tools you will need to create the file '~/.irods/.irodsEnv':<br/>\n";
  if ($zone === "")
    $zone = $default_zone;
  print "<p>irodsHost=$irods_host><br/>\n";
  print "irodsPort=$irods_port<br/>\n";
  print "irodsDefResource=$irods_resource<br/>\n";
  print "irodsHome=/$zone/home/$username<br/>\n";
  print "irodsCwd=/$zone/home/$username<br/>\n";
  print "irodsUserName=$username<br/>\n";
  print "irodsZone=$zone</p>\n";
} elseif ($userExisted) {
  $isDiffDN = false;
  print "<p>Your GENI iRODS account has already been created.</p>";
  print "<table><tr><td class='label'><b>Username</b></td><td>$username</td></tr>";
  if ($createTime !== "")
    print "<tr><td class='label'><b>Created</b></td><td>$createTime</td></tr>\n";
  if ($zone !== "")
    print "<tr><td class='label'><b>irodsZone</b></td><td>$zone</td></tr>\n";
  if ($userDN !== "") {
    print "<tr><td class='label'><b>User DN</b></td><td>$userDN</td></tr>\n";
    if ($userDN != $subjectDN)
      $isDiffDN = true;
  }
  // FIXME: Show what the config file should look like?
  print "</table>\n";
  print "<p><b>WARNING: You must find your iRODS password, or contact XXX to have it reset.</b></p>\n";
  if ($isDiffDN)
    print "<p><b>WARNING: This iRODS user has your username but a different DN. Is this you? Your DN is: " . $subjectDN . "/</b></p>\n";
  print "To use iRODS commandline tools you will need to create the file '~/.irods/.irodsEnv':<br/>\n";
  if ($zone === "")
    $zone = $default_zone;
  print "<p>irodsHost=$irods_host<br/>\n";
  print "irodsPort=$irods_port<br/>\n";
  print "irodsDefResource=$irods_resource<br/>\n";
  print "irodsHome=/$zone/home/$username<br/>\n";
  print "irodsCwd=/$zone/home/$username<br/>\n";
  print "irodsUserName=$username<br/>\n";
  print "irodsZone=$zone</p>\n";
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
