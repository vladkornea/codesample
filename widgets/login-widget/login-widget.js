$(loadLoginWidget)
function loadLoginWidget () {
	var pageData = window['pageData']
	var sessionData = pageData ? pageData['sessionData'] : null
	var userId = sessionData ? sessionData['user_id'] : null
	var $localContainer = $('#navbar')
	var $loginWidget = $(
		'<div id="login-widget">'
			+'<div id="login-links-container">'
				+(userId ? '' :
					'<a id="home-link" href="/">TypeTango</a>'
					+'<a id="create-account-link" href="/create-account">Create Account</a>'
					+'<a id="keyword-suggestions-link" href="/keyword-suggestions">Keyword Suggestions</a>'
					+'<a id="create-account-link" href="/help">Help</a>'
					+'<a id="login-link" href="javascript:">Log In</a>'
				)
				+(!userId ? '' :
					'<a id="home-link" href="/">TypeTango</a>'
					+'<a id="edit-profile-link" href="/profile">Edit Profile</a>'
					+'<a id="view-profile-link" href="/profile?user_id=' +userId +'">View Profile</a>'
					+'<a id="search-link" href="/search">Search</a>'
					+'<a id="contacts-link" href="/contacts">Contacts</a>'
					+'<a id="account-link" href="/account">My Account</a>'
					+'<a id="keyword-suggestions-link" href="/keyword-suggestions">Keyword Suggestions</a>'
					+'<a id="create-account-link" href="/help">Help</a>'
					+'<a id="logout-link" href="javascript:">Log Out</span>'
				)
			+'</div>'
			+'<div id="login-widget-forms-container">'
				+'<div id="login-form-container">'
					+'<form id="login-form" action="/ajax/account?action=log_in" method="post">'
						+'<h3>Log In</h3>'
						+'<table class="form-table">'
						+'<tbody><tr>'
							+'<td class="form-label"><label for="login-user-input">Username or Email</label></td>'
							+'<td class="form-field"><input type="text" id="login-user-input" name="user" size="20"></td>'
							+'<td><input id="forgot-password-button" type="button" value="Forgot password" tabindex="-1"></td>'
						+'</tr><tr>'
							+'<td class="form-label"><label for="login-password-input">Password</label></td>'
							+'<td class="form-field"><input type="password" id="login-password-input" name="password" size="20"></td>'
							+'<td class="form-field">'
							+'</td>'
						+'</tr><tr>'
							+'<td></td>'
							+'<td id="login-button-container"><input type="submit" id="login-button" value="Log in"></td>'
							+'<td class="form-field">'
								+'<label id="remember-me-checkbox-label" for="remember-me-checkbox">'
									+'<input type="checkbox" id="remember-me-checkbox" name="remember_me" checked>'
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
	$loginWidget.find('#login-link').click(handleLoginLinkClick)
	$loginWidget.find('#logout-link').click(handleLogoutLinkClick)
	$loginWidget.find('#login-form-container').hide()
	$loginWidget.find('#forgot-password-button').click(handleForgotPasswordClick)
	$loginWidget.appendTo($localContainer)
	return // functions below
	function collapseMenus () {
		$loginWidget.find('#login-links-container').find('.expanded').removeClass('expanded')
		$loginWidget.find('#login-form-container').hide()
	} // collapseMenus

	function handleDocumentClick (event) {
		var leftMouseButtonClicked = event.which == 1
		if (leftMouseButtonClicked) {
			collapseMenus()
		}
	} // handleDocumentClick

	function handleWidgetClick (event) {
		event.stopPropagation() // Don't let document.click trigger, handleDocumentClick() collapses menus.
	} // handleWidgetClick

	function handleLoginLinkClick (event) {
		handleMenuLinkClick(event, '#login-form-container')
	} // handleLoginLinkClick

	function handleMenuLinkClick (event, formContainer) {
		var $formContainer = $(formContainer)
		var formWasOpen = $formContainer.is(':visible')
		collapseMenus()
		if (formWasOpen) {
			return
		}
		$(event.currentTarget).addClass('expanded')
		$formContainer.show()
		$formContainer.find('input').first().focus()
	} // handleMenuLinkClick

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
		var $loginForm = $(event.currentTarget)
		removeOldErrorMessages($loginForm[0])
		var $user = $loginForm.find('#login-user-input')
		if (!$user.val()) {
			displayFormTableErrorMessage($user, "Enter your username or email address.")
			return
		}
		var $password = $loginForm.find('#login-password-input')
		if (!$password.val()) {
			displayFormTableErrorMessage($password, "Enter your password.")
			return
		}
		submitFormViaAjax(event.currentTarget, handleLoginResponse, getClientInfo())
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
} // loadLoginWidget

