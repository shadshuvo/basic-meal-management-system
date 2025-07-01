<?php
$new_password = "put_pass_here";
$hash = password_hash($new_password, PASSWORD_DEFAULT);
echo $hash;