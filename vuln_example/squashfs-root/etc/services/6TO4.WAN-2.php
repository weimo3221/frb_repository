<?
include "/etc/services/6TO4/6to4svcs.php";
fwrite("w",$START,"#!/bin/sh\n");
fwrite("w", $STOP,"#!/bin/sh\n");
6to4setup("WAN-2");
?>
