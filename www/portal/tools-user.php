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
require_once("user.php");
if (! $user->privSlice()) {
  exit();
}
?>
<h1>User Tools</h1>
<h2>Public Keys</h2>
<?php
$keys = db_fetch_public_keys($user->account_id);
if (count($keys) > 0) {
  print "\n<table border=\"1\">\n";
  print "<tr><th>Description</th><th>Key Prefix</th><th>Certificate</th></tr>\n";
  $base_url = relative_url("certificate.php?");
  foreach ($keys as $key) {
    $description = $key['description'];
    $key_prefix = substr($key['public_key'], 0, 10);
    $certificate = $key['certificate'];
    $args['id'] = $key['public_key_id'];
    $query = http_build_query($args);
    $download_url = $base_url . $query;
    print "<tr>"
      . "<td>" . htmlentities($description) . "</td>"
      . "<td>" . htmlentities($key_prefix) . "</td>"
      . "<td><a href=\"" . $download_url . "\">Download Certificate</a></td>"
      . "</tr>\n";
  }
  print "</table>\n";
} else {
  print "<i>No public keys.</i><br/>\n";
}
?>
<br/>
<b>Upload a public key</b><br/>
<form action="uploadkey.php" method="post" enctype="multipart/form-data">
<label for="file">Public Key File:</label>
<input type="file" name="file" id="file" />
<label for="file">Description (optional):</label>
<input type="text" name="description"/>
<br/>
<input type="submit" name="submit" value="Upload"/>
</form>
