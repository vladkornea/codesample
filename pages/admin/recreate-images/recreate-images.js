$( printRecreateImagesPageInterface )

function printRecreateImagesPageInterface () {
	var $localContainer = $( 'main' );
	requestImageData()
	return // functions below
	function requestImageData () {
		apiCall( '/pages/admin/recreate-images/ajax.php?action=get_image_data', handlePhotoDataResponse );
	} // requestImageData

	function handlePhotoDataResponse (response ) {
		if ( ! response[ 'photo_data' ] ) {
			alert( "No photo data in response." )
			return
		}
		printForm( response[ 'photo_data' ] )
	} // handlePhotoDataResponse

	function printForm ( photoData ) {
		console.log( photoData )
		var $form = $(
			'<form method="post" action="/pages/admin/recreate-images/ajax?action=accept_changes">' +
				' Rotate angle <input type="text" name="rotate_angle" value="' + photoData[ 'new_rotation' ] + '" size="3">' +
				' <input type="submit" value="Apply">' +
				' <a href="' + photoData['profile_url'] + '" target="_blank">' + photoData[ 'username' ] + '</a>' +
				'<br>' +
				'<img src="' + photoData[ 'photo_standard_url' ] + '" alt="Old Standard">' +
				' <img src="' + photoData[ 'photo_thumbnail_url' ] + '" alt="Old Thumbnail">' +
				'<br>' +
				'<img src="' + photoData[ 'photo_new_standard_url' ] + '" alt="New Standard">' +
				' <img src="' + photoData[ 'photo_new_thumbnail_url' ] + '" alt="New Thumbnail">' +
				'<br>' +
				'<img src="' + photoData[ 'photo_original_url' ] + '" alt="Original Image">' +
				'<input type="hidden" name="photo_id" value="' + photoData[ 'photo_id' ]  + '">' +
				'<input type="hidden" name="new_thumbnail_width" value="' + photoData[ 'new_thumbnail_width' ]  + '">' +
				'<input type="hidden" name="new_thumbnail_height" value="' + photoData[ 'new_thumbnail_height' ]  + '">' +
				'<input type="hidden" name="new_standard_width" value="' + photoData[ 'new_standard_width' ]  + '">' +
				'<input type="hidden" name="new_standard_height" value="' + photoData[ 'new_standard_height' ]  + '">' +
			'</form>'
		)
		$form.on( 'submit', handleFormSubmit )
		$form.appendTo( $localContainer )
	} // printForm

	function handleFormSubmit ( event ) {
		event.preventDefault()
		submitFormViaAjax( event.currentTarget, handleFormSubmitResponse )
	} // handleFormSubmit

	function handleFormSubmitResponse ( response ) {
		console.log( response )
		location.reload()
	} // handleFormSubmitResponse
} // printRecreateImagesPageInterface

