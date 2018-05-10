$(printAccountCreationPageInterface)
function printAccountCreationPageInterface () {
	var $localContainer = $('#account-creation-form-container')
	var pageData = window['pageData'] || {}
	if (pageData['nextAllowedAccountCreationTime']) {
		var userTimezone = moment.tz.guess()
		var nextAllowedCreationMoment = moment.tz(pageData['nextAllowedAccountCreationTime'], userTimezone)
		var nextAllowedCreationFormatted = (function(){
			var currentMoment = moment.tz(userTimezone)
			if (currentMoment.day() == nextAllowedCreationMoment.day()) {
				return nextAllowedCreationMoment.format('[at] h:mma z')
			} else if ((currentMoment.day() + 1) == nextAllowedCreationMoment.day()) {
				return nextAllowedCreationMoment.format('[tomorrow at] h:mma z')
			} else {
				return nextAllowedCreationMoment.format('MMM Do [at] h:mma z')
			}
		})()
		$('<p>You can create another account from your IP address '+nextAllowedCreationFormatted+'</p>').appendTo($localContainer)
		return
	}
	confirmBackspaceNavigations()
	var $createAccountForm = $(
		'<form id="account-creation-form" action="/pages/create-account/ajax?action=create_account">'
			+'<table id="account-creation-form-table" class="form-table">'
				+'<colgroup><col id="labels-column"><col id="fields-column"></colgroup>'
				+'<tbody>'
					+'<tr>'
						+'<th><label for="mbti-type">Personality Type</label></th>'
						+'<td><select id="mbti-type" name="mbti_type" aria-required="true" required="">'
							+'<option></option>'
							+'<option>ENFJ</option>'
							+'<option>ENFP</option>'
							+'<option>ENTJ</option>'
							+'<option>ENTP</option>'
							+'<option>ESFJ</option>'
							+'<option>ESFP</option>'
							+'<option>ESTJ</option>'
							+'<option>ESTP</option>'
							+'<option>INFJ</option>'
							+'<option>INFP</option>'
							+'<option>INTJ</option>'
							+'<option>INTP</option>'
							+'<option>ISFJ</option>'
							+'<option>ISFP</option>'
							+'<option>ISTJ</option>'
							+'<option>ISTP</option>'
						+'</select></td>'
					+'</tr><tr>'
						+'<th><label for="username">Username</label></th>'
						+'<td><input id="username" type="text" name="username" aria-required="true" required="" maxlength="20" autocomplete="username" size="12"></td>'
					+'</tr><tr>'
						+'<th><label for="email-address">Email Address</label></th>'
						+'<td><input id="email-address" type="email" name="email" aria-required="true" required="" maxlength="255" autocomplete="email" size="20"></td>'
					+'</tr><tr>'
						+'<th><label for="password">Password</label></th>'
						+'<td><input id="password" type="password" name="password" aria-required="true" required="" autocomplete="new-password" size="15"></td>'
					+'</tr><tr>'
						+'<th><label for="confirm-password">Confirm Password</label></th>'
						+'<td><input id="confirm-password" type="password" name="confirm_password" aria-required="true" required="" autocomplete="new-password" size="15"></td>'
					+'</tr><tr>'
						+'<th>Birthday</th>'
						+'<td>'
							+'<select id="birth-month" name="birth_month" aria-required="true" required="">'
								+'<option value=""></option>'
								+'<option value="1">January</option>'
								+'<option value="2">February</option>'
								+'<option value="3">March</option>'
								+'<option value="4">April</option>'
								+'<option value="5">May</option>'
								+'<option value="6">June</option>'
								+'<option value="7">July</option>'
								+'<option value="8">August</option>'
								+'<option value="9">September</option>'
								+'<option value="10">October</option>'
								+'<option value="11">November</option>'
								+'<option value="12">December</option>'
							+'</select>'
							+' <select id="birth-day" name="birth_day" aria-required="true" required=""><option value=""></option></select>'
							+' <input id="birth-year" type="text" name="birth_year" maxlength="4" aria-required="true" required="" size="4">'
						+'</td>'
					+'</tr><tr>'
						+'<th>Gender</th>'
						+'<td id="gender-field-container">'
							+'<label><input type="radio" name="gender" value="male" aria-required="true" required="">Man</label>'
							+'<label><input type="radio" name="gender" value="female" aria-required="true" required="">Woman</label>'
						+'</td>'
					+'</tr><tr>'
						+'<th>Orientation</th>'
						+'<td id="orientation-field-container">'
							+'<label><input type="radio" name="orientation" value="straight">Straight</label>'
							+'<label><input type="radio" name="orientation" value="gay">Gay</label>'
							+'<label><input type="radio" name="orientation" value="bi">Bi</label>'
						+'</td>'
					+'</tr><tr>'
						+'<th><label for="country">Country</label></th>'
						+'<td><select id="country" name="country" aria-required="true" required=""></select></td>'
					+'</tr><tr id="location-row">'
					+'</tr><tr id="coordinates-row">'
						+'<th><label for="latitude">Latitude</label>, <label for="longitude">Longitude</label></th>'
						+'<td><input id="latitude" type="text" name="latitude" size="10">, <input id="longitude" type="text" name="longitude" size="10"> <span class="field-note">(not shown to others)</span><span class="field-block-note"><a href="https://www.google.com/maps/place/Statue+of+Liberty+National+Monument/@40.6892494,-74.0466891,17z" target="_blank">The Statue of Liberty is at 40.689247, -74.044502</a></span></td>'
					+'</tr>'
				+'</tbody><tfoot>'
					+'<tr>'
						+'<th></th>'
						+'<td><input id="create-account-submit-button" type="submit" value="Create Account"></td>'
					+'</tr>'
				+'</tfoot>'
			+'</table>'
		+'</form>'
	)
	$createAccountForm.on('submit', handleAccountCreationFormSubmit)
	$createAccountForm.appendTo($localContainer)

	;(function printBirthDayOfMonthOptions(){
		var $birthDayOfMonthInput = $('#birth-day')
		for (var i = 1; i <= 31; i++) {
			$birthDayOfMonthInput.append($('<option></option>').val(i).text(i))
		}
	})() // printBirthDayOfMonthOptions

	;(function printCountryListOptions(){
		var countries = window['countries']
		if (!countries || !countries.length) {
			alert("Error loading list of countries.")
			return
		}
		var $countrySelect = $('#country')
		for (var i = 0; i < countries.length; i++) {
			var country = countries[i]
			var countryCode = country['country_code']
			var countryName = country['country_name']
			var $option = $('<option></option>').val(countryCode).text(countryName);
			$option.appendTo($countrySelect)
		}
		$countrySelect.val('US')
		$countrySelect.on('change', handleCountryChange)
		printLocationRowBasedOnCountry()
	})() // printCountryListOptions

	return // functions below
	function handleCountryChange () {
		printLocationRowBasedOnCountry()
	} // handleCountryChange

	function printLocationRowBasedOnCountry () {
		var $locationRow = $('#location-row')
		$locationRow.empty()
		var $country = $('#country')
		var isAmerican = $country.val() == 'US' ? true : false
		if (isAmerican) {
			var $locationRowCells = $(
				'<th><label for="city">City</label>, <label for="state">State</label> <label for="zip-code">Zip Code</label></th>'
				+'<td>'
					+'<input id="city" type="text" name="city" aria-required="true" required="" placeholder="City" size="16">, '
					+'<select name="state" id="state" aria-required="true" required=""><option value=""></option></select> '
					+'<input id="zip-code" type="text" name="zip_code" aria-required="true" required="" placeholder="Zip Code" autocomplete="postal-code" size="5">'
				+'</td>'
			)
			;(function addStates(){
				var $state = $locationRowCells.find('#state')
				for (var i = 0; i < window.states.length; i++) {
					var state = window.states[i]
					var stateName = state['name']
					var stateCode = state['code']
					var $option = $('<option></option>').val(stateCode).text(stateName)
					$option.appendTo($state)
				}
			})() // addStates
			var $zipCode = $locationRowCells.find('#zip-code')
			$zipCode.on({'input': handleZipCodeInput})
			$locationRowCells.appendTo($locationRow)
		} else {
			var $locationRowCells = $(
				'<th><label for="city">City</label>, <label for="state">Province</label> <label for="zip-code">Postal Code</label></th>'
				+'<td>'
					+'<input id="city" type="text" name="city" aria-required="true" required="" placeholder="City" size="16">, '
					+'<input id="state" type="text" name="state" placeholder="Province" size="14"> '
					+'<input id="zip-code" type="text" name="zip_code" placeholder="Postal Code" autocomplete="postal-code" size="9">'
				+'</td>'
			)
			$locationRowCells.appendTo($locationRow)
		}
	} // printLocationRowBasedOnCountry

	function handleZipCodeInput (event) {
		var $zipCode = $(event.currentTarget)
		var zipCode = $zipCode.val()
		if (zipCode.length === 5) {
			setCoordinatesFromZipCode(zipCode)
		}
	} // handleZipCodeInput

	function setCoordinatesFromZipCode (zipCode) {
		var $zipCode = $('#zip-code')
		var $latitude = $('#latitude')
		var $longitude = $('#longitude')
		$zipCode.prop('disabled', true)
		$latitude.prop('disabled', true)
		$longitude.prop('disabled', true)
		apiCall('/ajax/account?action=get_zip_code_coordinates', handleZipCodeCoordinatesResponse, {'zip_code': zipCode})
		return // functions below
		function handleZipCodeCoordinatesResponse (response) {
			$zipCode.prop('disabled', false)
			$latitude.prop('disabled', false)
			$longitude.prop('disabled', false)
			if ($zipCode.val() == response['zip_code']) {
				$latitude.val(response['latitude'])
				$longitude.val(response['longitude'])
			}
		} // handleZipCodeCoordinatesResponse
	} // setCoordinatesFromZipCode

	function handleAccountCreationFormSubmit (event) {
		event.preventDefault()
		removeOldErrorMessages($localContainer[0])
		var $form = $(event.currentTarget)
		if ($form.find('#password').val() != $form.find('#confirm-password').val()) {
			displayFormTableErrorMessage('#confirm-password', "Passwords don't match.")
			return
		}
		var $submitButton = $form.find('#create-account-submit-button')
		submitFormViaAjax($form, handleCreateAccountResponse, getClientInfo())
		return // functions below
		function handleCreateAccountResponse (response) {
			if (response['success']) {
				if (response['verification_email_sent']) {
					alert('A verification email has been sent to your email address.')
				}
				window.location = '/profile'
				return
			}
			if (response['error']) {
				if (response['error_message']) {
					displayFormTableErrorMessage($submitButton, response['error_message'])
				}
				if (response['error_messages']) {
					$.each(response['error_messages'], function(fieldName, errorMessage){
						var $input = $createAccountForm.find('[name='+fieldName+']').first()
						var fieldFound = $input.length > 0
						if (fieldFound) {
							displayFormTableErrorMessage($input, errorMessage);
						} else {
							if (fieldName == 'birth_date') {
								displayFormTableErrorMessage('#birth-year', errorMessage)
							} else {
								displayFormTableErrorMessage($submitButton, errorMessage)
							}
						}
					}) // each
				}
			}
		} // handleCreateAccountResponse
	} // handleAccountCreationFormSubmit
} // printAccountCreationPageInterface
