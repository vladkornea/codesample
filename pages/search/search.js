$(printSearchPageInterface)
function printSearchPageInterface () {
	var $localContainer = $( 'main' )
	var pageData = window['pageData']
	if (!pageData) {
		$('<p class="error">Missing pageData</p>').appendTo($localContainer)
		return
	}
	if (pageData['whyNotAllowedToSearch']) {
		$('<p class="error"></p>').text(pageData['whyNotAllowedToSearch']).appendTo($localContainer)
		return
	}
	printSearchForm()
	$(window).on('hashchange', processHashParams)
	processHashParams()
	return // functions below
	function processHashParams () {
		var preloadedFirstPageSearchResults = pageData['firstPageSearchResults']
		var page = getHashParams()['page'] || 1
		if (page == 1 && preloadedFirstPageSearchResults) {
			printSearchResults(preloadedFirstPageSearchResults)
		} else {
			requestSearchResults(page)
		}
	} // processHashParams

	function requestSearchResults (page) {
		$('body').addClass('wait')
		apiCall('/pages/search/ajax?action=get_search_results', printSearchResults, {'page':page})
	} // requestSearchResults

	function printSearchResults (searchResults) {
		$('body').removeClass('wait')
		$('#search-results-container').remove()
		var $searchResultsContainer = $('<section id="search-results-container"></section>').appendTo($localContainer)
		var foundUsers = searchResults['users']
		if (!foundUsers.length) {
			$('<p>None found.</p>').appendTo($searchResultsContainer)
			return
		}
		var $searchResultsList = $('<ol id="search-results-list"></ol>').appendTo($searchResultsContainer)
		;(function printSearchResultListItems(){
			for (var i = 0; i < foundUsers.length; i++) {
				var userData = foundUsers[i]
				var $listItem = $(
					'<li><a class="profile-link">'
						+'<div class="thumbnail-container"></div>'
						+'<div class="user-description-container"><span class="match-score"></span> <span class="username"></span><div class="user-description"></div></div>'
					+'</a></li>'
				)
				if (userData['thumbnail_url']) {
					var $thumbnail = $('<img>').attr({
						 'src':    userData['thumbnail_url']
						,'width':  userData['primary_thumbnail_width']
						,'height': userData['primary_thumbnail_height']
						,'alt':    userData['username']
					})
					if ( userData['primary_thumbnail_rotate_angle'] ) {
						$thumbnail.css( 'transform', 'rotate(' +userData['primary_thumbnail_rotate_angle'] +'deg)' )
					}
					$thumbnail.appendTo( $listItem.find('.thumbnail-container') )
				}
				$listItem.find('a.profile-link').attr('href', '/profile?username=' +encodeURIComponent(userData['username']))
				$listItem.find('.username').text(userData['username'])
				$listItem.find('.match-score').text('('+userData['match_score']+')')
				$listItem.find('.user-description').text(userData['description'])
				$listItem.appendTo($searchResultsList)
			}
		})() // printSearchResultListItems
		printPagination()
		smoothScrollIntoView($searchResultsList)
		return // functions below
		function printPagination () {
			var totalPages = searchResults['total_pages']
			var currentPageNumber = searchResults['current_page']
			document.title = "Search Page " +currentPageNumber +" - TypeTango"
			if (totalPages < 2) {
				return
			}
			var $paginationContainer = $('<footer id="pagination-container"></footer>').insertAfter($searchResultsList)
			var isLastPage = currentPageNumber == totalPages
			if (!isLastPage) {
				var nextPageNumber = parseInt(currentPageNumber) + 1
				$('<a rel="next" id="next-page-link">➜</a>').attr('href', '#page='+nextPageNumber).appendTo($paginationContainer)
			}
			;(function printPageNumbers (){
				var pagesToShow = getPagesToShow(currentPageNumber, totalPages)
				for (var i = 0; i < pagesToShow.length; i++) {
					var loopPageNumber = pagesToShow[i]
					var isCurrentPage = loopPageNumber == currentPageNumber
					if (isCurrentPage) {
						$('<span id="current-page-link"></span>').text(loopPageNumber).appendTo($paginationContainer)
					} else {
						$('<a></a>').attr('href', '#page='+loopPageNumber).text(loopPageNumber).appendTo($paginationContainer)
					}
				}
			})()
			var matchCount = searchResults['total_users']
			if (matchCount.toLocaleString) {
				matchCount = matchCount.toLocaleString()
			}
			$('<div id="number-of-matches"></div>').text(matchCount +' matches').appendTo($paginationContainer)
		} // printPagination
	} // printSearchResults

	function printSearchForm () {
		$('#search-form-container').remove()
		var $searchFormContainer = $('<section id="search-form-container"></section>').appendTo($localContainer)
		var searchFormData = pageData['searchFormData']
		if (!searchFormData) {
			$('<p class="error"></p>').text('Missing searchFormData').appendTo($searchFormContainer)
			return
		}
		var $searchForm = $(
			'<form id="search-form" action="/pages/search/handle-search-form-submit" method="post">'
				+'<fieldset id="gender-fieldset">'
					+'<legend>Genders</legend>'
					+'<label><input type="checkbox" name="genders[]" value="female"><span class="label">Women</span></label>'
					+' <label><input type="checkbox" name="genders[]" value="male"><span class="label">Men</span></label>'
					+'<br><label><input type="checkbox" name="must_like_my_gender" value="1"><span class="label">...who like my gender</span></label>'
				+'</fieldset>'
				+'<fieldset id="mbti_type-fieldset">'
					+'<legend>Personality Types <span id="toggle-type-checkboxes">Flip All</span></legend>'
					+'<table id="personality-types-table" class="structural">'
						+'<tr>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="ISTJ"><span class="label">ISTJ</span></label></td>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="ISFJ"><span class="label">ISFJ</span></label></td>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="INFJ"><span class="label">INFJ</span></label></td>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="INTJ"><span class="label">INTJ</span></label></td>'
						+'</tr><tr>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="ISTP"><span class="label">ISTP</span></label></td>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="ISFP"><span class="label">ISFP</span></label></td>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="INFP"><span class="label">INFP</span></label></td>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="INTP"><span class="label">INTP</span></label></td>'
						+'</tr><tr>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="ESTP"><span class="label">ESTP</span></label></td>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="ESFP"><span class="label">ESFP</span></label></td>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="ENFP"><span class="label">ENFP</span></label></td>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="ENTP"><span class="label">ENTP</span></label></td>'
						+'</tr><tr>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="ESTJ"><span class="label">ESTJ</span></label></td>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="ESFJ"><span class="label">ESFJ</span></label></td>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="ENFJ"><span class="label">ENFJ</span></label></td>'
							+'<td><label><input type="checkbox" name="mbti_types[]" value="ENTJ"><span class="label">ENTJ</span></label></td>'
						+'</tr>'
					+'</table>'
				+'</fieldset>'
				+'<fieldset id="age-fieldset">'
					+'<legend>Age</legend>'
					+'<label>From <input id="min_age-input" name="min_age" type="text" maxlength="3" size="3" autocomplete="off"></label>'
					+' <label>To <input id="max_age-input" name="max_age" type="text" maxlength="3" size="3" autocomplete="off"></label>'
				+'</fieldset>'
				+'<fieldset id="distance-fieldset">'
					+'<legend>Location</legend>'
					+'<div><label>Country <select id="country-select" name="country"><option value="">All</option></select></label></div>'
				+'</fieldset>'
				+'<fieldset id="search-options-fieldset">'
					+'<legend>Search Options</legend>'
					+'<ul class="structural">'
						+'<li><label><input type="checkbox" class="boolean" value="1" name="must_have_picture"><span class="label">Has picture</span></label></li>'
						+'<li><label><input type="checkbox" class="boolean" value="1" name="must_have_description"><span class="label">Has description</span></label></li>'
						+'<li><label><input type="checkbox" class="boolean" value="1" name="has_login_time_limit"><span class="label">Visited within <input name="logged_in_within_days" type="text" maxlength="3" size="3" autocomplete="off"> days</span></label></li>'
						+'<li><label><input type="checkbox" class="boolean" value="1" name="has_time_limit"><span class="label">Registered within <input name="newer_than_days" type="text" maxlength="3" size="3" autocomplete="off"> days</span></label></li>'
						+'<li><label><input type="checkbox" class="boolean" value="1" name="exclude_contacted"><span class="label">Exclude contacts</span></label></li>'
						+'<li><label><input type="checkbox" class="boolean" value="1" name="match_shared_negatives"><span class="label">Match shared negatives</span></label> <span id="match-shared-negatives-explanation" title="">(?)</span></li>'
					+'</ul>'
				+'</fieldset>'
				+'<input id="search-form-submit-button" type="submit" value="Search">'
			+'</form>'
		).appendTo($searchFormContainer)

		;(function addCountrySelectOptions(){
			var $countrySelect = $( '#country-select' )
			var userCountryCode = pageData[ 'userCountryCode' ]
			var countries = pageData[ 'countriesWithUsers' ]
			var optionElements = []
			var countriesLength = countries.length
			for ( var i = 0; i < countriesLength; i++ ) {
				var country = countries[ i ]
				var countryCode = country[ 'code' ]
				var countryName = country[ 'name' ]
				optionElements.push( $( '<option></option>' ).val( countryCode ).text( countryName ) )
				if ( countryCode === userCountryCode ) {
					$( '<option></option>' ).val( countryCode ).text( countryName ).appendTo( $countrySelect )
				}
			}
			$countrySelect.append( optionElements )
			if ( 'US' === userCountryCode ) {
				$( '<div><label><input class="boolean" name="has_distance_limit" value="1" type="checkbox"><span class="label">Within <input id="max_distance-input" name="max_distance" type="text" maxlength="3" size="3" autocomplete="off"> miles</span></label></div>' ).appendTo( '#distance-fieldset' )
			}
		})() // addCountrySelectOptions

		$('#match-shared-negatives-explanation').on('click', function(){
			alert("If the \"Match shared negatives\" option is disabled, then the search will only match your positives with other users' positives, your positives with others' negatives, and your negatives with their positives, but it will not match your negatives with their negatives. The reasoning behind this is that shared dislikes aren't as important as shared likes. You will still see all shared negatives when viewing profiles.")
		})

		$('#toggle-type-checkboxes').on('click', function(event){
			$(event.currentTarget).closest('fieldset').find('input[type=checkbox]').click()
		})

		prefillSearchFormValues()
		return // functions below
		function prefillSearchFormValues () {
			if (searchFormData['mbti_types']) {
				;(function checkMbtiTypesCheckboxes(){
					var $checkboxes = $searchForm.find('input[name="mbti_types[]"]')
					for (var i = 0; i < $checkboxes.length; i++) {
						var $checkbox = $($checkboxes[i])
						var mbtiType = $checkbox.val()
						var isTypeSelected = searchFormData['mbti_types'].match(mbtiType)
						if (isTypeSelected) {
							$checkbox.prop('checked', true)
						}
					}
				})() // checkMbtiTypesCheckboxes
			}
			if (searchFormData['min_age']) {
				$searchForm.find('input[name="min_age"]').val(searchFormData['min_age'])
			}
			if (searchFormData['max_age']) {
				$searchForm.find('input[name="max_age"]').val(searchFormData['max_age'])
			}
			if (searchFormData['max_distance']) {
				$searchForm.find('input[name="max_distance"]').val(searchFormData['max_distance'])
				$searchForm.find('input[name="has_distance_limit"]').prop('checked', true)
			}
			if (searchFormData['country']) {
				$searchForm.find('select[name="country"]').val(searchFormData['country'])
			}
			;(function checkBooleanCheckboxes(){
				var searchOptionsFields = ['must_have_picture', 'must_have_description', 'match_shared_negatives', 'exclude_contacted', 'must_like_my_gender']
				for (var i = 0; i < searchOptionsFields.length; i++) {
					var fieldName = searchOptionsFields[i]
					if (searchFormData[fieldName] == 1) {
						$searchForm.find('input[name="'+fieldName+'"]').prop('checked', true)
					}
				}
			})() // checkBooleanCheckboxes
			if (parseInt(searchFormData['newer_than_days'])) {
				$searchForm.find('input[name="newer_than_days"]').val(searchFormData['newer_than_days'])
				$searchForm.find('input[name="has_time_limit"]').prop('checked', true)
			}
			if (parseInt(searchFormData['logged_in_within_days'])) {
				$searchForm.find('input[name="logged_in_within_days"]').val(searchFormData['logged_in_within_days'])
				$searchForm.find('input[name="has_login_time_limit"]').prop('checked', true)
			}
			if (!searchFormData['gender']) {
				$searchForm.find('input[name="genders[]"]').prop('checked', true)
			} else {
				$searchForm.find('input[name="genders[]"][value="'+searchFormData['gender']+'"]').prop('checked', true)
			}
		} // prefillSearchFormValues
	} // printSearchForm
} // printSearchPageInterface

