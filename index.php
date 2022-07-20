<?php

$data_Path = "./_data/";
$log_Path = "./_log/";

if (isset($_SERVER['REQUEST_URI'])) {
    $request = $_SERVER['REQUEST_URI'];
} else {
    $request = substr($_SERVER['PHP_SELF'], 1);
    if (isset($_SERVER['QUERY_STRING']) AND $_SERVER['QUERY_STRING'] != '') {
        $request .= '?' . $_SERVER['QUERY_STRING'];
    }
}
if (isset($_POST['action'])) {
    $request = $_POST['action'] . '/';
}

{
    header("HTTP/1.1 200 OK");
    $path = parse_url ($request, PHP_URL_PATH);
    $action = basename( $path );
    switch ($action) {
        case "getvalue": // this action enable from v 0.1.x
            // JSON_API , Post Parameters : tag
            $tagName  = $_REQUEST['tag'];
            $tagValue = '';
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Content-type: application/json');
	    if (substr($tagName, -4, 4) == ".log") {
	    	$tagFile = $data_Path . $tagName;
		$fh = fopen($tagFile, "r") or die("File not found.");
		$tagValue = array();
		while (($buffer = fgets($fh)) !== false) {
        	    $tagValue[] = rtrim($buffer);
    		}
            	// echo json_encode($tagValue);
            	echo json_encode(array(
                    "VALUE",
                    $tagName,
                    $tagValue
            	));
    		fclose($fh);
	    } else {
	    	$tagFile = $data_Path . $tagName . ".txt";
            	is_file($tagFile) && ($tagValue = file_get_contents($tagFile));
            	echo json_encode(array(
                    "VALUE",
                    $tagName,
                    $tagValue
            	));
	    }
            exit;
            break;
        case "storeavalue": // this action will enable from v 0.2.x
            // JSON_API , Post Parameters : tag,value
            $tagName     = $_POST['tag'];
            $tagValue    = $_POST['value'];
            $apiKey      = '';	// $_POST['apikey'];
            $log_message = sprintf("%s:%s\n", date('Y-m-d H:i:s'), "storeavalue: ($apiKey) $tagName -- $tagValue");
            $file_name   = $log_Path . 'tinywebdb_' . date('Y-m-d') . '.log';
            error_log($log_message, 3, $file_name);
            $setting_apikey = '';
            if ($apiKey == $setting_apikey) {
	    	$tagFile = $data_Path . $tagName;
                if(strlen($tagValue) == 0) { 
		    unlink($tagFile . ".txt");
                    echo "Removed tagName: " . $tagName;
		    exit;
		}
                $fh = fopen($tagFile . ".txt", "w") or die("check file write permission.");
                fwrite($fh, $tagValue);
                fclose($fh);
		$tagLog = json_encode(array(
                    'date'=>time(),
                    'tagName'=>$tagName,
                    'tagValue'=>$tagValue
                ));
                $fh = fopen($tagFile . ".log", "a") or die("check file write permission.");
                fwrite($fh, $tagLog . "\n");
                fclose($fh);
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Content-type: application/json');
                echo json_encode(array(
                    "STORED",
                    $tagName,
                    $tagValue
                ));
            } else {
                echo "check api key.";
            }
            exit;
            break;
        default:
            break;
    }
}

$listTxt = array();
if ($handler = opendir($data_Path)) {
    while (($sub = readdir($handler)) !== FALSE) {
        if (substr($sub, -4, 4) == ".txt") {
            $listTxt[] = $sub;
        }
    }
    closedir($handler);
}

$listLog = array();
if ($handler = opendir($log_Path)) {
    while (($sub = readdir($handler)) !== FALSE) {
        if (substr($sub, 0, 10) == "tinywebdb_") {
            $listLog[] = $sub;
        }
    }
    closedir($handler);
}

?>
<h3>App Inventor (TinyWebDB) Web Database Service</h3>

<h1> 出席管理システム・プロトタイプ</h1>
<h2> Attend Managment System Prototype </h2>
<br/>
<?php

if (file_exists('weeks.php')) echo "<h3>TinyWebDB <a href=weeks.php>Weeks</a></h3>";
else echo "<h3>TinyWebDB Tags</h3>";
echo "<table border=1>";
echo "<thead><tr>";
echo "<th> Tag </th>";
echo "<th> Value </th>";
echo "<th> Time </th>";
echo "<th> Size </th>";
echo "<th> Log </th>";
echo "</tr></thead>\n";
if ($listTxt) {
    sort($listTxt);
    foreach ($listTxt as $sub) {
	$tagName = substr($sub, 0, -4);
        echo "<tr>";
        echo "<td><a href=getvalue?tag=$tagName>$tagName</a></td>\n";
        echo "<td>" ; readfile($data_Path . $sub) ; echo "</td>\n";
        echo "<td>" . date('Y-m-d H:i:s',filemtime($data_Path . $sub)) . "</td>\n";
        echo "<td>" . filesize($data_Path . $sub) . "</td>\n";
        echo "<td><a href=getvalue?tag=$tagName.log>$tagName.log</a></td>\n";
        echo "</tr>";
    }
}
echo "</table>";

echo "<h3>TinyWebDB Log Tail</h3>";
echo "<table border=1>";
echo "<thead><tr>";
echo "<th> Log Name </th>";
echo "<th> Size </th>";
echo "</tr></thead>\n";
if ($listLog) {
    sort($listLog);
    foreach ($listLog as $sub) {
        echo "<tr>";
        echo "<td><a href=?logfile=" . $sub . ">$sub</a></td>\n";
        echo "<td>" . filesize($log_Path . $sub) . "</td>\n";
        echo "</tr>";
    }
}
echo "</table>";

if (isset($_GET['logfile'])) {
    $logfile = $log_Path . substr($_GET['logfile'], 0, 24);
    echo "<h2>Log file : " . $logfile . "</h2>";
    if (file_exists($logfile)) {
	if (file_exists('draw.php')) echo "<p><img src = 'draw.php?logfile=$logfile'></p>";
	$lines = wp_tinywebdb_api_read_tail($logfile, 20);
	foreach ($lines as $line) {
	    echo $line . "<br>";
	}
    }
}


exit; // this stops rest steps

function wp_tinywebdb_api_read_tail($file, $lines)
{
    //global $fsize;
    $handle      = fopen($file, "r");
    $linecounter = $lines;
    $pos         = -2;
    $beginning   = false;
    $text        = array();
    while ($linecounter > 0) {
        $t = " ";
        while ($t != "\n") {
            if (fseek($handle, $pos, SEEK_END) == -1) {
                $beginning = true;
                break;
            }
            $t = fgetc($handle);
            $pos--;
        }
        $linecounter--;
        if ($beginning) {
            rewind($handle);
        }
        $text[$lines - $linecounter - 1] = fgets($handle);
        if ($beginning)
            break;
    }
    fclose($handle);
    return array_reverse($text);
}

