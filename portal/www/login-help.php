<?php

function show_header($title)
{
  global $extra_js;

  echo '<!DOCTYPE HTML>';
  echo '<html>';
  echo '<head>';
  echo '<title>';
  echo $title;
  echo '</title>';
  /* Stylesheet(s) */
  echo '<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/humanity/jquery-ui.css" rel="Stylesheet" />';
  echo '<link type="text/css" href="/common/css/portal.css" rel="Stylesheet"/>';

  /* Close the "head" */
  echo '</head>';
  echo '<body>';
  echo '<div id="header">';
  echo '<table>';
  echo '<tr><td>';
  echo '<img src="/images/geni.png" alt="GENI"/>';
  echo '</td><td>';
  echo '<a href="index.php"><img src="/images/portal.png" alt="Portal"/></a>';
  echo '</td>';
  echo '<td>';
  echo '<div id="loginDiv">';
  echo '<div id="loginButtons">';
  echo '<button id="loginButton" onClick="window.location.href=\'secure/home.php\'"><b>Use GENI</b></button>';
  echo '</div>';
  echo '<img id="incommon-logo" src="common/InC_Participant.png"/>';
  echo '</div>';

  echo '</td>';
  echo '</tr>';


  echo '</table>';
  echo '</div>';
  echo '<hr/>';
  echo '<div id="content">';
}

show_header("Troubleshooting logging into the GENI Portal")
?>

<h1> Logging into the GENI Portal </h1>

<p> 
The GENI Portal supports single sign on login via our partnership in the <a href="http://www.incommonfederation.org">InCommon</a> federation. 
<ul>
  <li>If you are affiliated with a US college or university that is a <a href="http://www.incommon.org/federation/info/all-entities.html">member of the InCommon federation</a>, <a href="secure/home.php">login</a> using your InCommon single sign on username and password and register for a GENI account.
  </li><li>If you are not affiliated with an InCommon federated institution, <a href="mailto:help@geni.net">request a single sign on login for the GENI Portal</a>.</li>
</ul>
</p>

<h1> Troubleshooting logging into the GENI Portal </h1>
<h2>If you don't know if you are a member of an InCommon federated institution...</h2>
<ul>
<li>
... check this list of <a href="http://www.incommon.org/federation/info/all-entities.html">members of the InCommon federation</a>.
</li>
</ul>


<h2 id='requestGENIAccount''>If you are a member of an InCommon federated institution...</h2>
<ul>
<li>
<b>... and you don't have a Single Sign On login through InCommon</b>, contact your local IT department or computer help desk to inquire about getting a login.
</li><li>
<b>... and you have a Single Sign On login through InCommon, but can't log in</b>. Your login and password are managed at your local instition.  Contact your local IT department or computer help desk for assistance.
</li>
</ul>



<h2>If you are NOT a member of an InCommon federated institution...</h2>

<ul>
<li>
<b>... and don't have a login or can't login</b>, contact <a href="mailto:help@geni.net">GENI help</a> and request a  Single Sign On login to the GENI portal. 
</li>
</ul>

<h2>For all other issues...</h2>
<ul>
<li>
... contact <a href="mailto:help@geni.net">GENI help</a>. Please describe your problem and the date and time you observed the issue.
</li>
</ul>

<!-- THIS SHOULD BE IN A COMMON FOOTER FILE --> 
</div>
<div id="footer">
  <hr/>
  <small><i><a href="http://www.geni.net/">GENI</a> is sponsored by the <a href="http://www.nsf.gov/">NSF</a></i></small>
  <!-- put the copyright off to the right -->
  <div style="float:right;">
  <small><i>GENI Portal Copyright 2012, BBN Technologies</i></small>
  </div>
</div>
</body>
</html>