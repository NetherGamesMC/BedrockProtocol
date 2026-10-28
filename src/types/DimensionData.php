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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Spec name: DimensionDefinition
 */
final class DimensionData{

	/** Default used when the value is not available (protocols older than 1.26.60) */
	public const DEFAULT_CLOUD_HEIGHT = 128;

	/** >= ProtocolInfo::PROTOCOL_1_26_40 (nil UUID for older protocols) */
	private UuidInterface $packId;

	public function __construct(
		private int $minimumY,
		private int $heightRange,
		private int $generator,
		private int $dimensionType,
		?UuidInterface $packId = null,
		/** >= ProtocolInfo::PROTOCOL_1_26_50 */
		private string $defaultBiome = "",
		/** >= ProtocolInfo::PROTOCOL_1_26_60 */
		private int $cloudHeight = self::DEFAULT_CLOUD_HEIGHT,
		/** >= ProtocolInfo::PROTOCOL_1_26_60 */
		private bool $renderClouds = true
	){
		$this->packId = $packId ?? Uuid::fromString(Uuid::NIL);
	}

	public function getMinimumY() : int{ return $this->minimumY; }

	public function getHeightRange() : int{ return $this->heightRange; }

	/**
	 * Maximum height (exclusive), as sent to protocols older than 1.26.50.
	 */
	public function getMaxHeight() : int{ return $this->minimumY + $this->heightRange; }

	/**
	 * Minimum height, as sent to protocols older than 1.26.50.
	 */
	public function getMinHeight() : int{ return $this->minimumY; }

	public function getGenerator() : int{ return $this->generator; }

	public function getDimensionType() : int{ return $this->dimensionType; }

	public function getPackId() : UuidInterface{ return $this->packId; }

	public function getDefaultBiome() : string{ return $this->defaultBiome; }

	/** >= ProtocolInfo::PROTOCOL_1_26_60 */
	public function getCloudHeight() : int{ return $this->cloudHeight; }

	/** >= ProtocolInfo::PROTOCOL_1_26_60 */
	public function isRenderClouds() : bool{ return $this->renderClouds; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			$minimumY = VarInt::readSignedInt($in);
			$heightRange = VarInt::readSignedInt($in);
		}else{
			//before 1.26.50 these were the maximum and minimum heights
			$maxHeight = VarInt::readSignedInt($in);
			$minimumY = VarInt::readSignedInt($in);
			$heightRange = $maxHeight - $minimumY;
		}
		$generator = VarInt::readSignedInt($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
			$dimensionType = VarInt::readSignedInt($in);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$packId = CommonTypes::getUUID($in);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			$defaultBiome = CommonTypes::getString($in);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			$cloudHeight = VarInt::readSignedInt($in);
			$renderClouds = CommonTypes::getBool($in);
		}

		return new self(
			$minimumY,
			$heightRange,
			$generator,
			$dimensionType ?? DimensionIds::OVERWORLD,
			$packId ?? null,
			$defaultBiome ?? "",
			$cloudHeight ?? self::DEFAULT_CLOUD_HEIGHT,
			$renderClouds ?? true
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			VarInt::writeSignedInt($out, $this->minimumY);
			VarInt::writeSignedInt($out, $this->heightRange);
		}else{
			VarInt::writeSignedInt($out, $this->getMaxHeight());
			VarInt::writeSignedInt($out, $this->getMinHeight());
		}
		VarInt::writeSignedInt($out, $this->generator);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
			VarInt::writeSignedInt($out, $this->dimensionType);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::putUUID($out, $this->packId);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			CommonTypes::putString($out, $this->defaultBiome);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			VarInt::writeSignedInt($out, $this->cloudHeight);
			CommonTypes::putBool($out, $this->renderClouds);
		}
	}
}
