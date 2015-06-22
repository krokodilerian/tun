<?php

require_once("config.php");

if ($argc!=2) {
	echo "Usage: $argv[0] userid\n";
	exit(3);
}

$uid=(int) $argv[1];

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
