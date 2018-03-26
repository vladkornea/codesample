$(printTestPageInterface)
function printTestPageInterface () {
	// nosuch.nosuch // test error handling
	;(function attempt404 () {
		// apiCall('nosuch')
	})() // attempt404
	;(function once () {
		;(function twice () {
			;(function thrice () {
				apiCall('nosuch')
				nosuch.nosuch
			})()
		})()
	})()

	// testMomentJsWithTz()
	// apiCall('http://dev.kornea.com')
	// apiCall('https://dev.kornea.com')

} // printTestPageInterface

function testMomentJsWithTz () {
	// alert(moment.tz.guess()) // America/New_York
	// moment.tz.setDefault(moment.tz.guess()) // America/New_York has now been set as the default timezone
	// alert(moment.tz().format('z')) // shows UTC

	// moment.tz.setDefault('America/New_York') // America/New_York has now been set as the default timezone
	// alert(moment.tz().format('z')) // shows UTC
	// setDefault() doesn't work
} // testMomentJsWithTz

