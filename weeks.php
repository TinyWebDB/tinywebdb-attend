<h1>TinyWebDB API and Weekly View</h1>
<h2> <a href=index.php>HOME</a>  <a href=weeks.php>Weeks</a></h2>
<form method="POST" action="">
<?php

$data_Path = "./_data/";
$log_Path = "./_log/";

setlocale(LC_TIME, "ja_JP");
date_default_timezone_set('Asia/Tokyo');
$listLog = array();
$listTxt = array();
if ($handler = opendir($data_Path)) {
    while (($sub = readdir($handler)) !== FALSE) {
        if (substr($sub, -4, 4) == ".txt") {
            $listTxt[] = $sub;
        } elseif (substr($sub, 0, 10) == "tinywebdb_") {
            $listLog[] = $sub;
        }
    }
    closedir($handler);
}

echo "<h3>TinyWebDB Tags</h3>";
echo "<table border=1>";

echo "<thead><tr>";
echo "<th> Student </th>";
for($i = 1; $i<=15; $i++) {
    echo "<th> W$i </th>";
}
echo "</tr></thead>\n";

$wlist = get_everydate("weeks");

if ($listTxt) {
    sort($listTxt);
    foreach ($listTxt as $sub) {
	if ($sub == "weeks.txt" ) continue;
    	echo "<tr><td>" . substr($sub, 0, -4) . "</td>\n";
	foreach($wlist as $w) {
	    get_attendate(substr($sub, 0, -4), $w);
	}
    }

    echo "</tr>\n";
}

exit; // this stops rest steps

function get_everydate($id){
    global $data_Path;

    echo "<tr><td></td>\n";
    $fh = fopen($data_Path . "$id.log", "r") or die("File not found.");
    while (($buffer = fgets($fh)) !== false) {
        $json = rtrim($buffer);
        $obj = json_decode($json);
        echo "<td>" . $obj->date . "</td>\n";
        $wlist[] =  $obj->date;
    }
    echo "</tr>\n";
    return $wlist;
}

function get_attendate($id,$w){
    global $data_Path;
    $date = new DateTime("2022/$w 13:10:00");
    $wdate = $date->format('U');

    echo "<td>";
    # echo $w . "|";
    # echo $wdate . "|";
    $fh = fopen($data_Path . "$id.log", "r") or die("---$data_Path$id.log File not found.");
    while (($buffer = fgets($fh)) !== false) {
        $json = rtrim($buffer);
        $obj = json_decode($json);
	$diff = $wdate - $obj->date;
        if($diff > -1800 && $diff < 1800) {
	    echo 20 + intval($diff / 60);
	    break;
	}
    }
    echo "</td>";

}

