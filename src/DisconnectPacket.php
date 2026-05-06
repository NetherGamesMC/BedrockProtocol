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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class DisconnectPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::DISCONNECT_PACKET;

	public int $reason; //TODO: add constants / enum
	public ?string $message;
	public ?string $filteredMessage;

	/**
	 * @generate-create-func
	 */
	public static function create(int $reason, ?string $message, ?string $filteredMessage) : self{
		$result = new self;
		$result->reason = $reason;
		$result->message = $message;
		$result->filteredMessage = $filteredMessage;
		return $result;
	}

	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_40){
			$this->reason = VarInt::readSignedInt($in);
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
			$type = VarInt::readUnsignedInt($in);
		}else{
			$type = CommonTypes::getBool($in) ? 1 : 0;
		}

		$this->message = $type === 0 ? CommonTypes::getString($in) : null;
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			$this->filteredMessage = $type === 0 ? CommonTypes::getString($in) : null;
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_40){
			VarInt::writeSignedInt($out, $this->reason);
		}

		$skipMessage = $this->message === null && $this->filteredMessage === null;
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
			VarInt::writeUnsignedInt($out, $skipMessage ? 1 : 0);
		}else{
			CommonTypes::putBool($out, $skipMessage);
		}

		if(!$skipMessage){
			CommonTypes::putString($out, $this->message ?? "");
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
				CommonTypes::putString($out, $this->filteredMessage ?? "");
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleDisconnect($this);
	}
}
