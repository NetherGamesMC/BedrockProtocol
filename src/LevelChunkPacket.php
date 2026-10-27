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
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use function count;

class LevelChunkPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::LEVEL_CHUNK_PACKET;

	//this appears large enough for a world height of 1024 blocks - it may need to be increased in the future
	private const MAX_BLOB_HASHES = 64;

	private ChunkPosition $chunkPosition;
	/** @phpstan-var DimensionIds::* */
	private int $dimensionId;
	private int $subChunkCount;
	private ?int $subChunkRequestLimit;
	private bool $cacheEnabled;
	/** @var int[] */
	private array $usedBlobHashes = [];
	private string $extraPayload;
	/** Whether the server biome data overrides the client biome data. */
	private bool $clientBiomeUpdate;

	/**
	 * @generate-create-func
	 * @param int[] $usedBlobHashes
	 * @phpstan-param DimensionIds::* $dimensionId
	 */
	public static function create(
		ChunkPosition $chunkPosition,
		int $dimensionId,
		int $subChunkCount,
		?int $subChunkRequestLimit,
		bool $cacheEnabled,
		array $usedBlobHashes,
		string $extraPayload,
		bool $clientBiomeUpdate,
	) : self{
		$result = new self;
		$result->chunkPosition = $chunkPosition;
		$result->dimensionId = $dimensionId;
		$result->subChunkCount = $subChunkCount;
		$result->subChunkRequestLimit = $subChunkRequestLimit;
		$result->cacheEnabled = $cacheEnabled;
		$result->usedBlobHashes = $usedBlobHashes;
		$result->extraPayload = $extraPayload;
		$result->clientBiomeUpdate = $clientBiomeUpdate;
		return $result;
	}

	public function getChunkPosition() : ChunkPosition{ return $this->chunkPosition; }

	public function getDimensionId() : int{ return $this->dimensionId; }

	public function getSubChunkCount() : int{
		return $this->subChunkCount;
	}

	public function getSubChunkRequestLimit() : ?int{
		return $this->subChunkRequestLimit;
	}

	public function isCacheEnabled() : bool{
		return $this->cacheEnabled;
	}

	/**
	 * @return int[]
	 */
	public function getUsedBlobHashes() : array{
		return $this->usedBlobHashes;
	}

	public function getExtraPayload() : string{
		return $this->extraPayload;
	}

	public function isClientBiomeUpdate() : bool{ return $this->clientBiomeUpdate; }

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->chunkPosition = ChunkPosition::read($in);
		$this->dimensionId = VarInt::readSignedInt($in);

		$this->subChunkCount = VarInt::readUnsignedInt($in);
		$this->subChunkRequestLimit = CommonTypes::readOptional($in, VarInt::readSignedInt(...));

		$this->cacheEnabled = CommonTypes::getBool($in);
		$this->usedBlobHashes = [];
		$count = VarInt::readUnsignedInt($in);
		if($count > self::MAX_BLOB_HASHES){
			throw new PacketDecodeException("Expected at most " . self::MAX_BLOB_HASHES . " blob hashes, got " . $count);
		}
		for($i = 0; $i < $count; ++$i){
			$this->usedBlobHashes[] = LE::readUnsignedLong($in);
		}

		$this->extraPayload = CommonTypes::getString($in);
		$this->clientBiomeUpdate = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		$this->chunkPosition->write($out);
		VarInt::writeSignedInt($out, $this->dimensionId);

		VarInt::writeUnsignedInt($out, $this->subChunkCount);
		CommonTypes::writeOptional($out, $this->subChunkRequestLimit, VarInt::writeSignedInt(...));

		CommonTypes::putBool($out, $this->cacheEnabled);
		//these are always written as of 26.40. Not sure why they don't just make an optional out of it since they seem
		//to like optional lists so much
		VarInt::writeUnsignedInt($out, count($this->usedBlobHashes));
		foreach($this->usedBlobHashes as $hash){
			LE::writeUnsignedLong($out, $hash);
		}

		CommonTypes::putString($out, $this->extraPayload);
		CommonTypes::putBool($out, $this->clientBiomeUpdate);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleLevelChunk($this);
	}
}
