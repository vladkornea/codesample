$(printSpammersPageInterface)
function printSpammersPageInterface () {
	var $localContainer = $('main')
	var pageData = window['pageData']
	if (!pageData) {
		$('<p class="error">Missing pageData</p>').appendTo($localContainer)
		return
	}
	var suspectedSpammers = pageData['suspectedSpammers']
	var knownSpammers = pageData['knownSpammers']
	var $spammersForm = $('<form action="/pages/admin/spammers/ajax?action=update_spammers" method="post"></form>')

	;(function appendKnownSpammers(){
		if (knownSpammers.length == 0) {
			return
		}
		$('<div>Check to mark as not spammer:</div><ul id="known-spammers-list" class="structural"></ul>').appendTo($spammersForm)
		var $knownSpammersList = $spammersForm.find('#known-spammers-list')
		for (var i = 0; i < knownSpammers.length; i++) {
			var spammerData = knownSpammers[i]
			var $listItem = $('<li><input type="checkbox" name="not_spammers_ids[]"><a class="spammer-link"></a></li>')
			$listItem.find('[name="not_spammers_ids[]"]').val(spammerData['user_id'])
			$listItem.find('a').attr({'href': '/profile?username='+spammerData['username'], 'target': '_blank'}).text(spammerData['username'])
			$listItem.appendTo($knownSpammersList)
		}
	})() // appendKnownSpammers

	;(function appendNewSpammersInput(){
		$(
			'<label>New spammers IDs:<textarea name="new_spammers_ids" rows="6" cols="40"></textarea></label>'
			+'<input type="submit" value="Unset Checked and Submit New Spammer IDs">'
		).appendTo($spammersForm)
	})() // appendNewSpammersInput

	;(function appendSuspectedSpammers(){
		if (suspectedSpammers.length == 0) {
			return
		}
		$('<div>Suspected spammers:</div><ul id="suspected-spammers-list" class="structural"></ul>').appendTo($spammersForm)
		var $suspectedSpammersList = $spammersForm.find('#suspected-spammers-list')
		for (var i = 0; i < suspectedSpammers.length; i++) {
			var spammerData = suspectedSpammers[i]
			var $listItem = $('<li><textarea class="message-text" style="display:inline;" cols="80" rows="4"></textarea> <a class="spammer-link"></a> / <span>' +spammerData['from_user_id'] +'</span></li>')
			$listItem.find('[name="suspected_spammers_ids[]"]').val(spammerData['from_user_id'])
			$listItem.find('a.spammer-link').attr({'href': '/profile?username='+spammerData['username'], 'target': '_blank'}).text(spammerData['username'])
			$listItem.find('.message-text').val(spammerData['message_text'])
			$listItem.appendTo($suspectedSpammersList)
		}
	})() // appendSuspectedSpammers

	$spammersForm.submit(handleNewSpammersFormSubmit)
	$spammersForm.appendTo($localContainer)
	return // functions below
	function handleNewSpammersFormSubmit (event) {
		event.preventDefault()
		submitFormViaAjax($spammersForm, handleNewSpammersResponse)
		return // functions below
		function handleNewSpammersResponse (response) {
			console.log(response)
			window.location.reload()
		} // handleNewSpammersResponse
	} // handleNewSpammersFormSubmit
} // printSpammersPageInterface

