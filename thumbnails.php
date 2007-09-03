<?php
/*
Plugin Name: Thumbnails Manager
Plugin URI: http://olive.juan.free.fr/blog/
Description: Thumbnails Manager allows a more fine management of thumbnails in wordpress. Feedback wellcomed.
Author: Olivier Juan
Version: 0.6
Author URI: http://olive.juan.free.fr/
*/

require_once('thumbnails.inc.php');

function thm_manage_page() {
	global $plugin_page;
	if ( function_exists('add_management_page') )
		add_management_page(__('Thumbnails'), __('Thumbnails'), 'moderate_comments', __FILE__, 'thm_manager');
	if ( basename($plugin_page) == basename(__FILE__) )
		add_action('admin_head', 'thm_header');
}

function thm_header() {
	echo '<script type="text/javascript" src="../wp-includes/js/tw-sack.js"></script>
<script type="text/javascript" src="list-manipulation.js"></script>
';
}
function thm_manager() {
	global $wpdb, $user_ID, $plugin_page, $parent_file;

$wpvarstoreset = array('action','message','ID');
for ($i=0; $i<count($wpvarstoreset); $i += 1) {
    $wpvar = $wpvarstoreset[$i];
    if (!isset($$wpvar)) {
        if (empty($_POST["$wpvar"])) {
            if (empty($_GET["$wpvar"])) {
                $$wpvar = '';
            } else {
                $$wpvar = $_GET["$wpvar"];
            }
        } else {
            $$wpvar = $_POST["$wpvar"];
        }
    }
}

	switch($action) {

	case 'settings':
	$ratio = get_option('thm_ratio');
	check_admin_referer('update-ratio_' . $ratio);

	update_option('thm_ratio', (int)$_POST['ratio']);

	wp_redirect($parent_file . '?page=' . $plugin_page . '&message=4');

	break;

	case 'pages':
	$numberperpage = get_option('thm_number_per_page');
	check_admin_referer('update-pages_' . $numberperpage);
	
	update_option('thm_number_per_page', (int)$_POST['numberperpage']);
	
	wp_redirect($parent_file . '?page=' . $plugin_page);

	break;

	case 'delete':
	$ID = (int) $ID;
	check_admin_referer('delete-image_' . $ID);
	
	if ( !current_user_can('edit_post', $ID) )
		die(__('You are not allowed to delete this attachment.').' <a href=$parent_file?page=$plugin_page">'.__('Go back').'</a>');
	wp_delete_attachment($ID);
	
	wp_redirect($parent_file . '?page=' . $plugin_page . '&message=2');

	break;

	case 'resizethb':
	$ID = (int) $ID;
	check_admin_referer('update-image-size_' . $ID);

	if ( !current_user_can('edit_post', $ID) )
		die(__('You are not allowed to modify this attachment.').' <a href=$parent_file?page=$plugin_page">'.__('Go back').'</a>');
	
	$attachment = get_image_to_edit($ID);
	
	$size_thumb_str = $attachment->hwstring_small;
	$size_thumb_str = str_replace(' ','&',$size_thumb_str);
	$size_thumb_str = str_replace('\'','',$size_thumb_str);
	parse_str($size_thumb_str, $size_thumb);
	$size_thumb['height'] = (int) $size_thumb['height'];
	$size_thumb['width'] = (int) $size_thumb['width'];
	
	$coef = ((double)$_POST['coef']) / 100.0;
	
	$size_thumb['height'] = (int)(((double)$attachment->height) * $coef);
	$size_thumb['width'] = (int)(((double)$attachment->width) * $coef);
	
	$max_side = $size_thumb['height'];
	if ($size_thumb['width'] > $size_thumb['height'])
		$max_side = $size_thumb['width'];
	
	$thumb = wp_create_thumbnail($attachment->file, $max_side);
	
	if ( @file_exists($thumb) ) {
		$meta = get_post_meta($ID, '_wp_attachment_metadata', true);
		$newmeta = $meta;
		$newmeta['thumb'] = basename($thumb);
		$newmeta['hwstring_small'] = "height='" . $size_thumb['height'] . "' width='" . $size_thumb['width'] . "'";
		update_post_meta($ID, '_wp_attachment_metadata', $newmeta, $meta);
	} else {
		$error = $thumb;
	}
	
	wp_redirect($parent_file . '?page=' . $plugin_page . '&message=1');
	
		break;

	case 'editedthm':
	$ID = (int) $ID;
	check_admin_referer('update-image_' . $ID);

	if ( !current_user_can('edit_post', $ID) )
		die(__('You are not allowed to modify this attachment.').' <a href=$parent_file?page=$plugin_page">'.__('Go back').'</a>');
	
	wp_update_image($_POST);
	
	wp_redirect($parent_file . '?page=' . $plugin_page . '&message=3');
	
		break;

	case 'edit':
//    require_once ('admin-header.php');
    $ID = (int) $ID;
	if ( !current_user_can('edit_post', $ID) )
		die(__('You are not allowed to modify this attachment.').' <a href=$parent_file?page=$plugin_page">'.__('Go back').'</a>');
   $attachment = get_image_to_edit($ID);
    ?>

<div class="wrap">
 <h2><?php _e('Edit Image Properties') ?></h2>
 <form name="editthm" action="<?php echo $parent_file . '?page=' . $plugin_page . '&amp;noheader=1' ?>" method="post">
	  <?php wp_nonce_field('update-image_' .  $attachment->ID); ?>
	  <table class="editform" width="100%" cellspacing="2" cellpadding="5">
		<tr>
		  <th width="33%" scope="row"><?php _e('Image title:') ?></th>
		  <td width="67%"><input name="post_title" type="text" value="<?php echo wp_specialchars($attachment->post_title); ?>" size="40" /><input type="hidden" name="action" value="editedthm" />
		<input type="hidden" name="ID" value="<?php echo $attachment->ID ?>" /></td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Description:') ?></th>
			<td><textarea name="post_content" rows="5" cols="50" style="width: 97%;"><?php echo wp_specialchars($attachment->post_content, 1); ?></textarea></td>
		</tr>
		</table>
	  <p class="submit"><input type="submit" name="submit" value="<?php _e('Save Image properties') ?> &raquo;" /></p>
 </form>
 <p><a href="<?php echo $parent_file . '?page=' . $plugin_page ?>"><?php _e('&laquo; Return to Images list'); ?></a></p>
</div>
<?php
if (!isset($attachment->thumb)) {
?>
<div class="wrap">
 <h2><?php _e('Resize Thumbnail') ?></h2>
 <p>There is no thumbnail for this image. A reason might be that the image is already too small.</p>
</div>
<?php
} else {
$size_thumb_str = $attachment->hwstring_small;
$size_thumb_str = str_replace(' ','&',$size_thumb_str);
$size_thumb_str = str_replace('\'','',$size_thumb_str);
parse_str($size_thumb_str, $size_thumb);
$size_thumb['height'] = (int) $size_thumb['height'];
$size_thumb['width'] = (int) $size_thumb['width'];
$src = str_replace(basename($attachment->guid), $attachment->thumb, $attachment->guid);
if ( $attachment->height > $attachment->width)
	$coef = (int)($size_thumb['height'] *100 / $attachment->height);
else 
	$coef = (int)($size_thumb['width'] *100 / $attachment->width);
?>
<div class="wrap">
 <h2><?php _e('Resize Thumbnail') ?></h2>
 <p>If the thumbnail does not appear, you might need to recreate it by clicking the button "Resize Thumbnail". If it does not work, the file is probably missing on the server: consider deleting and uploading again.</p>
 <form name="resizethb" action="<?php echo $parent_file . '?page=' . $plugin_page . '&amp;noheader=1' ?>" method="post">
	  <?php wp_nonce_field('update-image-size_' .  $attachment->ID); ?>
	  <table class="the-list-x" width="100%" cellspacing="2" cellpadding="5">
		<tr>
		<th scope='col'>Thumbnail Image</th>
		<th scope='col'></th>
		</tr>
		<tr>
		  <td width="<?php echo $size_thumb['width']; ?>px" style="text-align:center" rowspan="5"><img src='<?php echo $src; ?>' alt='<?php echo $attachment->post_title; ?>' <?php echo $attachment->hwstring_small; ?> /></th>
		  <th width="50%" style="text-align:center">Image Size:
		  <input type="hidden" name="action" value="resizethb" />
		  <input type="hidden" name="ID" value="<?php echo $attachment->ID ?>" /></td>
		</tr>
		<tr>
		  <td width="50%" style="text-align:center">Height: <?php echo $attachment->height; ?>px, Width: <?php echo $attachment->width; ?>px</td>
		</tr>
		<tr>
		  <th width="50%" style="text-align:center">Thumbnail Size:</td>
		</tr>
		<tr>
		  <td width="50%" style="text-align:center">Height: <?php echo $size_thumb['height']; ?>px, Width: <?php echo $size_thumb['width']; ?>px</td>
		</tr>
		<tr>
		  <td width="50%" style="text-align:center">Ratio: <input name="coef" type="text" value="<?php echo $coef; ?>" size="3" />%</td>
		</tr>
		</table>
	  <p class="submit"><input type="submit" name="submit" value="<?php _e('Resize Thumbnail') ?> &raquo;" /></p>
 </form>
 <p><a href="<?php echo $parent_file . '?page=' . $plugin_page ?>"><?php _e('&laquo; Return to Thumbnails list'); ?></a></p>
</div>
    <?php
}
		break;

	default:

$messages[1] = __('Thumbnail resized.');
$messages[2] = __('Image deleted.');
$messages[3] = __('Image updated.');
$messages[4] = __('Settings updated.');
if (isset($message) && $message > 0) : ?>
<div id="message" class="updated fade"><p><?php echo $messages[$message]; ?></p></div>
<?php endif;
	$numberperpage = get_option('thm_number_per_page');
	if (!$numberperpage) {
		$numberperpage = 20;
		update_option('thm_number_per_page', $numberperpage);
	}
	$ratio = get_option('thm_ratio');
	if (!$ratio) {
		$ratio = 50;
		update_option('thm_ratio', $ratio);
	}
	if ( isset( $_GET['apage'] ) )
		$page = (int) $_GET['apage'];
	else
		$page = 1;
	$start = ( $page - 1 ) * $numberperpage;
	$end = $start + $numberperpage;

	if (! current_user_can('edit_others_posts') )
		$and_user = "AND post_author = " . $user_ID;
	$attachments = $wpdb->get_results("SELECT ID, post_date, post_title, post_content, post_mime_type, guid FROM $wpdb->posts WHERE post_type = 'attachment' $and_user ORDER BY post_date_gmt DESC LIMIT $start, $numberperpage", ARRAY_A);
	$total = $wpdb->get_var( "SELECT count(*) FROM $wpdb->posts WHERE post_type = 'attachment' $and_user" );
	
		echo "<div class='wrap'>
<h2>Thumbnails Manager Settings</h2>
<p>This plugin extends and expands the management functionality of Thumbnail files in WordPress. Feedback is extremely wellcomed: <a href='http://olive.juan.free.fr/blog/index.php/thumbnails-manager-plugin-for-wordpress/'>Leave a comment on this entry on my blog!</a>.</p>
<p>First you can set the default resize ratio of wordpress to a desired value:</p>";
?>
<form name="ratiothm" action="<?php echo $parent_file . '?page=' . $plugin_page . '&amp;noheader=1' ?>" method="post">
<?php wp_nonce_field('update-settings_' .  $ratio); ?>
<table class="editform" width="100%" cellspacing="2" cellpadding="5">
<tr>
<th width="33%" scope="row"><?php _e('Thumbnail default resize ratio:') ?></th>
<td><input name="ratio" type="text" value="<?php echo $ratio; ?>" size="3" />%<input type="hidden" name="action" value="settings" /></td>
</tr>
</table>
<p class="submit"><input type="submit" name="submit" value="<?php _e('Save Settings') ?> &raquo;" /></p>
</form>
</div>
<div class='wrap'>
<h2>Thumbnails Management</h2>
<p>Want to change the number of thumbnails per page?</p>
<form name="pagesthm" action="<?php echo $parent_file . '?page=' . $plugin_page . '&amp;noheader=1' ?>" method="post">
<?php wp_nonce_field('update-pages_' .  $numberperpage); ?>
<table class="editform" width="100%" cellspacing="2" cellpadding="5">
<tr>
<th width="33%" scope="row"><?php _e('Number of thumnails per pages:') ?></th>
<td><input name="numberperpage" type="text" value="<?php echo $numberperpage; ?>" size="3" /><input type="hidden" name="action" value="pages" /></td>
</tr>
</table>
<p class="submit"><input type="submit" name="submit" value="<?php _e('Refresh') ?> &raquo;" /></p>
</form>
<?php

if ( $total > 0 ){
	if ( $total > $numberperpage ) {
		$total_pages = ceil( $total / $numberperpage );
		$r = '';
		if ( 1 < $page ) {
			$args['apage'] = $page - 1;
			$r .=  '<a class="prev" href="' . add_query_arg( $args ) . '">&laquo; '. __('Previous Page') .'</a>' . "\n";
		}
		if ( ( $total_pages = ceil( $total / $numberperpage ) ) > 1 ) {
			for ( $page_num = 1; $page_num <= $total_pages; $page_num++ ) :
				if ( $page == $page_num ) :
					$r .=  "<strong>$page_num</strong>\n";
				else :
					$p = false;
					if ( $page_num < 3 || ( $page_num >= $page - 3 && $page_num <= $page + 3 ) || $page_num > $total_pages - 3 ) :
						$args['apage'] = $page_num;
						$r .= '<a class="page-numbers" href="' . add_query_arg($args) . '">' . ( $page_num ) . "</a>\n";
					$in = true;
					elseif ( $in == true ) :
						$r .= "...\n";
						$in = false;
					endif;
				endif;
			endfor;
		}
		if ( ( $page ) * $numberperpage < $total || -1 == $total ) {
			$args['apage'] = $page + 1;
			$r .=  '<a class="next" href="' . add_query_arg($args) . '">'. __('Next Page') .' &raquo;</a>' . "\n";
		}
		echo "<p>$r</p>";
	}
echo "<table id='the-list-x' width='100%' cellpadding='3' cellspacing='3'>
	<tr>
	<th scope='col'>ID</th>
	<th scope='col' width='128px'>Thumbnail</th>
        <th scope='col' width='25%'>Title</th>
        <th scope='col' width='45%'>Description</th>
        <th colspan='2'>Action</th>
	</tr>
";
		thm_images_rows($attachments, $parent_file . '?page=' . $plugin_page, $start, $end);
		echo "</table>";
	if ( $total > $numberperpage ) {
		$total_pages = ceil( $total / $numberperpage );
		$r = '';
		if ( 1 < $page ) {
			$args['apage'] = $page - 1;
			$r .=  '<a class="prev" href="' . add_query_arg( $args ) . '">&laquo; '. __('Previous Page') .'</a>' . "\n";
		}
		if ( ( $total_pages = ceil( $total / $numberperpage ) ) > 1 ) {
			for ( $page_num = 1; $page_num <= $total_pages; $page_num++ ) :
				if ( $page == $page_num ) :
					$r .=  "<strong>$page_num</strong>\n";
				else :
					$p = false;
					if ( $page_num < 3 || ( $page_num >= $page - 3 && $page_num <= $page + 3 ) || $page_num > $total_pages - 3 ) :
						$args['apage'] = $page_num;
						$r .= '<a class="page-numbers" href="' . add_query_arg($args) . '">' . ( $page_num ) . "</a>\n";
					$in = true;
					elseif ( $in == true ) :
						$r .= "...\n";
						$in = false;
					endif;
				endif;
			endfor;
		}
		if ( ( $page ) * $numberperpage < $total || -1 == $total ) {
			$args['apage'] = $page + 1;
			$r .=  '<a class="next" href="' . add_query_arg($args) . '">'. __('Next Page') .' &raquo;</a>' . "\n";
		}
		echo "<p>$r</p>";
	}
}		
echo "</div>";
			break;

}
}

add_action('admin_menu', 'thm_manage_page');


function thm_set_default_thumbnails_size ($max_size, $attachment_id, $file) {
	$attachment = get_post( $attachment_id );
	$ratio = get_option('thm_ratio');
	if (!$ratio) {
		$ratio = 50;
		update_option('thm_ratio', $ratio);
	}
	$ratio /= 100.0;

	if ( preg_match('!^image/!', get_post_mime_type( $attachment )) ) {
		$imagesize = getimagesize($file);
		$width = $imagesize['0']*$ratio;
		$height = $imagesize['1']*$ratio;
		$max_size = $width > $height ? $width : $height;
	}
	return $max_size;
}

function thm_correct_metadata($metadata) {
	
	if ($metadata['thumb']) {
		$thumb = dirname($metadata['file']).'/'.$metadata['thumb'];
		$imagesizet = getimagesize($thumb);
		list($uwidth, $uheight) = array($imagesizet['0'], $imagesizet['1']);
		$metadata['hwstring_small'] = "height='$uheight' width='$uwidth'";
	}
	return $metadata;
}

add_filter('wp_thumbnail_max_side_length', 'thm_set_default_thumbnails_size' ,10 ,3);
add_filter('wp_generate_attachment_metadata', 'thm_correct_metadata');

?>