# CurrentMedia Nucleus Plugin

__As of 2015-07-12 I am no longer using Nucleus CMS on my site or maintaining this plugin.__

__If you would like to continue maintaining this plugin's code, I'm willing to transfer ownership of the repository. Pull requests and forks are also welcome.__

Nucleus CMS plugin to search Amazon and display the music, book, movie, or video game that you are currently enjoying.

__Please read this ENTIRE file before contacting for support.__


## Contents

1. Introduction
2. Requirements and Installation Instructions
3. Amazon Web Services Account
4. Upgrade Instructions
5. Plugin Options
6. Usage
7. Questions
8. Known Issues / To Do
9. Bug Reports / Contact
10. Thanks
11. Changelog


## Introduction

The CurrentMedia plugin allows you to display a block of the music, book, movie, or video game that you are currently enjoying on each blog post. You may use Amazon.com search results or you may enter custom values.

If you are an Amazon associate (a free program to join), you can have your Associate ID included in the media links, meaning potential commission fees if viewers click the links and buy the item themselves.


## Requirements And Installation Instructions

This plugin requires Nucleus version 3.5+, and a javascript enabled web browser.

This plugin also requires an Amazon Web Services (AWS) account. More information below.

Unzip the file 'CurrentMedia.zip' and upload the contents into your plugin directory. By default this directory is 'nucleus/plugins', but may vary depending if you used different folder names when you installed Nucleus.

Login to your site's Nucleus administration area, and go to the plugin page under "Nucleus Management".  Scroll down to the "Install New Plugin" heading, and select "CurrentMedia" from the drop-down selection box.  Then click "Install".

The plugin should be installed now and will show up in the list of installed plugins (same page).


## Amazon Web Services Account

This plugin requires an Amazon Web Services account in order to send authenticated requests to Amazon's Product Advertising API.

Create your account here: <http://aws.amazon.com/>

You will need your "AWS Access Key ID" and "Secret Access Key."  These are not emailed to you. For more information on getting these values, please refer to: <http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/index.html?ViewingCredentials.html>

After installing or upgrading the CurrentMedia plugin, enter the AWS Access Key ID and Secret Access Key in the plugin options.

**Important Note from Amazon:**

Your Secret Access Key is a secret and only you and AWS should know it. It is important to keep it confidential to protect your account. Never include it in your requests to AWS, and never e-mail it to anyone. Do not share it outside your organization, even if an inquiry appears to come from AWS or Amazon.com. No one who legitimately represents Amazon will ever ask you for your Secret Access Key.


## Upgrade Instructions

It is highly recommended that you back up your existing database before performing an upgrade.

### Upgrading from v0.5 or v0.4:

1. On the plugin page of the Nucleus Admin Panel, click 'edit options' for the CurrentMedia plugin and make sure the option to "Delete this plugin's table and data when uninstalling?" is set to 'no'.
2. On the plugin page of the Nucleus Admin Panel, uninstall the CurrentMedia plugin.
3. Delete the CurrentMedia plugin files and the currentmedia/ subdirectory.
4. Upload the new version of Current Media into the plugin directory. This includes all files in the currentmedia/ subdirectory.
5. On the plugin page of the Nucleus Admin Panel, install the CurrentMedia plugin.

### Upgrading from v0.3 or earlier:

1. In file NP_CurrentMedia.php, make sure the sql_query line in function "unInstall()" is commented out.  Comments are indicated by two forward slashes.  The line should look like:

    <code>// sql_query('DROP TABLE ' . sql_table('plugin_currentmedia') );</code>
2. On the plugin page of the Nucleus Admin Panel, uninstall the CurrentMedia plugin.
3. Upload the new version CurrentMedia files on top of the existing CurrentMedia files. This includes all files in the currentmedia/ subdirectory.
4. On the plugin page of the Nucleus Admin Panel, install the CurrentMedia plugin.

STEP 1 is important because if that line is not commented out for some reason, all previous media data will be deleted upon uninstalling the plugin. Default installations of CurrentMedia have this line commented out, so unless you have uncommented that line, everything should be OK. It's a good idea to double check, though, before uninstalling the plugin.


## Plugin Options

From the plugin page, click "edit options" on the Current Media plugin.

First, there is an option to choose which Amazon site to search.  Then there are two fields for your AWS Access Key and AWS Secret Key.  Refer to the Amazon Web Services Account part of this document for more information about these fields.

If you have an Amazon Associate ID, you may enter it in Associate ID field. This will be used in the creation of all links, meaning potential commission fees if people click the links and buy the item.  The default associate ID is for the Nucleus project, meaning the Nucleus project will receive any commission fees generated unless you change the Associate ID.


## Usage

To display the CurrentMedia block on your Nucleus posts, insert the tag `<%CurrentMedia%>` in the appropriate template(s).  This plugin works per-item, so the tag must be in a template, not a skin.  _It will not work in a skin._

The CurrentMedia block is constructed using a `<div>` element and `<p>` elements around each line. The visual style can be controlled using CSS. Add definitions for these CSS classes to your stylesheet:

* .cm_media: `<div>` element that wraps the individual lines
* .cm_heading: `<p>` element that wraps the media heading (e.g. "Currently Watching")
* .cm_image: `<p>` element that wraps the media `<img>` element
* .cm_title: `<p>` element that wraps the media title
* .cm_description: `<p>` element that wraps the media description

If you desire to customize the display even further, you can insert specific fields in your template like so:

* `<%CurrentMedia(heading)%>`
* `<%CurrentMedia(image)%>` (generates the `<img>` element)
* `<%CurrentMedia(image_url)%>` (just the URL to the image)
* `<%CurrentMedia(title)%>`
* `<%CurrentMedia(description)%>`

When adding a new blog post, you will see a section for the CurrentMedia plugin under the "Extra Plugin Options" heading.  Choose the type of media you would like to lookup and enter search terms in the keywords field. The Amazon results will appear directly below.  Click "Select This" to select one of the search results.  Beside the media's image are links to select the small, medium, or large image.  The default choice is "small."

After selecting an Amazon item, you may select the "Edit This" check box to make any text edits to the heading, title, description, or URL.

You also have the option of not searching Amazon and instead entering your own custom media by selecting "Enter custom media instead of searching Amazon."

When editing an item, if no media item has been selected previously, the procedure is the same as above.  If a media item has been selected previously, you will see the media information along with a link to "Delete This Media." To change the media item, simply perform another search and select a new item.


## Questions

Q: I clicked 'delete', why wasn't the media data deleted from the weblog item?  
A: The actual deletion isn't committed until you finalize your edit of the blog post by clicking "Edit".

Q: I clicked 'change' and selected another media item, why doesn't it change on the weblog item?  
A: The actual update of the media item isn't committed until you finalize your edit of the blog post by clicking "Edit".


## Known Issues / To Do

When entering a custom media item, there is not currently a way to select an associated image.


## Bug Reports / Contact

### 2014 UPDATE:

As of version 1.0, I will not be actively developing this plugin further. The code is open source and I have made it available on GitHub so that others may more easily take it and do what they like to it.

<https://github.com/gRegorLove/nucleus-plugin-currentmedia>

You may still post on the Nucleus forum and someone can probably help you.

### OLDER:

Please post on the Nucleus forums *FIRST*.  That is my preferred method of communication.  I will do my best to check at least once a week on there, typically more often than that.  If for some reason you don't hear back from me on the forum within a week, feel free to visit my website and use my contact form there.

Nucleus forums: http://forum.nucleuscms.org  
My site: http://gregorlove.com


## Thanks

Thanks to jaal for the help/inspiration to add different language Amazon sites.

Thanks to roel for his update to NP_CustomField.  I took his idea for the plugin option whether to delete the table data upon uninstall.

And last but not least, thanks for the users of this plugin for their feedback and their patience with me fixing things. :)


## Changelog

Version 1.0  
* Minor code cleanup
* Updated the jQuery version and some of the javascript to be compatible with it

Version 0.9  
* Overhauled the plugin entirely.
* Using jQuery from Google Code to display search results inline; no more popup.
* Now supports customizing the media / entering your own without searching Amazon.

Version 0.5  
* Added plugin capability to list "Currently Playing" video games
* Added DOCTYPE to popup search window
* Corrected minor bug that would show PHP errors for books with no authors listed
* Cleaned up/streamlined miscellaneous parts of the code. (I'm a neat freak sometimes :D )

Version 0.4  
* Fixed "apostrophe" bug by escaping certain characters before inserting in the database.
* Moved popup stylesheet to its own css file.
* Added Amazon.de as a search option.
* Added language options (for the plugin words that display on the weblog)
* Added plugin option to delete the tables and data on uninstall, defaulted to 'no'. (thanks roel!)
* Changed to use HTTP_POST_VARS and HTTP_GET_VARS for compatibility with PHP versions < 4.1.0
* Added correctly functioning supportsFeature switch statement.
* Added "Upgrade Instructions", "Plugin Options", and "Thanks" sections to this README file.

Version 0.3  
* Corrected missing IDs from form fields that was causing javascript errors in Mozilla Firefox (and maybe Opera).
* Updated file structure and coding for easier readability .

Version 0.2  
* Fixed a bug with embedded FORM tags.  The bug caused the "Extended Entry" field to not be stored in the database.
* Added CSS to the popup window to make it look like the default Nucleus admin area.
* Stable release.

Version 0.1  
* Stable pre-release.
