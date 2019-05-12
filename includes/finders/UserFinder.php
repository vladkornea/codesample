<?php

require_once 'BaseFinder.php';

interface UserFinderInterface extends BaseFinderInterface {
	function addExcludedUserIds (array $user_ids): void;
	function find (): mysqli_result;
	function getRecentlyCreatedUsers (int $current_page = 1, int $page_size = 100): mysqli_result;
	function getSearchResults (array $desired_fields): array;
	function includeUsuallyExcludedUsers (): void;
	function setLoggedInWithinDays (int $days): void;
	function setExcludeFrequentlyBouncingDomains (): void;
	function setCountry (string $country): void;
	function setGender (string $gender): void;
	function setIsIgnorantOfNewSite (): void;
	function setIsNotBlockedByUserId (int $user_id): void;
	function setSpammersOnly (): void;
	function setMatchSharedNegatives (): void;
	function setMaxAge (int $max_age): void;
	function setMbtiTypes (array $mbti_types): void;
	function setMinAge (int $min_age): void;
	function setMustHaveDescription (): void;
	function setMustHavePicture (): void;
	function setMustLikeGender (string $gender): void;
	function setNewerThanDays (int $days): void;
	function setSortStalestFirst (): void;
	function setUserId (int $user_id): void;
	static function getCountOfUsersRegistered ($time_string_or_timestamp = 'yesterday'): int;
	static function getCountriesWithUsers (): array;
	static function getCountryStatistics (): array;
	static function getIdFromEmail (string $email): ?int;
	static function getIdFromVerificationCode (string $verification_code): ?int;
	static function getSomeoneIgnorantOfNewSite (): ?int;
	static function getSpammers (int $page_number = 1, int $page_size = 0): array;
	static function getSuspectedSpammers (): array;
	static function getTypeDistribution (): array;
	static function getUserSummary (array $user_data): string;
} // UserFinderInterface

class UserFinder extends BaseFinder implements UserFinderInterface {
	use UserTraits;

	/* @see addExcludedUserIds() */ protected $excludedUserIds = [];
	/* @see setGender() */ protected $gender;
	/* @see setMbtiTypes() */ protected $mbtiTypes;
	/* @see setMinAge() */ protected $minAge;
	/* @see setMaxAge() */ protected $maxAge;
	/* @see setMustHaveDescription() */protected $mustHaveDescription;
	/* @see setMustHavePicture() */ protected $mustHavePicture;
	/* @see setMustLikeGender() */ protected $mustLikeGender;
	/* @see setNewerThanDays() */ protected $newerThanDays;
	/* @see setUserId() */ protected $userId;
	/* @see setCoordinatesRange() */ protected $minLatitude, $maxLatitude, $minLongitude, $maxLongitude;
	/* @see setSortByMatchWithUserId() */ protected $sortByMatchWithUserId;
	/* @see setMatchSharedNegatives() */ protected $matchSharedNegatives;
	/* @see setIsNotBlockedByUserId() */ protected $isNotBlockedByUserId;
	/* @see setSpammersOnly() */ protected $spammersOnly = null;
	/* @see setSortStalestFirst() */ protected $sortStalestFirst = null;
	/* @see setIsIgnorantOfNewSite() */ protected $isIgnorantOfNewSite;
	/* @see setExcludeFrequentlyBouncingDomains() */ protected $excludedDomains;
	/* @see setLoggedInWithinDays() */ protected $loggedInWithinDays;
	/* @see setCountry() */ protected $country;
	/* @see includeUsuallyExcludedUsers() */ protected $includeUsuallyExcludedUsers;
	protected $orderBy = null;


	protected static function getAdHocFields (): array {
		$adhoc_fields = [];
		$adhoc_fields['username']    = 'users.username';
		$adhoc_fields['last_visit']  = 'date_format(users.last_visit, "%M %Y")';
		$adhoc_fields['birth_day']   = 'day(' .static::$tableName .'.birth_date)';
		$adhoc_fields['birth_month'] = 'month(' .static::$tableName .'.birth_date)';
		$adhoc_fields['birth_year']  = 'year(' .static::$tableName .'.birth_date)';
		$adhoc_fields['match_score'] = 'weights.match_score';
		$adhoc_fields['country_name'] = 'countries.country_name';
		return $adhoc_fields;
	} // getAdHocFields


	protected static function getUsuallyExcludedUsersConditions (): array {
		return [
			 'users.deactivated = false'
			,'users.deleted_by_admin = false'
			,'(users.email_bouncing = "ok" or users.email_bouncing is null)'
			,'users.verified_email is not null'
			,'users.spammer = false'
		];
	} // getUsuallyExcludedUsersConditions


	public function find (array $resource_fields = ['birth_date', 'birth_day', 'birth_month', 'birth_year', 'body_type', 'city', 'country', 'country_namme', 'deactivated', 'gender', 'have_children', 'height_in_in', 'inserted', 'last_visit', 'latitude', 'longitude', 'lover_described', 'mbti_type', 'orientation', 'photo_order', 'primary_thumbnail_height', 'primary_thumbnail_width', 'self_described', 'share_keywords', 'state', 'updated', 'user_id', 'username', 'virtrades', 'want_children', 'weight_in_kg', 'would_relocate', 'zip_code']): mysqli_result {
		$valid_desired_fields = $this->getValidDesiredFields($resource_fields ?: [static::$primaryKeyName]);

		$from = static::$tableName;
		if (in_array('country_name', $resource_fields)) {
			$from .= "\nleft join countries on countries.country_code = users.country\n";
		}

		$where = [];
		if ($this->excludedUserIds) {
			$where[] = DB::where(['users.user_id' => DB::not($this->excludedUserIds)]);
		}
		if ($this->gender) {
			$where[] = DB::where(['users.gender' => $this->gender]);
		}
		if (!$this->includeUsuallyExcludedUsers) {
			$where = array_merge($where, static::getUsuallyExcludedUsersConditions());
		}
		if ($this->spammersOnly) {
			$where[] = 'users.spammer = true';
		}
		if ($this->isNotBlockedByUserId) {
			$userModel = new UserModel($this->isNotBlockedByUserId);
			$blocked_user_ids = $userModel->getBlockedUsers();
			$reported_user_ids = $userModel->getReportedUsers();
			$excluded_user_ids = array_merge($blocked_user_ids, $reported_user_ids);
			$where[] = DB::where(['users.user_id' => DB::not($excluded_user_ids)]);
		}
		if ($this->mbtiTypes) {
			$where[] = DB::where(['users.mbti_type' => $this->mbtiTypes]);
		}
		if ($this->minAge) {
			$min_date_of_birth = DB::datetime("-$this->minAge years", 'date');
			$where[] = "users.birth_date <= '$min_date_of_birth'";
		}
		if ($this->maxAge) {
			$years_to_subtract = $this->maxAge + 1;
			$max_date_of_birth = DB::datetime("-$years_to_subtract years", 'date');
			$where[] = "users.birth_date > '$max_date_of_birth'";
		}
		if ($this->mustHaveDescription) {
			$where[] = "users.self_described is not null and users.self_described != ''";
		}
		if ($this->mustHavePicture) {
			$where[] = "users.photo_order != ''";
		}
		if ($this->mustLikeGender) {
			$gender = $this->mustLikeGender == 'male' ? 'male' : 'female';
			$other_gender = $this->mustLikeGender == 'male' ? 'female' : 'male';
			$where[] = "((users.gender='$other_gender' and users.orientation!='gay') or (users.gender='$gender' and users.orientation!='straight'))";
		}
		if ($this->newerThanDays) {
			$datetime = DB::datetime("-$this->newerThanDays days");
			$where[] = "users.inserted >= '$datetime'";
		}
		if ($this->loggedInWithinDays) {
			$datetime = DB::datetime("-$this->loggedInWithinDays days");
			$where[] = "users.last_visit >= '$datetime'";
		}
		if ($this->userId) {
			$where[] = DB::where(['users.user_id' => $this->userId]);
		}
		if ($this->minLatitude) {
			$condition = "users.latitude > $this->minLatitude and users.latitude < $this->maxLatitude and users.longitude > $this->minLongitude and users.longitude < $this->maxLongitude";
			$where[] = $condition;
		}
		if ($this->isIgnorantOfNewSite) {
			$where[] = '0 = (select count(*) as times_emailed from emails where emails.to_email = users.verified_email)';
			$where[] = '0 = (select count(*) as times_logged_in from logins where logins.user_id = users.user_id)';
		}
		if ($this->excludedDomains) {
			foreach ($this->excludedDomains as $domain) {
				$where[] = "verified_email not like '%@".DB::escape($domain)."'";
			}
		}
		if ($this->country) {
			$where[] = DB::where(['users.country' => $this->country]);
		}

		$order_by = [];
		if ($this->sortByMatchWithUserId) {
			$ungrouped_keyword_weights_for_positives = "
				select keyword, cast(weight as signed) as absolute_weight from positive_keywords pk where user_id = $this->sortByMatchWithUserId
				union all
				select keyword, (-1 * cast(weight as signed)) as absolute_weight from negative_keywords pk where user_id = $this->sortByMatchWithUserId";
			if ($this->matchSharedNegatives) {
				$ungrouped_keyword_weights_for_negatives = "
					select keyword, cast(weight as signed) as absolute_weight from positive_keywords pk where user_id = $this->sortByMatchWithUserId
					union all
					select keyword, (-1 * cast(weight as signed)) as absolute_weight from negative_keywords pk where user_id = $this->sortByMatchWithUserId";
			} else {
				$ungrouped_keyword_weights_for_negatives = "
					select keyword, cast(weight as signed) as absolute_weight from positive_keywords pk where user_id = $this->sortByMatchWithUserId";
			}
			$keyword_weights_subselect = "
				select user_id, match_score
				from (
					select weights.user_id, sum(weights.absolute_weight) as match_score from (
						select pk.user_id, kw.absolute_weight
						from ($ungrouped_keyword_weights_for_positives) as kw
						join positive_keywords pk on kw.keyword = pk.keyword
						union all
						select nk.user_id, kw.absolute_weight * -1
						from ($ungrouped_keyword_weights_for_negatives) as kw
						join negative_keywords nk on kw.keyword = nk.keyword
						union all
						select user_id, 0
						from users
					) as weights
					group by user_id
					order by match_score desc
				) as weights";
			$from .= "\njoin ($keyword_weights_subselect) as weights on weights.user_id = users.user_id";
			$order_by[] = 'match_score desc';
		}
		if ($this->sortStalestFirst) {
			$order_by[] = 'users.last_visit asc';
		} else {
			$order_by[] = 'users.last_visit desc';
		}
		$order_by = implode(',', $order_by);

		$select = self::getSelectClause($valid_desired_fields);
		$where_clause = $where ? 'where '.implode(' and ', $where) : '';
		$query = "select $select from $from $where_clause order by $order_by";
		$result = $this->query($query);
		return $result;
	} // find


	// this adds post hoc fields that ->find() doesn't fetch from the database, such as 'description' and 'thumbnail_url'
	public function getSearchResults (array $desired_fields = ['birth_date', 'birth_day', 'birth_month', 'birth_year', 'body_type', 'city', 'country', 'country_namme', 'deactivated', 'gender', 'have_children', 'height_in_in', 'inserted', 'last_visit', 'latitude', 'longitude', 'lover_described', 'mbti_type', 'orientation', 'photo_order', 'primary_thumbnail_height', 'primary_thumbnail_width', 'self_described', 'share_keywords', 'state', 'updated', 'user_id', 'username', 'virtrades', 'want_children', 'weight_in_kg', 'would_relocate', 'zip_code', 'description', 'thumbnail_url', 'positive_keywords', 'negative_keywords', 'email_bouncing']): array {
		$post_hoc_field_dependencies = [
			 'description' => ['birth_date', 'orientation', 'mbti_type', 'gender', 'city', 'state', 'country', 'country_name']
			,'thumbnail_url' => ['photo_order', 'user_id']
			,'positive_keywords' => []
			,'negative_keywords' => []
		];
		$original_desired_fields = $desired_fields;

		// add resource fields needed to create desired post-hoc fields
		foreach ($post_hoc_field_dependencies as $current_field => $dependencies) {
			foreach ($dependencies as $dependency) {
				if (!in_array($dependency, $desired_fields)) {
					$desired_fields[] = $dependency;
				}
			}
		}

		// extract resource fields from desired fields and perform the search
		$resource_fields = [];
		foreach ($desired_fields as $desired_field) {
			if (array_key_exists($desired_field, $post_hoc_field_dependencies)) {
				continue;
			}
			$resource_fields[] = $desired_field;
		}
		$search_result_resource = $this->find($resource_fields);

		// structure our findings
		$search_results_array = [];
		$search_results_array['total_pages']  = $this->getFoundPages();
		$search_results_array['current_page'] = $this->getCurrentPage();
		$search_results_array['total_users']  = $this->getFoundRows();
		$search_results_array['users']        = (function() use($search_result_resource, $desired_fields, $original_desired_fields): array {
			$found_users = []; // return value
			while ($loop_row = DB::getRow($search_result_resource)) {
				$user_id = $loop_row['user_id'];
				if (in_array('thumbnail_url', $desired_fields)) {
					if (empty($loop_row['photo_order'])) {
						$loop_row['thumbnail_url'] = null;
					} else {
						$thumbnail_id = explode(',', $loop_row['photo_order'])[0];
						$thumbnail_url = PROFILE_PHOTOS_REMOTE_DIR ."/$user_id/$thumbnail_id/thumbnail.jpeg";
						$loop_row['thumbnail_url'] = $thumbnail_url;
					}
				}
				if (in_array('description', $desired_fields)) {
					$loop_row['description'] = UserFinder::getUserSummary($loop_row);
				}
				$userModel = new UserModel($user_id);
				if (in_array('positive_keywords', $desired_fields)) {
					$loop_row['positive_keywords'] = $userModel->getPositiveKeywords();
				}
				if (in_array('negative_keywords', $desired_fields)) {
					$loop_row['negative_keywords'] = $userModel->getNegativeKeywords();
				}
				$pruned_row = [];
				foreach ($original_desired_fields as $field_name) {
					$pruned_row[$field_name] = $loop_row[$field_name];
				}
				$found_users[] = $pruned_row;
			}
			return $found_users;
		})();
		return $search_results_array;
	} // getSearchResults

	public static function getUserSummary (array $user_data): string {
		$user_summary = ''; // return value
		if (!empty($user_data['birth_date'])) {
			$age = get_age_from_birth_date($user_data['birth_date']);
			$user_summary .= "$age year old";
		}
		if (!empty($user_data['orientation'])) {
			$user_summary .= " {$user_data['orientation']}";
		}
		if (!empty($user_data['mbti_type'])) {
			$user_summary .= " {$user_data['mbti_type']}";
		}
		if (!empty($user_data['gender'])) {
			$gender = $user_data['gender'] == 'male' ? 'man' : ($user_data['gender'] == 'female' ? 'woman' : null);
			if ($gender) {
				$user_summary .= " $gender";
			}
		}
		$location = (function() use($user_data): string {
			$us_states = [
				'AK'=>'Alaska',
				'AL'=>'Alabama',
				'AR'=>'Arkansas',
				'AZ'=>'Arizona',
				'CA'=>'California',
				'CO'=>'Colorado',
				'CT'=>'Connecticut',
				'DE'=>'Delaware',
				'FL'=>'Florida',
				'GA'=>'Georgia',
				'HI'=>'Hawaii',
				'IA'=>'Iowa',
				'ID'=>'Idaho',
				'IL'=>'Illinois',
				'IN'=>'Indiana',
				'KS'=>'Kansas',
				'KY'=>'Kentucky',
				'LA'=>'Louisiana',
				'MA'=>'Massachusetts',
				'MD'=>'Maryland',
				'ME'=>'Maine',
				'MI'=>'Michigan',
				'MN'=>'Minnesota',
				'MO'=>'Missouri',
				'MS'=>'Mississippi',
				'MT'=>'Montana',
				'NC'=>'North Carolina',
				'ND'=>'North Dakota',
				'NE'=>'Nebraska',
				'NH'=>'New Hampshire',
				'NJ'=>'New Jersey',
				'NM'=>'New Mexico',
				'NV'=>'Nevada',
				'NY'=>'New York',
				'OH'=>'Ohio',
				'OK'=>'Oklahoma',
				'OR'=>'Oregon',
				'PA'=>'Pennsylvania',
				'PR'=>'Puerto Rico',
				'RI'=>'Rhode Island',
				'SC'=>'South Carolina',
				'SD'=>'South Dakota',
				'TN'=>'Tennessee',
				'TX'=>'Texas',
				'UT'=>'Utah',
				'VA'=>'Virginia',
				'VT'=>'Vermont',
				'WA'=>'Washington',
				'WI'=>'Wisconsin',
				'WV'=>'West Virginia',
				'WY'=>'Wyoming'
			];
			$location_chunks = [];
			$trim_charlist = ' -.,';
			$city = trim($user_data['city'] ?? '', $trim_charlist);
			$state = trim($user_data['state'] ?? '', $trim_charlist);
			$is_in_usa = strtoupper($user_data['country']) == 'US';
			if ($city) {
				if (strlen($city) > 2) {
					if ($city == strtolower($city) or $city == strtoupper($city)) {
						$city = ucwords(strtolower($city));
					}
				}
				$location_chunks[] = $city;
			}
			if ($is_in_usa) {
				$usa_state_name = $us_states[strtoupper($state)] ?? null;
				if ($usa_state_name) {
					$location_chunks[] = $usa_state_name;
				} else {
					if ($state) {
						$location_chunks[] = $state;
					} else {
						$location_chunks[] = 'United States';
					}
				}
			} else {
				if (!empty($user_data['country_name'])) {
					$location_chunks[] = $user_data['country_name'];
				} elseif (!empty($user_data['country'])) {
					$country_and_state = [];
					if ($state) {
						$country_and_state[] = $state;
					}
					$country_and_state[] = $user_data['country'];
					$location_chunks[] = implode(", ", $country_and_state);
				}
			}
			return implode(', ', $location_chunks);
		})();
		if ($location) {
			$user_summary .= " in $location";
		}
		return trim($user_summary);
	} // getUserSummary

	public function getRecentlyCreatedUsers (int $current_page = 1, int $page_size = 100): mysqli_result {
		$this->setPageSize($page_size);
		$this->setPageNumber($current_page);
		$this->orderBy = 'inserted desc';
		return $this->find();
	} // getRecentlyCreatedUsers


	public static function getIdFromVerificationCode (string $verification_code): ?int {
		return DB::getCell(
			'select ' .static::$primaryKeyName .' from ' .static::$tableName .' where ' .DB::where(['verification_code' => $verification_code])
		);
	} // getIdFromVerificationCode


	public static function getIdFromEmail (string $email_address): ?int {
		$user_id = DB::getCell('select ' .static::$primaryKeyName .' from ' .static::$tableName .' where ' .DB::where(['verified_email' => $email_address]));
		if (!$user_id) {
			$user_id = DB::getCell('select ' .static::$primaryKeyName .' from ' .static::$tableName .' where ' .DB::where(['unverified_email' => $email_address]));
		}
		return $user_id;
	} // getIdFromEmail


	public static function getIdFromUsername (string $username): ?int {
		$user_id = DB::getCell('select ' .static::$primaryKeyName .' from ' .static::$tableName .' where ' .DB::where(['username' => $username]));
		return $user_id;
	} // getIdFromUsername


	public static function getUsernameFromId (int $user_id): ?string {
		return DB::getCell(DB::select(static::$tableName, 'username', ['user_id' => $user_id]));
	} // getUsernameFromId


	/**
	 * @param string $username_or_email
	 * @return int|null user_id
	 */
	public static function getIdFromUsernameOrEmail ($username_or_email) {
		$is_email = strpos($username_or_email, '@') !== false ? true : false;
		if ($is_email) {
			$email = $username_or_email;
			$user_id = UserFinder::getIdFromEmail($email);
		} else {
			$username = $username_or_email;
			$user_id = UserFinder::getIdFromUsername($username);
		}
		return $user_id;
	} // getIdFromUsernameOrEmail


	public static function getSpammers (int $page_number = 1, int $page_size = 0): array {
		$userFinder = new UserFinder;
		$userFinder->setSpammersOnly();
		$userFinder->setPageNumber($page_number);
		$userFinder->setPageSize($page_size);
		$desired_fields = ['user_id', 'username'];
		$result_resource = $userFinder->find($desired_fields);
		$result_array = DB::getTable($result_resource);
		return $result_array;
	} // getSpammers


	public static function getSuspectedSpammers (): array {
		$query = '
			select username, from_user_id, message_text, count(*) total
			from user_messages join users on users.user_id =  user_messages.from_user_id
			where message_text like \'%@%\' and users.spammer = false
			group by username, from_user_id, message_text
			having total >= 20
			order by total desc';
		$result_resource = DB::query($query);
		return DB::getTable($result_resource);
	} // getSuspectedSpammers


	public function setSpammersOnly (): void {
		$this->spammersOnly = true;
	} // setSpammersOnly


	public function addExcludedUserIds (array $user_ids): void {
		$this->excludedUserIds = array_merge($this->excludedUserIds, $user_ids);
	} // addExcludedUserIds


	public function setCoordinatesRange (float $min_latitude, float $max_latitude, float $min_longitude, float $max_longitude): void {
		$this->minLatitude  = $min_latitude;
		$this->maxLatitude  = $max_latitude;
		$this->minLongitude = $min_longitude;
		$this->maxLongitude = $max_longitude;
	} // setCoordinatesRange


	public function setGender (string $gender): void {
		$this->gender = $gender;
	} // setGender


	public function includeUsuallyExcludedUsers (): void {
		$this->includeUsuallyExcludedUsers = true;
	} // includeUsuallyExcludedUsers


	public function setIsNotBlockedByUserId (int $user_id): void {
		$this->isNotBlockedByUserId = $user_id;
	} // setIsNotBlockedByUserId


	public function setMatchSharedNegatives (): void {
		$this->matchSharedNegatives = true;
	} // setMatchSharedNegatives


	public function setMbtiTypes (array $mbti_types): void {
		$this->mbtiTypes = $mbti_types;
	} // setMbtiTypes


	public function setMinAge (int $min_age): void {
		$this->minAge = $min_age;
	} // setMinAge


	public function setMaxAge (int $max_age): void {
		$this->maxAge = $max_age;
	} // setMaxAge


	public function setMustHaveDescription (): void {
		$this->mustHaveDescription = true;
	} // setMustHaveDescription


	public function setMustHavePicture (): void {
		$this->mustHavePicture = true;
	} // setMustHavePicture


	public function setMustLikeGender (string $gender): void {
		if ($gender != 'male' and $gender != 'female') {
			trigger_error("Incorrect `gender` argument value: $gender", E_USER_WARNING);
			return;
		}
		$this->mustLikeGender = $gender;
	} // setMustLikeGender


	public function setNewerThanDays (int $days): void {
		$this->newerThanDays = $days;
	} // setNewerThanDays


	public function setLoggedInWithinDays (int $days): void {
		$this->loggedInWithinDays = $days;
	} // setLoggedInWithinDays


	public function setSortByMatchWithUserId (int $user_id): void {
		$this->sortByMatchWithUserId = $user_id;
	} // setSortByMatchWithUserId


	public function setUserId (int $user_id): void {
		$this->userId = (int)$user_id;
	} // setUserId


	// return like ['INTP'=>1231,'ENTP'=>1231]
	public static function getTypeDistribution (): array {
		$query = '
			select mbti_type, count(*) as total
			from ' .static::$tableName .'
			where deactivated = false and verified_email is not null and spammer = false
			group by mbti_type';
		$mbtitypes_resource = DB::query($query);
		$mbtitypes_array = DB::getKeyValueMap($mbtitypes_resource);
		return $mbtitypes_array;
	} // getTypeDistribution


	public static function getAgeDistribution (): array {
		$query = '
			select
				year(curdate()) - year(birth_date) - ( right(curdate(), 5) < right(birth_date, 5) ) as age,
				count(*) as age_count
			from users
			where deactivated = false and verified_email is not null and spammer = false and birth_date is not null
			group by age
			having age < 100
			order by age';
		$age_distribution = DB::getKeyValueMap($query);
		$highest_age = max(array_keys($age_distribution));
		for ($i = 0; $i <= $highest_age; $i++) {
			if (!isset($age_distribution[$i])) {
				$age_distribution[$i] = 0;
			}
		}
		ksort($age_distribution);
		return $age_distribution;
	} // getAgeDistribution


	public static function getAgeDistributionGoogleChartData (): ?array {
		return null; // TODO UPDATE CHART API CALL https://developers.google.com/chart/image/ -> https://developers.google.com/chart/
		$data = ['width'=>400, 'height'=>196];
		$age_distribution_chart_image_local_relative_path = '/images/age-distribution-chart.png';
		$local_image_location = $_SERVER['DOCUMENT_ROOT'] .$age_distribution_chart_image_local_relative_path;
		if (file_exists($local_image_location)) {
			$last_modified = filemtime($local_image_location);
			$expiration_timestamp = strtotime('-1 day');
			$cached_image_expired = $last_modified < $expiration_timestamp;
			if (!$cached_image_expired) {
				$data['chart_url'] = $age_distribution_chart_image_local_relative_path;
				return $data;
			}
		}

		$age_distribution = static::getAgeDistribution();
		$highest_age = max(array_keys($age_distribution));
		$highest_age_count = max($age_distribution);
		$http_query = [];
//		$http_query['chtt'] = 'Age Distribution'; // chart title
		$http_query['chtt'] = ''; // chart title
		$http_query['chts'] = '000000,13';        // chart title color and font size
		$http_query['chf']  = 'bg,s,FFFACD';      // background color
//		$http_query['chf']  = 'bg,s,FFFFFF';      // background color
		$http_query['cht']  = 'bvs';              // chart type = bar vertical stacked
		$http_query['chco'] = '4169E1';           // chart color
		$http_query['chs']  = '400x196';          // chart size
		$http_query['chbh'] = '3,0';              // bar width = 3px, bar spacing = 0px
		$http_query['chxt'] = 'x,y,x,y';              // show x and y axes
		$http_query['chds'] = "0,$highest_age_count"; // chart minimum and maximum values (google calls it "scale", but what it actually does is truncate any values outside of this range, and it does not affect the labels)
		$http_query['chxr'] = "0,0,$highest_age,10|1,0,$highest_age_count,100"; // http://code.google.com/apis/chart/docs/gallery/bar_charts.html#axis_range
		$http_query['chxl'] = '2:|Age|3:|Members';
		$http_query['chxp'] = '2,50|3,50';
		$http_query['chd']  = 't:' . implode(',', $age_distribution); // data
		$google_chart_url = "http://chart.apis.google.com/chart?" . http_build_query($http_query);
		copy($google_chart_url, $local_image_location);
		$data['chart_url'] = $age_distribution_chart_image_local_relative_path;
		return $data;
	} // getAgeDistributionGoogleChartData

	public static function getCountOfUsersRegistered ($time_string_or_timestamp = 'yesterday'): int {
		$when_timestamp = is_numeric($time_string_or_timestamp) ? $time_string_or_timestamp : strtotime($time_string_or_timestamp);
		$when_date = date('Y-m-d', $when_timestamp);
		$from_datetime = "$when_date 00:00:00";
		$to_datetime = "$when_date 23:59:59";
		$count_of_users_registered = DB::getCell("select count(*) as users_registered from users where inserted>='$from_datetime' and inserted<='$to_datetime'");
		return $count_of_users_registered;
	} // getCountOfUsersRegistered

	public function setSortStalestFirst (): void {
		$this->sortStalestFirst = true;
	} // setSortStalestFirst

	public function setIsIgnorantOfNewSite (): void {
		$this->isIgnorantOfNewSite = true;
	} // setIsIgnorantOfNewSite

	public function setExcludeFrequentlyBouncingDomains (): void {
		$this->excludedDomains = ['aol.com', 'gmail.com', 'hotmail.com', 'yahoo.com'];
	} // setExcludeFrequentlyBouncingDomains

	public static function getSomeoneIgnorantOfNewSite (): ?int {
		$userFinder = new static;
		$userFinder->setIsIgnorantOfNewSite();
		$userFinder->setPageSize(1);
//		$userFinder->setSortStalestFirst(); // sending to stalest first sent us over the SES limit on bounces
//		$userFinder->setExcludeFrequentlyBouncingDomains();
		$userFinder->setMustHaveDescription();
		$userFinder->setMustHavePicture();
		$result = $userFinder->find(['user_id']);
		$user_id = DB::getCell($result);
		return $user_id;
	} // getSomeoneIgnorantOfNewSite

	public function setCountry (string $country): void {
		$this->country = strtoupper(trim($country));
	} // setCountry

	public static function getCountryStatistics (): array {
		$where = implode(' and ', static::getUsuallyExcludedUsersConditions());
		$query = "
			select format(user_count, 0) as user_count, country_name from (
				select count(*) as user_count, countries.country_name
				from users
					join countries on countries.country_code = users.country
				where $where
				group by country
				order by user_count desc, countries.country_name
			) as country_statistics";
		return DB::getTable($query);
	} // getCountryStatistics

	public static function getCountriesWithUsers (): array {
		$where = implode(' and ', static::getUsuallyExcludedUsersConditions());
		$query = "
			select distinct countries.country_code as code, countries.country_name as name
			from users
				join countries on countries.country_code = users.country
			where $where
			order by countries.country_name";
		return DB::getTable($query);
	} // getCountriesWithUsers
} // UserFinder

