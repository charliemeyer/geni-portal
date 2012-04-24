<?php
//----------------------------------------------------------------------
// Copyright (c) 2012 Raytheon BBN Technologies
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

require_once("user.php");
require_once("header.php");
require_once('util.php');
require_once('sr_constants.php');
require_once('sr_client.php');
require_once("sa_constants.php");
require_once("sa_client.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive() || ! $user->privSlice()) {
  relative_redirect('home.php');
}
show_header('GENI Portal: Slices', $TAB_SLICES);

$slice_id = "None";
$slice_name = "None";
include("tool-lookupids.php");
include("tool-breadcrumbs.php");
print "<h1>Add resources to GENI Slice: " . $slice_name . "</h1>\n";

// Put up a warning to upload SSH keys, if not done yet.
$keys = fetchSshKeys($user->account_id);
if (count($keys) == 0) {
  // No ssh keys are present.
  print "No ssh keys have been uploaded. ";
  print "Please <button onClick=\"window.location='uploadsshkey.php'\">Upload an SSH key</button> to enable logon to nodes.\n";
  print "<br/>\n";
}

print "<p>Click to reserve a default set of resources at an available AM.</p>";
print "<p>Otherwise click 'Cancel'.</p>";
print '<br/>';

//$edit_url = 'do-edit-slice.php?slice_id='.$slice_id;
$cancel_url = 'slice.php?slice_id='.$slice_id;
$edit_url = 'sliceresource.php?slice_id='.$slice_id;
print "<button onClick=\"window.location='$edit_url'\"><b>Reserve Resources</b></button>    \n";
//print "<button onClick=\"window.location='$cancel_url'\">Cancel</button>\n";
print "<button onClick=\"history.back(-1)\">Cancel</button>\n";

include("footer.php");
?>
