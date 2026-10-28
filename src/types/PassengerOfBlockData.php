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
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * Spec name: PassengerOfBlockArguments
 */
final class PassengerOfBlockData{

	public function __construct(
		private readonly BlockPosition $blockPos,
		private readonly Vector3 $offset,
		private readonly float $rotation,
		private readonly float $rotationLimit,
		private readonly EmoteType $emoteType
	){
	}

	public function getBlockPos() : BlockPosition{
		return $this->blockPos;
	}

	public function getOffset() : Vector3{
		return $this->offset;
	}

	public function getRotation() : float{
		return $this->rotation;
	}

	public function getRotationLimit() : float{
		return $this->rotationLimit;
	}

	public function getEmoteType() : EmoteType{
		return $this->emoteType;
	}

	public function write(ByteBufferWriter $out) : void{
		CommonTypes::putBlockPosition($out, $this->blockPos);
		CommonTypes::putVector3($out, $this->offset);
		LE::writeFloat($out, $this->rotation);
		LE::writeFloat($out, $this->rotationLimit);
		Byte::writeUnsigned($out, $this->emoteType->value);
	}

	public static function read(ByteBufferReader $in) : self{
		$blockPos = CommonTypes::getBlockPosition($in);
		$offset = CommonTypes::getVector3($in);
		$rotation = LE::readFloat($in);
		$rotationLimit = LE::readFloat($in);
		$emoteType = EmoteType::fromPacket(Byte::readUnsigned($in));

		return new self($blockPos, $offset, $rotation, $rotationLimit, $emoteType);
	}
}
