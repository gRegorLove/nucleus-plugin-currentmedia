<?php

	/**
	 * If your plugin directory is not in the default location, 
	 * edit this variable to point to your site directory 
	 * where config.php is located
	 */
	$strRel = '../../../';

	include($strRel . 'config.php');

	if ( !$member->isLoggedIn() )
	{
		doError('You are not logged in.');
	}

	include($DIR_LIBS . 'PLUGINADMIN.php');

	# create the admin area page
	$oPluginAdmin = new PluginAdmin('CurrentMedia');
	$oPluginAdmin->start();

	$aws_key = $oPluginAdmin->plugin->getOption('AWSAccessKeyId');
	$secret_key = $oPluginAdmin->plugin->getOption('AWSSecretKey');

	echo '<h2> Current Media Administration </h2>';

	# if: AWS key or secret key aren't specified, display message in place of admin area
	if ( empty($aws_key) || empty($secret_key) )
	{
		$plugin_id = $oPluginAdmin->plugin->plugid;
		echo '<p style="color: red;"> <em>You need to specify your AWS Key and AWS Secret Key in the <a href="index.php?action=pluginoptions&plugid=', $plugin_id, '">plugin options</a> before you can use this plugin.</em> </p>';
		$oPluginAdmin->end();
		exit;
	} # end if

	$table = sql_table('plugin_currentmedia');

	$result = sql_query("SHOW COLUMNS FROM $table WHERE field = 'cm_heading'");
	$num_rows = mysql_num_rows($result);

	$result = sql_query("SELECT COUNT(`cm_id`) AS `total` FROM `{$table}`");
	$total_items = sql_result($result, 0);
	$item_word = ($total_items == 1) ? 'item' : 'items';

	# if: the 'heading' field doesn't exist, we need to update the database
	if ( $num_rows == 0 )
	{
		print <<< END
	<h4> Update Database </h4>
	<p> Your database table needs to be updated before you can use this plugin.  <a href="./plugins/currentmedia/update.php">Click here</a> to perform the update. </p>\n
END;
	}
	# else:
	else
	{
		print <<< END
	<h4> Update Database </h4>
	<p> Your database is already updated to the most recent version. </p>\n
END;
	} # end if

	print <<< END
	<h4> Statistics </h4>
	<p> The Current Media database currently has {$total_items} {$item_word} in it. </p>

	<h4> Synchronize Database (beta) </h4>
	<p> If you were running a version of Current Media version 0.5 or earlier, you may <a href="./plugins/currentmedia/synchronize.php">try this beta feature</a> to update your media items in a single batch. </p>

	<h4> Support </h4>

	<p> As of version 1.0, I will not be actively developing this plugin further. The code is open source and available <a href="https://github.com/gRegorLove/nucleus-plugin-currentmedia">on GitHub</a>, though. </p>

	<p> You may also try asking questions on <a href="http://forum.nucleuscms.org/viewtopic.php?t=20293">this thread</a> in the Nucleus forum. </p>	
END;

	$oPluginAdmin->end();
