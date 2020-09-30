<?php

class SESv4 implements SesInterface {

	protected $rawMessage        = '';
	protected $requestPayload    = '';
	protected $streamContextOptions = '';
	protected $canonicalRequest  = '';
	protected $credentialScope   = '';
	protected $stringToSign      = '';
	protected $binaryDerivedSigningKey = null;
	protected $requestSignatureInLowercaseHexits = '';
	protected $headers           = [];
	protected $canonicalHeaders  = '';
	protected $signedHeaders     = '';
	protected $requestTimestamp  = 0;
	protected $requestDateTime   = '';
	protected $requestDate       = '';
	protected $responseXmlString = '';
	const AWS_REGION             = 'us-east-1';
	const AWS_SERVICE            = 'email';
	const REQUEST_METHOD         = 'POST'; // capitalization follows example in docs and might matter
	const TERMINATION_STRING     = 'aws4_request';
	const HASH_ALGO_AWS          = 'AWS4-HMAC-SHA256';

	/**
	 * @link http://docs.aws.amazon.com/ses/latest/DeveloperGuide/send-email-raw.html
	 * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_SendRawEmail.html
	 * @link http://docs.aws.amazon.com/ses/latest/DeveloperGuide/email-format.html
	 * @link http://docs.aws.amazon.com/ses/latest/APIReference/API_RawMessage.html
	 * @param string $raw_message
	 * @return array with keys: 'message_id', 'request_id', 'error_message'
	 * @throws Exception
	 */
	public function sendRawEmail ( string $raw_message ) : array {
		$return_values = [
			'message_id'    => null,
			'request_id'    => null,
			'error_message' => null,
		];
		$this->rawMessage       = $raw_message;
		// http://docs.aws.amazon.com/AWSEC2/latest/APIReference/CommonParameters.html
		$headers = [];
		foreach ( $this->getHeaders() as $header_name => $header_value ) {
			$headers[] = "$header_name:$header_value";
		}
		$headers[] = $this->getAuthorizationHeader();
		$stream_context_options = [
			'http' => [
				'method'        => self::REQUEST_METHOD,
				'header'        => $headers,
				'content'       => $this->getRequestPayload(),
				'ignore_errors' => true,
			]
		];
		$url = 'https://' . getenv('AWS_SES_ENDPOINT');
		$stream_context_resource = stream_context_create( $stream_context_options );
		$this->streamContextOptions = stream_context_get_options( $stream_context_resource );
		$this->responseXmlString = file_get_contents( $url, false, $stream_context_resource );
		$response = simplexml_load_string( $this->responseXmlString );
		if (isset($response->Error)) {
			$return_values['error_message'] = "Error sending email via SES: {$response->Error->Message}";
			Email::sendEmailToDeveloperViaSendmail([
				 'reply-to' => DEFAULT_REPLY_TO
				,'to'       => ERROR_RECIPIENT
				,'subject'  => $return_values['error_message']
				,'text'     => print_r($response, true)
			]);
			return $return_values;
		}
		$response = [
			'message_id' => (string) $response->SendRawEmailResult->MessageId,
			'request_id' => (string) $response->ResponseMetadata->RequestId
		];
		return $response;
	} // sendRawEmail

	/**
	 * Task 1: Create a canonical request for Signature Version 4
	 * https://docs.aws.amazon.com/general/latest/gr/sigv4-create-canonical-request.html
	 */
	protected function getCanonicalRequest () : string {
		if ( ! $this->canonicalRequest ) {
			$http_request_method    = self::REQUEST_METHOD;
			$canonical_uri          = '/';
			$canonical_query_string = '';
			$canonical_headers      = $this->getCanonicalHeaders();
			$signed_headers         = $this->getSignedHeaders();
			$hashed_payload         = hash( 'sha256', $this->getRequestPayload(), false );
			$canonical_request      = implode( "\n", [
				$http_request_method,
				$canonical_uri,
				$canonical_query_string,
				$canonical_headers,
				$signed_headers,
				$hashed_payload,
			] );
			$this->canonicalRequest = $canonical_request;
		}
		return $this->canonicalRequest;
	} // getCanonicalRequest

	/**
	 * Task 2: Create a string to sign for Signature Version 4
	 * https://docs.aws.amazon.com/general/latest/gr/sigv4-create-string-to-sign.html
	 */
	protected function getStringToSign () : string {
		if ( ! $this->stringToSign ) {
			$algorithm                = self::HASH_ALGO_AWS;
			$request_datetime         = $this->getRequestDateTime_ISO8601();
			$credential_scope         = $this->getCredentialScope();
			$hashed_canonical_request = hash( 'sha256', $this->getCanonicalRequest(), false );
			$string_to_sign           = implode( "\n", [
				$algorithm,
				$request_datetime,
				$credential_scope,
				$hashed_canonical_request,
			] );
			$this->stringToSign = $string_to_sign;
		}
		return $this->stringToSign;
	} // getStringToSign

	/**
	 * Task 3: Calculate the signature for AWS Signature Version 4
	 * https://docs.aws.amazon.com/general/latest/gr/sigv4-calculate-signature.html
	 * https://docs.aws.amazon.com/general/latest/gr/sigv4_elements.html
	 */
	protected function getSignature () : string {
		if ( ! $this->requestSignatureInLowercaseHexits ) {
			if ( ! $this->binaryDerivedSigningKey ) {
				// https://docs.aws.amazon.com/general/latest/gr/signature-v4-examples.html
				$date_key    = hash_hmac( 'sha256', $this->getRequestDate_Ymd(), 'AWS4' . getenv('AWS_SECRET_ACCESS_KEY'), true );
				$region_key  = hash_hmac( 'sha256', self::AWS_REGION, $date_key, true );
				$service_key = hash_hmac( 'sha256', self::AWS_SERVICE, $region_key, true );
				$signing_key = hash_hmac( 'sha256', self::TERMINATION_STRING, $service_key, true );
				$this->binaryDerivedSigningKey = $signing_key;
			}
			$signature = hash_hmac( 'sha256', $this->getStringToSign(), $this->binaryDerivedSigningKey, false );
			$this->requestSignatureInLowercaseHexits = $signature;
		}
		return $this->requestSignatureInLowercaseHexits;
	} // getSignature

	/**
	 * Task 4: Add the signature to the HTTP request
	 * https://docs.aws.amazon.com/general/latest/gr/sigv4-add-signature-to-request.html
	 */
	protected function getAuthorizationHeader () : string {
		$credential = getenv('AWS_ACCESS_KEY_ID') . '/' . $this->getCredentialScope();
		$signed_headers = $this->getSignedHeaders();
		$signature = $this->getSignature();
		$authorization_header = 'Authorization:' . self::HASH_ALGO_AWS . " Credential=$credential, SignedHeaders=$signed_headers, Signature=$signature";
		return $authorization_header;
	} // getAuthorizationHeader

	// 6 @ https://docs.aws.amazon.com/general/latest/gr/sigv4-create-canonical-request.html
	protected function getRequestPayload () : string {
		if ( ! $this->requestPayload ) {
			$request_params = [
				'Action'          => 'SendRawEmail',
				'RawMessage.Data' => base64_encode( $this->rawMessage ),
			];
			$this->requestPayload = http_build_query( $request_params, '', '&', PHP_QUERY_RFC3986 );
		}
		return $this->requestPayload;
	} // getRequestPayload

	protected function getHeaders () : array {
		if ( ! $this->headers ) {
			$this->headers = [
				'content-type'   => 'application/x-www-form-urlencoded',
				'host'           => getenv('AWS_SES_ENDPOINT'),
				'x-amz-date'     => $this->getRequestDateTime_ISO8601(),
	//			'Content-Length' => strlen( $this->getRequestPayload() ),
	//			'Connection'     => 'close',
			];
		}
		return $this->headers;
	} // getHeaders

	// 4 @ https://docs.aws.amazon.com/general/latest/gr/sigv4-create-canonical-request.html
	protected function getCanonicalHeaders () : string {
		if ( ! $this->canonicalHeaders ) {
			$canonical_headers_array = [];
			foreach ( $this->getHeaders() as $header_name => $header_value ) {
				$header_value = preg_replace( '/ {2,}/', ' ', $header_value );
				$canonical_headers_array[ strtolower( $header_name ) ] = trim( $header_value );
			}
			ksort($canonical_headers_array, SORT_STRING);
			$canonical_headers_string = '';
			foreach ( $canonical_headers_array as $header_name => $header_value ) {
				$canonical_headers_string .= "$header_name:$header_value\n";
			}
			$this->canonicalHeaders = $canonical_headers_string;
		}
		return $this->canonicalHeaders;
	} // getCanonicalHeaders

	// 5 @ https://docs.aws.amazon.com/general/latest/gr/sigv4-create-canonical-request.html
	protected function getSignedHeaders () : string {
		if ( ! $this->signedHeaders ) {
			$lowercase_headers = array_map( 'strtolower', array_keys( $this->getHeaders() ) );
			sort( $lowercase_headers, SORT_STRING );
			$signed_headers = implode( ';', $lowercase_headers );
			$this->signedHeaders = $signed_headers;
		}
		return $this->signedHeaders;
	} // getSignedHeaders

	protected function getRequestTimestamp () : int {
		if ( ! $this->requestTimestamp ) {
			$this->requestTimestamp = time();
		}
		return $this->requestTimestamp;
	} // getRequestTimestamp

	// 3 @ https://docs.aws.amazon.com/general/latest/gr/sigv4-create-string-to-sign.html
	// 1 @ https://docs.aws.amazon.com/general/latest/gr/sigv4-calculate-signature.html
	protected function getRequestDate_Ymd () : string {
		if ( ! $this->requestDate ) {
			$this->requestDate = date( 'Ymd', $this->getRequestTimestamp() );
		}
		return $this->requestDate;
	} // getRequestDate_Ymd

	// 2 @ https://docs.aws.amazon.com/general/latest/gr/sigv4-create-string-to-sign.html
	protected function getRequestDateTime_ISO8601 () : string {
		if ( ! $this->requestDateTime ) {
			$this->requestDateTime = date( 'Ymd\THis\Z', $this->getRequestTimestamp() );
		}
		return $this->requestDateTime;
	} // getRequestDateTime_ISO8601

	// 3 @ https://docs.aws.amazon.com/general/latest/gr/sigv4-create-string-to-sign.html
	// https://docs.aws.amazon.com/general/latest/gr/sigv4_elements.html
	protected function getCredentialScope () : string {
		if ( ! $this->credentialScope ) {
			$this->credentialScope = implode('/', [ $this->getRequestDate_Ymd(), self::AWS_REGION, self::AWS_SERVICE, self::TERMINATION_STRING ] );
		}
		return $this->credentialScope;
	} // getCredentialScope

} // SESv4
