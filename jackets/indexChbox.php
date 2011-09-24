<html>
<head><link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
  <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>

<script>
    $(document).ready(function(){
        $("button").click(function(){
            $("settingsTable").load("one man");
        }
    }


	$(function() {
		$( "#start" ).datepicker();
	});
	
	$(function() {
		$( "#last" ).datepicker();
	});
	</script>

</head>
<body>
<basefont size="5" color="green">
    

<h2>Build Jacket Tags:</h2>
<p>
<form action="jacketTags.php" method="get">
	Judge (last name)
    <input type="text" name="judge"><br />
	Start Date (must be in 2011-09-07 format)
    <input type="text" name="start" id="start">
    <br />End Date (must be in 2011-09-07 format)
    <input type="text" name="last" id="last"><br />
    Contact line:
    <input type="text" name="contact" id="contact"><br />
    
<input type="checkbox" name="judge" value="Allen" /> Allen<br />
<input type="checkbox" name="judge" value="Myers" /> Myers<br /><input type="checkbox" name="judge" value="Metz" /> Metz<br />
<input type="checkbox" name="judge" value="Nadel" /> Nadel

<br />

    <input type="radio" name="casetype" value="0"> Civil<br>
    <input type="radio" name="casetype" value="1" checked> Criminal<br>
    <input type="radio" name="casetype" value="2"> Both
    <hr>    
    
	<input type="submit" value="Create">
	<input type="reset" value="Clear">
	<button>Change Content</button
	<select name="users" onchange="showUser(this.value)">
    <option value="">Select a person:</option>
    <option value="1">Peter Griffin</option>
    <option value="2">Lois Griffin</option>
    <option value="3">Glenn Quagmire</option>
    <option value="4">Joseph Swanson</option>
    </select>
	
	
</form></p>
<div id="settingsTable"><b>The listbofncase settings will appear here.</b></div>
</body>
</html>
