<module>
	<service><?=$GETCFG_SVC?></service>
	<device>
		<autobridge><?echo get("x", "/device/autobridge");?></autobridge>
		<layout><?echo get("x","/device/layout");?></layout>
		<router>
			<mode><?echo get("x", "/device/router/mode");?></mode>
		</router>
		<bridge>
			<mode><?echo get("x", "/device/bridge/mode");?></mode>
		</bridge>
	</device>
</module>
