<?php

require_once 'LoggedModel.php';

interface UserModelInterface extends LoggedModelInterface {
	function activate (): ?array;
	function blockUser (int $user_id): void;
	function deactivate (): ?array;
	function getAllKeywordsWithSignedWeights (): array;
	function getBlockedUsers (): array;
	function getBlockedUsersData (): array;
	function getContactedUsersData (): array;
	function getConversationWith (int $user_id): ?array;
	function getCountryCode (): string;
	function getEmail (): ?string;
	function getEmailBouncing (): ?string;
	function getGender (): string;
	function getIsBlockedByUserId (int $user_id): bool;
	function getIsBouncing (): bool;
	function getIsDeactivated (): bool;
	function getIsDeletedByAdmin (): bool;
	function getIsPasswordValid (string $password): bool;
	function getIsReportedBy (int $user_id): bool;
	function getIsSpammer (): bool;
	function getKeywordsAsOther (UserModel $theirUserModel): array;
	function getLatitude (): ?float;
	function getLongitude (): ?float;
	function getNegativeKeywords (): array;
	function getPhotoCarouselData (): array;
	function getPhotoOrder (): string;
	function getPositiveKeywords (): array;
	function getPreviouslyContactedUsersIds (): array;
	function getReportedUsers (): array;
	function getReportedUsersData (): array;
	function getSearchCriteria (array $desired_fields): array;
	function getSearchCriteriaModel (): SearchCriteriaModel;
	function getUnverifiedEmail (): ?string;
	function getUserSummary (): string;
	function getUsername (): string;
	function getVerifiedEmail (): ?string;
	function getWhenNextSendAllowed (): ?string;
	function getWhetherCanSendMessages (): bool;
	function getWhetherMarkedOurEmailAsSpam (): bool;
	function getWhetherPreviousContactExistsWith (int $their_user_id): bool;
	function getWhyCannotViewUser (int $user_id): ?string;
	function getWhyCannotViewUsers (): ?string;
	function logIn (string $login_method, bool $remember_me = false): ?array;
	function reportUser (int $user_id): void;
	function sendEmailVerificationEmail (): void;
	function sendForgotPasswordEmail (): void;
	function setIsBouncing (bool $is_bouncing = true): void;
	function setIsSpammer (bool $whether = true): void;
	function setKeywords (string $positive_or_negative, array $keywords_with_weights): void;
	function setLastVisit (): void;
	function setPassword (string $password): ?array;
	function setPhotoOrder ($photo_order): ?array;
	function setSearchCriteria (array $form_data): ?array;
	function setUnverifiedEmail (string $email_address): ?array;
	function setUserComplained (): void;
	function setUsername (string $username): ?array;
	function setVerificationCode (string $verification_code): ?array;
	function unblockUser (int $user_id): void;
	function unreportUser (int $user_id): void;
	function verifyEmailAddress (string $verification_code): bool;
} // UserModelInterface

class UserModel extends LoggedModel implements UserModelInterface {
	use UserTraits;

	protected static $minPasswordLength = 6;
	protected static $maxUsernameLength = 20;
	protected static $minUsernameLength = 3; // too few can be confusing to other users

	/** @var int $searchCriteriaId search_criteria.search_criteria_id call ->getSearchCriteriaId() */
	protected $searchCriteriaId = null;
	/** @var SearchCriteriaModel $searchCriteriaModel call ->getSearchCriteriaModel() */
	protected $searchCriteriaModel = null;

	const MAX_KEYWORD_WEIGHT = 250;

	public static function create (array $form_data, string $event_synopsis = ''): array {
		$next_allowed_account_creation_time_of_ip_address = LoginFinder::getNextAllowedAccountCreationTimeOfIpAddress($_SERVER['REMOTE_ADDR']);
		if ($next_allowed_account_creation_time_of_ip_address) {
			$error_messages = ['permission' => "Cannot create another account from this IP address until $next_allowed_account_creation_time_of_ip_address."];
			return ['user_id'=>null, 'error_messages'=>$error_messages];
		}
		['error_messages'=>$error_messages, 'row_data'=>$row_data] = static::getRowDataAndErrorMessagesFromFormData($form_data);
		if ($error_messages) {
			return ['user_id'=>null, 'error_messages'=>$error_messages];
		}
		$user_id = parent::create($row_data, $event_synopsis ?: "User {$row_data['unverified_email']} inserted.");
		return ['user_id'=>$user_id, 'error_messages'=>$error_messages];
	} // create


	public function update (array $form_data, string $event_synopsis = ''): ?array {
		['error_messages'=>$error_messages, 'row_data'=>$row_data] = static::getRowDataAndErrorMessagesFromFormData($form_data, $this);
		if ($error_messages) {
			return $error_messages;
		}
		parent::update($row_data, $event_synopsis ?: "User updated.");
		return null;
	} // update


	protected static function getRowDataAndErrorMessagesFromFormData (array $form_data, UserModel $userModel = null): array {
		$row_data = [];
		$error_messages = [];
		$create_or_update = $userModel ? 'update' : 'create';

		{ // user_id
			if (array_key_exists('user_id', $form_data)) {
				throw new Exception("Cannot set user_id directly.");
			}
		}

		{ // username
			if (!array_key_exists('username', $form_data)) {
				if ($create_or_update == 'create') {
					$error_messages['username'] = "Username is required.";
				}
			} else {
				$row_data['username'] = trim($form_data['username']);
				if (empty($row_data['username'])) {
					$error_messages['username'] = "Username cannot be blank.";
				} else {
					$invalid_character_regex_pattern = '/[^a-zA-Z0-9_]/';
					$username_has_invalid_characters = preg_match($invalid_character_regex_pattern, $row_data['username']);
					if ($username_has_invalid_characters) {
						$error_messages['username'] = "Username can only contain letters, numbers, and underscores.";
					} else {
						$username_is_too_long = strlen($row_data['username']) > static::$maxUsernameLength;
						if ($username_is_too_long) {
							$error_messages['username'] = "Username cannot have more than " .static::$maxUsernameLength ." characters.";
						} else {
							$username_is_too_short = strlen($row_data['username']) < static::$minUsernameLength;;
							if ($username_is_too_short) {
								$error_messages['username'] = "Username cannot have less than " .static::$minUsernameLength ." characters.";
							} else {
								$username_starts_or_ends_with_underline = $row_data['username'][0] == '_' or $row_data['username'][-1] == '_';
								if ($username_starts_or_ends_with_underline) {
									$error_messages['username'] = "Username cannot begin or end with an underline.";
								} else {
									$username_has_adjacent_underlines = strpos($row_data['username'], '__') !== false;
									if ($username_has_adjacent_underlines) {
										$error_messages['username'] = "Username cannot have multiple adjacent underlines.";
									} else {
										$username_user_id = UserFinder::getIdFromUsername($row_data['username']);
										if ($username_user_id) {
											if ($create_or_update == 'create') {
												$error_messages['username'] = "Username already taken.";
											} else {
												$is_same_user = $userModel->getId() == $username_user_id;
												if (!$is_same_user) {
													$error_messages['username'] = "Username already taken.";
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		} // username

		{ // verified_email
			if (array_key_exists('verified_email', $form_data)) {
				if ($create_or_update == 'create') {
					throw new InvalidArgumentException("Wrong to set `verified_email` in create() method.");
				}
				$row_data['verified_email'] = trim($form_data['verified_email']) ?: null;
				if (empty($row_data['verified_email'])) {
					$error_messages['verified_email'] = "Empty `verified_email`.";
				} else {
					if (!Email::isAddressValid($row_data['verified_email'])) {
						$error_messages['verified_email'] = "Invalid `verified_email`: {$row_data['verified_email']}";
					} else {
						$user_id = UserFinder::getIdFromEmail($row_data['verified_email']);
						if ($user_id) {
							if ($create_or_update == 'create' or $userModel->getId() != $user_id) {
								$error_message = "Verified email address is already being used.";
								$error_messages['verified_email'] = $error_message;
								trigger_error($error_message, E_USER_WARNING);
							}
						}
					}
				}
			}
		}

		{ // unverified_email
			if (!array_key_exists('unverified_email', $form_data)) {
				if ($create_or_update == 'create') {
					$error_messages['unverified_email'] = "Email address is required.";
				}
			} else {
				$row_data['unverified_email'] = trim($form_data['unverified_email']) ?: null;
				if (empty($row_data['unverified_email'])) {
					$error_messages['unverified_email'] = "Email address cannot be blank.";
				} else {
					if (!Email::isAddressValid($row_data['unverified_email'])) {
						$error_messages['unverified_email'] = "Invalid email address: {$row_data['unverified_email']}";
					} else {
						if ($create_or_update == 'create') {
							$user_id = UserFinder::getIdFromEmail($row_data['unverified_email']);
							if ($user_id) {
								$error_messages['unverified_email'] = "Account with this email address already exists.";
							}
						}
						if ($create_or_update == 'update') {
							$user_is_reverting_to_current_verified_email_address = strtolower($userModel->getVerifiedEmail()) == strtolower($row_data['unverified_email']) ? true : false;
							if ($user_is_reverting_to_current_verified_email_address) {
								$row_data['unverified_email'] = null;
								$row_data['verification_code'] = null; // Prevent logging in using links sent to old addresses.
							} else {
								$user_id = UserFinder::getIdFromEmail($row_data['unverified_email']);
								if ($user_id and ($user_id != $userModel->getId())) {
									$error_messages['unverified_email'] = "Account with this email address already exists.";
								}
							}
						}
					}
				}
			}
		} // unverified_email

		{ // email_bouncing
			if (array_key_exists('email_bouncing', $form_data)) {
				$row_data['email_bouncing'] = $form_data['email_bouncing'] ?: null;
			}
		}

		{ // spammer
			if (array_key_exists('spammer', $form_data)) {
				$row_data['spammer'] = (bool)$form_data['spammer'];
			}
		}

		{ // last_visit
			if (array_key_exists('last_visit', $form_data)) {
				$row_data['last_visit'] = $form_data['last_visit'] ?: null;
			}
		}

		{ // verification_code
			if (array_key_exists('verification_code', $form_data)) {
				$row_data['verification_code'] = trim($form_data['verification_code']) ?: null;
			}
		}

		{ // password_hash and password
			if (array_key_exists('password_hash', $form_data)) {
				throw new InvalidArgumentException("Wrong to set `password_hash` directly; set `password` instead, I'll make a hash out of it.");
			}
			if (!array_key_exists('password', $form_data)) {
				if ($create_or_update == 'create') {
					$error_messages['password'] = "Password is required.";
				}
			} else {
				if (empty($form_data['password'])) {
					$error_messages['password'] = "Password cannot be blank.";
				} else {
					$password_is_too_short = strlen($form_data['password']) < static::$minPasswordLength;
					if ($password_is_too_short) {
						$error_messages['password'] = "Password must have at least " .static::$minPasswordLength ." characters.";
					} else {
						$row_data['password_hash'] = password_hash($form_data['password'], PASSWORD_DEFAULT);
					}
				}
			}
		} // password

		{ // mbti_type
			if (array_key_exists('mbti_type', $form_data)) {
				$row_data['mbti_type'] = trim($form_data['mbti_type']) ?: null;
				if (!$row_data['mbti_type']) {
					$error_messages['mbti_type'] = "Personality type is required.";
				}
			}
		}

		{ // gender
			if (!array_key_exists('gender', $form_data)) {
				if ($create_or_update == 'create') {
					$error_messages['gender'] = "Gender is required.";
				}
			} else {
				$row_data['gender'] = trim($form_data['gender']);
				if (!$row_data['gender']) {
					$error_messages['gender'] = "Gender is required.";
				} elseif (!in_array($row_data['gender'], ['male', 'female', 'other'])) {
					$error_messages['gender'] = "Unrecognized gender value: {$row_data['gender']}";
				}
			}
		}

		{ // orientation
			if (!array_key_exists('orientation', $form_data)) {
				if ($create_or_update == 'create') {
					$error_messages['orientation'] = "Sexual orientation is required.";
				}
			} else {
				$row_data['orientation'] = trim($form_data['orientation']) ?: null;
				if (!$row_data['orientation']) {
					$error_messages['orientation'] = "Sexual orientation is required.";
				} elseif (!in_array($row_data['orientation'], ['straight', 'gay', 'bi'])) {
					$error_messages['orientation'] = "Undefined `orientation` field value: {$row_data['orientation']}";
				}
			}
		}

		{ // birth_date
			$birthDatetime = null;
			if (array_key_exists('birth_date', $form_data)) {
				$birthDatetime = DateTime::createFromFormat('Y-m-d', $form_data['birth_date']);
			}
			$dob_provided_in_chunks = array_key_exists('birth_day', $form_data) or array_key_exists('birth_month', $form_data) or array_key_exists('birth_year', $form_data);
			if ($dob_provided_in_chunks) {
				$alleged_date_string = @"{$form_data['birth_year']}-{$form_data['birth_month']}-{$form_data['birth_day']}";
				$birthDatetime = DateTime::createFromFormat('Y-n-j', $alleged_date_string);
			}
			if (!$birthDatetime) {
				if ($birthDatetime === false) {
					$error_messages['birth_date'] = "Invalid date of birth.";
				} elseif ($create_or_update == 'create') {
					$error_messages['birth_date'] = "Date of birth is required.";
				}
			}
			if ($birthDatetime) {
				$row_data['birth_date'] = $birthDatetime->format('Y-m-d');
			}
			unset($birthDatetime);
		}

		{ // body_type
			if (array_key_exists('body_type', $form_data)) {
				$row_data['body_type'] = trim($form_data['body_type']) ?: null;
			}
		}

		{ // height_in_in
			if (array_key_exists('height_in_in', $form_data)) {
				$row_data['height_in_in'] = trim($form_data['height_in_in']) ?: 0;
			}
		}

		{ // weight_in_kg
			if (array_key_exists('weight_in_kg', $form_data)) {
				$row_data['weight_in_kg'] = trim($form_data['weight_in_kg']) ?: 0;
			}
		}

		{ // country
			if (!array_key_exists('country', $form_data)) {
				if ($create_or_update == 'create') {
					$error_messages['country'] = "Country is required.";
				}
			} else {
				$row_data['country'] = strtoupper(trim($form_data['country'] ?? ''));
				if (!$row_data['country']) {
					$error_messages['country'] = "Country is required.";
				} elseif (strlen($row_data['country']) != 2) {
					$error_messages['country'] = "Invalid country code.";
				}
			}
		}

		{ // city
			if (!array_key_exists('city', $form_data)) {
				if ($create_or_update == 'create') {
					$error_messages['city'] = "City is required.";
				}
			} else {
				$row_data['city'] = trim($form_data['city']) ?: '';
				if (!$row_data['city']) {
					$error_messages['city'] = "City is required.";
				}
			}
		}

		{ // state
			if (array_key_exists('state', $form_data)) {
				$row_data['state'] = trim($form_data['state']) ?: '';
			}
		}

		{ // zip_code
			if (array_key_exists('zip_code', $form_data)) {
				$row_data['zip_code'] = trim($form_data['zip_code']) ?: '';
				if (isset($row_data['country']) and $row_data['country'] == 'US') {
					if ($row_data['zip_code'] and strlen($row_data['zip_code']) != 5) {
						$error_messages['zip_code'] = "Zip code must have 5 digits.";
					}
				}
			}
		}

		{ // latitude
			if (array_key_exists('latitude', $form_data)) {
				$row_data['latitude'] = trim($form_data['latitude']) ?: null;
				if ($row_data['latitude'] and !is_numeric($row_data['latitude'])) {
					$error_messages['latitude'] = "Latitude must be numeric.";
				}
			}
		}

		{ // longitude
			if (array_key_exists('longitude', $form_data)) {
				$row_data['longitude'] = trim($form_data['longitude']) ?: null;
				if ($row_data['longitude'] and !is_numeric($row_data['longitude'])) {
					$error_messages['longitude'] = "Longitude must be numeric.";
				}
			}
		}

		{ // share_keywords
			if (array_key_exists('share_keywords', $form_data)) {
				$row_data['share_keywords'] = (bool)$form_data['share_keywords'];
			}
		}

		{ // have_children
			if (array_key_exists('have_children', $form_data)) {
				$row_data['have_children'] = $form_data['have_children'] ?: null;
			}
		}

		{ // want_children
			if (array_key_exists('want_children', $form_data)) {
				$row_data['want_children'] = $form_data['want_children'] ?: null;
			}
		}

		{ // would_relocate
			if (array_key_exists('would_relocate', $form_data)) {
				$row_data['would_relocate'] = $form_data['would_relocate'] ?: null;
			}
		}

		{ // photo_order
			if (array_key_exists('photo_order', $form_data)) {
				$row_data['photo_order'] = $form_data['photo_order'] ?: '';
			}
		}

		{ // primary_thumbnail_width
			if (array_key_exists('primary_thumbnail_width', $form_data) and array_key_exists('primary_thumbnail_height', $form_data)) {
				$row_data['primary_thumbnail_width'] = $form_data['primary_thumbnail_width'] ?: 0;
				$row_data['primary_thumbnail_height'] = $form_data['primary_thumbnail_height'] ?: 0;
			}
		}

		{ // self_described
			if (array_key_exists('self_described', $form_data)) {
				$row_data['self_described'] = $form_data['self_described'] ?: null;
			}
		}

		{ // lover_described
			if (array_key_exists('lover_described', $form_data)) {
				$row_data['lover_described'] = $form_data['lover_described'] ?: null;
			}
		}

		{ // virtrades
			if (array_key_exists('virtrades', $form_data)) {
				$row_data['virtrades'] = $form_data['virtrades'] ?: null;
			}
		}

		{ // deactivated
			if (array_key_exists('deactivated', $form_data)) {
				$row_data['deactivated'] = (bool)$form_data['deactivated'];
			}
		}

		{ // updated
			if (array_key_exists('updated', $form_data)) {
				$row_data['updated'] = $form_data['updated'];
			}
		}

		if ($error_messages) {
			$row_data = false;
		}
		return ['error_messages' => $error_messages, 'row_data' => $row_data];
	} // getRowDataAndErrorMessagesFromFormData


	public function blockUser (int $user_id): void {
		DB::insert('blocked_users', ['blocked_user_id' => $user_id, 'blocked_by_user_id' => $this->getId()], true);
	} // blockUser


	public function unblockUser (int $user_id): void {
		DB::query('delete from blocked_users where ' .DB::where(['blocked_user_id'=>$user_id, 'blocked_by_user_id'=>$this->getId()]));
	} // unblockUser


	public function reportUser (int $user_id): void {
		DB::insert('reported_users', ['reported_user_id' => $user_id, 'reported_by_user_id' => $this->getId()], true);
	} // reportUser


	public function unreportUser (int $user_id): void {
		DB::query('delete from reported_users where ' .DB::where(['reported_user_id'=>$user_id, 'reported_by_user_id'=>$this->getId()]));
	} // unreportUser


	public function logIn (string $login_method, bool $remember_me = false): ?array {
		return Session::logIn($this->getId(), $login_method, $remember_me);
	} // logIn


	public function sendEmailVerificationEmail (): void {
		$email_body = <<<EMAIL_TEXT
{$this->getUsername()},

Someone (presumably you) submitted the Create Account form at https://{$_SERVER['HTTP_HOST']}

To verify your email address, go to the following URL:

{$this->getEmailVerificationUrl()}

TypeTango Jungian Myers-Briggs/Keirsey Personality Theory Dating
EMAIL_TEXT;
		Email::sendEmailToClientViaAmazonSES([
			 'reply-to' => DEFAULT_REPLY_TO
			,'to'       => $this->getUnverifiedEmail()
			,'subject'  => "TypeTango Email Verification"
			,'text'     => $email_body
		]);
	} // sendEmailVerificationEmail


	public function sendForgotPasswordEmail (): void {
		$email_body = <<<EMAIL_TEXT
{$this->getUsername()},

Someone (presumably you) submitted the Forgot Password form at https://{$_SERVER['HTTP_HOST']}

To log in and set a new password, go to the following URL:

{$this->getForgotPasswordUrl()}

TypeTango Jungian Myers-Briggs/Keirsey Personality Theory Dating
EMAIL_TEXT;
		Email::sendEmailToClientViaAmazonSES([
			 'reply-to' => DEFAULT_REPLY_TO
			,'to'       => $this->getEmail()
			,'subject'  => "TypeTango Forgot Password"
			,'text'     => $email_body
		]);
	} // sendForgotPasswordEmail


	/**
	 * @param string $verification_code
	 * @return bool success
	 */
	public function verifyEmailAddress (string $verification_code): bool {
		$query = '
			update users
			set
				verified_email = unverified_email
				,unverified_email = null
			where
				verification_code = "' .DB::escape($verification_code) .'"
				and unverified_email is not null';
		$affected_rows = DB::getAffectedRows($query);
		$success = $affected_rows ? true : false;
		return $success;
	} // verifyEmailAddress


	public function getIsPasswordValid (string $unhashed_password): bool {
		$db_password_hash = $this->commonGet('password_hash');
		if (!password_verify($unhashed_password, $db_password_hash)) {
			$legacy_password_hash = md5($unhashed_password);
			if ($legacy_password_hash != $db_password_hash) {
				return false;
			}
		}
		if (password_needs_rehash($db_password_hash, PASSWORD_DEFAULT)) {
			$new_password_hash = password_hash($unhashed_password, PASSWORD_DEFAULT);
			$event_synopsis = "{$this->getEmail()}: Password hash reset.";
			parent::update(['password_hash' => $new_password_hash], $event_synopsis);
		}
		return true;
	} // getIsPasswordValid

	// special setters below

	public function setIsBouncing (bool $is_bouncing = true): void {
		if ($is_bouncing) {
			$event_synopsis = "{$this->getEmail()}: User status changed to bouncing.";
			$this->update(['email_bouncing' => 'bounced'], $event_synopsis);
			EmailModel::deleteQueuedEmails($this->getEmail());
		}
		if (!$is_bouncing) {
			$event_synopsis = "{$this->getEmail()}: User status changed to okay.";
			$this->update(['email_bouncing' => 'ok'], $event_synopsis);
		}
	} // setIsBouncing


	public function setIsSpammer (bool $whether = true): void {
		$this->update(['spammer' => $whether]);
	} // setIsSpammer


	public function setLastVisit (): void {
		$this->update(['last_visit' => DB::verbatim('now()')]);
	} // setLastVisit


	public function setUserComplained (): void {
		$event_synopsis = "{$this->getEmail()}: User status changed to complained.";
		$this->update(['email_bouncing' => 'complained'], $event_synopsis);
		EmailModel::deleteQueuedEmails($this->getEmail());
	} // setUserComplained

	// common setters below

	// returns array of error messages on error
	public function setPassword (string $password): ?array {
		return $this->update(['password' => $password]);
	} // setPassword


	// returns array of error messages on error
	public function setVerificationCode (string $verification_code = null): ?array {
		return $this->update(['verification_code' => $verification_code]);
	} // setVerificationCode


	// returns array of error messages on error
	public function setUnverifiedEmail (string $email_address): ?array {
		return $this->update(['unverified_email' => $email_address]);
	} // setUnverifiedEmail


	// returns array of error messages on error
	public function setUsername (string $username): ?array {
		return $this->update(['username' => $username]);
	} // setUsername

	// special getters below

	public function getConversationWith (int $user_id): ?array {
		$userMessageFinder = new UserMessageFinder;
		$userMessageFinder->setOurUserId($this->getId());
		$userMessageFinder->setTheirUserId($user_id);
		$result_resource = $userMessageFinder->find(['user_message_id', 'from_user_id', 'to_user_id', 'message_text', 'inserted']);
		return DB::getTable($result_resource);
	} // getConversationWith


	public function getEmail (): ?string {
		$email = $this->getVerifiedEmail();
		if (!$email) {
			$email = $this->getUnverifiedEmail();
		}
		return $email;
	} // getEmail

	public function getEmailBouncing (): ?string {
		return $this->commonGet('email_bouncing');
	} // getEmailBouncing

	protected function getEmailVerificationUrl (): string {
		$get_params = http_build_query([EMAIL_AUTH_PARAM_NAME => $this->getVerificationCode()]);
		return "https://{$_SERVER['HTTP_HOST']}/verify-email?$get_params";
	} // getEmailVerificationUrl


	protected function getForgotPasswordUrl (): string {
		$get_params = http_build_query([EMAIL_AUTH_PARAM_NAME => $this->getVerificationCode()]);
		return "https://{$_SERVER['HTTP_HOST']}/forgot-password?$get_params";
	} // getForgotPasswordUrl


	protected function getVerificationCode (bool $force_regenerate = false): string {
		$query = 'select verification_code from users where
			user_id = ' .(int)$this->getId() .' and updated > "' .DB::datetime('-3 month') .'"';
		$valid_code = DB::getCell($query);
		if ($valid_code and !$force_regenerate) {
			error_log("{$this->getEmail()}: Extended confirmation code lifespan.");
			$this->update(['updated' => DB::verbatim('now()')]);
			return $valid_code;
		}
		do {
			$verification_code = get_random_string(16, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');
			$is_duplicate = DB::getCell('select true from users where ' .DB::where(['verification_code' => $verification_code]));
		} while ($is_duplicate);
		$this->update(['verification_code' => $verification_code], false);
		return $verification_code;
	} // getVerificationCode


	/**
	 * If there is no `search_criteria` row for this user, this method will insert one and return
	 * its ID (throws a RuntimeException if this fails, since that should be impossible).
	 */
	protected function getSearchCriteriaId (): int {
		if (!$this->searchCriteriaId) {
			$this->searchCriteriaId = SearchCriteriaFinder::getSearchCriteriaIdFromUserId($this->getId());
			if (!$this->searchCriteriaId) {
				$this->searchCriteriaId = SearchCriteriaModel::create(['user_id' => $this->getId()]);
				if (!is_numeric($this->searchCriteriaId)) {
					$error_message = $this->searchCriteriaId;
					throw new RuntimeException($error_message);
				}
			}
		}
		return $this->searchCriteriaId;
	} // getSearchCriteriaId


	public function getSearchCriteriaModel (): SearchCriteriaModel {
		if (!isset($this->searchCriteriaModel)) {
			$search_criteria_id = $this->getSearchCriteriaId();
			if ($search_criteria_id) {
				$this->searchCriteriaModel = new SearchCriteriaModel($search_criteria_id);
			}
		}
		return $this->searchCriteriaModel;
	} // getSearchCriteriaModel


	// returns array of error messages on error
	public function setSearchCriteria (array $form_data): ?array {
		$search_criteria_id = $this->getSearchCriteriaId();
		if (!$search_criteria_id) {
			$search_criteria = $form_data;
			$search_criteria['user_id'] = $this->getId();
			$search_criteria_id = SearchCriteriaModel::create($search_criteria);
			$error_messages = is_numeric($search_criteria_id) ? null : $search_criteria_id;
			return $error_messages ?: null;
		}
		$searchCriteriaModel = new SearchCriteriaModel($search_criteria_id);
		return $searchCriteriaModel->update($form_data);
	} // setSearchCriteria


	public function getSearchCriteria (array $desired_fields = ['search_criteria_id', 'user_id', 'country', 'mbti_types', 'gender', 'min_age', 'max_age', 'max_distance', 'newer_than_days', 'match_shared_negatives', 'exclude_contacted', 'must_have_description', 'must_have_picture', 'must_like_my_gender', 'logged_in_within_days']): array {
		return $this->getSearchCriteriaModel()->getSearchCriteria($desired_fields);
	} // getSearchCriteria


	public function getPreviouslyContactedUsersIds (): array {
		$reported_users = $this->getReportedUsers();
		$blocked_users = $this->getBlockedUsers();
		$excluded_users = array_merge($blocked_users, $reported_users);
		$where = $excluded_users ? 'where ' .DB::where(['user_id' => DB::not($excluded_users)]) : '';
		$escaped_user_id = (int)$this->getId();
		$query = "
			select user_id, max(inserted) as latest_inserted from (
				select um.from_user_id as user_id, um.inserted
				from user_messages um join users u on u.user_id = um.from_user_id
				where um.to_user_id = $escaped_user_id and u.deactivated = false
					union
				select um.to_user_id as user_id, um.inserted
				from user_messages um join users u on u.user_id = um.to_user_id
				where um.from_user_id = $escaped_user_id and u.deactivated = false
			) as sent_messages
			$where
			group by user_id
			order by latest_inserted desc";
		$previously_contacted_users_ids = DB::getColumn($query);
		return $previously_contacted_users_ids;
	} // getPreviouslyContactedUsersIds


	// data needed for each contacted user: username, user_id, thumbnail_url, thumbnail_width, thumbnail_height
	public function getContactedUsersData (): array {
		$contacted_users_data = []; // return value
		foreach ($this->getPreviouslyContactedUsersIds() as $loop_user_id) {
			$userFinder = new UserFinder;
			$userFinder->setUserId($loop_user_id);
			['users' => $user_data] = $userFinder->getSearchResults(['username', 'user_id', 'thumbnail_url', 'primary_thumbnail_width', 'primary_thumbnail_height']);
			if (!$user_data) { // deactivated user
				continue;
			}
			$contacted_users_data[] = $user_data[0];
		}
		return $contacted_users_data;
	} // getContactedUsersData


	public function getBlockedUsersData (): array {
		$blocked_users_data = []; // return value
		foreach ($this->getBlockedUsers() as $loop_user_id) {
			$userFinder = new UserFinder;
			$userFinder->setUserId($loop_user_id);
			['users' => $user_data] = $userFinder->getSearchResults(['username', 'user_id', 'thumbnail_url', 'primary_thumbnail_width', 'primary_thumbnail_height']);
			if (!$user_data) { // deactivated user
				continue;
			}
			$blocked_users_data[] = $user_data[0];
		}
		return $blocked_users_data;
	} // getBlockedUsersData


	public function getReportedUsersData (): array {
		$reported_users_data = []; // return value
		foreach ($this->getReportedUsers() as $loop_user_id) {
			$userFinder = new UserFinder;
			$userFinder->setUserId($loop_user_id);
			['users' => $user_data] = $userFinder->getSearchResults(['username', 'user_id', 'thumbnail_url', 'primary_thumbnail_width', 'primary_thumbnail_height']);
			if (!$user_data) { // deactivated user
				continue;
			}
			$reported_users_data[] = $user_data[0];
		}
		return $reported_users_data;
	} // getReportedUsersData


	protected function getLastUnansweredMessageTimestamp (): ?int {
		$user_id = (int)$this->getId();
		$query = "select unix_timestamp(max(inserted)) from user_messages where from_user_id=$user_id and to_user_id not in (select distinct from_user_id from user_messages where to_user_id=$user_id)";
		$last_unanswered_message_timestamp = DB::getCell($query);
		return $last_unanswered_message_timestamp;
	} // getLastUnansweredMessageTimestamp


	public function getWhenNextSendAllowed (): ?string {
		$last_unanswered_message_timestamp = Session::getUserModel()->getLastUnansweredMessageTimestamp();
		if (!$last_unanswered_message_timestamp) {
			return null;
		}
		$next_send_allowed_at_timestamp = strtotime('+20 hours', $last_unanswered_message_timestamp);
		if ($next_send_allowed_at_timestamp < time()) {
			return null;
		}
		$next_send_allowed_at = date('c', $next_send_allowed_at_timestamp);
		return $next_send_allowed_at;
	} // getWhenNextSendAllowed


	public function getWhetherPreviousContactExistsWith (int $their_user_id): bool {
		$our_user_id = $this->getId();
		$query = "select true from user_messages where (to_user_id = $their_user_id and from_user_id = $our_user_id) or (from_user_id=$their_user_id and to_user_id=$our_user_id) limit 1";
		$previous_contact_exists = DB::getCell($query);
		return (bool)$previous_contact_exists;
	} // getWhetherPreviousContactExistsWith

	public function getWhetherCanSendMessages (): bool {
		if ($this->commonGet('deactivated')) {
			return false;
		}
		if ($this->commonGet('spammer')) {
			return false;
		}
		if (in_array($this->commonGet('email_bouncing'), ['bounced', 'complained'])) {
			return false;
		}
		if ($this->commonGet('deleted_by_admin')) {
			return false;
		}
		return true;
	} // getWhetherCanSendMessages

	public function getIsReportedBy (int $user_id): bool {
		return (bool)DB::getCell('select true from reported_users where ' .DB::where(['reported_user_id' => $this->getId(), 'reported_by_user_id' => $user_id]) .' limit 1');
	} // getIsReportedBy

	public function getWhetherMarkedOurEmailAsSpam (): bool {
		return $this->commonGet('email_bouncing') == 'complained';
	} // getWhetherMarkedOurEmailAsSpam

	public function getIsBouncing (): bool {
		return $this->getEmailBouncing() == 'bounced';
	} // getIsBouncing

	public function getIsDeletedByAdmin (): bool {
		return $this->commonGet('deleted_by_admin');
	} // getIsDeletedByAdmin

	public function getIsSpammer (): bool {
		return $this->commonGet('spammer');
	} // getIsSpammer

	public function getUnverifiedEmail (): ?string {
		return $this->commonGet('unverified_email');
	} // getUnverifiedEmail

	public function getUsername (): string {
		return $this->commonGet('username');
	} // getUsername

	public function getVerifiedEmail (): ?string {
		return $this->commonGet('verified_email');
	} // getVerifiedEmail

	public function getPossessivePronoun () {
		switch ($this->getGender()) {
			case 'female':
				return 'her';
			case 'male':
				return 'his';
			default:
				return 'their';
		}
	} // getPossessivePronoun


	public function getIsSharingKeywords (): bool {
		return $this->commonGet('share_keywords');
	} // getIsSharingKeywords

	public function getLatitude (): ?float {
		return (float)$this->commonGet('latitude');
	} // getLatitude

	public function getLongitude (): ?float {
		return (float)$this->commonGet('longitude');
	} // getLongitude

	public function getNegativeKeywords (): array {
		$query = '
			select keyword, weight
			from negative_keywords
			where ' .DB::where([static::$primaryKeyName => $this->getId()]) .'
			order by weight desc, keyword';
		$negative_keywords = DB::getTable($query);
		return $negative_keywords;
	} // getNegativeKeywords

	public function getPositiveKeywords (): array {
		$query = '
			select keyword, weight
			from positive_keywords
			where ' .DB::where([static::$primaryKeyName => $this->getId()]) .'
			order by weight desc, keyword';
		$positive_keywords = DB::getTable($query);
		return $positive_keywords;
	} // getPositiveKeywords

	/**
	 * @param string $positive_or_negative 'positive' or 'negative'
	 * @param array $keywords_with_weights like [['keyword' => 'sarcasm', weight => 3], ['keyword' => 'dancing', weight => 1]]
	 */
	public function setKeywords (string $positive_or_negative, array $keywords_with_weights): void {
		$table_name = $positive_or_negative == 'positive' ? 'positive_keywords' : 'negative_keywords';
		if (!$keywords_with_weights) {
			$delete_all_keywords_query = "delete from $table_name where " .DB::where(['user_id'=>$this->getId()]);
			DB::query($delete_all_keywords_query);
			return;
		}
		$saved_keywords = [];
		foreach ($keywords_with_weights as $current_keyword_with_weight) {
			$keyword = trim($current_keyword_with_weight['keyword']);
			if (!$keyword) {
				continue;
			}
			$weight = $current_keyword_with_weight['weight'];
			$this->saveKeyword($positive_or_negative, null, $keyword, $weight);
			$saved_keywords[] = $keyword;
		}
		$delete_keywords_not_saved_now_query = "delete from $table_name where " .DB::where(['keyword' =>DB::not($saved_keywords), 'user_id' =>$this->getId()]);
		DB::query($delete_keywords_not_saved_now_query);
	} // setKeywords

	public function getAllKeywordsWithSignedWeights (): array {
		$where = DB::where(['user_id' => $this->getId()]);
		$query = "
			select lower(keyword), cast(weight as signed) * -1 as weight
			from negative_keywords where $where
			union distinct
			select lower(keyword), weight
			from positive_keywords where $where";
		$all_keywords_with_weights = DB::getKeyValueMap($query);
		return $all_keywords_with_weights;
	} // getAllKeywordsWithSignedWeights

	public function getBlockedUsers (): array {
		return DB::getColumn('select blocked_user_id from blocked_users where ' .DB::where(['blocked_by_user_id' => $this->getId()]));
	} // getBlockedUsers

	public function getIsBlockedByUserId (int $user_id): bool {
		return (bool)DB::getCell('select true from blocked_users where ' .DB::where(['blocked_user_id' => $this->getId(), 'blocked_by_user_id' => $user_id]) .' limit 1');
	} // getIsBlockedByUserId

	public function getReportedUsers (): array {
		return DB::getColumn('select reported_user_id from reported_users where ' .DB::where(['reported_by_user_id' => $this->getId()]));
	} // getReportedUsers

	public function getKeywordsAsOther (UserModel $theirUserModel): array {
		// their keywords
		$all_their_keywords_with_weights = $theirUserModel->getAllKeywordsWithSignedWeights();

		// our keywords that they can see
		$where = ['user_id' => $this->getId()];
		if (!$this->getIsSharingKeywords() or !$theirUserModel->getIsSharingKeywords()) {
			if (!$all_their_keywords_with_weights) {
				return ['positive_keywords' => [], 'negative_keywords' => []];
			} else {
				$where['keyword'] = array_keys($all_their_keywords_with_weights);
			}
		}
		$where = DB::where($where);
		$our_positive_keywords = DB::getColumn("select lower(trim(keyword)) from positive_keywords where $where");
		$our_negative_keywords = DB::getColumn("select lower(trim(keyword)) from negative_keywords where $where");

		// our positives with their weights
		$our_positives_with_their_weights = [];
		foreach ($our_positive_keywords as $our_keyword) {
			$our_positives_with_their_weights[$our_keyword] = isset($all_their_keywords_with_weights[$our_keyword]) ? (int)$all_their_keywords_with_weights[$our_keyword] : null;
		}

		// our negatives with their weights
		$our_negatives_with_their_weights = [];
		foreach ($our_negative_keywords as $our_keyword) {
			$our_negatives_with_their_weights[$our_keyword] = isset($all_their_keywords_with_weights[$our_keyword]) ? (int)$all_their_keywords_with_weights[$our_keyword] : null;
		}

		// return
		$keywords['positive_keywords'] = $our_positives_with_their_weights;
		$keywords['negative_keywords'] = $our_negatives_with_their_weights;
		return $keywords;
	} // getKeywordsAsOther

	/**
	 * For cases such as the user correcting a typo in a keyword, pass $old_keyword so we know which keyword to replace.
	 * If the new keyword is already in the database, its weight will be updated (no duplicates get inserted).
	 */
	public function saveKeyword (string $positive_or_negative, ?string $old_keyword, ?string $new_keyword, ?int $new_weight): ?string {
		$table = $positive_or_negative == 'negative' ? 'negative_keywords' : 'positive_keywords';
		$new_keyword = trim($new_keyword);
		if ($new_weight < 0) { // Maybe some user reckons that negative keywords should have negative values.
			$new_weight = abs($new_weight);
		}
		if ($new_weight > static::MAX_KEYWORD_WEIGHT) {
			$new_weight = static::MAX_KEYWORD_WEIGHT;
		}
		$old_keyword_provided_and_different_from_new = $old_keyword and $old_keyword != $new_keyword;
		if ($old_keyword_provided_and_different_from_new) {
			// Updating would fail if the new value would be a duplicate, simpler to delete then insert/replace.
			$delete_old_keyword_query = "delete from $table where " .DB::where(['user_id'=>$this->getId(), 'keyword'=>$old_keyword]);
			DB::query($delete_old_keyword_query);
		}
		if ($new_keyword) {
			DB::insert($table, ['user_id'=>$this->getId(), 'keyword'=>$new_keyword, 'weight'=>$new_weight], true);
		}
		return null;
	} // saveKeyword

	public function getPhotoCarouselData (): array {
		$photo_carousel_data = [
			'user_id'               => $this->getId()
			,'max_bytes'            => PhotoModel::MAX_IMAGE_BYTES
			,'max_standard_width'   => PhotoModel::STANDARD_MAX_WIDTH
			,'max_standard_height'  => PhotoModel::STANDARD_MAX_HEIGHT
			,'max_thumbnail_width'  => PhotoModel::THUMBNAIL_MAX_WIDTH
			,'max_thumbnail_height' => PhotoModel::THUMBNAIL_MAX_HEIGHT
			,'upload_max_filesize'  => convert_shorthand_byte_notation_to_bytes(ini_get('upload_max_filesize'))
			,'post_max_size'        => convert_shorthand_byte_notation_to_bytes(ini_get('post_max_size'))
			,'max_file_uploads'     => (int)ini_get('max_file_uploads')
			,'photos'               => PhotoFinder::getPhotoCarouselPhotosData($this->getId(), $this->getPhotoOrder())
		];
		return $photo_carousel_data;
	} // getPhotoCarouselData

	/**
	 * @param string|array $photo_order comma separated string of photo_id
	 * @return array|number
	 */
	public function setPhotoOrder ($photo_order): ?array {
		$update = [];
		$photo_order_array = is_array($photo_order) ? $photo_order : explode(',', $photo_order);
		$photo_order_string = is_array($photo_order) ? implode(',', $photo_order) : $photo_order;
		$primary_photo_id = $photo_order_array ? $photo_order_array[0] : 0;
		if ($primary_photo_id) {
			$primaryPhotoModel = new PhotoModel($primary_photo_id);
			$update['primary_thumbnail_width'] = $primaryPhotoModel->getThumbnailWidth();
			$update['primary_thumbnail_height'] = $primaryPhotoModel->getThumbnailHeight();
		}
		$update['photo_order'] = $photo_order_string;
		return $this->update($update);
	} // setPhotoOrder

	/** @return string like '12,51,14' */
	public function getPhotoOrder (): string {
		return $this->commonGet('photo_order');
	} // getPhotoOrder

	public function activate (): ?array {
		return $this->update(['deactivated' => false]);
	} // activate

	public function deactivate (): ?array {
		return $this->update(['deactivated' => true]);
	} // deactivate

	public function getIsDeactivated (): bool {
		return (bool)$this->commonGet('deactivated');
	} // getIsDeactivated

	public function getGender (): string {
		return $this->commonGet('gender');
	} // getGender

	public function sendEmailIntroducingNewSite (): void {
		$userFinder = new UserFinder;
		$userFinder->setUserId($this->getId());
		$desired_fields = ['last_visit', 'inserted', 'description', 'username'];
		$search_results = $userFinder->getSearchResults($desired_fields);
		$search_result = $search_results['users'][0];
		['username' => $username, 'inserted' => $inserted, 'description' => $description, 'last_visit' => $last_visit] = $search_result;
		$forgot_password_url = $this->getForgotPasswordUrl();
		$when_created = date('F Y', strtotime($inserted));
		$email_body = "$username,

You created a TypeTango account in $when_created and last logged in $last_visit.

In case you don't remember, TypeTango is a dating site based on Jungian Myers-Briggs/Keirsey personality theory.

You described yourself as $description

When you originally used it, TypeTango was merely an abandoned college project. It has since been expertly rewritten, and its problems fixed.

The site is still free to use, but in order to reduce spam, new contacts are now limited to one per day.

Go to the following URL to log in instantly and set a new password:

$forgot_password_url

In the future, expect to see fun statistics beyond merely type distribution, age distribution, and the most popular keywords.

https://{$_SERVER['HTTP_HOST']}

TypeTango Jungian Myers-Briggs/Keirsey dating site";
		$email_params = [
			'to' => $this->getVerifiedEmail()
			,'subject' => 'TypeTango has been reborn'
			,'text' => $email_body
		];
		$error_message = Email::sendEmailToClientViaAmazonSES($email_params);
		if ($error_message) {
			trigger_error($error_message, E_USER_WARNING);
		}
	} // sendEmailIntroducingNewSite

	public function getUserSummary (): string {
		$userFinder = new UserFinder;
		$userFinder->setUserId($this->getId());
		$userFinder->includeUsuallyExcludedUsers();
		$result_resource = $userFinder->find(['birth_date', 'orientation', 'mbti_type', 'gender', 'city', 'state', 'country']);
		$user_data = DB::getRow($result_resource);
		$user_summary = UserFinder::getUserSummary($user_data);
		return $user_summary;
	} // getUserSummary

	public function getCountryCode (): string {
		return strtoupper(trim((string)$this->commonGet('country')));
	} // getCountryCode

	public function getWhyCannotViewUsers (): ?string {
		if ($this->getIsDeletedByAdmin()) {
			return "Your account was deleted by an admin.";
		}
		if ($this->getIsSpammer()) {
			return "You have been marked as a spammer.";
		}
		if ($this->getIsDeactivated()) {
			return "You have deactivated your account.";
		}
		if ($this->getIsBouncing()) {
			return "Your email address is bouncing.";
		}
		if ($this->getWhetherMarkedOurEmailAsSpam()) {
			return "You marked an email from us as spam.";
		}
		if (!$this->getVerifiedEmail()) {
			return "You have not verified your email address.";
		}
		return null;
	} // getWhyCannotViewUsers

	public function getWhyCannotViewUser (int $user_id): ?string {
		$viewing_own_profile = $this->getId() == $user_id;
		if ($viewing_own_profile) {
			return null;
		}
		$why_cannot_view_users = $this->getWhyCannotViewUsers();
		if ($why_cannot_view_users) {
			return $why_cannot_view_users;
		}
		$otherUserModel = new UserModel($user_id);
		if ($otherUserModel->getIsDeletedByAdmin()) {
			return "User's account was deleted by admin.";
		}
		if ($otherUserModel->getIsDeactivated()) {
			return "User's account is deactivated.";
		}
		if ($otherUserModel->getIsBouncing()) {
			return "User's email address is bouncing.";
		}
		if ($otherUserModel->getIsSpammer()) {
			return "User has been marked as a spammer.";
		}
		if ($otherUserModel->getWhetherMarkedOurEmailAsSpam()) {
			return "User marked an email from us as spam.";
		}
		if (!$otherUserModel->getVerifiedEmail()) {
			return "User has not verified " .$otherUserModel->getPossessivePronoun() ." email address.";
		}
		if ($this->getIsBlockedByUserId($user_id)) {
			return "You were blocked by the other user.";
		}
		if ($this->getIsReportedBy($user_id)) {
			return "You were reported by the other user.";
		}
		return null;
	} // getWhyCannotViewUser
} // UserModel

