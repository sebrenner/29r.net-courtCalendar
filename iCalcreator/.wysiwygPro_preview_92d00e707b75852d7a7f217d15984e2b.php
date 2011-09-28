<?php
if ($_GET['randomId'] != "l3V_5JMgUT3SdIbWiLw6GGj2sfCTN6zNWmBdCFodUSsYMe07tpdStgZKrJ5EOPae") {
    echo "Access Denied";
    exit();
}

// display the HTML code:
echo stripslashes($_POST['wproPreviewHTML']);

?>  
