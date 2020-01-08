<?php
require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';
$userModel = Session::getUserModel();
if ( ! $userModel || ! $userModel->getIsAdmin() ) {
	HttpPageShell::forbid();
}
header('Content-Type: text/javascript');
?>
function enableAdminPhotoRotate () {
	var $selectedPhotoContainer = $( '#selected-photo-container' )
	$selectedPhotoContainer.on( 'click', handlePhotoContainerClick )
	return // functions below
	function handlePhotoContainerClick ( clickEvent ) {
		if ( ! clickEvent.ctrlKey ) {
			return
		}

		// Calculate degrees to rotate.
		var $photoContainer = $( clickEvent.target )
		var mousePageX = clickEvent.pageX
		var mousePageY = clickEvent.pageY
		var photoTopLeftPageX = $photoContainer.offset().left
		var photoTopLeftPageY = $photoContainer.offset().top
		var photoWidth = $photoContainer.width()
		var photoHeight = $photoContainer.height()
		var photoCenterPageX = photoTopLeftPageX + ( photoWidth / 2 )
		var photoCenterPageY = photoTopLeftPageY + ( photoHeight / 2 )
		var clickedLeftOfCenter = mousePageX < photoCenterPageX
		var clickedAboveMiddle = mousePageY < photoCenterPageY
		var degreesToRotate = 0
		if ( ! clickedLeftOfCenter && clickedAboveMiddle ) {
			degreesToRotate = 270
		} else if ( ! clickedLeftOfCenter && ! clickedAboveMiddle ) {
			degreesToRotate = 180
		} else if ( clickedLeftOfCenter && ! clickedAboveMiddle ) {
			degreesToRotate = 90
		}
		if ( ! degreesToRotate ) {
			return
		}

		// Confirm rotation.
		var rotationConfirmed = confirm( "Rotate photo " + degreesToRotate + " degrees and refresh?" );
		if ( ! rotationConfirmed ) {
			return
		}

		// Call the server.
		var photoId = $( '#selected-carousel-photo' ).data( 'photo_id' )
		var requestArgs = { 'photo_id': photoId, 'add_to_rotate_angle': degreesToRotate }
		apiCall( '/pages/profile/ajax?action=edit_photo', handleRotatePhotoResponse, requestArgs )
	} // handlePhotoContainerClick

	function handleRotatePhotoResponse ( response ) {
		location.reload()
	} // handleRotatePhotoResponse
} // addAdminActions
