<html>
<head><link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
  <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
  
  <script src="../jquery.Storage.js"></script>
  <script src="../date.js"></script> 
  
<script>
    $(document).ready(function(){
        // Set default values from local storage and calculate dates
        $('#contact').val($.Storage.get("contactLine"));
        $('#judge').val($.Storage.get("judge"));
        var nextMonday = Date.today().next().monday();
        $('#start').val(nextMonday.toString("M/d/yyyy"));
        var nextFriday = nextMonday.add(4).days();
        $('#last').val(nextFriday.toString("M/d/yyyy"));    
        
        // Store local data
        $('#contact').blur(function(){
            //alert('Handler for .blur() called.');
            $.Storage.set({"contactLine": $('#contact').val() });
        });
        $('#judge').blur(function(){
            //alert('Handler for .blur() called.');
            $.Storage.set({"judge": $('#judge').val() });
        });
        
    });


    // Create date pickers with no weekends
    $(function() {
        $( "#start" ).datepicker({ beforeShowDay: $.datepicker.noWeekends });        
    });

    $(function() {
        $( "#last" ).datepicker({ beforeShowDay: $.datepicker.noWeekends });
    });
    
    //
    $.Storage.set("name", "value") -
    $.Storage.get("name")
    
</script>

<style type="text/css">
th.ui-datepicker-week-end,
td.ui-datepicker-week-end {
    display: none;
}

.contact { float: left; margin-right: 1em; }
.contact p { margin-top: 0; }
</style>

</head>
<body>
<basefont size="15">

<h2>Create Jacket Tags:</h2>
<p>
<form action="jacketTags.php" method="get" id="jacksetForm">
    Judge (last name)
    <input type="text" name="judge" id="judge"><br />
    From: <input type="text" name="start" id="start"> to: 
    <input type="text" name="last" id="last"><br />
    Contact line:
    <input type="text" id="contact" name="contact"><br />
    Case type: <input type="radio" name="casetype" value="0"> Civil <input type="radio" name="casetype" value="1" checked> Criminal <input type="radio" name="casetype" value="2"> Both   
    
    <input type="submit" value="Create">
    <input type="reset" value="Clear">
</form></p>
<hr>

</body>
</html>
