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
		doError('You are not logged in.');
	}

	include('functions.php');
	include($DIR_LIBS . 'PLUGINADMIN.php');

	# create the admin area page
	$oPluginAdmin = new PluginAdmin('CurrentMedia');

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
	$lang_select_this = _CM_SELECT_THIS;
	$lang_search_results = _CM_SEARCH_RESULTS;
	$lang_previous = _CM_PREVIOUS;
	$lang_next = _CM_NEXT;
	$lang_page = _CM_PAGE;

	$search_index = array(
		1 => 'DVD',
		2 => 'BluRay',
		3 => 'Books',
		4 => 'Music',
		5 => 'VHS',
		6 => 'VideoGames'
	);

	$type = $_POST['type'];
	$keywords = $_POST['keywords'];
	$page = $_POST['page'];

	$locale = $oPluginAdmin->plugin->getOption('site');
	$aws_key = $oPluginAdmin->plugin->getOption('AWSAccessKeyId');
	$secret_key = $oPluginAdmin->plugin->getOption('AWSSecretKey');
	$associate_id = $oPluginAdmin->plugin->getOption('assocID');

	# if: for Blu-ray we just add "blu" to the search terms to narrow the results
	if ( $type == '2' )
	{
		$type = '1';
		$keywords .= ' blu';
	} # end if

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

	$parameters = array(
		'AssociateTag'		=> $associate_id,
		'Operation'			=> 'ItemSearch',
		'SearchIndex'		=> $search_index[$type],
		'Keywords'			=> urlencode($keywords),
		'ResponseGroup'		=> 'Images,Small',
		'ItemPage'			=> $page
	);

	# loop: each parameter
	foreach( $parameters as $key => $value )
	{
		$fields .= "$key=" . urlencode($value) . '&';
	} # end loop

	$fields = rtrim($fields, '&');
	$xmlfeed = $endpoint . '&' . $fields;
	$xmlfeed = getRequest($secret_key, $xmlfeed, $aws_key);

	$parsedXML = simplexml_load_file($xmlfeed);

	$numItems = $parsedXML->Items->TotalResults;
	$total_pages = $parsedXML->Items->TotalPages;
	$i = 0;

	# loop:
	foreach( $parsedXML->Items->Item as $item )
	{
		$i++;
		$meta = '';

		# if: process books
		if ( $type == '3' )
		{

			# if: author listed
			if ( $item->ItemAttributes->Author )
			{

				$meta .= _CM_AUTHOR_LABEL;

				# foreach:
				foreach ( $item->ItemAttributes->Author as $author )
				{
					$meta .= $author . ', ';
				} # end foreach

				$meta = rtrim($meta, ', ');

			} # end if

		}
		# else if: process music
		else if ( $type == '4' )
		{

			# if: artist listed
			if ( $item->ItemAttributes->Artist )
			{

				$meta .= _CM_BY_LABEL;

				# foreach:
				foreach ( $item->ItemAttributes->Artist as $artist )
				{
					$meta .= $artist . ', ';
				} # end foreach

				$meta = rtrim($meta, ', ');

			} # end if

		}
		# else if: process videos (Blu-ray falls under DVD)
		else if ( $type == '1' || $type == '5' || $type == '2' )
		{

			$directed_by = '';
			$starring = '';

			# if: director listed
			if ( $item->ItemAttributes->Director )
			{

				$directed_by .= _CM_DIRECTOR_LABEL;

				# foreach:
				foreach ( $item->ItemAttributes->Director as $director )
				{
					$directed_by .= $director . ', ';
				} # end foreach

				$directed_by = rtrim($directed_by, ', ');

			} # end if

			# if: actors listed
			if ( $item->ItemAttributes->Actor )
			{

				$starring .= _CM_STARRING_LABEL;

				# foreach:
				foreach ( $item->ItemAttributes->Actor as $actor )
				{
					$starring .= $actor . ', ';
				} # end foreach

				$starring = rtrim($starring, ', ');

			} # end if

			if ( !empty($directed_by) && !empty($starring) )
			{
				$meta = $directed_by . "\n" . $starring;
			}
			else if ( !empty($directed_by) )
			{
				$meta = $directed_by;
			}
			else if ( !empty($starring) )
			{
				$meta = $starring;
			}

		} # end if

		$url = $url_base . 'o/ASIN/' . $item->ASIN . '/' . $associate_id;

		$background_color = ($i % 2) ? '#fff' : '#ddd';

		$search_results .= <<< END
<div style="padding: 1em 0; background-color: $background_color; border-bottom: 0px solid #ccc;">
	<img src="{$item->SmallImage->URL}" style="float: left; margin-right: 1em;" />
	{$item->ItemAttributes->Title} <br />
	{$meta} <br />
	<a href="#cm_plugin" onclick="selectThis($i);">{$lang_select_this}</a>
	<div style="clear: both; height: 1px; overflow: hidden;">&nbsp;</div>

	<input type="hidden" name="cm_asin{$i}" id="i_cm_asin{$i}" value="{$item->ASIN}" />
	<input type="hidden" name="cm_title{$i}" id="i_cm_title{$i}" value="{$item->ItemAttributes->Title}" />
	<input type="hidden" name="cm_meta{$i}" id="i_cm_meta{$i}" value="{$meta}" />
	<input type="hidden" name="cm_image{$i}" id="i_cm_small_image{$i}" value="{$item->SmallImage->URL}" />
	<input type="hidden" name="cm_image{$i}" id="i_cm_medium_image{$i}" value="{$item->MediumImage->URL}" />
	<input type="hidden" name="cm_image{$i}" id="i_cm_large_image{$i}" value="{$item->LargeImage->URL}" />
	<input type="hidden" name="cm_url{$i}" id="i_cm_url{$i}" value="{$url}" />
</div>\n
END;
	} # end loop

	# if:
	if ( $page > 1 )
	{
		$previous_link = '<a href="" id="cm_previous_link">&laquo; ' . $lang_previous . '</a>';
	} # end if

	# if:
	if ( $page < $total_pages )
	{
		$next_link = '<a href="" id="cm_next_link">' . $lang_next . ' &raquo;</a>';
	} # end if

	# if:
	if ( isset($previous_link) )
	{
		$navigation =  $previous_link . ' | ';
	} # end if

	$navigation .= "<strong>{$lang_page} {$page}</strong>";

	# if:
	if ( isset($next_link) )
	{
		$navigation .= ' | ' . $next_link;
	} # end if

	print <<< END
	<!-- <a href="{$xmlfeed}" target="_blank">xml</a> -->
	<p> <strong>{$lang_search_results}</strong> </p>
	<p> {$navigation} </p>
	{$search_results}
	<p> {$navigation} </p>\n
END;
