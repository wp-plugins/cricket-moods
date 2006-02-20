<?php
/**
Plugin Name: Cricket Moods
Plugin URI: http://dev.wp-plugins.org/wiki/CricketMoods
Description: Allows an author to add multiple mood tags and mood smilies to every post.
Version: 3.0
Author: Keith "kccricket" Constable
Author URI: http://kccricket.net/
*/

/**
Copyright (c) 2006 Keith Constable

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

define('CM_VERSION', '3.0');
// The name of the option key that contains the available moods.
define('CM_OPTION_MOODS', 'cricketmoods_moods');
// The name of the option key that contains the next mood id.
define('CM_OPTION_INDEX', 'cricketmoods_index');
// The name of the option key that contains the image dir.
define('CM_OPTION_DIR', 'cricketmoods_dir');
// The name of the option key that contains the autoprint setting.
define('CM_OPTION_AUTOPRINT', 'cricketmoods_autoprint');

define('CM_OPTION_VERSION', 'cricketmoods_version');

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
	global $post, $wpdb;

	// Get the moods for the current post.
	$post_moods = get_post_meta($post->ID, CM_META_KEY);

	if( !empty($post_moods) ) {
		$count = count($post_moods) - 1;

		// Get a list of the available moods.
		$mood_list = cm_process_moods( get_the_author_ID() );

		$output = '';

		foreach( $post_moods as $i => $mood_id ) {
			if( $i == 0 ) {
				$output .= $before;
			}

			// Failsafe in case that mood ID doesn't actually exist.
			if( !empty($mood_list[$mood_id]) ) {
				$mood_name = wptexturize($mood_list[$mood_id]['mood_name']);

				// Only print the img tag if the mood has an associated image.
				if( !empty( $mood_list[$mood_id]['mood_image'] ) ) {
					$output .= '<img src="'. CM_IMAGE_DIR . wptexturize($mood_list[$mood_id]['mood_image']) .'" alt="'. $mood_name .' emoticon" /> ';
				}

				$output .= $mood_name;

				if( $i != $count ) {
					$output .= $separator;
				}
			}

			// Determine if this is the last mood.
 			if( $i == $count ) {
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
function cm_process_moods($user_ID = '') {
	if( $user_ID === '' ) {
		global $user_ID;
	}
	if( $user_ID != -1 ) {
		$moods = get_usermeta($user_ID, CM_OPTION_MOODS);
	}
	if( $moods ) {
		return $moods;
	} else {
		return get_option(CM_OPTION_MOODS);
	}
}




/**
cm_get_index

**/
function cm_get_index($user_ID = '') {
	if( $user_ID === '' ) {
		global $user_ID;
	}
	if( $user_ID != -1 && get_usermeta($user_ID, CM_OPTION_INDEX) ) {
		return get_usermeta($user_ID, CM_OPTION_INDEX);
	} else {
		return get_option(CM_OPTION_INDEX);
	}
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
function cm_update_post_moods($post_ID, $moods = null) {

	// If no $mood, pull from $_POST.
	if( !isset($moods) ) {
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

	// Add the current default moodlist to the usermeta to prevent issues.
	if( !empty($moods) && !get_usermeta($GLOBALS['user_ID'], CM_OPTION_MOODS) ) {
		update_usermeta( $GLOBALS['user_ID'], CM_OPTION_MOODS, get_option(CM_OPTION_MOODS) );
		update_usermeta( $GLOBALS['user_ID'], CM_OPTION_INDEX, get_option(CM_OPTION_INDEX) );
	}

	return $post_ID;
} // cm_update_moods

add_action('save_post', 'cm_update_post_moods');
add_action('edit_post', 'cm_update_post_moods');



/**
cm_list_select_moods

Prints a fieldset full of checkboxes.  Each
checkbox corresponds with a mood.  You get the
idea.
*/
function cm_list_select_moods() {
	global $post, $user_ID;

	// Get a list of the available moods.
	if( isset($post->post_author) && $post->post_author != $user_ID )
		$moods = cm_process_moods($post->post_author);
	else
		$moods = cm_process_moods($user_ID);

	// If we are editing an existing post, get that post's moods.
	if( !empty($post->ID) ) {
		$post_moods = cm_get_post_moods($post->ID);
	}

	echo '<fieldset id="cm_moodlist" class="dbx-box"><h3 class="dbx-handle">Moods</h3><div class="dbx-content">';

	// Begin printing a checkbox for every mood.
	foreach($moods as $mood_id => $mood_info) {
		echo "<label for='cm_mood_$mood_id' class='selectit'><input type='checkbox' id='cm_mood_$mood_id' name='cm_mood_$mood_id' value='$mood_id'";

		// If we are editing a post, and that post has moods, pre-check the
		// moods currently assigned to the post.
		if( !empty($post->ID) and !empty($post_moods) ) {
			foreach($post_moods as $post_mood_id) {
				if( $post_mood_id == $mood_id ) {
					echo ' checked="checked"';
				}
			}
		}

		echo " />";

		// If the mood has an associated image, show that just before the label.
		if( !empty($mood_info['mood_image']) )
			echo "<img src='". CM_IMAGE_DIR . $mood_info['mood_image'] ."' />";

		echo str_replace( ' ', '&nbsp;', wptexturize($mood_info['mood_name']) ) ."</label></span>\n";
	}

	echo '</div></fieldset>';

} // cm_list_select_moods

// add_action('simple_edit_form', 'cm_list_select_moods');  // Probably not necessary any more
add_action('dbx_post_sidebar', 'cm_list_select_moods');



/**
cm_mood_sort

**/
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
function cm_auto_moods($content) {
	return cm_the_moods(' &amp; ', '<p class="moods">Current Mood: ', '</p>', true) . $content;
}

if( !is_admin() && get_option(CM_OPTION_AUTOPRINT) == "on" ) {
	add_filter('the_content', 'cm_auto_moods');
}



/**
cm_admin_style

Prints the stylesheet that makes my crap
look decent.
*/
function cm_admin_style() { ?>

<!-- Cricket Moods styles -->
<style type="text/css">

#cm_moodlist img {
	vertical-align: middle;
	padding: 0 2px;
}

#cm_mood_table {
	text-align: center;
	width: 100%;
}

#cm_mood_table input.cm_text {
	width: 95%;
}

#cm_mood_table .delete:hover {
	background-color: #c00;
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

#cm_auto_print {
	width: 1.5em;
	height: 1.5em;
}
</style>
<!-- end Cricket Moods -->

<?php } // cm_admin_style

add_action('admin_head', 'cm_admin_style');



/**
cm_admin_add_panel

Adds the option page.
*/
function cm_admin_add_panel() {
	add_options_page('Cricket Moods', 'Cricket Moods', 8, 'cm-options', 'cm_admin_panel');
}

// Add the panel to the admin menu.
add_action('admin_menu', 'cm_admin_add_panel');



/**
cm_err

**/
function cm_err($item, &$err, $text = ' class="error" ') {
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

	$mood_list = cm_process_moods(-1);
	$index = cm_get_index(-1);

	// If the user pushed the update button.
	if ( isset($_POST['cm_options_update']) ) {
		$err = array();

		// We don't like a blank image directory.
		if ( !empty($_POST['cm_image_dir'] ) ) {
			// Add a trailing slash if it doesn't have one.
			if ( substr( $_POST['cm_image_dir'], -1, 1 ) != '/' ) {
				$_POST['cm_image_dir'] .= '/';
			}
			update_option( CM_OPTION_DIR, $_POST['cm_image_dir'] );
		} else {
			$err['cm_image_dir'] = 'You <em>must</em> supply an image directory!';
		}

		// Pretty obvious.  Set or unset the autoprint option.
		if ( !empty($_POST['cm_auto_print'] ) ) {
			update_option(CM_OPTION_AUTOPRINT, "on");
		} else {
			update_option(CM_OPTION_AUTOPRINT, "off");
		}

				foreach ($_POST as $name => $value) {

			// Existing moods start with 'cm_id_'.
			if ( substr($name, 0, 6) == 'cm_id_' ) {
				// If the user chose to delete this mood, delete the mood.
				if ( !empty($_POST["cm_delete_$value"]) ) {
					unset($mood_list[$value]);
				// Otherwise, update the mood name and image if both the name and the image are not blank.
				} elseif ( !empty($_POST["cm_name_$value"]) || !empty($_POST["cm_image_$value"]) ) {
					$mood_list[$value]['mood_name'] = $_POST["cm_name_$value"];
					$mood_list[$value]['mood_image'] = $_POST["cm_image_$value"];
				} else {
					$err['cm_id_'.$value] = 'You must supply <em>either</em> a mood name <em>or</em> an image name for the mood with ID #'.$value.'!';
				}
			}

			// New moods start with 'cm_new_id_' and should have either a name or an image.
			elseif ( substr($name, 0, 10) == 'cm_new_id_' && ( !empty($_POST["cm_new_name_$value"]) || !empty($_POST["cm_new_image_$value"]) ) ) {
				// Add the new mood to the mood list.
				$mood_list[$index++] = array( 'mood_name' => $_POST["cm_new_name_$value"], 'mood_image' => $_POST["cm_new_image_$value"] );
			}
		}

		// Update the option containing the index.
		update_option(CM_OPTION_INDEX, $index);

		// Finally, update the mood list.
		uasort($mood_list, 'cm_mood_sort');
		update_option(CM_OPTION_MOODS, stripslashes_deep($mood_list) );

		if ( empty($err) ) {
			echo '<div id="message" class="updated fade"><p>Options updated!</p></div>';
		} else {
			echo '<div id="message" class="error fade"><ul>';
			foreach ( $err as $name => $msg ) {
				echo '<li>'.wptexturize($msg).'</li>';
			}
			echo '</ul></div>';
		}
	} // End if update button pushed.
?>
<div class="wrap" id="cm_options_panel">
<h2>Cricket Moods Options</h2>

<p>To modify your personal list of moods, visit the <a href="edit.php?page=cm-manage-moods">Manage &raquo; Moods panel</a>.

<form method="post">

<table width="100%" cellspacing="2" cellpadding="5" class="editform">
<tr valign="top"<?php cm_err('cm_image_dir', $err) ?>>
<th width="33%" scope="row"><label for="cm_image_dir">Mood image directory:</label></th>
	<td><input type="text" id="cm_image_dir" name="cm_image_dir" value="<?php echo get_option(CM_OPTION_DIR) ?>" /><br/>
	Directory containing the images associated with the moods.  Should be relative to the root of your domain.</td>
</tr>
<tr valign="top"<?php cm_err('cm_auto_print', $err) ?>>
<th width="33%" scope="row"><label for="cm_auto_print">Automatically print moods:</label></th>
	<td><input type="checkbox" id="cm_auto_print" name="cm_auto_print" <?php if ( get_option(CM_OPTION_AUTOPRINT) == "on" ) echo 'checked="true"' ?>/><br/>
	Causes Cricket Moods to automatically display moods just before each post's content without the need to modify the active template.  Deselect if you've manually added <code>cm_the_moods()</code> to your template(s).</td>
</tr>
</table>

<h3>Default Moods</h3>
	<p>Use the table below to modify the <strong>default list of moods</strong> for new users.  You may leave <em>either</em> the name <em>or</em> the image blank, but not both.  Use the blank entries at the bottom to add new moods.<?php if($_GET['showimages'] != 'true') { ?>  You can also view a table of <a href="<?php echo $_SERVER['REQUEST_URI']. '&showimages=true' ?>">available mood images</a> in the mood image directory.<?php } ?></p>

<?php cm_edit_moods_table( cm_process_moods(-1) , $index, $err); ?>

<p class="submit">
<input type="submit" name="cm_options_update" value="Update Options &raquo;"/>
</p>
</form>

</div>
<?php } // cm_admin_panel



/**
cm_list_mood_images

**/
function cm_list_mood_images() {
	$d = dir($_SERVER['DOCUMENT_ROOT'].CM_IMAGE_DIR);
	while ( $entry = $d->read() ) {
		if ( eregi('\.gif|\.png|\.jp(g|eg?)', $entry) ) {
			$files[$entry] = CM_IMAGE_DIR . $entry;
		}
	}
	$d->close();
	natcasesort($files);
	reset($files);
?>

<table id="mood_image_list">
<?php
	$i = 0;
	foreach ($files as $n => $s) {
		if ($i == 0) {
			echo '<tr>';
		}
		echo "<td><img src='$s'><br>$n</td>";
		$i++;
		if ($i == 6) {
			echo '</tr>';
			$i = 0;
		}
	}
?>
</table>
<?php
} // cm_list_mood_images



/**
cm_edit_moods_table

**/
function cm_edit_moods_table($mood_list, $index, $err = array() ) {
?>
	<table id="cm_mood_table">
		<thead><tr><th>ID</th><th>Mood Name</th><th>Image File</th><th>Delete</th></tr></thead>
		<tfoot><tr><th>ID</th><th>Mood Name</th><th>Image File</th><th>Delete</th></tr></tfoot>
<?php
	// List the existing moods.
	ksort($mood_list);
	foreach ( $mood_list as $id => $mood ) {
?>
		<tr<?php if ($alt == true) { echo ' class="alternate'.cm_err("cm_id_$id", $err, ' error').'"'; $alt = false; } else { cm_err("cm_id_$id", $err); $alt = true; } ?> valign="middle">
			<td><?php echo $id ?><input type="hidden" name="cm_id_<?php echo $id ?>" value="<?php echo $id ?>"/></td>
			<td><input class="cm_text" type="text" name="cm_name_<?php echo $id ?>" value="<?php echo wp_specialchars($mood['mood_name'], true) ?>"/></td>
			<td><input class="cm_text" type="text" name="cm_image_<?php echo $id ?>" value="<?php echo wp_specialchars($mood['mood_image'], true) ?>"/></td>
			<td class="delete"><input type="checkbox" name="cm_delete_<?php echo $id ?>"/></td>
		</tr>
<?php
	}

	// Add blank rows for new moods.
	for ($i = $index; $i <= $index+5; $i++) {
?>
		<tr<?php if ($alt == true) { echo ' class="alternate"'; $alt = false; } else { $alt = true; } ?> valign="middle">
			<td><?php echo $i ?><input type="hidden" name="cm_new_id_<?php echo $i ?>" value="<?php echo $i ?>"/></td>
			<td><input class="cm_text" type="text" name="cm_new_name_<?php echo $i ?>"/></td>
			<td><input class="cm_text" type="text" name="cm_new_image_<?php echo $i ?>"/></td>
			<td>-</td>
		</tr>
<?php
	}
?>
	</table>
<?php
} // cm_edit_moods_table



/**
cm_manage_panel

**/
function cm_manage_panel() {
	global $wpdb, $user_ID, $table_prefix;

	$mood_list = cm_process_moods();
	$index = cm_get_index();

	// Begin updating moods from manage panel.
	if ( isset($_POST['cm_mood_update']) ) {
		$err = array();

		// Parse the $_POST for the CM options we want.
		foreach ($_POST as $name => $value) {

			// Existing moods start with 'cm_id_'.
			if ( substr($name, 0, 6) == 'cm_id_' ) {
				// If the user chose to delete this mood, delete the mood and any references to it.
				if ( !empty($_POST["cm_delete_$value"]) ) {

					if ( $wpdb->query("DELETE {$table_prefix}postmeta FROM {$table_prefix}postmeta JOIN {$table_prefix}posts ON ({$table_prefix}postmeta.post_id={$table_prefix}posts.ID) WHERE meta_key='mood' AND meta_value='$value' AND post_author=$user_ID") !== false ) {
						unset($mood_list[$value]);
					}

				// Otherwise, update the mood name and image if both the name and the image are not blank.
				} elseif ( !empty($_POST["cm_name_$value"]) || !empty($_POST["cm_image_$value"]) ) {
					$mood_list[$value]['mood_name'] = $_POST["cm_name_$value"];
					$mood_list[$value]['mood_image'] = $_POST["cm_image_$value"];
				} else {
					$err['cm_id_'.$value] = 'You must supply <em>either</em> a mood name <em>or</em> an image name for the mood with ID #'.$value.'!';
				}
			}

			// New moods start with 'cm_new_id_' and should have either a name or an image.
			elseif ( substr($name, 0, 10) == 'cm_new_id_' && ( !empty($_POST["cm_new_name_$value"]) || !empty($_POST["cm_new_image_$value"]) ) ) {
				// Add the new mood to the mood list.
				$mood_list[$index++] = array( 'mood_name' => $_POST["cm_new_name_$value"], 'mood_image' => $_POST["cm_new_image_$value"] );
			}
		}

		// Update the option containing the index.
		update_usermeta($user_ID, CM_OPTION_INDEX, $wpdb->escape($index) );

		// Finally, update the mood list.
		uasort($mood_list, 'cm_mood_sort');
		update_usermeta($user_ID, CM_OPTION_MOODS, stripslashes_deep($mood_list) );

		if ( empty($err) ) {
			echo '<div id="message" class="updated fade"><p>Moods updated!</p></div>';
		} else {
			echo '<div id="message" class="error fade"><ul>';
			foreach ( $err as $msg ) {
				echo '<li>'.wptexturize($msg).'</li>';
			}
			echo '</ul></div>';
		}
	}
?>

<div class="wrap">
<h2>Cricket Moods</h2>

<form method="post">
	<p>Use the table below to modify your list of moods.  You may leave <em>either</em> the name <em>or</em> the image blank, but not both.  Use the blank entries at the bottom to add new moods.<?php if($_GET['showimages'] != 'true') { ?>  You can also view a table of <a href="<?php echo $_SERVER['REQUEST_URI']. '&showimages=true' ?>">available mood images</a> in the mood image directory.<?php } ?></p>
	<p><strong>Deleting a mood will also remove any references to that mood from your posts.</strong></p>

<?php
	if( $_GET['showimages'] == 'true' ) {
		cm_list_mood_images();
	}

	cm_edit_moods_table(cm_process_moods(), $index, $err);
?>

	<p>If you need to add more than five new moods, just click the "Update Moods" button and five more blank lines will become available.</p>
<p class="submit">
<input type="submit" name="cm_mood_update" value="Update Moods &raquo;"/>
</p>
</form>

</div>

<?
} // cm_manage_panel

function cm_add_manage_panel() {
	add_management_page('Manage Moods', 'Moods', 1, 'cm-manage-moods', 'cm_manage_panel');
}
add_action('admin_menu', 'cm_add_manage_panel');



/**
cm_install

Initialize the default mood list.
*/
function cm_install() {
	if( substr($GLOBALS['wp_version'], 0, 1) < 2 ) {
		header('Location: plugins.php?action=deactivate&plugin='. basename(__FILE__) );
	}

	global $wpdb;

	if( !get_option(CM_OPTION_MOODS) ) {

		$inital_moods = array(
			array('mood_name' => 'Alarmed', 'mood_image' => 'icon_eek.gif'),
			array('mood_name' => 'Angry', 'mood_image' => 'icon_evil.gif'),
			array('mood_name' => 'Bored', 'mood_image' => 'icon_neutral.gif'),
			array('mood_name' => 'Confused', 'mood_image' => 'icon_confused.gif'),
			array('mood_name' => 'Cool', 'mood_image' => 'icon_cool.gif'),
			array('mood_name' => 'Esctatic', 'mood_image' => 'icon_biggrin.gif'),
			array('mood_name' => 'Flirtatious', 'mood_image' => 'icon_wink.gif'),
			array('mood_name' => 'Happy', 'mood_image' => 'icon_smile.gif'),
			array('mood_name' => 'Mischievous', 'mood_image' => 'icon_twisted.gif'),
			array('mood_name' => 'Playful', 'mood_image' => 'icon_razz.gif'),
			array('mood_name' => 'Sad', 'mood_image' => 'icon_cry.gif'),
			array('mood_name' => 'Sickly', 'mood_image' => 'icon_sad.gif'),
			array('mood_name' => 'Surprised', 'mood_image' => 'icon_surprised.gif')
		);

		update_option(CM_OPTION_MOODS, $inital_moods);
		update_option(CM_OPTION_INDEX, count($inital_moods) );
	}
	if ( !get_option(CM_OPTION_DIR) ) {
		update_option(CM_OPTION_DIR, '/wp-includes/images/smilies/');
	}
	if ( !get_option(CM_OPTION_AUTOPRINT) ) {
		update_option(CM_OPTION_AUTOPRINT, 'on');
	}

	delete_option(CM_OPTION_USERLEVEL);

	if ( get_option(CM_OPTION_VERSION) != CM_VERSION ) {
		update_option(CM_OPTION_VERSION, CM_VERSION);
	}

} // cm_install

register_activation_hook(__FILE__, 'cm_install');

?>