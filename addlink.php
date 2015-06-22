<?php



if ($argc!=7) {
	echo "Usage: $argv[0] userid linkslug mode linkinfo localpeer remotepeer\n";
	echo "Where:\n";
	echo "\tmode is one of ipip, gre, openvpn\n";
	echo "\tlinkslug is up to 3 chars\n";
	echo "\tlocalpeer is a name (marla, tyler)\n";
	echo "\tremote peer is the remote router name\n";
	exit(3);
}

require_once(dirname(__FILE__)."/config.php");

$uid = (int) $argv[1];
$slug=pg_escape_string($argv[2]);
$mode=pg_escape_string($argv[3]);
$remoteip=pg_escape_string($argv[4]);
$localpeer=pg_escape_string($argv[5]);
$remotepeer=pg_escape_string($argv[6]);


pg_query("BEGIN");
try {

	$res = pq_e("SELECT id FROM peers WHERE name='$localpeer'");
	if (pg_num_rows($res)!=1) throw new Exception("missing peer $localpeer");
	$row = pg_fetch_object($res);
	$peerid = $row->id;

	$res = pq_e("SELECT id FROM linktypes WHERE name='$mode'");
	if (pg_num_rows($res)!=1) throw new Exception("missing mode $mode");
	$row = pg_fetch_object($res);
	$modeid = $row->id;

	$res = pq_e("SELECT max(num)+1 as num FROM links WHERE userid=$uid");
	$row = pg_fetch_object($res);
	$num = $row->num;

	pq_e("INSERT INTO links (userid, slug, num, linktype, linkinfo, peer, localid) VALUES ($uid, '$slug', $num, $modeid, '$remoteip', $peerid, '$remotepeer')");

	$res = pq_e("select currval('links_id_seq'::regclass) as lastid");
	$row = pg_fetch_object($res);
	echo "Assigned id $row->lastid\n";

} catch (Exception $e)  {
	pg_query("ROLLBACK");
}
pg_query("COMMIT");
