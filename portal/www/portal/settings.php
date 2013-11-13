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
?>
<?php
//--------------------------------------------------
// Site settings for GENI Identity Portal
//--------------------------------------------------

// Where to find the gcf installation. This is necessary for
// generation of slice credentials.
$portal_gcf_dir = '/usr/share/geni-ch/portal/gcf';

// Where to find the local gcf configuration directory.
$portal_gcf_cfg_dir = '/usr/share/geni-ch/portal/gcf.d';

// Set to true for demo situations to auto approve new accounts.
$portal_auto_approve = false;

// Set to false to hide ABAC content and skip generating abac certs
$portal_enable_abac = false;

// Portal certificate file
$portal_cert_file = '/usr/share/geni-ch/portal/portal-cert.pem';

// Portal private key file
$portal_private_key_file = '/usr/share/geni-ch/portal/portal-key.pem';

// set to match the current GENI CH SA
$portal_max_slice_renewal_days = 185;

// Portal version
$portal_version = "2.5.1";

//----------------------------------------------------------------------
// Set error level to include user errors (generated by the portal).
//----------------------------------------------------------------------
// Add E_USER_ERROR to the current set
error_reporting(error_reporting() | E_USER_ERROR);

?>
