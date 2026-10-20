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

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * Spec name: SubChunkPacketData
 */
final class SubChunkPacketEntry{

	public function __construct(
		private SubChunkPositionOffset $offset,
		private int $requestResult,
		private ?string $terrainData,
		private int $heightMapType,
		private ?SubChunkPacketHeightMapInfo $heightMapData,
		private int $renderHeightMapType,
		private ?SubChunkPacketHeightMapInfo $renderHeightMapData,
		private ?int $usedBlobHash
	){
		if($heightMapType === SubChunkPacketHeightMapType::DATA && $heightMapData === null){
			throw new \InvalidArgumentException("Heightmap data type is DATA but no heightmap data was provided");
		}
		if($renderHeightMapType === SubChunkPacketHeightMapType::DATA && $renderHeightMapData === null){
			throw new \InvalidArgumentException("Render heightmap data type is DATA but no render heightmap data was provided");
		}
	}

	public function getOffset() : SubChunkPositionOffset{ return $this->offset; }

	public function getRequestResult() : int{ return $this->requestResult; }

	public function getTerrainData() : ?string{ return $this->terrainData; }

	public function getHeightMapType() : int{ return $this->heightMapType; }

	public function getHeightMapData() : ?SubChunkPacketHeightMapInfo{ return $this->heightMapData; }

	public function getRenderHeightMapType() : int{ return $this->renderHeightMapType; }

	public function getRenderHeightMapData() : ?SubChunkPacketHeightMapInfo{ return $this->renderHeightMapData; }

	public function getUsedBlobHash() : ?int{ return $this->usedBlobHash; }

	/**
	 * @param bool $cacheEnabled only affects the wire format of protocols older than 1.26.40, where the presence of the
	 *                           terrain data and the blob hash depended on whether the blob cache was enabled
	 */
	public static function read(ByteBufferReader $in, int $protocolId, bool $cacheEnabled) : self{
		if($protocolId <= ProtocolInfo::PROTOCOL_1_26_30){
			return self::readLegacy($in, $protocolId, $cacheEnabled);
		}

		$offset = SubChunkPositionOffset::read($in);

		$requestResult = Byte::readUnsigned($in);

		$data = CommonTypes::readOptional($in, CommonTypes::getString(...));

		$heightMapType = Byte::readUnsigned($in);
		$heightMapData = CommonTypes::readOptional($in, SubChunkPacketHeightMapInfo::read(...));
		if($heightMapType === SubChunkPacketHeightMapType::DATA && $heightMapData === null){
			throw new PacketDecodeException("Heightmap data type is DATA but no heightmap data was provided");
		}

		$renderHeightMapType = Byte::readUnsigned($in);
		$renderHeightMapData = CommonTypes::readOptional($in, SubChunkPacketHeightMapInfo::read(...));
		if($renderHeightMapType === SubChunkPacketHeightMapType::DATA && $renderHeightMapData === null){
			//TODO: probably COPIED should bail if the first heightmap isn't provided somehow?
			throw new PacketDecodeException("Render heightmap data type is DATA but no render heightmap data was provided");
		}

		$blobHash = CommonTypes::readOptional($in, LE::readUnsignedLong(...));

		return new self(
			$offset,
			$requestResult,
			$data,
			$heightMapType,
			$heightMapData,
			$renderHeightMapType,
			$renderHeightMapData,
			$blobHash
		);
	}

	/**
	 * Pre-1.26.40 layout (see SubChunkPacketEntryCommon/SubChunkPacketEntryWithCache): the terrain data is omitted for
	 * SUCCESS_ALL_AIR results when the cache is enabled, heightmap data is only present for the DATA type, the render
	 * heightmap only exists since 1.21.90, and the blob hash is a plain LE long present only when the cache is enabled.
	 */
	private static function readLegacy(ByteBufferReader $in, int $protocolId, bool $cacheEnabled) : self{
		$offset = SubChunkPositionOffset::read($in);

		$requestResult = Byte::readUnsigned($in);

		$data = !$cacheEnabled || $requestResult !== SubChunkRequestResult::SUCCESS_ALL_AIR ? CommonTypes::getString($in) : null;

		$heightMapType = Byte::readUnsigned($in);
		$heightMapData = match($heightMapType){
			SubChunkPacketHeightMapType::NO_DATA,
			SubChunkPacketHeightMapType::ALL_TOO_HIGH,
			SubChunkPacketHeightMapType::ALL_TOO_LOW => null,
			SubChunkPacketHeightMapType::DATA => SubChunkPacketHeightMapInfo::read($in),
			default => throw new PacketDecodeException("Unknown heightmap data type $heightMapType")
		};

		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_90){
			$renderHeightMapType = Byte::readUnsigned($in);
			$renderHeightMapData = match($renderHeightMapType){
				SubChunkPacketHeightMapType::NO_DATA,
				SubChunkPacketHeightMapType::ALL_TOO_HIGH,
				SubChunkPacketHeightMapType::ALL_TOO_LOW,
				SubChunkPacketHeightMapType::ALL_COPIED => null,
				SubChunkPacketHeightMapType::DATA => SubChunkPacketHeightMapInfo::read($in),
				default => throw new PacketDecodeException("Unknown render heightmap data type $renderHeightMapType")
			};
		}else{
			//no separate render heightmap before 1.21.90 - the regular heightmap was used for everything
			$renderHeightMapType = SubChunkPacketHeightMapType::ALL_COPIED;
			$renderHeightMapData = null;
		}

		$blobHash = $cacheEnabled ? LE::readUnsignedLong($in) : null;

		return new self(
			$offset,
			$requestResult,
			$data,
			$heightMapType,
			$heightMapData,
			$renderHeightMapType,
			$renderHeightMapData,
			$blobHash
		);
	}

	/**
	 * @param bool $cacheEnabled only affects the wire format of protocols older than 1.26.40
	 */
	public function write(ByteBufferWriter $out, int $protocolId, bool $cacheEnabled) : void{
		if($protocolId <= ProtocolInfo::PROTOCOL_1_26_30){
			$this->writeLegacy($out, $protocolId, $cacheEnabled);
			return;
		}

		$this->offset->write($out);

		Byte::writeUnsigned($out, $this->requestResult);

		CommonTypes::writeOptional($out, $this->terrainData, CommonTypes::putString(...));

		Byte::writeUnsigned($out, $this->heightMapType);
		CommonTypes::writeOptional($out, $this->heightMapData, static fn($out, $v) => $v->write($out));

		Byte::writeUnsigned($out, $this->renderHeightMapType);
		CommonTypes::writeOptional($out, $this->renderHeightMapData, static fn($out, $v) => $v->write($out));

		CommonTypes::writeOptional($out, $this->usedBlobHash, LE::writeUnsignedLong(...));
	}

	private function writeLegacy(ByteBufferWriter $out, int $protocolId, bool $cacheEnabled) : void{
		$this->offset->write($out);

		Byte::writeUnsigned($out, $this->requestResult);

		if(!$cacheEnabled || $this->requestResult !== SubChunkRequestResult::SUCCESS_ALL_AIR){
			CommonTypes::putString($out, $this->terrainData ?? "");
		}

		if(
			$this->heightMapType !== SubChunkPacketHeightMapType::NO_DATA &&
			$this->heightMapType !== SubChunkPacketHeightMapType::DATA &&
			$this->heightMapType !== SubChunkPacketHeightMapType::ALL_TOO_HIGH &&
			$this->heightMapType !== SubChunkPacketHeightMapType::ALL_TOO_LOW
		){
			throw new \InvalidArgumentException("Heightmap data type $this->heightMapType is not supported by this protocol version");
		}
		Byte::writeUnsigned($out, $this->heightMapType);
		if($this->heightMapType === SubChunkPacketHeightMapType::DATA){
			$heightMapData = $this->heightMapData ?? throw new \AssertionError("Checked in constructor");
			$heightMapData->write($out);
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_90){
			if($this->renderHeightMapType < SubChunkPacketHeightMapType::NO_DATA || $this->renderHeightMapType > SubChunkPacketHeightMapType::ALL_COPIED){
				throw new \InvalidArgumentException("Render heightmap data type $this->renderHeightMapType is not supported by this protocol version");
			}
			Byte::writeUnsigned($out, $this->renderHeightMapType);
			if($this->renderHeightMapType === SubChunkPacketHeightMapType::DATA){
				$renderHeightMapData = $this->renderHeightMapData ?? throw new \AssertionError("Checked in constructor");
				$renderHeightMapData->write($out);
			}
		}

		if($cacheEnabled){
			LE::writeUnsignedLong($out, $this->usedBlobHash ?? throw new \InvalidArgumentException("usedBlobHash must be set when the cache is enabled"));
		}
	}
}
