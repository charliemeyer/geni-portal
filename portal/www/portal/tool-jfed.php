<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2014 Raytheon BBN Technologies
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

require_once('user.php');
require_once('sr_client.php');
require_once('sr_constants.php');
require_once('ma_client.php');
require_once('ma_constants.php');

// FIXME: Chrome on Mac is not supported - it's a 32bit browser, and Java7 needs 64bit.. Warn user in advance?
// FIXME: Java on FF on Mac has to be updated for jFed to work (to Java7)
// Mac OSX 10.6 and below you use Software Update to update Java
// It's Apple Java 6 vs Oracle Java 7. Can't have both.
// Mac OSX 10.7+ does not come with Java
// Java Webstart doesn't work using Apple Java
// Once you install Oracla Java 7, then Apple Java 6 won't run, so no more Java from Chrome.

// Also, you'll be prompted if you want to let this applet run. Then you'll get a security warning potentially (or is that just our dev server).

// Sample page with a jfed button:

/* $jfedret = get_jfed_strs($user); */
/* $jfed_script_text = $jfedret[0]; */
/* $jfed_button_start = $jfedret[1]; */
/* print "<html><head>\n"; */
/* print $jfed_script_text; */
/* ?> */
/* </head> */
/* <body> */
/* <h1>Test Launch jFed with predefined credential</h1> */
/* <?php */
/*   // Show a jfed button if there wasn't an error generating it */
/*   if (! is_null($jfed_button_start)) { */
/*     print $jfed_button_start . "><b>Start jFed</b></button>"; */
/*   } */
/* ?> */
/* </body> */
/* </html> */

// Return is an array
// First return element is text to print on the page - preferably in the header
// second is the start of the jFed button - add the close brace,
// label, and the closing </button>
// But check for that 2nd arg being null - if so, avoid printing the button at all
// See tool-slices for sample usage
function get_jfed_strs($user) {
  $jfed_button_start = null;
  $jfed_script_text = '';

  $browser = getBrowser();
  if (strpos(strtolower($browser["name"]), "chrom") !== false and strpos(strtolower($browser["platform"]),"mac") === 0) {
    //error_log("User browser: " . $browser["name"] . " version " . $browser["version"] . " on " . $browser["platform"]);
    error_log("User running Chrome on Mac. Can't launch jFed. User should try Safari or Firefox.");
    $jfed_button_start = "<button type='button' onclick='alert(\"jFed cannot run in Chrome on a Mac. Try Safari or Firefox..\")'";
    return array($jfed_script_text, $jfed_button_start);
  }

  if (!isset($user)) {
    $user = geni_loadUser();
  }

  if (! isset($ma_url)) {
    $ma_url = get_first_service_of_type(SR_SERVICE_TYPE::MEMBER_AUTHORITY);
    if (! isset($ma_url) || is_null($ma_url) || $ma_url == '') {
      error_log("Found no MA in SR!'");
      return array('', null);
    }
  }

  // Code to set up jfed button
  $certresult = ma_lookup_certificate($ma_url, $user, $user->account_id);
  $expiration_key = 'expiration';
  $has_certificate = False;
  $has_key = False;
  $expired = False;
  $expiration = NULL;
  if (! is_null($certresult)) {
    $has_certificate = True;
    $has_key = array_key_exists(MA_ARGUMENT::PRIVATE_KEY, $certresult);
    if (array_key_exists($expiration_key, $certresult)) {
      $expiration = $certresult[$expiration_key];
      $now = new DateTime('now', new DateTimeZone("UTC"));
      $expired = ($expiration < $now);
    }
  }
  if (! $has_certificate or $expired) {
    $jfed_button_start = "<button type='button' onclick='alert(\"Generate an SSL (Omni) key pair to use jFed.\")'";
  } else {
    // Print the 2 script tags needed
    $params = '';
    if ($has_key) {
      $certstring = $certresult[MA_ARGUMENT::PRIVATE_KEY] . "\n" . $certresult[MA_ARGUMENT::CERTIFICATE];
      $params = ", params: {'login-certificate-string' : '" . base64_encode($certstring) . "' }";
    }
    $jfed_script_text = "
	<script src=\"dtjava_orig.js\"></script>
	<script>
		function launchjFed() {
                dtjava.launch( { url : 'http://jfed.iminds.be/jfed-geni.jnlp'
" . $params . "
		      }, { javafx : '2.2+' }, {} );
                return false;
	}
	</script>
";

    $jfed_button_start = "<button id='jfed' type='button' onclick='launchjFed()'";
  }
  return array($jfed_script_text, $jfed_button_start);
}