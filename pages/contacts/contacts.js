$(printContactsPageInterface)
function printContactsPageInterface () {
	// basic init
	var pageData = window['pageData']
	if (!pageData) {
		alert('Missing pageData')
		return
	}
	var $localContainer = $('main')

	printPageStructure()

	$(window).on('hashchange', processHashParams)
	processHashParams()
	return // functions below
	function processHashParams () {
		var hashParams = getHashParams()
		var currentTab = hashParams['tab']
		if ( ! currentTab ) {
			var hasUsersWaitingToHearFrom = pageData['users_waiting_to_hear_from_you'] && pageData['users_waiting_to_hear_from_you'].length
			if ( hasUsersWaitingToHearFrom ) {
				currentTab = 'waiting_to_hear_from_you'
			} else {
				currentTab = 'contacted_users'
			}
		}
		switch (currentTab) {
			case 'contacted_users':
				printContactedUsersTab();
				$('#contacted-users-tab').addClass('selected').siblings().removeClass('selected')
				break;
			case 'blocked_users':
				printBlockedUsersTab()
				$('#blocked-users-tab').addClass('selected').siblings().removeClass('selected')
				break;
			case 'reported_users':
				printReportedUsersTab()
				$('#reported-users-tab').addClass('selected').siblings().removeClass('selected')
				break;
			case 'waiting_to_hear_from_you':
				printWaitingToHearFromYouTab()
				$('#waiting-to-hear-from-you-tab').addClass('selected').siblings().removeClass('selected')
				break;
		}
	} // processHashParams

	function printPageStructure () {
		var $tabsContainer = $(
			'<div id="contacts-page-tabs-container">'
				+'<a id="waiting-to-hear-from-you-tab" href="#tab=waiting_to_hear_from_you">Waiting to Hear from You</a>'
				+'<a id="contacted-users-tab" href="#tab=contacted_users">Contacted Users</a>'
				+'<a id="blocked-users-tab" href="#tab=blocked_users">Blocked Users</a>'
				+'<a id="reported-users-tab" href="#tab=reported_users">Reported Users</a>'
			+'</div><div id="tab-content-container"></div>'
		)
		$tabsContainer.appendTo($localContainer)
	} // printPageStructure

	function printContactedUsersTab () {
		var $tabContentContainer = $('#tab-content-container')
		$tabContentContainer.empty()
		var users = pageData['contacted_users']
		if (!users || users.length == 0) {
			$('<p>You have no contacted users.</p>').appendTo($tabContentContainer)
			return
		}
		var tiles = getTabContent( users )
		$tabContentContainer.append( tiles )
	} // printContactedUsersTab

	function printBlockedUsersTab () {
		var $tabContentContainer = $('#tab-content-container')
		$tabContentContainer.empty()
		var users = pageData['blocked_users']
		if (!users || users.length == 0) {
			$('<p>You have no blocked users.</p>').appendTo($tabContentContainer)
			return
		}
		var tiles = getTabContent( users )
		$tabContentContainer.append( tiles )
	} // printBlockedUsersTab

	function printWaitingToHearFromYouTab () {
		var $tabContentContainer = $('#tab-content-container')
		$tabContentContainer.empty()
		var users = pageData['users_waiting_to_hear_from_you']
		if (!users || users.length == 0) {
			$('<p>You have no users waiting to hear from you.</p>').appendTo($tabContentContainer)
			return
		}
		var tiles = getTabContent( users )
		$tabContentContainer.append( tiles )
	} // printWaitingToHearFromYouTab

	function printReportedUsersTab () {
		var $tabContentContainer = $('#tab-content-container')
		$tabContentContainer.empty()
		var users = pageData['reported_users']
		if (!users || users.length == 0) {
			$('<p>You have no reported users.</p>').appendTo($tabContentContainer)
			return
		}
		var tiles = getTabContent( users )
		$tabContentContainer.append( tiles )
	} // printReportedUsersTab

	function getTabContent ( users ) {
		var tiles = []
		if ( ! users || ! users.length ) {
			return tiles
		}
		for ( var i = 0; i < users.length; i++ ) {
			var loopUser = users[ i ]
			var $userTile = $( '<div class="user-tile"><a class="user-link"><span class="thumbnail-container"></span><span class="username"></span></a></div>' )
			$userTile.find( '.username' ).text( loopUser[ 'username' ] )
			var linkUrl = '/profile?username=' +loopUser[ 'username' ]
			$userTile.find( '.user-link' ).attr( { 'href': linkUrl } )
			var thumbnailUrl = loopUser[ 'thumbnail_url' ]
			if ( thumbnailUrl ) {
				var primaryThumbnailWidth  = loopUser[ 'primary_thumbnail_width' ]
				var primaryThumbnailHeight = loopUser[ 'primary_thumbnail_height' ]
				var primaryThumbnailRotateAngle = loopUser[ 'primary_thumbnail_rotate_angle' ]
				var $thumbnail = $( '<img>' ).attr( {
					'src':    thumbnailUrl,
					'width':  primaryThumbnailWidth,
					'height': primaryThumbnailHeight,
					'alt':    loopUser[ 'username' ]
				} )
				var $thumbnailContainer = $userTile.find( '.thumbnail-container' )
				if ( primaryThumbnailRotateAngle ) {
					$thumbnail.css( 'transform', 'rotate(' +primaryThumbnailRotateAngle +'deg)' )
					if ( primaryThumbnailWidth > primaryThumbnailHeight ) {
						if ( primaryThumbnailRotateAngle == 90 || primaryThumbnailRotateAngle == 270 ) {
							$thumbnailContainer.css( 'writing-mode', 'tb' )
						}
					}
				}
				$thumbnail.prependTo( $thumbnailContainer );
			}
			tiles.push( $userTile )
		}
		return tiles
	} // getTabContent
} // printContactsPageInterface

