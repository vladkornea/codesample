$(printAccountPageInterface)
function printAccountPageInterface () {
	var $localContainer = $('main')
	var pageData = window['pageData']
	if (!pageData) {
		$localContainer.append('<p class="error">Missing page data.</p>')
		return
	}
	var sessionData = pageData['sessionData']
	if (!sessionData) {
		$localContainer.append('<p class="error">Missing session data.</p>')
		return
	}
	var defaultFormData = pageData['defaultFormData']
	if (!defaultFormData) {
		$localContainer.append('<p class="error">Missing form data.</p>')
		return
	}
	var username = sessionData['username']
	var $accountForm = $(
		'<form id="account-form" action="/pages/account/ajax?action=update_account">'
			+'<table id="account-form-structure" class="form-table">'
				+'<tr>'
					+'<th></th>'
					+'<td><label><input id="change-password-checkbox" name="change_password" type="checkbox"><span class="label">Change Password</span></label></td>'
				+'</tr><tr>'
					+'<th><label for="new-password-input">New Password</label></th>'
					+'<td><input id="new-password-input" name="password" type="password" size="15"></td>'
				+'</tr><tr>'
					+'<th><label for="confirm-new-password-input">Confirm New Password</label></th>'
					+'<td><input id="confirm-new-password-input" name="confirm_password" type="password" size="15"></td>'
				+'</tr><tr id="verified-email-row">'
					+'<th>Verified Email</th>'
					+'<td><span id="verified-email"></span></td>'
				+'</tr><tr>'
					+'<th><label for="email-input">Email</label></th>'
					+'<td><input id="email-input" name="email" type="email" size="25" aria-required="true" required=""></td>'
				+'</tr><tr>'
					+'<th><label for="username-input">Username</label></th>'
					+'<td><input id="username-input" name="username" type="text" size="15" aria-required="true" required="" disabled="disabled"></td>'
				+'</tr><tr>'
					+'<th><label for="current-password-input">Current Password</label></th>'
					+'<td><input id="current-password-input" name="current_password" type="password" size="15" aria-required="true" required=""></td>'
				+'</tr><tr>'
					+'<th></th>'
					+'<td><label><input id="deactivate-account-checkbox" name="deactivated" value="1" type="checkbox" class="boolean"><span class="label">Deactivate Profile</span></label></td>'
				+'</tr><tr>'
					+'<th></th>'
					+'<td><input id="account-form-submit-button" type="submit" value="Submit"></td>'
				+'</tr>'
			+'</table>'
		+'</form>'
	)
	$accountForm.on('submit', handleAccountFormSubmit)
	$accountForm.appendTo($localContainer)
	$('#username-input').val(username)
	$('#deactivate-account-checkbox').prop('checked', defaultFormData['deactivated'])
	$('#change-password-checkbox').on('change', handleChangePasswordCheckboxChange)
	;(function configureEmailInputs (){
		var email = sessionData['email']
		var verifiedEmail = defaultFormData['verifiedEmail']
		var unverifiedEmail = defaultFormData['unverifiedEmail']
		var userHasMultipleEmailAddresses = verifiedEmail && unverifiedEmail && (verifiedEmail != unverifiedEmail) ? true : false
		if (userHasMultipleEmailAddresses) {
			$('#verified-email').text(verifiedEmail)
			$('#email-input').val(unverifiedEmail)
		} else {
			$('#verified-email-row').remove()
			$('#email-input').val(email)
		}
		var isBouncing = pageData['defaultFormData']['email_bouncing'] == 'bounced'
		if (isBouncing) {
			var $bouncingNotice = $('<div id="bouncing-notice" class="error">Email is bouncing. Profile is inactive.</div>')
			$bouncingNotice.insertAfter('#email-input')
		}
	})() // configureEmailInputs
	refreshFormFields()
	return // functions below
	function handleAccountFormSubmit (event) {
		event.preventDefault()
		removeOldErrorMessages($localContainer[0])
		var formData = {}
		var newUsername = $('#username-input').val()
		if (newUsername && newUsername != username) {
			formData['new_username'] = newUsername
		}
		var $email = $('#email-input')
		var newEmail = $email.val()
		if (!isValidEmail(newEmail)) {
			displayFormTableErrorMessage($email, "Invalid email address.")
			$email.focus()
			return
		}
		formData['new_email'] = newEmail
		if ($('#change-password-checkbox').is(':checked')) {
			var $newPassword = $('#new-password-input')
			var $confirmNewPassword = $('#confirm-new-password-input')
			var newPassword = $newPassword.val()
			var confirmNewPassword = $confirmNewPassword.val()
			if (newPassword || confirmNewPassword) {
				if (newPassword != confirmNewPassword) {
					displayFormTableErrorMessage($newPassword, "Passwords do not match.")
					$newPassword.focus()
					return
				}
				var minimumPasswordLength = 6
				if (newPassword.length < minimumPasswordLength) {
					displayFormTableErrorMessage($newPassword, "Password must contain at least " +minimumPasswordLength +" characters.")
					$newPassword.focus()
					return
				}
				formData['new_password'] = newPassword
			}
		}
		var $submitButton = $('input[type=submit]')

		var $password = $('#current-password-input')
		var password = $password.val()
		if (!password) {
			displayFormTableErrorMessage($password, "Enter your current password.")
			$password.focus()
			return
		}
		formData['current_password'] = password
		formData['deactivated'] = $('#deactivate-account-checkbox').is(':checked') ? 1 : 0

		$submitButton.prop('disabled', true)
		apiCall('/pages/account/ajax?action=update_account', handleUpdateAccountResponse, formData)
		return // functions below
		function handleUpdateAccountResponse (response) {
			$submitButton.prop('disabled', false)
			if (response['success'] == 1) {
				location.reload()
			}
			if (response['error']) {
				if (response['error_message']) {
					displayFormTableErrorMessage('#account-form-submit-button', response['error_message'])
				}
				if (response['error_messages']) {
					$.each(response['error_messages'], function (fieldName, errorMessage) {
						var $input = $accountForm.find('[name='+fieldName+']').first()
						var fieldFound = $input.length > 0
						if (fieldFound) {
							displayFormTableErrorMessage($input, errorMessage)
						} else {
							if (fieldName === 'unverified_email') {
								displayFormTableErrorMessage('#email-input', errorMessage)
							} else {
								displayFormTableErrorMessage($submitButton, errorMessage)
							}
						}
					}) // each
				}
			}
		} // handleUpdateAccountResponse
	} // handleAccountFormSubmit

	function handleChangePasswordCheckboxChange () {
		refreshFormFields()
		if ($('#change-password-checkbox').is(':checked')) {
			$('#new-password-input').focus()
		}
	} // handleChangePasswordCheckboxChange

	function refreshFormFields () {
		var shouldDisableNewPasswordInputs = $('#change-password-checkbox').is(':checked') ? false : true
		$('#new-password-input, #confirm-new-password-input').prop('disabled', shouldDisableNewPasswordInputs)
	} // refreshFormFields
} // printAccountPageInterface

