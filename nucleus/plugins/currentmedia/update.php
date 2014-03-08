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
		doError('You\'re not logged in.');
	}

	include($DIR_LIBS . 'PLUGINADMIN.php');

	// create the admin area page
	$oPluginAdmin = new PluginAdmin('CurrentMedia');
	$oPluginAdmin->start();

	echo '<h2> Current Media Administration </h2>';

	$table = sql_table('plugin_currentmedia');

	$result = sql_query("SHOW COLUMNS FROM $table WHERE field = 'cm_heading'");
	$rows = sql_num_rows($result);

	// begin if: the 'heading' field already exists, no update required
	if ( $rows == 1 )
	{

		print <<< END
	<h4> Database Update </h4>
	<p> Your database table is already up to date. </p>\n
END;

	// else: run the update query
	}
	else
	{

		$query = <<< END
ALTER TABLE `{$table}`
	ADD COLUMN `cm_heading` VARCHAR(255) NOT NULL DEFAULT '' AFTER `cm_asin`,
	ADD COLUMN `cm_description` TEXT AFTER `cm_title`,
	ADD COLUMN `cm_url` VARCHAR(255) NOT NULL DEFAULT '' AFTER `cm_description`,
	ADD COLUMN `cm_selected_image` ENUM('small','medium','large','none') NOT NULL DEFAULT 'none' AFTER `cm_url`,
	ADD COLUMN `cm_small_image` VARCHAR(255) NOT NULL DEFAULT '' AFTER `cm_selected_image`,
	ADD COLUMN `cm_medium_image` VARCHAR(255) NOT NULL DEFAULT '' AFTER `cm_small_image`,
	ADD COLUMN `cm_large_image` VARCHAR(255) NOT NULL DEFAULT '' AFTER `cm_medium_image`
END;
		$result = sql_query($query);

		// begin if:
		if ( $result )
		{
			print <<< END
	<h4> Database Update </h4>
	<p> Your database table has been updated successfully. </p>\n
END;
		// else:
		}
		else
		{
			print <<< END
	<h4> Database Update </h4>
	<p> There was an error updating your database table. </p>\n
END;
		} // end if

	} // end if

	echo '<p> <a href="./plugins/currentmedia/index.php">Return to Current Media Administration</a>. </p>';

	$oPluginAdmin->end();
