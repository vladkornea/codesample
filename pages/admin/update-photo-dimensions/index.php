<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new AdminPageShell( "Update Photo Dimensions" );

if ( ! empty( $_POST ) ) {
	DB::log();
	$thumbnail_absolute_paths = glob( PROFILE_PHOTOS_LOCAL_DIR . '/*/*/' . PhotoModel::THUMBNAIL_FILE );
	echo '<pre>';
	foreach ( $thumbnail_absolute_paths as $thumbnail_absolute_path ) {
		$path_chunks = explode( '/', $thumbnail_absolute_path );
		$photo_id = $path_chunks[ count( $path_chunks ) - 2 ];
		$photoModel = new PhotoModel( $photo_id );
		try {
			$photoModel->getIsDeleted();
		} catch ( Exception $exception ) {
			continue;
		}
		[ $thumbnail_width, $thumbnail_height ] = getimagesize( $photoModel->getThumbnailPhotoAbsolutePath() );
		[  $standard_width,  $standard_height ] = getimagesize( $photoModel->getStandardPhotoAbsolutePath() );
		if ( ! $thumbnail_width or ! $thumbnail_height ) {
			echo "<p>Missing thumbnail width or height for photo ID $photo_id</p>";
			return;
		}
		if ( ! $standard_width or ! $standard_height ) {
			echo "<p>Missing standard width or height for photo ID $photo_id</p>";
			return;
		}
		try {
			$row_update = [
				'thumbnail_width'  => $thumbnail_width,
				'thumbnail_height' => $thumbnail_height,
				'standard_width'   => $standard_width,
				'standard_height'  => $standard_height,
			];
			$photoModel->update( $row_update );
		} catch ( Exception $exception ) {
			continue;
		}
	}
	$log = DB::log( false );
	var_dump( $log );
	echo '</pre>';
}

?>
<form method="post" action="<?= htmlspecialchars( $_SERVER[ 'REQUEST_URI' ] ) ?>">
	<input type="hidden" name="action" value="Update Photo Dimensions">
	<input type="submit" value="Update Photo Dimensions">
</form>

