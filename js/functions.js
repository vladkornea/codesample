/** API for $.ajax() with custom error handling */
function apiCall (url, responseHandler, data) {
	var ajaxSettings = {
		'url':       url
		,'method':   data ? 'post' : 'get'
		,'data':     data || {}
		,'dataType': 'json'
		,'success':  handleSuccessResponse
	}
	if (arguments && arguments.callee) {
		ajaxSettings['callers'] = arguments.callee.caller.name +'() => ' +arguments.callee.name +'()'
	}
	$.ajax(ajaxSettings)
	return // functions below
	function handleSuccessResponse (data, textStatus, jqXHR) {
		var response = jqXHR['responseJSON']
		if (response['alert']) {
			alert(response['alert'])
		}
		if (responseHandler) {
			responseHandler(response)
		}
	} // handleSuccessResponse
} // apiCall


function submitFormViaAjax (formElement/*can be a jQuery object or selector string*/, responseHandler, extraData) {
	var $form = $(formElement)
	var formData = new FormData($form[0])
	$form.find('input[type=checkbox].boolean:not(:checked)').each(function(i, uncheckedBooleanCheckbox){
		formData.append( $(uncheckedBooleanCheckbox).attr('name'), '0' )
	})
	;(function addExtraDataToFormData(){
		if (extraData) {
			for (var fieldName in extraData) {
				var fieldValue = extraData[fieldName]
				formData.append(fieldName, fieldValue)
			}
		}
	})() //addExtraDataToFormData
	$.ajax({
		'url':          $form.attr('action')
		,'data':        formData
		,'success':     function(data, textStatus, jqXHR){ if(responseHandler){responseHandler(jqXHR['responseJSON'])} } // Errors are handled by the global AJAX error handler (see `registerAjaxGlobalErrorHandler()`).
		,'method':      'post' // Required by context.
		,'dataType':    'json' // We expect responses in this format by convention.
		,'contentType': false // Must be false or MIME boundary for binary data will not be added. Required to work with FormData.
		,'processData': false // Do not transform data into a query string. Required to work with FormData.
		,'callers':     (typeof arguments.callee != 'undefined') ? (arguments.callee.caller.name+'() called '+arguments.callee.name+'()') : '' // For custom error handling.
	})
} // submitFormViaAjax


/**
 * Examples:
 * getUrlParams()['myparam']    // url defaults to the current page
 * getUrlParams(url)['myparam'] // url can be just a query string
 *
 * Results of calling `getUrlParams(url)['myparam']` with various urls:
 * example.com                               (undefined)
 * example.com?                              (undefined)
 * example.com?myparam                       (empty string)
 * example.com?myparam=                      (empty string)
 * example.com?myparam=0                     (the string '0')
 * example.com?myparam=0&myparam=override    (the string 'override')
 *
 * Origin: http://stackoverflow.com/a/23946023/2407309
 */
function getUrlParams (url) {
	var urlParams = {} // return value
	var queryString = getQueryString()
	if (queryString) {
		var keyValuePairs = queryString.split('&')
		for (var i = 0; i < keyValuePairs.length; i++) {
			var keyValuePair = keyValuePairs[i].split('=')
			var paramName = keyValuePair[0]
			var paramValue = keyValuePair[1] || ''
			urlParams[paramName] = decodeURIComponent(paramValue.replace(/\+/g, ' '))
		}
	}
	return urlParams // functions below
	function getQueryString () {
		var reducedUrl = url || window.location.search
		reducedUrl = reducedUrl.split('#')[0] // Discard fragment identifier.
		var queryString = reducedUrl.split('?')[1]
		if (!queryString) {
			if (reducedUrl.search('=') !== false) { // URL is a query string.
				queryString = reducedUrl
			}
		}
		return queryString
	} // getQueryString
} // getUrlParams


function displayFormTableErrorMessage ($field, errorMessage) {
	$field = $($field)
	var $fieldContainer = $field.closest('td')
	$('<div class="error"></div>').text(errorMessage).appendTo($fieldContainer)
	$field.focus()
} // displayFormTableErrorMessage


function removeOldErrorMessages (form) {
	var elementsToRemove = form.getElementsByClassName('error')
	while (elementsToRemove.length > 0) {
		elementsToRemove[0].parentNode.removeChild(elementsToRemove[0])
	}
} // removeOldErrorMessages


function isValidEmail (email) {
	return email.match(/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i) ? true : false
} // isValidEmail


function confirmBackspaceNavigations () {
    // http://stackoverflow.com/a/22949859/2407309
    var backspaceIsPressed = false
    $(document).keydown(function(event){
        if (event.which == 8) {
            backspaceIsPressed = true
        }
    })
    $(document).keyup(function(event){
        if (event.which == 8) {
            backspaceIsPressed = false
        }
    })
    $(window).on('beforeunload', function(){
        if (backspaceIsPressed) {
            backspaceIsPressed = false
            return "Are you sure you want to leave this page?"
        }
    })
} // confirmBackspaceNavigations


/**
 * Returns an object like { 'name':'Vladimir Kornea', 'favorites':['Ayn Rand','TNG','House MD'] }
 * All fields that have a 'name' attribute are included.
 * Checkboxes can be boolean toggles or a set of values. While we could assume that whenever there
 * is only one checkbox it is boolean, that assumption might fail at times, for example maybe
 * something was once a set of options, but for some reason the set has now been reduced to one
 * option. Also, it is semantically clearer to just add the css class `.boolean` to checkboxes that
 * should be treated as booleans. No surprises.
 */
function getNamedFormData (form) {
	var $form = $(form)
	var formData = {}
	var $namedInputFields = $form.find('[name]')
	for (var i = 0; i < $namedInputFields.length; i++) {
		var $input = $($namedInputFields[i])
		var inputName = $input.attr('name')
		var inputValue = $input.val()

		if ($input.is('[type=checkbox]')) {
			if ($input.is('.boolean')) {
				formData[inputName] = $input.is(':checked') ? inputValue : ''
				continue
			}
			if (!formData[inputName]) {
				formData[inputName] = []
			}
			if ($input.is(':checked')) {
				formData[inputName].push(inputValue)
				continue
			}
			continue
		}

		if ($input.is('[type=radio]')) {
			if ($input.is(':checked')) {
				formData[inputName] = inputValue
				continue
			}
			if (!(inputName in formData)) {
				formData[inputName] = ''
				continue
			}
			continue
		}

		formData[inputName] = inputValue // haven't considered file inputs yet
	}
	return formData
} // getNamedFormData


// format of data: {'key':'value'}
function setNamedFormData (formElement, formData) {
	var $form = $(formElement)
	$.each(formData, function processField(fieldName, fieldValue) {
		var $inputElement = $form.find('[name="'+fieldName+'"]')
		if ($inputElement.length == 1) {
			$inputElement.val(fieldValue)
			return
		}
		var $radioInput = $form.find('[name='+fieldName+'][value="'+fieldValue+'"]')
		if ($radioInput.length == 1) {
			$radioInput.prop('checked', true)
			return
		}
		// todo handle checkboxes
	}) // processField
} // setNamedFormData


// Pass a formElement argument to append input fields to the form rather than returning a keyvalmap.
function getClientInfo (formElement/*can be a jQuery object or selector*/) {
	var clientInfo = {
		'screen_width':   screen.width
		,'screen_height': screen.height
		,'color_depth':   screen.colorDepth
		,'window_width':  $(window).width()
		,'window_height': $(window).height()
		,'utc_offset':    new Date().getTimezoneOffset()
	}
	if (!formElement) {
		return clientInfo
	}
	for (var fieldName in clientInfo) {
		var fieldValue = clientInfo[fieldName]
		$('<input type="hidden">').attr('name', fieldName).val(fieldValue).appendTo(formElement)
	}
} // getClientInfo


function preserveDocumentHeight () {
	$(document.body).css('min-height', $(document).height())
} // preserveDocumentHeight


function getHashParams (url) {
	if (typeof url == 'undefined') {
		url = window.location.href // window.location.hash is urldecoded, which causes problems
	}
	var hashString = url.split('#')[1]
	var hashParams = hashString ? getUrlParams(hashString) : {}
	return hashParams
} // getHashParams


function changeHashParams (replacementHashParams) {
	var queryString = getQueryString(replacementHashParams)
	var newHash = queryString ? '#' +getQueryString(replacementHashParams) : ''
	window.location.hash = newHash
} // changeHashParams


function getQueryString (queryParams) {
	var keyValuePairs = []
	for (var paramName in queryParams) {
		var paramValue = queryParams[paramName]
		var keyValuePair = paramName +'=' +encodeURIComponent(paramValue)
		keyValuePairs.push(keyValuePair)
	}
	var queryString = keyValuePairs.join('&')
	queryString = queryString.replace(/%20/g, '+') // '%20' shown as an actual space in the hash string
	return queryString
} // getQueryString


function roundWithPrecision (number, precision) {
	var magnitude = Math.pow(10, precision)
	return (Math.round(number * magnitude) / magnitude).toString()
} // roundWithPrecision


function getAgeFromBirthday (thenMonth, thenDay, thenYear) {
	var now = new Date()
	var nowYear = now.getFullYear()
	var nowMonth = now.getMonth() + 1
	var nowDay = now.getDate()
	var yearsSince = nowYear - thenYear
	var monthsSince = nowMonth - thenMonth
	var daysSince = nowDay - thenDay
	var birthdayReachedThisYear = (monthsSince < 0) || (monthsSince == 0 && daysSince < 0) ? false : true
	var age = birthdayReachedThisYear ? yearsSince : yearsSince - 1
	return age
} // getAgeFromBirthday


/**
 * Used for pagination. Sometimes there are more pages than we want to show. We want to show
 * the previous and next few pages, the first, last, and current pages, and every 10th page.
 * This function returns an array of pages to show.
 */
function getPagesToShow (currentPage, lastPage) {
	currentPage = parseInt(currentPage)
	lastPage = parseInt(lastPage)
	var pagesToShow = []
	var firstPageToShow = currentPage - 3
	var lastPageToShow = currentPage + 3
	while (firstPageToShow < 1) {
		firstPageToShow++
		lastPageToShow++
	}
	if (lastPageToShow > lastPage) {
		lastPageToShow = lastPage
	}
	if (firstPageToShow != 1) {
		pagesToShow.push(1)
	}
	for (var page = firstPageToShow; page <= lastPageToShow; page++) {
		pagesToShow.push(page)
	}
	if (lastPageToShow != lastPage) {
		pagesToShow.push(lastPage)
	}
	return pagesToShow
} // getPagesToShow


function queueFunction (callbackFunction) {
	// http://stackoverflow.com/a/24966345/2407309
	setTimeout(callbackFunction, 0)
} // queueFunction


/**
 * @param newTop integer
 * @param options object|function Callback or options like {'pixelsPerSecond':2000, 'maxScrollDuration':300, 'callback':myFunction}
 */
function smoothScrollViewport (newTop, options) {
	if (!window.stopScrollingViewport) {
		window.stopScrollingViewport = function (event) {
			if (event.isDefaultPrevented()) {
				/**
				 * If we attempt to scroll programmatically in response to a keydown event, the event would eventually
				 * propagate to window, and the scroll would be stopped by this function right after being started.
				 * We could deal with this by calling event.stopPropagation() before the event has propagated to window,
				 * but we'd have no reason to know that. Fortunately, since in such cases we would usually be calling
				 * event.preventDefault() anyway, we can instead stop the animation only if default is not prevented.
	             */
				return
			}
			$('html,body').stop()
		}
		$(window).on('mousewheel DOMMouseScroll mousedown keydown', window.stopScrollingViewport)
	}
	;(function setOptions () {
		if ('function' === typeof options) {
			options = {'callback': options}
		} else if (!options) {
			options = {}
		}
		if (!('pixelsPerSecond' in options)) {
			options.pixelsPerSecond = 2000
		}
		if (!('maxScrollDuration' in options)) {
			options.maxScrollDuration = 300
		}
		if (!('callback' in options)) {
			options.callback = function(){}
		}
	})()
	var currentTop = $(document).scrollTop()
	var animationDuration = Math.round(Math.abs(currentTop - newTop) * 1000 / options.pixelsPerSecond)
	if (animationDuration > options.maxScrollDuration) {
		animationDuration = options.maxScrollDuration
	}
	$('html,body').stop().animate({'scrollTop':newTop}, animationDuration, options.callback)
} // smoothScrollViewport


/**
 * Accepts an element or a collection of elements to scroll into view, in order of priority. If collection of elements is
 * taller than viewport, as much of the top of the most important element possible will be shown, unless 'bottom' is passed as
 * the second argument; this argument can alternatively be a callback function or an object specifying multiple options:
 * smoothScrollIntoView( [myFirstElement, mySecondElement] )
 * smoothScrollIntoView(myElements, 'bottom')
 * smoothScrollIntoView(myElement, function(){myElement.focus()})
 * smoothScrollIntoView(myElements, {'align':'bottom', 'callback':myFunction, 'clearance':10})
 * Since this function calls smoothScrollViewport(), it also understands all of its options.
 */
function smoothScrollIntoView (elements, options) {
	// Sometimes smoothScrollIntoView() is called right after element .show(). Since refreshing the screen is queued
	// to happen after this function executes, and since we need the element to be shown, we use queueFunction().
	queueFunction(function(){
		;(function setOptions(){
			var defaults = {'align':'top', 'clearance':8}
			if ('function' === typeof options) {
				options = {'callback': options}
			} else if ('string' === typeof options) {
				options = {'align': options}
			} else if (!options) {
				options = {}
			}
			for (var option in defaults) {
				if (!(option in options)) {
					options[option] = defaults[option]
				}
			}
		})()

		elements = $(elements)
		if (!elements.length) {
			if ('callback' in options) {
				options.callback()
			}
			return
		}

		var documentHeight = $(document).height()
		var viewportHeight = $(window).height()

		var combinedTop, combinedBottom, smallestFoundElementTop, largestFoundElementBottom
		for (var i = 0; i < elements.length; i++) {
			var element = $(elements[i])
			if (!element.length || element.is(':hidden')) {
				continue
			}

			var paddedElementTop = element.offset().top - options.clearance
			if (paddedElementTop < 0) {
				paddedElementTop = 0
			}
			var paddedElementBottom = element.offset().top + element.outerHeight(true) + options.clearance
			if (paddedElementBottom > documentHeight) {
				paddedElementBottom = documentHeight
			}

			if ('undefined' === typeof combinedTop) {
				combinedTop = paddedElementTop
				combinedBottom = paddedElementBottom
			} else {
				if (paddedElementTop < combinedTop) {
					combinedTop = paddedElementTop
				}
				if (paddedElementBottom > combinedBottom) {
					combinedBottom = paddedElementBottom
				}
			}

			var combinedHeightFitsViewport = combinedBottom - combinedTop <= viewportHeight
			if (combinedHeightFitsViewport) {
				smallestFoundElementTop = combinedTop
				largestFoundElementBottom = combinedBottom
				continue
			}

			// If we made it this far, the viewport cannot fit all the elements.
			var firstElementExceedsViewport = 'undefined' === typeof smallestFoundElementTop
			if (firstElementExceedsViewport) {
				if ('bottom' === options.align) {
					largestFoundElementBottom = paddedElementBottom
					smallestFoundElementTop = largestFoundElementBottom - viewportHeight
				} else {
					smallestFoundElementTop = paddedElementTop
					largestFoundElementBottom = smallestFoundElementTop + viewportHeight
				}
			} else {
				var incompleteElementTopIsAboveCompleteElementsTop = paddedElementTop < smallestFoundElementTop
				var incompleteElementBottomIsBelowCompleteElementsBottom = paddedElementBottom > largestFoundElementBottom
				if (incompleteElementTopIsAboveCompleteElementsTop && incompleteElementBottomIsBelowCompleteElementsBottom) {
					if ('bottom' === options.align) {
						largestFoundElementBottom = smallestFoundElementTop + viewportHeight
					} else {
						smallestFoundElementTop = largestFoundElementBottom - viewportHeight
					}
				} else if (incompleteElementBottomIsBelowCompleteElementsBottom) {
					largestFoundElementBottom = smallestFoundElementTop + viewportHeight
				} else {
					smallestFoundElementTop = largestFoundElementBottom - viewportHeight
				}
			}

			if (smallestFoundElementTop < 0) {
				smallestFoundElementTop = 0
			}
			if (largestFoundElementBottom > documentHeight) {
				largestFoundElementBottom = documentHeight
			}
			break
		} // for loop

		;(function moveViewport(){
			var viewportTop = $(document).scrollTop()
			var viewportBottom = viewportTop + viewportHeight
			var largestAllowedViewportTop = smallestFoundElementTop
			if (viewportTop > largestAllowedViewportTop) {
				smoothScrollViewport(largestAllowedViewportTop, options)
				return
			}
			var smallestAllowedViewportBottom = largestFoundElementBottom
			if (viewportBottom < smallestAllowedViewportBottom) {
				var desiredTop = smallestAllowedViewportBottom - viewportHeight
				if (desiredTop > largestAllowedViewportTop) {
					desiredTop = largestAllowedViewportTop
				} else if (desiredTop < 0) {
					desiredTop = 0
				}
				if (viewportTop != desiredTop) {
					smoothScrollViewport(desiredTop, options)
					return
				}
			}
			if ('callback' in options) {
				options.callback()
			}
		})()
	})
} // smoothScrollIntoView

