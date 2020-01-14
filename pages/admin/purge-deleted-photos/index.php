<?php
require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';
$pageShell = new AdminPageShell("Purge Deleted Photos");
?>
<form method="post" action="<?= htmlspecialchars( $_SERVER[ 'REQUEST_URI' ] ) ?>">
<?php
$photoFinder = new PhotoFinder();
$photoFinder->setIsDeleted();
$photosFound = $photoFinder->find();
while ( $photo_id = DB::getCell( $photosFound ) ) {
	$photoModel = new PhotoModel( $photo_id );
	if ( ! empty( $_POST[ 'process' ] ) ) {
		$photoModel->deleteFiles();
	} else {
		$glob_pattern = $photoModel->getPhotoLocalDir() . '/thumbnail.jpeg';
		$absolute_paths = glob( $glob_pattern );
		foreach ( $absolute_paths as $absolute_path ) {
			try {
				$path_chunks = explode( '/', $absolute_path );
				$filename = $path_chunks[ count( $path_chunks ) - 1 ];
				$img_src = $photoModel->getPhotoRemoteDir() . '/' . $filename;
				$profile_url = "/profile?username=" .urlencode( $photoModel->getUserModel()->getUsername() ); ?>
	<a href="<?=htmlspecialchars($profile_url)?>" target="_blank">
	<img src="<?=htmlspecialchars($img_src)?>" alt="<?=htmlspecialchars($img_src)?>">
	</a><?php
			} catch ( Exception $exception ) {
				echo $exception->getMessage();
			}
		}
	}
}
?>
<input type="hidden" name="process" value="1">
<input type="submit" value="Purge Deleted Photos" style="display:block;">
</form>
