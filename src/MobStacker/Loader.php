<?php

namespace MobStacker;

use pocketmine\plugin\PluginBase;

class Loader extends PluginBase {

public function onLoad() : void{
    $this->getLogger()->info("Plugin is being enabled. Please wait..");
}
public function onEnable() : void{
    $this->getServer()->getPluginManager()->registerEvents(new StackEvent($this), $this);
    $this->getLogger()->info("Plugin has been enabled succesfully, by TheWalker0.");
}
public function onDisable() : void{
    $this->getLogger()->info("Plugin has been disabled made by TheWalker0");
}
}
