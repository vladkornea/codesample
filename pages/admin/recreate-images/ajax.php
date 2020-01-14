<?php
require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';
$pageShell = new AdminAjaxPageShell;

define( 'CORRECT_SINCE', strtotime( '2020-01-09 08:15:00' ) );

ini_set( 'memory_limit', 1024 * 1024 * 1024 );

$request_action = $_GET[ 'action' ] ?? null;
if ( empty( $request_action ) ) {
	$pageShell->error( "Empty action." );
}
switch ( $request_action ) {
	case 'get_image_data':
		handle_get_image_data();
		break;
	case 'accept_changes':
		handle_accept_changes();
		break;
	default:
		$pageShell->error( "Unrecognized action: $request_action" );
}

return; // functions below

function handle_get_image_data () {
	global $pageShell;
	if ( ! isset( $_SESSION[ 'filenames' ] ) ) {
		$_SESSION[ 'filenames' ] = glob( PROFILE_PHOTOS_LOCAL_DIR . '/*/*/' . PhotoModel::ORIGINAL_FILE );
	}
	if ( ! $_SESSION[ 'filenames' ] ) {
		$pageShell->error( "No photo files found." );
	}

	// Remove filenames already correct from list of filenames.
	clearstatcache();
	while ( $original_file_absolute_path = $_SESSION[ 'filenames' ][ 0 ] ?? null ) {
		$standard_file_absolute_path = str_replace( PhotoModel::ORIGINAL_FILE, PhotoModel::STANDARD_FILE, $original_file_absolute_path );
		$filemtime = filemtime( $standard_file_absolute_path );
		$file_correct_already = $filemtime > CORRECT_SINCE;
		if ( $file_correct_already ) {
			array_shift( $_SESSION[ 'filenames' ] );
			continue;
		} else {
			$file_path_chunks = explode( '/', $original_file_absolute_path );
			array_pop( $file_path_chunks );
			$photo_id = (int) array_pop( $file_path_chunks );
			$photoModel = new PhotoModel( $photo_id );
			if ( $photoModel->getIsDeleted() ) {
				array_shift( $_SESSION[ 'filenames' ] );
				unset( $photoModel );
				continue;
			} else {
				break;
			}
		}
	}
	if ( ! isset( $photoModel ) ) {
		$pageShell->error( "No unprocessed files found." );
	}

	// Get css rotate angle.
	$old_rotate_angle = $photoModel->getRotateAngle();

	// Figure out exif orientation degrees.
	$exif_orientation_degrees = $photoModel->getOriginalPhotoExifOrientationDegrees();

	// Get original photo resource.
	$photo_resource = PhotoModel::getPhotoResource( $original_file_absolute_path );
	if ( ! $photo_resource ) {
		$pageShell->error( "Error getting photo resource." );
	}

	// Rotate original photo resource based on exif orientation.
	if ( $exif_orientation_degrees ) {
		$photo_resource = imagerotate( $photo_resource, $exif_orientation_degrees, 0 );
	}
	$oriented_width  = imagesx( $photo_resource );
	$oriented_height = imagesy( $photo_resource );

	$css_rotation_matches_exif_orientation = $exif_orientation_degrees == $old_rotate_angle;
	if ( $css_rotation_matches_exif_orientation ) {}

	// Create new standard photo.
	$width_ratio  = $oriented_width  / PhotoModel::STANDARD_MAX_WIDTH;
	$height_ratio = $oriented_height / PhotoModel::STANDARD_MAX_HEIGHT;
	$size_ratio = max( $width_ratio, $height_ratio );
	if ( $size_ratio > 1 ) {
		$new_standard_width  = round( $oriented_width / $size_ratio );
		$new_standard_height = round( $oriented_height / $size_ratio );
	} else {
		$new_standard_width  = $oriented_width;
		$new_standard_height = $oriented_height;
	}
	$standard_photo_resource = imagecreatetruecolor( $new_standard_width, $new_standard_height );
	$success = imagecopyresampled( $standard_photo_resource, $photo_resource, 0, 0, 0, 0, $new_standard_width, $new_standard_height, $oriented_width, $oriented_height );
	if ( ! $success ) {
		$pageShell->error( [ 'file' => "Error resizing photo to standard size." ] );
	}
	$temp_standard_photo_path = tempnam( sys_get_temp_dir(), 'typetango_photo_' );
	$success = imagejpeg( $standard_photo_resource, $temp_standard_photo_path );
	if ( ! $success ) {
		$pageShell->error( [ 'file' => "Error saving standard sized photo." ] );
	}

	// Create new thumbnail.
	$width_ratio  = $oriented_width  / PhotoModel::THUMBNAIL_MAX_WIDTH;
	$height_ratio = $oriented_height / PhotoModel::THUMBNAIL_MAX_HEIGHT;
	$size_ratio = max( $width_ratio, $height_ratio );
	if ( $size_ratio > 1 ) {
		$new_thumbnail_width  = round( $oriented_width / $size_ratio );
		$new_thumbnail_height = round( $oriented_height / $size_ratio );
	} else {
		$new_thumbnail_width  = $oriented_width;
		$new_thumbnail_height = $oriented_height;
	}
	$thumbnail_photo_resource = imagecreatetruecolor( $new_thumbnail_width, $new_thumbnail_height );
	$success = imagecopyresampled( $thumbnail_photo_resource, $photo_resource, 0, 0, 0, 0, $new_thumbnail_width, $new_thumbnail_height, $oriented_width, $oriented_height );
	if ( ! $success ) {
		$pageShell->error( [ 'file' => "Error resizing photo to thumbnail size." ] );
	}
	$temp_thumbnail_photo_path = tempnam( sys_get_temp_dir(), 'typetango_photo_' );
	$success = imagejpeg( $thumbnail_photo_resource, $temp_thumbnail_photo_path );
	if ( ! $success ) {
		$pageShell->error( [ 'file' => "Error saving thumbnail." ] );
	}

	$new_standard_file_name  = 'new.standard.jpeg';
	$new_thumbnail_file_name = 'new.thumbnail.jpeg';

	// Move photos to correct locations.
	$photo_local_dir = $photoModel->getPhotoLocalDir();

	$new_standard_file_absolute_path = "$photo_local_dir/$new_standard_file_name";
	rename( $temp_standard_photo_path, $new_standard_file_absolute_path );

	$new_thumbnail_file_absolute_path = "$photo_local_dir/$new_thumbnail_file_name";
	rename( $temp_thumbnail_photo_path, $new_thumbnail_file_absolute_path );

	$photo_remote_dir = $photoModel->getPhotoRemoteDir();

	if ( null === $old_rotate_angle ) {
		$new_rotate_angle = 0;
	} else {
		$new_rotate_angle = ( $old_rotate_angle + $exif_orientation_degrees ) % 360;
	}

	$username = $photoModel->getUserModel()->getUsername();
	$profile_url = '/profile?username=' . urlencode( $username ) . '#photo_id=' . $photoModel->getId();

	// Respond with file information.
	$photo_data = [
		'photo_id'                 => $photoModel->getId(),
		'user_id'                  => $photoModel->getUserId(),
		'photo_standard_url'       => $photoModel->getStandardUrl(),
		'photo_thumbnail_url'      => $photoModel->getThumbnailUrl(),
		'photo_original_url'       => $photoModel->getOriginalUrl(),
		'photo_new_standard_url'   => "$photo_remote_dir/$new_standard_file_name",
		'photo_new_thumbnail_url'  => "$photo_remote_dir/$new_thumbnail_file_name",
		'exif_orientation_degrees' => $exif_orientation_degrees,
		'current_rotation'         => $old_rotate_angle,
		'new_rotation'             => $new_rotate_angle,
		'new_thumbnail_height'     => $new_thumbnail_height,
		'new_thumbnail_width'      => $new_thumbnail_width,
		'new_standard_height'      => $new_standard_height,
		'new_standard_width'       => $new_standard_width,
		'profile_url'              => $profile_url,
		'username'                 => $username,
	];
	$response_data = [ 'photo_data' => $photo_data ];
	if ( $photo_data[ 'new_rotation' ] ) {
		$response_data[ 'alert' ] = "New rotation is not 0.";
	}
	$pageShell->success( $response_data );
} // handle_get_image_data


function handle_accept_changes () {
	global $pageShell;

	$photo_id = $_POST[ 'photo_id' ] ?? null;
	if ( empty( $photo_id ) ) {
		$pageShell->error( "Empty photo_id" );
	}
	$photoModel = new PhotoModel( $photo_id );
	$new_standard_photo_absolute_path  = $photoModel->getPhotoLocalDir() .'/new.standard.jpeg';
	$new_thumbnail_photo_absolute_path = $photoModel->getPhotoLocalDir() .'/new.thumbnail.jpeg';
	$standard_photo_absolute_path  = $photoModel->getStandardPhotoAbsolutePath();
	$thumbnail_photo_absolute_path = $photoModel->getThumbnailPhotoAbsolutePath();
	rename ( $new_standard_photo_absolute_path, $standard_photo_absolute_path );
	rename ( $new_thumbnail_photo_absolute_path, $thumbnail_photo_absolute_path );

	$form_data = [
		'rotate_angle'     => (int) $_POST[ 'rotate_angle' ],
		'thumbnail_height' => (int) $_POST[ 'new_thumbnail_height' ],
		'thumbnail_width'  => (int) $_POST[ 'new_thumbnail_width' ],
		'standard_width'   => (int) $_POST[ 'new_standard_width' ],
		'standard_height'  => (int) $_POST[ 'new_standard_height' ],
	];
	$photoModel->update( $form_data );

	$pageShell->success("rename ( $new_thumbnail_photo_absolute_path, $thumbnail_photo_absolute_path );");
} // handle_accept_changes

