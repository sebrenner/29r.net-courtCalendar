<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<link rel='stylesheet' type='text/css' href='fullcalendar/fullcalendar.css' />
<link rel='stylesheet' type='text/css' href='fullcalendar/fullcalendar.print.css' media='print' />
<script type='text/javascript' src='jquery/jquery-1.5.2.min.js'></script>
<script type='text/javascript' src='jquery/jquery-ui-1.8.11.custom.min.js'></script>
<script type='text/javascript' src='fullcalendar/fullcalendar.min.js'></script>
<script type='text/javascript'>

    function getURLParameter(name) {
        return decodeURI(
            (RegExp(name + '=' + '(.+?)(&|$)').exec(location.search)||[,null])[1]
        );
    }


	$(document).ready(function() {
	
	    var date = new Date();
		var d = date.getDate();
		var m = date.getMonth();
		var y = date.getFullYear();
		var taken23 = getURLParameter('take');
		var judge = getURLParameter('judge');
		var earliestDate = getURLParameter('earliestDate');
        var lastDate = getURLParameter('lastDate');
        
		
		
		$('#calendar').fullCalendar({
		    header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,agendaWeek,agendaDay'
			},
			defaultView: 'agendaWeek',      
            weekends: false,        // will hide Saturdays and Sundays
            lazyFetching: true,     // will rely on cache when switching views.
            
//          events: "fullCalendarJSONData.php?judge=allen&mystart=2010-08-01&end=2011-12-01",
            events: "https://www.google.com/calendar/feeds/en.usa%23holiday%40group.v.calendar.google.com/public/basic"
            error: function() {
                    alert('there was an error while fetching events!');
                },
                // color: 'yellow',   // a non-ajax option
                // textColor: 'black' // a non-ajax option
            },

			eventDrop: function(event, delta) {
				alert(event.title + ' was moved ' + delta + ' days\n' +
					'(should probably update your database)');
			},
			
			eventMouseover: function(calEvent, domEvent) {
            	var layer ="<div id='events-layer' class='fc-transparent' style='position:absolute; width:100%; height:100%; top:-1px; text-align:right; z-index:100'></div>";
            	$(this).append(layer);
            }, 

            eventMouseout: function(calEvent, domEvent) {
            },
			
			
			
			loading: function(bool) {
				if (bool) $('#loading').show();
				else $('#loading').hide();
			}
			
		});
		
	});

</script>
<style type='text/css'>

	body {
		margin-top: 40px;
		text-align: center;
		font-size: 14px;
		font-family: "Lucida Grande",Helvetica,Arial,Verdana,sans-serif;
		}
		
	#loading {
		position: absolute;
		top: 5px;
		right: 5px;
		}

	#calendar {
		width: 900px;
		margin: 0 auto;
		}

</style>
</head>
<body>
<div id='loading' style='display:none'>loading...</div>
<div id='calendar'></div>
<p>json-events.php needs to be running in the same directory.</p>
</body>
</html>