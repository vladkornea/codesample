<?php

require_once 'BaseModel.php';

interface LoginModelInterface extends BaseModelInterface {
	static function create (array $form_data): array;
	function logOut (): void;
	function getCookiePassword (): string;
	function getUserId (): int;
	function getUserModel (): UserModel;
} // LoginModelInterface

class LoginModel extends BaseModel implements LoginModelInterface {
	use LoginTraits;

	protected $userModel;

	public static function create (array $form_data): array {
		$error_messages = [];
		if (empty($form_data['user_id'])) {
			throw new InvalidArgumentException("Used ID not provided.");
		}
		do {
			$cookie_password = get_random_string(26);
		} while (DB::getCell('select true from logins where ' .DB::where(['cookie_password'=>$cookie_password])));
		$form_data['cookie_password'] = $cookie_password;
		$login_id = parent::create($form_data);
		if (!is_numeric($login_id)) {
			$error_messages = $login_id;
			$login_id = null;
		}
		return ['error_messages'=>$error_messages, 'login_id'=>$login_id];
	} // create

	public function logOut (): void {
		$this->update(['logout_timestamp' => DB::verbatim('now()')]);
	} // logOut

	public function getCookiePassword (): string {
		return $this->commonGet('cookie_password');
	} // getCookiePassword

	public function getUserId (): int {
		return $this->commonGet('user_id');
	} // getUserId

	public function getUserModel (): UserModel {
		if (!$this->userModel) {
			$this->userModel = new UserModel($this->getUserId());
		}
		return $this->userModel;
	} // getUserModel
} // LoginModel

