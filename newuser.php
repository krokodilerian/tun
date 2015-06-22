<?php



if ($argc!=6) {
	echo "Usage: $argv[0] slug name email contactinfo range-size\n";
	exit(3);
}

require_once(dirname(__FILE__)."/config.php");

$slug=pg_escape_string($argv[1]);
$name=pg_escape_string($argv[2]);
$email=pg_escape_string($argv[3]);
$contact=pg_escape_string($argv[4]);
$range=(int) $argv[5];


pg_query("BEGIN");
try {

	/* really stupid, but i need to make sure there are no gaps */
	$res = pq_e("SELECT max(id)+1 AS m FROM users");
	$row = pg_fetch_object($res);
	$id = $row->m;

	pq_e("INSERT INTO users (id, slug, name, email, contactinfo) VALUES ($id, '$slug', '$name', '$email', '$contact')");

	for ($i=1; $i < $maxpfx; $i++) {
		$hh = sprintf("%x", $i);
		$res = pq_e("SELECT count(*) AS cnt FROM assigned_nets WHERE range && '$prefix:$hh::/$range'");
		$row = pg_fetch_object($res);
		if ($row->cnt == 0) break;
	}

	if ($i==$maxpfx) throw new Exception("out of networks");
	
	echo "Assigning $prefix:$hh::/$range\n";

	pq_e("INSERT INTO assigned_nets (userid, range) VALUES ($id, '$prefix:$hh::/$range')");

} catch (Exception $e)  {
	pg_query("ROLLBACK");
}
pg_query("COMMIT");
exit();
$res = pg_query("select u.id as userid, u.slug, n.range, l.slug as linkslug, l.linkinfo, l.num as linknum, lt.name, p.name as peername, p.localip4 as peerip, p.id as peerid from users u, assigned_nets n, links l, linktypes lt, peers p where u.id=$uid and n.userid=u.id and l.userid=u.id and lt.id=l.linktype and p.id=l.peer");

$bgp=false;
if (pg_num_rows($res)>1) $bgp=true;

while ($row = pg_fetch_object($res)) {
	#var_dump($row);

	$usr_ifname=$row->peername."-ipv6-".$row->linkslug;

	$srv_ifname="i6-".$row->slug."-".$row->linkslug;

	echo "\n\n";

	echo "auto $usr_ifname\n";
	echo "iface $usr_ifname inet6 v4tunnel\n";
	echo "\tmode ipip\n";
	echo "\tttl 225\n";
	echo "\taddress $linknetpfx$row->peerid:$row->userid:$row->linknum:2\n";
	echo "\tnetmask 120\n";
	echo "\tlocal $row->linkinfo\n";
	echo "\tendpoint $row->peerip\n";
	if (!$bgp)
		echo "\tgateway $linknetpfx$row->peerid:$row->userid:$row->linknum:1\n";

	echo "\n\n";

	echo "auto $srv_ifname\n";
	echo "iface $srv_ifname inet6 v4tunnel\n";
	echo "\tmode ipip\n";
	echo "\tttl 225\n";
	echo "\taddress $linknetpfx$row->peerid:$row->userid:$row->linknum:1\n";
	echo "\tnetmask 120\n";
	echo "\tlocal $row->peerip\n";
	echo "\tendpoint $row->linkinfo\n";
	if (!$bgp)
		echo "\tup ip r add $row->range via $linknetpfx$row->peerid:$row->userid:$row->linknum:2\n";

}
