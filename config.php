<?php

global $dbc;

$linknetpfx="2001:67c:21bc:7fff:000";

$dbconn = pg_connect("host=localhost dbname=iptun user=vasil");

$fwdfilename="/etc/bind/gen-zones/ptp.tun.initlab.org";
$revfilename="/etc/bind/gen-zones/gen-c.b.1.2.c.7.6.0.1.0.0.2.ip6.arpa";


function iptorevdns ($ip) {
	$addr = inet_pton($ip);
	$unpack = unpack('H*hex', $addr);
	$hex = $unpack['hex'];
	$arpa = implode('.', array_reverse(str_split($hex))) . '.ip6.arpa';
	return $arpa;
}

function iptorevdns64 ($ip) {
	$addr = inet_pton(substr($ip, 0, strpos($ip, '/')));
	$unpack = unpack('H16hex', $addr);
	$hex = $unpack['hex'];
	$arpa = implode('.', array_reverse(str_split($hex))) . '.ip6.arpa';
	return $arpa;
}
