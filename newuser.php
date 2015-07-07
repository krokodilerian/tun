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
		$res = pq_e("SELECT count(*) AS cnt FROM assigned_nets WHERE range && ( '$prefix:$hh::'::ip6 &  ((pow(2::numeric,(128)::numeric)::numeric - (pow(2::numeric,(128-$range)::numeric)::numeric))::ip6 )||'/$range')::ip6r");
		$row = pg_fetch_object($res);
		if ($row->cnt == 0) break;
	}

	if ($i==$maxpfx) throw new Exception("out of networks");
	

	pq_e("INSERT INTO assigned_nets (userid, range) VALUES ($id, '$prefix:$hh::/$range')");
	$res = pq_e("select currval('assigned_nets_id_seq'::regclass) as lastid");
	$row = pg_fetch_object($res);
	echo "Assigning id $row->lastid prefix $prefix:$hh::/$range\n";

} catch (Exception $e)  {
	pg_query("ROLLBACK");
}
pg_query("COMMIT");
