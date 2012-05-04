<?php
//----------------------------------------------------------------------
// Copyright (c) 2011 Raytheon BBN Technologies
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

// Form for creating a slice. Submit to self.

require_once("settings.php");
require_once("db-util.php");
require_once("file_utils.php");
require_once("util.php");
require_once("user.php");
require_once('pa_constants.php');
require_once('pa_client.php');
require_once("sr_constants.php");
require_once("sr_client.php");
require_once("sa_client.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive() || ! $user->privSlice()) {
  relative_redirect('home.php');
}

$slice_name = NULL;
$project_id = NULL;
$message = NULL;
include("tool-lookupids.php");
if (array_key_exists("slice_name", $_REQUEST)) {
  $slice_name = $_REQUEST['slice_name'];
}

if (is_null($project_id) || $project_id == '') {
  error_log("createslice: invalid project_id from GET");
  relative_redirect("home.php");
}

function omni_create_slice($user, $slice_id, $name)
{
    /* Write key and credential files */
    $row = db_fetch_inside_private_key_cert($user->account_id);
    $cert = $row['certificate'];
    $private_key = $row['private_key'];
    $cert_file = '/tmp/' . $user->username . "-cert.pem";
    $key_file = '/tmp/' . $user->username . "-key.pem";	
    $omni_file = '/tmp/' . $user->username . "-omni.ini";
    file_put_contents($cert_file, $cert);
    file_put_contents($key_file, $private_key);

    /* Create OMNI config file */
    $omni_config = "[omni]\n"
    . "default_cf = my_gcf\n"
    . "[my_gcf]\n"
    . "type=gcf\n"
    . "authority=geni:gpo:portal\n"
    . "ch=https://localhost:8000\n"
    . "cert=" . $cert_file . "\n"
    . "key=" . $key_file;
    file_put_contents($omni_file, $omni_config);

    /* Call OMNI */
    global $portal_gcf_dir;
    $cmd_array = array($portal_gcf_dir . '/src/omni.py',
                   '-c',
		   $omni_file,
		   'createslice',
		   $name
                   );
     $command = implode(" ", $cmd_array);
     $result = exec($command, $output, $status);
//     print_r($output);  
//     print_r($result);
//     print "RESULT = " . $result . "\n";
//     print "STATUS = " . $status . "\n";
     unlink($cert_file);
     unlink($key_file);
     unlink($omni_file);

}

function sa_create_slice($user, $slice_name, $project_id, $project_name)
{
  $sa_url = get_first_service_of_type(SR_SERVICE_TYPE::SLICE_AUTHORITY);
  $owner_id = $user->account_id;
  $result = create_slice($sa_url, $project_id, $project_name, $slice_name,
                         $owner_id);
  return $result;
}


// Do we have all the required params?
if ($slice_name) {
  // Create the slice...
  $result = sa_create_slice($user, $slice_name, $project_id, $project_name);
  /* $pretty_result = print_r($result, true); */
  /* error_log("sa_create_slice result: $pretty_result\n"); */
 
  // Redirect to this slice's page now...
  $slice_id = $result[SA_SLICE_TABLE_FIELDNAME::SLICE_ID];
  relative_redirect('slice.php?slice_id='.$slice_id);
}

// If here, present the form
require_once("header.php");
show_header('GENI Portal: Debug', '');
if ($message) {
  // It would be nice to put this in red...
  print "<i>" . $message . "</i>\n";
}
include("tool-breadcrumbs.php");
print "<h2>Create New Slice</h2>\n";
print "Project name: <b>$project_name</b><br/>\n";
print '<form method="GET" action="createslice">';
print "\n";
print "<input type='hidden' name='project_id' value='$project_id'/><br/>";
print "\n";
print 'Slice name: ';
print "\n";
print '<input type="text" name="slice_name"/><br/>';
print "\n";
print '<input type="submit" value="Create slice"/>';
print "\n";
print "<input type=\"button\" value=\"Cancel\" onClick=\"history.back(-1)\"/>\n";
print '</form>';
print "\n";
?>
<?php
include("footer.php");
?>
