<?php
//----------------------------------------------------------------------
// Copyright (c) 2014 Raytheon BBN Technologies
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
require_once("header.php");
require_once 'geni_syslog.php';
require_once 'db-util.php';

$user = geni_loadUser();
if (!isset($user) || is_null($user) || ! $user->isActive()) {
  relative_redirect('home.php');
}

function from_request($request_var, $default_value) {
  if (array_key_exists($request_var, $_REQUEST)) {
    return $_REQUEST[$request_var];
  } else {
    return $default_value;
  }
}

$rspec_id = from_request('id', NULL);
$rspec_name = from_request('name', NULL);
$rspec_desc = from_request('description', NULL);
$rspec_vis = from_request('visibility', NULL);

if (is_null($rspec_id)) {
  relative_redirect('home.php');
}

/* $rspec is the XML */
$rspec = fetchRSpec($rspec_id);

if (is_null($rspec)) {
  relative_redirect('home.php');
}

/* If I'm not the owner of the rspec, bail. */
$owner = $rspec['owner_id'];
if (! $owner === $user->account_id) {
  relative_redirect('home.php');
}

$result = updateRSpec($rspec_id, $rspec_name, $rspec_desc, $rspec_vis,
                      $error_msg);
// Was there an error? Display it.
if (! $result) {
  $_SESSION['lasterror'] = $error_msg;
}

relative_redirect('profile.php#rspecs');