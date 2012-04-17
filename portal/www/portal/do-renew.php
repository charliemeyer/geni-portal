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
?>
<?php
require_once("settings.php");
require_once("user.php");
require_once("file_utils.php");
require_once("sr_client.php");
require_once("sr_constants.php");
require_once("am_client.php");
require_once("sa_client.php");
$user = geni_loadUser();
if (! $user->privSlice() || ! $user->isActive()) {
  relative_redirect("home.php");
}
?>
<?php
function no_slice_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No slice id specified.';
  exit();
}

function no_time_error() {
  header('HTTP/1.1 404 Not Found');
  print 'No expiration time specified.';
  exit();
}

if (! count($_GET)) {
  // No parameters. Return an error result?
  // For now, return nothing.
  no_slice_error();
}

include("tool-lookupids.php");

if (! isset($slice)) {
  no_slice_error();
}

if (array_key_exists('slice_expiration', $_GET)) {
  $slice_expiration = $_GET['slice_expiration'];
} else {
  no_time_error();
}

// Get an AM
$am_url = get_first_service_of_type(SR_SERVICE_TYPE::AGGREGATE_MANAGER);
error_log("SLIVER_RENEW AM_URL = " . $am_url);

// Get the slice credential from the SA
$slice_credential = get_slice_credential($sa_url, $slice_id, $user->account_id);

error_log("point A $slice_id");
// Get the slice URN via the SA
$slice = lookup_slice($sa_url, $slice_id);
error_log("point B $slice_id");
$slice_urn = $slice[SA_ARGUMENT::SLICE_URN];
$name = $slice[SA_ARGUMENT::SLICE_NAME];
error_log("SLIVER_RENEW SLICE_URN = $slice_urn");

// Call renew sliver at the AM
$sliver_output = renew_sliver($am_url, $user, $slice_credential,
                               $slice_urn, $slice_expiration);

error_log("RenewSliver output = " . $sliver_output);

$header = "Renewed Sliver on slice: $name";
$text = $sliver_output;
$slice_name = $name;
include("print-text.php");


// relative_redirect('slices');

?>
