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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\GatheringJoinInfo;

class TransferPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::TRANSFER_PACKET;

	public string $address;
	public int $port = 19132;
	public bool $reloadWorld = false;
	/** >= ProtocolInfo::PROTOCOL_1_26_40 */
	public ?GatheringJoinInfo $gatheringsConfig = null;

	/**
	 * @generate-create-func
	 */
	public static function create(string $address, int $port, bool $reloadWorld, ?GatheringJoinInfo $gatheringsConfig) : self{
		$result = new self;
		$result->address = $address;
		$result->port = $port;
		$result->reloadWorld = $reloadWorld;
		$result->gatheringsConfig = $gatheringsConfig;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->address = CommonTypes::getString($in);
		$this->port = LE::readUnsignedShort($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_30){
			$this->reloadWorld = CommonTypes::getBool($in);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->gatheringsConfig = CommonTypes::readOptional($in, fn(ByteBufferReader $in) => GatheringJoinInfo::read($in, $protocolId));
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->address);
		LE::writeUnsignedShort($out, $this->port);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_30){
			CommonTypes::putBool($out, $this->reloadWorld);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeOptional($out, $this->gatheringsConfig, fn(ByteBufferWriter $out, GatheringJoinInfo $info) => $info->write($out, $protocolId));
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleTransfer($this);
	}
}
