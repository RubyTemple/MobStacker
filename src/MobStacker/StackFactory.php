<?php

namespace MobStacker;

use pocketmine\entity\Living;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;

class StackFactory {

	public const TAG_STACK_DATA = "StackData";

	public static function isStack($entity): bool{
		if(!$entity instanceof Player){
			return $entity instanceof Living and (!$entity instanceof ItemEntity) and $entity->namedtag->hasTag(self::TAG_STACK_DATA, CompoundTag::class);
		}

		return false;
	}

	public static function getStackSize(Living $entity){
		if(self::isStack($entity)){
			return $entity->namedtag->getCompoundTag(self::TAG_STACK_DATA)->getInt("Amount", 1);
		}

		return 1;
	}

	public static function increaseStackSize(Living $entity, $amount = 1): bool{
		if(self::isStack($entity)){
			$entity->namedtag->getCompoundTag(self::TAG_STACK_DATA)->setInt("Amount", self::getStackSize($entity) + $amount);

			return true;
		}

		return false;
	}

	public static function decreaseStackSize(Living $entity, $amount = 1): bool{
		if(self::isStack($entity)){
			$entity->namedtag->getCompoundTag(self::TAG_STACK_DATA)->setInt("Amount", self::getStackSize($entity) - $amount);

			return true;
		}

		return false;
	}

	public static function createStack(Living $entity, $count = 1): bool{
		if(!$entity instanceof Player){
			$nbt = new CompoundTag("StackData", [
				"Amount" => new IntTag("Amount", $count),
			]);

			$entity->namedtag->setTag($nbt);
		}

		return true;
	}

	public static function addToStack(Living $stack, Living $entity): bool{
		if(!$entity instanceof Player){
			if(is_a($entity, get_class($stack)) and $stack !== $entity){
				if(self::increaseStackSize($stack, self::getStackSize($entity))){
					$entity->flagForDespawn();

					return true;
				}
			}

			return false;
		}

		return true;
	}

	public static function removeFromStack(Living $entity): bool{
		//echo "buonsalve";
		if(!$entity instanceof Player){
			assert(self::isStack($entity));
			if(self::decreaseStackSize($entity)){
				if(self::getStackSize($entity) <= 0) return false;
				$level = $entity->getLevel();
				//$count = self::getStackSize($entity);
				$pos = new Vector3($entity->x, $entity->y, $entity->z);
				$server = $level->getServer();
				$server->getPluginManager()->callEvent($ev = new EntityDeathEvent($entity, $entity->getDrops()));
				foreach($ev->getDrops() as $drops){
					$level->dropItem($pos, $drops);
					//echo "buondio";
				}
				return true;
			}

			return false;
		}

		return true;
	}

	public static function recalculateStackName(Living $entity): bool{
		if(!$entity instanceof Player){
			assert(self::isStack($entity));
			$count = self::getStackSize($entity);
			if($count < 0){
				$count = 0;
			}
			$entity->setNameTagVisible(true);
			$entity->setNameTag("§l§e{$count}x {$entity->getName()}");
		}

		return true;
	}

	public static function findNearbyStack(Living $entity, $range = 16){
		if(!$entity instanceof Player){
			$stack = null;
			$closest = $range;
			$bb = $entity->getBoundingBox();
			$bb = $bb->expandedCopy($range, $range, $range);
			if (!($entity->isFlaggedForDespawn() or $entity->isClosed())) {
				foreach($entity->getLevel()->getCollidingEntities($bb) as $e){
					if(is_a($e, get_class($entity)) and $stack !== $entity){
						$distance = $e->distance($entity);
						if($distance < $closest){
							if(!self::isStack($e) and self::isStack($stack)) continue;
							$closest = $distance;
							$stack = $e;
						}
					}
				}
				return $stack;
			}
		}
		return true;
	}

	public static function addToClosestStack(Living $entity, $range = 16): bool{
		if(!$entity instanceof Player){
			$stack = self::findNearbyStack($entity, $range);
			if(self::isStack($stack)){
				if(self::addToStack($stack, $entity)){
					self::recalculateStackName($stack);

					return true;
				}
			}else{
				if($stack instanceof Living && !$stack instanceof Player){
					self::createStack($stack);
					self::addToStack($stack, $entity);
					self::recalculateStackName($stack);

					return true;
				}
			}

			return false;
		}

		return true;
	}
}
