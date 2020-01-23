<?php

require_once 'LoggedModel.php';

interface SearchCriteriaModelInterface extends LoggedModelInterface {
	function getUserId (): int;
	function getUserModel (): UserModel;
	function getSearchCriteria (array $desired_fields): array;
	function getSearchResults (int $current_page = 1, int $page_size = 10): array;
} // SearchCriteriaModelInterface

class SearchCriteriaModel extends LoggedModel implements SearchCriteriaModelInterface {
	use SearchCriteriaTraits;

	/* @see getUserModel() */ protected $userModel;


	static protected function transformFormDataToDbRowFormat (array $form_data, SearchCriteriaModel $existingModel = null): array {
		$db_row = [];
		$error_messages = [];

		// inserts only
		if (!$existingModel) {
			// user_id
			if (empty($form_data['user_id'])) {
				$error_messages['user_id'] = "Empty required field: user_id";
				trigger_error($error_messages['user_id'], E_USER_WARNING); // programmer error
			}
			$db_row['user_id'] = $form_data['user_id'];
		}

		// updates only
		if ($existingModel) {
			// user_id
			if (array_key_exists('user_id', $form_data)) {
				$error_messages['user_id'] = "Attempted to change a search_criteria's user_id";
				trigger_error($error_messages['user_id'], E_USER_WARNING); // programmer error
			}
		}

		// country
		if (array_key_exists('country', $form_data)) {
			$db_row['country'] = strtoupper(trim($form_data['country']));
		}

		// max_distance
		if (array_key_exists('max_distance', $form_data)) {
			$db_row['max_distance'] = (function(int $max_distance): ?int {
				if ($max_distance < 1) {
					return null;
				}
				return min(999, $max_distance);
			})(intval($form_data['max_distance']));
		}

		// exclude_contacted
		if (array_key_exists('exclude_contacted', $form_data)) {
			$db_row['exclude_contacted'] = (bool)$form_data['exclude_contacted'];
		}

		// gender
		if (array_key_exists('gender', $form_data)) {
			$db_row['gender'] = $form_data['gender'];
		}

		// match_shared_negatives
		if (array_key_exists('match_shared_negatives', $form_data)) {
			$db_row['match_shared_negatives'] = (bool)$form_data['match_shared_negatives'];
		}

		// mbti_types
		if (array_key_exists('mbti_types', $form_data)) {
			$db_row['mbti_types'] = $form_data['mbti_types'];
		}

		// min_age
		if (array_key_exists('min_age', $form_data)) {
			$db_row['min_age'] = (function(int $min_age): ?int {
				if ($min_age < 1) {
					return null;
				}
				return min(250, $min_age);
			})(intval($form_data['min_age']));
		}

		// max_age
		if (array_key_exists('max_age', $form_data)) {
			$db_row['max_age'] = (function(int $max_age){
				if ($max_age < 1) {
					return null;
				}
				return min(250, $max_age);
			})(intval($form_data['max_age']));
		}

		// must_have_description
		if (array_key_exists('must_have_description', $form_data)) {
			$db_row['must_have_description'] = (bool)$form_data['must_have_description'];
		}

		// must_have_picture
		if (array_key_exists('must_have_picture', $form_data)) {
			$db_row['must_have_picture'] = (bool)$form_data['must_have_picture'];
		}

		// must_like_my_gender
		if (array_key_exists('must_like_my_gender', $form_data)) {
			$db_row['must_like_my_gender'] = (bool)$form_data['must_like_my_gender'];
		}

		// newer_than_days
		if (array_key_exists('newer_than_days', $form_data)) {
			$db_row['newer_than_days'] = (function(int $newer_than_days): ?int {
				if ($newer_than_days < 1) {
					return null;
				}
				return min(999, $newer_than_days);
			})(intval($form_data['newer_than_days']));
		}

		// logged_in_within_days
		if (array_key_exists('logged_in_within_days', $form_data)) {
			$db_row['logged_in_within_days'] = (function(int $logged_in_within_days): ?int {
				if ($logged_in_within_days < 1) {
					return null;
				}
				return min(999, $logged_in_within_days);
			})(intval($form_data['logged_in_within_days']));
		}

		$db_row_and_errors = ['error_messages'=>$error_messages, 'db_row'=>$db_row];
		return $db_row_and_errors;
	} // transformFormDataToDbRowFormat

	/**
	 * @param array $form_data
	 * @param string $event_synopsis
	 * @return int|array error messages or `search_criteria.search_criteria_id`
	 */
	public static function create (array $form_data, string $event_synopsis = '', bool $log_query = true) {
		['error_messages'=>$error_messages, 'db_row'=>$db_row] = static::transformFormDataToDbRowFormat($form_data);
		if ($error_messages) {
			return $error_messages;
		}
		return parent::create($db_row, $event_synopsis);
	} // create


	// returns array of error messages on error
	public function update (array $form_data, string $event_synopsis = ''): ?array {
		['error_messages'=>$error_messages, 'db_row'=>$db_row] = static::transformFormDataToDbRowFormat($form_data, $this);
		if ($error_messages) {
			return $error_messages;
		}
		parent::update($db_row, $event_synopsis);
		return null;
	} // update


	public function getSearchCriteria (array $desired_fields): array {
		$searchCriteriaFinder = new SearchCriteriaFinder;
		$searchCriteriaFinder->setSearchCriteriaId($this->getId());
		$result = $searchCriteriaFinder->find($desired_fields);
		$search_criteria = DB::getRow($result);
		return $search_criteria;
	} // getSearchCriteria


	public function getUserId (): int {
		return (int)$this->commonGet('user_id');
	} // getUserId


	public function getUserModel (): UserModel {
		if (!$this->userModel) {
			$this->userModel = new UserModel($this->getUserId());
		}
		return $this->userModel;
	} // getUserModel


	// excludes current user from search results
	public function getSearchResults (int $current_page = 1, int $page_size = 7): array {
		$userFinder = new UserFinder;
		$userFinder->setPageNumber($current_page);
		$userFinder->setPageSize($page_size);
		$userFinder->addExcludedUserIds([$this->getUserId()]);
		if ($this->commonGet('exclude_contacted')) {
			$contacted_users_ids = $this->getUserModel()->getPreviouslyContactedUsersIds();
			$userFinder->addExcludedUserIds($contacted_users_ids);
		}
		if ($gender = $this->commonGet('gender')) {
			$userFinder->setGender($gender);
		}
		if ($mbti_types_string = $this->commonGet('mbti_types')) {
			$mbti_types_array = explode(',', $mbti_types_string);
			$userFinder->setMbtiTypes($mbti_types_array);
		}
		if ($country = $this->commonGet('country')) {
			$userFinder->setCountry($country);
		}
		if ($max_distance = $this->commonGet('max_distance')) {
			if ($latitude = $this->getUserModel()->getLatitude() and $longitude = $this->getUserModel()->getLongitude()) {
				$miles_per_latitude  = 69;
				$latitude_offset = $max_distance / $miles_per_latitude;
				$miles_per_longitude = $miles_per_latitude * cos(deg2rad($latitude));
				$longitude_offset = $max_distance / $miles_per_longitude;
				$min_latitude = $latitude - $latitude_offset;
				$max_latitude = $latitude + $latitude_offset;
				$min_longitude = $longitude - $longitude_offset;
				$max_longitude = $longitude + $longitude_offset;
				$userFinder->setCoordinatesRange($min_latitude, $max_latitude, $min_longitude, $max_longitude);
			}
		}
		if ($min_age = $this->commonGet('min_age')) {
			$userFinder->setMinAge($min_age);
		}
		if ($max_age = $this->commonGet('max_age')) {
			$userFinder->setMaxAge($max_age);
		}
		if ($this->commonGet('must_have_description')) {
			$userFinder->setMustHaveDescription();
		}
		if ($this->commonGet('must_have_picture')) {
			$userFinder->setMustHavePicture();
		}
		if ($this->commonGet('must_like_my_gender')) {
			$userFinder->setMustLikeGender( $this->getUserModel()->getGender() );
		}
		if ($newer_than_days = $this->commonGet('newer_than_days')) {
			$userFinder->setNewerThanDays($newer_than_days ?: null);
		}
		if ($logged_in_within_days = $this->commonGet('logged_in_within_days')) {
			$userFinder->setLoggedInWithinDays($logged_in_within_days);
		}
		if ($this->commonGet('match_shared_negatives')) {
			$userFinder->setMatchSharedNegatives();
		}
		$userFinder->setSortByMatchWithUserId($this->getUserId());
		$desired_fields = ['user_id', 'username', 'primary_thumbnail_width', 'primary_thumbnail_height', 'primary_thumbnail_rotate_angle', 'description', 'thumbnail_url', 'match_score'];
		$search_results_array = $userFinder->getSearchResults($desired_fields);
		return $search_results_array;
	} // getSearchResults
} // SearchCriteriaModel

