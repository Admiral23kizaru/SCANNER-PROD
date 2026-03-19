<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "scan_up");
$res = $mysqli->query("DESCRIBE attendance");
while ($row = $res->fetch_assoc()) if($row['Field']=='session') echo $row['Type'];
