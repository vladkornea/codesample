$(printSettingsPageInterface)
function printSettingsPageInterface () {
	var $localContainer = $('#settings-form-container')
	var pageData = window['pageData']
	if (!pageData) {
		$localContainer.append('<p class="error">Missing page data.</p>')
		return
	}
	var currentSettings = pageData['settings']
	if (!currentSettings) {
		$localContainer.append('<p class="error">Missing current settings.</p>')
		return
	}
	var validSettings = currentSettings['valid_settings']
	var settings = currentSettings['settings']
	printSettingsForm(validSettings, settings)
	return // functions below
	function printSettingsForm (validSettings, settings) {
		var $settingsForm = $('<form id="settings-form" action="/pages/admin/settings/ajax?action=save_settings" method="post"><input type="submit" value="Submit"></form>')
		$settingsForm.on('submit', handleSettingsFormSubmit)
		$settingsForm.appendTo($localContainer)

		;(function printSettingsList(){
			var $settingsList = $('<ul id="settings-list" class="structural"></ul>').prependTo($settingsForm)
			for (var i = 0; i < validSettings.length; i++) {
				var settingName = validSettings[i]
				var settingValue = settings[settingName]
				var $listItem = $('<li><label><input class="setting-checkbox" type="checkbox"><span class="setting-name"></span></label></li>').appendTo($settingsList)
				$listItem.find('.setting-name').text(settingName)
				var $settingCheckbox = $listItem.find('.setting-checkbox')
				$settingCheckbox.attr('name', 'settings['+settingName+']')
				if (settingValue === '1' || settingValue === 1) {
					$settingCheckbox.prop('checked', true)
				}
			}
		})() // printSettingsList

		return // functions below
		function handleSettingsFormSubmit (event) {
			event.preventDefault()
			var uncheckedSettings = {}
			var $uncheckedCheckboxes = $settingsForm.find('input[type=checkbox]:not(:checked)')
			$uncheckedCheckboxes.each(function(i, checkbox){
				var $checkbox = $(checkbox)
				var checkboxName = $checkbox.attr('name')
				uncheckedSettings[checkboxName] = ''
			})
			submitFormViaAjax($settingsForm, handleSaveSettingsResponse, uncheckedSettings)
			return // functions below
			function handleSaveSettingsResponse (response) {
				window.location.reload()
			} // handleSaveSettingsResponse
		} // handleSettingsFormSubmit
	} // printSettingsForm
} // printSettingsPageInterface

