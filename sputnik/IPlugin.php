<?php

interface IPlugin {
	//public pluginId;
	public function SetBaseObject(&$base);
	public function OnLoad();
}

?>
