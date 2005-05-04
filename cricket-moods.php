<?php
/**
Plugin Name: Cricket Moods
Plugin URI: http://dev.wp-plugins.org/wiki/CricketMoods
Description: Allows an author to add multiple mood tags and mood smilies to every post.
Version: 1.0.0
Author: Keith "kccricket" Constable
Author URI: http://kccricket.net/
*/

/**
Copyright (c) 2005 Keith Constable

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

// The URL that contains your smilie images.  Must include a trailing slash.
define('CM_IMAGE_DIR', '/wp-images/smilies/');

// These are used for writing various debug information to a file.  They are
// currently unused.
//define('CM_DEBUG_FILE', $_SERVER['DOCUMENT_ROOT'].'/wp-content/plugins/cricket-moods/debug.txt');
//$cm_debug = fopen(CM_DEBUG_FILE, 'a');


/**
cm_the_moods

	separator = string (optional)
		Placed between moods.
	before = string (optional)
		Placed before the first mood.
	after = string (optional)
		Placed after the last mood.

Prints the moods from the current post.  Must be
called from within The Loop.
*/
function cm_the_moods($separator=' &amp; ', $before = null, $after = null) {
	global $wpdb;

	// Get the moods for the current post.
	$post_moods = get_post_custom_values('mood');

	if( !empty($post_moods) ) {
		$count = count($post_moods) - 1;

		// Get a list of the available moods.
		$mood_list = cm_process_moods();

		foreach( $post_moods as $i => $mood_id ) {
			$mood_name = wptexturize($mood_list[$mood_id]['mood_name']);
			echo $before;

			// Only print the img tag if the mood has an associated image.
			if( !empty( $mood_list[$mood_id]['mood_image'] ) ) {
				echo '<img src="'. CM_IMAGE_DIR . wptexturize($mood_list[$mood_id]['mood_image']) .'" alt="'. $mood_name .' emoticon" /> ';
			}

			echo $mood_name;

			// Determine if this is the last mood.
			if( $i != $count ) {
				echo $separator;
			} else {
				echo $after;
			}
		}
	}
} // cm_the_moods


/**
cm_has_moods

	$post_ID = integer (optional)
		The ID of the post you are inquiring about.

Checks to see if the current post has mood
information.  Returns TRUE or FALSE accordingly.
Could be useful if not all your posts have moods.
Must be called from within The Loop if $post_ID
is NULL.
*/
function cm_has_moods($post_ID = null) {
	if($post_ID === null) {
		$post_moods = get_post_custom_values('mood');
		$post_moods = $post_moods[0];
	}
	else
		$post_moods = cm_get_post_moods($post_ID);

	if( empty($post_moods) )
		return false;
	else
		return true;
}


/**
The functions after this line are not meant to be used outside of this plugin
file.  But, if you really want to, you can.
*/


/**
cm_process_moods

Retrieves a list of available moods form the
database.  Returns them as an array in the form:
	'mood_id' => ('mood_name' => 'The Mood Name', 'mood_image' => 'themoodimage.gif')
*/
function cm_process_moods() {
	global $wpdb;

	$mood_list = array();

	foreach( $wpdb->get_results("SELECT * FROM cm_moods ORDER BY mood_name", ARRAY_A) as $line ) {
		$mood_list[ $line['mood_id'] ] = array('mood_name' => $line['mood_name'], 'mood_image' => $line['mood_image']);
	}

	return $mood_list;
}


/**
cm_get_posted_moods

Parses $_POST elements and returns an array of
the values (mood ids) used by CM.  Returns FALSE
if no applicable values were submitted.
*/
function cm_get_posted_moods() {
	$moods = array();
	foreach($_POST as $key => $val) {
		// CM input element names are prefixed by 'cm_mood_'.
		if( substr($key, 0, 8) == 'cm_mood_' )
			$moods[] = stripslashes( trim($val) );
	}

	if( !empty($moods) )
		return $moods;
	else
		return false;
}


/**
cm_post_mood

	$post_id = integer
		ID number of the post to look up.

Returns an associative array containing a post's
mood IDs in the form of:
	'meta_id' => 'mood_id'

Modified version of WP's get_post_meta function.
*/
function cm_get_post_moods($post_id) {
	global $wpdb;

	$metalist = $wpdb->get_results("SELECT meta_id,meta_value FROM $wpdb->postmeta WHERE post_id = '$post_id' AND meta_key = 'mood'", ARRAY_A);
	$values = array();

	if ($metalist) {
		foreach ($metalist as $metarow) {
			$values[$metarow['meta_id']] = $metarow['meta_value'];
		}
	}

	return $values;
}


/**
cm_update_moods
	$post_ID = integer
		ID of the post to modify.
	$moods = array (optional)
		An array containing the list of moods to
		assign to the post.

Modifies the moods associated with a post.  If the
$moods parameter is NULL, try to pull the moods
from $_POST.
*/
function cm_update_moods($post_ID, $moods = null) {
	global $wpdb;

	// If no $mood, pull from $_POST.
	if(!$moods) {
		$moods = cm_get_posted_moods();
	}

	if( cm_has_moods($post_ID) ) {
		if($moods) {
			// Find out what moods the post currently has.
			$current_moods = cm_get_post_moods($post_ID);

			// Diff the arrays and add any moods that weren't there before.
//			$new_moods = array_diff($moods, $current_moods);
			foreach( array_diff($moods, $current_moods) as $mood_id ) {
				$wpdb->query("INSERT INTO $wpdb->postmeta (post_id,meta_key,meta_value) VALUES ('$post_ID','mood','". $wpdb->escape($mood_id) ."')");
			}

			// Diff the other way and remove any unchecked moods.
//			$old_moods = array_diff($current_moods, $moods);
			foreach( array_diff($current_moods, $moods) as $meta_id => $mood_id ) {
				$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_id = '$meta_id'");
			}
		}

		// If no moods were posted and no moods were passed, remove all moods
		// from the post.
		else {
			$wpdb->query("DELETE FROM $wpdb->postmeta WHERE post_id = '$post_ID' AND meta_key = 'mood'");
		}

	// If the post doesn't currently have any moods, don't bother diffing.
	} elseif($moods) {
		foreach($moods as $mood_id) {
			$wpdb->query("INSERT INTO $wpdb->postmeta (post_id,meta_key,meta_value) VALUES ('$post_ID','mood','". $wpdb->escape($mood_id) ."')");
		}
	}

	return $post_ID;
} // cm_update_moods


/**
cm_list_select_moods

Prints a fieldset full of checkboxes.  Each
checkbox corresponds with a mood.  You get the
idea.
*/
function cm_list_select_moods() {
	global $post_ID;

	// Get a list of the available moods.
	$moods = cm_process_moods();

	// If we are editing an existing post, get that post's moods.
	if( !empty($post_ID) ) {
		$post_moods = cm_get_post_moods($post_ID);
	}

	echo '<fieldset id="cm_moodlist"><legend>Moods</legend>';

	// Begin printing a checkbox for every mood.
	foreach($moods as $mood_id => $mood_info) {
		echo "<span class='mood_item'><input type='checkbox' id='cm_mood_$mood_id' name='cm_mood_$mood_id' value='$mood_id'";

		// If we are editing a post, and that post has moods, pre-check the
		// moods currently assigned to the post.
		if( !empty($post_ID) and !empty($post_moods) ) {
			foreach($post_moods as $post_mood_id) {
				if( $post_mood_id == $mood_id ) {
					echo ' checked="checked"';
				}
			}
		}

		echo " /><label for='cm_mood_$mood_id'>";

		// If the mood has an associated image, show that just before the label.
		if( !empty($mood_info['mood_image']) )
			echo "<img src='". CM_IMAGE_DIR . $mood_info['mood_image'] ."' />";

		echo str_replace( ' ', '&nbsp;', wptexturize($mood_info['mood_name']) ) ."</label></span>\n";
	}

	echo '</fieldset>';

} // cm_list_select_moods


/**
cm_admin_style

Prints the stylesheet that makes the checkboxes
look decent.
*/
function cm_admin_style() { ?>

<style type="text/css">
#cm_moodlist .mood_item {
	background-color: #f2f2f2;
	margin-right: 2px;
	padding: 1px 4px;
	line-height: 175%;
}

#cm_moodlist .mood_item:hover {
	background-color: #ddd;
}

#cm_moodlist input {

}

#cm_moodlist img {
	vertical-align: middle;
	padding-right: 2px;
}

#cm_moodlist {
	text-align: justify;
	padding-bottom: .5em;
}
</style>

<?php } // cm_admin_style


// Update the moods whenever a post is saved or edited.
add_action('save_post', 'cm_update_moods');
add_action('edit_post', 'cm_update_moods');

// Display the mood checkboxes in the edit forms.
add_action('simple_edit_form', 'cm_list_select_moods');
add_action('edit_form_advanced', 'cm_list_select_moods');

// Include the stylesheet for the checkboxes.
add_action('admin_head', 'cm_admin_style');

?>