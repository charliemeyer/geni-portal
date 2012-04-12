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

require_once('util.php');
require_once('cs_constants.php');
require_once('cs_client.php');

error_log("CS TEST\n");

/* Could be HTTP_HOST or SERVER_NAME */
$http_host = $_SERVER['HTTP_HOST'];
$cs_url = "https://" . $http_host . "/cs/cs_controller.php";

function dump_all_assertions_and_policies()
{
  global $cs_url;
  $assertion_rows = query_assertions($cs_url, '-1', CS_CONTEXT_TYPE::RESOURCE, null);
  foreach($assertion_rows as $assertion) {
    error_log("ASSERT " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::ID] . " " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::SIGNER] . " " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::PRINCIPAL] . " " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::ATTRIBUTE] . " " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::CONTEXT_TYPE] . " " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::CONTEXT] . " " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::EXPIRATION] . " " . 
	      $assertion[CS_ASSERTION_TABLE_FIELDNAME::ASSERTION_CERT]);
  }
  $policy_rows = query_policies($cs_url);
  foreach($policy_rows as $policy) {
    error_log("POLICY " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::ID] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::SIGNER] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::ATTRIBUTE] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::CONTEXT_TYPE] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::PRIVILEGE] . " " . 
	      $policy[CS_POLICY_TABLE_FIELDNAME::POLICY_CERT]);
  }

}

$signer = '22222222222222222222222222222222';
$principal_id = '33333333333333333333333333333333';
$project_id = '44444444444444444444444444444444';

$result = create_assertion($cs_url, 
			   $signer,
			   $principal_id, 
			   CS_ATTRIBUTE_TYPE::ADMIN,
			   CS_CONTEXT_TYPE::RESOURCE,
			   null);
error_log("RES(1) = " . $result);
dump_all_assertions_and_policies($result);

$result = create_assertion($cs_url, 
			   $signer,
			   $principal_id,
			   CS_ATTRIBUTE_TYPE::LEAD,
			   CS_CONTEXT_TYPE::PROJECT,
			   $project_id
			   );
error_log("RES(2) = " . $result);
dump_all_assertions_and_policies();

$result = create_policy($cs_url, 
			$signer,
			CS_ATTRIBUTE_TYPE::ADMIN,
			CS_CONTEXT_TYPE::MEMBER, 
			1);
error_log("RES(3) = " . $result);
dump_all_assertions_and_policies();

$result = create_policy($cs_url, 
			$signer,
			CS_ATTRIBUTE_TYPE::LEAD,
			CS_CONTEXT_TYPE::PROJECT,
			1);
error_log("RES(4) = " . $result);
dump_all_assertions_and_policies();

$result = create_policy($cs_url, 
			$signer,
			CS_ATTRIBUTE_TYPE::LEAD,
			CS_CONTEXT_TYPE::PROJECT,
			1);
error_log("RES(5) = " . $result);
dump_all_assertions_and_policies();

$result = request_authorization($cs_url, 
				$principal_id,
				'create_assertion', 
				CS_CONTEXT_TYPE::MEMBER, null);
error_log("Auth(1) = " . $result);

$result = request_authorization($cs_url,
				$principal_id,
				'create_assertion',
				CS_CONTEXT_TYPE::SERVICE, null);
error_log("Auth(2) = " . $result);

$result = request_authorization($cs_url, 
				$principal_id,
				'create_slice',
				CS_CONTEXT_TYPE::PROJECT,
				$project_id);
error_log("Auth(3) = " . $result);

$result = request_authorization($cs_url, 
				$principal_id,
				'create_slice',
				CS_CONTEXT_TYPE::PROJECT,
				$project_id);
error_log("Auth(4) = " . $result);

$result = renew_assertion($cs_url, '1');
dump_all_assertions_and_policies();

$result = delete_policy($cs_url, '2');
dump_all_assertions_and_policies();


relative_redirect('debug');
?>
