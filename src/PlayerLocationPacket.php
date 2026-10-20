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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\PlayerLocationType;

class PlayerLocationPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_LOCATION_PACKET;

	private PlayerLocationType $type;
	private int $actorUniqueId;
	private ?Vector3 $position = null;

	/**
	 * @generate-create-func
	 */
	private static function create(PlayerLocationType $type, int $actorUniqueId, ?Vector3 $position) : self{
		$result = new self;
		$result->type = $type;
		$result->actorUniqueId = $actorUniqueId;
		$result->position = $position;
		return $result;
	}

	public static function createCoordinates(int $actorUniqueId, Vector3 $position) : self{
		return self::create(PlayerLocationType::PLAYER_LOCATION_COORDINATES, $actorUniqueId, $position);
	}

	public static function createHide(int $actorUniqueId) : self{
		return self::create(PlayerLocationType::PLAYER_LOCATION_HIDE, $actorUniqueId, null);
	}

	public function getType() : PlayerLocationType{ return $this->type; }

	public function getActorUniqueId() : int{ return $this->actorUniqueId; }

	public function getPosition() : ?Vector3{ return $this->position; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
			$this->type = PlayerLocationType::fromPacket(VarInt::readUnsignedInt($in));
			$innerType = VarInt::readSignedInt($in);
			if($innerType !== $this->type->value){
				throw new PacketDecodeException("Unexpected inner type, expected {$this->type->value}, got $innerType");
			}
		}else{
			$this->type = PlayerLocationType::fromPacket(LE::readUnsignedInt($in));
			$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
		}

		if($this->type === PlayerLocationType::PLAYER_LOCATION_COORDINATES){
			$this->position = CommonTypes::getVector3($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
			VarInt::writeUnsignedInt($out, $this->type->value);
			VarInt::writeSignedInt($out, $this->type->value); //inner type
		}else{
			LE::writeUnsignedInt($out, $this->type->value);
			CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
		}

		if($this->type === PlayerLocationType::PLAYER_LOCATION_COORDINATES){
			if($this->position === null){ // this should never be the case
				throw new \LogicException("PlayerLocationPacket with type PLAYER_LOCATION_COORDINATES require a position to be provided");
			}
			CommonTypes::putVector3($out, $this->position);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerLocation($this);
	}
}
