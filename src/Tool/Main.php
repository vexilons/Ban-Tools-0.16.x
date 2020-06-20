<?php

namespace Tool;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\utils\TextFormat as T;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class Main extends PluginBase implements Listener{
	
	public $freeze = array();
	public $chat = array();
	
	public function onEnable(){
	$this->getServer()->getPluginManager()->registerEvents($this, $this);
	$this->getLogger()->notice("§b@BEcraft_MCPE");
	@mkdir($this->getDataFolder());
	$config = new Config($this->getDataFolder()."Config.yml", Config::YAML, [
"Twitter" => "GreenNetwork_",
"Left-Message" => "§7[§c-§7]§c {player}",
"Join-Message" => "§7[§a+§7]§a {player}",
"Block-Long-Damage" => false,
"Long-Distance" => 6,
"Alert-long-distance" => true,
]);
	$this->c = $config;
	$this->c->reload();
	$this->c->save();
	}
	
	public function onQuit(PlayerQuitEvent $e){
		if(in_array($e->getPlayer()->getName(), $this->freeze)){
			unset($this->freeze[$e->getPlayer()->getName()]);
			$config = new Config($this->getDataFolder().$e->getPlayer()->getName().".yml", Config::YAML);
			$reason = "You left the game while you was freezed";
            $config->set("Datos", array($e->getPlayer()->getName(), $e->getPlayer()->getClientId(), $e->getPlayer()->getAddress(), $reason));
            $config->save();
			$this->getServer()->getNameBans()->addBan($e->getPlayer()->getName(), $reason, null, null);
			$this->getServer()->getIPBans()->addBan($e->getPlayer()->getAddress(), $reason, null, null);
			$this->getServer()->getNetwork()->blockAddress($e->getPlayer()->getAddress(), -1);
			}
			if(in_array($e->getPlayer()->getName(), $this->chat)){
				unset($this->chat[$e->getPlayer()->getName()]);
				}
		}
		
	public function onBreak(BlockBreakEvent $e){
	$p = $e->getPlayer();
	if(in_array($p->getName(), $this->freeze)){
		$e->setCancelled(true);
		}
	}
		
	public function onPlace(BlockPlaceEvent $e){
	$p = $e->getPlayer();
	if(in_array($p->getName(), $this->freeze)){
		$e->setCancelled(true);
		}
	}
	
	public function onMove(PlayerMoveEvent $e){
		$p = $e->getPlayer();
		if(in_array($p->getName(), $this->freeze)){
			$to = clone $e->getFrom();
			$to->yaw = $e->getTo()->yaw;
			$to->pitch = $e->getTo()->pitch;
			$e->setTo($to);
			$p->sendPopup("§cYou cant move!");
			}
		}
		
	public function onChat(PlayerChatEvent $event){
		$player = $event->getPlayer();
		if(in_array($player->getName(), $this->chat)){
			foreach($this->getServer()->getOnlinePlayers() as $players){
				if(in_array($players->getName(), $this->chat)){
					$players->sendMessage("§7[§6OP§7-§bCHAT§7] §e".$player->getName()." §7|| §a".$event->getMessage());
					$event->setCancelled(true);
					}
				}
			}
		}
		
	public function onDamage(EntityDamageEvent $e){
    if($e instanceof EntityDamageByEntityEvent){
    if($e->getEntity() instanceof Player and $e->getDamager() instanceof Player){
    $entity = $e->getEntity();
    $damager = $e->getDamager();
    if($entity->distance($damager) >= $this->c->get("Long-Distance")){
    	if($this->c->get("Block-long-damage", true)){
    	$e->setCancelled(true);
    	}
    foreach($this->getServer()->getOnlinePlayers() as $players){
    if($players->isOp()){
    	if($this->c->get("Alert-long-distance", true)){
    $players->sendPopup("§cWarning: §a".$damager->getName()."§7[§a".$entity->distance($damager)."§7]");
    }
    }
    }
    }
    if((in_array($entity->getName(), $this->freeze)) and (!in_array($damager->getName(), $this->freeze))){
    	$damager->sendMessage("§cWarning: §7You cant hit this player!");
    $e->setCancelled(true);
    }
    	if((!in_array($entity->getName(), $this->freeze)) and (in_array($damager->getName(), $this->freeze))){
    $damager->sendMessage("§cWarning: §7You cant hit this player!");
    $e->setCancelled(true);
    }
    
    }
    }
    }
    
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
	switch($cmd){
	case "invisible":
	if($sender->isOp()){
		if($sender instanceof Player){
			$cast = $this->c->get("Left-Message");
			$cast = str_replace("{player}", $sender->getName(), $cast);
			$this->getServer()->broadcastMessage($cast);
			$sender->sendMessage("§eSpy§6Mode §aEnabled");
			foreach($this->getServer()->getOnlinePlayers() as $players){
				$players->hidePlayer($sender);
				$sender->setDisplayName("");
				//$sender->setNameTag("");
				$sender->despawnFromAll();
				$sender->setAllowFlight(true);
				$sender->setFlying(true);
				}
			}else{$sender->sendMessage("§cRun on game...");}
	}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
	return true;
	break;
	
	case "visible":
	if($sender->isOp()){
		if($sender instanceof Player){
			$cast = $this->c->get("Join-Message");
			$cast = str_replace("{player}", $sender->getName(), $cast);
			$this->getServer()->broadcastMessage($cast);
			$sender->sendMessage("§eSpy§6Mode §cDisabled");
			foreach($this->getServer()->getOnlinePlayers() as $players){
				$players->showPlayer($sender);
				$sender->spawnToAll();
				//$sender->setNameTag("§a".$sender->getName());
				$sender->setDisplayName($sender->getName());
				$sender->setAllowFlight(false);
				$sender->setFlying(false);
				}
			}else{$sender->sendMessage("§cRun on game...");}
	}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
	return true;
	break;
	
	case "eb":
	if($sender->isOp()){
		if(isset($args[0])){
			$p = array_shift($args);
		$player = $sender->getServer()->getPlayer($p);
		//if(isset($args[1])){
			$reason = null;
			for($i = 0; $i < count($args); $i++){
				$reason .= $args[$i];
				$reason .= " ";
				}
	if($player instanceof Player){
@mkdir($this->getDataFolder());
$config = new Config($this->getDataFolder().$player->getName().".yml", Config::YAML);
$ip = $player->getAddress();
$id = $player->getClientId();
$config->set("Datos", array($player->getName(), $player->getClientId(), $player->getAddress(), $reason));
$config->save();
		$sender->getServer()->getNameBans()->addBan($player->getName(), $reason, null, $sender->getName());
		if($this->getServer()->getName() === "Genisys"){
		$sender->getServer()->getCIDBans()->addBan($player->getClientId(), $reason, null, $sender->getName());
		}
		$sender->getServer()->getIPBans()->addBan($player->getAddress(), $reason, null, $sender->getName());
		$sender->getServer()->getNetwork()->blockAddress($player->getAddress(), -1);
		$this->getServer()->broadcastMessage("§a".$player->getName()." §7has been banned, reason: §6".$reason);
	    $player->kick("§7[§ax§7]§cYou have been banned§7[§ax§7] \n§6Banned by: §e{$sender->getName()}\n§6Reason: §e{$reason}\n§7If you think this ban is incorrect or\nyou have any question please contact us\nat §b@{$this->c->get("Twitter")} §7thanks for play!", false);
		}else{$sender->sendMessage("§cNot player found...");}
		//}else{$sender->sendMessage("§cuse: /eb <player> <reason>");}
		}else{$sender->sendMessage("§cuse: /eb <player> <reason>");}
	}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
	return true;
	break;
	
	case "ep":
	if($sender->isOp()){
		if(isset($args[0])){
		$player = $args[0];
		if(file_exists($this->getDataFolder().$player.".yml")){
			$config = new Config($this->getDataFolder().$player.".yml", Config::YAML);
			$datos = $config->get("Datos");
			/*pardon name*/
			$sender->getServer()->getNameBans()->remove($datos[0]);
			/*pardon ip*/
			if($this->getServer()->getName() === "Genisys"){
			$sender->getServer()->getNetwork()->unblockAddress($datos[2]);
			}
			$sender->getServer()->getIPBans()->remove($datos[2]);
			/*pardon cid*/
			if($this->getServer()->getName() === "Genisys"){
			$sender->getServer()->getCIDBans()->remove($datos[1]);
			}
			//remove file
			@unlink($this->getDataFolder().$player.".yml");
			$sender->sendMessage("§ePardon: §a".$player."\n§aCompleted!");
			}else{$sender->sendMessage("§cSorry this player didnt been banned by this plugin,\n§cuse default command...");}
			}else{$sender->sendMessage("§cuse: /ep <player>");}
		}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
		return true;
		break;
	
	case "info":
	if($sender->isOp()){
		if(isset($args[0])){
			$player = $sender->getServer()->getPlayer($args[0]);
			if($player instanceof Player){
				$health = $player->getHealth();
				if($player->getGameMode() == 0){
					$game = "Survival";
					}else if($player->getGamemode() == 1){
						$game = "Creative";
						}else if($player->getGamemode() == 2){
							$game = "Adventure";
							}else if($player->getGamemode() == 3){
								$game = "Spectator";
								}
								if($player->isOp()){
									$op = "true";
									}else{
										$op = "false";
										}
								$ip = $player->getAddress();
if($this->getServer()->getName() === "Genisys"){
	$sender->sendMessage(
								"§7Name: §a".$player->getName()."\n".
								"§7Health: §a".$health."\n".
								"§7Gamemode: §a".$game."\n".
								"§7OP: §a".$op."\n".
								"§7Address: §a".$ip."\n".
								"§7ClientID: §a".$player->getClientId()
);
	}else{
		$sender->sendMessage(
								"§7Name: §a".$player->getName()."\n".
								"§7Health: §a".$health."\n".
								"§7Gamemode: §a".$game."\n".
								"§7OP: §a".$op."\n".
								"§7Address: §a".$ip
);
		}
				}else{$sender->sendMessage("§cNo player found");}
			}else{$sender->sendMessage("§cuse: /info <player>");}
		}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
		return true;
		break;
		
	case "fre":
	if($sender->isOp()){
		if(isset($args[0])){
			$player = $sender->getServer()->getPlayer($args[0]);
			if($player instanceof Player){
				if(!in_array($player->getName(), $this->freeze)){
					$sender->sendMessage("§e".$player->getName()." §ahas been freezed!");
					$player->sendMessage("§cYou have been freezed, please dont log out!");
					$this->freeze[$player->getName()] = $player->getName();
					}else{
						$sender->sendMessage("§e".$player->getName()." §ahas been unfreezed");
						$player->sendMessage("§aYou can move right now");
						unset($this->freeze[$player->getName()]);
						}
				}else{$sender->sendMessage("§cNo player found");}
			}else{$sender->sendMessage("§cuse /fre [player]");}
		}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
		return true;
		break;
		
	case "co":
	if($sender->isOp()){
		if($sender instanceof Player){
		if(!in_array($sender->getName(), $this->chat)){
			$sender->sendMessage("§aYou joined to OP-CHAT!");
			$this->chat[$sender->getName()] = $sender->getName();
			foreach($this->getServer()->getOnlinePlayers() as $players){
				if(in_array($players->getName(), $this->chat)){
					$players->sendMessage("§a".$sender->getName()." joined to the chat!");
					}
				}
			}else{
				foreach($this->getServer()->getOnlinePlayers() as $players){
				if(in_array($players->getName(), $this->chat)){
					$players->sendMessage("§c".$sender->getName()." left to the chat!");
					}
				}
				$sender->sendMessage("§cYou left from OP-CHAT!");
				unset($this->chat[$sender->getName()]);
				}
				}else{$sender->sendMessage("§cRun only in game!");}
		}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
		return true;
		break;
	
	case "tools":
	if($sender->isOp()){
		$sender->sendMessage("§7-=]§6Ban§eTools§7[=-\n§6/visible §7[§aMake you visible to other players!§7]\n§6/invisible §7[§aBe like a ghost!§7]\n§6/eb [player] [reason] §7[§aBan any player from this server!§7]\n§6/ep [player] §7[§aPardon any player which is banned!§7]\n§6/co §7[§aJoin and Left from OP-CHAT!§7]\n§6/fre §7[§aFreeze and Unfreeze any player!§7]\n§6/info [player] §7[§aCheck any player information§7]\n§6/tools §7[§aCheck all commands§7]\n§6/bancheck <name> §7[§aCheck banned players information§7]\n§eAuthor: §b@BEcraft_MCPE");
		}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
		return true;
		break;
	
	case "bancheck":
	if($sender->isOp()){
		if(isset($args[0])){
		$banned = $args[0];
		if(file_exists($this->getDataFolder().$banned.".yml")){
			$config = new Config($this->getDataFolder().$banned.".yml", Config::YAML);
			$datos = $config->get("Datos");
			$sender->sendMessage("§7-=] §e".$banned."'s §6ban info §7[=-\n§7Address: §6".$datos[2]."\n§7Client ID: §6".$datos[1]."\n§7Reason: §c".$datos[3]);
			}else{$sender->sendMessage("§cthere is not any player banned with name §a".$banned."§ccheck at next time!");}
			}else{$sender->sendMessage("§cuse /bancheck <name>");}
		}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
	return true;
	break;
	
	}
	}
  
    public function onBanned(PlayerPreLoginEvent $event){
    $player = $event->getPlayer();
    if($player->isBanned()){
    if(file_exists($this->getDataFolder().$player->getName().".yml")){
    $config = new Config($this->getDataFolder().$player->getName().".yml", Config::YAML);
    $datos = $config->get("Datos");
    $event->setKickMessage("§cSorry §a".$player->getName()."§c You are banned from this server...\n§eName: §7".$datos[0]."\n§eReason: §7".$datos[3]);
    $event->setCancelled(true);
    }else{
    $event->setKickMessage("§cSorry §a".$player->getName()."§c You are banned from this server...");
    $event->setCancelled(true);
    }
    }
    }
    
	}