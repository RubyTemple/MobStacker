<?php
declare(strict_types=1);

namespace MobStacker;

use pocketmine\Player;
use pocketmine\entity\Living;
use pocketmine\event\Listener;
use pocketmine\event\entity\{EntityDamageEvent, EntitySpawnEvent, EntityMotionEvent};
use slapper\entities\SlapperEntity;
use slapper\entities\SlapperHuman;

class StackEvent implements Listener{
    /** @var Core  */
    private $plugin;
    public function __construct(Loader $plugin){
        $this->plugin = $plugin;
        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
    }
    public function onDamage(EntityDamageEvent $e): void{
        $entity = $e->getEntity();
        if($e->getFinalDamage() >= $entity->getHealth()){
            if($entity instanceof Living and StackFactory::isStack($entity)){
                $entity->setLastDamageCause($e);
                if(StackFactory::removeFromStack($entity)){
                    $e->setCancelled(true);
                    $entity->setHealth($entity->getMaxHealth());
                }
                StackFactory::recalculateStackName($entity);
            }
        }
    }

    public function onMotion(EntityMotionEvent  $e): void{
        $entity = $e->getEntity();
        if($entity instanceof Living && !$entity instanceof Player){
            $e->setCancelled(true);
        }
    }

    public function onSpawn(EntitySpawnEvent $e): void{
        $entity = $e->getEntity();
        if (!($entity instanceof SlapperEntity) && !($entity instanceof SlapperHuman)){
            if(!($entity instanceof Living) && !($entity instanceof Player)) return;
            StackFactory::addToClosestStack($entity, 16);
        }
    }
}
