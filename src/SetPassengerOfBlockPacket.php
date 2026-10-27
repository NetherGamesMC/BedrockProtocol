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
use pocketmine\network\mcpe\protocol\types\PassengerOfBlockData;

final class SetPassengerOfBlockPacket extends DataPacket implements ClientboundPacket{

	public const NETWORK_ID = ProtocolInfo::SET_PASSENGER_OF_BLOCK_PACKET;

	private int $actorUniqueId;

	private ?PassengerOfBlockData $passengerData;

	/**
	 * @generate-create-func
	 */
	public static function create(int $actorUniqueId, ?PassengerOfBlockData $passengerData) : self{
		$result = new self;
		$result->actorUniqueId = $actorUniqueId;
		$result->passengerData = $passengerData;
		return $result;
	}

	public function getActorUniqueId() : int{
		return $this->actorUniqueId;
	}

	public function getPassengerData() : ?PassengerOfBlockData{
		return $this->passengerData;
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
		CommonTypes::writeOptional($out, $this->passengerData, static fn(ByteBufferWriter $out, PassengerOfBlockData $data) => $data->write($out));
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->passengerData = CommonTypes::readOptional($in, PassengerOfBlockData::read(...));
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSetPassengerOfBlock($this);
	}
}
