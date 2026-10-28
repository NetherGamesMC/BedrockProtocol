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
use pocketmine\network\mcpe\protocol\types\PlayerPartyInfo;

class PartyChangedPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::PARTY_CHANGED_PACKET;

	private ?PlayerPartyInfo $partyInfo;

	/**
	 * @generate-create-func
	 */
	public static function create(?PlayerPartyInfo $partyInfo) : self{
		$result = new self;
		$result->partyInfo = $partyInfo;
		return $result;
	}

	/** null if the player is not in a party. Always set when decoding protocols older than 1.26.60. */
	public function getPartyInfo() : ?PlayerPartyInfo{ return $this->partyInfo; }

	/** Kept for BC. Empty if the player is not in a party. */
	public function getPartyId() : string{ return $this->partyInfo?->getPartyId() ?? ""; }

	/** Kept for BC */
	public function isPartyLeader() : bool{ return $this->partyInfo?->isPartyLeader() ?? false; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			$this->partyInfo = CommonTypes::readOptional($in, PlayerPartyInfo::read(...));
		}else{
			$partyId = CommonTypes::getString($in);
			$partyLeader = false;
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
				$partyLeader = CommonTypes::getBool($in);
			}
			$this->partyInfo = new PlayerPartyInfo($partyId, $partyLeader);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			CommonTypes::writeOptional($out, $this->partyInfo, static fn(ByteBufferWriter $out, PlayerPartyInfo $info) => $info->write($out));
		}else{
			CommonTypes::putString($out, $this->getPartyId());
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
				CommonTypes::putBool($out, $this->isPartyLeader());
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePartyChanged($this);
	}
}
