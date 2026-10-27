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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use Ramsey\Uuid\UuidInterface;

/**
 * Spec name: DimensionDefinition
 */
final class DimensionData{

	public function __construct(
		private int $minimumY,
		private int $heightRange,
		private int $generator,
		private int $dimensionType,
		private UuidInterface $packId,
		private string $defaultBiome,
		private int $cloudHeight,
		private bool $renderClouds
	){}

	public function getMinimumY() : int{ return $this->minimumY; }

	public function getHeightRange() : int{ return $this->heightRange; }

	public function getGenerator() : int{ return $this->generator; }

	public function getDimensionType() : int{ return $this->dimensionType; }

	public function getPackId() : UuidInterface{ return $this->packId; }

	public function getDefaultBiome() : string{ return $this->defaultBiome; }

	public function getCloudHeight() : int{ return $this->cloudHeight; }

	public function isRenderClouds() : bool{ return $this->renderClouds; }

	public static function read(ByteBufferReader $in) : self{
		$minimumY = VarInt::readSignedInt($in);
		$heightRange = VarInt::readSignedInt($in);
		$generator = VarInt::readSignedInt($in);
		$dimensionType = VarInt::readSignedInt($in);
		$packId = CommonTypes::getUUID($in);
		$defaultBiome = CommonTypes::getString($in);
		$cloudHeight = VarInt::readSignedInt($in);
		$renderClouds = CommonTypes::getBool($in);

		return new self($minimumY, $heightRange, $generator, $dimensionType, $packId, $defaultBiome, $cloudHeight, $renderClouds);
	}

	public function write(ByteBufferWriter $out) : void{
		VarInt::writeSignedInt($out, $this->minimumY);
		VarInt::writeSignedInt($out, $this->heightRange);
		VarInt::writeSignedInt($out, $this->generator);
		VarInt::writeSignedInt($out, $this->dimensionType);
		CommonTypes::putUUID($out, $this->packId);
		CommonTypes::putString($out, $this->defaultBiome);
		VarInt::writeSignedInt($out, $this->cloudHeight);
		CommonTypes::putBool($out, $this->renderClouds);
	}
}
