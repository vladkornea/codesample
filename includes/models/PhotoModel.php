<?php

require_once 'LoggedModel.php';

interface PhotoModelInterface extends LoggedModelInterface {
	function setCaption ( string $caption ): void;
	function getIsDeleted (): bool;
	function getIsEditableBy ( UserModel $userModel = null ): bool;
	function getRotateAngle (): ?int;
	function getUserId (): int;
	function getThumbnailWidth (): int;
	function getThumbnailHeight (): int;
	function delete (): void;
} // PhotoModelInterface

class PhotoModel extends LoggedModel implements PhotoModelInterface {
	use PhotoTraits;

	const THUMBNAIL_MAX_WIDTH  = 80;
	const THUMBNAIL_MAX_HEIGHT = 80;
	const STANDARD_MAX_WIDTH  = 480;
	const STANDARD_MAX_HEIGHT = 480;
	const MAX_IMAGE_BYTES = 2000000;

	protected $userModel = null;

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

		// handle ad hoc field 'file'
		$temp_original_photo_path = $form_data['file'];
		if ( ! file_exists( $temp_original_photo_path ) ) {
			trigger_error( "File not found: $temp_original_photo_path", E_USER_WARNING );
			return [ 'file' => "File not found." ];
		}
		unset( $form_data[ 'file' ] );

		// handle ad hoc field 'original_filename'
		$original_filename = $form_data[ 'original_filename' ]; // DB does not care, but we need to know the extension.
		unset( $form_data[ 'original_filename' ] );

		// create image resource from original photo file
		$original_file_extension = strtolower( pathinfo( $original_filename, PATHINFO_EXTENSION ) );
		switch ( $original_file_extension ) {
			case 'jpeg':
			case 'jpg':
				$uploaded_photo_resource = imagecreatefromjpeg( $temp_original_photo_path );
				break;
			case 'png':
				$uploaded_photo_resource = imagecreatefrompng( $temp_original_photo_path );
				break;
			case 'gif':
				$uploaded_photo_resource = imagecreatefromgif( $temp_original_photo_path );
				break;
//			case 'bmp':
//				$uploaded_photo_resource = imagecreatefrombmp( $temp_original_photo_path ); // PHP 7.2 required
//				break;
			default:
				return [ 'file' => "Unrecognized image format." ];
				break;
		} // switch
		if ( ! $uploaded_photo_resource ) {
			return [ 'file' => "Error creating resource from file." ];
		}

		// Rotate image based on orientation in exif metadata.
		$imagetype_constant = exif_imagetype( $temp_original_photo_path );
		if ( false !== $imagetype_constant ) {
			$exif_data = exif_read_data( $temp_original_photo_path );
			$exif_orientation = $exif_data[ 'Orientation' ] ?? null;
			if ( $exif_orientation ) {
				switch ( $exif_orientation ) {
					case 8:
						$uploaded_photo_resource = imagerotate( $uploaded_photo_resource, 90, 0 );
						break;
					case 3:
						$uploaded_photo_resource = imagerotate( $uploaded_photo_resource, 180, 0 );
						break;
					case 6:
						$uploaded_photo_resource = imagerotate( $uploaded_photo_resource, 270, 0 );
						break;
				}
			}
		}

		// set original photo width and height
		$form_data[ 'original_width' ]  = imagesx( $uploaded_photo_resource );
		$form_data[ 'original_height' ] = imagesy( $uploaded_photo_resource );

		// standard photo
		$width_ratio = $form_data[ 'original_width' ] / static::STANDARD_MAX_WIDTH;
		$height_ratio = $form_data[ 'original_height' ] / static::STANDARD_MAX_HEIGHT;
		$size_ratio = max( $width_ratio, $height_ratio );
		if ( $size_ratio > 1 ) {
			$form_data[ 'standard_width' ]  = round( $form_data[ 'original_width' ] / $size_ratio );
			$form_data[ 'standard_height' ] = round( $form_data[ 'original_height' ] / $size_ratio );
		} else {
			$form_data[ 'standard_width' ]  = $form_data[ 'original_width' ];
			$form_data[ 'standard_height' ] = $form_data[ 'original_height' ];
		}
		$standard_photo_resource = imagecreatetruecolor( $form_data[ 'standard_width' ], $form_data[ 'standard_height' ] );
		$success = imagecopyresampled( $standard_photo_resource, $uploaded_photo_resource, 0, 0, 0, 0, $form_data[ 'standard_width' ], $form_data[ 'standard_height' ], $form_data[ 'original_width' ], $form_data[ 'original_height' ] );
		if ( ! $success ) {
			return [ 'file' => "Error resizing photo to standard size." ];
		}
		$temp_standard_photo_path = tempnam( sys_get_temp_dir(), 'typetango_photo_' );
		$success = imagejpeg( $standard_photo_resource, $temp_standard_photo_path );
		if ( ! $success ) {
			return [ 'file' => "Error saving standard sized photo." ];
		}

		// thumbnail
		$thumbnail_width_ratio  = $form_data[ 'original_width' ] / static::THUMBNAIL_MAX_WIDTH;
		$thumbnail_height_ratio = $form_data[ 'original_height' ] / static::THUMBNAIL_MAX_HEIGHT;
		$thumbnail_size_ratio = max( $thumbnail_width_ratio, $thumbnail_height_ratio );
		if ( $thumbnail_size_ratio > 1 ) {
			$form_data[ 'thumbnail_width' ]  = round($form_data[ 'original_width' ] / $thumbnail_size_ratio );
			$form_data[ 'thumbnail_height' ] = round($form_data[ 'original_height' ] / $thumbnail_size_ratio );
		} else {
			$form_data[ 'thumbnail_width' ]  = $form_data[ 'original_width' ];
			$form_data[ 'thumbnail_height' ] = $form_data[ 'original_height' ];
		}
		$thumbnail_resource = imagecreatetruecolor( $form_data[ 'thumbnail_width' ], $form_data[ 'thumbnail_height' ] );
		$success = imagecopyresampled( $thumbnail_resource, $uploaded_photo_resource, 0, 0, 0, 0, $form_data[ 'thumbnail_width' ], $form_data[ 'thumbnail_height' ], $form_data[ 'original_width' ], $form_data[ 'original_height' ] );
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

		rename( $temp_original_photo_path, "$new_photo_dir/original.jpeg" );
		chmod( "$new_photo_dir/original.jpeg", 0774 );
		rename( $temp_standard_photo_path, "$new_photo_dir/standard.jpeg" );
		chmod( "$new_photo_dir/standard.jpeg", 0774 );
		rename( $temp_thumbnail_path, "$new_photo_dir/thumbnail.jpeg" );
		chmod( "$new_photo_dir/thumbnail.jpeg", 0774 );

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
		$editable_fields = [ 'caption', 'deleted', 'rotate_angle' ];
		$row_data = [];
		foreach ( $form_data as $field_name => $form_field_value ) {
			$field_is_editable = in_array( $field_name, $editable_fields );
			if ( ! $field_is_editable ) {
				$error_message = [ $field_name => "Photo field $field_name is not editable" ];
				return $error_message;
			}
			switch ( $field_name ) {
				case 'rotate_angle':
					$rotate_angle = $form_field_value % 360;
					$row_field_value = $rotate_angle;
					$primary_photo_id = $this->getUserModel()->getPrimaryPhotoId();
					if ( $this->getId() == $primary_photo_id ) {
						if ( $this->getRotateAngle() != $rotate_angle ) {
							$this->getUserModel()->setPrimaryThumbnailRotateAngle($rotate_angle);
						}
					}
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
		return parent::update( $row_data, $event_synopsis );
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

} // PhotoModel

