<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\utils\Limits;

class SetSpawnPositionPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::SET_SPAWN_POSITION_PACKET;

	public const TYPE_PLAYER_SPAWN = 0;
	public const TYPE_WORLD_SPAWN = 1;

	public int $spawnType;
	public BlockPosition $spawnPosition;
	public int $dimension;
	/**
	 * Position of the respawn anchor or bed that this spawn position was set by.
	 * This may be different from the spawn position (e.g. the actual spawn position may be next to a bed, while this
	 * would be the position of the bed block itself).
	 */
	public BlockPosition $causingBlockPosition;

	public bool $spawnForced;

	/**
	 * @generate-create-func
	 */
	private static function create(int $spawnType, BlockPosition $spawnPosition, int $dimension, BlockPosition $causingBlockPosition, bool $spawnForced) : self{
		$result = new self;
		$result->spawnType = $spawnType;
		$result->spawnPosition = $spawnPosition;
		$result->dimension = $dimension;
		$result->causingBlockPosition = $causingBlockPosition;
		$result->spawnForced = $spawnForced;
		return $result;
	}

	public static function playerSpawn(BlockPosition $spawnPosition, int $dimension, BlockPosition $causingBlockPosition, bool $spawnForced = false) : self{
		return self::create(self::TYPE_PLAYER_SPAWN, $spawnPosition, $dimension, $causingBlockPosition, $spawnForced);
	}

	public static function worldSpawn(BlockPosition $spawnPosition, int $dimension, bool $spawnForced = false) : self{
		return self::create(self::TYPE_WORLD_SPAWN, $spawnPosition, $dimension, new BlockPosition(Limits::INT32_MIN, Limits::INT32_MIN, Limits::INT32_MIN), $spawnForced);
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->spawnType = $in->getVarInt();
		$this->spawnPosition = $in->getBlockPosition();
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_0){
			$this->dimension = $in->getVarInt();
			$this->causingBlockPosition = $in->getBlockPosition();
		}else{
			$this->spawnForced = $in->getBool();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putVarInt($this->spawnType);
		$out->putBlockPosition($this->spawnPosition);
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_0){
			$out->putVarInt($this->dimension);
			$out->putBlockPosition($this->causingBlockPosition);
		}else{
			$out->putBool($this->spawnForced);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSetSpawnPosition($this);
	}
}
