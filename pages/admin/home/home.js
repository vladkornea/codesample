$(printAdminHomePageInterface)
function printAdminHomePageInterface () {
	requestAllGlobalSettings()
	return // functions below

	function requestAllGlobalSettings () {
		apiCall('/pages/admin/home/ajax?action=get_all', handleGlobalSettingsResponse)
	} // requestAllGlobalSettings

	function handleGlobalSettingsResponse (response) {
		var currentSettings = response['settings'] || {}
		printGlobalSettingsForm(currentSettings)
	} // handleGlobalSettingsResponse

	function printGlobalSettingsForm (currentSettings) {
		var $globalSettingsForm = $(
			'<form id="global-settings-form" action="/pages/admin/home/ajax?action=save" method="post">'
				+'<label>'
					+'<input id="process-received-queue-checkbox" name="process_received_queue" type="checkbox">'
					+'<span class="label">Process Received Queue</span>'
				+'</label>'
				+'<label>'
					+'<input id="process-sent-queue-checkbox" name="process_sent_queue" type="checkbox">'
					+'<span class="label">Process Sent Queue</span>'
				+'</label>'
				+'<label>'
					+'<input id="send-queued-emails-checkbox" name="queued_email_sending" type="checkbox">'
					+'<span class="label">Send Queued Emails</span>'
				+'</label>'
				+'<input type="submit" value="Save">'
			+'</form>'
		).appendTo('main')
		$globalSettingsForm.find('#process-received-queue-checkbox').prop('checked', currentSettings['process_received_queue'] == 1)
		$globalSettingsForm.find('#process-sent-queue-checkbox').prop('checked', currentSettings['process_sent_queue'] == 1)
		$globalSettingsForm.find('#send-queued-emails-checkbox').prop('checked', currentSettings['queued_email_sending'] == 1)
		$globalSettingsForm.on('submit', handleGlobalSettingsFormSubmit)
	} // printGlobalSettingsForm

	function handleGlobalSettingsFormSubmit (event) {
		event.preventDefault()
		submitFormViaAjax(event.currentTarget, handleSaveAllSettingsResponse)
		function handleSaveAllSettingsResponse (response) {
			var currentSettings = response['settings']
			printGlobalSettingsForm(currentSettings)
		} // handleSaveAllSettingsResponse
	} // handleGlobalSettingsFormSubmit
} // printAdminHomePageInterface

