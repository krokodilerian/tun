<?php

require_once("config.php");

$res = pg_query("
select
	u.id as userid,
	u.slug,
	n.range,
	l.slug as linkslug,
	l.linkinfo,
	l.num as linknum,
	lt.name,
	p.name as peername,
	p.localip4 as peerip,
	p.id as peerid,
	coalesce(l.localid,'u')  as localid 
from users u, assigned_nets n, links l, linktypes lt, peers p 
where n.userid=u.id and l.userid=u.id and lt.id=l.linktype and p.id=l.peer");

$rev="";
$fwd="";

while ($row = pg_fetch_object($res)) {
	#var_dump($row);

	$usr_ifname=$row->peername."-ipv6-".$row->linkslug;

	$srv_ifname="i6-".$row->slug."-".$row->linkslug;


	$userip="$linknetpfx$row->peerid:$row->userid:$row->linknum:2";
	$srvip="$linknetpfx$row->peerid:$row->userid:$row->linknum:1";

	$usern=iptorevdns($userip);
	$srvn=iptorevdns($srvip);

	$userh="$row->peername.$row->linkslug.$row->slug.ptp.t6.initlab.org";
	$srvh="$row->localid.$row->linkslug.$row->slug.ptp.t6.initlab.org";

	$rev .= "$srvn.\t\tIN\tPTR\t$srvh.\n";
	$rev .= "$usern.\t\tIN\tPTR\t$userh.\n";

	$fwd .= "$srvh.\t\tIN\tAAAA\t$srvip\n";
	$fwd .= "$userh.\t\tIN\tAAAA\t$userip\n";

}

$res = pg_query("
select r.addr, r.name from revdns r, assigned_nets a where a.userid=r.userid and r.addr<<a.range 
union 
select r.addr, r.name from revdns r where userid=0;");

while ($row = pg_fetch_object($res)) {
	$rev .= iptorevdns($row->addr).".\t\tIN\tPTR\t".$row->name.".\n";
}

$res = pg_query("
select ip6r_split(n.range, 64) as addr, r.server from assigned_nets n, revdns_delegate r where n.id=r.netid;
");

while ($row = pg_fetch_object($res)) {
	$rev .= iptorevdns64($row->addr).".\t\tIN\tNS\t".$row->server.".\n";
}

echo $rev; exit();
$f = fopen($revfilename, "w+");
fwrite($f, $rev);
fclose($f);

$f = fopen($fwdfilename, "w+");
fwrite($f, $fwd);
fclose($f);
