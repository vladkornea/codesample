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
			'<section id="demographic-tabs">'
				+'<nav id="tab-row">'
					+'<a id="type-distribution-tab" class="tab" href="#tab=types">Type Distribution</a>'
					+'<a id="country-distribution-tab" class="tab" href="#tab=countries">Country Distribution</a>'
				+'</nav>'
				+'<div id="demographic-tab-content"></div>'
			+'</section>'
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
		var typeWords = new Map([
			['I', 'Introverted'],
			['E', 'Extroverted'],
			['S', 'Sensing'],
			['N', 'iNtuitive'],
			['T', 'Thinking'],
			['F', 'Feeling'],
			['J', 'Judging'],
			['P', 'Perceiving']
		])
		var functionStacksOfMbtiTypes = new Map([
			['ISTJ', 'SiTeFiNe'],
			['ISFJ', 'SiFeTiNe'],
			['INFJ', 'NiFeTiSe'],
			['INTJ', 'NiTeFiSe'],
			['ISTP', 'TiSeNiFe'],
			['ISFP', 'FiSeNiTe'],
			['INFP', 'FiNeSeTe'],
			['INTP', 'TiNeSiFe'],
			['ESTP', 'SeTiFeNi'],
			['ESFP', 'SeFiTeNi'],
			['ENFP', 'NeFiTeSi'],
			['ENTP', 'NeTiFeSi'],
			['ESTJ', 'TeSiNeFi'],
			['ESFJ', 'FeSiNeTi'],
			['ENFJ', 'FeNiSeTi'],
			['ENTJ', 'TeNiSeFi']
		])
		var functionAliases = new Map([
			['Ti', 'Clarity'],
			['Te', 'Purpose'],
			['Fi', 'Emotion'],
			['Fe', 'Relationships'],
			['Si', 'Memory'],
			['Se', 'Physical Interaction'],
			['Ne', 'Imagination'],
			['Ni', 'Perspective']
		])
		var $typeDistributionChartBody = $('<tbody></tbody>')
		for (var i = 0; i < mbtiTypes.length; i++) {
			var loopMbtiType = mbtiTypes[i]
			if (i % 4 === 0) {
				var $currentRowOfChart = $('<tr></tr>').appendTo($typeDistributionChartBody)
			}
			var functionStack = functionStacksOfMbtiTypes.get( loopMbtiType )
			var typeDesc = (
				typeWords.get( loopMbtiType.charAt(0) )
				+ ' ' + typeWords.get( loopMbtiType.charAt(1) )
				+ ' ' + typeWords.get( loopMbtiType.charAt(2) )
				+ ' ' + typeWords.get( loopMbtiType.charAt(3) )
				+ ' - Dominant ' + functionAliases.get( functionStack.substr(0, 2) )
				+ ', Auxiliary ' + functionAliases.get( functionStack.substr(2, 2) )
				+ ', Tertiary ' + functionAliases.get( functionStack.substr(4, 2) )
				+ ', Inferior ' + functionAliases.get( functionStack.substr(6, 2) )
			)
			var $mbtiType = $('<abbr></abbr>').text(loopMbtiType)
				.prop('title', typeDesc)
			var typeDistributionPercentage = Math.round(100 * typeDistribution[loopMbtiType] / totalUsers)
			var $percentage = $('<abbr></abbr>').text(typeDistribution[loopMbtiType])
				.prop('title', typeDistributionPercentage+'%')
			var gradientCss = 'linear-gradient(to right, RoyalBlue ' +typeDistributionPercentage +'%, LemonChiffon 0)'
			$('<td></td>').css('background', gradientCss)
				.append($mbtiType, '<br>', $percentage)
				.appendTo($currentRowOfChart)
		}
		return $('<table id="type-distribution"></table>').append($typeDistributionChartBody).get(0)
	} // getTypeDistributionTable
} // printHomePageInterface

