<?php
/*
 * A simple Calendar
 */

$wgExtensionFunctions[] = "wfCalendarExtension";

// function adds the wiki extension
function wfCalendarExtension() {
	global $wgHooks, $wgSpecialPages;
	$wgSpecialPages[ 'Calendar' ] = 'Calendar';
	$wgHooks['EditFilter'][] = "CalendarEditFilter";
}

$wgLocalPath = str_replace("\\", "/" , substr($_SERVER["SCRIPT_FILENAME"], 0, strlen($_SERVER["SCRIPT_FILENAME"]) - strlen($_SERVER["SCRIPT_NAME"])));

require_once( "$IP/includes/Sanitizer.php" );

function CalendarEditFilter($editPage, $textbox1, $section) {
	global $IP;

	require_once( "$IP/includes/Article.php" );
	require_once( "$IP/includes/EditPage.php" );
	require_once( "$IP/includes/Title.php" );

	$title = $editPage->mArticle->getTitle();
	if ( !preg_match( '/^Calendar-/', $title->getText() ) )
		return true;

	if ( $section == 'new' && $editPage->summary == '' )
		return false;

	if ( $textbox1 == '' )
		$editPage->textbox1 = "\n";

	return true;
}

// calendar class
class Calendar extends SpecialPage {   
	function __construct()
	{
		parent::__construct( 'Calendar' );

		// set the calendar's date
		$today = getdate();

		$this->month = $today['mon'];
		$this->year = $today['year'];
		$this->calendarStartYear = $this->year;
	}

	function getDescription() {
		return "Calendar";
	}

	// Calculate the number of days in a month, taking into account leap years.
	function getDaysInMonth()
	{
		if ($this->month < 1 || $this->month > 12)
		{
			return 0;
		}

		$d = $this->daysInMonth[$this->month - 1];

		if ($this->month == 2)
		{
			// Check for leap year
			// Forget the 4000 rule, I doubt I'll be around then...

			if ($this->year%4 == 0)
			{
				if ($this->year%100 == 0)
				{
					if ($this->year%400 == 0)
					{
						$d = 29;
					}
				}
				else
				{
					$d = 29;
				}
			}
		}

		return $d;
	}

	// Generate the HTML for a given month
	function getHTML()
	{   
		global $wgScriptPath, $wgLocalPath;

		/***** Replacement tags *****/
		// the month select box [[MonthSelect]]
		$tag_monthSelect = "";
		// the previous month button [[PreviousMonthButton]]
		$tag_previousMonthButton = "";
		// the next month button [[NextMonthButton]]
		$tag_nextMonthButton = "";
		// the year select box [[YearSelect]]
		$tag_yearSelect = "";
		// the previous year button [[PreviousYearButton]]
		$tag_previousYearButton = "";
		// the next year button [[NextYearButton]]
		$tag_nextYearButton = "";
		// the calendar name [[CalendarName]]
		$tag_calendarName = "";
		// the calendar month [[CalendarMonth]]
		$tag_calendarMonth = "";
		// the calendar year [[CalendarYear]]
		$tag_calendarYear = "";
		// the calendar day [[Day]]
		$tag_day = "";
		// the add event link [[AddEvent]]
		$tag_addEvent = ""; 
		// the event list [[EventList]]
		$tag_eventList = "";


		/***** Calendar parts (loaded from template) *****/
		// html for the entire template
		$html_template = "";
		// calendar pieces
		$html_calendar_start = "";
		$html_calendar_end = "";
		// the calendar header
		$html_header = "";
		// the day heading
		$html_day_heading = "";
		// the calendar week pieces
		$html_week_start = "";
		$html_week_end = "";
		// the calendar footer
		$html_footer = "";
		// arrays for the day formats
		$daysNormalHTML = array();
		$daysMissingHTML = array();
		$daysSelectedHTML = array();

		/***** Other variables *****/
		// the string to return
		$calendarString = "";
		// the days in the current month
		$daysInMonth = $this->getDaysInMonth();
		// the date for the first day of the month
		$firstDate = getdate(mktime(12, 0, 0, $this->month, 1, $this->year));
		// the first day of the month
		$first = $firstDate["wday"];
		// today's date
		$todayDate = getdate();
		// if the day being processed is today
		$isSelected = false;
		// if the calendar cell being processed is in the current month
		$isMissing = false;

		/***** Paths to important files *****/
		// the path to this extension (install location)
		$calendarExtensionPath = preg_replace('/[^\\/]*$/', "", __FILE__);
		// referrer (the page with the calendar currently displayed)
		$referrerURL = preg_replace('/(Calendar).*/', '\1',
				$_SERVER['PHP_SELF']);
		//if ($_SERVER['QUERY_STRING'] != '')
		//$referrerURL .= "?" . $_SERVER['QUERY_STRING'];
		// the path to the CalendarAdjust.php file
		$calendarAdjustPath = $calendarExtensionPath . "/CalendarAdjust.php";
		// the template file (full path needed)
		$calendarTemplate = $calendarExtensionPath . "/calendar_template.html";

		/***** Build the known tag elements (non-dynamic) *****/
		// set the month's name tag
		$tag_calendarName = str_replace('_', ' ', $this->name);
		if ($tag_calendarName == "") {
			$tag_calendarName = "Calendar";
		}

		// set the month's mont and year tags
		$tag_calendarMonth = $this->monthNames[$this->month - 1];
		$tag_calendarYear = $this->year;

		// build the month select box
		$tag_monthSelect = "<select onChange=\"javascript:document.location='" . $referrerURL . "/" . $this->year . "-' + this.options[this.selectedIndex].value;\">\n";
		for ($i = 0; $i < count($this->monthNames); $i += 1) {
			if ($i + 1 == $this->month) {
				$tag_monthSelect .= "<option value=\"" . ($i + 1) . "\" selected=\"true\">" . $this->monthNames[$i] . "</option>\n";
			}
			else {
				$tag_monthSelect .= "<option value=\"" . ($i + 1) . "\">" . $this->monthNames[$i] . "</option>\n";
			}
		}
		$tag_monthSelect .= "</select>";

		// build the year select box, with +/- 5 years in relation to the currently selected year
		$tag_yearSelect = "<select onChange=\"javascript:document.location='" .$referrerURL . "/' + this.options[this.selectedIndex].value + '-" . $this->month . "';\">\n";
		for ($i = $this->calendarStartYear; $i <= $todayDate['year'] + $this->yearsAhead; $i += 1) {
			if ($i == $this->year) {
				$tag_yearSelect .= "<option value=\"" . $i . "\" selected=\"true\">" . $i . "</option>\n";
			}
			else {
				$tag_yearSelect .= "<option value=\"" . $i . "\">" . $i . "</option>\n";
			}
		}
		$tag_yearSelect .= "</select>";

		// build the previous month button
		$tag_previousMonthButton = "<input type=\"button\" value=\"<<\" onClick=\"javascript:document.location='" . $referrerURL . "/" . ($this->year - ($this->month == 1)) . "-" . ($this->month == 1 ? 12 : $this->month - 1) . "'\">";

		// build the next month button
		$tag_nextMonthButton = "<input type=\"button\" value=\">>\" onClick=\"javascript:document.location='" . $referrerURL . "/" . ($this->year + ($this->month == 12)) . "-" . ($this->month == 12 ? 1 : $this->month + 1) . "'\">";

		// build the previous year button
		$tag_previousYearButton = "<input type=\"button\" value=\"<<\" onClick=\"javascript:document.location='" . $referrerURL . "/" . ($this->year - 1) . "-" . $this->month . "'\">";

		// build the next year button
		$tag_nextYearButton = "<input type=\"button\" value=\">>\" onClick=\"javascript:document.location='" . $referrerURL . "/" . ($this->year + 1) . "-" . $this->month . "'\">";


		/***** load the html code pieces from the template *****/
		// load the template file
		$html_template = file_get_contents($calendarTemplate);

		// grab the HTML for the calendar
		// calendar pieces
		$html_calendar_start = $this->searchHTML($html_template, "<!-- Calendar Start -->", "<!-- Header Start -->");
		$html_calendar_end = $this->searchHTML($html_template, "<!-- Footer End -->", "<!-- Calendar End -->");;
		// the calendar header
		$html_header = $this->searchHTML($html_template, "<!-- Header Start -->", "<!-- Header End -->");
		// the day heading
		$html_day_heading = $this->searchHTML($html_template, "<!-- Day Heading Start -->", "<!-- Day Heading End -->");
		// the calendar week pieces
		$html_week_start = $this->searchHTML($html_template, "<!-- Week Start -->", "<!-- Sunday Start -->");
		$html_week_end = $this->searchHTML($html_template, "<!-- Saturday End -->", "<!-- Week End -->");
		// the individual day cells
		$daysNormalHTML[0] = $this->searchHTML($html_template, "<!-- Sunday Start -->", "<!-- Sunday End -->");
		$daysNormalHTML[1] = $this->searchHTML($html_template, "<!-- Monday Start -->", "<!-- Monday End -->");
		$daysNormalHTML[2] = $this->searchHTML($html_template, "<!-- Tuesday Start -->", "<!-- Tuesday End -->");
		$daysNormalHTML[3] = $this->searchHTML($html_template, "<!-- Wednesday Start -->", "<!-- Wednesday End -->");
		$daysNormalHTML[4] = $this->searchHTML($html_template, "<!-- Thursday Start -->", "<!-- Thursday End -->");
		$daysNormalHTML[5] = $this->searchHTML($html_template, "<!-- Friday Start -->", "<!-- Friday End -->");
		$daysNormalHTML[6] = $this->searchHTML($html_template, "<!-- Saturday Start -->", "<!-- Saturday End -->");

		$daysSelectedHTML[0] = $this->searchHTML($html_template, "<!-- Selected Sunday Start -->", "<!-- Selected Sunday End -->");
		$daysSelectedHTML[1] = $this->searchHTML($html_template, "<!-- Selected Monday Start -->", "<!-- Selected Monday End -->");
		$daysSelectedHTML[2] = $this->searchHTML($html_template, "<!-- Selected Tuesday Start -->", "<!-- Selected Tuesday End -->");
		$daysSelectedHTML[3] = $this->searchHTML($html_template, "<!-- Selected Wednesday Start -->", "<!-- Selected Wednesday End -->");
		$daysSelectedHTML[4] = $this->searchHTML($html_template, "<!-- Selected Thursday Start -->", "<!-- Selected Thursday End -->");
		$daysSelectedHTML[5] = $this->searchHTML($html_template, "<!-- Selected Friday Start -->", "<!-- Selected Friday End -->");
		$daysSelectedHTML[6] = $this->searchHTML($html_template, "<!-- Selected Saturday Start -->", "<!-- Selected Saturday End -->");

		$daysMissingHTML[0] = $this->searchHTML($html_template, "<!-- Missing Sunday Start -->", "<!-- Missing Sunday End -->");
		$daysMissingHTML[1] = $this->searchHTML($html_template, "<!-- Missing Monday Start -->", "<!-- Missing Monday End -->");
		$daysMissingHTML[2] = $this->searchHTML($html_template, "<!-- Missing Tuesday Start -->", "<!-- Missing Tuesday End -->");
		$daysMissingHTML[3] = $this->searchHTML($html_template, "<!-- Missing Wednesday Start -->", "<!-- Missing Wednesday End -->");
		$daysMissingHTML[4] = $this->searchHTML($html_template, "<!-- Missing Thursday Start -->", "<!-- Missing Thursday End -->");
		$daysMissingHTML[5] = $this->searchHTML($html_template, "<!-- Missing Friday Start -->", "<!-- Missing Friday End -->");
		$daysMissingHTML[6] = $this->searchHTML($html_template, "<!-- Missing Saturday Start -->", "<!-- Missing Saturday End -->");

		// the calendar footer
		$html_footer = $this->searchHTML($html_template, "<!-- Footer Start -->", "<!-- Footer End -->");


		/***** Begin Building the Calendar (pre-week) *****/    	
		// add the header to the calendar HTML code string
		$calendarString .= $html_calendar_start;
		$calendarString .= $html_header;
		$calendarString .= $html_day_heading;


		/***** Search and replace variable tags at this point *****/
		$calendarString = str_replace("[[MonthSelect]]", $tag_monthSelect, $calendarString);
		$calendarString = str_replace("[[PreviousMonthButton]]", $tag_previousMonthButton, $calendarString);
		$calendarString = str_replace("[[NextMonthButton]]", $tag_nextMonthButton, $calendarString);
		$calendarString = str_replace("[[YearSelect]]", $tag_yearSelect, $calendarString);
		$calendarString = str_replace("[[PreviousYearButton]]", $tag_previousYearButton, $calendarString);
		$calendarString = str_replace("[[NextYearButton]]", $tag_nextYearButton, $calendarString);
		$calendarString = str_replace("[[CalendarName]]", $tag_calendarName, $calendarString);
		$calendarString = str_replace("[[CalendarMonth]]", $tag_calendarMonth, $calendarString);    	
		$calendarString = str_replace("[[CalendarYear]]", $tag_calendarYear, $calendarString);    	


		/***** Begin building the calendar days *****/
		// determine the starting day offset for the month
		$dayOffset = -$first;

		// determine the number of weeks in the month
		$numWeeks = floor(($daysInMonth - $dayOffset + 6) / 7);  	

		// begin writing out month weeks
		for ($i = 0; $i < $numWeeks; $i += 1) {
			// write out the week start code
			$calendarString .= $html_week_start;

			// write out the days in the week
			for ($j = 0; $j < 7; $j += 1) {
				$thedate = getdate(mktime(12, 0, 0, $this->month, ($dayOffset + 1), $this->year));
				$today = getdate();

				// determine the HTML to grab for the day
				$tempString = "";
				if ($dayOffset >= 0 && $dayOffset < $daysInMonth) {
					if ($thedate['mon'] == $today['mon'] && $thedate['year'] == $today['year'] && $thedate['mday'] == $today['mday']) {
						$tempString = $daysSelectedHTML[$j];
					}
					else {
						$tempString = $daysNormalHTML[$j];	  					
					}

					// determine variable tag values
					// day value
					$tag_day = ($dayOffset + 1);
					// event list tag
					// grab the events for the day
					$events = $this->getArticlesForDay($this->month, ($dayOffset + 1), $this->year);

					$dayLink = preg_replace("/Special:Calendar.*/", "", $referrerURL) . "Calendar-" . $this->year . "-" . $this->month . "-" . ($dayOffset + 1);
					// write out the links for each event
					$tag_eventList = "";
					if (count($events) > 0) {
						$tag_eventList .= "<ul>";
						for ($k = 0; $k < count($events); $k += 1) {
							$tag_eventList .= "<li><a href=" . $dayLink . "#" . Sanitizer::escapeId( $events[$k] ) . ">" . $events[$k] . "</a></li>";
						}
						$tag_eventList .= "</ul>";
					}

					// add event link value
					$tag_addEvent = "<a href=\"" . $dayLink . "?action=edit&section=new\" target=_blank>Add Event</a>"; 
					// replace variable tags in the string
					$tempString = str_replace("[[Day]]", $tag_day, $tempString);
					$tempString = str_replace("[[AddEvent]]", $tag_addEvent, $tempString);
					$tempString = str_replace("[[EventList]]", $tag_eventList, $tempString);
				} 
				else {
					$tempString = $daysMissingHTML[$j];
				}					

				// add the generated day HTML code to the calendar HTML code
				$calendarString .= $tempString;

				// move to the next day
				$dayOffset += 1;
			}

			// add the week end code
			$calendarString .= $html_week_end; 
		}

		/***** Do footer *****/
		$tempString = $html_footer;

		// replace potential variables in footer
		$tempString = str_replace("[[MonthSelect]]", $tag_monthSelect, $tempString);
		$tempString = str_replace("[[PreviousMonthButton]]", $tag_previousMonthButton, $tempString);
		$tempString = str_replace("[[NextMonthButton]]", $tag_nextMonthButton, $tempString);
		$tempString = str_replace("[[YearSelect]]", $tag_yearSelect, $tempString);
		$tempString = str_replace("[[PreviousYearButton]]", $tag_previousYearButton, $tempString);
		$tempString = str_replace("[[NextYearButton]]", $tag_nextYearButton, $tempString);
		$tempString = str_replace("[[CalendarName]]", $tag_calendarName, $tempString);
		$tempString = str_replace("[[CalendarMonth]]", $tag_calendarMonth, $tempString);    	
		$tempString = str_replace("[[CalendarYear]]", $tag_calendarYear, $tempString);

		$calendarString .= $tempString;

		/***** Do calendar end code *****/
		$calendarString .= $html_calendar_end;

		// return the generated calendar code
		return $this->stripLeadingSpace($calendarString);  	
	}

	// returns the HTML that appears between two search strings.
	// the returned results include the text between the search strings,
	// else an empty string will be returned if not found.
	function searchHTML($html, $beginString, $endString) {
		$temp = split($beginString, $html);
		if (count($temp) > 1) {
			$temp = split($endString, $temp[1]);
			return $temp[0];
		}
		return "";
	}

	// strips the leading spaces and tabs from lines of HTML (to prevent <pre> tags in Wiki)
	function stripLeadingSpace($html) {
		$index = 0;

		$temp = split("\n", $html);

		$tempString = "";
		while ($index < count($temp)) {
			while (strlen($temp[$index]) > 0 && (substr($temp[$index], 0, 1) == ' ' || substr($temp[$index], 0, 1) == '\t')) {
				$temp[$index] = substr($temp[$index], 1);
			}
			$tempString .= $temp[$index];
			$index += 1;    		
		}

		return $tempString;	
	}

	// returns an array of existing article names for a specific day
	function getArticlesForDay($month, $day, $year) {
		// the name of the article to check for
		$articleName = "";
		// the article count
		$articleCount = 0;
		// the array of article names
		$articleNames = array();

		// keep searching until name not found
		// generate name
		$articleName = "Calendar-" . $year . "-" . $month . "-" . $day;
		$article = new Article(Title::newFromText($articleName));
		if ($article->exists()) {
			$content = $article->getContent();
			$i = 0;
			while ($i++ < 20) {
				$section = $article->getSection($content, $i);
				if ($section == '')
					break;
				else
					$articleNames[] = preg_replace('/== (.*) ==.*/', '\1', $section);
			}
		}

		return $articleNames;
	}

	// the current month
	var $month = 1;
	// the current year
	var $year = 2006;
	// the number of years to include ahead of this year
	var $yearsAhead = 3;

	/*
	   The labels to display for the days of the week. The first entry in this array
	   represents Sunday.
	 */
	var $dayNames = array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");

	/*
	   The labels to display for the months of the year. The first entry in this array
	   represents January.
	 */
	var $monthNames = array("January", "February", "March", "April", "May", "June",
			"July", "August", "September", "October", "November", "December");


	/*
	   The number of days in each month. You're unlikely to want to change this...
	   The first entry in this array represents January.
	 */
	var $daysInMonth = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);   

	function execute( $par ) {
		global $wgOut;

		$params = split( '-', $par );
		if ( count($params) > 0 ) {
			if ($params[0] > 0)
				$this->year = $params[0];
			if ( count($params) > 1)
				$this->month = $params[1];
		}

		$this->setHeaders();

		$wgOut->addHTML( $this->getHTML() );
	}

}

