<?php

(function (): void {
	// Fundamentals
	define('SERVER_ROLE',     getenv('SERVER_ROLE'));
	define('ERROR_RECIPIENT', getenv('ERROR_RECIPIENT'));

	// Paths outside of repo
	define('PROFILE_PHOTOS_LOCAL_DIR',  getenv('PROFILE_PHOTOS_LOCAL_DIR'));
	define('PROFILE_PHOTOS_REMOTE_DIR', getenv('PROFILE_PHOTOS_REMOTE_DIR'));

	// Basic Auth
	define('ADMIN_IP_ADDRESS', getenv('ADMIN_IP_ADDRESS'));
	define('ADMIN_HTTP_USERNAME',   getenv('ADMIN_HTTP_USERNAME'));
	define('ADMIN_HTTP_PASSWORD',     getenv('ADMIN_HTTP_PASSWORD'));

	// Database
	define('DB_NAME',   getenv('DB_NAME'));
	define('DB_USER',   getenv('DB_USER'));
	define('DB_PASSWD', getenv('DB_PASSWD'));

	// Email sending and error handling
	define('EMAIL_DOMAIN', getenv('EMAIL_DOMAIN'));
	define('DEFAULT_FROM',       'webserver@'.EMAIL_DOMAIN);
	define('DEFAULT_REPLY_TO',       'owner@'.EMAIL_DOMAIN);
	define('ENVELOPE_MAIL_FROM', 'mail-from@'.EMAIL_DOMAIN);

	// Misc
	define('EMAIL_AUTH_PARAM_NAME', 'verification_code');
})();

