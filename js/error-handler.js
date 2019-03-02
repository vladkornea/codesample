;(function registerWindowErrorHandler() {
	var MAX_ERRORS_REPORTED = 10 // IE10 does not support const
	var errorsReportedCount = 0
	var previousErrorHandler = window.onerror
	window.onerror = handleWindowError
	return // functions below
	function handleWindowError (messageOrEvent, errorFileUrl, lineNumber, column, originalErrorData) {
		if (errorsReportedCount >= MAX_ERRORS_REPORTED) {
			window.onerror = previousErrorHandler
			previousErrorHandler(messageOrEvent, errorFileUrl, lineNumber, column, originalErrorData)
			return
		}
		errorsReportedCount++
		var errorDetails
		;(function setErrorDetails(){
			errorDetails = {'page_url': window.location.href}
			if (originalErrorData) {
				errorDetails['error_file_url'] = originalErrorData['fileName']
				errorDetails['line_number']    = originalErrorData['lineNumber']
				errorDetails['column_number']  = originalErrorData['columnNumber']
				errorDetails['error_message']  = originalErrorData['message']
				errorDetails['stack_trace']    = originalErrorData['stack']
			} else { // old browsers IE9(test others) do not pass error
				errorDetails['error_message']  = messageOrEvent
				errorDetails['error_file_url'] = errorFileUrl
				errorDetails['line_number']    = lineNumber
				if (column) {
					errorDetails['column_number']  = column
				}
			}
		})() // setErrorDetails
		;(function processErrorDetails(){
			if (originalErrorData && originalErrorData['stack']) {
				StackTrace.fromError(originalErrorData).then(submitErrorDetails)
			} else { // syntax errors have no stack
				submitErrorDetails([])
			}
		})() // processErrorDetails
		if (previousErrorHandler) {
			previousErrorHandler(messageOrEvent, errorFileUrl, lineNumber, column, originalErrorData)
		}
		return // functions below
		function submitErrorDetails (stackFrames) {
			var stackTrace = []
			;(function setStackTrace() {
				for (var i = 0; i < stackFrames.length; i++) {
					var stackFrame = stackFrames[i]
					var isMinifiedThirdPartyLibrary = stackFrame['fileName'].match(/\/js\/lib\/.+\.min\.js/) ? true : false
					var isCurrentFile = stackFrame['fileName'].match(/\/js\/error-handler\.js\//) ? true : false
					if (isMinifiedThirdPartyLibrary || isCurrentFile) {
						continue
					}
					if (!errorDetails['error_file_url']) { // Chrome does not provide
						errorDetails['error_file_url'] = stackFrame['fileName']
					}
					if (!errorDetails['line_number']) {
						errorDetails['line_number'] = stackFrame['lineNumber']
					}
					stackTrace.push({
						'function':  stackFrame['functionName']
						,'file':     stackFrame['fileName']
						,'line':     stackFrame['lineNumber']
					})
				}
			})() // setStackTrace
			var errorReport = {
				'error_message':   errorDetails['error_message']
				,'page_url':       errorDetails['page_url']
				,'error_file_url': errorDetails['error_file_url']
				,'line_number':    errorDetails['line_number']
				,'stack_trace':    stackTrace.length > 0 ? stackTrace : '' /* Empty arrays don't get sent,
					and we want to inform the server script that we didn't forget to set this variable. */
			}
			$(function whenJqueryIsReady(){
				apiCall('/ajax/report-error.php?action=report_javascript_error', null, errorReport)
			})
		} // submitErrorDetails
	} // handleWindowError
})() // registerWindowErrorHandler

;(function registerAjaxGlobalErrorHandler(){
	var MAX_ERRORS_REPORTED = 10 // IE10 does not support const
	var errorsReportedCount = 0
	$(document).ajaxError(
		function handleGlobalAjaxError(event, jqxhr, settings, httpErrorMessage) {
			if (errorsReportedCount >= MAX_ERRORS_REPORTED) {
				return
			}
			errorsReportedCount++
			alert(httpErrorMessage)
			;(function reportAjaxError () {
				var errorReport = {
					'page_url':            window.location.href
					,'ajax_request_url':   settings['url']
					,'http_error_message': httpErrorMessage || ''
					,'error_functions':    settings['callers'] || ''
					,'error_message':      'Error requesting ' +settings['url'] +(httpErrorMessage ? ': ' +httpErrorMessage : '')
				}
				apiCall('/ajax/report-error.php?action=report_ajax_error', null, errorReport)
			})() // reportAjaxError
		} // handleGlobalAjaxError
	) // ajaxError
})() // registerAjaxGlobalErrorHandler
