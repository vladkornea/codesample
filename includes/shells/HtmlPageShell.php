<?php

require_once 'HttpPageShell.php';

interface HtmlPageShellInterface extends HttpPageShellInterface {
	function addCssCode (string $css_code): void;
	function addCssFile (string $css_file): void;
	function addJsCode (string $js_code): void;
	function addJsFile (string $js_file): void;
	function addJsFiles (array $js_files): void;
	function addJsVar (string $var_name, $var_value): void;
	function appendToHead (string $meta_tag): void;
	function includeMomentJsLib () : void;
	function setDescription (string $meta_description): void;
	function setDiscourageIndexing (): void;
	function setKeywords (string $keywords): void;
	function setTitle (string $title): void;
	static function setConfirmationMessage (string $confirmation_message): void;
} // HtmlPageShellInterface

class HtmlPageShell extends HttpPageShell implements HtmlPageShellInterface {
	protected $author       = "Vladimir Kornea";
	protected $pageTitle    = ''; // Passed to constructor.
	protected $keywords     = ''; // Call ->setKeywords().
	protected $appendToHead = ''; // Call ->appendToHead().
	protected $metaDescription = ""; // Call ->setDescription().
	protected $jsVars       = []; // Call ->addJsVar(string, mixed)
	protected $confirmationMessageFromPreviousPage = ''; // __construct() set it from $_SESSION, call ->getConfirmationMessageMarkupOnce()
	protected static $confirmationMessageForNextPage = ''; // __destruct() sets $_SESSION from it, call ::setConfirmationMessage()

	function __construct (string $page_title = 'TypeTango', string $page_description = "TypeTango Jungian Myers-Briggs/Keirsey Personality Theory Dating: INTJ, ENTJ, INTP, ENTP, ISTJ, ESTJ, ISTP, ESTP, INFJ, ENFJ, INFP, ENFP, ISFJ, ESFJ, ISFP, ESFP") {
		ob_start();
		parent::__construct();
		ini_set('html_errors', true);
		$this->setTitle( $page_title );
		$this->setDescription( $page_description );
		if (isset($_SESSION['Confirmation Message'])) {
			$this->confirmationMessageFromPreviousPage = $_SESSION['Confirmation Message'];
		}
		if (headers_sent()) {
			throw new RuntimeException("Headers already sent.");
		}
	} // __construct

	public function includeMomentJsLib () : void {
		$lib_dir = '/js/lib/moment/2.29.4';
		$this->addJsFiles( [ "$lib_dir/moment-with-locales.min.js", "$lib_dir/moment-timezone-with-data.min.js" ] );
	} // includeMomentJsLib

	public function addCssCode (string $css_code): void {
		$this->appendToHead('<style type="text/css">' ."\n$css_code\n" .'</style>');
	} // addCssCode

	/** @param string $css_file Path relative to document root, starting with slash. */
	public function addCssFile (string $css_file): void {
		$filemtime = filemtime($_SERVER['DOCUMENT_ROOT'] .$css_file);
		$this->appendToHead('<link rel="stylesheet" type="text/css" media="screen" href="' ."$css_file?filemtime=$filemtime" .'">');
	} // addCssFile

	/** @param string $js_code without `<script>` tags */
	public function addJsCode (string $js_code): void {
		$this->appendToHead('<script>'."\n".$js_code."\n".'</script>');
	} // addJsCode

	/** @param string $js_file Path relative to document root, starting with slash. */
	public function addJsFile (string $js_file): void {
		$requested_filename = $_SERVER['DOCUMENT_ROOT'] .$js_file;
		$filemtime = file_exists($requested_filename) ? filemtime($requested_filename) : filemtime("$requested_filename.php");
		$this->appendToHead('<script defer src="' ."$js_file?filemtime=$filemtime" .'"></script>');
	} // addJsFile

	/** @param array $js_files Some files like create-account.js and profile.js rely on files like countries.js and usa-states.js. */
	public function addJsFiles (array $js_files): void {
		foreach ($js_files as $js_file) {
			$this->addJsFile($js_file);
		}
	} // addJsFiles

	public function addJsVar (string $var_name, $var_value): void {
		$this->jsVars[$var_name] = $var_value;
	} // addJsVar

	public function appendToHead (string $meta_tag): void {
		$this->appendToHead .= "$meta_tag\n";
	} // appendToHead

	public function setDescription (string $meta_description): void {
		$this->metaDescription = (string)$meta_description;
	} // setDescription

	/** Tells robots not to index this page. */
	public function setDiscourageIndexing (): void {
		$this->appendToHead('<meta name="robots" content="noindex, follow">');
	} // setDiscourageIndexing

	public function setKeywords (string $keywords): void {
		$this->keywords = $keywords;
	} // setKeywords

	public function setTitle ( string $page_title ): void {
		$site_name_is_in_page_title = false !== strpos( $page_title, 'TypeTango');
		if ( ! $site_name_is_in_page_title ) {
			$page_title = "$page_title - TypeTango";
		}
		$this->pageTitle = $page_title;
	} // setTitle

	// Temporary session message to print on the next page.
	public static function setConfirmationMessage (string $confirmation_message): void {
		static::$confirmationMessageForNextPage = (string)$confirmation_message;
	} // setConfirmationMessage

	/** @return string previous confirmation message markup (once--it also deletes the confirmation message) */
	protected function getConfirmationMessageMarkupOnce (): string {
		if (!$this->confirmationMessageFromPreviousPage) {
			return '';
		}
		$confirmation_message_markup = '<div class="confirmation-message">' .htmlspecialchars($this->confirmationMessageFromPreviousPage) .'</div>';
		unset($this->confirmationMessageFromPreviousPage);
		return $confirmation_message_markup;
	} // getConfirmationMessageMarkupOnce

	/** The destructor compiles the final page. */
	function __destruct () {
		$ob_content = ob_get_clean();
		$this->addJsCode("window.pageData = " .json_encode($this->jsVars, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
		?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?=htmlspecialchars($this->pageTitle)?></title>
<?php
	if ($this->metaDescription) {
		echo '<meta name="description" content="', htmlspecialchars($this->metaDescription), '">', "\n";
	}
	if ($this->author) {
		echo '<meta name="author" content="', htmlspecialchars($this->author), '">', "\n";
	}
	if ($this->keywords) {
		echo '<meta name="keywords" content="', htmlspecialchars($this->keywords), '">', "\n";
	}
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Content-Security-Policy" content="default-src 'unsafe-inline' <?=htmlspecialchars($_SERVER['SERVER_NAME'])?>">
<?=$this->appendToHead?>
<link rel="icon" href="/favicon.ico">
</head>
<body>
<?=$this->getConfirmationMessageMarkupOnce()?>
<?=$ob_content?>
</body>
</html>
<?php
		if (static::$confirmationMessageForNextPage) {
			$_SESSION['Confirmation Message'] = static::$confirmationMessageForNextPage;
			static::$confirmationMessageForNextPage = null;
		}
		parent::__destruct();
	} // __destruct
} // class HtmlPageShell

