<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    
    <title>Case Jacket Tag Picker</title>
    <style type="text/css" title="currentStyle">
      @import "/DataTables/media/css/demo_page.css";
      @import "/DataTables/media/css/demo_table.css";
    </style>
    
    <script type="text/javascript" language="javascript" src="/DataTables/js/jquery.js"></script>
    <script type="text/javascript" language="javascript" src="/DataTables/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf-8">
        function getUrlVars(){
                var vars = [], hash;
                var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
                for(var i = 0; i < hashes.length; i++)
                {
                    hash = hashes[i].split('=');
                    vars.push(hash[0]);
                    vars[hash[0]] = hash[1];
                }
                return vars;
     	}
                        
	// Get any paramaters from the url and build a url
	// inNumber removes the getURlVars results that are keyed
	// to a numberic index.  WTF.  This is a kludge.
	function isNumber(n) {
              return !isNaN(parseFloat(n)) && isFinite(n);
        }
            
	JSONdata = "/dataTableJSON.php?";
	JSONdata = "/jackets/jsondata.php?";
	myParams = getUrlVars();
	for(var index in myParams) {
		if  (isNumber(index)) {continue;}
		JSONdata = JSONdata + index + "=" + myParams[index] + "&";
		
		if ( index == "judge" )    { judge = decodeURIComponent( myParams[index] ); 
			//document.write ( $judge );
			}
		if ( index == "start" )    { start = decodeURIComponent ( myParams[index] ); 
			//document.write ( $start);
			}
		if ( index == "last" )	   { last =  decodeURIComponent( myParams[index] ); 
			//document.write ( $last );
			}
		if ( index == "contact" )  { contact = decodeURIComponent( myParams[index] ); }
		if ( index == "casetype" ) { casetype = decodeURIComponent( myParams[index] ); }
	}
        
        //  adds an event to all links with class .popup to open in a popup window
        $(document).ready(function() {
            jQuery('a.popup').live('click', function(){
                newwindow=window.open($(this).attr('href'),'','height=580,width=790');
                if ( window.focus ) { newwindow.focus() }
                return false;
                $(document).keydown(function(e) {
                  switch(e.keyCode) { 
                     // User pressed "right" arrow
                     case 39:
                        $('#example').fullCalendar('next');
                     break;
                     // User pressed "left" arrow
                     case 37:
                        $('#example').fullCalendar('prev');
                     break;
                  }
               })
                
          });
          
          
        var oTable = $('#example').dataTable( {
          "bProcessing": true,
          "bAutoWidth": false,
          "bPaginate": false,
          "aaSorting": [[ 3, "asc" ]],
          "sAjaxSource": JSONdata,
          "aoColumns": [
		{ "mDataProp": "check_box" },
		{ "mDataProp": "NAC_date_formatted" },
		{ "mDataProp": "caption" },
		{ "mDataProp": "NAC"}
            ]
        } );
        
        $("#judge").val( decodeURIComponent( judge ) );     
		$("#start").val( decodeURIComponent( start ) );
		$("#last").val( last );
		$("#casetype").val( casetype );
		$("#contact").val( decodeURIComponent( contact ) );        
      } );
      
      $(function(){

          // add multiple select / deselect functionality
          $("#selectall").click(function () {
                $('.case').attr('checked', this.checked);
          });

          // if all checkbox are selected, check the selectall checkbox
          // and viceversa
          $(".case").click(function(){

              if($(".case").length == $(".case:checked").length) {
                  $("#selectall").attr("checked", "checked");
              } else {
                  $("#selectall").removeAttr("checked");
              }

          });
      });
      
    </script>
  </head>
  <body id="dt_example">
    <div id="dynamic">
      <form name="input" action="pdf.php" method="get">
          <input type="hidden" id="contact" name="contact" value="23" />     
       <input type="checkbox" id="selectall"/>Select / Deselect All 
    
<table cellpadding="0" cellspacing="0" border="0" class="display" id="example">
  <thead>
    <tr>
      <th width="5%">Select</th>
      <th width="25%">Date &amp; Time</th>
      <th width="50%">Caption</th>
      <th width="20%">Action</th>
    </tr>
  </thead>
  <tbody>
    
  </tbody>
  <tfoot>
    <tr>
      <th>Select</th>      
        <th>Date &amp; Time</th>
        <th>Caption</th>
      <th>Action</th>
    
    </tr>
  </tfoot>
</table>
<input type="submit" value="Create Tags" />

</form>
      </div>
</body>
</html>