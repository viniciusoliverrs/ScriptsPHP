<?php
function dump() {
exec("mysqldump --host=[host] -u [user] -[pass] [dbname] > mysqldump --host=[host] -u [user] -[pass] [dbname]");
}
dump();
?>
