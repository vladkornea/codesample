<?php

require_once 'LoggedModel.php';

interface PhotoModelInterface extends LoggedModelInterface {
	function delete (): void;
	function getIsDeleted (): bool;
	function getIsEditableBy ( UserModel $userModel = null ): bool;
	function getOriginalPhotoAbsolutePath () : string;
	function getOriginalPhotoExifData ();
	function getOriginalPhotoExifOrientation ();
	function getOriginalPhotoExifOrientationDegrees () : int;
	function getOriginalUrl () : string;
	function getPhotoLocalDir () : string;
	function getPhotoRemoteDir () : string;
	function getRotateAngle (): ?int;
	function getStandardPhotoAbsolutePath () : string;
	function getStandardUrl (): string;
	function getThumbnailHeight (): int;
	function getThumbnailPhotoAbsolutePath () : string;
	function getThumbnailUrl () : string;
	function getThumbnailWidth (): int;
	function getUserId (): int;
	function getUserModel (): UserModel;
	function setCaption ( string $caption ): void;
	function setRotateAngle ( int $rotate_angle ): void;
	function update ( array $form_data, string $event_synopsis = "" );
	static function getPhotoResource ( string $photo_path, string $original_filename, int $imagetype_constant = null );
	static function create ( array $form_data, string $event_synopsis = '', bool $log_query = true );
} // PhotoModelInterface

class PhotoModel extends LoggedModel implements PhotoModelInterface {
	use PhotoTraits;

	const THUMBNAIL_MAX_WIDTH  = 80;
	const THUMBNAIL_MAX_HEIGHT = 80;
	const STANDARD_MAX_WIDTH  = 480;
	const STANDARD_MAX_HEIGHT = 480;
	const MAX_IMAGE_BYTES = 5000000;

	const TYPE_JPEG = 'jpeg';
	const TYPE_GIF  = 'gif';
	const TYPE_PNG  = 'png';

	const STANDARD_FILE  = 'standard.jpeg';
	const THUMBNAIL_FILE = 'thumbnail.jpeg';
	const ORIGINAL_FILE  = 'original.jpeg';

	protected $userModel;
	protected $originalPhotoExifData;

	public function getPhotoRemoteDir () : string {
		return PROFILE_PHOTOS_REMOTE_DIR . '/' . $this->getUserId() . '/' . $this->getId();
	} // getPhotoRemoteDir
	public function getOriginalUrl () : string {
		return $this->getPhotoRemoteDir() . '/' . static::ORIGINAL_FILE;
	} // getOriginalUrl
	public function getStandardUrl (): string {
		return $this->getPhotoRemoteDir() . '/' . static::STANDARD_FILE;
	} // getStandardUrl
	public function getThumbnailUrl () : string {
		return $this->getPhotoRemoteDir() . '/' . static::THUMBNAIL_FILE;
	} // getThumbnailUrl

	public function getPhotoLocalDir () : string {
		return PROFILE_PHOTOS_LOCAL_DIR . '/' . $this->getUserId() . '/' . $this->getId();
	} // getPhotoLocalDir
	public function getOriginalPhotoAbsolutePath () : string {
		return $this->getPhotoLocalDir() . '/' . static::ORIGINAL_FILE;
	} // getOriginalPhotoAbsolutePath
	public function getStandardPhotoAbsolutePath () : string {
		return $this->getPhotoLocalDir() . '/' . static::STANDARD_FILE;
	} // getStandardPhotoAbsolutePath
	public function getThumbnailPhotoAbsolutePath () : string {
		return $this->getPhotoLocalDir() . '/' . static::THUMBNAIL_FILE;
	} // getThumbnailPhotoAbsolutePath

	public function getOriginalPhotoExifData () {
		if ( ! $this->originalPhotoExifData ) {
			$this->originalPhotoExifData = @exif_read_data(
				$this->getOriginalPhotoAbsolutePath()
			);
		}
		return $this->originalPhotoExifData;
	} // getOriginalPhotoExifData

	public function getOriginalPhotoExifOrientation () {
		return $this->getOriginalPhotoExifData()[ 'Orientation' ] ?? null;
	} // getOriginalPhotoExifOrientation

	public function getOriginalPhotoExifOrientationDegrees () : int {
		switch ( $this->getOriginalPhotoExifOrientation() ) {
			case 8:
				return 90;
			case 3:
				return 180;
			case 6:
				return 270;
			default:
				return 0;
		}
	} // getOriginalPhotoExifOrientationDegrees

	/**
	 * @param string $photo_path
	 * @param string $image_type static::TYPE_JPEG, static::TYPE_GIF, or static::TYPE_PNG
	 * @return resource|null
	 */
	static protected function getPhotoResourceFromImageType ( string $photo_path, string $image_type ) {
		switch ( $image_type ) {
			case static::TYPE_JPEG;
				$photo_resource = imagecreatefromjpeg( $photo_path );
				break;
			case static::TYPE_PNG;
				$photo_resource = imagecreatefrompng( $photo_path );
				break;
			case static::TYPE_GIF:
				$photo_resource = imagecreatefromgif( $photo_path );
				break;
			default:
				$photo_resource = null;
				break;
		}
		return is_resource( $photo_resource ) ? $photo_resource : null;
	} // getPhotoResourceFromImageType

	/**
	 * @param string $photo_path
	 * @param string|null $original_filename
	 * @param int|null $exif_imagetype_constant
	 * @return resource|null
	 */
	static public function getPhotoResource ( string $photo_path, string $original_filename = null, int $exif_imagetype_constant = null ) {
		if ( ! $exif_imagetype_constant ) {
			$exif_imagetype_constant = @exif_imagetype( $photo_path );
		}
		$image_type_from_exif = null;
		if ( $exif_imagetype_constant ) {
			switch ( $exif_imagetype_constant ) {
				case IMAGETYPE_JPEG:
					$image_type_from_exif = static::TYPE_JPEG;
					break;
				case IMAGETYPE_PNG:
					$image_type_from_exif = static::TYPE_PNG;
					break;
				case IMAGETYPE_GIF:
					$image_type_from_exif = static::TYPE_GIF;
					break;
			}
			if ( $image_type_from_exif ) {
				$photo_resource = static::getPhotoResourceFromImageType( $photo_path, $image_type_from_exif );
				if ( is_resource( $photo_resource ) ) {
					return $photo_resource;
				}
			}
		}
		if ( ! $original_filename ) {
			return null;
		}
		$original_file_extension = strtolower( pathinfo( $original_filename, PATHINFO_EXTENSION ) );
		$image_type_from_extension = null;
		switch ( $original_file_extension ) {
			case 'jpeg':
			case 'jpg':
				$image_type_from_extension = static::TYPE_JPEG;
				break;
			case 'png':
				$image_type_from_extension = static::TYPE_PNG;
				break;
			case 'gif':
				$image_type_from_extension = static::TYPE_GIF;
				break;
		}
		if ( ! $image_type_from_extension ) {
			return null;
		}
		$already_tried_this = $image_type_from_extension === $image_type_from_exif;
		if ( $already_tried_this  ) {
			return null;
		}
		$photo_resource = static::getPhotoResourceFromImageType( $photo_path, $image_type_from_extension );
		if ( ! is_resource( $photo_resource ) ) {
			trigger_error( "Error creating resource from file.", E_USER_WARNING );
			return null;
		}
		return $photo_resource;
	} // getPhotoResource

	/**
	 * @param array $form_data can have ad hoc fields 'file' and 'original_filename'
	 * @param string $event_synopsis
	 * @param bool $log_query
	 * @return array|int
	 */
	static public function create ( array $form_data, string $event_synopsis = '', bool $log_query = true ) {
		// refute invalid arguments
		if ( empty( $form_data[ 'user_id' ] ) ) {
			return [ 'user_id' => "Empty `user_id` argument." ];
		}
		if ( ! is_numeric( $form_data[ 'user_id' ] ) ) {
			return [ 'user_id' => "Invalid `user_id` argument." ];
		}
		if ( empty( $form_data[ 'file' ] ) ) {
			trigger_error( 'Empty `file` argument.', E_USER_WARNING );
			return [ 'file' => "Empty `file` argument." ];
		}
		if ( empty( $form_data[ 'original_filename' ] ) ) {
			return [ 'original_filename' => "Empty `original_filename` argument." ];
		}
		if ( filesize( $form_data[ 'file' ] ) > static::MAX_IMAGE_BYTES ) {
			return [ 'file' => "File size exceeds " .static::MAX_IMAGE_BYTES ." byte limit." ];
		}

		// Extract ad hoc field 'file'
		$temp_original_photo_path = $form_data[ 'file' ];
		unset( $form_data[ 'file' ] );
		if ( ! file_exists( $temp_original_photo_path ) ) {
			trigger_error( "File not found: $temp_original_photo_path", E_USER_WARNING );
			return [ 'file' => "File not found." ];
		}

		// Extract ad hoc field 'original_filename'
		$original_filename = $form_data[ 'original_filename' ]; // DB does not care, but we accept the field because we might need to know the extension.
		unset( $form_data[ 'original_filename' ] );

		$exif_imagetype_constant = @exif_imagetype( $temp_original_photo_path );

		// Create image resource from original photo file.
		$uploaded_photo_resource = static::getPhotoResource( $temp_original_photo_path, $original_filename, $exif_imagetype_constant );
		if ( ! $uploaded_photo_resource ) {
			trigger_error("Error creading resource from file.", E_USER_WARNING);
			return [ 'file' => "Error creating resource from file." ];
		}

		// Set original photo width and height.
		$form_data[ 'original_width' ]  = imagesx( $uploaded_photo_resource );
		$form_data[ 'original_height' ] = imagesy( $uploaded_photo_resource );

		// Rotate image based on orientation in exif metadata.
		$oriented_width  = $form_data[ 'original_width' ];
		$oriented_height = $form_data[ 'original_height' ];
		$original_exif_orientation = @exif_read_data( $temp_original_photo_path )[ 'Orientation' ] ?? null;
		if ( $original_exif_orientation ) {
			$rotate_angle = 0;
			switch ( $original_exif_orientation ) {
				case 8:
					$rotate_angle = 90;
					break;
				case 3:
					$rotate_angle = 180;
					break;
				case 6:
					$rotate_angle = 270;
					break;
			}
			if ( $rotate_angle ) {
				$uploaded_photo_resource = imagerotate( $uploaded_photo_resource, $rotate_angle, 0 );
				$oriented_width  = imagesx( $uploaded_photo_resource );
				$oriented_height = imagesy( $uploaded_photo_resource );
			}
		}

		// Standard photo.
		$width_ratio = $oriented_width / static::STANDARD_MAX_WIDTH;
		$height_ratio = $oriented_height / static::STANDARD_MAX_HEIGHT;
		$size_ratio = max( $width_ratio, $height_ratio );
		if ( $size_ratio > 1 ) {
			$form_data[ 'standard_width' ]  = round( $oriented_width / $size_ratio );
			$form_data[ 'standard_height' ] = round( $oriented_height / $size_ratio );
		} else {
			$form_data[ 'standard_width' ]  = $oriented_width;
			$form_data[ 'standard_height' ] = $oriented_height;
		}
		$standard_photo_resource = imagecreatetruecolor( $form_data[ 'standard_width' ], $form_data[ 'standard_height' ] );
		$success = imagecopyresampled( $standard_photo_resource, $uploaded_photo_resource, 0, 0, 0, 0, $form_data[ 'standard_width' ], $form_data[ 'standard_height' ], $oriented_width, $oriented_height );
		if ( ! $success ) {
			return [ 'file' => "Error resizing photo to standard size." ];
		}
		$temp_standard_photo_path = tempnam( sys_get_temp_dir(), 'typetango_photo_' );
		$success = imagejpeg( $standard_photo_resource, $temp_standard_photo_path );
		if ( ! $success ) {
			return [ 'file' => "Error saving standard sized photo." ];
		}

		// Thumbnail.
		$thumbnail_width_ratio  = $oriented_width / static::THUMBNAIL_MAX_WIDTH;
		$thumbnail_height_ratio = $oriented_height / static::THUMBNAIL_MAX_HEIGHT;
		$thumbnail_size_ratio = max( $thumbnail_width_ratio, $thumbnail_height_ratio );
		if ( $thumbnail_size_ratio > 1 ) {
			$form_data[ 'thumbnail_width' ]  = round($oriented_width / $thumbnail_size_ratio );
			$form_data[ 'thumbnail_height' ] = round($oriented_height / $thumbnail_size_ratio );
		} else {
			$form_data[ 'thumbnail_width' ]  = $oriented_width;
			$form_data[ 'thumbnail_height' ] = $oriented_height;
		}
		$thumbnail_resource = imagecreatetruecolor( $form_data[ 'thumbnail_width' ], $form_data[ 'thumbnail_height' ] );
		$success = imagecopyresampled( $thumbnail_resource, $uploaded_photo_resource, 0, 0, 0, 0, $form_data[ 'thumbnail_width' ], $form_data[ 'thumbnail_height' ], $oriented_width, $oriented_height );
		if ( ! $success ) {
			return [ 'file' => "Error resizing photo to thumbnail size." ];
		}
		$temp_thumbnail_path = tempnam( sys_get_temp_dir(), 'typetango_photo_' );
		$success = imagejpeg( $thumbnail_resource, $temp_thumbnail_path );
		if ( ! $success ) {
			return [ 'file' => "Error saving thumbnail." ];
		}

		// insert data
		$photo_id = parent::create( $form_data );
		if ( ! is_numeric( $photo_id ) ) {
			$error_message = $photo_id;
			return [ 'file' => $error_message ];
		}

		// move photos to correct locations
		$user_id = (int) $form_data[ 'user_id' ];
		$new_photo_dir = PROFILE_PHOTOS_LOCAL_DIR ."/$user_id/$photo_id";
		mkdir( $new_photo_dir, 0774, true );

		rename( $temp_original_photo_path, $new_photo_dir . '/' . static::ORIGINAL_FILE );
		chmod( $new_photo_dir . '/' . static::ORIGINAL_FILE, 0774 );
		rename( $temp_standard_photo_path, $new_photo_dir . '/' . static::STANDARD_FILE );
		chmod( $new_photo_dir . '/' . static::STANDARD_FILE, 0774 );
		rename( $temp_thumbnail_path, $new_photo_dir . '/' . static::THUMBNAIL_FILE );
		chmod( $new_photo_dir . '/' . static::THUMBNAIL_FILE, 0774 );

		// update `photo_order` in `users` table
		$user_update = [];
		$userModel = new UserModel( $user_id );
		$old_photo_order = $userModel->getPhotoOrder();
		if ( $old_photo_order ) {
			$user_update[ 'photo_order' ] = "$old_photo_order,$photo_id";
		} else {
			$photoModel = new PhotoModel( $photo_id );
			$user_update[ 'photo_order' ] = $photo_id;
			$user_update[ 'primary_thumbnail_width' ]        = $form_data[ 'thumbnail_width' ] ?? 0;
			$user_update[ 'primary_thumbnail_height' ]       = $form_data[ 'thumbnail_height' ] ?? 0;
			$user_update[ 'primary_thumbnail_rotate_angle' ] = $form_data[ 'rotate_angle' ] ?? 0;
		}
		$userModel->update( $user_update );

		return $photo_id;
	} // create

	/**
	 * @param array $form_data
	 * @param string $event_synopsis
	 * @return number|array affected rows or error message keyed by field name
	 */
	public function update ( array $form_data, string $event_synopsis = "" ) {
		$editable_fields = [ 'caption', 'deleted', 'rotate_angle', 'thumbnail_height', 'thumbnail_width', 'standard_width', 'standard_height' ];
		$row_data = [];
		foreach ( $form_data as $field_name => $form_field_value ) {
			$field_is_editable = in_array( $field_name, $editable_fields );
			if ( ! $field_is_editable ) {
				$error_message = [ $field_name => "Photo field $field_name is not editable" ];
				return $error_message;
			}
			switch ( $field_name ) {
				case 'rotate_angle':
					$row_field_value = $form_field_value % 360;
					break;
				case 'caption':
					$row_field_value = trim( $form_field_value );
					break;
				case 'deleted':
					$row_field_value = (bool) $form_field_value;
					break;
				default:
					$row_field_value = $form_field_value;
					break;
			}
			$row_data[ $field_name ] = $row_field_value;
		}
		$affected_rows = parent::update( $row_data, $event_synopsis );
		$this_is_the_primary_photo = $this->getUserModel()->getPrimaryPhotoId() === $this->getId();
		if ( $this_is_the_primary_photo ) {
			$user_row = [
				'primary_thumbnail_width'        => $row_data[ 'thumbnail_width' ]  ?? $this->getThumbnailWidth(),
				'primary_thumbnail_height'       => $row_data[ 'thumbnail_height' ] ?? $this->getThumbnailHeight(),
				'primary_thumbnail_rotate_angle' => $row_data[ 'rotate_angle' ]     ?? $this->getRotateAngle(),
			];
			$this->getUserModel()->update( $user_row );
		}
		return $affected_rows;
	} // update

	public function getIsEditableBy ( UserModel $userModel = null ): bool {
		if ( ! $userModel ) {
			return false;
		}
		if ( $userModel->getIsAdmin() ) {
			return true;
		}
		$photo_belongs_to_this_user = $userModel->getId() === $this->getUserId();
		return $photo_belongs_to_this_user;
	} // getIsEditableBy

	public function getUserId (): int {
		return (int) $this->commonGet( 'user_id' );
	} // getUserId

	public function getUserModel (): UserModel {
		if ( ! $this->userModel ) {
			$this->userModel = new UserModel( $this->getUserId() );
		}
		return $this->userModel;
	} // getUserModel

	public function setCaption ( string $caption ): void {
		$this->update( [ 'caption' => $caption ] );
	} // setCaption

	public function setRotateAngle ( int $rotate_angle ): void {
		$rotate_angle = $rotate_angle % 360;
		$invalid_rotate_angle = $rotate_angle % 90;
		if ( $invalid_rotate_angle ) {
			trigger_error( "Invalid rotate angle: $rotate_angle", E_USER_WARNING );
			$rotate_angle = 0;
		}
		$this->update( [ 'rotate_angle' => $rotate_angle ] );
	} // setRotateAngle

	public function delete (): void {
		$this->update( [ 'deleted' => true ] );

		{ // update photo_order
			$photo_id = $this->getId();
			$user_id  = $this->getUserId();
			$userModel = new UserModel( $user_id );
			$original_photo_order_string = $userModel->getPhotoOrder();
			$original_photo_order_array = explode( ',', $original_photo_order_string );
			$original_index_position = array_search( $photo_id, $original_photo_order_array );
			if ( $original_index_position === false ) {
				trigger_error( "Programmer Error: Deleted photo ID $photo_id is not in photo_order $original_photo_order_string.", E_USER_WARNING );
				return;
			}
			$other_photos_exist = count( $original_photo_order_array ) > 1 ? true : false;
			if ( ! $other_photos_exist ) {
				$user_update = [
					'photo_order' => ''
					,'primary_thumbnail_width'  => 0
					,'primary_thumbnail_height' => 0
					,'primary_thumbnail_rotate_angle' => null
				];
				$userModel->update( $user_update );
			}
			if ( $other_photos_exist ) {
				$user_update = [];
				$new_photo_order_array = $original_photo_order_array;
				unset( $new_photo_order_array[ $original_index_position ] );
				$new_photo_order_string = implode( ',', $new_photo_order_array );
				$user_update[ 'photo_order' ] = $new_photo_order_string;

				$thumbnail_dimensions_need_updating = $original_index_position === 0 ? true : false;
				if ( $thumbnail_dimensions_need_updating ) {
					$new_first_photo_id = $original_photo_order_array[ 1 ];
					$newFirstPhotoModel = new PhotoModel( $new_first_photo_id );
					$user_update[ 'primary_thumbnail_width' ]        = $newFirstPhotoModel->getThumbnailWidth();
					$user_update[ 'primary_thumbnail_height' ]       = $newFirstPhotoModel->getThumbnailHeight();
					$user_update[ 'primary_thumbnail_rotate_angle' ] = $newFirstPhotoModel->getRotateAngle();
				}
				$userModel->update( $user_update );
			}
		} // photo_order updated

		$this->deleteFiles();
	} // delete

	public function getIsDeleted (): bool {
		return $this->commonGet( 'deleted' );
	} // getIsDeleted

	public function getThumbnailWidth (): int {
		return $this->commonGet( 'thumbnail_width' );
	} // getThumbnailWidth

	public function getThumbnailHeight (): int {
		return $this->commonGet( 'thumbnail_height' );
	} // getThumbnailHeight

	public function getRotateAngle (): ?int {
		return $this->commonGet( 'rotate_angle' );
	} // getRotateAngle

	public function deleteFiles () {
		if ( ! $this->getIsDeleted() ) {
			throw new Exception( "Error deleting files: photo is not marked as deleted in the DB" );
		}
		unlink( $this->getThumbnailPhotoAbsolutePath() );
		unlink( $this->getStandardPhotoAbsolutePath() );
		$original_photo_absolute_path = $this->getOriginalPhotoAbsolutePath();
		if ( file_exists( $original_photo_absolute_path ) ) {
			unlink( $original_photo_absolute_path );
		}
		rmdir( $this->getPhotoLocalDir() );
	} // deleteFiles

} // PhotoModel

