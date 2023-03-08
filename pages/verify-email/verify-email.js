$(printVerifyEmailPageInterface)
function printVerifyEmailPageInterface () {
	var $localContainer = $('main')
	sendVerifyEmailRequest()
	return // functions below
	function sendVerifyEmailRequest () {
		var verificationCode = getUrlParams()['verification_code']
		var data = getClientInfo()
		data['verification_code'] = verificationCode
		apiCall('/pages/verify-email/ajax?action=verify_email', handleVerifyEmailResponse, data)
		return // functions below
		function handleVerifyEmailResponse (response) {
			if (response['success']) {
				if (response['message']) {
					$('<p></p>').text(response['message']).appendTo($localContainer)
				}
			}
			if (response['error']) {
				if (response['error_message']) {
					$('<p></p>').text(response['error_message']).appendTo($localContainer)
				}
			}
		} // handleVerifyEmailResponse
	} // sendVerifyEmailRequest
} // printVerifyEmailPageInterface

