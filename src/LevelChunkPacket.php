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
use pocketmine\utils\Limits;
use function count;

class LevelChunkPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::LEVEL_CHUNK_PACKET;

	/**
	 * Value of {@link LevelChunkPacket::getSubChunkRequestLimit()} meaning the client may request all subchunks as
	 * needed up to the top of the world (no limit).
	 * Before 1.26.40 this was encoded as {@link LevelChunkPacket::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT}; any negative
	 * limit is treated this way when encoding for those versions.
	 */
	public const SUB_CHUNK_REQUEST_LIMIT_NONE = -1;

	/**
	 * Before 1.26.40: client will request all subchunks as needed up to the top of the world
	 */
	private const CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT = Limits::UINT32_MAX;
	/**
	 * Before 1.26.40: client will request subchunks as needed up to the height written in the packet, and assume that
	 * anything above that height is air (wtf mojang ...)
	 */
	private const CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT = Limits::UINT32_MAX - 1;

	//this appears large enough for a world height of 1024 blocks - it may need to be increased in the future
	private const MAX_BLOB_HASHES = 64;

	private ChunkPosition $chunkPosition;
	/** @phpstan-var DimensionIds::* */
	private int $dimensionId = DimensionIds::OVERWORLD;
	private int $subChunkCount;
	/**
	 * null = client sub-chunk requests disabled. Negative ({@link LevelChunkPacket::SUB_CHUNK_REQUEST_LIMIT_NONE}) =
	 * requests enabled without a limit (full column).
	 */
	private ?int $subChunkRequestLimit;
	private bool $cacheEnabled;
	/** @var int[] */
	private array $usedBlobHashes = [];
	private string $extraPayload;

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
	) : self{
		$result = new self;
		$result->chunkPosition = $chunkPosition;
		$result->dimensionId = $dimensionId;
		$result->subChunkCount = $subChunkCount;
		$result->subChunkRequestLimit = $subChunkRequestLimit;
		$result->cacheEnabled = $cacheEnabled;
		$result->usedBlobHashes = $usedBlobHashes;
		$result->extraPayload = $extraPayload;
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

	public function isClientSubChunkRequestsEnabled() : bool{
		return $this->subChunkRequestLimit !== null;
	}

	/** @deprecated incorrect name */
	public function isClientSubChunkRequestEnabled() : bool{
		return $this->isClientSubChunkRequestsEnabled();
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

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->chunkPosition = ChunkPosition::read($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_60){
			$this->dimensionId = VarInt::readSignedInt($in);
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->subChunkCount = VarInt::readUnsignedInt($in);
			$this->subChunkRequestLimit = CommonTypes::readOptional($in, VarInt::readSignedInt(...));

			$this->cacheEnabled = CommonTypes::getBool($in);
			$this->usedBlobHashes = $this->readBlobHashes($in);
		}else{
			$subChunkCountButNotReally = VarInt::readUnsignedInt($in);
			if($subChunkCountButNotReally === self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT){
				$this->subChunkCount = 0;
				$this->subChunkRequestLimit = self::SUB_CHUNK_REQUEST_LIMIT_NONE;
			}elseif($subChunkCountButNotReally === self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT){
				$this->subChunkCount = 0;
				$this->subChunkRequestLimit = LE::readUnsignedShort($in);
			}else{
				$this->subChunkCount = $subChunkCountButNotReally;
				$this->subChunkRequestLimit = null;
			}

			$this->cacheEnabled = CommonTypes::getBool($in);
			$this->usedBlobHashes = $this->cacheEnabled ? $this->readBlobHashes($in) : [];
		}

		$this->extraPayload = CommonTypes::getString($in);
	}

	/**
	 * @return int[]
	 */
	private function readBlobHashes(ByteBufferReader $in) : array{
		$hashes = [];
		$count = VarInt::readUnsignedInt($in);
		if($count > self::MAX_BLOB_HASHES){
			throw new PacketDecodeException("Expected at most " . self::MAX_BLOB_HASHES . " blob hashes, got " . $count);
		}
		for($i = 0; $i < $count; ++$i){
			$hashes[] = LE::readUnsignedLong($in);
		}
		return $hashes;
	}

	private function writeBlobHashes(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, count($this->usedBlobHashes));
		foreach($this->usedBlobHashes as $hash){
			LE::writeUnsignedLong($out, $hash);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		$this->chunkPosition->write($out);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_60){
			VarInt::writeSignedInt($out, $this->dimensionId);
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			VarInt::writeUnsignedInt($out, $this->subChunkCount);
			CommonTypes::writeOptional($out, $this->subChunkRequestLimit, VarInt::writeSignedInt(...));

			CommonTypes::putBool($out, $this->cacheEnabled);
			//these are always written as of 26.40. Not sure why they don't just make an optional out of it since they seem
			//to like optional lists so much
			$this->writeBlobHashes($out);
		}else{
			if($this->subChunkRequestLimit === null){
				VarInt::writeUnsignedInt($out, $this->subChunkCount);
			}elseif($this->subChunkRequestLimit < 0){
				VarInt::writeUnsignedInt($out, self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT);
			}else{
				VarInt::writeUnsignedInt($out, self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT);
				LE::writeUnsignedShort($out, $this->subChunkRequestLimit);
			}

			CommonTypes::putBool($out, $this->cacheEnabled);
			if($this->cacheEnabled){
				$this->writeBlobHashes($out);
			}
		}

		CommonTypes::putString($out, $this->extraPayload);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleLevelChunk($this);
	}
}
