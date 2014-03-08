<?php

	/**
	 * This function will take an existing Amazon request and change it so that it will be usable
	 * with the new authentication.
	 * Source: <http://www.a2sdeveloper.com/page-rest-authentication-for-php4.html>
	 *
	 * @param string $secret_key - your Amazon AWS secret key
	 * @param string $request - your existing request URI
	 * @param string $access_key - your Amazon AWS access key
	 * @param string $version - (optional) the version of the service you are using
	 **/
	function getRequest($secret_key, $request, $access_key = FALSE, $version = '2010-11-01')
	{
		# Get a nice array of elements to work with
		$uri_elements = parse_url($request);

		# Grab our request elements
		$request = $uri_elements['query'];

		# Throw them into an array
		parse_str($request, $parameters);

		# Add the new required paramters
		$parameters['Timestamp'] = gmdate("Y-m-d\TH:i:s\Z");
		$parameters['Version'] = $version;

		if ( strlen($access_key) > 0 )
		{
			$parameters['AWSAccessKeyId'] = $access_key;
		}

		# The new authentication requirements need the keys to be sorted
		ksort($parameters);

		# Create our new request
		foreach ( $parameters as $parameter => $value )
		{
			# We need to be sure we properly encode the value of our parameter
			$parameter = str_replace("%7E", "~", rawurlencode($parameter));
			$value = str_replace("%7E", "~", rawurlencode($value));
			$request_array[] = $parameter . '=' . $value;
		}

		# Put our & symbol at the beginning of each of our request variables and put it in a string
		$new_request = implode('&', $request_array);

		# Create our signature string
		$signature_string = "GET\n{$uri_elements['host']}\n{$uri_elements['path']}\n{$new_request}";

		# Create our signature using hash_hmac
		$signature = urlencode(base64_encode(hash_hmac('sha256', $signature_string, $secret_key, TRUE) ) );

		# Return our new request
		return "http://{$uri_elements['host']}{$uri_elements['path']}?{$new_request}&Signature={$signature}";
	}


	/**
	 *
	 **/
	function synchronize($xml_feed, &$items, $options)
	{
		$parsedXML = simplexml_load_file($xml_feed);

		# foreach:
		foreach( $parsedXML->Items->Item as $item )
		{
			# Initialize variables
			$meta = '';
			$directed_by = '';
			$starring = '';

			$asin = (string) $item->ASIN;
			$product_group = (string) $item->ItemAttributes->ProductGroup;

			switch($product_group)
			{
				case 'DVD':
				case 'Video':
					$type = 1;
					$heading = $options['lang_currently'] . ' ' . $options['lang_watching'];
				break;

				case 'Music':
					$type = 4;
					$heading = $options['lang_currently'] . ' ' . $options['lang_listening'];
				break;

				case 'Book':
					$type = 3;
					$heading = $options['lang_currently'] . ' ' . $options['lang_reading'];
				break;

				case 'Video Games':
					$type = 6;
					$heading = $options['lang_currently'] . ' ' . $options['lang_playing'];
				break;
			}

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

			$temp['cm_type'] = $type;
			$temp['cm_heading'] = $heading;
			$temp['cm_title'] = (string) $item->ItemAttributes->Title;
			$temp['cm_description'] = $meta;
			$temp['cm_url'] = $options['url_base'] . 'o/ASIN/' . $asin . '/' . $options['associate_id'];
			$temp['cm_small_image'] = (string) $item->SmallImage->URL[0];
			$temp['cm_medium_image'] = (string) $item->MediumImage->URL[0];
			$temp['cm_large_image'] = (string) $item->LargeImage->URL[0];

			$items[$asin] = $temp;
			unset($temp);

		} # end loop

	}
