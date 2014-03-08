<?php

/**
 * If the sql_real_escape_string() is not defined (older Nucleus versions)
 */
if ( !function_exists('sql_real_escape_string') )
{

	function sql_real_escape_string($value, $connection = FALSE)
	{
		global $MYSQL_CONN;

		if ( !$connection )
		{
			$connection = $MYSQL_CONN;
		}

		return mysql_real_escape_string($value, $connection);
	}

}

class NP_CurrentMedia extends NucleusPlugin
{
	/**
	 * The plugin table's name
	 * @var string
	 * @access private
	 */
	private $table;


	/**
	 * This method returns the plugin name
	 * @access public
	 * @return string
	 */
	public function getName()
	{
		return 'Current Media';
	} # end method getName()


	/**
	 * This method returns the author name
	 * @access public
	 * @return string
	 */
	public function getAuthor()
	{
		return 'gRegor Morrill';
	} # end method getAuthor()


	/**
	 * This method returns the plugin author URL
	 * @access public
	 * @return string
	 */
	public function getURL()
	{
		return 'http://gregorlove.com';
	} # end method getURL()


	/**
	 * This method returns the plugin version
	 * @access public
	 * @return string
	 */
	public function getVersion()
	{
		return '1.0';
	} # end method getVersion()


	/**
	 * This method returns the plugin description
	 * @return string
	 */
	public function getDescription()
	{
		return 'Display a block of the music, book, movie, or video game that you are currently enjoying on each blog post.';
	} # end method getDescription()


	/**
	 * This method returns the plugin table list
	 * @access public
	 * @return array
	 */
	public function getTableList()
	{
		return array( sql_table('plugin_currentmedia') );
	} # end method getTableList()


	/**
	 * This method returns the events this plugin subscribes to
	 * @access public
	 * @return array
	 */
	public function getEventList()
	{
		return array(
			'PostAddItem', 
			'AddItemFormExtras', 
			'EditItemFormExtras', 
			'PreUpdateItem', 
			'PostDeleteItem'
		);
	} # end method getEventList()


	/**
	 * This method indicates the plugin has an admin area
	 * @access public
	 * @return int
	 */
	public function hasAdminArea()
	{
		return 1;
	} # end method hasAdminArea()


	/**
	 * This method returns the minimum Nucleus version required to run the plugin
	 * @access public
	 * @return int
	 */
	public function getMinNucleusVersion()
	{
		return 350;
	} # end method getMinNucleusVersion()


	/**
	 * This method returns information about the features this plugin supports
	 * @param string $feature
	 * @access public
	 * @return int
	 **/
	public function supportsFeature($feature)
	{

		# begin switch
		switch( $feature )
		{
			case 'SqlTablePrefix':
				return 1;
			break;

			case 'SqlApi':
				return 1;
			break;

			default:
				return 0;
			break;
		} # end switch

	} # end method supportsFeature()


	/**
	 * This method handles initializing the plugin
	 * @access public
	 */
	public function init()
	{
		# Set the table name
		$this->table = sql_table('plugin_currentmedia');

		# Get the language name
		$language = preg_replace('#[\\|/]#', '', getLanguageName());

		# if: a language file exists matching the language name, include it
		if ( file_exists($this->getDirectory() . 'languages/' . $language . '.php') )
		{
			include_once($this->getDirectory() . 'languages/' . $language . '.php');
		}
		# else: default to include the english file
		else
		{
			include_once($this->getDirectory() . 'languages/english.php');
		} # end if

	} # end method init()


	/**
	 * This method installs the plugin
	 * There is an option to use your own Amazon Associate ID. By default this associate ID is for Nucleus, meaning any sales will generate commissions for Nucleus.
	 * @access public
	 */
	public function install()
	{
		$query = <<< END
CREATE TABLE IF NOT EXISTS `{$this->table}` (
	`cm_id` INT(10) UNSIGNED NOT NULL,
	`cm_type` SMALLINT(6) NOT NULL DEFAULT '0',
	`cm_asin` VARCHAR(25) NOT NULL DEFAULT '',
	`cm_heading` VARCHAR(255) NOT NULL DEFAULT '',
	`cm_title` VARCHAR(255) NOT NULL DEFAULT '',
	`cm_description` TEXT,
	`cm_url` VARCHAR(255) NOT NULL DEFAULT '',
	`cm_selected_image` ENUM('small','medium','large','none') NOT NULL DEFAULT 'none',
	`cm_small_image` VARCHAR(255) NOT NULL DEFAULT '',
	`cm_medium_image` VARCHAR(255) NOT NULL DEFAULT '',
	`cm_large_image` VARCHAR(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`cm_id`)
)
END;
		sql_query($query);

		$this->createOption('site', 'Amazon site to use for searches:', 'select', 'en', 'Amazon.com|en|Amazon.ca|ca|Amazon.de|de|Amazon.fr|fr|Amazon.jp|jp|Amazon.co.uk|uk');
		$this->createOption('AWSAccessKeyId', 'AWS Access Key', 'text');
		$this->createOption('AWSSecretKey', 'AWS Secret Key', 'text');
		$this->createOption('assocID', '(Optional) Your Amazon Associate ID:', 'text', 'nucleuscms-20');
		$this->createOption('deletetables', 'Delete this plugin\'s table and data when uninstalling?', 'yesno', 'no');
	} # end method install()


	/**
	 * This method uninstalls the plugin
	 * This method drops the table from the database on uninstall, if the appropriate plugin option is set to 'yes'.  It is set to 'no' by default.
	 * Generally, when upgrading this plugin, users should set the "delete tables" option to 'no', uninstall it, and re-install the newer version.
	 * @access public
	 */
	public function unInstall()
	{
		# if: plugin set to delete tables on uninstall
		if ( $this->getOption('deletetables') == 'yes' )
		{
			sql_query('DROP TABLE ' . $this->table);
		} # end if

	} # end method unInstall()


	/**
	 * This method is used to display the plugin data when called from templates
	 * @param object &$item
	 * @param string $field
	 * @access public
	 **/
	public function doTemplateVar(&$item, $field = '')
	{
		$itemid = $item->itemid;

		# query media for this item
		$query = 'SELECT * FROM ' . $this->table . ' WHERE `cm_id` = ' . $itemid;
		$result = sql_query($query);

		# if: there is media data, retrieve and output it.
		if ( sql_num_rows($result) != 0 )
		{
			$media_data = $this->_getMediaData($result);

			# switch: set the image URL based on the 'selected_image' field
			switch ( $media_data['selected_image'] )
			{
				case 'small':
					$image_url = $media_data['small_image'];
				break;

				case 'medium':
					$image_url = $media_data['medium_image'];
				break;

				case 'large':
					$image_url = $media_data['large_image'];
				break;

				default:
					$image_url = '';
				break;
			} # end switch

			# switch: based on the specific field requested
			switch ( $field )
			{
				case 'heading':
					echo $media_data['heading'];
				break;

				case 'image':

					# if: an image is selected for this CurrentMedia, build an <img /> element and return it
					if ( $media_data['selected_image'] != 'none' )
					{
						echo '<img src="' . $image_url . '" alt="" title="' . $media_data['title'] . '" />';
					} # end if

				break;

				case 'image_url':

					# if: an image is selected for this CurrentMedia, return the raw url
					if ( $media_data['selected_image'] != 'none' )
					{
						echo $image_url;
					} # end if

				break;

				case 'title':

					# if: a URL exists for this CurrentMedia
					if ( !empty($media_data['url']) )
					{
						echo '<a href="' . $media_data['url'] . '">' . $media_data['title'] . '</a>';
					}
					# else:
					else
					{
						echo $media_data['title'];
					} # end if

				break;

				case 'description':
					echo $media_data['description'];
				break;

				# Default layout when calling the TemplateVar with no field name
				default:

					# variable defaults
					$image = '';

					# if: an image is selected for this CurrentMedia
					if ( $media_data['selected_image'] != 'none' )
					{
						$image = '<p class="cm_image"> <img src="' . $image_url . '" alt="" title="' . $media_data['title'] . '" /> </p>';
					} # end if

					# if: a URL exists for this CurrentMedia
					if ( !empty($media_data['url']) )
					{
						$title = '<a href="' . $media_data['url'] . '">' . $media_data['title'] . '</a>';
					}
					else
					{
						$title = $media_data['title'];
					}

					print <<< END
	<div class="cm_media">
		<p class="cm_heading"> {$media_data['heading']} </p>
		{$image}
		<p class="cm_title"> {$title} </p>
		<p class="cm_description"> {$media_data['description']} </p>
	</div>\n
END;
				break;
			} # end switch

		} # end if

	} # end method doTemplateVar()


	/**
	 * This hook is used to display the plugin form on the 'Add Item' page
	 * @param array $data
	 * @access public
	 **/
	public function event_AddItemFormExtras($data)
	{
		$this->_showPluginForm();
	} # end method event_AddItemFormExtras()


	/**
	 * This hook is used to display the plugin form on the 'Edit Item page
	 * @param array $data
	 * @access public
	 **/
	public function event_EditItemFormExtras($data)
	{
		$this->_showPluginForm('edit', $data['itemid']);
	} # end method event_EditItemFormExtras()


	/**
	 * This hook is used to handle adding the media item from the 'Add Item' page.
	 * @param array $data
	 * @access public
	 **/
	public function event_PostAddItem($data)
	{
		$action = requestVar('cm_action');

		# if: action is specified and equals 'add'
		if ( $action == 'add' )
		{
			$itemid = $data['itemid'];
			$type = intval(requestVar('cm_type'));
			$asin = sql_real_escape_string(requestVar('cm_asin'));
			$heading = sql_real_escape_string(requestVar('cm_heading'));
			$title = sql_real_escape_string(requestVar('cm_title'));
			$description = sql_real_escape_string(requestVar('cm_description'));
			$url = sql_real_escape_string(requestVar('cm_url'));
			$selected_image = sql_real_escape_string(requestVar('cm_selected_image'));
			$selected_image = ( empty($selected_image)) ? 'none' : $selected_image;
			$small_image = sql_real_escape_string(requestVar('cm_small_image'));
			$medium_image = sql_real_escape_string(requestVar('cm_medium_image'));
			$large_image = sql_real_escape_string(requestVar('cm_large_image'));

			$query = <<< END
INSERT INTO {$this->table} SET
	`cm_id` = {$itemid},
	`cm_type` = {$type},
	`cm_asin` = '{$asin}',
	`cm_heading` = '{$heading}',
	`cm_title` = '{$title}',
	`cm_description` = '{$description}',
	`cm_url` = '{$url}',
	`cm_selected_image` = '{$selected_image}',
	`cm_small_image` = '{$small_image}',
	`cm_medium_image` = '{$medium_image}',
	`cm_large_image` = '{$large_image}'
END;
			sql_query($query);
		} # end if

	} # end method event_PostAddItem()


	/**
	 * This hook is used to handle updating the media item from the 'Edit Item' page.
	 * By default this does nothing, unless the user wants to change or delete the media attached to the item.
	 * If updating, there are two possibilities: inserting new media (if nothing was added previously), or updating pre-existing media
	 * @param array $data
	 **/
	public function event_PreUpdateItem($data)
	{
		$itemid = $data['itemid'];
		$action = requestVar('cm_action');
		$type = intval(requestVar('cm_type') );
		$asin = sql_real_escape_string(requestVar('cm_asin') );
		$heading = sql_real_escape_string(requestVar('cm_heading') );
		$title = sql_real_escape_string(requestVar('cm_title') );
		$description = sql_real_escape_string(requestVar('cm_description') );
		$url = sql_real_escape_string(requestVar('cm_url') );
		$selected_image = sql_real_escape_string(requestVar('cm_selected_image') );
		$selected_image = ( empty($selected_image) ) ? 'none' : $selected_image;
		$small_image = sql_real_escape_string(requestVar('cm_small_image') );
		$medium_image = sql_real_escape_string(requestVar('cm_medium_image') );
		$large_image = sql_real_escape_string(requestVar('cm_large_image') );

		# if: add/update (as appropriate) media item
		if ( $action == 'add' )
		{
			$query = <<< END
INSERT INTO {$this->table} SET
	`cm_id` = {$itemid},
	`cm_type` = {$type},
	`cm_asin` = '{$asin}',
	`cm_heading` = '{$heading}',
	`cm_title` = '{$title}',
	`cm_description` = '{$description}',
	`cm_url` = '{$url}',
	`cm_selected_image` = '{$selected_image}',
	`cm_small_image` = '{$small_image}',
	`cm_medium_image` = '{$medium_image}',
	`cm_large_image` = '{$large_image}'
ON DUPLICATE KEY UPDATE
	`cm_type` = {$type},
	`cm_asin` = '{$asin}',
	`cm_heading` = '{$heading}',
	`cm_title` = '{$title}',
	`cm_description` = '{$description}',
	`cm_url` = '{$url}',
	`cm_selected_image` = '{$selected_image}',
	`cm_small_image` = '{$small_image}',
	`cm_medium_image` = '{$medium_image}',
	`cm_large_image` = '{$large_image}'
END;
			sql_query($query);
		}
		# else if: delete media item
		else if ( $action == 'delete' )
		{
			$query = "DELETE FROM `{$this->table}` WHERE `cm_id` = {$itemid} LIMIT 1";
			sql_query($query);
		} # end if

	} # end method event_PreUpdateItem()


	/**
	 * This hook is used to handle deleting media data in case the item it is attached to is deleted. Prevents plugin table from having extra entries for items that have been deleted.
	 * @param array $data
	 **/
	public function event_PostDeleteItem($data)
	{
		$itemid = $data['itemid'];
		$query = "DELETE FROM `{$this->table}` WHERE `cm_id` = {$itemid} LIMIT 1";
		sql_query($query);
	} # end method event_PostDeleteItem()


	/**
	 * This function is used to show the plugin's form on the 'Add Item' and 'Edit Item' forms.
	 * Depending whether the user is adding or editing a post - plus whether or not media was previously added - is taken into account using javascript and the $is_media variable.
	 * @param string $mode
	 * @param string $itemid
	 * @access private
	 **/
	private function _showPluginForm($mode = 'add', $itemid = '')
	{
		# initialize variables
		$action = 'none';
		$is_entered = 0;
		$image = $checked2 = $checked3 = $checked4 = $checked5 = $checked6 = '';
		$checked1 = ' checked="checked"';

		print <<< END
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
		<script type="text/javascript" src="plugins/currentmedia/cm.js"></script>

		<h3 id="cm_plugin"> Current Media Plugin </h3>\n
END;

		# default assumes no media on the item
		$is_media = FALSE;

		# retrieve the Amazon API options
		$aws_key = $this->getOption('AWSAccessKeyId');
		$secret_key = $this->getOption('AWSSecretKey');

		$delete_class = ' class="hide"';
		$reset_class = ' class="hide"';
		$selected_class = ' class="hide"';
		$image_class = ' class="hide"';

		# internationalization
		$lang_delete_this_media = _CM_DELETE_THIS_MEDIA;
		$lang_reset_this_media = _CM_RESET_THIS_MEDIA;
		$lang_image_label = _CM_IMAGE_LABEL;
		$lang_small = _CM_SMALL;
		$lang_medium = _CM_MEDIUM;
		$lang_large = _CM_LARGE;
		$lang_remove_image = _CM_REMOVE_IMAGE;
		$lang_heading_label = _CM_HEADING_LABEL;
		$lang_title_label = _CM_TITLE_LABEL;
		$lang_description_label = _CM_DESCRIPTION_LABEL;
		$lang_url_label = _CM_URL_LABEL;
		$lang_edit_this = _CM_EDIT_THIS;
		$lang_dvd = _CM_DVD;
		$lang_blu_ray = _CM_BLU_RAY;
		$lang_books = _CM_BOOKS;
		$lang_music = _CM_MUSIC;
		$lang_vhs = _CM_VHS;
		$lang_video_games = _CM_VIDEO_GAMES;
		$lang_enter_custom_media = _CM_ENTER_CUSTOM_MEDIA;
		$lang_keywords_label = _CM_KEYWORDS_LABEL;
		$lang_search_button = _CM_SEARCH_BUTTON;
		$lang_currently = _CM_CURRENTLY;
		$lang_listening = _CM_LISTENING;
		$lang_watching = _CM_WATCHING;
		$lang_reading = _CM_READING;
		$lang_playing = _CM_PLAYING;

		# if: AWS key or secret key aren't specified, display message in place of plugin form
		if ( empty($aws_key) || empty($secret_key) )
		{
			echo '<p style="color: red;"> <em>You need to specify your AWS Key and AWS Secret Key in the <a href="index.php?action=pluginoptions&plugid=', $this->plugid, '">plugin options</a> before you can use this plugin.</em> </p>';
			return;
		} # end if

		# if: set up for the 'Edit Item' form
		if ( $mode == 'edit' )
		{
			# query to see if a media item exists for this post
			$query = "SELECT * FROM `{$this->table}` WHERE `cm_id` = '{$itemid}'";
			$result = sql_query($query);

			# if: a media item exists for this post
			if ( sql_num_rows($result) == 1 )
			{
				$action = 'add';
				$is_entered = 1;

				# load the media data
				$media_data = $this->_getMediaData($result);

				# set up the correct checked media type
				$checked1 = ( $media_data['type'] == 1 ) ? ' checked="checked"' : '';
				$checked2 = ( $media_data['type'] == 2 ) ? ' checked="checked"' : '';
				$checked3 = ( $media_data['type'] == 3 ) ? ' checked="checked"' : '';
				$checked4 = ( $media_data['type'] == 4 ) ? ' checked="checked"' : '';
				$checked5 = ( $media_data['type'] == 5 ) ? ' checked="checked"' : '';
				$checked6 = ( $media_data['type'] == 6 ) ? ' checked="checked"' : '';

				# if: small image selected and the image URL is provided
				if ( $media_data['selected_image'] == 'small' && !empty($media_data['small_image']) )
				{
					$image = '<img src="' . $media_data['small_image'] . '" />';
				}
				# else if: medium image selected and the image URL is provided
				else if ( $media_data['selected_image'] == 'medium' && !empty($media_data['medium_image']) )
				{
					$image = '<img src="' . $media_data['medium_image'] . '" />';
				}
				# else if: large image selected and the image URL is provided
				else if ( $media_data['selected_image'] == 'large' && !empty($media_data['large_image']) )
				{
					$image = '<img src="' . $media_data['large_image'] . '" />';
				}
				# else: no image
				else
				{
					$image = 'None';
				} # end if

				# set the media flag to true
				$is_media = TRUE;

				# remove the 'hide' class so the media will display on the edit form
				$selected_class = '';

				# remove the 'hide' class so the 'delete' link will display on the edit form
				$delete_class = '';

				# if: image is selected, remove the 'hide' class so it will display on the form
				if ( !empty($image) )
				{
					$image_class = '';
				} # end if

			} # end if

		} # end if

		# Start outputing the plugin form
		print <<< END
	<div id="i_cm_selected"{$selected_class} style="border-bottom: 1px solid #ccc;">
		<div id="i_cm_delete"{$delete_class}> <a href="#" id="cm_delete_link" style="color: #f00;">{$lang_delete_this_media}</a> </div>
		<div id="i_cm_reset"{$reset_class}> <a href="#" id="cm_reset_link">{$lang_reset_this_media}</a> </div>
		<div>
			<p> <label for="i_cm_image" style="float: left; width: 100px;">{$lang_image_label}</label> </p>
			<div style="float: left; margin-right: 1em;" id="i_cm_image">{$image}</div>
			<div id="i_cm_image_controls">
				<a href="#" id="i_cm_select_small">{$lang_small}</a> |
				<a href="#" id="i_cm_select_medium">{$lang_medium}</a> |
				<a href="#" id="i_cm_select_large">{$lang_large}</a> |
				<a href="#" id="i_cm_image_remove">{$lang_remove_image}</a>
			</div>
			<div style="clear: both; height: 1px; overflow: hidden;">&nbsp;</div>
		</div>

		<p> <label for="i_cm_heading" style="float: left; width: 100px;">{$lang_heading_label}</label> <input type="text" name="cm_heading" id="i_cm_heading" size="20" value="{$media_data['heading']}" readonly="readonly" style="background-color: #eee;" /> </p>
		<p> <label for="i_cm_title" style="float: left; width: 100px;">{$lang_title_label}</label> <input type="text" name="cm_title" id="i_cm_title" size="100" value="{$media_data['title']}" readonly="readonly" style="background-color: #eee;" /> </p>
		<p> <label for="i_cm_description" style="float: left; width: 100px;">{$lang_description_label}</label> <textarea name="cm_description" id="i_cm_description" rows="2" cols="100" readonly="readonly" style="background-color: #eee; width: auto;">{$media_data['description']}</textarea> </p>
		<p> <label for="i_cm_url" style="float: left; width: 100px;">{$lang_url_label}</label> <input type="text" name="cm_url" id="i_cm_url" size="100" value="{$media_data['url']}" readonly="readonly" style="background-color: #eee;" /> </p>
		<p style="margin-left: 100px;"> <input type="checkbox" name="cm_edit" id="i_cm_edit" value="1" /> <label for="i_cm_edit">{$lang_edit_this}</label> </p>
	</div>

	<p>
		<input type="radio" name="cm_type" id="i_cm_type1" value="1"{$checked1} /> <label for="i_cm_type1">{$lang_dvd}</label>
		<input type="radio" name="cm_type" id="i_cm_type2" value="2"{$checked2} /> <label for="i_cm_type2">{$lang_blu_ray}</label>
		<input type="radio" name="cm_type" id="i_cm_type3" value="3"{$checked3} /> <label for="i_cm_type3">{$lang_books}</label>
		<input type="radio" name="cm_type" id="i_cm_type4" value="4"{$checked4} /> <label for="i_cm_type4">{$lang_music}</label>
		<input type="radio" name="cm_type" id="i_cm_type5" value="5"{$checked5} /> <label for="i_cm_type5">{$lang_vhs}</label>
		<input type="radio" name="cm_type" id="i_cm_type6" value="6"{$checked6} /> <label for="i_cm_type6">{$lang_video_games}</label> <br />
		<input type="checkbox" name="cm_custom" id="i_cm_custom" value="1" /> <label for="i_cm_custom">{$lang_enter_custom_media}</label>
	</p>

	<p> <label for="i_cm_keywords">{$lang_keywords_label}</label> <input type="text" name="cm_keywords" id="i_cm_keywords" size="30" /> </p>

	<p> <input type="submit" id="i_cm_search" value="{$lang_search_button}" /> <span id="i_cm_loading1"><img src="plugins/currentmedia/ajax-loader.gif" /></span> </p>

	<input type="hidden" name="cm_asin" id="i_cm_asin" value="{$media_data['asin']}" />
	<input type="hidden" name="cm_small_image" id="i_cm_small_image" value="{$media_data['small_image']}" />
	<input type="hidden" name="cm_medium_image" id="i_cm_medium_image" value="{$media_data['medium_image']}" />
	<input type="hidden" name="cm_large_image" id="i_cm_large_image" value="{$media_data['large_image']}" />
	<input type="hidden" name="cm_selected_image" id="i_cm_selected_image" value="{$media_data['selected_image']}" />
	<input type="hidden" name="cm_page" id="i_cm_page" value="1" />

	<input type="hidden" name="cm_listening_words" id="i_cm_listening_words" value="{$lang_currently} {$lang_listening}" />
	<input type="hidden" name="cm_watching_words" id="i_cm_watching_words" value="{$lang_currently} {$lang_watching}" />
	<input type="hidden" name="cm_reading_words" id="i_cm_reading_words" value="{$lang_currently} {$lang_reading}" />
	<input type="hidden" name="cm_playing_words" id="i_cm_playing_words" value="{$lang_currently} {$lang_playing}" />
	<input type="hidden" name="cm_action" id="i_cm_action" value="{$action}" />
	<input type="hidden" name="cm_is_entered" id="i_cm_is_entered" value="{$is_entered}" />

	<div id="i_cm_results"></div>
	<span id="i_cm_loading2"><img src="plugins/currentmedia/ajax-loader.gif" /></span>\n
END;
	} # end method _showPluginForm()


	/**
	 * This method is used to put the media data values for a specific item into an array
	 * @param resource $result
	 * @return array
	 * @access private
	 */
	private function _getMediaData($result)
	{
		$array['type'] = sql_result($result, 0, 'cm_type');
		$array['asin'] = sql_result($result, 0, 'cm_asin');
		$array['heading'] = sql_result($result, 0, 'cm_heading');
		$array['title'] = sql_result($result, 0, 'cm_title');
		$array['description'] = sql_result($result, 0, 'cm_description');
		$array['url'] = sql_result($result, 0, 'cm_url');
		$array['selected_image'] = sql_result($result, 0, 'cm_selected_image');
		$array['small_image'] = sql_result($result, 0, 'cm_small_image');
		$array['medium_image'] = sql_result($result, 0, 'cm_medium_image');
		$array['large_image'] = sql_result($result, 0, 'cm_large_image');

		return $array;
	} # end method _getMediaData()

}
