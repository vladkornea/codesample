$( hiliteCurrentLocation )

$( installLoginWidget )

function hiliteCurrentLocation () {
	$( '#page-structure > *:not(tbody) a[href="' + location.pathname + location.search + '"]' ).addClass( 'current-page' )
} // hiliteCurrentLocation

function installLoginWidget () {
	$( '#login-link' ).one( 'click', handleFirstLoginLinkClick )
} // installLoginWidget

function handleFirstLoginLinkClick ( clickEvent ) {
	clickEvent.preventDefault()
	clickEvent.stopPropagation()
	runLoginWidget()
} // handleFirstLoginLinkClick

function runLoginWidget () {
	var $loginLink = $( '#login-link' )
	var $localContainer = $loginLink.parent()
	var $loginWidget = $(
		'<div id="login-widget">'
			+'<div id="login-widget-forms-container">'
				+'<div id="login-form-container">'
					+'<form id="login-form" action="/ajax/account?action=log_in" method="post">'
						+'<h3>Log In</h3>'
						+'<table class="form-table">'
						+'<tbody><tr>'
							+'<td class="form-label"><label for="login-user-input">Username or Email</label></td>'
							+'<td class="form-field"><input type="text" id="login-user-input" name="user" size="20" aria-required="true" required="" autocomplete="email"></td>'
							+'<td><input id="forgot-password-button" type="button" value="Forgot password" tabindex="-1"></td>'
						+'</tr><tr>'
							+'<td class="form-label"><label for="login-password-input">Password</label></td>'
							+'<td class="form-field"><input type="password" id="login-password-input" name="password" size="20" autocomplete="current-password"></td>'
							+'<td class="form-field">'
							+'</td>'
						+'</tr><tr>'
							+'<td></td>'
							+'<td id="login-button-container"><input type="submit" id="login-button" value="Log in"></td>'
							+'<td class="form-field">'
								+'<label id="remember-me-checkbox-label" for="remember-me-checkbox">'
									+'<input type="checkbox" id="remember-me-checkbox" name="remember_me" ' + ( 1 == navigator.doNotTrack ? '' : 'checked' ) + '>'
									+'<span class="label">Remember me</span>'
								+'</label>'
							+'</td>'
						+'</tr></tbody></table>'
					+'</form>'
				+'</div>'
			+'</div>'
		+'</div>'
	)
	var $loginForm = $loginWidget.find('#login-form')
	$loginForm.on('submit', handleLoginFormSubmit)
	$(document).click(handleDocumentClick)
	$loginWidget.click(handleWidgetClick)
	$loginLink.click(handleLoginLinkClick)
	$loginWidget.find('#logout-link').click(handleLogoutLinkClick)
	$loginWidget.find('#forgot-password-button').click(handleForgotPasswordClick)
	$loginWidget.appendTo($localContainer)

	openLoginForm()
	return // functions below
	function openLoginForm () {
		$('#login-form-container').show().find('input').first().focus()
	} // openLoginForm

	function closeLoginForm () {
		$('#login-form-container').hide()
	} // closeLoginForm

	function handleDocumentClick (event) {
		var leftMouseButtonClicked = event.which == 1
		if (leftMouseButtonClicked) {
			closeLoginForm()
		}
	} // handleDocumentClick

	function handleWidgetClick (event) {
		event.stopPropagation() // Don't let document.click trigger, handleDocumentClick() collapses menus.
	} // handleWidgetClick

	function handleLoginLinkClick (event) {
		event.stopPropagation() // Don't let document.click trigger, handleDocumentClick() collapses menus.
		event.preventDefault()
		openLoginForm()
	} // handleLoginLinkClick

	function handleLogoutLinkClick (event) {
		event.preventDefault()
		apiCall('/ajax/account?action=log_out', handleLogoutResponse)
		return // functions below
		function handleLogoutResponse () {
			window.location.reload()
		} // handleLogoutResponse
	} // handleLogoutLinkClick

	function handleForgotPasswordClick () {
		removeOldErrorMessages($loginWidget[0])
		var $user = $loginWidget.find('#login-user-input')
		var user = $user.val()
		apiCall('/ajax/account?action=forgot_password', handleForgotPasswordResponse, {'user':user})
		return // functions below
		function handleForgotPasswordResponse (response) {
			if (response['success']) {
				displayFormTableErrorMessage('#login-button', "Password reset email sent.")
				return
			}
			if (response['error']) {
				var errorMessage = response['error_message']
				var errorMessages = response['error_messages']
				if (errorMessage) {
					displayFormTableErrorMessage('#login-button', errorMessage)
				}
				if (errorMessages) {
					if (errorMessages['user']) {
						displayFormTableErrorMessage('#login-user-input', errorMessages['user'])
					}
					if (errorMessages['password']) {
						displayFormTableErrorMessage('#login-password-input', errorMessages['password'])
					}
				}
			}
		} // handleForgotPasswordResponse
	} // handleForgotPasswordClick

	function handleLoginFormSubmit (event) {
		event.preventDefault()
		var loginForm = event.currentTarget
		{ // check for obvious errors
			removeOldErrorMessages(loginForm)
			var userField = document.getElementById('login-user-input')
			if (!userField.value) {
				displayFormTableErrorMessage(userField, "Enter your username or email address.")
				return
			}
			var passwordField = document.getElementById('login-password-input')
			if (!passwordField.value) {
				displayFormTableErrorMessage(passwordField, "Enter your password.")
				return
			}
		}
		submitFormViaAjax(loginForm, handleLoginResponse, getClientInfo())
		return // functions below
		function handleLoginResponse (response) {
			removeOldErrorMessages($loginWidget[0])
			if (response['error']) {
				var errorMessage = response['error_message']
				var errorMessages = response['error_messages']
				if (errorMessage) {
					displayFormTableErrorMessage('#login-button', errorMessage)
				}
				if (errorMessages) {
					if (errorMessages['user']) {
						displayFormTableErrorMessage('#login-user-input', errorMessages['user'])
					}
					if (errorMessages['password']) {
						displayFormTableErrorMessage('#login-password-input', errorMessages['password'])
					}
				}
			}
			if (response['success']) {
				window.location.reload()
				return
			} else {
				console.log(response)
			}
		} // handleLoginResponse
	} // handleLoginFormSubmit
} // runLoginWidget

