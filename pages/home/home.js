// Written by Vladimir Kornea for TypeTango.com
$(printHomePageInterface)
function printHomePageInterface () {
	var $localContainer = $('main')
	var pageData = window['pageData']
	if (!pageData) {
		$localContainer.html('<p class="error-message">Missing pageData</p>')
		return
	}
	var ageDistributionChartData = pageData['ageDistributionChartData']
	var countryStatisticsData = pageData['countryStatistics']
	var typeDistributionData = pageData['typeDistribution']
	var totalUsers = pageData['totalUsers']

	printTabStructure()

	$(window).on('hashchange', processHashParams)
	processHashParams()
	return // functions below
	function processHashParams () {
		var $tabContent = $('#demographic-tab-content').empty()
		var tab = getHashParams()['tab']
		switch (tab) {
			case 'ages':
				$tabContent.append( getAgeDistributionChart(ageDistributionChartData) )
				$('#age-distribution-tab').addClass('selected').siblings().removeClass('selected')
				break;
			case 'countries':
				$tabContent.append( getCountryStatisticsTable(countryStatisticsData) )
				$('#country-distribution-tab').addClass('selected').siblings().removeClass('selected')
				break;
			case 'types': default:
				$tabContent.append( getTypeDistributionTable(typeDistributionData, totalUsers) )
				$('#type-distribution-tab').addClass('selected').siblings().removeClass('selected')
				break;
		}
	} // processHashParams

	function printTabStructure () {
		var $demographicTabs = $(
			'<div id="demographic-tabs">'
				+'<div id="tab-row">'
					+'<a id="type-distribution-tab" class="tab" href="#tab=types">Type Distribution</a>'
					//+'<a id="age-distribution-tab" class="tab" href="#tab=ages">Age Distribution</a>'
					+'<a id="country-distribution-tab" class="tab" href="#tab=countries">Country Distribution</a>'
				+'</div>'
				+'<div id="demographic-tab-content"></div>'
			+'</div>'
		)
		$demographicTabs.appendTo($localContainer)
	} // printTabStructure

	function getCountryStatisticsTable (countryStatistics) {
		var $countryStatistics = $('<div id="country-statistics"><table><thead><tr><th>Users</th><th>Country</th></tr></thead><tbody></tbody></table></div>')
		var $tbody = $countryStatistics.find('tbody')
		for (var i = 0; i < countryStatistics.length; i++) {
			var countryData = countryStatistics[i]
			var countryName = countryData['country_name']
			var userCount = countryData['user_count']
			var $row = $('<tr><td class="user-count"></td><td class="country-name"></td></tr>')
			$row.find('.user-count').text(userCount)
			$row.find('.country-name').text(countryName)
			$row.appendTo($tbody)
		}
		return $countryStatistics[0]
	} // getCountryStatisticsTable

	function getAgeDistributionChart (ageDistributionChartData) {
		if (!ageDistributionChartData) {
			return
		}
		var $chart = $('<img alt="Age Distribution Chart">').attr({
			 'src':    ageDistributionChartData['chart_url']
			,'width':  ageDistributionChartData['width']
			,'height': ageDistributionChartData['height']
		})
		return $chart[0]
	} // getAgeDistributionChart

	function getTypeDistributionTable (typeDistribution, totalUsers) {
		if (!typeDistribution || !totalUsers) {
			return
		}
		var mbtiTypes = ['ISTJ','ISFJ','INFJ','INTJ','ISTP','ISFP','INFP','INTP','ESTP','ESFP','ENFP','ENTP','ESTJ','ESFJ','ENFJ','ENTJ']
		var chartBackgroundColor = 'LemonChiffon'
		var chartForegroundColor = 'RoyalBlue'
		var $typeDistributionTable = $(
			'<table id="type-distribution">'
				// +'<thead><tr><th colspan="4">Type Distribution</th></tr></thead>'
				+'<tbody></tbody>'
			+'</table>'
		)
		var $typeDistributionChartBody = $typeDistributionTable.find('tbody')
		for (var i = 0; i < mbtiTypes.length; i++) {
			var loopMbtiType = mbtiTypes[i]
			if (i % 4 === 0) {
				var $currentRowOfChart = $('<tr></tr>').appendTo($typeDistributionChartBody)
			}
			var totalUsersOfThisType = typeDistribution[loopMbtiType]
			var typeDistributionPercentage = Math.round(100 * typeDistribution[loopMbtiType] / totalUsers)
			var gradientCss = 'background:linear-gradient(to right, ' +chartForegroundColor +' ' +typeDistributionPercentage +'%, ' +chartBackgroundColor +' 0)'
			$('<td style="'+gradientCss+'"></td>').html(loopMbtiType+'<br>'+totalUsersOfThisType).appendTo($currentRowOfChart)
		}
		return $typeDistributionTable[0]
	} // getTypeDistributionTable
} // printHomePageInterface

