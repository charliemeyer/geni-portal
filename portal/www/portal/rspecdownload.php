<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2015 Raytheon BBN Technologies
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

require_once("settings.php");
require_once("user.php");

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

$rspec_id = NULL;
if (array_key_exists('id', $_GET)) {
  $rspec_id = $_GET['id'];
}

$rspec_contents = NULL;
if (array_key_exists("rspec", $_GET)) {
  $rspec_contents = $_GET['rspec'];
}

$rspec_tempfile = NULL;
if (array_key_exists('tempfile', $_GET)) {
  $rspec_tempfile = $_GET['tempfile'];
}

if (is_null($rspec_id && is_null($rspec_contents))) {
  relative_redirect('home.php');
}

/* $rspec is the XML */
if(!(is_null($rspec_id ))) {
  $rspec = fetchRSpecById($rspec_id);
  $name = fetchRSpecNameById($rspec_id);
} else if (!(is_null($rspec_tempfile))) {
  // Get the canonical path without symlinks or '.' or '..'
  $rspec_tempfile = realpath($rspec_tempfile);
  /* See saverspectoserver.php */
  // Perform a modicum of validation. Yes, we could do more.
  if (strpos($rspec_tempfile, '/tmp/saverspectoserver') !== 0) {
    error_log("Not allowing download of rspec_tempfile $rspec_tempfile");
    header('Not Found', true, 404);
    exit();
  }
  $rspec = file_get_contents($rspec_tempfile);
  $name = "";
  // This is a one-time get file: it is written and then deleted when downloaded
  unlink($rspec_tempfile); 
} else { 
  $rspec = $rspec_contents;
  $name = "";
} 

if (array_key_exists('slice_name', $_GET)) {
  $name = $_GET['slice_name'];
}

$name2 = preg_replace("/[^a-zA-Z0-9]/", "_", $name);
if ($name2 != ""){
   $filename = $name2 . "_request_rspec.xml";
} else {
  $filename = "rspec.xml";
}

/* How to improve this?
 *  - store the filename when uploaded
 *  - convert name to filename (space --> hyphen, append ".xml"
 */
if (is_null($rspec)) {
  relative_redirect('home.php');
} else {
// Set headers for download
  header("Cache-Control: public");
  header("Content-Description: File Transfer");
  header("Content-Disposition: attachment; filename=$filename");
  header("Content-Type: text/xml");
  print $rspec;
}
?>
