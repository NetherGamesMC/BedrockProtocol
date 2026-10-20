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

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\OverrideUpdateType;

class PlayerUpdateEntityOverridesPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_UPDATE_ENTITY_OVERRIDES_PACKET;

	private int $actorUniqueId;
	private int $propertyIndex;
	private OverrideUpdateType $updateType;
	private ?int $intOverrideValue = null;
	private ?float $floatOverrideValue = null;

	/**
	 * @generate-create-func
	 */
	private static function create(int $actorUniqueId, int $propertyIndex, OverrideUpdateType $updateType, ?int $intOverrideValue, ?float $floatOverrideValue) : self{
		$result = new self;
		$result->actorUniqueId = $actorUniqueId;
		$result->propertyIndex = $propertyIndex;
		$result->updateType = $updateType;
		$result->intOverrideValue = $intOverrideValue;
		$result->floatOverrideValue = $floatOverrideValue;
		return $result;
	}

	public static function createIntOverride(int $actorUniqueId, int $propertyIndex, int $value) : self{
		return self::create($actorUniqueId, $propertyIndex, OverrideUpdateType::SET_INT_OVERRIDE, $value, null);
	}

	public static function createFloatOverride(int $actorUniqueId, int $propertyIndex, float $value) : self{
		return self::create($actorUniqueId, $propertyIndex, OverrideUpdateType::SET_FLOAT_OVERRIDE, null, $value);
	}

	public static function createClearOverrides(int $actorUniqueId, int $propertyIndex) : self{
		return self::create($actorUniqueId, $propertyIndex, OverrideUpdateType::CLEAR_OVERRIDES, null, null);
	}

	public static function createRemoveOverride(int $actorUniqueId, int $propertyIndex) : self{
		return self::create($actorUniqueId, $propertyIndex, OverrideUpdateType::REMOVE_OVERRIDE, null, null);
	}

	public function getActorUniqueId() : int{ return $this->actorUniqueId; }

	/**
	 * Before 1.26.40, this field was an actor runtime ID. The same value is written in both cases.
	 * @deprecated Use {@link PlayerUpdateEntityOverridesPacket::getActorUniqueId()}
	 */
	public function getActorRuntimeId() : int{ return $this->actorUniqueId; }

	public function getPropertyIndex() : int{ return $this->propertyIndex; }

	public function getUpdateType() : OverrideUpdateType{ return $this->updateType; }

	public function getIntOverrideValue() : ?int{ return $this->intOverrideValue; }

	public function getFloatOverrideValue() : ?float{ return $this->floatOverrideValue; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
			$this->propertyIndex = VarInt::readUnsignedInt($in);
			$this->updateType = OverrideUpdateType::fromOrdinal(VarInt::readUnsignedInt($in));
			$innerType = OverrideUpdateType::fromPacket(CommonTypes::getString($in));
			if($innerType !== $this->updateType){
				throw new PacketDecodeException("Unexpected inner type, expected " . $this->updateType->value . ", got " . $innerType->value);
			}
		}else{
			//before 1.26.40, this field was an actor runtime ID
			$this->actorUniqueId = CommonTypes::getActorRuntimeId($in);
			$this->propertyIndex = VarInt::readUnsignedInt($in);
			//the legacy int values are the same as the ordinals
			$this->updateType = OverrideUpdateType::fromOrdinal(Byte::readUnsigned($in));
		}
		if($this->updateType === OverrideUpdateType::SET_INT_OVERRIDE){
			$this->intOverrideValue = LE::readSignedInt($in);
		}elseif($this->updateType === OverrideUpdateType::SET_FLOAT_OVERRIDE){
			$this->floatOverrideValue = LE::readFloat($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
			VarInt::writeUnsignedInt($out, $this->propertyIndex);
			VarInt::writeUnsignedInt($out, $this->updateType->toOrdinal());
			CommonTypes::putString($out, $this->updateType->value);
		}else{
			//before 1.26.40, this field was an actor runtime ID
			CommonTypes::putActorRuntimeId($out, $this->actorUniqueId);
			VarInt::writeUnsignedInt($out, $this->propertyIndex);
			//the legacy int values are the same as the ordinals
			Byte::writeUnsigned($out, $this->updateType->toOrdinal());
		}
		if($this->updateType === OverrideUpdateType::SET_INT_OVERRIDE){
			if($this->intOverrideValue === null){ // this should never be the case
				throw new \LogicException("PlayerUpdateEntityOverridesPacket with type SET_INT_OVERRIDE requires intOverrideValue to be provided");
			}
			LE::writeSignedInt($out, $this->intOverrideValue);
		}elseif($this->updateType === OverrideUpdateType::SET_FLOAT_OVERRIDE){
			if($this->floatOverrideValue === null){ // this should never be the case
				throw new \LogicException("PlayerUpdateEntityOverridesPacket with type SET_FLOAT_OVERRIDE requires floatOverrideValue to be provided");
			}
			LE::writeFloat($out, $this->floatOverrideValue);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerUpdateEntityOverrides($this);
	}
}
