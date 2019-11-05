/**
 * Frontier JQuery Full Calendar Plugin.
 *
 * June 24, 2010 - v1.3.2 - Bug fix in getAgendaItemByDataAttr(). I suck...
 * June 23, 2010 - v1.3.1 - Bug fix in deleteAgendaItemByDataAttr() function, and new reRenderAgendaItems() function!
 * June 22, 2010 - v1.3   - Tooltip support. Additional callbacks, applyAgendaTooltipCallback, agendaDragStartCallback, 
 *						    and agendaDragStopCallback.
 * June 17, 2010 - v1.2   - Drag-and-drop, CSS updates, allDay event option.
 * June 14, 2010 - v1.1   - Few bug fixes, tweaks, and basic VEVENT ical support.
 * June 09, 2010 - v1.0   - Initial version.
 *
 * Seth Lenzi
 * slenzi@gmail.com
 *
 * This plugin is free. Do with it as you want. I claim no responsibility if it explodes and ruins your day. ;)
 *
 * MIT License - http://en.wikipedia.org/wiki/MIT_License
 *
 * Dependencies:
 *
 * This plugin requires the following javascript libraries.
 *
 * 1) JQuery Core and JQuery UI.
 *    For IE you need to use the inlcuded modified version of jQuery Core, js/jquery-core/jquery-1.4.2-ie-fix.min.js for drag-and-drop.
 *    Drag-and-drop works fine in Chrome, Opera, Firefox, and Safari using unmodified jQuery core. For more info read the txt file
 *    that should have been included with this plugin at js/jquery-core/README-IE-FIX.TXT
 *    http://jquery.com/
 *    http://jqueryui.com   
 *
 * 2) jshashtable.js
 *    Should be included with this plugin in the js/lib/ folder.
 *    Tim Down
 *    http://code.google.com/p/jshashtable/
 *    http://www.timdown.co.uk/jshashtable/index.html
 *
 ******************************************************
 **** These last ones are already inlcued in this file. 
 ****************************************************** 
 * 
 * 3) WResize is the jQuery plugin for fixing the IE window resize bug.
 *    This plugin is already included at the end of this file.
 *    Copyright 2007 / Andrea Ercolino
 *    LICENSE: http://www.opensource.org/licenses/mit-license.php 
 *    WEBSITE: http://noteslog.com/
 *
 * 4) Javascript iCal parsers. Merci!
 *    This is already included in this file.
 *    http://code.google.com/p/ijp/
 */ 
(function($) {

	// keep track of options for each instance
	var allOptions = new Array();
	
	// using jshashset.js library
	var myCalendars = new Hashtable();
	
	/**
	 * String startsWith function. 
	 */
	String.prototype.startsWith = function(str){
		return (this.match("^"+str)==str);
	}
	
	/**
	 * An agenda object to store data for a single agenda item on the calendar.
	 *
	 * An agenda item may wrap weeks so it will have more than one <div/> element to render.
	 *
	 * @param title		- (String)  - The title to be displayed on the agenda <div/>. This is what users will see on
	 *								  the calendar agenda item along with the start and end dates.
	 * @param startDate - (Date)    - The date the agenda item starts.
	 * @param endDate   - (Date)    - The date the agenda item ends.
	 * @param allDay    - (boolean) - True if an all day event (do not show start time on agenda div element), false otherwise. False by default.
	 * @param hashData  - (Hashtable from jshashtable.js) - A Hashtable that contains all data for the agenda item.
	 */	
	function CalendarAgendaItem(title,startDate,endDate,allDay,hashData) {
		
		// a unique ID to identify this agenda item. The Calendar will use this internal ID to locate this agenda item for various purposes.
		// users can store their own ID in the agenda data hash.
		this.id = 0;
		
		// agenda title to be displayed on div element
		this.titleValue = title; 
		// start date & time
		this.startDt = startDate;
		// end date and time
		this.endDt = endDate;
		// by default we show the start time on the agenda div element. If allDayEvent is set to true we won't show the time.
		this.allDayEvent = allDay;
		
		// By default we use the colors defined in the CSS file but we can override those settings
		// if we set these variables.
		this.backgroundColor = null;
		this.foregroundColor = null;
		
		// using jshashset.js library
		// an agenda item can store arbitrary data. we have no idea what the user will want to
		// store so we give them a hashtable so they can store whatever.
		this.agendaData = hashData;

		this.isAllDay = function(){
			return this.allDayEvent;
		};
		
		this.setAllDay = function(b){
			this.allDayEvent = b;
		};
		
		this.setBackgroundColor = function(c){
			this.backgroundColor = c;
		};
		
		this.setForegroundColor = function(c){
			this.foregroundColor = c;
		};

		this.getBackgroundColor = function(){
			return this.backgroundColor;
		};
		
		this.getForegroundColor = function(){
			return this.foregroundColor;
		};			
		
		/**
		 * Set the ID. This ID is generated and used by the Calendar object
		 *
		 * @param agendaId - integer
		 */
		this.setAgendaId = function(agendaId){
			this.id = agendaId;
		};
		
		/**
		 * Get the id. This ID is used by the Calendar object.
		 *
		 * @return integer
		 */
		this.getAgendaId = function(){
			return this.id;
		};
		
		/**
		 * Get the agenda start date.
		 *
		 * @return Date object
		 */
		this.getStartDate = function(){
			return this.startDt;
		};
		
		/**
		 * Get the agenda end date.
		 *
		 * @return Date object
		 */		
		this.getEndDate = function(){
			return this.endDt;
		};
		
		/**
		 * Set the agenda start date.
		 *
		 */
		this.setStartDate = function(d){
			this.startDt = d;
		};
		
		/**
		 * Set the agenda end date.
		 *
		 */		
		this.setEndDate = function(d){
			this.endDt = d;
		};		

		/**
		 * Get the agenda title.
		 *
		 * @return string
		 */		
		this.getTitle = function(){
			return this.titleValue;
		};
		
		/**
		 * store some data in the agenda item
		 *
		 * @param key   - (Object) - The key for the data.
		 * @param value - (Object) - The data to store.
		 */
		this.addAgendaData = function(key,value){
			this.agendaData.put(key,value);
		};	
		
		/**
		 * get some data in the agenda item
		 *
		 * @param key - (Object) - The key used to lookup the item.
		 * @return      (Object) - The data that was stored, or null.
		 */
		this.getAgendaData = function(key){
			return this.agendaData.get(key);
		};
		
		this.getAgendaDataHash = function(){
			return this.agendaData;
		};			

		/**
		 * Debug function.
		 */
		this.toString = function(){
			var s = "Title: " + this.titleValue + "\n";
			s += "Start Date: " + this.startDt + "\n";
			s += "End Date: " + this.endDt + "\n";
			if(this.agendaData != null && this.agendaData.size() > 0){
				var keys = this.agendaData.keys();
				for(var keyIndex = 0; keyIndex < keys.length; keyIndex++){
					var keyName = keys[keyIndex];
					var val = this.getAgendaData(keyName);
					s += keyName + ": " + val + "\n";
				}
			}
			return s;
		};
	
	};

	/**
	 * One day cell in the calendar.
	 *
	 * @param jqyObj - (JQuery object) - Reference to the day cell <div/> element.
	 */
	function CalendarDayCell(jqyObj) {
		
		// jquery object that reference one day cell <div/> 
		this.jqyObj = jqyObj;
		
		// A Date object with the year, month, and day set for this day cell.
		this.date = null;
		
		/*
		All the agenda <div> elements being rendered over this day cell.
		keys are integers (agenda ID), values are jquery objects (agenda <div> elements)
		*/
		//this.agendaDivHash = new Hashtable();
		this.agendaDivArray = new Array();
		
		// the query object for the "more" link
		this.jqyMoreDiv = null;
		
		// add css class
		this.addClass = function(c){
			this.jqyObj.addClass(c);
		};

		// remove class, or all classes
		this.removeClass = function(name){
			if(name != null && name != ""){
				this.jqyObj.removeClass(name);
			}else{
				this.jqyObj.removeClass();
			}
		};
		
		/**
		 * Add the "more" link div and renders it. If you add a more link and one is already there
		 * than the existing one is removed and the new one is added.
		 */
		this.addMoreDiv = function(element){
			if(this.jqyMoreDiv == null){
				this.jqyMoreDiv = element;
				this.appendHtml(element);
			}else{
				this.jqyMoreDiv.remove();
				this.jqyMoreDiv = element;
				this.appendHtml(element);
			}
		};
		
		/**
		 * Checks to see if this day cell already has a "more" div link.
		 *
		 * @return true or false
		 */
		this.hasMoreDiv = function(){
			if(this.jqyMoreDiv != null){
				return true;
			}
			return false;
		};
		
		/**
		 * Add an agenda <div> element.
		 *
		 * @param id - integer - the agenda ID.
		 * @param element - jquery object - the agenda <div> element.
		 */
		this.addAgendaDivElement = function(id,element){
			//this.agendaDivHash.put(id,element);
			this.agendaDivArray.push(element);
		};
		
		/**
		 * Clears html for this day cell <div/> and clears the hash of agenda <div> elements.
		 */
		this.clearAgendaDivElements = function(){
			this.clearHtml();
			this.jqyMoreDiv = null;
			//this.agendaDivHash = new Hashtable();
			this.agendaDivArray = new Array();
		};

		/**
		 * Returns the next Y coordinate starting at start Y where a new <div> with the height 'agendaDivHeight' will fit, allowing for
		 * space for the more link <div> of height 'moreDivHeight' to fit at the end.
		 *
		 * @param starty - integer - The start Y coordinate. We start looking at this coordinate.
		 * @param agendaDivHeight - integer - Height for the new agenda <div> element.
		 * @param moreDivHeight - integer - Height for the "more" link <div> element. We always need to leave room for the "more" link.
		 * @return - integer - A Y coorindate where a next agenda <div> element could be rendered, or -1 if there is no space.
		 */
		this.getNextAgendaYstartY = function(startY,agendaDivHeight,moreDivHeight){
			var nextY = startY;
			//var divArray = this.agendaDivHash.values();
			var divArray = this.agendaDivArray;
			var max = (this.getY() + this.getHeight()) - (agendaDivHeight+1) - (moreDivHeight+1);
			if(divArray != null && divArray.length > 0){
				// sort agenda <div> elements by their Y coordinates
				divArray.sort(this.sortDivByY);
				var divTop = 0;
				var divBottom = 0; 
				for(var i = 0; i < divArray.length; i++){
					// using position.top seems to produce incorrect results in IE (at least IE 7). We get the top value using the css() call instead
					divTop = parseInt(divArray[i].css("top").replace("px",""));
					divBottom = divTop + parseInt(divArray[i].css("height").replace("px","")) + 1;					
					// is there enough space between top of agenda div and top of day cell?
					if((divTop+2-nextY) > (agendaDivHeight+1)){
					
					}else{
						if(!(divBottom < nextY)){
							nextY = divBottom;
						}
					}
				}
			}
			if( nextY > max ){
				// no room for another agenda <div> element of the height specified.
				return -1;
			}
			return parseInt(nextY);
		};
		// sort a jquery object by Y coordinate
		this.sortDivByY = function(a,b){
			// using position.top seems to produce incorrect results in IE (at least IE 7). We get the top value using the css() call instead
			var y1 = parseInt(a.css("top").replace("px",""));
			var y2 = parseInt(b.css("top").replace("px",""));
			//var y1 = parseInt(a.position().top);
			//var y2 = parseInt(b.position().top);
			if(y1 < y2){
				return -1;
			}else if(y1 > y2){
				return 1;
			}else{
				return 0;
			}
			//return ((y1 < y2) ? -1 : ((y1 > y2) ? 1 : 0));
		};
		
		/**
		 * Alerts the positions of all the agenda div elements.
		 */
		/*
		this.debugDivElements = function(){
			//var divArray = this.agendaDivHash.values();
			var divArray = this.agendaDivArray;
			if(divArray != null && divArray.length > 0){
				divArray.sort(this.sortDivByY);
				var s = divArray.length + " agenda div elements for " + this.date + ":\n\n";
				var divTop = 0;
				var divBottom = 0;			
				for(var i = 0; i < divArray.length; i++){
					divTop = parseInt(divArray[i].css("top").replace("px",""));
					divBottom = divTop + parseInt(divArray[i].css("height").replace("px","")) + 1;
					s += "Top: " + divTop + ", Bottom: " + divBottom + "\n";
				}
				alert(s);
			}else{
				alert("No agenda div elements for " + this.date);
			}
		}
		*/
		
		/**
		 * set the date for this day cell
		 *
		 * @param Date object with year, month, and day set.
		 */
		this.setDate = function(date){
			this.date = date;
		};		
		
		/**
		 * get the date for this day cell
		 *
		 * @return Date object.
		 */
		this.getDate = function(){
			return this.date;
		};
		
		/*
		get height of cell
		*/
		this.getHeight = function(){
			return this.jqyObj.height();
		};
		
		/*
		set height of cell
		*/
		this.setHeight = function(h){
			this.jqyObj.height(h);
		};		
		
		/*
		width, not inlcuding padding. @see jquery.width() method
		*/
		this.getWidth = function(){
			return this.jqyObj.width();
		};
		
		// set width
		this.setWidth = function(w){
			this.jqyObj.width(w);
		};	
		
		/*
		width, inlcuding paddings @see jquery.innerWidth() method
		*/
		this.getInnerWidth = function(){
			return this.jqyObj.innerWidth();
		};

		/*
		return inner width plus width of left & right border
		*/
		this.getInnerWidthPlusBorder = function(){
			return this.jqyObj.outerWidth();
		};

		/*
		get x coord of upper left corner
		*/
		this.getX = function(){
			return this.jqyObj.position().left;
		};
		
		/*
		get y coord of top left corner
		*/
		this.getY = function(){
			return this.jqyObj.position().top;
		};

		/*
		set html
		*/
		this.setHtml = function(htmlData){
			this.jqyObj.html(htmlData);
		};
		
		/*
		append html
		*/
		this.appendHtml = function(htmlData){
			this.jqyObj.append(htmlData);
		};

		/*
		clear html
		*/
		this.clearHtml = function(){
			this.setHtml("");
		};
		
		/*
		get html
		*/
		this.getHtml = function(){
			return this.jqyObj.html();
		};		
		
		/*
		set css value
		*/
		this.setCss = function(attr,value){
			this.jqyObj.css(attr,value);
		};
		
		/*
		get css value
		*/
		this.getCss = function(attr){
			return this.jqyObj.css(attr);
		};		
		
		/*
		set attribute value
		*/
		this.setAttr = function(id,value){
			this.jqyObj.attr(id,value);
		};
		
		/*
		get attribute value
		*/
		this.getAttr = function(id){
			return this.jqyObj.attr(id);
		};

		/*
		add a click event callback function to this day cell.
		the event object from the click will have the day object and date for the day
		e.g. var dayDate = eventObj.data.calDayDate;
		*/
		this.addClickHandler = function(handler){
			this.jqyObj.bind(
				"click",
				{
					//calDayObj:this,
					calDayDate:this.date
				},
				handler
			);
		};
	};

	/**
	 * One header cell in the calendar header.
	 *
	 * @param jqyObj - (JQuery object) - Reference to a header cell <div/> element.
	 */
	function CalendarHeaderCell(jqyObj) {
		
		// jquery object that reference one header cell <div/> in the header <div/> 
		this.jqyObj = jqyObj;
		
		this.addClass = function(c){
			this.jqyObj.addClass(c);
		};
		
		this.setHtml = function(htmlData){
			this.jqyObj.html(htmlData);
		};
		
		this.getHtml = function(){
			return this.jqyObj.html();
		};		
		
		this.setCss = function(attr,value){
			this.jqyObj.css(attr,value);
		};
		
		this.getCss = function(attr){
			return this.jqyObj.css(attr);
		};		
		
		this.setAttr = function(id,value){
			this.jqyObj.attr(id,value);
		};
		
		this.getAttr = function(id){
			return this.jqyObj.attr(id);
		};
		
		this.getX = function(){
			return this.jqyObj.position().left;
		};
		
		this.getY = function(){
			return this.jqyObj.position().top;
		};
		
		/*
		get height of cell
		*/
		this.getHeight = function(){
			return this.jqyObj.height();
		};
		
		/*
		set height of cell
		*/
		this.setHeight = function(h){
			this.jqyObj.height(h);
		};		
		
		/*
		width, not inlcuding padding. @see jquery.width() method
		*/
		this.getWidth = function(){
			return this.jqyObj.width();
		};
		
		// set width
		this.setWidth = function(w){
			this.jqyObj.width(w);
		};	
		
		// width, inlcuding padding
		this.getInnerWidth = function(){
			return this.jqyObj.innerWidth();
		};

		// return inner width plus width of left & right border
		this.getInnerWidthPlusBorder = function(){
			return this.jqyObj.outerWidth();
		};
		
	};
	
	/**
	 * One week header cell in the calendar week header.
	 *
	 * @param jqyObj - (JQuery object) - Reference to a week header cell <div/> element.
	 */
	function CalendarWeekHeaderCell(jqyObj) {
		
		// jquery object that reference one week header cell <div/> in the week header <div/> 
		this.jqyObj = jqyObj;
		
		// A Date object with the year, month, and day set for this day cell.
		this.date = null;

		this.addClass = function(c){
			this.jqyObj.addClass(c);
		};		
		
		/**
		 * set the date for this day cell
		 *
		 * @param Date object with year, month, and day set.
		 */
		this.setDate = function(date){
			this.date = date;
		};		
		
		/**
		 * get the date for this day cell
		 *
		 * @return Date object.
		 */
		this.getDate = function(){
			return this.date;
		};		
		
		this.setHtml = function(htmlData){
			this.jqyObj.html(htmlData);
		};
		
		this.getHtml = function(){
			return this.jqyObj.html();
		};		
		
		this.setCss = function(attr,value){
			this.jqyObj.css(attr,value);
		};
		
		this.getCss = function(attr){
			return this.jqyObj.css(attr);
		};		
		
		this.setAttr = function(id,value){
			this.jqyObj.attr(id,value);
		};
		
		this.getAttr = function(id){
			return this.jqyObj.attr(id);
		};
		
		this.getX = function(){
			return this.jqyObj.position().left;
		};
		
		this.getY = function(){
			return this.jqyObj.position().top;
		};
		
		/*
		get height of cell
		*/
		this.getHeight = function(){
			return this.jqyObj.height();
		};
		
		/*
		set height of cell
		*/
		this.setHeight = function(h){
			this.jqyObj.height(h);
		};		
		
		/*
		width, not inlcuding padding. @see jquery.width() method
		*/
		this.getWidth = function(){
			return this.jqyObj.width();
		};
		
		// set width
		this.setWidth = function(w){
			this.jqyObj.width(w);
		};	
		
		// width, inlcuding padding
		this.getInnerWidth = function(){
			return this.jqyObj.innerWidth();
		};

		// return inner width plus width of left & right border
		this.getInnerWidthPlusBorder = function(){
			return this.jqyObj.outerWidth();
		};
		
		// add a click event callback function to this day cell.
		// the event object from the click will have the day object and date for the day
		// e.g. var dayDate = eventObj.data.calDayDate;
		this.addClickHandler = function(handler){
			this.jqyObj.bind(
				"click",
				{
					calDayDate:this.date
				},
				handler
			);
		};		
		
	};	

	/**
	 * Calendar header object. Contains a collection of CalendarHeaderCell objects.
	 *
	 * @param jqyObj - (JQuery object) - Reference to the header <div/> element.
	 */
	function CalendarHeader(jqyObj) {
		
		// jquery object that reference the calendar header <div/>
		this.jqyObj = jqyObj;
		
		// all CalendarHeaderCell objects in the header
		this.headerCells = new Array();
		
		// append CalendarHeaderCell object to the header
		this.appendCalendarHeaderCell = function (calHeaderCell){
			// push is not supported by IE 5/Win with the JScript 5.0 engine
			this.headerCells.push(calHeaderCell);		
			this.jqyObj.append(calHeaderCell.jqyObj);
		};
		
		// returns an array of CalendarHeaderCell objects
		this.getHeaderCells = function(){
			return this.headerCells;
		}

		this.setHtml = function(htmlData){
			this.jqyObj.html(htmlData);
		};
		
		this.getHtml = function(){
			return this.jqyObj.html();
		};		
		
		this.setCss = function(attr,value){
			this.jqyObj.css(attr,value);
		};
		
		this.getCss = function(attr){
			return this.jqyObj.css(attr);
		};		
		
		this.setAttr = function(id,value){
			this.jqyObj.attr(id,value);
		};
		
		this.getAttr = function(id){
			return this.jqyObj.attr(id);
		};
		
		// set width of the calendar header <div/>
		this.setWidth = function(w){
			this.jqyObj.width(w);
		}
		
	};
	
	/**
	 * Calendar week header object. The row above each CalendarWeek object. Shows the day numbers.
	 * Contains a collection of CalendarWeekHeaderCell objects.
	 *
	 * @param jqyObj - (JQuery object) - Reference to the week header <div/> element.
	 */
	function CalendarWeekHeader(jqyObj) {
		
		// jquery object that reference the week header <div/>
		this.jqyObj = jqyObj;
		
		// all CalendarWeekHeaderCell objects in the week header
		this.weekHeaderCells = new Array();
		
		// append a CalendarWeekHeaderCell object
		this.appendCalendarWeekHeaderCell = function (weekHeaderCell){
			// push is not supported by IE 5/Win with the JScript 5.0 engine
			this.weekHeaderCells.push(weekHeaderCell);
			this.jqyObj.append(weekHeaderCell.jqyObj);
		};
		
		// returns an array of CalendarWeekHeaderCell objects
		this.getHeaderCells = function(){
			return this.weekHeaderCells;
		}
		
		this.setHtml = function(htmlData){
			this.jqyObj.html(htmlData);
		};
		
		this.getHtml = function(){
			return this.jqyObj.html();
		};		
		
		this.setCss = function(attr,value){
			this.jqyObj.css(attr,value);
		};
		
		this.getCss = function(attr){
			return this.jqyObj.css(attr);
		};		
		
		this.setAttr = function(id,value){
			this.jqyObj.attr(id,value);
		};
		
		this.getAttr = function(id){
			return this.jqyObj.attr(id);
		};

		// set width of the calendar week header <div/>
		this.setWidth = function(w){
			this.jqyObj.width(w);
		}		
	};	
	
	/**
	 * Calendar week object. One row in the calendar (7 days). Contains a collection of CalendarDayCell objects.
	 *
	 * @param jqyObj - (JQuery object) - Reference to the week <div/> element.
	 */
	function CalendarWeek(jqyObj) {
		
		// jquery object that reference the week <div/>
		this.jqyObj = jqyObj;
		
		// all CalendarDayCell objects in the week
		this.days = new Array();
		
		// append a CalendarDayCell object
		this.appendCalendarDayCell = function (calDayCell){
			// push is not supported by IE 5/Win with the JScript 5.0 engine
			this.days.push(calDayCell);
			this.jqyObj.append(calDayCell.jqyObj);
		};
		
		// returns an array of CalendarDayCell objects
		this.getDays = function(){
			return this.days;
		}
		
		this.setHtml = function(htmlData){
			this.jqyObj.html(htmlData);
		};
		
		this.getHtml = function(){
			return this.jqyObj.html();
		};		
		
		this.setCss = function(attr,value){
			this.jqyObj.css(attr,value);
		};
		
		this.getCss = function(attr){
			return this.jqyObj.css(attr);
		};		
		
		this.setAttr = function(id,value){
			this.jqyObj.attr(id,value);
		};
		
		this.getAttr = function(id){
			return this.jqyObj.attr(id);
		};

		// set width of the calendar header <div/>
		this.setWidth = function(w){
			this.jqyObj.width(w);
		}		
	};	

	/**
	 * Calendar object. Initializes to the current year & month.
	 *
	 * @param jqyObj - (JQuery object) - Reference to the calendar <div/> element.
	 */
	function Calendar() {
		
		// this value is set when Calendar.initialize(calElm,date) is called
		this.jqyObj = null;
		
		// reference to the CalendarHeader object
		this.calHeaderObj = null;
		
		// all CalendarWeek objects in the calendar
		this.weeks = new Array();
		
		// all buildCalendarWeekHeader objects in the calendar
		this.weekHeaders = new Array();	
		
		// by default the calendar will display the current month for the current year
		this.displayDate = new Date();
		
		// hash for storing agenda items. Uses jshashtable.js library. See notes at top of file.
		this.agendaItems = new Hashtable();
		
		// turn drag-and-drop on or off.
		this.dragAndDropEnabled = true;
		
		/*
		we already store all the CalendarDayCell objects inside the CalendarWeek objects
		but we use this hash because in many instances we want to be able to grab a
		particular day object as quickly as possible.
		keys = strings in the form of YYYYMMDD
		values = CalendarDayCell objects
		*/
		this.dayHash = new Hashtable();
		
		// the callback function that's triggered when users click a day cell div
		this.clickEvent_dayCell = null;
		// the callback function that's triggered when users click an agenda div item
		this.clickEvent_agendaCell = null;
		// the callback function that's triggered when users drop an agenda div element into a day cell (drag-and-drop)
		this.dropEvent_agendaCell = null;
		// the callback function that's triggered when users mouse oever an agenda div item
		this.mouseOverEvent_agendaCell = null;
		// optional callback where users can apply a tooltip to the agenda item div element
		this.callBack_agendaTooltip = null;
		// the callback function that's triggered when a drag event starts on an agenda div element.
		this.dragStart_agendaCell = null;
		// the callback function that's triggered when a drag event stops on an agenda div element.
		this.dragStop_agendaCell = null;		

		// each CalendarAgendaitem added to this calendar gets an ID. We'll increment this ID for each agendar item added.
		this.agendaId = 1;
		
		// default values...
		this.cellBorderWidth			= 1;	// border of all cells
		this.dayCellHeaderCellHeight	= 17;	// height of day cell header cell (in week header)
		this.agendaItemHeight 			= 15;	// height of agend item cell
		
		// by default we make the day cell heights the same as the day cell widths (minus the day cell week header height.)
		// we can change this behavior with this variable. This value can be anything between 0 and 1. A value of 0.5
		// would make the day cells half as tall as they are wide. 
		this.aspectRatio = 1;

		/**
		 * Builds the calendar data. This function must be called after new Calendar() in created
		 *
		 * @param calElm - A jquery object for the calendar <div/> element.
		 * @param date - A datejs Date object. The calendar will be set to the year and month of the date.
		 * @param dayCellClickHandler - A Function that's triggered when users click a day cell div element.
		 * @param agendaCellClickHandler - A Function that's triggered when users click an agenda div element
		 * @param agendaCellDropHandler - A Function that's triggered when users drop an agenda div element into a day cell div element.
		 * @param dragAndDrop - boolean - True to enable dra-and-drop, false to disable.
		 * @param agendaCellMouseoverHandler - A Function that's triggered when users mouse over an agenda div element.
		 * @param agendaTooltipHandler - A callabck function where users can apply their tooltip to the agenda div elements.
		 * @param agendaCellDragStartHandler - A Function that's triggered when a drag event starts on an agenda div element.
		 * @param agendaCellDragStopHandler - A Function that's triggered when a drag event stops on an agenda div element.
		 */
		this.initialize = function(
			calElm,
			date,
			dayCellClickHandler,
			agendaCellClickHandler,
			agendaCellDropHandler,
			dragAndDrop,
			agendaCellMouseoverHandler,
			agendaTooltipHandler,
			agendaCellDragStartHandler,
			agendaCellDragStopHandler){
			
			this.jqyObj = calElm;
			this.displayDate = date;
			this.clickEvent_dayCell = dayCellClickHandler;
			this.clickEvent_agendaCell = agendaCellClickHandler;
			this.dropEvent_agendaCell = agendaCellDropHandler;
			this.dragAndDropEnabled = dragAndDrop;
			this.mouseOverEvent_agendaCell = agendaCellMouseoverHandler;
			this.callBack_agendaTooltip = agendaTooltipHandler;
			this.dragStart_agendaCell = agendaCellDragStartHandler;
			this.dragStop_agendaCell = agendaCellDragStopHandler;			
			
			this.do_init();
			
		};
		
		/**
		 * Called by Calendar.initialize(). The real work happens here.
		 */
		this.do_init = function(){
		
			// clear header & weeks & week headers but don't clear agenda items.
			this.clear(false);
			
			// build header
			var calHeaderCell;
			var calHeader = this.buildCalendarHeader();
			for(var dayIndex = 0; dayIndex < Calendar.dayNames.length; dayIndex++){
				calHeaderCell = this.buildCalendarHeaderCell();
				calHeaderCell.setHtml("&nbsp;"+Calendar.dayNames[dayIndex]);
				calHeader.appendCalendarHeaderCell(calHeaderCell);
				if(dayIndex == 6){
					calHeaderCell.addClass("JFrontierCal-Header-Cell-Last");
				}
			}
			this.addHeader(calHeader); 			
			
			// initialize some variables we'll use for building the weeks and week headers
			
			// todays date
			var today = new Date();
			// year number for this date
			var currentYearNum = this.getCurrentYear();
			// month number for this date
			var currentMonthNum = this.getCurrentMonth();
			// day number for this date
			var currentDayNum = today.getDate();
			// number of days in this month
			var daysInCurrentMonth = this.getDaysCurrentMonth();
			// number of days in the previous month
			var daysInPreviousMonth = this.getDaysPreviousMonth();
			// number of days in the next month
			var daysInNextMonth = this.getDaysNextMonth();
			// Date object set to first day of the month
			var dtFirst = new Date(this.getCurrentYear(),this.getCurrentMonth(),1,0,0,0,0);
			// Date object set to last day of the month
			var dtLast = new Date(this.getCurrentYear(),this.getCurrentMonth(),daysInCurrentMonth,0,0,0,0);
			// index within the week of the first day of the month
			var firstDayWkIndex = dtFirst.getDay();
			// inidex within the week of the last day of the month
			var lastDayWkIndex = dtLast.getDay();
			
			var showTodayStyle = ((today.getFullYear() == currentYearNum && today.getMonth() == currentMonthNum) ? true : false);

			// number of day cells that appear on the calendar (days for current month + any days from 
			// previous month + any days from next month.) No more than 42 days, (7 days * 6 weeks.)
			var totalDayCells = daysInCurrentMonth + firstDayWkIndex;
			if(lastDayWkIndex > 0){
				totalDayCells += Calendar.dayNames.length - lastDayWkIndex - 1;
			}
			// number of week cells (rows) that appear on the calendar
			// this is also the number of week headers since each week has a header
			var numberWeekRows = Math.ceil(totalDayCells / Calendar.dayNames.length);

			// the day number that appears in each week header cell
			var dayNum = 1;
			// the Date object to be store in each CalendarDayCell object & CalendarWeekHeaderCell object
			// when users click a day cell or week header cell they can get access to this date cause we 
			// store it in the elements data (see jquery data() function)
			var dt = null;
			
			// when we display a month we can see a few days from the previous month on the calendar. This is the
			// day number of the earliest day we can see of the previous month.
			var firstDayPrevMonth = (daysInPreviousMonth - firstDayWkIndex) + 1;
			
			// build CalendarWeekHeader & CalendarWeek object for the first week row in the calenar
			var calDayCell;
			var calWeekObj;
			var calWeekHeaderCellObj;
			var calWeekHeaderObj;			
			calWeekObj = this.buildCalendarWeek(); // week <div/>
			calWeekHeaderObj = this.buildCalendarWeekHeader(); // week header <div/>
			for(var dayIndex = 0; dayIndex < Calendar.dayNames.length; dayIndex++){
				calDayCell = this.buildCalendarDayCell();
				calWeekHeaderCellObj = this.buildCalendarWeekHeaderCell();
				if(dayIndex < firstDayWkIndex){
					// previous month
					dt = new Date(currentYearNum,(currentMonthNum-1),firstDayPrevMonth,0,0,0,0);
					calDayCell.setDate(dt);
					calWeekHeaderCellObj.setDate(dt);
					calWeekHeaderCellObj.setHtml(firstDayPrevMonth+"&nbsp;");
					calDayCell.addClass("JFrontierCal-PrevMonth-Day-Cell");
					calWeekHeaderCellObj.addClass("JFrontierCal-PrevMonth-Week-Header-Cell");
					firstDayPrevMonth += 1;
				}else{
					// this month
					dt = new Date(currentYearNum,currentMonthNum,dayNum,0,0,0,0);
					calDayCell.setDate(dt);
					calWeekHeaderCellObj.setDate(dt);
					calWeekHeaderCellObj.setHtml(dayNum+"&nbsp;");
					if(showTodayStyle && dayNum == currentDayNum){
						//calDayCell.removeClass();
						calDayCell.addClass("JFrontierCal-Day-Cell-Today");
						calWeekHeaderCellObj.setHtml(/*"Today - "+*/dayNum+"&nbsp;");
					}					
					dayNum += 1;
				}
				if(dayIndex == 6){
					calDayCell.addClass("JFrontierCal-Day-Cell-Last");
					calWeekHeaderCellObj.addClass("JFrontierCal-Week-Header-Cell-Last");
				}
				// add click event handler if the user specified one
				if(this.clickEvent_dayCell != null){
					calDayCell.addClickHandler(this.clickEvent_dayCell);
					calWeekHeaderCellObj.addClickHandler(this.clickEvent_dayCell);
				}
				// add droppable event
				calDayCell.jqyObj.data("dayDate",dt);
				if(this.dragAndDropEnabled){
					calDayCell.jqyObj.droppable({
						hoverClass: "JFrontierCal-Day-Cell-Droppable",
						tolerance: "pointer",
						accept: ".JFrontierCal-Agenda-Item" /* only accept agenda items */
					});
					calDayCell.jqyObj.bind(
						"drop",
						{
							// pass calendar to the drop handler so we have access to it later.
							cal: this
						},					
						this.agendaDropHandler
					);
				}
				calWeekHeaderObj.appendCalendarWeekHeaderCell(calWeekHeaderCellObj);
				calWeekObj.appendCalendarDayCell(calDayCell);
				// add our day cell to our hash so we can look it up quickly when we need to later.
				this.dayHash.put(
					(calDayCell.getDate().getFullYear() + "") + 
					(calDayCell.getDate().getMonth() + "") + 
					(calDayCell.getDate().getDate() + ""),
					calDayCell);
			}
			this.addWeekHeader(calWeekHeaderObj);
			this.addWeek(calWeekObj);
			
			// add middle weeks & week headers
			for(var weekIndex = 2; weekIndex < numberWeekRows; weekIndex++){
				calWeekObj = this.buildCalendarWeek(); // week <div/>
				calWeekHeaderObj = this.buildCalendarWeekHeader(); // week header <div/>
				for(var dayIndex = 0; dayIndex < Calendar.dayNames.length; dayIndex++){
					calDayCell = this.buildCalendarDayCell();
					calWeekHeaderCellObj = this.buildCalendarWeekHeaderCell();
					dt = new Date(currentYearNum,currentMonthNum,dayNum,0,0,0,0);
					calDayCell.setDate(dt);						
					calWeekHeaderCellObj.setDate(dt);
					calWeekHeaderCellObj.setHtml(dayNum+"&nbsp;");
					// add click event handler if the user specified one
					if(this.clickEvent_dayCell != null){
						calDayCell.addClickHandler(this.clickEvent_dayCell);
						calWeekHeaderCellObj.addClickHandler(this.clickEvent_dayCell);
					}
					// add droppable event
					calDayCell.jqyObj.data("dayDate",dt);
					if(this.dragAndDropEnabled){
						calDayCell.jqyObj.droppable({
							hoverClass: "JFrontierCal-Day-Cell-Droppable",
							tolerance: "pointer",
							accept: ".JFrontierCal-Agenda-Item" /* only accept agenda items */
						});
						calDayCell.jqyObj.bind(
							"drop",
							{
								// pass calendar to the drop handler so we have access to it later.
								cal: this
							},					
							this.agendaDropHandler
						);
					}
					if(dayIndex == 6){
						calDayCell.addClass("JFrontierCal-Day-Cell-Last");
						calWeekHeaderCellObj.addClass("JFrontierCal-Week-Header-Cell-Last");
					}
					if(showTodayStyle && dayNum == currentDayNum){
						//calDayCell.removeClass();
						calDayCell.addClass("JFrontierCal-Day-Cell-Today");
						calWeekHeaderCellObj.setHtml(/*"Today - "+*/dayNum+"&nbsp;");
					}					
					calWeekHeaderObj.appendCalendarWeekHeaderCell(calWeekHeaderCellObj);
					calWeekObj.appendCalendarDayCell(calDayCell);					
					// add our day cell to our hash so we can look it up quickly when we need to later.
					this.dayHash.put(
						(calDayCell.getDate().getFullYear() + "") + 
						(calDayCell.getDate().getMonth() + "") + 
						(calDayCell.getDate().getDate() + ""),
						calDayCell);
					dayNum += 1;
				}
				this.addWeekHeader(calWeekHeaderObj);
				this.addWeek(calWeekObj);
			}
			
			// when we display a month we can see a few days from the next month on the calendar. this
			// is the day number of the first day on the next month. Will always be 1.
			var nextMonthDisplayDayNum = 1;
			
			//alert("Days in current month: " + daysInCurrentMonth);
			
			// add last week & last week header
			calWeekObj = this.buildCalendarWeek(); // week <div/>
			calWeekHeaderObj = this.buildCalendarWeekHeader(); // week header <div/>
			for(var dayIndex = 0; dayIndex < Calendar.dayNames.length; dayIndex++){
				calDayCell = this.buildCalendarDayCell();
				calWeekHeaderCellObj = this.buildCalendarWeekHeaderCell();
				if(dayNum <= daysInCurrentMonth){
					// this month
					dt = new Date(currentYearNum,currentMonthNum,dayNum,0,0,0,0);
					calDayCell.setDate(dt);						
					calWeekHeaderCellObj.setDate(dt);
					calWeekHeaderCellObj.setHtml(dayNum+"&nbsp;");				
				}else{
					// next month
					dt = new Date(currentYearNum,(currentMonthNum+1),nextMonthDisplayDayNum,0,0,0,0);
					calDayCell.setDate(dt);						
					calWeekHeaderCellObj.setDate(dt);
					calWeekHeaderCellObj.setHtml(nextMonthDisplayDayNum+"&nbsp;");
					calDayCell.addClass("JFrontierCal-NextMonth-Day-Cell");
					calWeekHeaderCellObj.addClass("JFrontierCal-NextMonth-Week-Header-Cell");
					nextMonthDisplayDayNum += 1;
				}
				if(dayIndex == 6 && dayNum <= daysInCurrentMonth){
					calDayCell.addClass("JFrontierCal-Day-Cell-Last");
					calWeekHeaderCellObj.addClass("JFrontierCal-Week-Header-Cell-Last");
				}else if(dayIndex == 6 && dayNum > daysInCurrentMonth){
					calDayCell.addClass("JFrontierCal-NextMonth-Day-Cell-Last");
					calWeekHeaderCellObj.addClass("JFrontierCal-NextMonth-Week-Header-Cell-Last");
				}
				if(showTodayStyle && dayNum == currentDayNum){
					//calDayCell.removeClass();
					calDayCell.addClass("JFrontierCal-Day-Cell-Today");
					calWeekHeaderCellObj.setHtml(/*"Today - "+*/dayNum+"&nbsp;");
				}					
				dayNum += 1;
				// add click event handler if the user specified one
				if(this.clickEvent_dayCell != null){
					calDayCell.addClickHandler(this.clickEvent_dayCell);
					calWeekHeaderCellObj.addClickHandler(this.clickEvent_dayCell);
				}
				// add droppable event
				calDayCell.jqyObj.data("dayDate",dt);
				if(this.dragAndDropEnabled){
					calDayCell.jqyObj.droppable({
						hoverClass: "JFrontierCal-Day-Cell-Droppable",
						tolerance: "pointer",
						accept: ".JFrontierCal-Agenda-Item" /* only accept agenda items */
					});
					calDayCell.jqyObj.bind(
						"drop",
						{
							// pass calendar to the drop handler so we have access to it later.
							cal: this
						},					
						this.agendaDropHandler
					);
				}
				calWeekHeaderObj.appendCalendarWeekHeaderCell(calWeekHeaderCellObj);
				calWeekObj.appendCalendarDayCell(calDayCell);
				// add our day cell to our hash so we can look it up quickly when we need to later.
				this.dayHash.put(
					(calDayCell.getDate().getFullYear() + "") + 
					(calDayCell.getDate().getMonth() + "") + 
					(calDayCell.getDate().getDate() + ""),
					calDayCell);				
			}
			this.addWeekHeader(calWeekHeaderObj);
			this.addWeek(calWeekObj);

			// get some user specified CSS values
			var headerCellTotalHeight = parseInt(calHeaderCell.jqyObj.outerHeight(true));
			var dayCellTotalHeight = parseInt(calDayCell.jqyObj.outerHeight(true) * numberWeekRows);
			var weekHeaderCellTotalHeight = parseInt(calWeekHeaderCellObj.jqyObj.outerHeight(true));
			
			// height of all calendar elements
			var totalCalendarHeight = dayCellTotalHeight + headerCellTotalHeight + weekHeaderCellTotalHeight;
			
			// if we don't set the height here than IE fails to render the agenda items correctly.... all other browsers are fine. weird....
			this.setCss("height",totalCalendarHeight+"px");
			
			this.jqyObj.addClass("JFrontierCal");

			// re-render all agenda items
			this.renderAgendaItems();
		
		};
		
		/**
		 * Retrieve all agenda items that appear on a particular day.
		 *
		 * @param date - Date - A date object with the year, month, and day set.
		 * @return An array of CalendarAgendaItem objects.
		 */
		this.getAgendaItemsForDay = function(date){
			// only render if we actually have some agenda items.
			if(this.agendaItems == null || this.agendaItems.size() == 0){
				return;
			}
			var itemArray = this.agendaItems.values();
			// loop through each CalendarAgendaItem
			var startDt = null;
			var endDt = null;
			var itemsForDay = new Array();
			for(var itemIndex = 0; itemIndex < this.agendaItems.size(); itemIndex++){
				// CalendarAgendaItem object
				var agi = itemArray[itemIndex];
				startDt = agi.getStartDate();
				endDt = agi.getEndDate();				
				if(DateUtil.isDateBetween(date,startDt,endDt)){
					// push is not supported by IE 5/Win with the JScript 5.0 engine
					itemsForDay.push(agi);
				}
			}
			return itemsForDay;
		};	

		/**
		 * Re-renders all the agenda items stored in the calendar.
		 *
		 */
		this.renderAgendaItems = function(){
			
			// only render if we actually have some agenda items.
			if(this.agendaItems == null || this.agendaItems.size() == 0){
				return;
			}
			
			// get all CalendarAgendaItem objects from our Hashtable
			var itemArray = this.agendaItems.values();
			
			// sort agenda items by start date
			itemArray.sort(Calendar.sortAgendaItemsByStartDate);
			
			// loop through each CalendarAgendaItem and render it
			for(var itemIndex = 0; itemIndex < this.agendaItems.size(); itemIndex++){
				// CalendarAgendaItem object
				var agi = itemArray[itemIndex];
				this.renderSingleAgendaItem(agi);
			}
		
		};

		/**
		 * Renders the agenda item
		 *
		 * @param agi - CalendarAgendaItem - The agenda item to render.
		 */
		this.renderSingleAgendaItem = function(agi){
			
			var now = new Date();
			LogUtil.log("Calendar.renderSingleAgendaItem() called.");
		
			if(agi == null){
				return;
			}
			if(this.weeks == null || this.weeks.length == 0){
				// no need to render if we don't have any week data. should never get here really...
				return;
			}
			var isEnd; // use for drawing triangle on agenda div
			var isBegining; // use for drawing triangle on agenda div
			var displayMessage = null;
			var agendaId = agi.getAgendaId();
			var agendaStartDate = agi.getStartDate();
			var agendaEndDate = agi.getEndDate();
			if(agendaStartDate == null || agendaEndDate == null){
				// no agenda dates, can't render
				return;
			}
			// get the first visible day on the calendar (this might be from the previous month, or year!)
			var firstVisibleDay = null;
			var firstWeekDayArray = this.weeks[0].getDays();
			if(firstWeekDayArray != null && firstWeekDayArray.length > 0){
				firstVisibleDay = firstWeekDayArray[0];
			}
			// get the last day visible on the calendar (this might be from the next month, or year!)
			var lastVisibleDay = null;
			var lastWeekDayArray = this.weeks[this.weeks.length-1].getDays();
			if(lastWeekDayArray != null && lastWeekDayArray.length > 0){
				lastVisibleDay = lastWeekDayArray[lastWeekDayArray.length-1];
			}
			if(firstVisibleDay == null || lastVisibleDay == null){
				// calendar has no weeks or days... failed initialization? can't render anything!
				return;
			}
			var firstVisDt = firstVisibleDay.getDate();
			var lastVisDt = lastVisibleDay.getDate();
			if(DateUtil.daysDifferenceDirection(firstVisDt,agendaEndDate) < 0){
				// the agenda item is out of view. it's earlier on the calendar. no need to render.
				return;
			}
			if(DateUtil.daysDifferenceDirection(lastVisDt,agendaStartDate) > 0){
				// the agenda item is out of view. it's later on the calendar. no need to render.
				return;
			}				
			// looks like we need to render this agenda item!
			var firstRenderDate = null;
			var lastRenderDate = null;
			if(DateUtil.daysDifferenceDirection(firstVisDt,agendaStartDate) < 0){
				// the agenda start date is out of view (earlier on the calendar). The first day
				// we'll render for agenda item is the first visible day on the calendar.
				firstRenderDate = firstVisDt;
			}else if(DateUtil.daysDifferenceDirection(firstVisDt,agendaStartDate) == 0){
				// the agenda start date is the first visible date on the calendar.
				firstRenderDate = firstVisDt;
			}else{
				// the agenda start date is the first render date.
				firstRenderDate = agendaStartDate;
			}
			
			if(DateUtil.daysDifferenceDirection(lastVisDt,agendaEndDate) > 0){
				// the agenda end date is out of view (later on the calendar). The last
				// day we'll render for the agenda item is the last visible day on the calendar.
				lastRenderDate = lastVisDt;
			}else if(DateUtil.daysDifferenceDirection(lastVisDt,agendaEndDate) == 0){
				// the agenda end date is the last visible date on the calendar.
				lastRenderDate = lastVisDt;
			}else{
				// the agenda end date is the last render date.
				lastRenderDate = agendaEndDate;
			}			
		
			// if firstRenderDate & lastRenderDate are not in the same week than we'll have
			// to render multiple <div/>'s for this agenda item (one div for each week.)
			
			var firstDtIndex = firstRenderDate.getDay();
			var lastDtIndex = lastRenderDate.getDay();
			
			if((DateUtil.daysDifference(firstRenderDate,lastRenderDate) + firstDtIndex) > 6){
			
				// we need to create multiple <div> elements because the agenda item spans more than one week
			
				// create first <div> from firstRenderDate to the last day in the same week
				if(agi.isAllDay()){
					// don't show start time
					displayMessage = agi.getTitle();
				}else{
					displayMessage = DateUtil.getAgendaDisplayTime(agi.getStartDate())+" " + agi.getTitle();
				}
				var lastDaySameWeekDate = DateUtil.getLastDayInSameWeek(firstRenderDate);
				isBegining = ((DateUtil.daysDifferenceDirection(agendaStartDate,firstRenderDate) == 0) ? true : false);
				isEnd = ((DateUtil.daysDifferenceDirection(agendaEndDate,lastDaySameWeekDate) == 0) ? true : false);
				this.renderAgendaDivElement(
					agi,
					displayMessage,
					this.getCalendarDayObjByDate(firstRenderDate),
					this.getCalendarDayObjByDate(lastDaySameWeekDate),
					isBegining,
					isEnd
				);
				// render the rest of the div elements till we get to the end
				displayMessage = agi.getTitle();
				while(DateUtil.daysDifferenceDirection(lastRenderDate,lastDaySameWeekDate) < 0){
					var firstDayNextWeekDate = DateUtil.getFirstDayNextWeek(lastDaySameWeekDate);	
					lastDaySameWeekDate = DateUtil.getLastDayInSameWeek(firstDayNextWeekDate);
					if(DateUtil.daysDifferenceDirection(lastRenderDate,lastDaySameWeekDate) < 0){
						// render div from firstDayNextWeekDate to lastDaySameWeekDate
						this.renderAgendaDivElement(
							agi,
							displayMessage,
							this.getCalendarDayObjByDate(firstDayNextWeekDate),
							this.getCalendarDayObjByDate(lastDaySameWeekDate),
							false,
							false
						);						
					}else{
						// render div from firstDayNextWeekDate to lastRenderDate
						isBegining = ((DateUtil.daysDifferenceDirection(agendaStartDate,firstDayNextWeekDate) == 0) ? true : false);
						isEnd = ((DateUtil.daysDifferenceDirection(agendaEndDate,lastRenderDate) == 0) ? true : false);
						this.renderAgendaDivElement(
							agi,
							displayMessage,
							this.getCalendarDayObjByDate(firstDayNextWeekDate),
							this.getCalendarDayObjByDate(lastRenderDate),
							isBegining,
							isEnd
						);						
					}
				}
			}else{
			
				// the <div/> to render for the agend item is all in the same week.
				var startDayObj = this.getCalendarDayObjByDate(firstRenderDate);
				var endDayObj   = this.getCalendarDayObjByDate(lastRenderDate);
				if(agi.isAllDay()){
					// don't show start time
					displayMessage = agi.getTitle();
				}else{
					displayMessage = DateUtil.getAgendaDisplayTime(agi.getStartDate())+" " + agi.getTitle();
				}
				isBegining = ((DateUtil.daysDifferenceDirection(agendaStartDate,firstRenderDate) == 0) ? true : false);
				isEnd = ((DateUtil.daysDifferenceDirection(agendaEndDate,lastRenderDate) == 0) ? true : false);
				this.renderAgendaDivElement(
					agi,
					displayMessage,
					startDayObj,
					endDayObj,
					isBegining,
					isEnd
				);
				
			}
			
			var then = new Date();
			LogUtil.log("Calendar.renderSingleAgendaItem() end. Elapsed time in ms = " + Math.abs(then - now));
			
		};
		
		/**
		 * Renders a absolute positioned <div/> element from the start day to the end day
		 * for the agenda item.
		 *
		 * @param agi - integer - The agenda item object.
		 *
		 * @param displayMessage - (String) - Text to show in the agenda div.
		 *
		 * @param startDayObject - (CalendarDayCell) - The start day. This is where the <div/> will start.
		 *
		 * @param endDayObject - (CalendarDayCell) - The end day. This is where the <div/> will end.
		 *
		 * @param leftEnd - (true/false) -
		 * True - If the startDayObject is the actual start day of the agenda item. Round the corners of the left end of the div element.
		 * False - If the startDayObject is not the actual start day of the agenda item. Do not round the left and of the div. Draw our jquery triangle icon.
		 *
		 * @param rightEnd - (true/false) -
		 * True - If the endDayObject is the actual end day of the agenda item. Round the corners of the right end of the div element.
		 * False - If the endDayObject is not the actual end day of the agenda item. Do not round the right and of the div. Draw our jquery triangle icon.		 
		 */
		this.renderAgendaDivElement = function(agi,displayMessage,startDayObject,endDayObject,leftEnd,rightEnd){
			
			//alert("Calendar.renderAgendaDivElement() called.");
			
			if(displayMessage == null || startDayObject == null || endDayObject == null){
				return;
			}
		
			var startX = startDayObject.getX() /*+ this.cellBorderWidth*/ + 1;
			var endX = endDayObject.getX() /*+ this.cellBorderWidth*/ + endDayObject.getWidth() - 1;
			var width = endX - startX;
			
			var spacerBetweenAgendaDivs = 1;
			
			var agendaDivHeight = this.agendaItemHeight;
			var moreDivHeight = agendaDivHeight;
			
			var nextY = this.getNextAgendaYPosition(startDayObject,endDayObject,agendaDivHeight,moreDivHeight);
			//alert("Next Y for item " + displayMessage + ": " + nextY);
			
			if(nextY > 0){
			
				var d = $("<div/>");
				// store agenda ID in agenda div so we can get it later in the drag-drop event
				d.data("agendaId",agi.getAgendaId());
				// item is draggble and will revert to it's original position if not dropped into a valid droppable (another day cell)
				if(this.dragAndDropEnabled){
					d.bind(
						"drag",
						function(event, ui) {
							// do something when dragging
						}
					);
					d.bind(
						"dragstart",
						{
							agendaDivElement: d,
							agendaId: agi.getAgendaId(),
							agendaItem: Calendar.buildUserAgendaObject(agi),
							callBack: this.dragStart_agendaCell
						},						
						function(event, ui) {
							var callBack = event.data.callBack;
							if(callBack != null){
								callBack(
									event,
									event.data.agendaDivElement,
									event.data.agendaItem
								);
							}
						}
					);
					d.bind(
						"dragstop",
						{
							agendaDivElement: d,
							agendaId: agi.getAgendaId(),
							agendaItem: Calendar.buildUserAgendaObject(agi),
							callBack: this.dragStop_agendaCell
						},						
						function(event, ui) {
							var callBack = event.data.callBack;
							if(callBack != null){
								callBack(
									event,
									event.data.agendaDivElement,
									event.data.agendaItem
								);
							}
						}
					);						
					d.draggable("enable");
					d.data("agendaDivElement",d);
					d.data("agendaId",agi.getAgendaId());
					d.data("agendaItem", Calendar.buildUserAgendaObject(agi));
					d.data("revertCallBack",this.callBack_agendaTooltip);
					d.draggable({ 
						revert: function(event,ui){
							var callBack = $(this).data("revertCallBack");
							var agendaDiv = $(this).data("agendaDivElement");
							var agendaItem = $(this).data("agendaItem");
							if(callBack != null){
								callBack(
									agendaDiv,
									agendaItem
								);
							}
							return true;
						},						
						scroll: true
					});
				}
				d.addClass("JFrontierCal-Agenda-Item");
				if(agi.getBackgroundColor() != null){
					d.css("background-color",agi.getBackgroundColor());
				}
				if(agi.getForegroundColor() != null){
					d.css("color",agi.getForegroundColor());
				}
				d.css("position","absolute");
				d.css("left",startX+"px");
				d.css("top",nextY+"px");					
				d.css("width",width+"px");
				d.css("white-space","nowrap");
				// round corners for webkit & safari (poor IE :( )
				if(leftEnd){
					d.css("-moz-border-radius-bottomleft","3px");
					d.css("-moz-border-radius-topleft","3px");
					d.css("-webkit-border-bottom-left-radius","3px");
					d.css("-webkit-border-top-left-radius","3px");
				}else{
					// left end is not the start day of the agenda item. Show our jquery trianle icon
					var triangle = $("<span/>");
					triangle.css("float","left");
					triangle.addClass("ui-icon ui-icon-circle-triangle-w");
					d.append(triangle);					
				}
				var mesg = $("<span/>");
				mesg.css("float","left");
				mesg.html(displayMessage);
				d.append(mesg);				
				if(rightEnd){
					d.css("-moz-border-radius-topright","3px");
					d.css("-moz-border-radius-bottomright","3px");
					d.css("-webkit-border-top-right-radius","3px");
					d.css("-webkit-border-bottom-right-radius","3px");
				}else{
					// right end is not the end day of the agenda item. Show our jquery trianle icon
					var triangle = $("<span/>");
					triangle.css("float","right");
					triangle.addClass("ui-icon ui-icon-circle-triangle-e");
					d.append(triangle);
				}
				// add click even lister for agenda item
				if(this.clickEvent_agendaCell != null){
					d.bind(
						'click',
						{
							// pass agenda ID so user will have access to it in their custom click callback function
							agendaId: agi.getAgendaId(),
							// pass click event callback function so we can call it in clickAgendaFromCalendarHandler() function
							callBack: this.clickEvent_agendaCell
						},						
						this.clickAgendaFromCalendarHandler
					);
				}
				// add mouse over event listener
				if(this.mouseOverEvent_agendaCell != null){
					d.bind(
						'mouseover',
						{
							// pass agenda ID so user will have access to it in their custom click callback function
							agendaId: agi.getAgendaId(),
							// pass mouseover event callback function so we can call it in mouseOverAgendaFromCalendarHandler() function
							callBack: this.mouseOverEvent_agendaCell
						},						
						this.clickAgendaFromCalendarHandler
					);				
				}
				// change mouse cusor to pointer when hovering over agenda divs.
				d.hover(
					function() {
						$(this).css('cursor','pointer');
					},
					function() {
						$(this).css('cursor','auto');
					}
				);
				// call the users custom tooltip function if they provided one. pass the agenda item div element so they have access to it,
				// add pass the user a agenda item object so they have access to the data.
				if(this.callBack_agendaTooltip){
					this.callBack_agendaTooltip(d,Calendar.buildUserAgendaObject(agi));
				}
	
				// add agenda <div> to all day cells.
				this.addAgendaDivToDays(startDayObject,endDayObject,d,agi.getAgendaId());

				// add agenda <div> to DOM.
				startDayObject.appendHtml(d);
			
			}else{

				this.addMoreDivToDays(startDayObject,endDayObject,moreDivHeight);
			
			}
			
		};

		/**
		 * Fired when users click an agenda item on the calendar. It simply calls the user specified
		 * agenda event click handler and than stops the click event from propogating to other
		 * elements below (triggering the day cell click event)
		 *
		 * @param eventObj - The event object from the click event. Should have the following values in its data.
		 *					 eventObj.data.callBack - The users custom click event callback function.
		 *					 eventObj.data.agendaId - The ID of the agenda item that was clicked.
		 *
		 */
		this.clickAgendaFromCalendarHandler = function(eventObj){
			eventObj.stopPropagation();
			var callBack = eventObj.data.callBack;
			if(callBack != null){
				// pass eventObj to the users click handler. they will have access to the agenda ID (eventObj.data.agendaId)
				callBack(eventObj);
			}
		};
		
		/**
		 * Fired when users mouse over and agenda item on the calendar.
		 */
		this.mouseOverAgendaFromCalendarHandler = function(eventObj){
			//eventObj.stopPropagation();
			var callBack = eventObj.data.callBack;
			if(callBack != null){
				// pass eventObj to the users click handler. they will have access to the agenda ID (eventObj.data.agendaId)
				callBack(eventObj);
			}		
		};
		
		/**
		 * Fired when users click and agenda item from the "more agenda items" modal dialog.
		 * This function closes the dialog, then it calls this.clickAgendaFromCalendarHandler()
		 *
		 * @param eventObj - The event object from the click event. Should have the following values in its data.
		 *					 eventObj.data.callBack - The users custom click event callback function.
		 *					 eventObj.data.agendaId - The ID of the agenda item that was clicked.
		 *					 eventObj.data.dialog - Reference to the "more agenda items" modal dialog.		 
		 */
		this.clickAgendaFromCalendarMoreModalDialogHandler = function(eventObj){
			// close the "more" dialog
			var modalDialog = eventObj.data.dialog;
			if(modalDialog != null){
				modalDialog.dialog("close");
			}
			// get the users callback and call it!
			var callBack = eventObj.data.callBack;
			if(callBack != null){
				// pass eventObj to the users click handler. they will have access to the agenda ID (eventObj.data.agendaId)
				callBack(eventObj);
			}
			eventObj.stopPropagation();
		};

		/**
		 * Fired when an agenda item is dropped into a day cell (drag-and-drop)
		 *
		 * ui.draggable = reference to the draggable (the agenda div)
		 * event.data.cal = refernce to the Calendar object that contains the agenda item.
		 * $(this) = reference to the droppable (the day cell the agenda div was dropped into)
		 *
		 */
		this.agendaDropHandler = function(event, ui){
		
			var calObj = event.data.cal;
			
			if(calObj == null){
				alert("Drop Error: Calendar object is null.");
			}			
		
			var agendaDiv = ui.draggable;
			
			var agendaId = parseInt(agendaDiv.data("agendaId"));
			if(agendaId == null){
				alert("Drop Error: Agenda id is null.");
			}
			var agendaItemObj = calObj.getAgendaItemById(agendaId);
			if(agendaItemObj == null){
				alert("Drop Error: Agenda item object is null.");
			}
			
			// The date on the calendar that agenda div was dropped to
			var toStartDate = $(this).data("dayDate");

			
			// fade out div that was dragged and dropped, remove agenda item from calendar, update dates, then re-add it.
			agendaDiv.fadeOut(function() {
			
				agendaDiv.draggable("destroy");
				agendaDiv == null;
				
				calObj.deleteAgendaItemById(agendaId);
				
				var fromStartDate = agendaItemObj.getStartDate();
				var fromEndDate = agendaItemObj.getEndDate();
				
				var daysDiffDirection = DateUtil.daysDifferenceDirection(fromStartDate,toStartDate);
				
				var newStartDt = DateUtil.addDays(fromStartDate,daysDiffDirection);
				var newEndDt = DateUtil.addDays(fromEndDate,daysDiffDirection);
				
				agendaItemObj.setStartDate(newStartDt);
				agendaItemObj.setEndDate(newEndDt);
				
				calObj.addAgendaItem(agendaItemObj);
				
				//event.data.cal = null; // remove calendar object from event.
				event.data.agendaId = agendaId; // add agenda ID to event so user has access to it.
				event.data.calDayDate = toStartDate; // add date that the agenda item was dropped to.
				
				// call users drop handler
				if(calObj.dropEvent_agendaCell != null){
					calObj.dropEvent_agendaCell(event);
				}
				
			});
			
			event.stopPropagation();
		
		};		

		/**
		 * Adds the more link <div> element to all the days, from start day to end day.
		 *
		 * @param startDayObj - CalendarDayCell - The start day.
		 * @param endDayObj - CalendarDayCell - The end day.
		 * @param moreDivHeight - jquery object - Height of the more link <div> element.
		 */
		this.addMoreDivToDays = function(startDayObj,endDayObj,moreDivHeight){
			if(startDayObj == null || endDayObj == null || moreDivHeight == null){
				return;
			}

			var startDt = startDayObj.getDate();
			var endDt = endDayObj.getDate();
			var nextDt = DateUtil.getNextDay(startDt);	

			// create div right at end of day cell
			var d = $("<div/>");
			d.addClass("JFrontierCal-Agenda-More-Link");
			d.css("position","absolute");
			// add click event
			var items = this.getAgendaItemsForDay(startDt);
			d.html("+ more (" + items.length + ")");
			d.bind(
				'click',
				{
					// pass calendar, start date, and agenda items so we have access to them in the click handler function.
					cal: this,
					calDayDate: startDt,
					agendaItems: items  
				},
				function(eventObj){
					eventObj.stopPropagation();
					// open our "more" modal dialog
					Calendar.showMoreAgendaModal(
						eventObj.data.cal,
						eventObj.data.calDayDate,
						eventObj.data.agendaItems
					);
				}
			);
			// change mouse cusor to pointer when hovering over agenda divs.
			d.hover(
				function() {
					$(this).css('cursor','pointer');
				},
				function() {
					$(this).css('cursor','auto');
				}
			);
			var startY = (startDayObj.getY() + startDayObj.getHeight()) - moreDivHeight - 1;
			var startX = startDayObj.getX();
			var width = startDayObj.getWidth();			
			d.css("top",startY+"px");
			d.css("left",startX+"px");
			d.css("width",width+"px");
			d.css("height",moreDivHeight+"px");
			startDayObj.addMoreDiv(d);

			while(DateUtil.daysDifferenceDirection(nextDt,endDt) >= 0){
				var nextDatObj = this.getCalendarDayObjByDate(nextDt);
				startDt = nextDatObj.getDate();
				d = $("<div/>");
				d.addClass("JFrontierCal-Agenda-More-Link");
				d.css("position","absolute");
				items = this.getAgendaItemsForDay(startDt);
				d.html("+ more (" + items.length + ")");
				// add click event to "more" link
				d.bind(
					'click',
					{
						// pass calendar, start date, and agenda items so we have access to them in the click handler function.
						cal: this,
						calDayDate:startDt,
						agendaItems: items
					},
					function(eventObj){
						eventObj.stopPropagation();
						// open our "more" modal dialog
						Calendar.showMoreAgendaModal(
							eventObj.data.cal,
							eventObj.data.calDayDate,
							eventObj.data.agendaItems
						);
					}
				);
				// change mouse cusor to pointer when hovering over agenda divs.
				d.hover(
					function() {
						$(this).css('cursor','pointer');
					},
					function() {
						$(this).css('cursor','auto');
					}
				);
				startY = (nextDatObj.getY() + nextDatObj.getHeight()) - moreDivHeight - 1;
				startX = nextDatObj.getX();
				width = nextDatObj.getWidth();			
				d.css("top",startY+"px");
				d.css("left",startX+"px");
				d.css("width",width+"px");
				d.css("height",moreDivHeight+"px");
				nextDatObj.addMoreDiv(d);
				nextDt = DateUtil.getNextDay(nextDt);
			}			
		};		
		
		/**
		 * Adds the agenda <div> elements to all the days, from start day to end day.
		 * You can pass null to remove it.
		 *
		 * @param startDayObj - CalendarDayCell - The start day.
		 * @param endDayObj - CalendarDayCell - The end day.
		 * @param agendaDiv - jquery object - the agenda <div> element, or null.
		 * @param agendaId - integer - The ID of the agenda item.
		 */
		this.addAgendaDivToDays = function(startDayObj,endDayObj,agendaDiv,agendaId){
			
			//alert("Calendar.addAgendaDivToDays() called.");
			
			if(startDayObj == null || endDayObj == null || agendaDiv == null || agendaId == null){
				return;
			}
			startDayObj.addAgendaDivElement(agendaId,agendaDiv);
			var startDt = startDayObj.getDate();
			var endDt = endDayObj.getDate();
			var nextDt = DateUtil.getNextDay(startDt);
			while(DateUtil.daysDifferenceDirection(nextDt,endDt) >= 0){
				var nextDatObj = this.getCalendarDayObjByDate(nextDt);
				nextDatObj.addAgendaDivElement(agendaId,agendaDiv);
				nextDt = DateUtil.getNextDay(nextDt);
			}			
		};		
		
		/**
		 * Examins all the agenda <div> elements currently rendered from start day to end day
		 * and finds the next Y coordinate where we can render another agenda <div> element.
		 *
		 * @param startDayObj - CalendarDayCell - The start day.
		 * @param endDayObj - CalendarDayCell - The end day.		 
		 * @param agendaDivHeight - integer - The height of the new agenda <div> element
		 * @param moreDivHeight - integer - The height of the "more" link <div> element
		 * @return integer - the next y coordinate, or -1 if no more space.
		 */
		this.getNextAgendaYPosition = function(startDayObj,endDayObj,agendaDivHeight,moreDivHeight){
			
			//alert("Calendar.getNextAgendaYPosition() called.");
			
			if(startDayObj == null || endDayObj == null || agendaDivHeight == null || moreDivHeight == null){
				// -1 means no more space
				return -1;
			}
			
			var maxY = 0;
			var nextY = startDayObj.getY();
			maxY = nextY;
			var nextDatObj = null;
			var found = false;
			var nextYArray = null;
			var startDt;
			var endDt;
			var nextDt;
			
			//startDayObj.debugDivElements();

			var itrIndex = 1;
			// if we get into a nasty loop this upper maximum will eventually end it.
			var maxIterations = 100;
			
			while(!found /*|| itrIndex <= maxIterations*/){
				nextYArray = new Array();
				nextY = startDayObj.getNextAgendaYstartY(nextY,agendaDivHeight,moreDivHeight);
				if(nextY > maxY){
					maxY = nextY;
				}
				nextYArray.push(nextY);
				startDt = startDayObj.getDate();
				endDt = endDayObj.getDate();
				nextDt = DateUtil.getNextDay(startDt);
				while(DateUtil.daysDifferenceDirection(nextDt,endDt) >= 0){
					nextDatObj = this.getCalendarDayObjByDate(nextDt);
					//nextDatObj.debugDivElements();
					nextY = nextDatObj.getNextAgendaYstartY(nextY,agendaDivHeight,moreDivHeight);
					if(nextY > maxY){
						maxY = nextY;
					}
					nextYArray.push(nextY);
					nextDt = DateUtil.getNextDay(nextDt);
				}
				nextY = nextYArray[0];
				if(nextY < 0){
					return -1;
				}else if(nextYArray.length == 1){
					return nextY;
				}
				var allEqual = true;
				for(var i=1; i<nextYArray.length; i++){
					if(nextYArray[i] < 0){
						return -1;
					//}else if(nextY != nextYArray[i]){
					}else if(Math.abs(nextY - nextYArray[i]) > 1){ // allow a difference of 1 for a little wiggle room
						allEqual = false;
					}
				}
				if(allEqual){
					return nextY;
				}
				nextY = maxY;
				itrIndex += 1;
			}
			return nextY;			
		};	
		
		/**
		 * Returns the CalendarDayCell object with the matching date: matching on year, month, and day.
		 *
		 * @param date - (Date) - A Date object with the year, month, and day set.
		 * @return A CalendarDayCell object with the matching date, or null.
		 */
		this.getCalendarDayObjByDate = function(date){
			if(date == null || this.dayHash == null){
				return null;
			}
			var key = (date.getFullYear()+"") + (date.getMonth()+"") + (date.getDate()+"");
			return this.dayHash.get(key);
			/*
			if(date == null){
				return null;
			}
			if(this.weeks == null || this.getNumberWeeks() == 0){
				return null;
			}
			for(var weekIndex = 0; weekIndex < this.getNumberWeeks(); weekIndex++){
				var dayCellsArray = this.weeks[weekIndex].getDays();
				if(dayCellsArray != null && dayCellsArray.length > 0){
					for(var dayIndex = 0; dayIndex < dayCellsArray.length; dayIndex++){
						var dayCell = dayCellsArray[dayIndex];
						var dayDate = dayCell.getDate();
						if(dayDate != null){
							if(dayDate.getFullYear() == date.getFullYear() && 
							   dayDate.getMonth() == date.getMonth() && dayDate.getDate() == date.getDate()){
								
								return dayCell;
							}
						}
					}
				}
			}
			*/
		};
		
		/**
		 * Set the calendar to the specified year & month.
		 * 
		 * @param date - A date object from the datejs library.
		 */
		this.setDisplayDate = function(date){

			// set the date
			this.displayDate = date;
			
			// re-initialize the calendar
			this.do_init();
			
			// resize
			this.resize();
		
		};
		
		/**
		 * Returns the calendars current date.
		 *
		 * @return A datejs Date object.
		 */
		this.getDisplayDate = function(){
			var dt = new Date(this.getCurrentYear(),this.getCurrentMonth(),this.getCurrentDay(),0,0,0,0);
			return dt;
		};

		/**
		 * Sets the calendar to the next month
		 */
		this.nextMonth = function(){
			var dt = new Date(0,0,1,0,0,0,0);
			if(this.displayDate.getMonth() == 11){
				dt.setFullYear(this.displayDate.getFullYear()+1);
				dt.setMonth(0);
			}else{
				dt.setFullYear(this.displayDate.getFullYear());
				dt.setMonth(this.displayDate.getMonth()+1);
			}
			this.setDisplayDate(dt);
		};
		
		/**
		 * Sets the calendar to the previous month
		 */
		this.previousMonth = function(){
			var dt = new Date(0,0,1,0,0,0,0);
			if(this.displayDate.getMonth() == 0){
				dt.setFullYear(this.displayDate.getFullYear()-1);
				dt.setMonth(11);
			}else{
				dt.setFullYear(this.displayDate.getFullYear());
				dt.setMonth(this.displayDate.getMonth()-1);
			}		
			this.setDisplayDate(dt);	
		};
		
		/**
		 * Builds a CalendarHeader object. This goes at the very top of the calendar and displays the day names.
		 * This object stores all the CalendarHeaderCell objects for the calendar header.
		 *
		 * @return a CalendarHeader object.
		 */
		this.buildCalendarHeader = function(){
			var jqyHeaderObj = $("<div/>");
			jqyHeaderObj.css("width",this.getWidth()+"px");
			var calHeaderObj = new CalendarHeader(jqyHeaderObj);			
			return calHeaderObj;
		};
		
		/**
		 * Builds a CalendarWeek object. This object stores all the CalendarDayCell objects for the week.
		 *
		 * @return a CalendarWeek object.
		 */
		this.buildCalendarWeek = function(){
			var weekCell = $("<div/>");
			weekCell.css("width",this.getWidth()+"px");
			var calWeek = new CalendarWeek(weekCell);			
			return calWeek;
		};

		/**
		 * Builds a CalendarWeekHeader object. This object stores all the CalendarWeekHeaderCell objects for the week.
		 *
		 * @return a CalendarWeekHeader object.
		 */
		this.buildCalendarWeekHeader = function(){
			var weekHeaderCell = $("<div/>");
			weekHeaderCell.css("width",this.getWidth()+"px");
			var calWeekHeader = new CalendarWeekHeader(weekHeaderCell);			
			return calWeekHeader;
		};			

		/**
		 * Builds a CalendarHeaderCell object. One cell in the CalendarHeader.
		 *
		 * @return a CalendarHeaderCell object.
		 */
		this.buildCalendarHeaderCell = function(){
			var headCell = $('<div/>');
			headCell.addClass("JFrontierCal-Header-Cell");
			var calHeadCell = new CalendarHeaderCell(headCell);
			return calHeadCell;
		};
		
		/**
		 * Builds a CalendarWeekHeaderCell object. One cell in the CalendarWeekHeader.
		 *
		 * @return a CalendarWeekHeaderCell object.
		 */
		this.buildCalendarWeekHeaderCell = function(){
			var weekHeaderCell = $('<div/>');
			weekHeaderCell.addClass("JFrontierCal-Week-Header-Cell");
			/*
			//experiment with jquery UI theme
			weekHeaderCell.addClass("ui-state-default");
			weekHeaderCell.css("padding","0px");
			weekHeaderCell.css("margin","0px");
			weekHeaderCell.css("border-top","0px");
			weekHeaderCell.css("border-right","0px");
			weekHeaderCell.css("border-left","0px");
			weekHeaderCell.css("border-bottom","0px");
			weekHeaderCell.removeAttr("background-image");
			*/
			var calWeekHeadCell = new CalendarWeekHeaderCell(weekHeaderCell);
			return calWeekHeadCell;
		};		 

		/**
		 * Builds a CalendarDayCell object. One cell in the CalendarWeek object.
		 *
		 * @return a CalendarDayCell object.
		 */
		this.buildCalendarDayCell = function(){
			var dayCell = $('<div/>');
			dayCell.addClass("JFrontierCal-Day-Cell");
			/*
			//experiment with jquery UI theme
			dayCell.addClass("ui-state-default");
			dayCell.css("padding","0px");
			dayCell.css("margin","0px");
			dayCell.css("border-top","0px");
			dayCell.css("border-right","0px");
			dayCell.css("border-left","0px");
			dayCell.css("border-bottom","0px");
			dayCell.removeAttr("background-image");
			*/
			var calDay = new CalendarDayCell(dayCell);
			return calDay;
		};
		
		/**
		 * Get the current year, 4-digit.
		 *
		 * @return integer
		 */
		this.getCurrentYear = function(){
			return parseInt(this.displayDate.getFullYear());
		};
		
		/**
		 * Get the current month
		 *
		 * @return integer, 0 = Jan, 11 = Dec
		 */
		this.getCurrentMonth = function(){
			return parseInt(this.displayDate.getMonth());
		};

		/**
		 * Get the current day
		 *
		 * @return integer
		 */

		this.getCurrentDay = function(){
			return parseInt(this.displayDate.getDate());
		};

		/**
		 * Get a new date with the next month
		 *
		 * @return A Date object
		 */
		this.getNextMonth = function(){
			var dt = new Date(0,0,1,0,0,0,0);
			if(this.getCurrentMonth() == 11){
				dt.setFullYear(this.getCurrentYear()+1);
				dt.setMonth(0);
			}else{
				dt.setFullYear(this.getCurrentYear());
				dt.setMonth(this.getCurrentMonth()+1);
			}
			return dt;
		};

		/**
		 * Get a new date with the previous month
		 *
		 * @return A Date object
		 */		
		this.getPreviousMonth = function(){
			var dt = new Date(0,0,1,0,0,0,0);
			if(this.getCurrentMonth() == 0){
				dt.setFullYear(this.getCurrentYear()-1);
				dt.setMonth(11);
			}else{
				dt.setFullYear(this.getCurrentYear());
				dt.setMonth(this.getCurrentMonth()-1);
			}
			return dt;
		};	
		
		/**
		 * Return number of days in current month
		 *
		 * @return integer
		 */
		this.getDaysCurrentMonth = function(){
			return parseInt(DateUtil.getDaysInMonth(this.displayDate));
		};
		
		/**
		 * Return number of days in previous month
		 *
		 * @return integer
		 */		
		this.getDaysPreviousMonth = function(){
			var prevDt = this.getPreviousMonth();
			return parseInt(DateUtil.getDaysInMonth(prevDt));
		};	
		
		/**
		 * Return number of days in next month
		 *
		 * @return integer
		 */			
		this.getDaysNextMonth = function(){
			var nextDt = this.getNextMonth();
			return parseInt(DateUtil.getDaysInMonth(nextDt));
		};
		
		this.setHtml = function(htmlData){
			this.jqyObj.html(htmlData);
		};
		
		this.getHtml = function(){
			return this.jqyObj.html();
		};		
		
		this.setCss = function(attr,value){
			this.jqyObj.css(attr,value);
		};
		
		this.getCss = function(attr){
			return this.jqyObj.css(attr);
		};		
		
		this.setAttr = function(id,value){
			this.jqyObj.attr(id,value);
		};
		
		this.getAttr = function(id){
			return this.jqyObj.attr(id);
		};		
		
		/**
		 * Clear all data in the calendar </div> element, inluding
		 * all week objects, week header objects & the calendar header object.
		 *
		 * @param clearAgenda - boolean - pass true to clear agenda items as well.
		 */
		this.clear = function(clearAgenda){
			this.jqyObj.html("");
			this.calHeaderObj = null;
			this.weeks = new Array();
			this.weekHeaders = new Array();
			this.dayHash = new Hashtable();
			if(clearAgenda){
				this.agendaItems = new Hashtable();
			}
		};
		
		/**
		 * Get the height of the calendar <div/> element
		 *
		 * @see JQuery.height();
		 * @return integer
		 */
		this.getHeight = function(){
			return this.jqyObj.height();
		}		
		
		/**
		 * Get the width of the calendar <div/> element
		 *
		 * @see JQuery.width();
		 * @return integer
		 */
		this.getWidth = function(){
			return this.jqyObj.width();
		};
		
		/**
		 * Set the width of the calendar <div/> element
		 *
		 * @see JQuery.width();
		 * @param w - integer
		 */
		this.setWidth = function(w){
			this.jqyObj.width(w);
			this.resize();
		};		
		
		/**
		 * Get the inner width of the calendar <div/> element
		 *
		 * @see JQuery.innerWidth();
		 * @return integer
		 */
		this.getInnerWidth = function(){
			return this.jqyObj.innerWidth();
		};
		
		/**
		 * Add a header to the calendar
		 *
		 * @param calHeader - A CalendarHeader object
		 */
		this.addHeader = function(calHeader){
			// remove existing header if there is one
			if(this.calHeaderObj != null){
				// already have header
				var headerDiv = this.jqyObj.children("div").first();
				headerDiv.remove();
				this.calHeaderObj = calHeader;
				this.jqyObj.prepend(calHeader.jqyObj);	
			}else{
				this.calHeaderObj = calHeader;
				this.jqyObj.prepend(calHeader.jqyObj);				
			}
		};
		
		// append a CalendarWeek object
		this.addWeek = function(calWeek){
			// push is not supported by IE 5/Win with the JScript 5.0 engine
			this.weeks.push(calWeek);		
			this.jqyObj.append(calWeek.jqyObj);
		};
		
		// append a CalendarWeekHeader object
		this.addWeekHeader = function(calWeekHeader){
			// push is not supported by IE 5/Win with the JScript 5.0 engine
			this.weekHeaders.push(calWeekHeader);		
			this.jqyObj.append(calWeekHeader.jqyObj);
		};
		
		// returns an array of CalendarWeek objects
		this.getWeeks = function(){
			return this.weeks;
		};

		// returns an array of CalendarWeekHeader objects
		this.getWeekHeaders = function(){
			return this.weekHeaders;
		};		
		
		// return the number of weeks for the current month
		this.getNumberWeeks = function(){
			return this.weeks.length;
		};
		
		/**
		 * Add a CalendarAgendaItem to the calendar.
		 *
		 * @param item - (CalendarAgendaItem) - A new CalendarAgendaItem object.
		 */
		this.addAgendaItem = function(item){
			if(item.getAgendaId() == 0){
				// no internal agend ID, we need to give it one
				item.setAgendaId(this.agendaId);	
				// increment id value for next agenda item.
				this.agendaId++;				
			}
			// add agenda item to hash with unique id
			this.agendaItems.put(item.getAgendaId(),item);
			// render the item
			this.renderSingleAgendaItem(item);			
		};
		
		/**
		 * Retrieve the number of agenda items in the calendar.
		 *
		 * @return integer
		 */
		this.getAgendaItemsCount = function(){
			return this.agendaItems.size();
		};		
		
		/**
		 * Retrieve all agenda items.
		 *
		 * @return (Hashtable jshashtable) of CalendarAgendaItem objects.
		 */
		this.getAgendaItems = function(){
			return this.agendaItems;
		};
		
		/**
		 * Get an agenda item by ID.
		 *
		 * @param id - integer - the unique agenda ID.
		 * @return A CalendarAgendaItem object.
		 */
		this.getAgendaItemById = function(id){
			return this.agendaItems.get(id);
		};
		
		/**
		 * Retrieve all agenda items with a specific attribute value in their data hash.
		 *
		 * @param attrName - string - The attribute name in the agenda data object.
		 * @param attrValue - string/number - The value of the attribute in the agenda data object.
		 * @return An array of CalendarAgendaItem objects.
		 */		
		this.getAgendaItemByDataAttr = function(attrName,attrValue){
			if(this.agendaItems != null && this.agendaItems.size() > 0){		
				var agi = null;
				var val = null;
				var pattern = null;
				var itemsToReturn = new Array();
				var itemArray = this.agendaItems.values();
				for(var itemIndex = 0; itemIndex < itemArray.length; itemIndex++){
					// CalendarAgendaItem object
					agi = itemArray[itemIndex];
					val = agi.getAgendaData(attrName);
					if(val != null){
						// check by exact value
						if(val == attrValue){
							itemsToReturn.push(agi);
						}
						/*
						else{
							// check by regular expression
							pattern = new RegExp(attrValue);
							if(pattern.test(val)){
								itemsToDelete.push(agi);
							}							
						}
						*/
					}
				}
				return itemsToReturn;
			}
			return null;
		};			
		
		/**
		 * Deletes an agenda item from the calendar.
		 *
		 * @param id - integer - The unique agenda ID.
		 */		
		this.deleteAgendaItemById = function(id){
			if(this.agendaItems != null && this.agendaItems.size() > 0){
				this.agendaItems.remove(id);
				this.clearDayCellData();
				this.renderAgendaItems();
			}
		};
		
		/**
		 * Delete an agenda item by a value in its data hash.
		 *
		 * @param attrName - string - The attribute name in the agenda data object.
		 * @param attrValue - string/number - The value of the attribute in the agenda data object.		
		 */
		this.deleteAgendaItemByDataAttr = function(attrName,attrValue){
			if(this.agendaItems != null && this.agendaItems.size() > 0){
				var agi = null;
				var val = null;
				var pattern = null;
				var itemsToDelete = new Array();
				var itemArray = this.agendaItems.values();
				for(var itemIndex = 0; itemIndex < itemArray.length; itemIndex++){
					// CalendarAgendaItem object
					agi = itemArray[itemIndex];
					val = agi.getAgendaData(attrName);
					if(val != null){
						// check by exact value
						if(val == attrValue){
							itemsToDelete.push(agi);
						}
						/*
						else{
							// check by regular expression
							pattern = new RegExp(attrValue);
							if(pattern.test(val)){
								itemsToDelete.push(agi);
							}							
						}
						*/
					}
				}
				if(itemsToDelete.length > 0){
					for(var i=0; i<itemsToDelete.length; i++){
						this.deleteAgendaItemById(itemsToDelete[i].getAgendaId());
					}
					this.clearDayCellData();
					this.renderAgendaItems();					
				}
			}
		};

		/**
		 * Deletes all agenda items.
		 */		
		this.deleteAllAgendaItems = function(){
			if(this.agendaItems != null && this.agendaItems.size() > 0){
				this.agendaItems = new Hashtable();
				this.clearDayCellData();
				this.renderAgendaItems();
			}
		};		
		
		// append a JQuery object
		this.appendJqyObj = function(obj){
			this.jqyObj.append(obj);
		};
		
		this.shoutOut = function(){
			alert("You have a calendar object!");
		};
		
		/**
		 * This function could be good when we delete agenda items. Since deleting an agenda item
		 * does not require resizing the calendar we can simply delete the agenda divs and 
		 * re-render them.
		 * 
		 * Loops through all the days cells and clears the html and agenda rendering positison.
		 */
		this.clearDayCellData = function(){
			var weekCount = 0;
			var weekCellsArray = this.getWeeks(); // all the week <div>'s in the calendar
			if(weekCellsArray != null && weekCellsArray.length > 0){
				weekCount = weekCellsArray.length;
				for(var weekIndex = 0; weekIndex < weekCellsArray.length; weekIndex++){
					// all the day cells for the current week cell
					var dayCellsArray = weekCellsArray[weekIndex].getDays();
					if(dayCellsArray != null && dayCellsArray.length > 0){
						// loop through all days of the week
						for(var dayIndex = 0; dayIndex < dayCellsArray.length; dayIndex++){
							dayCellsArray[dayIndex].clearAgendaDivElements();
						}
					}
				}
			}		
		};

		/**
		 * Set the rendering ratio. By default the value is 1 making the day cells roughly as tall as they are wide.
		 *
		 * @param ration - float - A number less than or equal to 1 and greater than 0. Use 0.5 to make the day cells
		 *		 	   roughly half as tall as they are wide.
		 */
		this.setRenderRatio = function(ratio){
			if(ratio != null && ratio <= 1 && ratio > 0){
				this.aspectRatio = ratio;
				this.resize();
			}
		}		
		
		/**
		 * call this function when the browser is resized. Resizes all <div/> elements. Clears all agenda item
		 * renders and then re-renders them.
		 *
		 */
		this.resize = function(){
			
			var firstDayCell = null;
			var firstWeekHeaderCell = null;
			var lastDayCell = null;
			var lastHeaderCell = null;
			var lastWeekHeaderCell = null;			
			
			var calWidth = this.getWidth(); // excludes padding
			
			// all day cells, header cells, and week header cells, should have the same left & right margin, left & right padding,
			// and left & right border widths. We'll grab the first day cell and use it's values for all other calculations.
			var weekArray = this.getWeeks();
			if(weekArray != null && weekArray.length > 0){
				var dayArray = weekArray[0].getDays();
				if(dayArray != null && dayArray.length > 0){
					firstDayCell = dayArray[0];
				}
			}
			// get first week header cell
			var weekHeadArray = this.getWeekHeaders();
			if(weekHeadArray != null && weekHeadArray.length > 0){
				var weekHeadCellArray = weekHeadArray[0].getHeaderCells();
				if(weekHeadCellArray != null && weekHeadCellArray.length > 0){
					firstWeekHeaderCell = weekHeadCellArray[0];
				}
			}
			var headerCellHeight = firstWeekHeaderCell.jqyObj.outerHeight(true);

			var borderSize = (firstDayCell.jqyObj.outerWidth(true) - firstDayCell.jqyObj.width()) * Calendar.dayNames.length;
			
			var cellWidth = Math.floor(calWidth / Calendar.dayNames.length) - (firstDayCell.jqyObj.outerWidth(true) - firstDayCell.jqyObj.width());
			var cellWidthLast = cellWidth + ( calWidth - (cellWidth * Calendar.dayNames.length)) - (firstDayCell.jqyObj.outerWidth(true) - firstDayCell.jqyObj.width())-borderSize;
			//var cellWidth = Math.floor(calWidth / Calendar.dayNames.length) - this.cellBorderWidth - (this.cellPadding * 2);
			//var cellWidthLast = cellWidth + ( calWidth - (cellWidth * Calendar.dayNames.length)) - this.cellBorderTotal - this.cellPaddingTotal;
			
			// make the day cells square
			var cellHeight = parseInt((cellWidth - headerCellHeight) * this.aspectRatio);
			//var cellHeight = cellWidth - this.dayCellHeaderCellHeight;
			
			// width of all elements inside the header <div/>
			var totalHeaderWidth = ((cellWidth * 6) + cellWidthLast) + ((firstDayCell.jqyObj.outerWidth(true) - firstDayCell.jqyObj.width()) * Calendar.dayNames.length) + 1;
			//var totalHeaderWidth = (cellWidth * 6) + cellWidthLast + this.cellBorderTotal + this.cellPaddingTotal;
			
			// set the width of the header <div/> that wraps all the header cells.
			this.calHeaderObj.setWidth(totalHeaderWidth);
			//this.calHeaderObj.jqyObj.css("width",totalHeaderWidth+"px");
			
			// loop over all cells in header and update their size
			var headerCellsArray = this.calHeaderObj.getHeaderCells();
			if(headerCellsArray != null && headerCellsArray.length > 0){
				for(var headIndex = 0; headIndex < headerCellsArray.length; headIndex++){
					//alert("Resizing header cell " + headIndex);
					if(headIndex == (headerCellsArray.length - 1)){
						// last cell in the header
						headerCellsArray[headIndex].setCss("width",cellWidthLast+"px");
					}else{
						headerCellsArray[headIndex].setCss("width",cellWidth+"px");
						lastHeaderCell = headerCellsArray[headIndex];
					}
				}
			}				
			
			// loop through all weeks & week headers. Update width of day cells and week header cells
			// each week has a week header (the arrays should be the same size if the initialization worked correctly)
			var weekCount = 0;
			var weekCellsArray = this.getWeeks(); // all the week <div>'s in the calendar
			var weekHeadersArray = this.getWeekHeaders(); // all the week header <div>'s in the calendar
			if(weekCellsArray != null && weekCellsArray.length > 0){
				weekCount = weekCellsArray.length;
				for(var weekIndex = 0; weekIndex < weekCellsArray.length; weekIndex++){
					// set the width of the week <div/> that wraps all the day cells.
					weekCellsArray[weekIndex].setWidth(totalHeaderWidth);
					// set the width of the week header <div/> that wraps all the day cells.
					weekHeadersArray[weekIndex].setWidth(totalHeaderWidth);
					var dayCellsArray = weekCellsArray[weekIndex].getDays(); // all the day cells for the current week cell
					var weekHeaderCellsArray = weekHeadersArray[weekIndex].getHeaderCells(); // all the week header cells for the current week header
					if(dayCellsArray != null && dayCellsArray.length > 0){
						// loop through all days of the week
						for(var dayIndex = 0; dayIndex < dayCellsArray.length; dayIndex++){
							if(dayIndex == (dayCellsArray.length - 1)){
								
								// last day cell in the week (Saturday)
								
								// set widths
								dayCellsArray[dayIndex].setCss("width",cellWidthLast+"px");
								weekHeaderCellsArray[dayIndex].setCss("width",cellWidthLast+"px");
								
								// set height (make it the same as width so we have a nice aspect ratio)
								dayCellsArray[dayIndex].setCss("height",cellHeight+"px");
								
								// clear agenda item html for this cell, we will re-render in
								dayCellsArray[dayIndex].clearAgendaDivElements();
								
							}else{
							
								// Sunday to friday day cells
							
								// set widths
								dayCellsArray[dayIndex].setCss("width",cellWidth+"px");
								weekHeaderCellsArray[dayIndex].setCss("width",cellWidth+"px");
								
								// set height (make it the same as width so we have a nice aspect ratio)
								dayCellsArray[dayIndex].setCss("height",cellHeight+"px");								
								
								// clear agenda item html for this cell, we will re-render in
								dayCellsArray[dayIndex].clearAgendaDivElements();
								
								// we'll use these later
								lastDayCell = dayCellsArray[dayIndex];
								lastWeekHeaderCell = weekHeaderCellsArray[dayIndex];
								
							}
						}
					}
				}
			}
			
			// get some user specified CSS values
			var headerCellTotalHeight = parseInt(lastHeaderCell.jqyObj.outerHeight(true));
			var dayCellTotalHeight = parseInt(lastDayCell.jqyObj.outerHeight(true) * weekCount);
			var weekHeaderCellTotalHeight = parseInt(lastWeekHeaderCell.jqyObj.outerHeight(true) * weekCount);
			
			var totalCalendarHeight = headerCellTotalHeight + dayCellTotalHeight + weekHeaderCellTotalHeight;

			// set height of calendar <div/>
			this.setCss("height",totalCalendarHeight+"px");
			
			// re-render all agenda items
			this.renderAgendaItems();
			
		
		};
		
		/**
		 * Gets the day index within the week for the day name
		 *
		 * getWeekIndex("Sun") = 0;
		 * getWeekIndex("Mon") = 1;
		 * getWeekIndex("Tue") = 2;
		 * getWeekIndex("Wed") = 3;
		 * getWeekIndex("Thu") = 4;
		 * getWeekIndex("Fri") = 5;
		 * getWeekIndex("Sat") = 6;
		 *
		 * @param dayName - The name of the day, fullname or abbreviated name.
		 @ @return number - Index of day within the week.
		 */
		this.getWeekIndex = function(dayName){
			if(dayName.toUpperCase() == "SUN" || dayName.toUpperCase() == "SUNDAY"){
				return 0;
			}else if(dayName.toUpperCase() == "MON" || dayName.toUpperCase() == "MONDAY"){
				return 1;
			}else if(dayName.toUpperCase() == "TUE" || dayName.toUpperCase() == "TUESDAY"){
				return 2;
			}else if(dayName.toUpperCase() == "WED" || dayName.toUpperCase() == "WEDNESDAY"){
				return 3;
			}else if(dayName.toUpperCase() == "THU" || dayName.toUpperCase() == "THURSDAY"){
				return 4;
			}else if(dayName.toUpperCase() == "FRI" || dayName.toUpperCase() == "FRIDAY"){
				return 5;
			}else if(dayName.toUpperCase() == "SAT" || dayName.toUpperCase() == "SATURDAY"){
				return 6;
			}else{
				return -1
			}			
		};
	
	};
	// static properties
	Calendar.dayNames = new Array("Sun","Mon","Tue","Wed","Thu","Fri","Sat");
	/**
	 * Sort function for sorting agenda items by start date.
	 *
	 * @param agi1 - CalendarAgendaItem
	 * @param agi2 - CalendarAgendaItem
	 */
	Calendar.sortAgendaItemsByStartDate = function(agi1,agi2){
		var start1 = agi1.getStartDate();
		var start2 = agi2.getStartDate();
		var s = DateUtil.secondsDifferenceDirection(start1,start2);
		if(s > 0){
			return -1;
		}else if(s < 0){
			return 1;
		}else{
			return 0;
		}
	};
	/**
	 * Converts an internal CalendarAgendaItem object to a new javascript agenda object that
	 * we can pass back to the user. We do this because we don't want the user to have access
	 * the full CalendarAgendaItem object.
	 *
	 * @param agendaObject - A CalendarAgendaItem object.
	 *
	 * @return An object with the following specification.
	 * {
	 *     agendaId: [integer],
	 *     title: [string]
	 *     startDate: [Date],
	 *     endDate: [Date],
	 *	   allDay: [boolean],
	 *	   data: {
	 *			key1: [value1],
	 *          key2: [value2],
	 *          etc...
	 *     },
	 *     displayProp: {
	 *			backgroundColor: [string],
	 *			foregroundColor: [string]
	 *	   }
	 * }	 
	 */
	Calendar.buildUserAgendaObject = function(agendaObject){
		if(agendaObject == null){
			return null;
		}
		var dataObj = null;
		var agendaItemHashData = agendaObject.getAgendaDataHash();
		if(agendaItemHashData != null && agendaItemHashData.size() > 0){
			dataObj = new Object();
			var key = null;
			var val = null;
			var keyArray = agendaItemHashData.keys();
			for(var keyIndex=0; keyIndex<keyArray.length; keyIndex++){
				key = keyArray[keyIndex];
				val = agendaItemHashData.get(key);
				dataObj[key] = val;
			}
		}
		var displayPropObj = new Object();
		displayPropObj["backgroundColor"] = agendaObject.getBackgroundColor();
		displayPropObj["foregroundColor"] = agendaObject.getForegroundColor();		
		var agendaData = {
			agendaId: agendaObject.getAgendaId(),
			title: agendaObject.getTitle(),
			startDate: agendaObject.getStartDate(),
			endDate: agendaObject.getEndDate(),
			allDay: ((agendaObject.isAllDay()) ? true : false),
			data: dataObj,
			displayProp: displayPropObj
		};
		return agendaData;	
	};
	/**
	 * Open the "more agenda items" modal dialog for the specified day.
	 *
	 * @param cal - Object - The Calendar object
	 * @param date - Date object - The day that the modal will show agenda items from.
	 * @param
	 */
	Calendar.showMoreAgendaModal = function(cal,date,agendaItems){
		if(cal == null || date == null || agendaItems == null){
			alert("Can't open dialog. One ore more of Calendar/Date/agendaItems parameters are null.");
		}
		var modalId = cal.jqyObj.attr("id") + "-more-agenda-modal";
		
		// see if we already have a modal dialog with this ID.
		var haveModel = (($("#"+modalId).length == 0) ? false : true);
		
		// clear previous modal dialog
		if(haveModel){
			$("#"+modalId).dialog("destroy");
			$("#"+modalId).remove();
		}
		
		// create modal dialog
		var modal = $("<div/>");
		modal.attr("id",modalId);
		modal.attr("title","Agenda Items for " + date.toDateString());
		modal.dialog({
			autoOpen: false,
			height: 350,
			width: 400,
			modal: true,
			buttons: {
				Ok: function(){
					$(this).dialog('close');
				}
			},
			open: function(event, ui){

			},
			close: function(){

			}
		});
		//$("body").append(modal);
		
		// populate modal with agenda data
		var agi = null;
		var agendaDiv  = null;
		modal.append("<br>");
		
		agendaItems.sort(Calendar.sortAgendaItemsByStartDate);
		
		var isBegining = false;
		var isEnd = false;
		var agendaStartDt = null;
		var agendaEndDt = null;
		
		for(var i=0; i<agendaItems.length; i++){
			agi = agendaItems[i];
			agendaStartDt = agi.getStartDate();
			agendaEndDt = agi.getEndDate();
			agendaDiv = $("<div/>");
			agendaDiv.addClass("JFrontierCal-Modal-Agenda-Item");
			// see if user overloaded the default CSS colors and specified their own through the API call.
			if(agi.getBackgroundColor() != null){
				agendaDiv.css("background-color",agi.getBackgroundColor());
			}
			if(agi.getForegroundColor() != null){
				agendaDiv.css("color",agi.getForegroundColor());
			}			
			agendaDiv.css("width","100%");
			agendaDiv.css("height","30px");
			agendaDiv.css("margin-bottom","2px");
			//agendaDiv.css("padding","3px");
			// round corners for webkit & safari (poor IE :( )
			isBegining = ((DateUtil.daysDifferenceDirection(agendaStartDt,date) == 0) ? true : false);
			isEnd = ((DateUtil.daysDifferenceDirection(agendaEndDt,date) == 0) ? true : false);
			if(isBegining){
				agendaDiv.css("-moz-border-radius-bottomleft","3px");
				agendaDiv.css("-moz-border-radius-topleft","3px");
				agendaDiv.css("-webkit-border-bottom-left-radius","3px");
				agendaDiv.css("-webkit-border-top-left-radius","3px");
			}else{
				var triangle = $("<span/>");
				triangle.css("float","left");
				triangle.addClass("ui-icon ui-icon-circle-triangle-w");
				agendaDiv.append(triangle);			
			}
			if(isEnd){
				agendaDiv.css("-moz-border-radius-topright","3px");
				agendaDiv.css("-moz-border-radius-bottomright","3px");
				agendaDiv.css("-webkit-border-top-right-radius","3px");
				agendaDiv.css("-webkit-border-bottom-right-radius","3px");
			}else{
				var triangle = $("<span/>");
				triangle.css("float","right");
				triangle.addClass("ui-icon ui-icon-circle-triangle-e");
				agendaDiv.append(triangle);			
			}
			
			agendaDiv.append(agi.getTitle() + "<br>");
			if(agi.isAllDay()){
				// don't show start time
				agendaDiv.append("(All day event)<br>");
			}else{
				agendaDiv.append("From " + DateUtil.getAgendaDisplayTime(agendaStartDt) + " on " + agendaStartDt.toDateString() + 
					" To " + DateUtil.getAgendaDisplayTime(agendaEndDt) + " " + agendaEndDt.toDateString()  + "<br>");
			}
			// add click event
			if(cal.clickEvent_agendaCell != null){
				agendaDiv.bind(
					'click',
					{
						// pass agenda ID so user will have access to it in their custom click callback function
						agendaId: agi.getAgendaId(),
						// pass click event callback function so we can call it in clickAgendaFromCalendarHandler() function
						callBack: cal.clickEvent_agendaCell,
						// pass this modal into the click event so we can close it when users click an agenda item
						// before we call the users click handler
						dialog: modal
					},
					// the function that will be called for the click event
					cal.clickAgendaFromCalendarMoreModalDialogHandler						
				);			
			}
			// change mouse cusor to pointer when hovering over agenda divs.
			agendaDiv.hover(
				function() {
					$(this).css('cursor','pointer');
				},
				function() {
					$(this).css('cursor','auto');
				}
			);			
			modal.append(agendaDiv);
		}
		modal.dialog('open');				
		
	};
	/**
	 * Loads the iCal calendar data into the calendar.
	 *
	 * @param cal - Calendar object - A Frontier Calendar.
	 * @param iCalUrl - URL of the ICal file.
	 * @param responseDataType - Data type of the response data from the AJAX call, "html","xml","json","script"
	 */
	Calendar.loadICalSource = function(cal,iCalUrl,responseDataType){
		if(cal == null || iCalUrl == null){
			return;
		}
		//alert("Load ical data: " + cal.getAgendaItemsCount());
		// make ajax call to get ical data
		$.ajax({
			url: iCalUrl,
			type: "POST",
			data: {
				chromeFix: ""
			},
			dataType: responseDataType,
			success: function(data) {
				if(data == null || data == ""){
					alert("iCal load error: Returned data was null or empty string.\n\nSource, " + iCalUrl);
					return;
				}
				icalParser.clear();
				icalParser.parseIcal(data);
				/*				
				All the vevent elements
				alert("Events: " + icalParser.ical.events.length);
				All the vtodo elements
				alert("VToDos: " + icalParser.ical.todos.length);
				All the journal elements
				alert("Journals: " + icalParser.ical.journals.length);
				All the freebusy elements
				alert("Freebusy: " + icalParser.ical.freebusys.length);
				*/
				if(icalParser.ical.events.length > 0){
					var event; var summary;
					var startYear; var startMonth; var startDay;
					var startHour; var startMin; var startSec;
					var endYear; var endMonth; var endDay;
					var endHour; var endMin; var endSec;
					var startDt; var endDt;
					for(var i=0; i<icalParser.ical.events.length; i++){
						event = icalParser.ical.events[i]; 
						summary = ((event.summary != null) ? event.summary.value : "");
						// Example iCal date time 20100617T154500Z
						// strip any preceeding 0's
						startYear = ((event.dtstart != null) ? event.dtstart.value : "").substring(0,4);
						startMonth = ((event.dtstart != null) ? event.dtstart.value : "").substring(4,6).replace(/^[0]+/g,"");
						startDay = ((event.dtstart != null) ? event.dtstart.value : "").substring(6,8).replace(/^[0]+/g,"");
						startHour = ((event.dtstart != null) ? event.dtstart.value : "").substring(9,11).replace(/^[0]+/g,"");
						startMin = ((event.dtstart != null) ? event.dtstart.value : "").substring(11,13).replace(/^[0]+/g,"");
						startSec = ((event.dtstart != null) ? event.dtstart.value : "").substring(13,15).replace(/^[0]+/g,"");
						if(startHour == ""){ startHour = "0"; }						
						if(startMin == ""){ startMin = "0"; }
						if(startSec == ""){ startSec = "0"; }
						//alert("Year: " + startYear + ", Month: " + startMonth + ", Day: " + startDay + ", Hour: " + startHour + ", Min: " + startMin + ", Sec: " + startSec);
						endYear = ((event.dtend != null) ? event.dtend.value : "").substring(0,4);
						endMonth = ((event.dtend != null) ? event.dtend.value : "").substring(4,6).replace(/^[0]+/g,"");
						endDay = ((event.dtend != null) ? event.dtend.value : "").substring(6,8).replace(/^[0]+/g,"");
						endHour = ((event.dtend != null) ? event.dtend.value : "").substring(9,11).replace(/^[0]+/g,"");
						endMin = ((event.dtend != null) ? event.dtend.value : "").substring(11,13).replace(/^[0]+/g,"");
						endSec = ((event.dtend != null) ? event.dtend.value : "").substring(13,15).replace(/^[0]+/g,"");
						if(endHour == ""){ endHour = "0"; }						
						if(endMin == ""){ endMin = "0"; }
						if(endSec == ""){ endSec = "0"; }						
						startDt = new Date(parseInt(startYear),parseInt(startMonth)-1,parseInt(startDay),parseInt(startHour)-1,parseInt(startMin),parseInt(startSec),0);
						endDt = new Date(parseInt(endYear),parseInt(endMonth)-1,parseInt(endDay),parseInt(endHour)-1,parseInt(endMin),parseInt(endSec),0);
						if(DateUtil.secondsDifferenceDirection(startDt,endDt) >= 0){
							// continue if start date is before or on end date
							var hashData = new Hashtable();
							hashData.put('UID',((event.uid != null) ? event.uid.value : ""));
							hashData.put('SUMMARY',((event.summary != null) ? event.summary.value : ""));
							hashData.put('DTSTART',((event.dtstart != null) ? event.dtstart.value : ""));
							hashData.put('DTEND',((event.dtend != null) ? event.dtend.value : ""));
							hashData.put('CLASS',((event.classification != null) ? event.classification.value : ""));
							hashData.put('CREATED',((event.created != null) ? event.created.value : ""));
							hashData.put('DESCRIPTION',((event.description != null) ? event.description.value : ""));
							hashData.put('GEO',((event.geo != null) ? event.geo.value : ""));
							hashData.put('LAST-MODIFIED',((event.lastmod != null) ? event.lastmod.value : ""));
							hashData.put('LOCATION',((event.location != null) ? event.location.value : ""));
							hashData.put('ORGANIZER',((event.organizer != null) ? event.organizer.value : ""));
							hashData.put('PRIORITY',((event.priority != null) ? event.priority.value : ""));
							hashData.put('DTSTAMP',((event.dtstamp != null) ? event.dtstamp.value : ""));
							hashData.put('SEQUENCE',((event.seq != null) ? event.seq.value : ""));
							hashData.put('STATUS',((event.status != null) ? event.status.value : ""));
							hashData.put('TRANSP',((event.transp != null) ? event.transp.value : ""));
							hashData.put('URL',((event.url != null) ? event.url.value : ""));
							hashData.put('RECURRENCE-ID',((event.recurid != null) ? event.recurid.value : ""));
							hashData.put('DURATION',((event.duration != null) ? event.duration.value : ""));
							hashData.put('ATTACH',((event.attach != null) ? event.attach.value : ""));
							hashData.put('ATTENDEE',((event.attendee != null) ? event.attendee.value : ""));
							hashData.put('CATEGORIES',((event.categories != null) ? event.categories.value : ""));
							hashData.put('COMMENT',((event.comment != null) ? event.comment.value : ""));
							hashData.put('CONTACT',((event.contact != null) ? event.contact.value : ""));
							hashData.put('EXDATE',((event.exdate != null) ? event.exdate.value : ""));
							hashData.put('EXRULE',((event.exrule != null) ? event.exrule.value : ""));
							hashData.put('REQUEST-STATUS',((event.rstatus != null) ? event.rstatus.value : ""));
							hashData.put('RELATED',((event.related != null) ? event.related.value : ""));
							hashData.put('RESOURCES',((event.resources != null) ? event.resources.value : ""));
							hashData.put('RDATE',((event.rdate != null) ? event.rdate.value : ""));
							hashData.put('RRULE',((event.rrule != null) ? event.rrule.value : ""));
							hashData.put('X-',((event.xprop != null) ? event.xprop.value : ""));
							var agi = new CalendarAgendaItem(summary,startDt,endDt,false,hashData);
							cal.addAgendaItem(agi);
						}			
					}
				}	
			},
			error: function(request,status,errorThrown) {
				alert("iCal load error: Failure in requesting " + iCalUrl + ": " + errorThrown);
			}
		});		
	};	
	
	/**
	 * Some utility functions for working with javascript Dates.
	 *
	 */
	function DateUtil() {
	
	};
	/**
	 * Get the number of days in the set year & month
	 *
	 * @return integer 0-31
	 */
	DateUtil.getDaysInMonth = function(date){
		/*
		var dt = new Date(date.getFullYear(),date.getMonth(),1,0,0,0,0);
		var month = dt.getMonth();
		var lastMonth = month;
		var dayCount = 0;
		while(lastMonth == month){
			dayCount++;
			dt.setDate(dt.getDate()+1);
			month = dt.getMonth();
		}
		return dayCount;
		*/
		return 32 - new Date(date.getFullYear(), date.getMonth(), 32).getDate();
	};
	/**
         * Check if date1 is between date2 & date3, or on date2 and date3. 
         *
	 * @param date1 - Date - The date you want to check.
	 * @param date2 - Date - The earlier date.
	 * @param date3 - Date - The later date.
         * @return true if date1 is between date2 & date3, or date1 is same day as date2 or date3, false otherwise.
	 */
	DateUtil.isDateBetween = function(date1,date2,date3){
		if(date1 == null || date2 == null || date3 == null){
			return false;
		}
		if(DateUtil.daysDifferenceDirection(date1, date2) <= 0 && DateUtil.daysDifferenceDirection(date1, date3) >= 0){
			return true;
		}
		return false;
	};
	/**
	 * Given two dates this function returns the number of days differnt.
	 *
	 * date1 == date2 return 0;
	 * date1 < date2 return positive integer (number of days)
	 * date2 < date1 return positive inetger (number of days)
	 *
	 * @return integer - The days different
	 */
	DateUtil.daysDifference = function(date1,date2) {
		// The number of milliseconds in one day
		var ONE_DAY = 1000 * 60 * 60 * 24;
		// create new dates so we ignore all hour, min, sec, and milli data
		var dt1 = new Date(date1.getFullYear(),date1.getMonth(),date1.getDate(),0,0,0,0);
		var dt2 = new Date(date2.getFullYear(),date2.getMonth(),date2.getDate(),0,0,0,0);
		// Convert both dates to milliseconds
		var date1_ms = dt1.getTime();
		var date2_ms = dt2.getTime();
		// Calculate the difference in milliseconds
		var difference_ms = Math.abs(date2_ms - date1_ms);
		var diff_day = difference_ms/ONE_DAY;
		return diff_day;
	};
	/**
	 * Similar to this.daysDifference() only this function can return negative
	 * values so you can tell if date2 is before or after date1, and by how many
	 * days.
	 *
	 * date1 == date2 return 0;
	 * date1 < date2 return positive integer (number of days)
	 * date2 < date1 return negative inetger (number of days)
	 *
	 * @return integer - The days different
	 */
	DateUtil.daysDifferenceDirection = function(date1,date2) {
		// create new dates so we ignore all hour, min, sec, and milli data
		var dt1 = new Date(date1.getFullYear(),date1.getMonth(),date1.getDate(),0,0,0,0);
		var dt2 = new Date(date2.getFullYear(),date2.getMonth(),date2.getDate(),0,0,0,0);
		// 1000 * 60 * 60 * 24 = number of milliseconds in one day
		return (dt2.getTime() - dt1.getTime()) / (1000 * 60 * 60 * 24);
	};
	/**
	 * Get seconds difference between date 1 and 2.
	 *
	 * @return integer - The difference in seconds. Negative if date2 < date1, positive if
	 *					 date1 < date2, or 0 if same second.
	 */
	DateUtil.secondsDifferenceDirection = function(date1,date2) {
		return Math.round((date2.getTime() - date1.getTime()) / 1000);
	};	
	/**
	 * Given a Date object with the year, month, and day set, this function will
	 * return a Date object set to the next day. Note, this may be in a different 
	 * month and year!
	 *
	 * @param date - (Date) - A date object with the year, month, and day set.
	 * @return Date - A Date object set to the next day. Note, this may be in a different 
	 * month and year!
	 */
	DateUtil.getNextDay = function(date){
	
		// week index for the set day (from 0-6)
		var dayIndex = date.getDay();
		// day of the month (from 1-31)
		var dayNum = date.getDate();
		// month index for the set month (from 0-11)
		var monthNum = date.getMonth();
		// the 4-digit year
		var yearNum = date.getFullYear();
		// number of days in month
		var daysInMonth = DateUtil.getDaysInMonth(date);
		
		if(dayNum == daysInMonth){
			// next month
			if(yearNum == 11){
				// next year
				return new Date(yearNum+1,monthNum+1,1,0,0,0,0);
			}else{
				// same year
				return new Date(yearNum,monthNum+1,1,0,0,0,0);
			}
		}else{
			// same month & year
			return new Date(yearNum,monthNum,dayNum+1,0,0,0,0);
		}

	};
	/**
	 * Add or remove days to a date.
	 *
	 * @param date - Date object - The date to add or remove days to/from.
	 * @param days - integer - A positive number to add days, a negative number to remove days.
	 * @return A new Date object that very well could be in the next/previous month or year.
	 */
	DateUtil.addDays = function(date,days){
		return new Date(date.getTime() + (days*24*60*60*1000));	
	};	
	/**
	 * Give a date object with the year and month set this function will return a new
	 * date set to the previous month. This may be in a different year.
	 *
	 *
	 * @return Date - A Date object set to the previous month. May be in different year.
	 */
	DateUtil.getPreviousMonth = function(date){
		var dt = new Date(0,0,1,0,0,0,0);
		if(dt.getMonth() == 0){
			dt.setFullYear(date.getFullYear()-1);
			dt.setMonth(11);
		}else{
			dt.setFullYear(date.getFullYear());
			dt.setMonth(date.getMonth()-1);
		}
		return dt;
	};	
	/**
	 * Given a Date object with the year, month, and day set, this function will
	 * return a Date object set to the first day of the same week. This may be in a different
	 * month or year.
	 *
	 * @param date - (Date) - A date object with the year, month, and day set.
	 * @return Date - A Date object set to the last day in the same week. May be in different month or year!
	 */	
	DateUtil.getFirstDayInSameWeek = function(date){

		// week index for the set day (from 0-6)
		var dayIndex = date.getDay();
		// day of the month (from 1-31)
		var dayNum = date.getDate();
		// month index for the set month (from 0-11)
		var monthNum = date.getMonth();
		// the 4-digit year
		var yearNum = date.getFullYear();
		// number of days in month
		var daysInMonth = DateUtil.getDaysInMonth(date);	
		
		if(dayIndex == 0){
			// this is the first day of the week! (Sunday)
			return new Date(yearNum,monthNum,dayNum,0,0,0,0);
		}
		var backDayNum = dayNum - dayIndex;
		if(backDayNum < 1){
			// previous month
			var prevMonthDt = DateUtil.getPreviousMonth(date);
			var daysPrevMonth = DateUtil.getDaysInMonth(prevMonthDt);
			var newDay = daysPrevMonth + backDayNum;
			if(monthNum == 0){
				// previous year
				return new Date(yearNum-1,11,newDay,0,0,0,0);
			}else{
				// same year			
				return new Date(yearNum,monthNum-1,newDay,0,0,0,0);
			}
		}else{
			// same month & year
			return new Date(yearNum,monthNum,backDayNum,0,0,0,0);
		}
		
	};	
	/**
	 * Given a Date object with the year, month, and day set, this function will
	 * return a Date object set to the last day in the same week (last day being Saturday)
	 * Note, this may be in a different month and year!
	 *
	 * @param date - (Date) - A date object with the year, month, and day set.
	 * @return Date - A Date object set to the last day in the same week. May be in different month or year!
	 */
	DateUtil.getLastDayInSameWeek = function(date){
		
		// week index for the set day (from 0-6)
		var dayIndex = date.getDay();
		// day of the month (from 1-31)
		var dayNum = date.getDate();
		// month index for the set month (from 0-11)
		var monthNum = date.getMonth();
		// the 4-digit year
		var yearNum = date.getFullYear();
		// number of days in month
		var daysInMonth = DateUtil.getDaysInMonth(date);
		
		if(dayIndex == 6){
			// this is the last day of the week!
			return new Date(yearNum,monthNum,dayNum,0,0,0,0);
		}
		var daysTillEndWeek = 6 - dayIndex;
		
		if((dayNum + daysTillEndWeek) > daysInMonth){
			// next month
			var nextSunday = daysTillEndWeek - (daysInMonth - dayNum);
			if(yearNum == 11){
				// next year
				return new Date(yearNum+1,monthNum+1,nextSunday,0,0,0,0);
			}else{
				// same year
				return new Date(yearNum,monthNum+1,nextSunday,0,0,0,0);
			}
		}else{
			// same month & year
			return new Date(yearNum,monthNum,dayNum+daysTillEndWeek,0,0,0,0);
		}
		
	};
	/**
	 * Given a Date object with the year, month, and day set, this function will
	 * return a Date object set to the first day of the next week (Sunday.) This may
	 * be in the next month or even the next year.
	 *
	 * @param date - (Date) - A date object with the year, month, and day set.
	 * @return Date - A Date object set to the first dat of the next week. May be in next month or year.
	 */		
	DateUtil.getFirstDayNextWeek = function(date){

		// week index for the set day (from 0-6)
		var dayIndex = date.getDay();
		// day of the month (from 1-31)
		var dayNum = date.getDate();
		// month index for the set month (from 0-11)
		var monthNum = date.getMonth();
		// the 4-digit year
		var yearNum = date.getFullYear();
		// number of days in month
		var daysInMonth = DateUtil.getDaysInMonth(date);	
		
		if(dayIndex == 0){
			// this is the first day of the week! (Sunday)
			return new Date(yearNum,monthNum,dayNum,0,0,0,0);
		}
		var daysTillEndWeek = 6 - dayIndex;
		var nextMonday = dayNum + daysTillEndWeek + 1;
		if(nextMonday > daysInMonth){
			// next month
			var newDay = daysTillEndWeek - (daysInMonth - dayNum) + 1;
			if(yearNum == 11){
				// next year
				return new Date(yearNum+1,monthNum+1,newDay,0,0,0,0);
			}else{
				// same year
				return new Date(yearNum,monthNum+1,newDay,0,0,0,0);
			}
		}else{
			// same month & year
			return new Date(yearNum,monthNum,nextMonday,0,0,0,0);
		}
	};
	/**
	 * Gets the time is a suitable format for displaying on an agendaItem
	 */
	DateUtil.getAgendaDisplayTime = function(date){
		if(date == null){
			return "";
		}
		var c = ":";
		var h = date.getHours();
		var m = date.getMinutes();
		var ampm = "am";
		if(m == 0){
			// don't show minutes if on the hour
			m = "";
			c = "";
		}else if(m < 10){
			m = "0" + m;
		}
		// covert 24-hour to 12-hour with am/pm
		if(h == 0){
			h = 12;
		}else if(h > 12){
			h = h-12;
			ampm = "pm"
		}
		return h + c + m + ampm;
	};
	
	/**
	 * Some utility functions for working with colors
	 *
	 */
	function LogUtil() {
	
	};
	/**
	 * Logs to Firebug console. Comment out for production.
	 *
	 * @param s - string - message to log.
	 */
	LogUtil.log = function(s){
		//console.log((new Date()).toLocaleTimeString() + ": " + s);
	};
	
	/**
	 * Extend JQuery and and add our function.
	 */
	$.fn.jFrontierCal = function(attr,options) {
	
		var elmId = $(this).attr('id');
	
		// default options.
		var opts;
		var defaults = {
			foo: 'bar',
			date: new Date(),
			dayClickCallback: function(eventObj){
				// users can override this method to do something when a day cell is clicked.
			},
			agendaClickCallback: function(eventObj){
				// users can override this method to do something when an agenda item is clicked.
				eventObj.stopPropagation();
			},
			agendaDropCallback: function(eventObj){
				// users can override this method to do something after an agenda item is dropped into a day cell.
			},
			agendaMouseoverCallback: function(eventObj){
				// users can override this method to do something on agenda mouse over events.
			},
			applyAgendaTooltipCallback: function(agendaDivElement,agendaItem){
				// users can override this method to apply an optional tooltip to agenda items for mouse events.
			},
			agendaDragStartCallback: function(eventObj,agendaDivElement,agendaItem){
				// users can override this method to do something when dragging starts on an agenda div element
			},
			agendaDragStopCallback: function(eventObj,agendaDivElement,agendaItem){
				// users can override this method to do something when dragging stops on an agenda div element
			},
			/*true = enable drag-and-drop, false = disabled*/
			dragAndDropEnabled: true
		};	
	
		// Check to see if an object is a plain object (created using "{}" or "new Object").
		if($.isPlainObject(attr)){
			
			/*
			This block will be executed when we call our plugin with options:
			$("#elmId").jFrontierCal({
					foo: '1',
			     bar: '2'
			});
			*/
			
			// extend default options with any that were provided by user
			var options = attr;
			opts = $.extend(defaults, options);
			allOptions[elmId] = opts;
			
		}else{
			
			/*
			This block will be executed when we call our plugin like so:
			$("#elmId").jVertTabsDev();
			Or..
			$("#elmId").jVertTabsDev('active',true);
			*/
			
			opts = $.extend(defaults, options);
			allOptions[elmId] = opts;

		}	
		
		// instantiate instance of plugin and initialize it for all matching elements.
        return this.each(function() {
		
			var calElm = $(this);
			
			// Return early if this element already has a plugin instance
			if (calElm.data('plugin')){
				return;
			}
			
			// options for this calendar
			var thisCalOpts = allOptions[elmId];
			
			// create plugin
			var myplugin = new jFrontierCalPlugin(
				calElm,
				thisCalOpts.dayClickCallback,
				thisCalOpts.agendaClickCallback,
				thisCalOpts.agendaDropCallback,
				thisCalOpts.dragAndDropEnabled,
				thisCalOpts.agendaMouseoverCallback,
				thisCalOpts.applyAgendaTooltipCallback,
				thisCalOpts.agendaDragStartCallback,
				thisCalOpts.agendaDragStopCallback
			);
			
			// initialize calendar
			myplugin.init();
			
			// Store plugin object in this element's data so the user can access it in their code
			calElm.data('plugin', myplugin);
			
        });		

	};	
	
	/**
	 * The interface to our calendar.
	 *
	 * @param calElm - jQuery object reference for the calendar <div/>
	 * @param dayClickCallback - A callback function for clicks on the day cells.
	 * @param agendaClickCallback - A callback function for clicks on agenda items.
	 * @param agendaDropCallback - A callback function for drop events on agenda items.
	 * @param dragAndDropEnabled - boolean - True to enable drag-and-drop, false to disable.
	 * @param agendaMouseoverCallback - A callback function for mouse over events on agenda items.
	 * @param applyAgendaTooltipCallback - A callback function that applies an optional tooltip to agenda div elements.
	 * @param agendaDragStartCallback - A callback function that fires when a drag event starts on an agenda div element.
	 * @param agendaDragStopCallback - A callback function that fires when a drag event stops on an agenda div element.
	 */
	var jFrontierCalPlugin = function(
		calElm,
		dayClickCallback,
		agendaClickCallback,
		agendaDropCallback,
		dragAndDropEnabled,
		agendaMouseoverCallback,
		applyAgendaTooltipCallback,
		agendaDragStartCallback,
		agendaDragStopCallback){
	
		var obj = this;

		// id of calendar <div/> element
		var calId = calElm.attr('id');
		
		// the callback function that's triggered when users click a day cell
		var clickEvent_dayCell = dayClickCallback;
		// the callback function that's triggered when users click an agenda cell
		var clickEvent_agendaCell = agendaClickCallback;
		// the callback function that's triggered when users drop an agenda div into a day cell (drag-and-drop)
		var dropEvent_agendaCell = agendaDropCallback;
		// the callback function that's triggered when users mouse over an agenda item.
		var mouseOverEvent_agendaCell = agendaMouseoverCallback;
		// optional callback function where users can apply a tooltip to an agenda div element.
		var callBack_agendaTooltip = applyAgendaTooltipCallback;
		// the callback function that's triggered when a drag event starts on an agenda div element.
		var dragStart_agendaCell = agendaDragStartCallback;
		// the callback function that's triggered when a drag event stops on an agenda div element.
		var dragDrop_agendaCell = agendaDragStopCallback;
		
		/**
		 * Initialized the plugin. Builds the calendar.
		 *
		 */
		this.init = function(){
		
			// current date and time
			var dtNow = new Date();
			
			var calObj = new Calendar();
			calObj.initialize(
				calElm,
				dtNow,
				clickEvent_dayCell,
				clickEvent_agendaCell,
				dropEvent_agendaCell,
				dragAndDropEnabled,
				mouseOverEvent_agendaCell,
				callBack_agendaTooltip,
				dragStart_agendaCell,
				dragDrop_agendaCell
			);
			
			// store our calendar in a global hash so we can get at it later
			// var calId = calObj.getAttr("id");
			myCalendars.put(calId,calObj);
			
			// when the window is resized we want to resize all calendars we are keeping track of.
			//$(window).resize(this.doResizeAll);
			
			// custom resize (fixes Internet Explorer double resize issue)
			$(window).wresize(this.doResizeAll);
			
			// resize all elements in the calendar relative to the parent clendar </div> element
			this.doResizeAll();
			this.doResizeAll();
			
			return calObj;
		};

		/**
		 * Add agenda item to Calendar.
		 *
		 * @param calId     - (String)  - The ID of the calendar </div> element.
		 * @param title     - (String)  - The title of the agenda item.
		 * @param startDate - (Date)    - Date the agenda event starts.
		 * @param endDate   - (Date)    - Date the agenda event ends.
		 * @param allDay    - (boolean) - True if an all day event (do not show start time on agenda div element), false otherwise. False by default.
		 *
		 * @param data      - (Object)  - Any optional data you want to store in the agenda item. An object with a series of key value pairs, or null.
		 *
		 * Example: Any key value pairs you want.
		 * {
		 *		FirstName: "Robin",
		 *		LastName: "Hood",
		 *		Address: "Sherwood Forest, Nottinghamshire, England"
		 * }
		 *
		 * @param displayProp - (Object) - Optional display properties for the agenda item, or null.
		 *
		 * Allowed Options: 
		 * {
		 *     backgroundColor: [string],
		 *     foregroundColor: [string]
		 * }
		 *
		 */
		this.addAgendaItem = function(calId,title,startDate,endDate,allDay,data,displayProp){
			if(calId != null && title != null && startDate != null && endDate != null && allDay != null){
				// make sure start date comes before end date
				if(DateUtil.secondsDifferenceDirection(startDate,endDate) < 0){
					alert("Sorry, you can't create an event that ends before it starts");
					return;
				}
				calId = stripNumberSign(calId);
				var hashData = new Hashtable();
				if(data != null){
					for(var key in data){
						hashData.put(key,data[key]);
					}
				}
				var agi = new CalendarAgendaItem(title,startDate,endDate,allDay,hashData);
				if(displayProp != null){
					if(displayProp.backgroundColor != null){
						agi.setBackgroundColor(displayProp.backgroundColor);
					}
					if(displayProp.foregroundColor != null){
						agi.setForegroundColor(displayProp.foregroundColor);
					}
				}
				var calObj = myCalendars.get(calId);
				calObj.addAgendaItem(agi);		
			}
		};
		
		/**
		 * Retrieve all agenda items in the calendar.
		 *
		 * @param calId - (String) - The ID of the calendar </div> element.
		 *
		 * @return An array of javascript objects with the following specification:
		 *         
		 * {
		 *     agendaId: [integer],
		 *     title: [string]
		 *     startDate: [Date],
		 *     endDate: [Date],
		 *	   allDay: [boolean],
		 *	   data: {
		 *			key1: [value1],
		 *          key2: [value2],
		 *          etc...
		 *     },
		 *     displayProp: {
		 *			backgroundColor: [string],
		 *			foregroundColor: [string]
		 *	   }
		 * }		 
		 */
		this.getAllAgendaItems = function(calId){
			var itemsToReturn = new Array();
			if(calId != null){
				calId = stripNumberSign(calId);
				var calObj = myCalendars.get(calId);
				var items = calObj.getAgendaItems();
				if(items != null && items.size() > 0){
					var agendObj = null;
					var itemArray = items.values();
					for(var itemIndex = 0; itemIndex < itemArray.length; itemIndex++){
						// CalendarAgendaItem object
						var agi = itemArray[itemIndex];
						agendObj = Calendar.buildUserAgendaObject(agi);
						itemsToReturn.push(agendObj);
					}
				}
			}
			return itemsToReturn;
		};
		
		/**
		 * Get agenda item data by agenda ID. 
		 *
		 * @param calId - (String) - The ID of the calendar </div> element.			 
		 * @param agendaId - integer - The unique ID of the agenda item.
		 * 
		 * When an agenda item is clicked you can get the agenda ID from the click function eventObj.
		 * 
		 * For example if you intialize your calendar like so:
		 *
		 * var jfcalplugin = $("#mycal").jFrontierCal({
		 *     date: new Date(),
		 *     dayClickCallback: myDayClickHandler,
		 * 	   agendaClickCallback: myAgendaClickHandler
		 * }).data("plugin");			 
		 *
		 * You can get the agenda ID like so:
		 *
		 * function myAgendaClickHandler(eventObj){
		 *     var agendaId = eventObj.data.agendaId;
		 *     alert("You clicked agenda item " + agendaId);
		 * }
		 *
		 * @return Javascript Object with the following specification:
		 *         
		 * {
		 *     agendaId: [integer],
		 *     title: [string]
		 *     startDate: [Date],
		 *     endDate: [Date],
		 *	   allDay: [boolean],
		 *	   data: {
		 *			key1: [value1],
		 *          key2: [value2],
		 *          etc...
		 *     },
		 *     displayProp: {
		 *			backgroundColor: [string],
		 *			foregroundColor: [string]
		 *	   }
		 * }
		 *
		 * agendaId  = The unique agenda ID. This is an internal ID used by the calendar. You may have your own agenda ID (for database storage)
		 *             which you can store in the data object (key value pairs)
		 * title	 = The text/message that shows on the agenda item <div/> element.
		 * startDate = The date the agenda item starts
		 * endDate   = The date the agenda item ends
		 * data      = This is the data object that was optionally passed in to the jFrontierCal.addAgendaItem() function
		 *             when you created the agenda item. Whatever data you passed in will be in this object.
         	 *			   e.g. {var1: "some data", var2: "more data", var3: "etc..."}
		 *
		 */
		this.getAgendaItemById = function(calId,agendaId){
			if(calId != null && agendaId != null){
				calId = stripNumberSign(calId);
				var calObj = myCalendars.get(calId);
				var calAgendaItem = calObj.getAgendaItemById(parseInt(agendaId));
				if(calAgendaItem == null){
					return null;
				}
				return Calendar.buildUserAgendaObject(calAgendaItem);
			}
		};
		
		/**
		 * Retrieve all agenda items that have the matching attribute value in their data object.
		 *
		 * @param calId - string - The ID of the calendar </div> element.	
		 * @param attrName - string - The attribute name in the agenda data object.
		 * @param attrValue - string/number - The value of the attribute in the agenda data object.		 
		 *
		 * @return An array of javascript objects with the following specification:
		 *
		 * {
		 *     agendaId: [integer],
		 *     title: [string]
		 *     startDate: [Date],
		 *     endDate: [Date],
		 *	   allDay: [boolean],
		 *	   data: {
		 *			key1: [value1],
		 *          key2: [value2],
		 *          etc...
		 *     },
		 *     displayProp: {
		 *			backgroundColor: [string],
		 *			foregroundColor: [string]
		 *	   }
		 * }		 
		 */
		this.getAgendaItemByDataAttr = function(calId,attrName,attrValue){
			if(calId != null && attrName != null && attrValue != null){
				var itemsToReturn = new Array();
				calId = stripNumberSign(calId);
				var calObj = myCalendars.get(calId);
				var itemArray = calObj.getAgendaItemByDataAttr(attrName,attrValue);
				if(itemArray != null && itemArray.length > 0){
					var agendObj = null;
					for(var itemIndex = 0; itemIndex < itemArray.length; itemIndex++){
						// CalendarAgendaItem object
						var agi = itemArray[itemIndex];
						agendObj = Calendar.buildUserAgendaObject(agi);
						itemsToReturn.push(agendObj);
					}					
				}
				return itemsToReturn;
			}
			return null;
		};
		
		/**
		 * Delete an agenda item by ID.
		 *
		 * @param calId - (String) - The ID of the calendar </div> element.		 
		 * @param agendaId - integer - The unique ID of the agenda item.
		 */
		this.deleteAgendaItemById = function(calId,agendaId){
			if(calId != null && agendaId != null){
				calId = stripNumberSign(calId);
				var calObj = myCalendars.get(calId);
				calObj.deleteAgendaItemById(agendaId);
			}				
		};
		
		/**
		 * Delete an agenda item by data attribute. Will delete all agenda items where the data attribute value matches.
		 *
		 * @param calId - (String) - The ID of the calendar </div> element.
		 * @param attrName - string - The attribute name in the agenda data object.
		 * @param attrValue - string/number - The value of the attribute in the agenda data object.
		 */
		this.deleteAgendaItemByDataAttr = function(calId,attrName,attrValue){
			if(calId != null && attrName != null && attrValue != null){
				calId = stripNumberSign(calId);
				var calObj = myCalendars.get(calId);
				calObj.deleteAgendaItemByDataAttr(attrName,attrValue);
			}				
		};

		/**
		 * Delete all agenda items from the calendar.
		 *
		 * @param calId - (String) - The ID of the calendar </div> element.
		 */
		this.deleteAllAgendaItems = function(calId){
			if(calId != null){
				calId = stripNumberSign(calId);
				var calObj = myCalendars.get(calId);
				calObj.deleteAllAgendaItems();
			}		
		};
		
		/**
		 * Get the number of agenda items stored in the calendar.
		 *
		 * @param calId - (String) - The ID of the calendar </div> element.
		 * @return integer.
		 */
		this.getAgendaItemsCount = function(calId){
			if(calId != null){
				calId = stripNumberSign(calId);
				var calObj = myCalendars.get(calId);
				return calObj.getAgendaItemsCount();
			}		
		};
		
		/**
		 * Switch to the previous month
		 *
		 * @param calId - (String) - The ID of the calendar </div> element.
		 */
		this.showPreviousMonth = function(calId){
			if(calId != null){
				calId = stripNumberSign(calId);
				var calObj = myCalendars.get(calId);
				calObj.previousMonth();
			}
		};
		
		/**
		 * Switch to the next month
		 *
		 * @param calId - (String) - The ID of the calendar </div> element.
		 */
		this.showNextMonth = function(calId){
			if(calId != null){
				calId = stripNumberSign(calId);
				var calObj = myCalendars.get(calId);
				calObj.nextMonth();
			}
		};		
		
		/**
		 * Set the calendar to the specified year & month.
		 *
		 * @param calId - (String) - The ID of the calendar </div> element.
		 * @param year  - (String) - 4-digit year (e.g, "2010" or "1979")
		 * @param month - (String) - Month (e.g. "0" = Janurary, "1" or "01" = February, "11" = December)
		 */
		this.showMonth = function(calId,year,month){
			if(calId != null && year != null && month != null && year.length == 4 && (month.length == 1 || month.length == 2)){
				calId = stripNumberSign(calId);
				// strip any preceeding 0's
				month = month.replace(/^[0]+/g,"");
				var yearInt = parseInt(year);
				var monthInt = parseInt(month);
				var dateToShow = new Date(yearInt,monthInt,1,0,0,0,0);
				var calObj = myCalendars.get(calId);
				calObj.setDisplayDate(dateToShow);
			}
		};
		
		/**
		 * Retrieves the date that the calendar is currently set to.
		 *
		 * @param calId - (String) - The ID of the calendar </div> element.
		 * @return A Date object. The date the calendar is currently set to (year & month)
		 */
		this.getCurrentDate = function(calId){
			if(calId != null){
				calId = stripNumberSign(calId);
				var calObj = myCalendars.get(calId);
				return calObj.getDisplayDate();
			}
		};
		
		/**
		 * Re-render all the agenda items on the calendar. If your js code causes the location of the calendar
		 * to move (e.g. get shifted down on the page...) the agenda items may not be rendered in the correct
		 * location. You can call this function to re-render the agenda items so they appear in the right places.
		 *
		 * For example, you may have a div element located above the calendar that changes size at some point. This will
		 * cause the calendar to shift down on the page. Since the agenda items are absolute positioned they will no
		 * longer be in the right place. You can call this function to re-render them.
		 *
		 *
		 */
		this.reRenderAgendaItems = function(calId){
			if(calId != null){
				calId = stripNumberSign(calId);
				var calObj = myCalendars.get(calId);
				calObj.clearDayCellData();
				calObj.renderAgendaItems();			
			}		
		};
		
		/**
		 * Resizes all calendars that the plugin is managing.
		 *
		 */		
		this.doResizeAll = function(){
			if(myCalendars != null && myCalendars.size() > 0){
				var cals = myCalendars.values();
				for(var i=0; i<cals.length; i++){
					cals[i].resize();
				}
			}
		};

		/**
		 * Fire resize event for a specific calendar. Sometimes you may want to manually call the resize event. For example
		 * if you are using JQuery tabs UI element and you initialize the calendar in a closed tab the width will be 0 causing
		 * the calendar to render incorrectly. In the tabs show() event you can call this doResize() method on the calendar to
		 * get it to properly render.
		 *
		 * @param calId - (String) - The ID of the calendar </div> element.
		 */		
		this.doResize = function(calId){
			if(myCalendars != null && myCalendars.size() > 0 && calId != null){
				calId = stripNumberSign(calId);
				var calObj = myCalendars.get(calId);
				calObj.resize();
			}
		};

		/**
		 * Set the aspect ratio of the calendar. By default the aspect ratio is 1. This means all day cells will be roughly
		 * the same height as they are wide (minus the height of the week headers which show the day numbers.) If you want
		 * the calendar to be wider than it is tall (i.e. shorten its height) than you can set the aspect ratio to something
		 * like 0.75 or 0.5. A value of 0.5 means the day cells will be roughly half as high as they are wide.
		 *
		 * @param calId - (String) - The ID of the calendar </div> element.
		 * @param ratio - float - A value less than or equal to 1 and greater than 0. For example, 0.5 will make the day
		 *		           cells half as tall as they are wide, 0.75 will make them 3/4th as tall as they are wide,
		 *			       and a value of 0.25 will make them a quarter tall as they are wide resulting in a very
		 *			       wide calendar relative to its height.
		 */ 
		this.setAspectRatio = function(calId,ratio){
			if(calId != null && ratio != null){
				if(ratio > 1 || ratio <= 0){
					alert("Ratio is out of range. Ratio must be less than or equal to 1, and greater than 0.");
				}else{
					calId = stripNumberSign(calId);
					var calObj = myCalendars.get(calId);
					calObj.setRenderRatio(ratio);
				}
			}
		};

		/**
		 * Performs an AJAX call to retrieve the iCal data at the specified URL and attempts to load the iCal data into the calendar.
		 *
		 * @param calId - (String) - The ID of the calendar </div> element.
		 * @param iCalUrl -(String) - URL for the iCal data.
		 * @param responseDataType - Data type of the response data, "html","xml","json","script"
		 */
		this.loadICalSource = function(calId,iCalUrl,responseDataType){
			if(calId != null && iCalUrl != null){
				calId = stripNumberSign(calId);
				var calObj = myCalendars.get(calId);
				Calendar.loadICalSource(calObj,iCalUrl,responseDataType);
			}
		};
		
		/**
		 *
		 * Following methods are not exposed via the plugin. These are private.
		 *
		 */
		
		/**
		 * Strips the "#" from the begining of string s (if there is one)
		 *
		 * @param s - (String) - A string with a single preceeding # character.
		 * @return A String. The string s without the preceeding # character, or simply s if there is no # character.
		 */
		function stripNumberSign(s){
			if(s != null){
				if(s.startsWith("#")){
					return s.substring(1,s.length);
				}
			}
			return s;
		};

	};

	/**
	 * iCal parser.
	 * http://code.google.com/p/ijp/
	 */
	var icalParser = {

		ical:{
			version:'',
			prodid:'',
			events:[],
			todos:[],
			journals:[],
			freebusys:[]
		},
		test: function(){
			alert("icalParser test is good!");
		},
		clear: function(){
			this.ical.version = '';
			this.ical.prodid = '';
			this.ical.events = [];
			this.ical.todos = [];
			this.ical.journals = [];
			this.ical.freebusys = [];
			
		},
		parseIcal: function(icsString){

			this.ical.version=this.getValue('VERSION',icsString,false);
			this.ical.prodid=this.getValue('PRODID',icsString,false);
		
			var reg=/BEGIN:VEVENT(\r?\n[^B].*)+/g;
			var matches=icsString.match(reg);
			if(matches){
				for(i=0;i<matches.length;i++){
					this.parseVevent(matches[i]);
				}
			}
			reg=/BEGIN:VTODO(\r?\n[^B].*)+/g;

			matches=icsString.match(reg);
			if(matches){
				for(i=0;i<matches.length;i++){
					this.parseVtodo(matches[i]);
				}
			}
			reg=/BEGIN:VJOURNAL(\r?\n[^B].*)+/g;
			matches=icsString.match(reg);
			if(matches){
				for(i=0;i<matches.length;i++){
					this.parseVjournal(matches[i]);
				}
			}
			reg=/BEGIN:VFREEBUSY(\r?\n[^B].*)+/g;
			matches=icsString.match(reg);
			if(matches){
				for(i=0;i<matches.length;i++){
					this.parseVfreebusy(matches[i]);
				}
			}
		},
		parseVfreebusy: function(vfreeString){
			var freebusy={
				contact:this.getValue('CONTACT',vfreeString,false), //
				dtstart:this.getValue('DTSTART',veventString,false), //This property specifies when the calendar component begins.
				dtend:this.getValue('DTEND',veventString,false), //This property specifies when the calendar component ends.
				duration:this.getValue('DURATION',vfreeString,false), //
				description:this.getValue('DESCRIPTION',vfreeString,false), //This property provides a more complete description of the calendar component, than that provided by the "SUMMARY" property.
				dtstamp:this.getValue('DTSTAMP',vfreeString,false), //The property indicates the date/time that the instance of the iCalendar object was created.
				organizer:this.getValue('ORGANIZER',vfreeString,false), //The property defines the organizer for a calendar component.
				uid:this.getValue('UID',vfreeString,false), //This property defines the persistent, globally unique identifier for the calendar component.
				url:this.getValue('URL',vfreeString,false), //This property defines a Uniform Resource Locator (URL) associated with the iCalendar object.
				attendee:this.getValue('ATTENDEE',vfreeString,true), //The property defines an "Attendee" within a calendar component.
				comment:this.getValue('COMMENT',vfreeString,true), //This property specifies non-processing information intended to provide a comment to the calendar user.			
				freebusy:this.getValue('FREEBUSY',vfreeString,true), //The property defines one or more free or busy time intervals.
				rstatus:this.getValue('REQUEST-STATUS',vfreeString,true), //This property defines the status code returned for a scheduling request.			
				xprop:this.getValue('X-',vfreeString,true)
			};
			this.ical.freebusys[this.ical.freebusys.length]=freebusy;
		},
		parseVjournal: function(vjournalString){
			var journal={
				classification:this.getValue('CLASS',vjournalString,false), //This property defines the access classification for a calendar component.
				created:this.getValue('CREATED',vjournalString,false), //This property specifies the date and time that the calendar information was created by the calendar user agent in the calendar store.
				description:this.getValue('DESCRIPTION',vjournalString,false), //This property provides a more complete description of the calendar component, than that provided by the "SUMMARY" property.
				dtstart:this.getValue('DTSTART',veventString,false), //This property specifies when the calendar component begins.
				dtstamp:this.getValue('DTSTAMP',vjournalString,false), //The property indicates the date/time that the instance of the iCalendar object was created.
				lastmod:this.getValue('LAST-MODIFIED',vjournalString,false), //The property specifies the date and time that the information associated with the calendar component was last revised in the calendar store.
				organizer:this.getValue('ORGANIZER',vjournalString,false), //The property defines the organizer for a calendar component.
				recurid:this.getValue('RECURRENCE-ID',vjournalString,false), //This property is used in conjunction with the "UID" and "SEQUENCE" property to identify a specific instance of a recurring "VEVENT", "VTODO" or "VJOURNAL" calendar component. The property value is the effective value of the "DTSTART" property of the recurrence instance.
				seq:this.getValue('SEQUENCE',vjournalString,false), //This property defines the revision sequence number of the calendar component within a sequence of revisions.
				status:this.getValue('STATUS',vjournalString,false), //This property defines the overall status or confirmation for the calendar component.
				summary:this.getValue('SUMMARY',vjournalString,false), //This property defines a short summary or subject for the calendar component.
				uid:this.getValue('UID',vjournalString,false), //This property defines the persistent, globally unique identifier for the calendar component.
				url:this.getValue('URL',vjournalString,false), //This property defines a Uniform Resource Locator (URL) associated with the iCalendar object.
				attach:this.getValue('ATTACH',vjournalString,true), //The property provides the capability to associate a document object with a calendar component.
				attendee:this.getValue('ATTENDEE',vjournalString,true), //The property defines an "Attendee" within a calendar component.
				categories:this.getValue('CATEGORIES',vjournalString,true), //This property defines the categories for a calendar component.
				comment:this.getValue('COMMENT',vjournalString,true), //This property specifies non-processing information intended to provide a comment to the calendar user.			
				contact:this.getValue('CONTACT',vjournalString,true), //The property is used to represent contact information or alternately a reference to contact information associated with the calendar component.
				exdate:this.getValue('EXDATE',vjournalString,true), //This property defines the list of date/time exceptions for a recurring calendar component.
				exrule:this.getValue('EXRULE',vjournalString,true), //This property defines a rule or repeating pattern for an exception to a recurrence set.
				related:this.getValue('RELATED',vjournalString,true), //To specify the relationship of the alarm trigger with respect to the start or end of the calendar component.
				rdate:this.getValue('RDATE',vjournalString,true), //This property defines the list of date/times for a recurrence set.
				rrule:this.getValue('RRULE',vjournalString,true), //This property defines a rule or repeating pattern for recurring events, to-dos, or time zone definitions.
				rstatus:this.getValue('REQUEST-STATUS',vjournalString,true), //This property defines the status code returned for a scheduling request.			
				xprop:this.getValue('X-',vjournalString,true)
			};
			this.ical.journals[this.ical.journals.length]=journal;
		},
		parseVtodo: function(vtodoString){
			var todo={
				classification:this.getValue('CLASS',vtodoString,false), //This property defines the access classification for a calendar component.
				completed:this.getValue('COMPLETED',vtodoString,false), //This property defines the date and time that a to-do was actually completed.
				created:this.getValue('CREATED',vtodoString), //This property specifies the date and time that the calendar information was created by the calendar user agent in the calendar store.
				description:this.getValue('DESCRIPTION',vtodoString,false), //This property provides a more complete description of the calendar component, than that provided by the "SUMMARY" property.
				dtstamp:this.getValue('DTSTAMP',vtodoString,false), //The property indicates the date/time that the instance of the iCalendar object was created.
				geo:this.getValue('GEO',vtodoString,false), //This property specifies information related to the global position for the activity specified by a calendar component.
				lastmod:this.getValue('LAST-MODIFIED',vtodoString,false), //The property specifies the date and time that the information associated with the calendar component was last revised in the calendar store.
				location:this.getValue('LOCATION',vtodoString,false), //The property defines the intended venue for the activity defined by a calendar component.
				organizer:this.getValue('ORGANIZER',vtodoString,false), //The property defines the organizer for a calendar component.
				percent:this.getValue('PERCENT-COMPLETE',vtodoString,false), //This property is used by an assignee or delegatee of a to-do to convey the percent completion of a to-do to the Organizer.
				priority:this.getValue('PRIORITY',vtodoString,false), //The property defines the relative priority for a calendar component.
				recurid:this.getValue('RECURRENCE-ID',vtodoString,false), //This property is used in conjunction with the "UID" and "SEQUENCE" property to identify a specific instance of a recurring "VEVENT", "VTODO" or "VJOURNAL" calendar component. The property value is the effective value of the "DTSTART" property of the recurrence instance.
				seq:this.getValue('SEQUENCE',vtodoString,false), //This property defines the revision sequence number of the calendar component within a sequence of revisions.
				status:this.getValue('STATUS',vtodoString,false), //This property defines the overall status or confirmation for the calendar component.
				summary:this.getValue('SUMMARY',vtodoString,false), //This property defines a short summary or subject for the calendar component.
				uid:this.getValue('UID',vtodoString,false), //This property defines the persistent, globally unique identifier for the calendar component.
				url:this.getValue('URL',vtodoString,false), //This property defines a Uniform Resource Locator (URL) associated with the iCalendar object.
				due:this.getValue('DUE',vtodoString,false), //This property defines the date and time that a to-do is expected to be completed.
				duration:this.getValue('DURATION',vtodoString,false), //The property specifies a positive duration of time.
				attach:this.getValue('ATTACH',vtodoString,true), //The property provides the capability to associate a document object with a calendar component.
				attendee:this.getValue('ATTENDEE',vtodoString,true), //The property defines an "Attendee" within a calendar component.
				categories:this.getValue('CATEGORIES',vtodoString,true), //This property defines the categories for a calendar component.
				comment:this.getValue('COMMENT',vtodoString,true), //This property specifies non-processing information intended to provide a comment to the calendar user.			
				contact:this.getValue('CONTACT',vtodoString,true), //The property is used to represent contact information or alternately a reference to contact information associated with the calendar component.
				exdate:this.getValue('EXDATE',vtodoString,true), //This property defines the list of date/time exceptions for a recurring calendar component.
				exrule:this.getValue('EXRULE',vtodoString,true), //This property defines a rule or repeating pattern for an exception to a recurrence set.
				rstatus:this.getValue('REQUEST-STATUS',vtodoString,true), //This property defines the status code returned for a scheduling request.			
				related:this.getValue('RELATED',vtodoString,true), //To specify the relationship of the alarm trigger with respect to the start or end of the calendar component.
				resources:this.getValue('RESOURCES',vtodoString,true), //This property defines the equipment or resources anticipated for an activity specified by a calendar entity..
				rdate:this.getValue('RDATE',vtodoString,true), //This property defines the list of date/times for a recurrence set.
				rrule:this.getValue('RRULE',vtodoString,true), //This property defines a rule or repeating pattern for recurring events, to-dos, or time zone definitions.
				xprop:this.getValue('X-',vtodoString,true)
			};
			this.ical.todos[this.ical.todos.length]=todo;
		},
		parseVevent: function(veventString){
			
			var event={
				classification:this.getValue('CLASS',veventString,false), //This property defines the access classification for a calendar component.
				created:this.getValue('CREATED',veventString,false), //This property specifies the date and time that the calendar information was created by the calendar user agent in the calendar store.
				description:this.getValue('DESCRIPTION',veventString,false), //This property provides a more complete description of the calendar component, than that provided by the "SUMMARY" property.
				geo:this.getValue('GEO',veventString,false), //This property specifies information related to the global position for the activity specified by a calendar component.
				lastmod:this.getValue('LAST-MODIFIED',veventString,false), //The property specifies the date and time that the information associated with the calendar component was last revised in the calendar store.
				location:this.getValue('LOCATION',veventString,false), //The property defines the intended venue for the activity defined by a calendar component.
				organizer:this.getValue('ORGANIZER',veventString,false), //The property defines the organizer for a calendar component.
				priority:this.getValue('PRIORITY',veventString,false), //The property defines the relative priority for a calendar component.
				dtstamp:this.getValue('DTSTAMP',veventString,false), //The property indicates the date/time that the instance of the iCalendar object was created.
				seq:this.getValue('SEQUENCE',veventString,false), //This property defines the revision sequence number of the calendar component within a sequence of revisions.
				status:this.getValue('STATUS',veventString,false), //This property defines the overall status or confirmation for the calendar component.
				transp:this.getValue('TRANSP',veventString,false), //This property defines whether an event is transparent or not to busy time searches.
				url:this.getValue('URL',veventString,false), //This property defines a Uniform Resource Locator (URL) associated with the iCalendar object.
				recurid:this.getValue('RECURRENCE-ID',veventString,false), //This property is used in conjunction with the "UID" and "SEQUENCE" property to identify a specific instance of a recurring "VEVENT", "VTODO" or "VJOURNAL" calendar component. The property value is the effective value of the "DTSTART" property of the recurrence instance.
				duration:this.getValue('DURATION',veventString,false), //The property specifies a positive duration of time.
				attach:this.getValue('ATTACH',veventString,true), //The property provides the capability to associate a document object with a calendar component.
				attendee:this.getValue('ATTENDEE',veventString,true), //The property defines an "Attendee" within a calendar component.
				categories:this.getValue('CATEGORIES',veventString,true), //This property defines the categories for a calendar component.
				comment:this.getValue('COMMENT',veventString,true), //This property specifies non-processing information intended to provide a comment to the calendar user.			
				contact:this.getValue('CONTACT',veventString,true), //The property is used to represent contact information or alternately a reference to contact information associated with the calendar component.
				exdate:this.getValue('EXDATE',veventString,true), //This property defines the list of date/time exceptions for a recurring calendar component.
				exrule:this.getValue('EXRULE',veventString,true), //This property defines a rule or repeating pattern for an exception to a recurrence set.
				rstatus:this.getValue('REQUEST-STATUS',veventString,true), //This property defines the status code returned for a scheduling request.			
				related:this.getValue('RELATED',veventString,true), //To specify the relationship of the alarm trigger with respect to the start or end of the calendar component.
				resources:this.getValue('RESOURCES',veventString,true), //This property defines the equipment or resources anticipated for an activity specified by a calendar entity..
				rdate:this.getValue('RDATE',veventString,true), //This property defines the list of date/times for a recurrence set.
				rrule:this.getValue('RRULE',veventString,true), //This property defines a rule or repeating pattern for recurring events, to-dos, or time zone definitions.
				xprop:this.getValue('X-',veventString,true), //
				uid:this.getValue('UID',veventString,false), //This property defines the persistent, globally unique identifier for the calendar component.
				summary:this.getValue('SUMMARY',veventString,false), //This property defines a short summary or subject for the calendar component.
				dtstart:this.getValue('DTSTART',veventString,false), //This property specifies when the calendar component begins.
				dtend:this.getValue('DTEND',veventString,false) //This property specifies the date and time that a calendar component ends.
			};
			this.ical.events[this.ical.events.length]=event;
			
		},
		getValue: function(propName,txt,multiple){
			if(multiple){
				eval('var matches=txt.match(/\\n'+propName+'[^:]*/g)');
				var props=[];
				if(matches){
					for(l=0;l<matches.length;l++){
						//on enleve les parametres 
						matches[l]=matches[l].replace(/;.*/,'');
						props[props.length]=this.getValue(matches[l],txt);
					}
					return props;
				}
			}else{
				var reg=new RegExp('('+propName+')(;[^=]*=[^;:\n]*)*:([^\n]*)','g');
				var matches=reg.exec(txt);
				if(matches){ //on a trouvé la propriété cherchée
					var valeur=RegExp.$3;
					var tab_params;
					if(RegExp.$2.length>0){ //il y a des paramètres associés
						var params=RegExp.$2.substr(1).split(';');
						var pair;var code='';
						for(k=0;k<params.length;k++){
							pair=params[k].split('=');
							if(!pair[1]) pair[1]=pair[0];
							code+=pair[0].replace(/-/,'')+' : "'+pair[1]+'", '; 
						}
						eval('tab_params=( { '+code.substr(0,code.length-2)+' } );');
					}
					return {
						value:valeur,
						params:tab_params
					};
				}else{
					return null;
				}
			}
		}
	}
		
})(jQuery);


/*   
=============================================================================== 
WResize is the jQuery plugin for fixing the IE window resize bug 
............................................................................... 
                                               Copyright 2007 / Andrea Ercolino 
------------------------------------------------------------------------------- 
LICENSE: http://www.opensource.org/licenses/mit-license.php 
WEBSITE: http://noteslog.com/ 

http://noteslog.com/post/how-to-fix-the-resize-event-in-ie/
=============================================================================== 
*/ 
 
( function( $ )  
{ 
    $.fn.wresize = function( f )  
    { 
        version = '1.1'; 
        wresize = {fired: false, width: 0}; 
 
        function resizeOnce()  
        { 
            if ( $.browser.msie ) 
            { 
                if ( ! wresize.fired ) 
                { 
                    wresize.fired = true; 
                } 
                else  
                { 
                    var version = parseInt( $.browser.version, 10 ); 
                    wresize.fired = false; 
                    if ( version < 7 ) 
                    { 
                        return false; 
                    } 
                    else if ( version == 7 ) 
                    { 
                        //a vertical resize is fired once, an horizontal resize twice 
                        var width = $( window ).width(); 
                        if ( width != wresize.width ) 
                        { 
                            wresize.width = width; 
                            return false; 
                        } 
                    } 
                } 
            } 
 
            return true; 
        } 
 
        function handleWResize( e )  
        { 
            if ( resizeOnce() ) 
            { 
                return f.apply(this, [e]); 
            } 
        } 
 
        this.each( function()  
        { 
            if ( this == window ) 
            { 
                $( this ).resize( handleWResize ); 
            } 
            else 
            { 
                $( this ).resize( f ); 
            } 
        } ); 
 
        return this; 
    }; 
 
} ) ( jQuery );


