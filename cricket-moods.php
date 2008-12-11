<?php
/**
Plugin Name: Cricket Moods
Plugin URI: http://wordpress.org/extend/plugins/cricket-moods/
Description: Allows an author to add multiple mood tags and mood smilies to every post.
Version: 3.7.2
Author: Keith "kccricket" Constable
Author URI: http://kccricket.net/
*/

/**
Cricket Moods: A flexible mood tag plugin for the WordPress publishing platform.
Copyright (c) 2008 Keith Constable

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

/** !! **************************************************
 * It is not necessary to modify anything in this file. *
 ************************************************** !! **/

// Serve up the style sheet if we're called directly.
if ( !defined('ABSPATH') ) {
	if( $_GET['style'] == 'true') {
		cm_admin_style();
	}
	exit();
}

define('CM_VERSION', '3.7.2');
// The name of the option key that contains the available moods.
define('CM_OPTION_MOODS', 'cricketmoods_moods');
// The name of the option key that contains the next mood id.
define('CM_OPTION_INDEX', 'cricketmoods_index');
// The name of the option key that contains the image dir.
define('CM_OPTION_DIR', 'cricketmoods_dir');
// The name of the option key that contains the autoprint setting.
define('CM_OPTION_AUTOPRINT', 'cricketmoods_autoprint');

define('CM_OPTION_VERSION', 'cricketmoods_version');

define('CM_META_KEY', 'mood');

load_plugin_textdomain('cricket-moods','wp-content/plugins/');

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
					$output .= '<img src="'. _cm_get_option(CM_OPTION_DIR) . wptexturize($mood_list[$mood_id]['mood_image']) .'" alt="'. $mood_name .' '. __('emoticon', 'cricket-moods') .'" /> ';
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
		global $post_ID;
	}

	if( cm_get_post_moods($post_ID) ) {
		return true;
	} else {
		return false;
	}
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
	if($moods) {
		if( is_string($moods) ) { // Quick fix for serialization issues
			$moods = unserialize($moods);
		}
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
cm_get_submitted_moods

Parses $_POST elements and returns an array of
the values (mood ids) used by CM.  Returns FALSE
if no applicable values were submitted.
*/
function cm_get_submitted_moods() {
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
function cm_update_post_moods($post_ID, $post) {

	if($post->post_type == 'revision') {
		return;
	}

	// Pull moods from $_POST.
	if( !current_user_can('edit_post', $post_ID) || !wp_verify_nonce($_POST['cricket-moods_verify-key'], 'update-postmoods_cricket-moods') ) return $post_ID;
	$moods = cm_get_submitted_moods();

	// If the current post already has moods associated with it.
	if( cm_has_moods($post_ID) ) {
		if($moods) {
			// Remove any doubled moods.
			$moods = array_unique($moods);

			// Find out what moods the post currently has.
			$current_moods = cm_get_post_moods($post_ID);

			// Diff the arrays and add any moods that weren't there before.
			foreach( array_diff($moods, $current_moods) as $mood_id ) {
				add_post_meta($post_ID, CM_META_KEY, $mood_id);
			}

			// Diff the other way and remove any deselected moods.
			foreach( array_diff($current_moods, $moods) as $mood_id ) {
				// Use a while statement to delete possibly duplicated moods.
				while( delete_post_meta($post_ID, CM_META_KEY, $mood_id) ) {
					// delete_post_meta returns false if there is nothing to delete.
					continue;
				}
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

add_action('save_post', 'cm_update_post_moods', 10, 2);



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

	echo '<div id="cm_moodlist" class="side-info"><h5>'. __('Moods', 'cricket-moods') .'</h5><ul>';

	// Begin printing a checkbox for every mood.
	foreach($moods as $mood_id => $mood_info) {
		echo "<li><label for='cm_mood_$mood_id' class='selectit'><input type='checkbox' id='cm_mood_$mood_id' name='cm_mood_$mood_id' value='$mood_id'";

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
			echo "<img src='". _cm_get_option(CM_OPTION_DIR) . $mood_info['mood_image'] ."' />";

		echo str_replace( ' ', '&nbsp;', wptexturize($mood_info['mood_name']) ) ."</label></li>\n";
	}

	echo '<input type="hidden" name="cricket-moods_verify-key" id="cricket-moods_verify-key" value="' . wp_create_nonce('update-postmoods_cricket-moods') . '" />';
	echo '</ul></div>';

} // cm_list_select_moods

add_action('submitpost_box', 'cm_list_select_moods');



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
	$pos = get_option(CM_OPTION_AUTOPRINT);
	$output = cm_the_moods(' &amp; ', '<p class="moods">'. __('Current Mood:', 'cricket-moods'), '</p>', true);
	if ($pos == "above") {
		return $output . $content;
	} elseif ($pos == "below") {
		return $content . $output;
	}
}

if( !is_admin() && get_option(CM_OPTION_AUTOPRINT) != "off" ) {
	add_filter('the_content', 'cm_auto_moods');
}



/**
cm_admin_style

Prints the stylesheet that makes my crap
look decent.
*/
function cm_admin_style() {

header('Content-Type: text/css');
?>

#cm_moodlist img {
	vertical-align: middle;
	padding: 0 2px;
}

#cm_mood_table {
	text-align: center;
	width: 80%;
	margin: 0 auto;
}

#cm_mood_table input.cm_text {
	width: 95%;
}

#cm_mood_table .delete:hover {
	background-color: #c00;
}

#mood_image_box {
	float: left;
	width: 18%;
	max-height: 40em;
	overflow: scroll;
}

#mood_image_box h4 {
	margin: 0;
}

#mood_image_list {
	list-style: none;
	margin: 0;
	padding: 0;
}

#mood_image_list li {
	margin: 2px 0;
	padding: 2px;
	background-color: silver;
}

.cm_danger {
	border: 2px groove red;
	padding: 0 1em;
}

.cm_danger:hover {
	background-color: #FFDDDD;
	color: black;
}

.cm_danger legend {
	font-size: 1.2em;
	font-weight: bold;
}

#cm_options_panel hr {
	clear: both;
	border-color: transparent;
}

#cm_reset_moods {
	float: left;
	width: 45%;
}

#cm_strip_posts {
	float: right;
	width: 45%;
}

p#cm_chirp {
	clear: both;
	font-style: italic;
	font-size: .85em;
	text-align: center;
	padding-top: 1em;
}

<?php
} // cm_admin_style


function cm_add_jquery() {
	global $wp_scripts;
	$wp_scripts->enqueue('jquery');
}
if( strpos($_SERVER['PHP_SELF'], 'wp-admin/') !== false ) {
	add_action('wp_print_scripts', 'cm_add_jquery');
}


function cm_admin_head() {
	$u = parse_url(get_option('siteurl'));
	$p = str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__); ?>
<!-- Cricket Moods stuff -->
<link rel="stylesheet" href="<?php echo $u['scheme'] .'://'. $u['host'] .'/'. $p ?>?style=true" type="text/css" />
<script type="text/javascript" language="javascript">
	// <![CDATA[
	function cmUE(id) {
		jQuery("img#cm_image_preview_"+id).attr("src","<?php echo wp_specialchars(get_option('siteurl') . _cm_get_option(CM_OPTION_DIR)); ?>"+jQuery("input#cm_image_" + id).val());
	}
	// ]]>
</script>
<!-- End Cricket Moods -->
<?php
}

add_action('admin_head', 'cm_admin_head');



/**
cm_admin_add_panel

Adds the option page.
*/
function cm_admin_add_panel() {
	add_options_page( __('Cricket Moods', 'cricket-moods'), __('Cricket Moods', 'cricket-moods'), 'manage_options', 'cm-options', 'cm_admin_panel');
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
	global $wpdb;

	$mood_list = cm_process_moods(-1);
	$index = cm_get_index(-1);

	// Only check the $_POST for updated options if the current user is allowed
	// to manage options.
	if ( current_user_can('manage_options') ) {
		if ( isset($_POST['cm_reset_moods']) ) {
			check_admin_referer('stripreset-moods_cricket-moods');
			delete_option(CM_OPTION_MOODS);
			delete_option(CM_OPTION_INDEX);
			cm_install(true);
			echo '<div id="message" class="updated fade"><p>'. __('Moods reset!', 'cricket-moods') .'</p></div>';
		}

		elseif ( isset($_POST['cm_strip_moods']) ){
			check_admin_referer('stripreset-moods_cricket-moods');
			$results =  $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key='". CM_META_KEY ."'");
			if ( $results === false ) {
				echo '<div id="message" class="error fade"><p>'. __('Stripping failed.', 'cricket-moods') .'</p></div>';
			}
			else {
				echo '<div id="message" class="updated fade"><p>'. sprintf( __('Stripped %s moods from all posts.', 'cricket-moods'), $results) .'</p></div>';
			}
		}

		// If the user pushed the update button.
		elseif ( isset($_POST['cm_options_update']) ) {
			check_admin_referer('update-options_cricket-moods');
			$err = array();

			// Check if we're running in WPMU.
			if ( !_cm_is_mu() || _cm_is_mu(TRUE) ) {
				// We don't like a blank image directory.
				if ( !empty($_POST['cm_image_dir']) ) {
					// Add a trailing slash if it doesn't have one.
					if ( substr( $_POST['cm_image_dir'], -1, 1 ) != '/' ) {
						$_POST['cm_image_dir'] .= '/';
					}
					if ( _cm_is_mu() ) {
						update_site_option( CM_OPTION_DIR, $_POST['cm_image_dir'] );
					} else {
						update_option( CM_OPTION_DIR, $_POST['cm_image_dir'] );
					}
					if( !is_readable($_SERVER['DOCUMENT_ROOT'].$_POST['cm_image_dir']) ) {
						$err['cm_image_dir'] = __('The image directory you supplied either does not exist or is not accessible.', 'cricket-moods');
					}
				} else {
					$err['cm_image_dir'] = __('You <em>must</em> supply an image directory!', 'cricket-moods');
				}
			}

			// Pretty obvious.  Set or unset the autoprint option.
			if ( !empty($_POST['cm_auto_print'] ) ) {
				update_option(CM_OPTION_AUTOPRINT, $_POST['cm_auto_print']);
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
						$err['cm_id_'.$value] = sprintf( __('You must supply <em>either</em> a mood name <em>or</em> an image name for the mood with ID # %s!', 'cricket-moods'), $value );
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
				echo '<div id="message" class="updated fade"><p>'. __('Options updated!', 'cricket-moods') .'</p></div>';
			} else {
				echo '<div id="message" class="error fade"><ul>';
				foreach ( $err as $name => $msg ) {
					echo '<li>'.wptexturize($msg).'</li>';
				}
				echo '</ul></div>';
			}
		} // End if update button pushed.
	} // End if user can manage options.
?>
<div class="wrap" id="cm_options_panel">
<h2><?php _e('Cricket Moods Options', 'cricket-moods') ?></h2>

<p><?php _e('To modify your personal list of moods, visit the <a href="edit.php?page=cm-manage-moods">Manage &raquo; Moods panel</a>', 'cricket-moods') ?>.</p>

<form method="post" action="">

<table width="100%" cellspacing="2" cellpadding="5" class="editform">
<?php if ( !_cm_is_mu() || _cm_is_mu(TRUE) ) { ?>
<tr valign="top"<?php cm_err('cm_image_dir', $err) ?>>
<th width="33%" scope="row"><?php _e('Mood image directory:', 'cricket-moods') ?></th>
	<td><input type="text" id="cm_image_dir" name="cm_image_dir" value="<?php echo attribute_escape( _cm_is_mu() ? get_site_option(CM_OPTION_DIR) : get_option(CM_OPTION_DIR) ); ?>" /><br/>
	<?php _e('Directory containing the images associated with the moods.  Should be relative to the root of your domain.', 'cricket-moods') ?></td>
</tr>
<?php } // is_mu ?>
<tr valign="top"<?php cm_err('cm_auto_print', $err) ?>>
<th width="33%" scope="row"><?php _e('Automatically print moods:', 'cricket-moods') ?></th>
	<td>
		<input type="radio" id="cm_auto_print_above" name="cm_auto_print" value="above" <?php if ( get_option(CM_OPTION_AUTOPRINT) == "above" ) echo 'checked="true"' ?>/> <label for="cm_auto_print_above"><?php _e('Above', 'cricket-moods') ?></label><br/>
		<input type="radio" id="cm_auto_print_below" name="cm_auto_print" value="below" <?php if ( get_option(CM_OPTION_AUTOPRINT) == "below" ) echo 'checked="true"' ?>/> <label for="cm_auto_print_below"><?php _e('Below', 'cricket-moods') ?></label><br/>
		<input type="radio" id="cm_auto_print_off" name="cm_auto_print" value="off" <?php if ( get_option(CM_OPTION_AUTOPRINT) == "off" ) echo 'checked="true"' ?>/> <label for="cm_auto_print_off"><?php _e('Off', 'cricket-moods') ?></label><br/>
		<?php _e('Causes Cricket Moods to automatically display moods just before or after each post\'s content without the need to modify the active template.  Turn this off if you\'ve manually added <code>cm_the_moods()</code> to your template.', 'cricket-moods') ?></td>
</tr>
</table>

<h3><?php _e('Default Moods', 'cricket-moods') ?></h3>
	<p><?php _e('Use the table below to modify the <strong>default list of moods</strong> for new users.  You may leave <em>either</em> the name <em>or</em> the image blank, but not both.  Use the blank entries at the bottom to add new moods.', 'cricket-moods'); ?></p>

<?php
	cm_list_mood_images();
	cm_edit_moods_table( cm_process_moods(-1) , $index, $err);
?>

<p class="submit">
<input type="submit" name="cm_options_update" value="<?php _e('Update Options', 'cricket-moods') ?> &raquo;"/>
</p>
<?php wp_nonce_field('update-options_cricket-moods'); ?>
</form>
<hr/>
<form method="post" action="">
<fieldset class="cm_danger" id="cm_reset_moods"><legend><?php _e('Reset Moods', 'cricket-moods') ?></legend>
<p><?php _e('Clicking this button will reset the blog\'s default mood list to the built-in "factory default" mood list.  This will not affect any user\'s personal mood list.', 'cricket-moods') ?></p>
<p class="submit"><input type="submit" name="cm_reset_moods" value="<?php _e('Reset moods to factory defaults!', 'cricket-moods') ?>" onclick="return confirm('<?php _e('Are you sure that you want to reset your moods?', 'cricket-moods') ?>');"/></p>
</fieldset>

<fieldset class="cm_danger" id="cm_strip_moods"><legend><?php _e('Strip Posts', 'cricket-moods') ?></legend>
<p><?php _e('Clicking this button will strip <strong>all</strong> posts by <strong>all users</strong> of any moods associated with them.', 'cricket-moods') ?></p>
<p class="submit"><input type="submit" name="cm_strip_moods" value="<?php _e('Strip moods from all posts!', 'cricket-moods') ?>" onclick="return confirm('<?php _e('Are you sure that you want to strip every post ever posted on this blog of moods?', 'cricket-moods') ?>');"/></p>
</fieldset>
<?php wp_nonce_field('stripreset-moods_cricket-moods'); ?>
</form>

<p id="cm_chirp">* chirp * chirp *</p>

</div>
<?php } // cm_admin_panel



/**
cm_list_mood_images

**/
function cm_list_mood_images() {
	$d = @dir($_SERVER['DOCUMENT_ROOT']._cm_get_option(CM_OPTION_DIR));
	$files = array();
	if ( !empty($d) ) {
		while ( false !== ( $entry = $d->read() ) ) {
			if ( eregi('\.gif|\.png|\.jp(g|eg?)', $entry) ) {
				$files[$entry] = _cm_get_option(CM_OPTION_DIR) . $entry;
			}
		}
		$d->close();
	}
	natcasesort($files);
	reset($files);
?>

<div id="mood_image_box">
<h4>Images</h4>
<ul id="mood_image_list">
<?php
	foreach ($files as $n => $s) {
		echo "<li><img src='". htmlspecialchars($s, ENT_QUOTES) ."'/> ". htmlspecialchars($n, ENT_QUOTES) ."</li>";
	}
?>
</ul>
</div>
<?php
} // cm_list_mood_images



/**
cm_edit_moods_table

**/
function cm_edit_moods_table($mood_list, $index, $err = array() ) {

$dir = _cm_get_option(CM_OPTION_DIR);

?>
	<table id="cm_mood_table">
		<thead><tr>
			<th><?php _e('ID', 'cricket-moods') ?></th>
			<th><?php _e('Mood Name', 'cricket-moods') ?></th>
			<th><?php _e('Image', 'cricket-moods') ?></th>
			<th><?php _e('Image File', 'cricket-moods') ?></th>
			<th><?php _e('Delete', 'cricket-moods') ?></th>
		</tr></thead>
		<tfoot><tr>
			<th><?php _e('ID', 'cricket-moods') ?></th>
			<th><?php _e('Mood Name', 'cricket-moods') ?></th>
			<th><?php _e('Image', 'cricket-moods') ?></th>
			<th><?php _e('Image File', 'cricket-moods') ?></th>
			<th><?php _e('Delete', 'cricket-moods') ?></th>
		</tr></tfoot>
<?php
	// List the existing moods.
	ksort($mood_list);
	foreach ( $mood_list as $id => $mood ) {
?>
		<tr<?php if ($alt == true) { echo ' class="alternate'.cm_err("cm_id_$id", $err, ' error').'"'; $alt = false; } else { cm_err("cm_id_$id", $err); $alt = true; } ?> valign="middle">
			<td><?php echo $id ?><input type="hidden" name="cm_id_<?php echo $id ?>" value="<?php echo $id ?>"/></td>
			<td><input class="cm_text" type="text" name="cm_name_<?php echo $id ?>" value="<?php echo wp_specialchars($mood['mood_name'], true) ?>"/></td>
			<td><?php if(!empty($mood['mood_image'])) { echo '<img src="'. $dir.$mood['mood_image'] .'" id="cm_image_preview_'. $id .'"/>'; } ?></td>
			<td><input class="cm_text" type="text" name="cm_image_<?php echo $id ?>" id="cm_image_<?php echo $id ?>" onchange="cmUE(<?php echo $id ?>);" value="<?php echo wp_specialchars($mood['mood_image'], true) ?>"/></td>
			<td class="delete"><input type="checkbox" name="cm_delete_<?php echo $id ?>" onclick="return confirm('<?php _e('Are you sure you want to delete this mood?', 'cricket-moods') ?>');"/></td>
		</tr>
<?php
	}

	// Add blank rows for new moods.
	for ($i = $index; $i <= $index+5; $i++) {
?>
		<tr<?php if ($alt == true) { echo ' class="alternate"'; $alt = false; } else { $alt = true; } ?> valign="middle">
			<td><?php echo $i ?><input type="hidden" name="cm_new_id_<?php echo $i ?>" value="<?php echo $i ?>"/></td>
			<td><input class="cm_text" type="text" name="cm_new_name_<?php echo $i ?>"/></td>
			<td><img src="images/notice.gif" id="cm_image_preview_<?php echo $i ?>"/></td>
			<td><input class="cm_text" type="text" name="cm_new_image_<?php echo $i ?>" id="cm_image_<?php echo $i ?>" onchange="cmUE(<?php echo $i ?>);"/></td>
			<td></td>
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
	global $wpdb, $user_ID;

	$mood_list = cm_process_moods();
	$index = cm_get_index();

	if ( current_user_can('edit_posts') ) {
		if ( isset($_POST['cm_reset_moods']) ) {
			check_admin_referer('stripreset-moods_cricket-moods');
			delete_usermeta($user_ID, CM_OPTION_MOODS);
			delete_usermeta($user_ID, CM_OPTION_INDEX);
			echo '<div id="message" class="updated fade"><p>'. __('Moods reset!', 'cricket-moods') .'</p></div>';
		}
		elseif ( isset($_POST['cm_strip_moods']) ){
			check_admin_referer('stripreset-moods_cricket-moods');
			$results =  $wpdb->query("DELETE $wpdb->postmeta FROM $wpdb->postmeta JOIN $wpdb->posts ON (". $wpdb->postmeta. ".post_id=". $wpdb->posts .".ID) WHERE meta_key='". CM_META_KEY ."' AND post_author=$user_ID");
			if ( $results === false ) {
				echo '<div id="message" class="error fade"><p>'. __('Stripping failed.', 'cricket-moods') .'</p></div>';
			}
			else {
				echo '<div id="message" class="updated fade"><p>'. sprintf( __('Stripped %s moods from your posts.', 'cricket-moods'), $results ) .'</p></div>';
			}
		}

		// Begin updating moods from manage panel.
		elseif ( isset($_POST['cm_mood_update']) ) {
			check_admin_referer('update-options_cricket-moods');
			$err = array();

			// Parse the $_POST for the CM options we want.
			foreach ($_POST as $name => $value) {

				// Existing moods start with 'cm_id_'.
				if ( substr($name, 0, 6) == 'cm_id_' ) {
					// If the user chose to delete this mood, delete the mood and any references to it.
					if ( !empty($_POST["cm_delete_$value"]) ) {

						if ( $wpdb->query("DELETE $wpdb->postmeta FROM $wpdb->postmeta JOIN $wpdb->posts ON (". $wpdb->postmeta.".post_id=". $wpdb->posts.".ID) WHERE meta_key='". CM_META_KEY ."' AND meta_value='$value' AND post_author=$user_ID") !== false ) {
							unset($mood_list[$value]);
						}

					// Otherwise, update the mood name and image if both the name and the image are not blank.
					} elseif ( !empty($_POST["cm_name_$value"]) || !empty($_POST["cm_image_$value"]) ) {
						$mood_list[$value]['mood_name'] = $_POST["cm_name_$value"];
						$mood_list[$value]['mood_image'] = $_POST["cm_image_$value"];
					} else {
						$err['cm_id_'.$value] = sprintf( __('You must supply <em>either</em> a mood name <em>or</em> an image name for the mood with ID # %s!', 'cricket-moods'), $value );
					}
				}

				// New moods start with 'cm_new_id_' and should have either a name or an image.
				elseif ( substr($name, 0, 10) == 'cm_new_id_' && ( !empty($_POST["cm_new_name_$value"]) || !empty($_POST["cm_new_image_$value"]) ) ) {
					// Add the new mood to the mood list.
					$mood_list[$index++] = array( 'mood_name' => $_POST["cm_new_name_$value"], 'mood_image' => $_POST["cm_new_image_$value"] );
				}
			}

			// Update the option containing the index.
			update_usermeta($user_ID, CM_OPTION_INDEX, $index);

			// Finally, update the mood list.
			uasort($mood_list, 'cm_mood_sort');
			update_usermeta($user_ID, CM_OPTION_MOODS, stripslashes_deep($mood_list) );

			if ( empty($err) ) {
				echo '<div id="message" class="updated fade"><p>'. __('Moods updated!', 'cricket-moods') .'</p></div>';
			} else {
				echo '<div id="message" class="error fade"><ul>';
				foreach ( $err as $msg ) {
					echo '<li>'.wptexturize($msg).'</li>';
				}
				echo '</ul></div>';
			}
		}
	}
?>

<div class="wrap">
<h2><?php _e('Cricket Moods', 'cricket-moods') ?></h2>

<?php if ( isset($_GET['debug']) ) { ?>
<div style="font-face: monospace; padding: 1em; border: 2px solid black; background-color: #FFDDDD;"><pre>
<?php print_r(get_usermeta($GLOBALS['user_ID'], CM_OPTION_MOODS)); ?>
</pre></div><?php } ?>

<form method="post" action="">
	<p><?php _e('Use the table below to modify your list of moods.  You may leave <em>either</em> the name <em>or</em> the image blank, but not both.  Use the blank entries at the bottom to add new moods.', 'cricket-moods'); ?></p>
	<p><strong><?php _e('Deleting a mood will also remove any references to that mood from your posts.', 'cricket-moods') ?></strong></p>

<?php
	cm_list_mood_images();
	cm_edit_moods_table(cm_process_moods(), $index, $err);
?>

	<p><?php _e('If you need to add more than five new moods, just click the "Update Moods" button and five more blank lines will become available.', 'cricket-moods') ?></p>
<p class="submit">
<input type="submit" name="cm_mood_update" value="<?php _e('Update Moods', 'cricket-moods') ?> &raquo;"/>
</p>
<?php wp_nonce_field('update-options_cricket-moods'); ?>
</form>

<form method="post" action="">
<fieldset class="cm_danger" id="cm_reset_moods"><legend><?php _e('Reset Moods', 'cricket-moods') ?></legend>
<p><?php _e('Clicking this button will delete your personal list of moods, causing the plugin to reinitialize your list with the moods specified in the Cricket Moods option panel.  Use this as a last resort only, as it will likely cause custom moods used in past posts to not appear.', 'cricket-moods') ?></p>
<p class="submit"><input type="submit" name="cm_reset_moods" value="<?php _e('Reset moods to blog defaults!', 'cricket-moods') ?>" onclick="return confirm('<?php _e('Are you sure that you want to reset your moods?', 'cricket-moods') ?>');"/></p>
</fieldset>

<fieldset class="cm_danger" id="cm_strip_moods"><legend><?php _e('Strip Posts', 'cricket-moods') ?></legend>
<p><?php _e('Clicking this button will strip <strong>all</strong> of your posts of any moods associated with them.', 'cricket-moods') ?></p>
<p class="submit"><input type="submit" name="cm_strip_moods" value="<?php _e('Strip moods from all posts!', 'cricket-moods') ?>" onclick="return confirm('<?php _e('Are you sure that you want to strip your posts of moods?', 'cricket-moods') ?>');"/></p>
</fieldset>
<?php wp_nonce_field('stripreset-moods_cricket-moods'); ?>
</form>

<p id="cm_chirp">* chirp * chirp *</p>

</div>

<?
} // cm_manage_panel

function cm_add_manage_panel() {
	add_management_page( __('Manage Moods', 'cricket-moods'), __('Moods', 'cricket-moods'), 'edit_posts', 'cm-manage-moods', 'cm_manage_panel');
}
add_action('admin_menu', 'cm_add_manage_panel');



function _cm_is_mu($blogone = FALSE) {
	if( $blogone === FALSE ) {
		return function_exists('get_blog_option');
	} else {
		return function_exists('get_blog_option') && $GLOBALS['blog_id'] == 1;
	}
}

function _cm_get_option($key) {
	if( function_exists('get_site_option') ) {
		return get_site_option($key);
	} else {
		return get_option($key);
	}
}



/**
cm_install

Initialize the default mood list.
*/
function cm_install($force = false) {

	// This plugin will not work with old versions of WP.
	if( !function_exists('wp_save_post_revision') ) {
		header('Location: plugins.php?action=deactivate&plugin='. basename(__FILE__) );
	}

	if ( get_option(CM_OPTION_VERSION) != CM_VERSION || ( $_GET['cm_force_install'] == 'true' && current_user_can('manage_options') ) || $force == true ) {

		update_option(CM_OPTION_VERSION, CM_VERSION);

		if( !get_option(CM_OPTION_MOODS) ) {

			$inital_moods = array(
				array('mood_name' => __('Alarmed', 'cricket-moods'), 'mood_image' => 'icon_eek.gif'),
				array('mood_name' => __('Angry', 'cricket-moods'), 'mood_image' => 'icon_evil.gif'),
				array('mood_name' => __('Bored', 'cricket-moods'), 'mood_image' => 'icon_neutral.gif'),
				array('mood_name' => __('Confused', 'cricket-moods'), 'mood_image' => 'icon_confused.gif'),
				array('mood_name' => __('Cool', 'cricket-moods'), 'mood_image' => 'icon_cool.gif'),
				array('mood_name' => __('Esctatic', 'cricket-moods'), 'mood_image' => 'icon_biggrin.gif'),
				array('mood_name' => __('Flirtatious', 'cricket-moods'), 'mood_image' => 'icon_wink.gif'),
				array('mood_name' => __('Happy', 'cricket-moods'), 'mood_image' => 'icon_smile.gif'),
				array('mood_name' => __('Mischievous', 'cricket-moods'), 'mood_image' => 'icon_twisted.gif'),
				array('mood_name' => __('Playful', 'cricket-moods'), 'mood_image' => 'icon_razz.gif'),
				array('mood_name' => __('Sad', 'cricket-moods'), 'mood_image' => 'icon_cry.gif'),
				array('mood_name' => __('Sickly', 'cricket-moods'), 'mood_image' => 'icon_sad.gif'),
				array('mood_name' => __('Surprised', 'cricket-moods'), 'mood_image' => 'icon_surprised.gif')
			);

			update_option(CM_OPTION_MOODS, $inital_moods);
			update_option(CM_OPTION_INDEX, count($inital_moods) );
		}
		if ( !_cm_get_option(CM_OPTION_DIR) ) {
			if ( _cm_is_mu() ) {
				$basepath = parse_url(get_blog_option(1, 'siteurl'));
				update_site_option(CM_OPTION_DIR,  $basepath['path'] .'/wp-includes/images/smilies/');
			} else {
				$basepath = parse_url(get_option('siteurl'));
				update_option(CM_OPTION_DIR,  $basepath['path'] .'/wp-includes/images/smilies/');
			}
		}
		if ( !get_option(CM_OPTION_AUTOPRINT) || get_option(CM_OPTION_AUTOPRINT) == "on" ) {
			update_option(CM_OPTION_AUTOPRINT, 'above');
		}

	}

} // cm_install

add_action('admin_init', 'cm_install');
