<?php

	/**
	 * If your plugin directory is not in the default location, 
	 * edit this variable to point to your site directory 
	 * where config.php is located
	 */
	$str_relative = '../../../';

	include($str_relative . 'config.php');

	if ( !$member->isLoggedIn() )
	{
		doError('You\'re not logged in.');
	}

	include('functions.php');
	include($DIR_LIBS . 'PLUGINADMIN.php');

	# create the admin area page
	$oPluginAdmin = new PluginAdmin('CurrentMedia');
	$oPluginAdmin->start();

	echo '<h2> Current Media Synchronization </h2>';

	$confirmed = ( getVar('confirm') == '1' ) ? TRUE : FALSE;

	# if: display confirmation message
	if ( !$confirmed )
	{

		echo '<p> <strong>Important Note:</strong> </p>';
		echo '<p> This is an <strong>beta</strong> feature that allows you to update all Current Media items in the database. </p>';
		echo '<p> This feature is primarily for users who were running an older version of Current Media (version 0.5 or earlier). Since the database structure has changed, old items will not display properly until they are updated. You can update media items individually through the "Edit Post" interface by performing a fresh search, but this tool allows you to update them all quickly in one batch. </p>';
		echo '<p> Before performing synchronization, you should <strong>backup</strong> your database. </p>';
		echo '<p> Once you have made a backup of your database, click: </p>';
		echo '<p> <a href="./plugins/currentmedia/synchronize.php?confirm=1">Yes, I want to synchronize my Current Media database</a>. </p>';

	}
	# else: perform synchronization
	else
	{
		# get the language name
		$language = preg_replace('#[\\|/]#', '', getLanguageName() );

		# get the plugin directory
		$plugin_directory = $oPluginAdmin->plugin->getDirectory();

		# if: a language file exists matching the language name, include it
		if ( file_exists($plugin_directory . 'languages/' . $language . '.php') )
		{
			include_once($plugin_directory . 'languages/' . $language . '.php');
		}
		# else: default to include the english file
		else
		{
			include_once($plugin_directory . 'languages/english.php');
		} # end if

		# Language variables
		$lang_currently = _CM_CURRENTLY;
		$lang_listening = _CM_LISTENING;
		$lang_watching = _CM_WATCHING;
		$lang_reading = _CM_READING;
		$lang_playing = _CM_PLAYING;
		$lang_track_label = _CM_TRACK_LABEL;

		$table = sql_table('plugin_currentmedia');

		$locale = $oPluginAdmin->plugin->getOption('site');
		$aws_key = $oPluginAdmin->plugin->getOption('AWSAccessKeyId');
		$secret_key = $oPluginAdmin->plugin->getOption('AWSSecretKey');
		$associate_id = $oPluginAdmin->plugin->getOption('assocID');

		# Construct the XML feed
		switch($locale)
		{

			case 'ca':
				$endpoint = 'http://ecs.amazonaws.ca/onca/xml?Service=AWSECommerceService';
				$url_base = 'http://amazon.ca/';
			break;

			case 'de':
				$endpoint = 'http://ecs.amazonaws.de/onca/xml?Service=AWSECommerceService';
				$url_base = 'http://amazon.de/';
			break;

			case 'fr':
				$endpoint = 'http://ecs.amazonaws.fr/onca/xml?Service=AWSECommerceService';
				$url_base = 'http://amazon.fr/';
			break;

			case 'jp':
				$endpoint = 'http://ecs.amazonaws.jp/onca/xml?Service=AWSECommerceService';
				$url_base = 'http://amazon.jp/';
			break;

			case 'uk':
				$endpoint = 'http://ecs.amazonaws.co.uk/onca/xml?Service=AWSECommerceService';
				$url_base = 'http://amazon.co.uk/';
			break;

			case 'us':
			default:
				$endpoint = 'http://webservices.amazon.com/onca/xml?Service=AWSECommerceService';
				$url_base = 'http://amazon.com/';
			break;

		}

		$options = array(
			'lang_currently' => $lang_currently,
			'lang_listening' => $lang_listening,
			'lang_watching' => $lang_watching,
			'lang_reading' => $lang_reading,
			'lang_playing' => $lang_playing,
			'url_base' => $url_base,
			'associate_id' => $associate_id
		);

		$query = <<< END
SELECT DISTINCT `cm_asin`
FROM `{$table}`
WHERE `cm_asin` <> ''
END;
		$result = sql_query($query);

		# if: 1 or more rows returned
		if ( sql_num_rows($result) > 0 )
		{

			$i = 0;

			# while: loop through results
			while ( !$stop && ($row = sql_fetch_assoc($result) ) )
			{

				$i++;
				$master_items[$row['cm_asin'] ] = 1;
				$item_ids[] = $row['cm_asin'];

				# if: we have 10 item ids, that's the most we can send in one Amazon request
				if ( count($item_ids) == 10 )
				{

					# Make a list for the API call
					$item_ids_list = implode(', ', $item_ids);

					# Reset the item_ids array
					$item_ids = array();

					$parameters = array(
						'AssociateTag'		=> $associate_id,
						'Operation'			=> 'ItemLookup',
						'ResponseGroup'		=> 'Images,Small',
						'ItemId'			=> $item_ids_list
					);

					# foreach:
					foreach( $parameters as $key => $value )
					{
						$fields .= "$key=" . $value . '&';
					} # end foreach

					$fields = rtrim($fields, '&');
					$xml_feed = $endpoint . '&' . $fields;
					$xml_feed = getRequest($secret_key, $xml_feed, $aws_key);

					synchronize($xml_feed, $items, $options);

				} # end if

			} # end loop

			# if: there are a few item ids left to synchronize
			if ( !empty($item_ids) )
			{

				# Make a list for the API call
				$item_ids_list = implode(', ', $item_ids);

				# Reset the item_ids array
				$item_ids = array();

				$parameters = array(
					'AssociateTag'		=> $associate_id,
					'Operation'			=> 'ItemLookup',
					'ResponseGroup'		=> 'Images,Small',
					'ItemId'			=> $item_ids_list
				);

				# foreach:
				foreach( $parameters as $key => $value )
				{
					$fields .= "$key=" . $value . '&';
				} # end foreach

				$fields = rtrim($fields, '&');
				$xml_feed = $endpoint . '&' . $fields;
				$xml_feed = getRequest($secret_key, $xml_feed, $aws_key, '2010-11-01');

				synchronize($xml_feed, $items, $options);

			} # end if

			# loop:
			foreach ( $items as $asin => $item )
			{

				$cm_type = sql_real_escape_string($item['cm_type']);
				$cm_heading = sql_real_escape_string($item['cm_heading']);
				$cm_title = sql_real_escape_string($item['cm_title']);
				$cm_description = sql_real_escape_string($item['cm_description']);
				$cm_url = sql_real_escape_string($item['cm_url']);
				$cm_selected_image = sql_real_escape_string('small');
				$cm_small_image = sql_real_escape_string($item['cm_small_image']);
				$cm_medium_image = sql_real_escape_string($item['cm_medium_image']);
				$cm_large_image = sql_real_escape_string($item['cm_large_image']);

				$query = <<< END
UPDATE `{$table}` SET
	`cm_type` = '{$cm_type}',
	`cm_heading` = '{$cm_heading}',
	`cm_title` = '{$cm_title}',
	`cm_description` = '{$cm_description}',
	`cm_url` = '{$cm_url}',
	`cm_selected_image` = '{$cm_selected_image}',
	`cm_small_image` = '{$cm_small_image}',
	`cm_medium_image` = '{$cm_medium_image}',
	`cm_large_image` = '{$cm_large_image}'
WHERE `cm_asin` = '{$asin}'
END;
				sql_query($query);

			} # end loop

			$query = <<< END
UPDATE `{$table}` SET
	`cm_description` = CONCAT(`cm_description`, ' ', '{$lang_track_label}', `cm_track`)
WHERE `cm_track` <> ''
END;
			sql_query($query);

		} # end if

		echo '<p> <strong>Synchronization Complete.</strong> </p>';

	} # end if

	echo '<p> <a href="./plugins/currentmedia/index.php">Return to Current Media Administration</a>. </p>';

	$oPluginAdmin->end();
