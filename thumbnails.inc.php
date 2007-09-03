<?php
/*
Plugin: Thumbnails Manager
URI: http://olive.juan.free.fr/blog/
Desc: Thumbnails Manager allows a more fine management of thumbnails in wordpress. Feedback wellcomed.
Author: Olivier Juan
Version: 0.6
Author URI: http://olive.juan.free.fr/
*/

function thm_images_rows($attachments, $file, $start , $end) {
	global $wpdb, $user_ID;//, $class;

	if (! current_user_can('edit_others_posts') )
		$and_user = "AND post_author = " . $user_ID;

	foreach ($attachments as $key => $attachment) {
		if (preg_match('!^image/!', $attachment['post_mime_type'] ) ) {
			$ID = (int) $attachment['ID'];
			$title = wp_specialchars($attachment['post_title']);
			$description = wp_specialchars($attachment['post_content']);

			$meta = get_post_meta($ID, '_wp_attachment_metadata', true);
			if (!is_array($meta)) {
				$meta = get_post_meta($ID, 'imagedata', true); // Try 1.6 Alpha meta key
				if (!is_array($meta)) {
					$meta = array();
				}
				add_post_meta($ID, '_wp_attachment_metadata', $meta);
			}
			$attachment = array_merge($attachment, $meta);

			if ( ($attachment['width'] > 128 || $attachment['height'] > 96) && !empty($attachment['thumb']) && file_exists(dirname($attachment['file']).'/'.$attachment['thumb']) ) {
				$src = str_replace(basename($attachment['guid']), $attachment['thumb'], $attachment['guid']);
			} else {
				$src = $attachment['guid'];
			}
			if ( current_user_can('manage_categories') ) {
				$edit = "<a href='$file&amp;action=edit&amp;ID=$ID' class='edit'>".__('Edit')."</a></td>";

				$edit .= "<td><a href='" . wp_nonce_url("$file&amp;action=delete&amp;ID=$ID&amp;noheader=1", 'delete-image_' . $ID ) . "' onclick=\"return deleteSomething( 'thb', $ID, '" . sprintf(__("You are about to delete the image &quot;%s&quot;.  All post containing the file will be defaced.\\n&quot;OK&quot; to delete, &quot;Cancel&quot; to stop."), js_escape($title))."' );\" class='delete'>".__('Delete')."</a>";
			}
			else
				$edit = '';

			$size_thumb_str = $attachment['hwstring_small'];
			$size_thumb_str = str_replace(' ','&',$size_thumb_str);
			$size_thumb_str = str_replace('\'','',$size_thumb_str);
			parse_str($size_thumb_str, $size_thumb);
			$size_thumb['height'] = (int) $size_thumb['height'];
			$size_thumb['width'] = (int) $size_thumb['width'];

			list($attachment['uwidth'], $attachment['uheight']) = get_udims($size_thumb['width'], $size_thumb['height']);
			$height_width = 'height="'.$attachment['uheight'].'" width="'.$attachment['uwidth'].'"';
			$xpadding = (128 - $attachment['uwidth']) / 2;
			$ypadding = (96 - $attachment['uheight']) / 2;

			$class = ('alternate' == $class) ? '' : 'alternate';
			echo "<tr id='thb-$ID' class='$class'>
	<th scope='row'>$ID</th>
	<td style='text-align:center'><img id='image{$ID}' src='$src' alt='$title' $height_width style='padding: {$ypadding}px {$xpadding}px;' /></td></td>
	<td>$title</td>
	<td>$description</td>
	<td>$edit</td>
	</tr>
";
		}
	}
}

function get_image_to_edit($id) {
	$attachment = get_image($id);

	return $attachment;
}
// Retrieves category data given a category ID or category object.
// Handles category caching.
function &get_image(&$attachment, $output = OBJECT) {
	global $wpdb;

	$_attachment = get_post($attachment);

	if ( !isset($_attachment->width) ) {
		$meta = get_post_meta($_attachment->ID, '_wp_attachment_metadata', true);
		if (!is_array($meta)) {
			$meta = get_post_meta($_attachment->ID, 'imagedata', true); // Try 1.6 Alpha meta key
			if (!is_array($meta)) {
				$meta = array();
			}
			add_post_meta($_attachment->ID, '_wp_attachment_metadata', $meta);
		}
		foreach ($meta as $key => $value) $_attachment->$key = $value;
	}

	if ( $output == OBJECT ) {
		return $_attachment;
	} elseif ( $output == ARRAY_A ) {
		return get_object_vars($_attachment);
	} elseif ( $output == ARRAY_N ) {
		return array_values(get_object_vars($_attachment));
	} else {
		return $_attachment;
	}
}

function wp_update_image($attachment) {
	global $wpdb;

	$ID = (int) $attachment['ID'];

	// First, get all of the original fields
	$_attachment = get_image($ID, ARRAY_A);

	// Escape data pulled from DB.
	$_attachment = add_magic_quotes($_attachment);

	// Merge old and new fields with new fields overwriting old ones.
	$attachment = array_merge($_attachment, $attachment);
	
	$oldfile = get_post_meta($ID, '_wp_attached_file');
	update_post_meta($ID, '_wp_attached_file', $newfile, $oldfile);
	
	$oldmeta = get_post_meta($ID, '_wp_attachment_metadata');
	foreach ($oldmeta as $key => $value) $newmeta['$key'] = $attachment['$key'] ? $attachment['$key'] : $oldmeta['$key'];
	update_post_meta($ID, '_wp_attachment_metadata', $newmeta, $oldmeta);

	$attachment['file'] = false;

	if (empty ($attachment['post_title']))
		$attachment['post_title'] = basename($attachment['file']);

	$attachment['post_name'] = $attachment['post_title'];
	
	if (empty ($attachment['description']))
		$attachment['description'] = '';

	wp_insert_attachment($attachment);
	
	do_action('edit_image', $ID);

	return $ID;
}


?>