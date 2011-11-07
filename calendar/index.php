<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<link rel='stylesheet' type='text/css' href='fullcalendar/fullcalendar.css' />
<link rel='stylesheet' type='text/css' href='fullcalendar/fullcalendar.print.css' media='print' />
<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js'></script>
<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js'></script>
<script type='text/javascript' src='fullcalendar/fullcalendar.min.js'></script>
<script type='text/javascript'>
    $(document).ready(function() {
        
        $.urlParam = function(name){
            var results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(window.location.href);
            return results[1] || 0;
        }
        

        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next today,prevYear,nextYear',
                center: 'title',
                right: 'month,agendaWeek,agendaDay,basicDay'
            },
            buttonText: {
                prev:     '&nbsp;&#9668;&nbsp;',  // left triangle
                next:     '&nbsp;&#9658;&nbsp;',  // right triangle
                prevYear: '&nbsp;Last Year&nbsp;', // <<
                nextYear: '&nbsp;Next Year&nbsp;', // >>
                today:    'Today',
                month:    'Month',
                agendaWeek:'Week',
                agendaDay:'Day',
                basicDay: 'List'
                },
            defaultView: 'month',
            minTime: 8,
            maxTime: 17,
            slotMinutes: 30,
            lazyFetching: true,
            weekends: false,
            
            eventSources: [
                {
                    cache: true,
                    url: 'fullCalendarJSON.php',
                    data: {
                        judge: '<?php
                            if(isset($_GET["judge"])){
                                echo htmlspecialchars($_GET["judge"]);
                            }
                            ?>',
                        casetype: 0
                    },
                    color: 'blue',   // a non-ajax option
                    textColor: 'white' // a non-ajax option
                },
                {
                    cache: true,
                    url: 'fullCalendarJSON.php',
                    data: {
                        judge: '<?php
                            if(isset($_GET["judge"])){
                                echo htmlspecialchars($_GET["judge"]);
                            }
                            ?>',
                        casetype: 1
                    },
                    color: 'red',   // a non-ajax option
                    textColor: 'white' // a non-ajax option
                }
            
            
            
            ],
            
            loading: function(bool) {
                if (bool) $('#loading').show();
                else $('#loading').hide();
            }
            
        });
        
        jQuery('a.fc-event').live('click', function(){
                    newwindow=window.open($(this).attr('href'),'','height=580,width=790');
                    if (window.focus) {newwindow.focus()}
                        return false;
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
<div id='calendar'><p>json-events.php needs to be running in the same directory.</p><p>



    </p></div>

</body>
</html>
