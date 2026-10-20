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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

class MobArmorEquipmentPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET;

	public int $actorRuntimeId;

	//this intentionally doesn't use an array because we don't want any implicit dependencies on internal order
	public ItemStackWrapper $head;
	public ItemStackWrapper $chest;
	public ItemStackWrapper $legs;
	public ItemStackWrapper $feet;
	public ItemStackWrapper $body;

	/**
	 * @generate-create-func
	 */
	public static function create(int $actorRuntimeId, ItemStackWrapper $head, ItemStackWrapper $chest, ItemStackWrapper $legs, ItemStackWrapper $feet, ItemStackWrapper $body) : self{
		$result = new self;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->head = $head;
		$result->chest = $chest;
		$result->legs = $legs;
		$result->feet = $feet;
		$result->body = $body;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
			$this->head = CommonTypes::getNetworkItemStackDescriptor($in, $protocolId);
			$this->chest = CommonTypes::getNetworkItemStackDescriptor($in, $protocolId);
			$this->legs = CommonTypes::getNetworkItemStackDescriptor($in, $protocolId);
			$this->feet = CommonTypes::getNetworkItemStackDescriptor($in, $protocolId);
			$this->body = CommonTypes::getNetworkItemStackDescriptor($in, $protocolId);
		}else{
			$this->head = CommonTypes::getItemStackWrapper($in, $protocolId);
			$this->chest = CommonTypes::getItemStackWrapper($in, $protocolId);
			$this->legs = CommonTypes::getItemStackWrapper($in, $protocolId);
			$this->feet = CommonTypes::getItemStackWrapper($in, $protocolId);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
				$this->body = CommonTypes::getItemStackWrapper($in, $protocolId);
			}
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
			CommonTypes::putNetworkItemStackDescriptor($out, $protocolId, $this->head);
			CommonTypes::putNetworkItemStackDescriptor($out, $protocolId, $this->chest);
			CommonTypes::putNetworkItemStackDescriptor($out, $protocolId, $this->legs);
			CommonTypes::putNetworkItemStackDescriptor($out, $protocolId, $this->feet);
			CommonTypes::putNetworkItemStackDescriptor($out, $protocolId, $this->body);
		}else{
			CommonTypes::putItemStackWrapper($out, $protocolId, $this->head);
			CommonTypes::putItemStackWrapper($out, $protocolId, $this->chest);
			CommonTypes::putItemStackWrapper($out, $protocolId, $this->legs);
			CommonTypes::putItemStackWrapper($out, $protocolId, $this->feet);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
				CommonTypes::putItemStackWrapper($out, $protocolId, $this->body);
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleMobArmorEquipment($this);
	}
}
