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
use pocketmine\network\mcpe\protocol\types\GameMode;

class UpdatePlayerGameTypePacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::UPDATE_PLAYER_GAME_TYPE_PACKET;

	private GameMode $gameMode;
	private int $playerActorUniqueId;
	private int $tick;

	/**
	 * @generate-create-func
	 */
	public static function create(GameMode $gameMode, int $playerActorUniqueId, int $tick) : self{
		$result = new self;
		$result->gameMode = $gameMode;
		$result->playerActorUniqueId = $playerActorUniqueId;
		$result->tick = $tick;
		return $result;
	}

	public function getGameMode() : GameMode{ return $this->gameMode; }

	public function getPlayerActorUniqueId() : int{ return $this->playerActorUniqueId; }

	public function getTick() : int{ return $this->tick; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->gameMode = GameMode::fromPacket(VarInt::readSignedInt($in));
		$this->playerActorUniqueId = CommonTypes::getActorUniqueId($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
			$this->tick = VarInt::readUnsignedLong($in);
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_20_80){
			$this->tick = VarInt::readUnsignedInt($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		VarInt::writeSignedInt($out, $this->gameMode->value);
		CommonTypes::putActorUniqueId($out, $this->playerActorUniqueId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
			VarInt::writeUnsignedLong($out, $this->tick);
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_20_80){
			VarInt::writeUnsignedInt($out, $this->tick);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleUpdatePlayerGameType($this);
	}
}
