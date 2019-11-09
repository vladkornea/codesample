$(printContactsPageInterface)
function printContactsPageInterface () {
	// basic init
	var pageData = window['pageData']
	if (!pageData) {
		alert('Missing pageData')
		return
	}
	var $localContainer = $('#contacts-page-interface-container')

	printPageStructure()

	$(window).on('hashchange', processHashParams)
	processHashParams()
	return // functions below
	function processHashParams () {
		var hashParams = getHashParams()
		var currentTab = hashParams['tab'] || 'contacted_users'
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
		}
	} // processHashParams

	function printPageStructure () {
		var $tabsContainer = $(
			'<div id="contacts-page-tabs-container">'
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
		var contactedUsers = pageData['contacted_users']
		if (!contactedUsers || contactedUsers.length == 0) {
			$('<p>You have no contacted users.</p>').appendTo($tabContentContainer)
			return
		}
		for (var i = 0; i < contactedUsers.length; i++) {
			var loopUser = contactedUsers[i]
			var $userTile = $('<div class="user-tile"><a class="user-link"><span class="thumbnail-container"></span><span class="username"></span></a></div>')
			$userTile.find('.username').text(loopUser['username'])
			var linkUrl = '/profile?username=' +loopUser['username']
			$userTile.find('.user-link').attr({'href': linkUrl})
			var thumbnailUrl = loopUser['thumbnail_url']
			if (thumbnailUrl) {
				var primaryThumbnailWidth = loopUser['primary_thumbnail_width']
				var primaryThumbnailHeight = loopUser['primary_thumbnail_height']
				var primaryThumbnailRotateAngle = loopUser['primary_thumbnail_rotate_angle']
				var $thumbnail = $('<img>').attr({'src': thumbnailUrl, 'width':primaryThumbnailWidth, 'height':primaryThumbnailHeight})
				var $thumbnailContainer = $userTile.find('.thumbnail-container')
				if ( primaryThumbnailRotateAngle ) {
					$thumbnail.css( 'transform', 'rotate(' +primaryThumbnailRotateAngle +'deg)' )
					if ( primaryThumbnailWidth > primaryThumbnailHeight ) {
						if ( primaryThumbnailRotateAngle == 90 || primaryThumbnailRotateAngle == 270 ) {
							$thumbnailContainer.css('writing-mode', 'tb')
						}
					}
				}
				$thumbnail.prependTo($userTile.find('.thumbnail-container'));
			}
			$userTile.appendTo($tabContentContainer)
		}
	} // printContactedUsersTab

	function printBlockedUsersTab () {
		var $tabContentContainer = $('#tab-content-container')
		$tabContentContainer.empty()
		var blockedUsers = pageData['blocked_users']
		if (!blockedUsers || blockedUsers.length == 0) {
			$('<p>You have no blocked users.</p>').appendTo($tabContentContainer)
			return
		}
		for (var i = 0; i < blockedUsers.length; i++) {
			var loopUser = blockedUsers[i]
			var $userTile = $('<div class="user-tile"><a class="user-link"><span class="thumbnail-container"></span><span class="username"></span></a></div>')
			$userTile.find('.username').text(loopUser['username'])
			var linkUrl = '/profile?username=' +loopUser['username']
			$userTile.find('.user-link').attr({'href': linkUrl})
			var thumbnailUrl = loopUser['thumbnail_url']
			if (thumbnailUrl) {
				var primaryThumbnailWidth = loopUser['primary_thumbnail_width']
				var primaryThumbnailHeight = loopUser['primary_thumbnail_height']
				var primaryThumbnailRotateAngle = loopUser['primary_thumbnail_rotate_angle']
				var $thumbnail = $('<img class="thumbnail">').attr({'src': thumbnailUrl, 'width':primaryThumbnailWidth, 'height':primaryThumbnailHeight})
				var $thumbnailContainer = $userTile.find('.thumbnail-container')
				if ( primaryThumbnailRotateAngle ) {
					$thumbnail.css( 'transform', 'rotate(' +primaryThumbnailRotateAngle +'deg)' )
					if ( primaryThumbnailWidth > primaryThumbnailHeight ) {
						if ( primaryThumbnailRotateAngle == 90 || primaryThumbnailRotateAngle == 270 ) {
							$thumbnailContainer.css('writing-mode', 'tb')
						}
					}
				}
				$thumbnail.appendTo( $userTile.find('.thumbnail-container') );
			}
			$userTile.appendTo($tabContentContainer)
		}
	} // printBlockedUsersTab

	function printReportedUsersTab () {
		var $tabContentContainer = $('#tab-content-container')
		$tabContentContainer.empty()
		var reportedUsers = pageData['reported_users']
		if (!reportedUsers || reportedUsers.length == 0) {
			$('<p>You have no reported users.</p>').appendTo($tabContentContainer)
			return
		}
		for (var i = 0; i < reportedUsers.length; i++) {
			var loopUser = reportedUsers[i]
			var $userTile = $('<div class="user-tile"><a class="user-link"><span class="thumbnail-container"></span><span class="username"></span></a></div>')
			$userTile.find('.username').text(loopUser['username'])
			var linkUrl = '/profile?username=' +loopUser['username']
			$userTile.find('.user-link').attr({'href': linkUrl})
			var thumbnailUrl = loopUser['thumbnail_url']
			if (thumbnailUrl) {
				var primaryThumbnailWidth = loopUser['primary_thumbnail_width']
				var primaryThumbnailHeight = loopUser['primary_thumbnail_height']
				var primaryThumbnailRotateAngle = loopUser['primary_thumbnail_rotate_angle']
				var $thumbnail = $('<img>').attr({'src': thumbnailUrl, 'width':primaryThumbnailWidth, 'height':primaryThumbnailHeight})
				var $thumbnailContainer = $userTile.find('.thumbnail-container')
				if ( primaryThumbnailRotateAngle ) {
					$thumbnail.css( 'transform', 'rotate(' +primaryThumbnailRotateAngle +'deg)' )
					if ( primaryThumbnailWidth > primaryThumbnailHeight ) {
						if ( primaryThumbnailRotateAngle == 90 || primaryThumbnailRotateAngle == 270 ) {
							$thumbnailContainer.css('writing-mode', 'tb')
						}
					}
				}
				$thumbnail.prependTo( $thumbnailContainer );
			}
			$userTile.appendTo($tabContentContainer)
		}
	} // printReportedUsersTab
} // printContactsPageInterface

