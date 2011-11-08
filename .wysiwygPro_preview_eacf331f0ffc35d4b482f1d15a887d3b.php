<?php
if ($_GET['randomId'] != "zYnsM_CiD7D76UwlBuW1bo1MhodOlNi_YvMnh5jQvzfZzrT07xLxnZChU9DGeCNe") {
    echo "Access Denied";
    exit();
}

// display the HTML code:
echo stripslashes($_POST['wproPreviewHTML']);

?>  
