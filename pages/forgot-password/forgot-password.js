$(printForgotPasswordPageInterface)
function printForgotPasswordPageInterface () {
	var $localContainer = $('main')
	var verificationCode = getUrlParams()['verification_code']
	var verificationCodeIsValid = window['pageData']['verificationCodeIsValid']
	if (!verificationCodeIsValid) {
		var errorMessage = "Invalid verification code (perhaps it expired or was used)."
		$('<p></p>').text(errorMessage).appendTo($localContainer)
		return
	}
	printForgotPasswordForm()
	return // functions below
	function printForgotPasswordForm () {
		var $forgotPasswordForm = $(
			'<form id="forgot-password-form" action="/pages/forgot-password/ajax.php?action=set_password" method="post">'
				+'<table class="form-table">'
					+'<tr>'
						+'<th><label for="new-password">New Password</label></th>'
						+'<td><input id="new-password" type="password" name="new_password"></td>'
					+'</tr><tr>'
						+'<th><label for="confirm-password">Confirm Password</label></th>'
						+'<td><input id="confirm-password" type="password"></td>'
					+'</tr><tr>'
						+'<th><input type="hidden" name="verification_code" value="'+verificationCode+'"></th>'
						+'<td><input id="forgot-password-submit-button" type="submit" value="Set New Password"></td>'
					+'</tr>'
				+'</table>'
			+'</form>'
		)
		$forgotPasswordForm.on('submit', handleForgotPasswordFormSubmit)
		$forgotPasswordForm.appendTo($localContainer)
		var $submitButton = $('#forgot-password-submit-button')
		function handleForgotPasswordFormSubmit (event) {
			event.preventDefault()
			removeOldErrorMessages($localContainer[0])

			// validate password
			var $password = $('#new-password')
			var $confirmPassword = $('#confirm-password')
			var minPasswordLength = 6
			var passwordIsTooShort = $password.val().length < minPasswordLength
			if (passwordIsTooShort) {
				displayFormTableErrorMessage($password, "Password must be at least " +minPasswordLength +" characters long.")
				return
			}
			var passwordMismatch = $password.val() !== $confirmPassword.val()
			if (passwordMismatch) {
				displayFormTableErrorMessage($confirmPassword, "Passwords do not match.")
				return
			}
			submitFormViaAjax($forgotPasswordForm, handleSetPasswordResponse, getClientInfo())
			return // functions below
			function handleSetPasswordResponse (response) {
				if (response['success']) {
					window.location = '/profile'
					return
				}
				if (response['error']) {
					var errorMessages = response['error_messages']
					$.each(errorMessages, function(fieldName, errorMessage){
						var $element = $forgotPasswordForm.find('[name='+fieldName+']').last()
						var elementFound = $element.length > 0 ? true : false
						if (elementFound) {
							displayFormTableErrorMessage($element, errorMessage)
						} else {
							displayFormTableErrorMessage($submitButton, errorMessage)
						}
					})
				}
			} // handleSetPasswordResponse
		} // handleForgotPasswordFormSubmit
	} // printForgotPasswordForm
} // printForgotPasswordPageInterface

