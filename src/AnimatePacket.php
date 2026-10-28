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
use pocketmine\network\mcpe\protocol\types\inventory\HandSlot;

class AnimatePacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::ANIMATE_PACKET;

	public const ACTION_SWING_ARM = 1;

	public const ACTION_STOP_SLEEP = 3;
	public const ACTION_CRITICAL_HIT = 4;
	public const ACTION_MAGICAL_CRITICAL_HIT = 5;
	public const ACTION_ROW_RIGHT = 128;
	public const ACTION_ROW_LEFT = 129;

	public int $action;
	public int $actorRuntimeId;
	public float $data = 0.0;
	public ?string $swingSource = null;
	public HandSlot $hand = HandSlot::MAINHAND;

	public static function create(int $actorRuntimeId, int $action, float $data = 0.0, ?string $swingSource = null, HandSlot $hand = HandSlot::MAINHAND) : self{
		$result = new self;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->action = $action;
		$result->data = $data;
		$result->swingSource = $swingSource;
		$result->hand = $hand;
		return $result;
	}

	public static function boatHack(int $actorRuntimeId, int $action, float $data) : self{
		if($action !== self::ACTION_ROW_LEFT && $action !== self::ACTION_ROW_RIGHT){
			throw new \InvalidArgumentException("Invalid actionId for boatHack: $action");
		}

		$result = self::create($actorRuntimeId, $action);
		$result->data = $data;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->action = $protocolId >= ProtocolInfo::PROTOCOL_1_21_130 ? Byte::readUnsigned($in) : VarInt::readSignedInt($in);
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_120 || ($this->action === self::ACTION_ROW_LEFT || $this->action === self::ACTION_ROW_RIGHT)){
			$this->data = LE::readFloat($in);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			$this->swingSource = CommonTypes::readOptional($in, CommonTypes::getString(...));
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			$this->hand = CommonTypes::readHandSlot($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			Byte::writeUnsigned($out, $this->action);
		}else{
			VarInt::writeSignedInt($out, $this->action);
		}
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_120 || ($this->action === self::ACTION_ROW_LEFT || $this->action === self::ACTION_ROW_RIGHT)){
			LE::writeFloat($out, $this->data);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			CommonTypes::writeOptional($out, $this->swingSource, CommonTypes::putString(...));
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			CommonTypes::writeHandSlot($out, $this->hand);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleAnimate($this);
	}
}
