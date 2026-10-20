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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\ScoreboardIdentityPacketEntry;
use function count;

class SetScoreboardIdentityPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::SET_SCOREBOARD_IDENTITY_PACKET;

	public const TYPE_REGISTER_IDENTITY = 0;
	public const TYPE_CLEAR_IDENTITY = 1;

	public int $type;
	/** @var ScoreboardIdentityPacketEntry[] */
	public array $entries = [];

	/**
	 * @generate-create-func
	 * @param ScoreboardIdentityPacketEntry[] $entries
	 */
	public static function create(int $type, array $entries) : self{
		$result = new self;
		$result->type = $type;
		$result->entries = $entries;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->type = Byte::readUnsigned($in);
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$entry = new ScoreboardIdentityPacketEntry();
			$entry->scoreboardId = VarInt::readSignedLong($in);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				$entry->actorUniqueId = CommonTypes::readOptional($in, CommonTypes::getActorUniqueId(...));
			}elseif($this->type === self::TYPE_REGISTER_IDENTITY){
				$entry->actorUniqueId = CommonTypes::getActorUniqueId($in);
			}

			$this->entries[] = $entry;
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		Byte::writeUnsigned($out, $this->type);
		VarInt::writeUnsignedInt($out, count($this->entries));
		foreach($this->entries as $entry){
			VarInt::writeSignedLong($out, $entry->scoreboardId);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				CommonTypes::writeOptional($out, $entry->actorUniqueId, CommonTypes::putActorUniqueId(...));
			}elseif($this->type === self::TYPE_REGISTER_IDENTITY){
				CommonTypes::putActorUniqueId($out, $entry->actorUniqueId);
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSetScoreboardIdentity($this);
	}
}
