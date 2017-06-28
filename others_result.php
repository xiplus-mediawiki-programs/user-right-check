<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
</head>
<body>
<?php
require(__DIR__."/../config/config.php");
date_default_timezone_set('UTC');
@include(__DIR__."/config.php");
require(__DIR__."/../function/log.php");

$timelimit = date("Y-m-d H:i:s", strtotime($_GET["limit"] ?? "-6 months"));
echo "顯示最後動作 < ".$timelimit." (".($_GET["limit"] ?? "-6 months").")<br>";

$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}userlist` WHERE `lastedit` < :lastedit AND `lastlog` < :lastlog AND `lastusergetrights` < :lastusergetrights ORDER BY `lastedit` ASC, `lastlog` ASC");
$sth->bindValue(":lastedit", $timelimit);
$sth->bindValue(":lastlog", $timelimit);
$sth->bindValue(":lastusergetrights", $timelimit);
$sth->execute();
$row = $sth->fetchAll(PDO::FETCH_ASSOC);

foreach ($row as $key => $user) {
	$row[$key]["rights"] = explode("|", $row[$key]["rights"]);
	$row[$key]["rights"] = array_diff($row[$key]["rights"], $C["right-whitelist"]);
	$row[$key]["rights"] = array_values($row[$key]["rights"]);
	if (count($row[$key]["rights"]) == 0) {
		unset($row[$key]);
		continue;
	}
	$row[$key]["time"] = 0;
	if ($row[$key]["lastedit"] !== "0000-00-00 00:00:00") {
		$row[$key]["time"] = max($row[$key]["time"], strtotime($row[$key]["lastedit"]));
	}
	if ($row[$key]["lastlog"] !== "0000-00-00 00:00:00") {
		$row[$key]["time"] = max($row[$key]["time"], strtotime($row[$key]["lastlog"]));
	}
	if ($row[$key]["lastusergetrights"] !== "0000-00-00 00:00:00") {
		$row[$key]["time"] = max($row[$key]["time"], strtotime($row[$key]["lastusergetrights"]));
	}
}
function cmp($a, $b) {
    if ($a["time"] == $b["time"]) {
        return 0;
    }
    return ($a["time"] < $b["time"]) ? -1 : 1;
}
usort($row, "cmp");

echo "共有".count($row)."筆<br>";

$count = 1;
?>
<table>
<tr>
	<th>#</th>
	<th>user</th>
	<th>user last edit</th>
	<th>user last log</th>
	<th>lastusergetrights</th>
	<th>user rights</th>
</tr>
<?php
foreach ($row as $user) {
	?><tr>
		<td><?php echo ($count++); ?></td>
		<td><a href="https://zh.wikipedia.org/wiki/User:<?=$user["name"]?>" target="_blank"><?=$user["name"]?></a></td>
		<td><a href="https://zh.wikipedia.org/wiki/Special:用户贡献/<?=$user["name"]?>" target="_blank"><?=$user["lastedit"]?></a></td>
		<td><a href="https://zh.wikipedia.org/wiki/Special:日志/<?=$user["name"]?>" target="_blank"><?=$user["lastlog"]?></a></td>
		<td><a href="https://zh.wikipedia.org/wiki/Special:日志/rights?page=User:<?=$user["name"]?>" target="_blank"><?=$user["lastusergetrights"]?></a></td>
		<td><a href="https://zh.wikipedia.org/wiki/Special:用户权限/<?=$user["name"]?>" target="_blank"><?=implode("|",$user["rights"])?></a></td>
	</tr><?php
}
?>
</table>
</body>
</html>
