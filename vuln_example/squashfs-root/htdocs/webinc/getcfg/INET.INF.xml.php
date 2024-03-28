<module>
	<service><?=$GETCFG_SVC?></service>
	<inet>
<?		echo dump(2, "/inet");
?>	</inet>
<?
	foreach("/inf")
	{
		echo '\t<inf>\n';
		echo dump(2, "");
		echo '\t</inf>\n';
	}
?>	<ACTIVATE>ignore</ACTIVATE>
</module>
