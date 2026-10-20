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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\SubChunkPacketEntry;
use pocketmine\network\mcpe\protocol\types\SubChunkPosition;
use function count;

class SubChunkPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::SUB_CHUNK_PACKET;

	private bool $cacheEnabled;
	private int $dimension;
	private SubChunkPosition $baseSubChunkPosition;
	/**
	 * @var SubChunkPacketEntry[]
	 * @phpstan-var list<SubChunkPacketEntry>
	 */
	private array $entries;

	/**
	 * @generate-create-func
	 * @param SubChunkPacketEntry[] $entries
	 * @phpstan-param list<SubChunkPacketEntry> $entries
	 */
	public static function create(bool $cacheEnabled, int $dimension, SubChunkPosition $baseSubChunkPosition, array $entries) : self{
		$result = new self;
		$result->cacheEnabled = $cacheEnabled;
		$result->dimension = $dimension;
		$result->baseSubChunkPosition = $baseSubChunkPosition;
		$result->entries = $entries;
		return $result;
	}

	public function isCacheEnabled() : bool{ return $this->cacheEnabled; }

	public function getDimension() : int{ return $this->dimension; }

	public function getBaseSubChunkPosition() : SubChunkPosition{ return $this->baseSubChunkPosition; }

	/**
	 * @return SubChunkPacketEntry[]
	 * @phpstan-return list<SubChunkPacketEntry>
	 */
	public function getEntries() : array{ return $this->entries; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->cacheEnabled = CommonTypes::getBool($in);
		$this->dimension = VarInt::readSignedInt($in);

		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->baseSubChunkPosition = SubChunkPosition::read($in);

			$this->entries = CommonTypes::readList($in, fn(ByteBufferReader $in) => SubChunkPacketEntry::read($in, $protocolId, $this->cacheEnabled));
		}else{
			$this->baseSubChunkPosition = SubChunkPosition::readVarInts($in);

			$this->entries = [];
			for($i = 0, $count = LE::readUnsignedInt($in); $i < $count; $i++){
				$this->entries[] = SubChunkPacketEntry::read($in, $protocolId, $this->cacheEnabled);
			}
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putBool($out, $this->cacheEnabled);
		VarInt::writeSignedInt($out, $this->dimension);

		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->baseSubChunkPosition->write($out);

			CommonTypes::writeList($out, $this->entries, fn(ByteBufferWriter $out, SubChunkPacketEntry $v) => $v->write($out, $protocolId, $this->cacheEnabled));
		}else{
			$this->baseSubChunkPosition->writeVarInts($out);

			LE::writeUnsignedInt($out, count($this->entries));
			foreach($this->entries as $entry){
				$entry->write($out, $protocolId, $this->cacheEnabled);
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSubChunk($this);
	}
}
