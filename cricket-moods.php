<?php
/**
Plugin Name: Cricket Moods
Plugin URI: http://dev.wp-plugins.org/wiki/CricketMoods
Description: Allows an author to add multiple mood tags and mood smilies to every post.
Version: 2.1
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

/** !! **************************************************
 * It is not necessary to modify anything in this file. *
 ************************************************** !! **/

define('CM_VERSION', '2.1');
// The name of the option key that contains the available moods.
define('CM_OPTION_MOODS', 'cricketmoods_moods');
// The name of the option key that contains the next mood id.
define('CM_OPTION_INDEX', 'cricketmoods_index');
// The name of the option key that contains the image dir.
define('CM_OPTION_DIR', 'cricketmoods_dir');
// The name of the option key that contains the autoprint setting.
define('CM_OPTION_AUTOPRINT', 'cricketmoods_autoprint');

define('CM_OPTION_USERLEVEL', 'cricketmoods_userlevel');

define('CM_OPTION_VERSION', 'cricketmoods_version');

/* Removing presentation options.  I need to think this through.
define('CM_OPTION_BEFORE', 'cricketmoods_before');
define('CM_OPTION_SEPARATOR', 'cricketmoods_separator');
define('CM_OPTION_AFTER', 'cricketmoods_after');
*/

define('CM_IMAGE_DIR', get_option(CM_OPTION_DIR) );
define('CM_META_KEY', 'mood');



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
function cm_the_moods($separator=' &amp; ', $before = null, $after = null, $return = false) {
	global $wpdb;

	// Get the moods for the current post.
	$post_moods = get_post_custom_values(CM_META_KEY);

	if( !empty($post_moods) ) {
		$count = count($post_moods) - 1;

		// Get a list of the available moods.
		$mood_list = cm_process_moods();

		$output = '';

		foreach( $post_moods as $i => $mood_id ) {
			$mood_name = wptexturize($mood_list[$mood_id]['mood_name']);
			if( $i == 0 ) {
				$output .= $before;
			}

			// Only print the img tag if the mood has an associated image.
			if( !empty( $mood_list[$mood_id]['mood_image'] ) ) {
				$output .= '<img src="'. CM_IMAGE_DIR . wptexturize($mood_list[$mood_id]['mood_image']) .'" alt="'. $mood_name .' emoticon" /> ';
			}

			$output .= $mood_name;

			// Determine if this is the last mood.
			if( $i != $count ) {
				$output .= $separator;
			} else {
				$output .= $after;
			}
		}

		if ($return) {
			return $output;
		} else {
			echo $output;
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
		$post_moods = get_post_custom_values(CM_META_KEY);
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
cm_process_moods

Retrieves a list of available moods from the
database.  Returns them as a multi-dimensional
array in the form:
	'mood_id' => ('mood_name' => 'The Mood Name', 'mood_image' => 'themoodimage.gif')
*/
function cm_process_moods() {
	return get_option(CM_OPTION_MOODS);
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
cm_get_post_mood

	$post_id = integer
		ID number of the post to look up.

Returns an array containing a post's mood IDs.
*/
function cm_get_post_moods($post_id) {
	return get_post_meta($post_id, CM_META_KEY);
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

	// If no $mood, pull from $_POST.
	if(!$moods) {
		$moods = cm_get_posted_moods();
	}

	// If the current post already has moods associated with it.
	if( cm_has_moods($post_ID) ) {
		if($moods) {
			// Find out what moods the post currently has.
			$current_moods = cm_get_post_moods($post_ID);

			// Diff the arrays and add any moods that weren't there before.
			foreach( array_diff($moods, $current_moods) as $mood_id ) {
				add_post_meta($post_ID, CM_META_KEY, $mood_id);
			}

			// Diff the other way and remove any deselected moods.
			foreach( array_diff($current_moods, $moods) as $mood_id ) {
				delete_post_meta($post_ID, CM_META_KEY, $mood_id);
			}
		}

		// If no moods were posted and no moods were passed, remove all moods
		// from the post.
		else {
			delete_post_meta($post_ID, CM_META_KEY);
		}

	// If the post doesn't currently have any moods, don't bother diffing.
	} elseif($moods) {
		foreach($moods as $mood_id) {
			add_post_meta($post_ID, CM_META_KEY, $mood_id);
		}
	}

	return $post_ID;
} // cm_update_moods


// Update the moods whenever a post is saved or edited.
add_action('save_post', 'cm_update_moods');
add_action('edit_post', 'cm_update_moods');



/**
cm_update_option

A slight modification of WP's update_option().
Sometimes, you *don't* want to trim.
*/
/* Removing presentation options.
function cm_update_option($option_name, $newvalue) {
	global $wpdb, $cache_settings;
	if ( is_array($newvalue) || is_object($newvalue) )
		$newvalue = serialize($newvalue);

//	$newvalue = trim($newvalue); // I can't think of any situation we wouldn't want to trim
	// kccricket says: Good for you, I can.

    // If the new and old values are the same, no need to update.
    if ($newvalue == get_option($option_name)) {
        return true;
    }

	// If it's not there add it
	if ( !$wpdb->get_var("SELECT option_name FROM $wpdb->options WHERE option_name = '$option_name'") )
		add_option($option_name);

	$newvalue = $wpdb->escape($newvalue);
	$wpdb->query("UPDATE $wpdb->options SET option_value = '$newvalue' WHERE option_name = '$option_name'");
	$cache_settings = get_alloptions(); // Re cache settings
	return true;
}
*/



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


// Display the mood checkboxes in the edit forms.
add_action('simple_edit_form', 'cm_list_select_moods');
add_action('edit_form_advanced', 'cm_list_select_moods');



function cm_mood_sort( $row1,$row2 ) {
	if ( $first = strnatcasecmp($row1['mood_name'], $row2['mood_name']) ) {
		return $first;
	} else {
		return strnatcasecmp($row1['mood_image'], $row2['mood_image']);
	}
}



/**
cm_auto_moods

Used if the AutoPrint option is enabled.
*/
function cm_auto_moods($time) {
// 	echo $time;
	return $time . cm_the_moods(' &amp; ', '<br/>Current Mood: ', '', true);
}


// AutoPrint after the_time if the option is enabled.
// is_admin() didn't work here...
if ( strpos($_SERVER['PHP_SELF'], 'wp-admin/') === false && get_option(CM_OPTION_AUTOPRINT) == "on" ) {
	add_filter('the_time', 'cm_auto_moods');
}



/**
cm_admin_style

Prints the stylesheet that makes the checkboxes
look decent.
*/
function cm_admin_style() { ?>

<!-- Cricket Moods styles -->
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

#cm_moodlist img {
	vertical-align: middle;
	padding-right: 2px;
}

#cm_moodlist {
	text-align: justify;
	padding-bottom: .5em;
}

#cm_options_panel label {
	font-weight: bold;
}

#cm_options_panel table {
	text-align: center;
	width: 100%;
}

#cm_options_panel .delete:hover {
	background-color: #c00;
}

.failed {
	background: #fff0f0;
	border: 1px solid #69c;
}

div.failed {
	margin: 1em 5% 10px;
	padding: 0 1em 0 1em;
}

#mood_image_list td {
		padding: 1em .25em;
		margin: 0;
		border: 1px solid silver;
}

table#mood_image_list {
		text-align: center;
		width: 100%;
}
</style>
<!-- end Cricket Moods -->

<?php } // cm_admin_style


// Include the stylesheet for the CM admin areas.
add_action('admin_head', 'cm_admin_style');



/**
cm_admin_add_panel

Adds the option page if it's supported.
*/
function cm_admin_add_panel() {
	if ( function_exists('add_options_page') ) {
		add_options_page('Cricket Moods', 'Cricket Moods', 8, 'cm-options', 'cm_admin_panel');
	}
}


// Add the panel to the admin menu.
add_action('admin_menu', 'cm_admin_add_panel');



function cm_err($item, &$err, $text = ' class="failed" ') {
	if ( !empty($err) && array_key_exists($item, $err) ) {
		echo $text;
	}
}



/**
cm_admin_panel

The option page.
*/
function cm_admin_panel() {
	// Proceed with the options panel.
	global $wpdb, $table_prefix;

	$mood_list = cm_process_moods();
	$index = get_option(CM_OPTION_INDEX);
?>
<div class="wrap" id="cm_options_panel">

<?php
	// If the user pushed the update button.
	if ( isset($_POST['cm_options_update']) ) {
		$err = array();
		$ok = array();
		$unknown = ' failed to update for an unknown reason.  Sorry...';
		$success = ' updated successfully!';

		// We don't like a blank image directory.
		if ( !empty($_POST['cm_image_dir'] ) ) {
			// Add a trailing slash if it doesn't have one.
			if ( substr( $_POST['cm_image_dir'], -1, 1 ) != '/' ) {
				$_POST['cm_image_dir'] .= '/';
			}
			if( update_option( CM_OPTION_DIR, stripslashes($_POST['cm_image_dir']) ) ) {
				$ok[] = 'Image directory'.$success;
			} else {
				$err['cm_image_dir'] = 'Image directory'.$unknown;
			}
		} else {
			$err['cm_image_dir'] = 'You <em>must</em> supply an image directory.';
		}

		// Pretty obvious.  Set or unset the autoprint option.
		if ( !empty($_POST['cm_auto_print'] ) ) {
			$ap = update_option(CM_OPTION_AUTOPRINT, "on");
		} else {
			$ap = update_option(CM_OPTION_AUTOPRINT, "off");
		}

		if ($ap) {
			$ok[] = 'Automatic printing option'.$success;
		} else {
			$err['cm_auto_print'] = 'Automatic printing option'.$unknown;
		}

/* Removing presentation options.
		cm_update_option(CM_OPTION_BEFORE, stripslashes($_POST['cm_before']) );
		cm_update_option(CM_OPTION_SEPARATOR, stripslashes($_POST['cm_separator']) );
		cm_update_option(CM_OPTION_AFTER, stripslashes($_POST['cm_after']) );
*/

		// Parse the $_POST for the CM options we want.
		foreach ($_POST as $name => $value) {

			// Existing moods start with 'cm_id_'.
			if ( substr($name, 0, 6) == 'cm_id_' ) {
				// If the user chose to delete this mood, delete the mood and any references to it.
				if ( !empty($_POST["cm_delete_$value"]) ) {

					if ( $wpdb->query("DELETE FROM `{$table_prefix}postmeta` WHERE `meta_key`='mood' AND `meta_value`='$value'") !== false ) {
						$ok[] = "Mood with ID #$value has been deleted successfully!";
						unset($mood_list[$value]);
					} else {
						$err['cm_id_'.$vaule] = "Mood with ID #$value $unknown";
					}

				// Otherwise, update the mood name and image if both the name and the image are not blank.
				} elseif ( !empty($_POST["cm_name_$value"]) || !empty($_POST["cm_image_$value"]) ) {
					$mood_list[$value]['mood_name'] = stripslashes($_POST["cm_name_$value"]);
					$mood_list[$value]['mood_image'] = stripslashes($_POST["cm_image_$value"]);
					$ok[] = "Mood with ID #$value $success";
				} else {
					$err['cm_id_'.$value] = 'You must supply <em>either</em> a mood name <em>or</em> an image name for mood ID #'.$value;
				}
			}

			// New moods start with 'cm_new_id_' and should have either a name or an image.
			elseif ( substr($name, 0, 10) == 'cm_new_id_' && ( !empty($_POST["cm_new_name_$value"]) || !empty($_POST["cm_new_image_$value"]) ) ) {
				// Add the new mood to the mood list.
				$mood_list[$index++] = array( 'mood_name' => stripslashes($_POST["cm_new_name_$value"]), 'mood_image' => stripslashes($_POST["cm_new_image_$value"]) );
				$ok[] = "New mood with ID #$value has been added successfully!";
			}
		}

		// Update the option containing the index.
		update_option(CM_OPTION_INDEX, $index);

		// Finally, update the mood list.
		uasort($mood_list, 'cm_mood_sort');
		update_option(CM_OPTION_MOODS, $mood_list);

		if ( empty($err) ) {
			echo "<p class='updated'>All options$success</p>";
		} else {
			if ( !empty($ok) ) {
				echo '<div class="updated"><ul>';
				foreach ($ok as $msg) {
					echo '<li>'.wptexturize($msg).'</li>';
				}
				echo '</ul></div>';
			}
			echo '<div class="failed"><ul>';
			foreach ( $err as $name => $msg ) {
				echo '<li>'.wptexturize($msg).'</li>';
			}
			echo '</ul></div>';
		}
?>

<?php
	} // End if update button pushed.
?>

<h2>Cricket Moods</h2>

<form method="post">
<fieldset class="options">
	<legend>General Options</legend>
	<ul>
	<li<?php cm_err('cm_image_dir', $err) ?>><label for="cm_image_dir">Mood image directory:</label><br/>
	<input type="text" id="cm_image_dir" name="cm_image_dir" value="<?php echo get_option(CM_OPTION_DIR) ?>"/><br/>
	Directory containing the images associated with the moods.</li>
	<li<?php cm_err('cm_auto_print', $err) ?>><input type="checkbox" id="cm_auto_print" name="cm_auto_print" <?php if ( get_option(CM_OPTION_AUTOPRINT) == "on" ) echo 'checked="true"' ?>/> <label for="cm_auto_print">Automatically print moods</label><br/>
	Causes Cricket Moods to automatically display moods directly after each post's time without the need to modify the active template.  Works best with the default WordPress theme.  Uncheck if you've manually added <code>cm_the_moods()</code> to your template(s).</li>
	<ul>
</fieldset>

<?php /* Removing presentation options.
<fieldset class="options">
	<legend>Presentation</legend>
	<strong>Leave these options blank to use the default values.</strong>
	<ul>
	<li><label for="cm_before">Text to place before the first mood:</label><br/>
	<input type="text" id="cm_before" name="cm_before" value="<?php echo htmlspecialchars(get_option(CM_OPTION_BEFORE) ) ?>"/><br/>
	Default: <code>'Current Mood: '</code></li>
	<li><label for="cm_separator">Text to place in between multiple moods:</label><br/>
	<input type="text" id="cm_separator" name="cm_separator" value="<?php echo htmlspecialchars(get_option(CM_OPTION_SEPARATOR) ) ?>"/><br/>
	Will only display if the current post has two or more moods.<br/>
	Default: <code>' &amp;amp; '</code></li>
	<li><label for="cm_after">Text to place after the last mood:</label><br/>
	<input type="text" id="cm_after" name="cm_after" value="<?php echo htmlspecialchars(get_option(CM_OPTION_AFTER) ) ?>"/><br/>
	Default: <em>(blank)</em></li>
	</ul>
</fieldset>
*/ ?>

<fieldset class="options">
	<legend>Moods</legend>

	<p>Use the table below to modify your list of moods.  You may leave <em>either</em> the name <em>or</em> the image blank, but not both.  Use the blank entries at the bottom to add new moods.  You can also view a table of <a href="<?php echo $_SERVER['REQUEST_URI']. '&showimages=true' ?>" target="_blank">available mood images</a> in the mood image directory.</p>
	<p><strong>Deleting a mood will also remove any references to that mood from your posts.</strong></p>

	<table>
		<thead><tr><th>ID</th><th>Mood Name</th><th>Image File</th><th>Delete</th></tr></thead>
		<tfoot><tr><th>ID</th><th>Mood Name</th><th>Image File</th><th>Delete</th></tr></tfoot>
<?php
	// List the existing moods.
	ksort($mood_list);
	foreach ( $mood_list as $id => $mood ) {
?>
		<tr<?php if ($alt == true) { echo ' class="alternate"'; $alt = false; } else { $alt = true; } ?> valign="middle">
			<td><?php echo $id ?><input type="hidden" name="cm_id_<?php echo $id ?>" value="<?php echo $id ?>"/></td>
			<td><input type="text" name="cm_name_<?php echo $id ?>" value="<?php echo $mood['mood_name'] ?>"/></td>
			<td><input type="text" name="cm_image_<?php echo $id ?>" value="<?php echo $mood['mood_image'] ?>"/></td>
			<td class="delete"><input type="checkbox" name="cm_delete_<?php echo $id ?>"/></td>
		</tr>
<?php
	}
?>
<?php
	// Add blank rows for new moods.
	for ($i = $index; $i <= $index+5; $i++) {
?>
		<tr<?php if ($alt == true) { echo ' class="alternate"'; $alt = false; } else { $alt = true; } ?> valign="middle">
			<td><?php echo $i ?><input type="hidden" name="cm_new_id_<?php echo $i ?>" value="<?php echo $i ?>"/></td>
			<td><input type="text" name="cm_new_name_<?php echo $i ?>"/></td>
			<td><input type="text" name="cm_new_image_<?php echo $i ?>"/></td>
			<td>-</td>
		</tr>
<?php
	}
?>
	</table>
	<p>If you need to add more than five new moods, just click "Update Options" and five more blank lines will be available.</p>
</fieldset>
<input type="submit" name="cm_options_update" value="Update Options"/>
</form>

</div>
<?php } // cm_admin_panel



/**
cm_install

Initialize the mood list for first time installs,
or upgrade an old database table.
*/
function cm_install() {
	global $wpdb, $user_level;

	// Make sure we're authorized to do this.
	get_currentuserinfo();
	if ($user_level < 8) {
		return;
	}

	// The old 1.0.x mood table was named:
	$table_name = 'cm_moods';

	// Upgrade the old table to the option system if it exists.
	if ( in_array( $table_name, $wpdb->get_col('SHOW TABLES') ) ) {
		$mood_list = array();

		foreach( $wpdb->get_results("SELECT * FROM $table_name ORDER BY mood_id", ARRAY_A) as $line ) {
			$mood_list[ $line['mood_id'] ] = array('mood_name' => $line['mood_name'], 'mood_image' => $line['mood_image']);
		}

		if( count($mood_list) ) {
			end($mood_list);
			update_option(CM_OPTION_INDEX, key($mood_list)+1 );
			reset($mood_list);

			uasort($mood_list, 'cm_mood_sort');
			update_option(CM_OPTION_MOODS, $mood_list);
		}

		$wpdb->query("DROP TABLE $table_name");
	}

	// Initialize the moods list if it doesn't already exist,
	if ( !get_option(CM_OPTION_MOODS) ) {
		$inital_moods = array(
			array('mood_name' => 'Esctatic', 'mood_image' => 'icon_biggrin.gif'),
			array('mood_name' => 'Confused', 'mood_image' => 'icon_confused.gif'),
			array('mood_name' => 'Cool', 'mood_image' => 'icon_cool.gif'),
			array('mood_name' => 'Sad', 'mood_image' => 'icon_cry.gif'),
			array('mood_name' => 'Alarmed', 'mood_image' => 'icon_eek.gif'),
			array('mood_name' => 'Angry', 'mood_image' => 'icon_evil.gif'),
			array('mood_name' => 'Bored', 'mood_image' => 'icon_neutral.gif'),
			array('mood_name' => 'Playful', 'mood_image' => 'icon_razz.gif'),
			array('mood_name' => 'Sickly', 'mood_image' => 'icon_sad.gif'),
			array('mood_name' => 'Happy', 'mood_image' => 'icon_smile.gif'),
			array('mood_name' => 'Surprised', 'mood_image' => 'icon_surprised.gif'),
			array('mood_name' => 'Mischievous', 'mood_image' => 'icon_twisted.gif'),
			array('mood_name' => 'Flirtatious', 'mood_image' => 'icon_wink.gif')
		);

		uasort($mood_list, 'cm_mood_sort');
		update_option(CM_OPTION_MOODS, $inital_moods);
		update_option(CM_OPTION_INDEX, count($inital_moods) );
	}

	if ( !get_option(CM_OPTION_DIR) ) {
		update_option(CM_OPTION_DIR, '/wp-images/smilies/');
	}
	if ( !get_option(CM_OPTION_AUTOPRINT) ) {
		update_option(CM_OPTION_AUTOPRINT, 'on');
	}
	if ( !get_option(CM_OPTION_USERLEVEL) ) {
		update_option (CM_OPTION_USERLEVEL, '6');
	}
	if ( get_option(CM_OPTION_VERSION) != CM_VERSION ) {
		update_option(CM_OPTION_VERSION, CM_VERSION);
	}

}


// If the plugin was just activated, perform the install.
if ( isset($_GET['activate']) && $_GET['activate'] == 'true' ) {
	add_action('init', 'cm_install');
}

include('cricket-moods-manage.inc');

?>