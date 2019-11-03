<?php

require_once 'BaseFinder.php';

interface PhotoFinderInterface extends BaseFinderInterface {
	static function getPhotoCarouselPhotosData ($user_id, $ordered_ids);
	function setIdOrder ($ordered_ids);
	function setUserId ($user_id);
} // PhotoFinderInterface

class PhotoFinder extends BaseFinder implements PhotoFinderInterface {
	use PhotoTraits;

	protected $userId;
	protected $idOrder;

	/** @param number $user_id */
	public function setUserId ($user_id) {
		$this->userId = (int)$user_id;
	} // setUserId

	/**
	 * @param string $ordered_ids like '123,31,23'
	 * @throws InvalidArgumentException if string contains anything other than commas and numbers
	 */
	public function setIdOrder ($ordered_ids) {
		// make sure nothing is in there but commas and numbers
		$regex_pattern = '![^\d,]!';
		if (preg_match($regex_pattern, $ordered_ids)) {
			throw new InvalidArgumentException("Attempted to pass $ordered_ids as \$ordered_ids");
		}
		$this->idOrder = $ordered_ids;
	} // setIdOrder

	protected static function getAdHocFields () {
		$ad_hoc_fields = [];
		$ad_hoc_fields['standard_url']  = "concat('" .PROFILE_PHOTOS_REMOTE_DIR ."/', photos.user_id, '/', photos.photo_id, '/standard.jpeg')";
		$ad_hoc_fields['thumbnail_url'] = "concat('" .PROFILE_PHOTOS_REMOTE_DIR ."/', photos.user_id, '/', photos.photo_id, '/thumbnail.jpeg')";
		$ad_hoc_fields['uploaded']      = 'if( photos.inserted, date_format(photos.inserted, "%M %Y"), "before 2018" )';
		return $ad_hoc_fields;
	} // getAdHocFields

	/**
	 * @param number $user_id
	 * @param string $ordered_ids like '12,31,51'
	 * @return array
	 */
	public static function getPhotoCarouselPhotosData ($user_id, $ordered_ids) {
		if (!is_numeric($user_id)) {
			throw new InvalidArgumentException("user_id is not numeric");
		}
		$photoFinder = new self();
		$photoFinder->setUserId($user_id);
		$photoFinder->setIdOrder($ordered_ids);
		$desired_fields = ['photo_id', 'caption', 'standard_url', 'standard_width', 'standard_height', 'thumbnail_url', 'thumbnail_width', 'thumbnail_height', 'rotate_angle', 'uploaded'];
		$result = $photoFinder->find($desired_fields);
		$photo_carousel_photos_data = DB::getTable($result);
		return $photo_carousel_photos_data;
	}  // getPhotoCarouselPhotosData

	public function find (array $resource_fields = null): mysqli_result {
		$where = ['deleted' => false];
		if ($this->userId) {
			$where['user_id'] = $this->userId;
		}
		$order_by_clause = '';
		if ($this->idOrder) {
			$order_by_clause = 'order by find_in_set(photo_id, "'.DB::escape($this->idOrder).'")';
		}
		$valid_desired_fields = $this->getValidDesiredFields($resource_fields ?: [static::$primaryKeyName]);
		$query = '
			select ' .static::getSelectClause($valid_desired_fields) .'
			from ' .static::$tableName .'
			where ' .DB::where($where) ."
			$order_by_clause"
		;
		$result = DB::query($query);
		return $result;
	} // find
} // PhotoFinder

