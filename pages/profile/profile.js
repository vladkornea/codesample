$(printProfilePageInterface)
function printProfilePageInterface () {
	var $localContainer = $('main')
	var pageData = window['pageData']
	if (!pageData) {
		$localContainer.append('<p class="error">Missing page data.</p>')
		return
	}
	if (pageData['isValidUser'] === false) {
		$localContainer.append('<p class="error">No such user.</p>')
		return
	}
	if (pageData['whyCannotViewUser']) {
		$('<div id="cannot-message-notice"></div>').text(pageData['whyCannotViewUser']).appendTo($localContainer)
		return
	}
	var sessionData = pageData['sessionData']
	if (!sessionData) {
		$localContainer.append('<p class="error">Missing session data.</p>')
		return
	}
	var profileData = pageData['profileData']
	if (!profileData) {
		$localContainer.append('<p class="error">Missing form data.</p>')
		return
	}

	if (pageData['isDeactivated']) {
		$localContainer.append('<p class="error">User is deactivated.</p>')
		return
	}

	var profileMode = (getUrlParams()['user_id'] || getUrlParams()['username']) ? 'view' : 'edit'

	var hasChildren = ['yes', 'part-time', 'away'].indexOf( profileData['have_children'] ) !== -1
	var readableValues = {
		'body_type': {
			'':     ''
			,'slender':    'Slender/Slim'
			,'average':    'Average/Medium'
			,'athletic':   'Athletic/Fit'
			,'muscular':   'Muscular/Built'
			,'voluptuous': 'Voluptuous/Curvy'
			,'stocky':     'Stocky/Stout'
			,'plump':      'Plump/Chubby'
			,'large':      'Large/Big'
		}
		,'have_children': {
			'':    ""
			,'no':        "I have no children"
			,'yes':       "They live with me full-time"
			,'part-time': "They live with me part-time"
			,'away':      "They don't live with me"
		}
		,'want_children': {
			'':    ""
			,'no':        'edit' === profileMode ? "I don't want (more) children" : hasChildren ? "I don't want more children" : "I don't want children"
			,'yes':       'edit' === profileMode ? "I want (more) children." : hasChildren ? "I want more children" : "I want children"
			,'undecided': 'edit' === profileMode ? "Undecided about having (more) children" : hasChildren ? "Undecided about having more children" : "Undecided about having children"
		}
		,'would_relocate': {
			'':    ""
			,'no':        "Would not relocate for a relationship"
			,'yes':       "Would relocate for a relationship"
			,'undecided': "Undecided about relocating"
		}
	} // readableValues

	var fieldQuestions = {
		'virtrades':        "If you could increase any qualities in yourself in exchange for decreasing any qualities you like about yourself by the same amount, what would you change?"
		,'self_described':  "What may the people you meet through TypeTango expect from you?"
		,'lover_described': "What will you expect from people you meet through TypeTango?"
	}

	if (profileMode == 'edit') {
		printPhotoCarouselWidget(pageData['photoCarouselData'])
		printProfileForm()
		printKeywordForms()
	}
	if (profileMode == 'view') {
		printPhotoCarouselWidget(pageData['photoCarouselData'])
		printProfileInfo()
		printKeywordsInfo()
		printContactForm()
		printBlockAndReportButtons()
	}

	return // functions below

	function printContactForm () {
		var $contactFormContainer = $('#contact-form-container')
		if ($contactFormContainer.length == 0) {
			$contactFormContainer = $('<div id="contact-form-container"></div>').appendTo($localContainer)
		} else {
			$contactFormContainer.empty()
		}

		var isEmailBouncing = profileData['email_bouncing'] == 'bounced' || profileData['email_bouncing'] == 'complained'
		if (isEmailBouncing || pageData['theyBlockedUs']) {
			$('<div id="cannot-message-notice">Cannot send message to user (usually because their email address is bouncing).</div>').appendTo($contactFormContainer)
			return
		}

		var $form = $(
			'<form id="contact-form" action="/pages/profile/ajax?action=send_message" method="post">'
				+'<div id="contact-form-instructions"></div>'
				+'<textarea name="message_text" cols="64" rows="6" placeholder="Write a Message (limit 65,000 characters)"></textarea>'
				+'<input type="hidden" name="to_user_id">'
				+'<input type="submit" value="Send Message">'
				+'<table id="conversation"></table>'
			+'</form>'
		)
		var nextSendAllowedAt = pageData['nextSendAllowedAt']
		var userPreviouslyContacted = $.isEmptyObject(pageData['conversation']) ? false : true
		if (nextSendAllowedAt && !userPreviouslyContacted) {
			var userTimezone = moment.tz.guess()
			var nextAllowedSendMoment = moment.tz(nextSendAllowedAt, userTimezone)
			var currentMoment = moment.tz(userTimezone)
			var nextAllowedSendFormatted = (function(){
				if (currentMoment.day() == nextAllowedSendMoment.day()) {
					return nextAllowedSendMoment.format('[at] h:mma z')
				} else if ((currentMoment.day() + 1) == nextAllowedSendMoment.day()) {
					return nextAllowedSendMoment.format('[tomorrow at] h:mma z')
				} else {
					return nextAllowedSendMoment.format('MMM Do [at] h:mma z')
				}
			})()
			$form.find('[name="message_text"]').attr('disabled', 'disabled')
			$form.find('[type="submit"]').attr('disabled', 'disabled')
			$form.find('#contact-form-instructions').text("You can send a new message " +nextAllowedSendFormatted)
		} else {
			if (!userPreviouslyContacted) {
				$form.find('#contact-form-instructions').text("You can only initiate contact once per day, so make your messages count.")
			} else {
				$form.find('#contact-form-instructions').text("Conversation messages are listed latest-first.")
			}
		}

		$form.submit(handleContactFormSubmit)
		$form.find('input[name="to_user_id"]').val(profileData['user_id'])
		$form.appendTo($contactFormContainer)
		addConversation()
		return // functions below
		function addConversation () {
			var conversation = pageData['conversation']
			var $conversation = $('#conversation')
			var ourUsername = sessionData['username']
			var ourUserId = sessionData['user_id']
			var theirUsername = profileData['username']
			for (var i = 0; i < conversation.length; i++) {
				var messageData = conversation[i]
				var senderUserId = messageData['from_user_id']
				var senderUsername = senderUserId == ourUserId ? ourUsername : theirUsername
				var messageText = messageData['message_text']
				var $userMessage = $('<tr class="user-message"><td class="sender-username"></td><td class="message-text"></td></tr>')
				$userMessage.find('.sender-username').text(senderUsername)
				$userMessage.find('.message-text').text(messageText)
				$userMessage.appendTo($conversation)
			}
		} // addConversation

		function handleContactFormSubmit (event) {
			event.preventDefault()
			if (!$(event.currentTarget).find('[name="message_text"]').val()) {
				return
			}
			submitFormViaAjax(event.currentTarget, handleContactFormResponse)
			return // functions below
			function handleContactFormResponse (response) {
				var nextSendAllowedAtTimestamp = response['next_send_allowed_at_timestamp']
				if (nextSendAllowedAtTimestamp) {
					var userTimezone = moment.tz.guess()
					var nextAllowedSendMoment = moment.tz(nextSendAllowedAtTimestamp, userTimezone)
					var currentMoment = moment.tz(userTimezone)
					var nextAllowedSendFormatted = (function(){
						if (currentMoment.day() == nextAllowedSendMoment.day()) {
							return nextAllowedSendMoment.format('h:mma z')
						} else if ((currentMoment.day() + 1) == nextAllowedSendMoment.day()) {
							return 'tomorrow at ' +nextAllowedSendMoment.format('h:mma z')
						} else {
							return nextAllowedSendMoment.format('MMM Do [at] h:mma z')
						}

					})()
					alert("Cannot send a message to a new user until " +nextAllowedSendFormatted)
					return
				}
				if (response['error_message']) {
					alert(response['error_message'])
				} else {
					pageData['conversation'] = response['conversation']
					printContactForm()
				}
			} // handleContactFormResponse
		} // handleContactFormSubmit
	} // printContactForm

	function printBlockAndReportButtons () {
		var $buttonsContainer = $('#block-and-report-buttons-container')
		if ($buttonsContainer.length == 0) {
			$buttonsContainer = $('<div id="block-and-report-buttons-container"></div>').appendTo($localContainer)
		} else {
			$buttonsContainer.empty()
		}
		var isBlocked = pageData['isBlocked']
		if (isBlocked) {
			$('<input type="button" value="Unblock">').click(handleUnblockButtonClick).appendTo($buttonsContainer)
		} else {
			$('<input type="button" value="Block">').click(handleBlockButtonClick).appendTo($buttonsContainer)
		}
		var isReported = pageData['isReported']
		if (isReported) {
			$('<input type="button" value="Unreport">').click(handleUnreportButtonClick).appendTo($buttonsContainer)
		} else {
			$('<input type="button" value="Report">').click(handleReportButtonClick).appendTo($buttonsContainer)
		}
		return // functions below
		function handleBlockButtonClick () {
			apiCall('/pages/profile/ajax?action=block', handleBlockResponse, {'user_id': profileData['user_id']})
			return // functions below
			function handleBlockResponse (response) {
				if (response['success']) {
					pageData['isBlocked'] = true
				}
				printBlockAndReportButtons()
			} // handleBlockResponse
		} // handleBlockButtonClick

		function handleReportButtonClick () {
			apiCall('/pages/profile/ajax?action=report', handleReportResponse, {'user_id': profileData['user_id']})
			return // functions below
			function handleReportResponse (response) {
				if (response['success']) {
					pageData['isReported'] = true
				}
				printBlockAndReportButtons()
			} // handleReportResponse
		} // handleReportButtonClick

		function handleUnblockButtonClick () {
			apiCall('/pages/profile/ajax?action=unblock', handleUnblockResponse, {'user_id': profileData['user_id']})
			return // functions below
			function handleUnblockResponse (response) {
				if (response['success']) {
					pageData['isBlocked'] = false
				}
				printBlockAndReportButtons()
			} // handleUnblockResponse
		} // handleUnblockButtonClick

		function handleUnreportButtonClick () {
			apiCall('/pages/profile/ajax?action=unreport', handleUnreportResponse, {'user_id': profileData['user_id']})
			return // functions below
			function handleUnreportResponse (response) {
				if (response['success']) {
					pageData['isReported'] = false
				}
				printBlockAndReportButtons()
			} // handleUnreportResponse
		} // handleUnreportButtonClick
	} // printBlockAndReportButtons

	function printProfileInfo () {
		var $profileInfo = $(
			'<div id="profile-info-container">'
				+'<table id="profile-info-table" class="form-table"></table>'
			+'</div>'
		)
		$profileInfo.appendTo($localContainer)
		var $profileInfoTable = $profileInfo.find('#profile-info-table')

		// basics
		if (profileData['description']) {
			$(
				'<tr>'
					+'<th>Basics</th>'
					+'<td id="basics-text"></td>'
				+'</tr>'
			).appendTo($profileInfoTable).find('#basics-text').text(profileData['description'])
		}

		// physical
		;(function printPhysicalDetails(){
			var heightInInches = profileData['height_in_in']
			var feet = Math.floor(heightInInches / 12)
			var inches = heightInInches - (feet * 12)
			var heightInCm = Math.round(heightInInches * 2.54)
			var height = feet ? (feet +"' " +inches +'" (' +heightInCm +' cm)') : ''

			var weightInKg = profileData['weight_in_kg']
			var weightInLbs = Math.round(2.204623 * weightInKg)
			var weight = weightInLbs ? (weightInLbs +' lbs (' +weightInKg +' kg)') : ''

			var bodyType = readableValues['body_type'][profileData['body_type']] || ''
			var physicalText = (height +"\n" +weight +"\n" +bodyType).trim()
			if (physicalText) {
				$('<tr>'
					+'<th>Physical</th>'
					+'<td id="physical-text"></td>'
				+'</tr>').appendTo($profileInfoTable).find('#physical-text').text(physicalText)
			}
		})()

		// children
		var haveChildren = readableValues['have_children'][profileData['have_children']] || ''
		var wantChildren = readableValues['want_children'][profileData['want_children']] || ''
		if (haveChildren || wantChildren) {
			var childrenText = ''
			if (haveChildren && wantChildren) {
				childrenText = (haveChildren +". / " +wantChildren +'.').trim()
			} else {
				if (haveChildren) {
					childrenText = haveChildren
				}
				if (wantChildren) {
					childrenText = wantChildren
				}
			}
			$('<tr>'
				+'<th>Children</th>'
				+'<td id="children-text"></td>'
			+'</tr>').appendTo('#profile-info-table').find('#children-text').text(childrenText)
		}

		// last visit
		var lastVisitText = profileData['last_visit']
		if (lastVisitText) {
			$('<tr>'
				+'<th>Last Visit</th>'
				+'<td id="last-visit-text"></td>'
			+'</tr>').appendTo($profileInfoTable).find('#last-visit-text').text(lastVisitText)
		}

		// text areas
		if (profileData['self_described'] || profileData['lover_described'] || profileData['virtrades']) {
			;(function printAnsweredQuestions(){
				var $descriptions = $('<table id="descriptions"><thead><tr></tr></thead><tbody><tr></tr></tbody></table>')
				if (profileData['self_described']) {
					$('<th id="self_described-question"></th>').text(fieldQuestions['self_described']).appendTo($descriptions.find('thead > tr'))
					$('<td id="self_described-text"></td>').text(profileData['self_described']).appendTo($descriptions.find('tbody > tr'))
				}
				if (profileData['lover_described']) {
					$('<th id="lover_described-question"></th>').text(fieldQuestions['lover_described']).appendTo($descriptions.find('thead > tr'))
					$('<td id="lover_described-text"></td>').text(profileData['lover_described']).appendTo($descriptions.find('tbody > tr'))
				}
				if (profileData['virtrades']) {
					$('<th id="virtrades-question"></th>').text(fieldQuestions['virtrades']).appendTo($descriptions.find('thead > tr'))
					$('<td id="virtrades-text"></td>').text(profileData['virtrades']).appendTo($descriptions.find('tbody > tr'))
				}
				$descriptions.appendTo('#profile-info-container')
			})() // printAnsweredQuestions
		}
	} // printProfileInfo

	function printKeywordsInfo () {
		var positiveKeywords = profileData['positive_keywords']
		var negativeKeywords = profileData['negative_keywords']
		if ($.isEmptyObject(negativeKeywords) && $.isEmptyObject(positiveKeywords)) {
			return
		}
		var $keywordsInfoTable = $(
			'<table id="keywords-info-table">'
				+'<thead><tr></tr></thead>'
				+'<tbody><tr></tr></tbody>'
			+'</table>'
		).appendTo($localContainer)
		if (!$.isEmptyObject(positiveKeywords)) {
			$('<th>Positive Keywords</th>').appendTo($keywordsInfoTable.find('thead > tr'))
			var $positiveKeywordsCell = $('<td id="positive-keywords"></td>').appendTo($keywordsInfoTable.find('tbody > tr'))
			;(function appendKeywords(){
				for (var keyword in positiveKeywords) {
					var weight = positiveKeywords[keyword]
					var text = keyword
					var spanClass = ''
					if (weight !== null) {
						text += ' ('+weight+')';
						if (weight < 0) {
							spanClass = 'negative-keyword'
						}
						if (weight > 0) {
							spanClass = 'positive-keyword'
						}
					}
					var $keyword = $('<span class="keyword"></span>').text(text).addClass(spanClass)
					$keyword.appendTo($positiveKeywordsCell)
				}
			})() // appendKeywords
		}
		if (!$.isEmptyObject(negativeKeywords)) {
			$('<th>Negative Keywords</th>').appendTo($keywordsInfoTable.find('thead > tr'))
			var $negativeKeywordsCell = $('<td id="negative-keywords"></td>').appendTo($keywordsInfoTable.find('tbody > tr'))
			;(function appendKeywords(){
				for (var keyword in negativeKeywords) {
					var weight = negativeKeywords[keyword]
					var text = keyword
					var spanClass = ''
					if (weight !== null) {
						weight *= -1
						text += ' ('+weight+')';
						if (weight > 0) {
							spanClass = 'negative-keyword'
						}
						if (weight < 0) {
							spanClass = 'positive-keyword'
						}
					}
					var $keyword = $('<span class="keyword"></span>').text(text).addClass(spanClass)
					$keyword.appendTo($negativeKeywordsCell)
				}
			})() // appendKeywords
		}
	} // printKeywordsInfo

	function printKeywordForms () {
		var $keywordFormsContainer = $('<div id="keywords-forms-container"></div>').appendTo($localContainer)
		var $positiveKeywordsForm = $(
			'<form id="positive-keywords-form" action="/pages/profile/ajax?action=save_positive_keywords">'
				+'<table>'
					+'<thead><tr><th>Positive Keyword</th><th>Weight</th></tr></thead>'
					+'<tbody></tbody>'
					+'<tfoot><tr><td colspan="2"><input type="submit" value="Sort"></td></tr></tfoot>'
				+'</table>'
			+'</form>'
		)
		$positiveKeywordsForm.appendTo($keywordFormsContainer)
		configKeywordsForm(pageData['profileData']['positive_keywords'], $positiveKeywordsForm)
		var $negativeKeywordsForm = $(
			'<form id="negative-keywords-form" action="/pages/profile/ajax?action=save_negative_keywords">'
				+'<table>'
					+'<thead><tr><th>Negative Keyword</th><th>Weight</th></tr></thead>'
					+'<tbody></tbody>'
					+'<tfoot><tr><td colspan="2"><input type="submit" value="Sort"></td></tr></tfoot>'
				+'</table>'
			+'</form>'
		)
		configKeywordsForm(pageData['profileData']['negative_keywords'], $negativeKeywordsForm)
		$negativeKeywordsForm.appendTo($keywordFormsContainer)
		return // functions below
		function configKeywordsForm (keywords, $keywordsForm) {
			var $keywordRowsContainer = $keywordsForm.find('tbody')
			$keywordRowsContainer.empty()
			;(function printKeywordFormRows(){
				for (var i = 0; i < keywords.length; i++) {
					printKeywordFormRow(keywords[i]['keyword'], keywords[i]['weight'])
				}
			})()
			$keywordsForm.one('submit', handleKeywordsFormSubmit)
			ensureThereAreAtLeast3EmptyTextInputsForAddingNewKeywords()
			return // functions below
			function printKeywordFormRow (loopKeyword, loopKeywordWeight) {
				var $loopRow = $('<tr><td class="keyword"><input type="text" name="keyword" maxlength="30" size="18"></td><td class="keyword-weight"><input type="text" name="keyword_weight" maxlength="3" size="3"></td></tr>')
				$loopRow.find('[name="keyword"]').val(loopKeyword).on('blur', handleKeywordTextInputBlur).on('focus', handleKeywordTextInputFocus)
				$loopRow.find('[name="keyword_weight"]').val(loopKeywordWeight).on('blur', handleKeywordTextInputBlur).on('focus', handleKeywordTextInputFocus)
				$loopRow.appendTo($keywordRowsContainer)
			} // printKeywordFormRow

			function handleKeywordTextInputFocus (event) {
				rememberOriginalTextInputValue(event.currentTarget)
			} //  handleKeywordTextInputFocus

			function rememberOriginalTextInputValue (textInputElement) {
				var $textInputElement = $(textInputElement)
				var originalValue = $textInputElement.val()
				$textInputElement.data('originalValue', originalValue)
			} // rememberOriginalTextInputValue

			function handleKeywordTextInputBlur (event) {
				var $textInputElement = $(event.currentTarget)
				var originalTextInputValue = $textInputElement.data('originalValue')
				var currentTextInputValue = $textInputElement.val()
				if (originalTextInputValue != currentTextInputValue) {
					updateKeyword(event.currentTarget)
				}
			} // handleKeywordTextInputBlur

			function ensureThereAreAtLeast3EmptyTextInputsForAddingNewKeywords () {
				var currentNumberOfEmptyTextInputs = (function(){
					var currentNumberOfEmptyTextInputs = 0
					var $keywordInputs = $keywordsForm.find('input[type=text][name=keyword]')
					$keywordInputs.each(function(index, element){
						if (!$(element).val()) {
							currentNumberOfEmptyTextInputs++
						}
					})
					return currentNumberOfEmptyTextInputs;
				})()
				while (currentNumberOfEmptyTextInputs < 3) {
					printKeywordFormRow('', 1)
					currentNumberOfEmptyTextInputs++
				}
			} // ensureThereAreAtLeast3EmptyTextInputsForAddingNewKeywords

			function updateKeyword (inputElement, callback) {
				var $inputElementsRow = $(inputElement).closest('tr')
				var $keywordInput = $inputElementsRow.find('input[name="keyword"]')
				var $keywordWeightInput = $inputElementsRow.find('input[name="keyword_weight"]')
				var newKeyword = $keywordInput.val()
				var oldKeyword = $keywordInput.data('originalValue')
				var newKeywordWeight = $keywordWeightInput.val()
				var oldKeywordWeight = $keywordInput.data('originalValue')
				var isUnchanged = newKeyword == oldKeyword && newKeywordWeight == oldKeywordWeight
				if (isUnchanged) {
					return
				}
				var action = $inputElementsRow.closest('form').is('#positive-keywords-form') ? 'update_positive_keyword' : 'update_negative_keyword'
				var url = '/pages/profile/ajax?action=' +action
				var postData = {'new_keyword':newKeyword, 'old_keyword':oldKeyword, 'new_keyword_weight':newKeywordWeight}
				apiCall(url, handleSaveKeywordResponse, postData)
				return // functions below
				function handleSaveKeywordResponse (response) {
					ensureThereAreAtLeast3EmptyTextInputsForAddingNewKeywords()
					if (callback) {
						callback(response)
					}
					if (!response['error']) {
						return
					}
				} // handleSaveKeywordResponse
			} // updateKeyword

			function handleKeywordsFormSubmit (event) {
				event.preventDefault()
				saveAndSortKeywords()
				return // functions below
				function saveAndSortKeywords () {
					var keywordsData = []
					var $keywordRows = $keywordsForm.find("tbody > tr")
					$keywordRows.each(function(i, element){
						var $keywordRow = $(element)
						var $keywordInput = $keywordRow.find('[name="keyword"]')
						var $keywordWeightInput = $keywordRow.find('[name="keyword_weight"]')
						var keyword = $keywordInput.val()
						var keywordWeight = $keywordWeightInput.val()
						var keywordRecord = {'keyword':keyword, 'weight':keywordWeight}
						keywordsData.push(keywordRecord)
					})
					apiCall($keywordsForm.attr('action'), handleSaveAndSortKeywordsResponse, {'keywords':keywordsData})
					return // functions below
					function handleSaveAndSortKeywordsResponse (response) {
						var keywords = response['keywords']
						configKeywordsForm(keywords, $keywordsForm)
					} // handleSaveAndSortKeywordsResponse
				} // handleKeywordUpdated
			} // handleKeywordsFormSubmit
		} // configKeywordsForm
	} // printKeywordForms

	function printProfileForm () {
		var $profileForm = $(
			'<form id="profile-form" action="/pages/profile/ajax?action=save_profile" method="post">'
				+'<table id="profile-form-structure" class="form-table">'
					+'<tr>'
						+'<th><label for="share_keywords-select">Share Keywords</label> <span id="share_keywords-help">(?)</span></th>'
						+'<td>'
							+'<select name="share_keywords" id="share_keywords-select">'
								+'<option value="1">Show all keywords (recommended)</option>'
								+'<option value="0">Show only keywords shared with browsing user</option>'
							+'</select>'
						+'</td>'
					+'</tr><tr>'
						+'<th><label for="mbti-type">Personality Type</label></th>'
						+'<td>'
							+'<select id="mbti-type" name="mbti_type" aria-required="true" required="">'
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
							+'</select>'
						+'</td>'
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
							+' <input id="birth-year" type="text" name="birth_year" size="4" maxlength="4" aria-required="true" required="">'
						+'</td>'
					+'</tr><tr>'
						+'<th>Gender</th>'
						+'<td id="gender-field-container">'
							+'<label><input type="radio" name="gender" value="male" aria-required="true" required=""><span class="label">Man</span></label>'
							+'<label><input type="radio" name="gender" value="female" aria-required="true" required=""><span class="label">Woman</span></label>'
						+'</td>'
					+'</tr><tr>'
						+'<th>Orientation</th>'
						+'<td id="orientation-field-container">'
							+'<label><input type="radio" name="orientation" value="straight"><span class="label">Straight</span></label>'
							+'<label><input type="radio" name="orientation" value="gay"><span class="label">Gay</span></label>'
							+'<label><input type="radio" name="orientation" value="bi"><span class="label">Bi</span></label>'
						+'</td>'
					+'</tr><tr>'
						+'<th><label for="height_in_in_select">Height</label></th>'
						+'<td>'
							+'<select id="height_in_in_select" name="height_in_in">'
								+'<option value="0">Unknown</option>'
								+'<option value="54">4\' 6" (137 cm)</option>'
								+'<option value="55">4\' 7" (140 cm)</option>'
								+'<option value="56">4\' 8" (142 cm)</option>'
								+'<option value="57">4\' 9" (145 cm)</option>'
								+'<option value="58">4\' 10" (147 cm)</option>'
								+'<option value="59">4\' 11" (150 cm)</option>'
								+'<option value="60">5\' 0" (152 cm)</option>'
								+'<option value="61">5\' 1" (155 cm)</option>'
								+'<option value="62">5\' 2" (157 cm)</option>'
								+'<option value="63">5\' 3" (160 cm)</option>'
								+'<option value="64">5\' 4" (163 cm)</option>'
								+'<option value="65">5\' 5" (165 cm)</option>'
								+'<option value="66">5\' 6" (168 cm)</option>'
								+'<option value="67">5\' 7" (170 cm)</option>'
								+'<option value="68">5\' 8" (173 cm)</option>'
								+'<option value="69">5\' 9" (175 cm)</option>'
								+'<option value="70">5\' 10" (178 cm)</option>'
								+'<option value="71">5\' 11" (180 cm)</option>'
								+'<option value="72">6\' 0" (183 cm)</option>'
								+'<option value="73">6\' 1" (185 cm)</option>'
								+'<option value="74">6\' 2" (188 cm)</option>'
								+'<option value="75">6\' 3" (191 cm)</option>'
								+'<option value="76">6\' 4" (193 cm)</option>'
								+'<option value="77">6\' 5" (196 cm)</option>'
								+'<option value="78">6\' 6" (198 cm)</option>'
								+'<option value="79">6\' 7" (201 cm)</option>'
								+'<option value="80">6\' 8" (203 cm)</option>'
								+'<option value="81">6\' 9" (206 cm)</option>'
								+'<option value="82">6\' 10" (208 cm)</option>'
								+'<option value="83">6\' 11" (211 cm)</option>'
								+'<option value="84">7\' 0" (213 cm)</option>'
							+'</select>'
						+'</td>'
					+'</tr><tr>'
						+'<th><label for="weight_in_kg-select">Weight</label></th>'
						+'<td><select id="weight_in_kg-select" name="weight_in_kg"></select></td>'
					+'</tr><tr>'
						+'<th><label for="body_type-select">Body Type</label></th>'
						+'<td><select id="body_type-select" name="body_type"></select></td>'
					+'</tr><tr>'
						+'<th><label for="have_children-select">Do you have children?</label></th>'
						+'<td><select id="have_children-select" name="have_children"></select></td>'
					+'</tr><tr>'
						+'<th></th>'
						+'<td><select id="want_children-select" name="want_children"></select></td>'
					+'</tr><tr>'
						+'<th><label for="country">Country</label></th>'
						+'<td><select id="country" name="country" aria-required="true" required=""></select></td>'
					+'</tr><tr id="location-row">'
					+'</tr><tr id="coordinates-row">'
						+'<th><label for="latitude">Latitude</label>, <label for="longitude">Longitude</label></th>'
						+'<td><input id="latitude" type="text" name="latitude" size="10">, <input id="longitude" type="text" name="longitude" size="10"> <span class="field-note">(not shown to others)</span><div class="field-block-note"><a rel="external nofollow" href="https://www.google.com/maps/place/Statue+of+Liberty+National+Monument/@40.6892494,-74.0466891,17z" target="_blank">The Statue of Liberty is at 40.689247, -74.044502</a></div></td>'
					+'</tr><tr>'
						+'<th><label for="would_relocate-select">Would Relocate</label></th>'
						+'<td><select id="would_relocate-select" name="would_relocate"></select></td>'
					+'</tr><tr>'
						+'<th><label id="self_described-label" for="self_described-textarea" class="block-label"></label></th>'
						+'<td><textarea id="self_described-textarea" name="self_described" rows="7" cols="40" placeholder="(limit 65,000 characters)" maxlength="65000"></textarea></td>'
					+'</tr><tr>'
						+'<th><label id="lover_described-label" for="lover_described-textarea" class="block-label"></label></th>'
						+'<td><textarea id="lover_described-textarea" name="lover_described" rows="7" cols="40" placeholder="(limit 65,000 characters)" maxlength="65000"></textarea></td>'
					+'</tr><tr>'
						+'<th><label id="virtrades-label" for="virtrades-textarea" class="block-label">If you could increase any qualities in yourself in exchange for decreasing any qualities you like about yourself by the same amount, what would you change?</label></th>'
						+'<td><textarea id="virtrades-textarea" name="virtrades" rows="7" cols="40" placeholder="(limit 65,000 characters)" maxlength="65000"></textarea></td>'
					+'</tr><tr>'
						+'<th></th>'
						+'<td><input id="profile-submit-button" type="submit" value="Save Profile"></td>'
					+'</tr>'
				+'</table>'
			+'</form>'
		)
		$profileForm.on('submit', handleProfileFormSubmit)
		$profileForm.appendTo($localContainer)

		var $bodyTypeSelect = $('#body_type-select')
		$.each(readableValues['body_type'], function(value, valueText) {
			$('<option></option>').val(value).text(valueText).appendTo($bodyTypeSelect)
		})
		var $haveChildrenSelect = $('#have_children-select')
		$.each(readableValues['have_children'], function(value, valueText) {
			$('<option></option>').val(value).text(valueText).appendTo($haveChildrenSelect)
		})
		var $wantChildrenSelect = $('#want_children-select')
		$.each(readableValues['want_children'], function(value, valueText) {
			$('<option></option>').val(value).text(valueText).appendTo($wantChildrenSelect)
		})
		var $wouldLocateSelect = $('#would_relocate-select')
		$.each(readableValues['would_relocate'], function(value, valueText) {
			$('<option></option>').val(value).text(valueText).appendTo($wouldLocateSelect)
		})
		$('#self_described-label').text(fieldQuestions['self_described'])
		$('#lover_described-label').text(fieldQuestions['lover_described'])
		$('#virtrades-label').text(fieldQuestions['virtrades'])

		;(function printWeightOptions() {
			var $weightSelect = $('#weight_in_kg-select')
			$('<option value="0">Unknown</option>').appendTo($weightSelect)
			for (var weightInKg = 35; weightInKg <= 200; weightInKg++) {
				var $weightOption = $('<option></option>')
				var weightInLbs = roundWithPrecision(2.204623 * weightInKg, 0)
				var optionText = weightInLbs +' lbs (' +weightInKg +' kgs)'
				$weightOption
					.val(weightInKg)
					.text(optionText)
					.appendTo($weightSelect)
			}
		})() // printWeightOptions

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
		})() // printCountryListOptions

		$('#share_keywords-help').click(function(){alert("If you choose to restrict keywords, other users will be able to see only those of your keywords which they entered as well, and vice-versa. If you don't choose to restrict keywords, other users will see all of your keywords, and you'll be able to see all of theirs.")})

		;(function prefillCurrentFormValues(){
			var defaultFormData = pageData['profileData']
			$('#country').val(defaultFormData['country'])
			printLocationRowBasedOnCountry()
			setNamedFormData($profileForm, defaultFormData)
		})() // prefillCurrentFormValues
	} // printProfileForm


	function handleProfileFormSubmit (event) {
		event.preventDefault()
		removeOldErrorMessages($localContainer[0])
		var $profileForm = $(event.currentTarget)
		submitFormViaAjax($profileForm, handleUpdateProfileResponse)
		return // functions below
		function handleUpdateProfileResponse (response) {
			if (response['success']) {
				location.reload()
				return
			}
			if (response['error']) {
				var $submitButton = $('#profile-submit-button')
				if (response['error_message']) {
					displayFormTableErrorMessage($submitButton, response['error_message'])
				}
				if (response['error_messages']) {
					$.each(response['error_messages'], function (fieldName, errorMessage) {
						var $input = $profileForm.find('[name='+fieldName+']').first()
						var fieldFound = $input.length > 0
						if (fieldFound) {
							displayFormTableErrorMessage($input, errorMessage)
						} else {
							if (fieldName == 'birth_date') {
								displayFormTableErrorMessage('#birth-year', errorMessage)
							} else {
								displayFormTableErrorMessage($submitButton, errorMessage)
							}
						}
					}) // each
				}
				if (!response['error_message'] && !response['error_messages']) {
					alert('Unknown error saving form data.')
				}
			}
		} // handleUpdateProfileResponse
	} // handleProfileFormSubmit

	function handleCountryChange () {
		printLocationRowBasedOnCountry()
	} // handleCountryChange

	function printLocationRowBasedOnCountry () {
		var $countrySelect = $('#country')
		var $locationRow = $('#location-row')
		var countryCode = $countrySelect.val()
		var isAmerican = countryCode == 'US' ? true : false
		$locationRow.empty()
		var $locationRowCells
		if (!isAmerican) {
			$locationRowCells = $(
				'<th><label for="city">City</label>, <label for="state">Province</label> <label for="zip_code">Postal Code</label></th>'
				+'<td>'
					+'<input id="city" type="text" name="city" aria-required="true" required="" placeholder="City" size="16">, '
					+'<input id="province" type="text" name="state" placeholder="Province" size="14"> '
					+'<input id="postal-code" type="text" name="zip_code" placeholder="Postal Code" autocomplete="postal-code" size="9">'
				+'</td>'
			)
			$locationRowCells.appendTo($locationRow)
		}
		if (isAmerican) {
			$locationRowCells = $(
				'<th><label for="city">City</label>, <label for="state">State</label> <label for="zip-code">Zip</label></th>'
				+'<td>'
					+'<input id="city" type="text" name="city" aria-required="true" required="" placeholder="City" size="16">, '
					+'<select id="state" name="state" aria-required="true" required=""><option value=""></option></select> '
					+'<input id="zip-code" type="text" name="zip_code" aria-required="true" required="" placeholder="Zip" autocomplete="postal-code" size="5" maxlength="5">'
				+'</td>'
			)
			;(function addStates(){
				var $state = $locationRowCells.find('#state')
				for (var i = 0; i < window['states'].length; i++) {
					var state = window['states'][i]
					var stateName = state['name']
					var stateCode = state['code']
					var $option = $('<option></option>').val(stateCode).text(stateName)
					$option.appendTo($state)
				}
			})() // addStates
			var $zipCode = $locationRowCells.find('#zip-code')
			$zipCode.on({'input': handleZipCodeInput})
			$locationRowCells.appendTo($locationRow)
		}
		var userSelectedOriginalCountryAgain = profileData['country'] == countryCode
		if (userSelectedOriginalCountryAgain) {
			if (countryCode == 'US') {
				$('#city').val(profileData['city'])
				$('#state').val(profileData['state'])
				$('#zip-code').val(profileData['zip_code'])
			}
			if (countryCode != 'US') {
				$('#city').val(profileData['city'])
				$('#province').val(profileData['state'])
				$('#postal-code').val(profileData['zip_code'])
			}
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
			var zipCode = response['zip_code']
			var latitude = response['latitude']
			var longitude = response['longitude']
			if ($zipCode.val() === zipCode) {
				$latitude.val(latitude)
				$longitude.val(longitude)
			}
			$zipCode.prop('disabled', false)
			$latitude.prop('disabled', false)
			$longitude.prop('disabled', false)
		} // handleZipCodeCoordinatesResponse
	} // setCoordinatesFromZipCode
} // printProfilePageInterface

function printPhotoCarouselWidget (photoCarouselData) {
	var carouselPhotos = photoCarouselData['photos']
	var urlParams = getUrlParams()
	var carouselMode = (urlParams['user_id'] || urlParams['username']) ? 'view' : 'edit'
	if (carouselMode == 'view' && !carouselPhotos.length) {
		return // no photos in view mode means no carousel
	}
	var hashParams = getHashParams()

	// Print HTML and attach event handlers
	var $document = $(document)
	var $localContainer = $('<div id="photo-carousel-widget-container"></div>').prependTo('main')
	var $carouselWidget = $(
		'<div id="photo-carousel-widget">'
			+'<div id="carousel-thumbnail-area" class="structural">'
				+'<span id="selected-photo-orbit"></span>'
			+'</div>'
		+'</div>'
	).appendTo($localContainer)
	$carouselWidget.addClass(carouselMode == 'edit' ? 'edit-mode' : 'view-mode')
	var $carouselThumbnailArea = $('#carousel-thumbnail-area')

	;(function printCarouselThumbnails() {
		if ( ! carouselPhotos ) {
			return
		}
		if ( 1 === carouselPhotos.length && 'view' === carouselMode ) {
			return
		}
		for ( var i = 0; i < carouselPhotos.length; i++ ) {
			var photoData = carouselPhotos[i]
			var photoId = parseInt( photoData['photo_id'] )
			var photoRotateAngle = photoData['rotate_angle'] || 0
			var $photoCarouselThumbnailLink = $('<a class="photo-carousel-thumbnail-link"><img class="photo-carousel-thumbnail"></a>')
			var $photoCarouselThumbnail = $photoCarouselThumbnailLink.find('.photo-carousel-thumbnail')
			$photoCarouselThumbnailLink
				.data('photo_id', photoId)
				.attr('href', '#photo_id='+photoId)
				.on('click', handleThumbnailClick)
			$photoCarouselThumbnail.attr({
				'src':     photoData['thumbnail_url']
				,'width':  photoData['thumbnail_width']
				,'height': photoData['thumbnail_height']
				,'alt':    photoData['caption'] || photoData['uploaded'] || 'Untitled Thumbnail'
			}).css( 'transform', 'rotate(' +photoRotateAngle +'deg)' )
			if ( 'edit' === carouselMode ) {
				$photoCarouselThumbnailLink.attr('draggable', 'false')
				$photoCarouselThumbnail.attr('draggable', 'false')
				$photoCarouselThumbnailLink.on('mousedown', handleThumbnailLinkMouseDown)
			}
			$photoCarouselThumbnailLink.appendTo($carouselThumbnailArea)
		}
	})() // printCarouselThumbnails

	;(function printUploadPhotoForm() {
		if (carouselMode != 'edit') {
			return
		}
		var $newPhotoUploadForm = $(
			'<form id="photo-upload-form" enctype="multipart/form-data">'
				+'<table class="form-table">'
					+'<tbody>'
						+'<tr>'
							+'<th><label for="photo-file-input">New Photo</label></th>'
							+'<td><input id="photo-file-input" type="file" name="photo_files[]" accept="image/*" multiple></td>'
						+'</tr><tr>'
							+'<th></th>'
							+'<td><input id="photo-carousel-form-submit-button" type="submit" value="Upload Photo"></td>'
						+'<tr>'
					+'</tbody>'
				+'</table>'
			+'</form>'
		)
		$newPhotoUploadForm.on('submit', handlePhotoUploadFormSubmit)
		$newPhotoUploadForm.appendTo($carouselThumbnailArea)
	})() // printUploadPhotoForm

	$document.on('keydown', handleDocumentKeydown)

	$(window).on('hashchange', handleHashParamsChange)
	processHashParams()

	return // functions below

	function handleThumbnailClick (event) {
		var $thumbnail = $(event.currentTarget)
		if ($thumbnail.is('.selected')) {
			return true
		}
		if (!confirmLocalNavigation()) {
			return false
		}
	} // handleThumbnailClick

	function confirmLocalNavigation () {
		if ( 'view' === carouselMode ) {
			return true
		}
		var $editSelectedPhotoForm = $('#edit-selected-photo-form')
		var $captionTextarea = $('#selected-photo-caption')
		var captionChanged = $captionTextarea.val() !== $captionTextarea.data('original_caption')
		if ( captionChanged ) {
			return confirm("You have unsaved changes to your photo caption. Lose them now?")
		}
		var $rotateAngleInput = $editSelectedPhotoForm.find('input[name=rotate_angle]')
		var rotateAngleChanged = $rotateAngleInput.val() !== $rotateAngleInput.data('original_rotate_angle')
		if ( rotateAngleChanged ) {
			$editSelectedPhotoForm.submit()
		}
		return true
	} // confirmLocalNavigation

	function handleDocumentKeydown (event) {
		var $targetElement = $(event.target)
		;(function tryNavigating () {
			var isSpecialKeyPressed = event.shiftKey || event.ctrlKey || event.altKey || event.metaKey
			if (isSpecialKeyPressed) {
				return
			}
			var elementHasFormAmongItsAncestors = $targetElement.parents('form').length > 0
			if (elementHasFormAmongItsAncestors) {
				return
			}
			if (event.which == 37) {
				navigateToPreviousThumbnail()
			}
			if (event.which == 39) {
				navigateToNextThumbnail()
			}
		})() // tryNavigating
	} // handleDocumentKeydown

	function navigateToPreviousThumbnail () {
		if (!confirmLocalNavigation()) {
			return
		}
		var $selectedThumbnail = $carouselThumbnailArea.find('.selected')
		var $prevThumbnail = $selectedThumbnail.prev('.photo-carousel-thumbnail-link')
		if ($prevThumbnail.length) {
			window.location = $prevThumbnail.attr('href')
		}
	} // navigateToPreviousThumbnail

	function navigateToNextThumbnail () {
		if (!confirmLocalNavigation()) {
			return
		}
		var $selectedThumbnail = $carouselThumbnailArea.find('.selected')
		var $nextThumbnail = $selectedThumbnail.next('.photo-carousel-thumbnail-link')
		if ($nextThumbnail.length) {
			window.location = $nextThumbnail.attr('href')
		}
	} // navigateToNextThumbnail

	function handleThumbnailLinkMouseDown (event) {
		var isLeftMouseButton = event.button == 0 ? true : false
		if (!isLeftMouseButton) {
			return
		}
		var $elementBeingMoved = $(event.currentTarget)
		var mouseDownPageX = event.pageX
		var mouseDownPageY = event.pageY
		var originalElementOffset = $elementBeingMoved.offset()
		var elementWidth = $elementBeingMoved.width()
		var elementHeight = $elementBeingMoved.height()
		var tileCenterX = Math.round( originalElementOffset.left + (elementWidth / 2) )
		var tileCenterY = Math.round( originalElementOffset.top + (elementHeight / 2) )
		var pointerDistanceToTileCenterX = tileCenterX - mouseDownPageX
		var pointerDistanceToTileCenterY = tileCenterY - mouseDownPageY
		$document.data( 'oldPhotoOrder', getPhotoOrder() )
		$document
			.one('mousemove', handleFirstMouseMove)
			.on('mousemove', handleThumbnailLinkMouseMove)
			.one('mouseup', markAsNoLongerBeingMoved)
			.on('selectstart', handleSelectStart)
		return // functions below
		function handleFirstMouseMove () {
			$carouselWidget.addClass('dragging-happening')
			$elementBeingMoved.addClass('being-moved')
		} // handleFirstMouseMove

		function handleThumbnailLinkMouseMove (event) {
			var currentPageX = event.pageX
			var currentPageY = event.pageY
			var scrollLeft = $document.scrollLeft()
			var scrollTop = $document.scrollTop()
			var tileCenterX = currentPageX + pointerDistanceToTileCenterX - scrollLeft
			var tileCenterY = currentPageY + pointerDistanceToTileCenterY - scrollTop
			$elementBeingMoved.css('z-index', '-100')
			var elementOverlapped = document.elementFromPoint(tileCenterX, tileCenterY) // relative to window
			$elementBeingMoved.css('z-index', '100')
			var $thumbnailLinkOverlapped = $(elementOverlapped).closest('.photo-carousel-thumbnail-link').not($elementBeingMoved)
			if ($thumbnailLinkOverlapped.length > 0) {
				if ($elementBeingMoved.next().is($thumbnailLinkOverlapped)) {
					$elementBeingMoved.detach().insertAfter($thumbnailLinkOverlapped)
				} else {
					$elementBeingMoved.detach().insertBefore($thumbnailLinkOverlapped)
				}
			}

			;(function setElementOffset(){
				var horizontalDistanceTraveled = currentPageX - mouseDownPageX
				var verticalDistanceTraveled = currentPageY - mouseDownPageY
				var newLeftOffset = originalElementOffset.left + horizontalDistanceTraveled
				var newTopOffset = originalElementOffset.top + verticalDistanceTraveled
				var offset = {'left':newLeftOffset, 'top':newTopOffset}
				$elementBeingMoved.offset(offset)
			})() // setElementOffset
		} // handleThumbnailLinkMouseMove

		function getPhotoOrder () {
			var $thumbnailLinks = $('.photo-carousel-thumbnail-link')
			var photoIds = []
			$thumbnailLinks.each(function(i, thumbnailLinkElement){
				var $thumbnailLink = $(thumbnailLinkElement)
				var photoId = $thumbnailLink.data('photo_id')
				photoIds.push(photoId)
			})
			return photoIds.join(',')
		} // getPhotoOrder

		function markAsNoLongerBeingMoved () {
			$document
				.off('mousemove', handleFirstMouseMove)
				.off('mousemove', handleThumbnailLinkMouseMove)
				.off('selectstart', handleSelectStart)
			$elementBeingMoved.removeClass('being-moved')
			$elementBeingMoved.css({'top':'', 'left':'', 'z-index':''})
			$carouselWidget.removeClass('dragging-happening')
			var photoOrder = getPhotoOrder()
			if ( $document.data('oldPhotoOrder') != photoOrder ) {
				apiCall('/pages/profile/ajax?action=set_photo_order', null, {'photo_order': photoOrder})
			}
		} // markAsNoLongerBeingMoved

		function handleSelectStart () {
			return false
		} // handleSelectStart
	} // handleThumbnailLinkMouseDown

	function getPhotoData (photoId) {
		if (!photoId) {
			return null
		}
		for (var i = 0; i < carouselPhotos.length; i++) {
			var currentPhotoData = carouselPhotos[i]
			if (currentPhotoData['photo_id'] == photoId) {
				return currentPhotoData
			}
		}
	} // getPhotoData

	function handleHashParamsChange () {
		hashParams = getHashParams()
		processHashParams()
	} // handleHashParamsChange

	function processHashParams () {
		var hadToCorrectUrl = enforceValidPhotoIdInUrl()
		if (hadToCorrectUrl) {
			return
		}
		updatePhotosWidget()
		return // functions below
		function enforceValidPhotoIdInUrl () {
			var photoIdInUrlIsValid = !hashParams['photo_id'] || getPhotoData(hashParams['photo_id'])
			if (!photoIdInUrlIsValid) {
				delete(hashParams['photo_id'])
				changeHashParams(hashParams)
				return true
			}
			return false
		} // enforceValidPhotoIdInUrl
	} // processHashParams

	function getSelectedPhotoId () {
		if (hashParams['photo_id']) {
			for (var i = 0; i < carouselPhotos.length; i++) {
				var photoData = carouselPhotos[i]
				var photoIdInHashIsValid = photoData['photo_id'] == hashParams['photo_id']
				if (photoIdInHashIsValid) {
					return hashParams['photo_id']
				}
			}
		}
		if (photoCarouselData['photos'] && photoCarouselData['photos'].length > 0) {
			return photoCarouselData['photos'][0]['photo_id']
		}
		return 0
	} // getSelectedPhotoId

	function updatePhotosWidget () {
		if ( ! carouselPhotos ) {
			return
		}
		var selectedPhotoId = getSelectedPhotoId()
		if ( ! selectedPhotoId ) {
			return
		}
		;(function highlightSelectedThumbnail () {
			var $selectedThumbnail = $( 'a[href="#photo_id=' +selectedPhotoId +'"]' )
			$selectedThumbnail.addClass('selected').siblings().removeClass('selected')
		})() // highlightSelectedThumbnail
		;(function insertSelectedPhoto() {
			var selectedPhotoData = getPhotoData( selectedPhotoId )
			if ( ! selectedPhotoData ) {
				return
			}
			var photoId                 = parseInt( selectedPhotoData['photo_id'] )
			var photoCaption            = selectedPhotoData['caption'] || ''
			var photoRotateAngle        = selectedPhotoData['rotate_angle'] || 0
			var $selectedPhotoOrbit     = $('#selected-photo-orbit')
			var $selectedPhotoContainer = $('<span id="selected-photo-container"><img id="selected-carousel-photo" alt="Selected Photo"></span>')
			var $selectedCarouselPhoto  = $selectedPhotoContainer.find('#selected-carousel-photo')
			$selectedCarouselPhoto.data( 'photo_id', photoId )
			$selectedPhotoOrbit.empty()
			$selectedPhotoContainer.css({'min-width': photoCarouselData['max_standard_width']+'px', 'min-height': photoCarouselData['max_standard_height']+'px'})

			var photoWidth  = selectedPhotoData['standard_width']
			var photoHeight = selectedPhotoData['standard_height']
			var desiredPhotoWidth  = photoCarouselData['max_standard_width']
			var desiredPhotoHeight = photoCarouselData['max_standard_height']
			var zoomIn = photoWidth < desiredPhotoWidth && photoHeight < desiredPhotoHeight
			if ( zoomIn ) {
				var zoomRatio = Math.max( photoWidth / desiredPhotoWidth, photoHeight / desiredPhotoHeight );
				photoWidth  = Math.round( photoWidth / zoomRatio );
				photoHeight = Math.round( photoHeight / zoomRatio );
			}
			$selectedCarouselPhoto.attr({
				'width':   photoWidth
				,'height': photoHeight
				,'src':    selectedPhotoData['standard_url']
			}).css( 'transform', 'rotate(' +photoRotateAngle +'deg)' )

			$selectedPhotoContainer.appendTo($selectedPhotoOrbit)
			if ( 'edit' === carouselMode ) {
				$selectedPhotoContainer.attr('title', "click to rotate")
				$selectedCarouselPhoto.on('click', handleSelectedCarouselPhotoClick)
				var $editSelectedPhotoForm = $(
					'<form id="edit-selected-photo-form" action="/pages/profile/ajax?action=edit_photo" method="post">'
						+'<label id="selected-photo-caption-label"><textarea id="selected-photo-caption" name="caption" rows="5" cols="45" placeholder="Photo Caption (limit 65,000 characters)" maxlength="65000"></textarea></label>'
						+'<input id="save-caption-button" type="submit" value="Save">'
						+'<input id="rotate-photo-button" type="button" value="Rotate">'
						+'<input id="delete-photo-button" type="button" value="Delete">'
						+'<input type="hidden" name="photo_id">'
						+'<input type="hidden" name="rotate_angle">'
					+'</form>'
				)
				$editSelectedPhotoForm.find('[name=photo_id]').val(photoId)
				$editSelectedPhotoForm.find('[name=rotate_angle]').data('original_rotate_angle', photoRotateAngle).val(photoRotateAngle)
				$editSelectedPhotoForm.find('[name=caption]').data('original_caption', photoCaption).val(photoCaption)
				$editSelectedPhotoForm.find('#delete-photo-button').on('click', handleDeletePhotoButtonClick)
				$editSelectedPhotoForm.find('#rotate-photo-button').on('click', handleRotatePhotoButtonClick)
				$editSelectedPhotoForm.on('submit', handleSelectedPhotoFormSubmit)
				$editSelectedPhotoForm.appendTo($selectedPhotoOrbit)
			} else {
				if ( selectedPhotoData['uploaded'] ) {
					$selectedCarouselPhoto.attr( 'title', "added " +selectedPhotoData['uploaded'] )
				}
				var $selectedPhotoCurrentCaption = $('<div id="selected-photo-caption"></div>').text(photoCaption)
				$selectedPhotoCurrentCaption.appendTo($selectedPhotoOrbit)
				$selectedPhotoContainer.on('click', handleViewModeSelectedCarouselPhotoContainerClick)
			}
		})() // insertSelectedPhoto

		// Include admin actions.
		setTimeout( function(){
			if ( 'function' === typeof enableAdminPhotoRotate  ) {
				enableAdminPhotoRotate()
			}
		}, 0 )
	} // updatePhotosWidget

	function handleRotatePhotoButtonClick () {
		rotateSelectedCarouselPhoto()
	} // handleRotatePhotoButtonClick

	function handleSelectedCarouselPhotoClick () {
		rotateSelectedCarouselPhoto()
	} // handleSelectedCarouselPhotoClick

	function rotateSelectedCarouselPhoto () {
		var $selectedCarouselPhoto = $('#selected-carousel-photo')
		var $selectedPhotoRotateAngleInput = $('#edit-selected-photo-form').find('input[name=rotate_angle]')
		var selectedPhotoRotateAngle = parseInt( $selectedPhotoRotateAngleInput.val() || 0 )
		selectedPhotoRotateAngle = ( selectedPhotoRotateAngle + 90 ) % 360
		$selectedPhotoRotateAngleInput.val( selectedPhotoRotateAngle )
		$selectedCarouselPhoto.css( 'transform', 'rotate(' + selectedPhotoRotateAngle + 'deg)' )
	} // rotateSelectedCarouselPhoto

	function handleViewModeSelectedCarouselPhotoContainerClick (event) {
		if ( event.ctrlKey ) {
			return
		}
		var $selectedPhotoContainer = $(event.currentTarget)
		var containerLeftX = $selectedPhotoContainer.offset()['left']
		var mouseX = event.pageX
		var containerWidth = $selectedPhotoContainer.width()
		var containerCenterX = parseInt(containerLeftX + (containerWidth / 2))
		var isClickOnLeftSide = mouseX < containerCenterX
		if (isClickOnLeftSide) {
			navigateToPreviousThumbnail()
		} else {
			navigateToNextThumbnail()
		}
	} // handleViewModeSelectedCarouselPhotoContainerClick

	function handleSelectedPhotoFormSubmit (event) {
		event.preventDefault()
		submitFormViaAjax(event.currentTarget, handleSaveCaptionResponse)
		return // functions below
		function handleSaveCaptionResponse (response) {
			if (response['success']) {
				reprintPhotoCarouselWidget(response['photoCarouselData'])
			}
			if (response['error']) {
				if (response['error_message']) {
					alert(response['error_message'])
				}
				if (response['error_messages']) {
					var errorMessages = []
					for (var i = 0; i < response['error_messages'].length; i++) {
						errorMessages.push(response['error_messages'][i])
					}
					var errorMessage = errorMessages.join("\n")
					if (errorMessage) {
						alert(errorMessage)
					}
				}
			}
		} // handleSaveCaptionResponse
	} // handleSelectedPhotoFormSubmit

	function handleDeletePhotoButtonClick () {
		if (!confirm("Are you sure you want to delete this photo?")) {
			return
		}
		apiCall('/pages/profile/ajax?action=delete_photo', handleDeletePhotoResponse, {'photo_id': getSelectedPhotoId()})
		return // functions below
		function handleDeletePhotoResponse (response) {
			if (response['success']) {
				reprintPhotoCarouselWidget(response['photoCarouselData'])
			}
		} // handleDeletePhotoResponse
	} // handleDeletePhotoButtonClick

	function handlePhotoUploadFormSubmit (event) {
		event.preventDefault()
		var photoForm = event.currentTarget
		var $photoFileInput = $('#photo-file-input')
		var photoFiles = $photoFileInput[0].files
		var someFilesAreSelected = photoFiles.length > 0
		if (!someFilesAreSelected) {
			$photoFileInput.click()
			return
		}
		var maxFileUploads = Math.min(20, photoCarouselData['max_file_uploads'])
		var filesSelected = photoFiles.length
		var tooManyFilesSelected = filesSelected > maxFileUploads
		if (tooManyFilesSelected) {
			alert("There is a maximum of " +maxFileUploads +" files per upload (you selected " +filesSelected +" files).")
			return
		}
		var maxUploadFileSize = Math.min(2000000, photoCarouselData['upload_max_filesize'])
		var totalPostSize = 0
		for (var i = 0; i < photoFiles.length; i++) {
			var photoFile = photoFiles[i]
			var photoSize = photoFile.size
			if (photoSize > maxUploadFileSize) {
				var fileName = photoFile.name
				alert("There is a maximum file size of " +maxUploadFileSize +" bytes (" +fileName +" has " +photoSize +" bytes).")
				return
			}
			totalPostSize += photoSize
		}
		var maxPostSize = photoCarouselData['post_max_size']
		if (totalPostSize > maxPostSize) {
			alert("There is a maximum total post size of " +maxPostSize +" bytes (your files add up to " +totalPostSize +" bytes).")
			return
		}
		var formData = new FormData(photoForm)
		var ajaxOptions = {
			'url': '/pages/profile/ajax?action=upload_photo'
			,'data': formData
			,'cache': false
			,'contentType': false // Must be false or MIME boundary for binary data will not be added.
			,'processData': false // Do not transform data into a query string.
			,'type': 'post'
			,'success': handlePhotoSubmitResponse
			,'dataType': 'json'
		}
		var $submitButton = $('#photo-carousel-form-submit-button')
		$submitButton.val('Uploading...').prop('disabled', true)
		$.ajax(ajaxOptions)
		return // functions below
		function handlePhotoSubmitResponse (response) {
			if (response['error']) {
				if (response['error_message']) {
					alert(response['error_message'])
				}
				if (response['error_messages']) {
					var errorMessages = []
					for (var property in response['error_messages']) {
						errorMessages.push(response['error_messages'][property])
					}
					alert(errorMessages.join("\n"))
				}
				reprintPhotoCarouselWidget( photoCarouselData )
			}
			if (response['success']) {
				reprintPhotoCarouselWidget(response['photoCarouselData'])
			}
		} // handlePhotoSubmitResponse
	} // handlePhotoUploadFormSubmit

	function reprintPhotoCarouselWidget (photoCarouselData) {
		$(window).off('hashchange', handleHashParamsChange
			// otherwise not only do we get double processing, but the continued existence
			// of the old event listener forces javascript to keep its closure in memory
		)
		$document.off('keydown', handleDocumentKeydown)
		$localContainer.css('min-height', $localContainer.height()+'px')
		$localContainer.empty()
		printPhotoCarouselWidget(photoCarouselData)
		$localContainer.css('min-height', '')
		$localContainer.remove()
	} // reprintPhotoCarouselWidget
} // printPhotoCarouselWidget

