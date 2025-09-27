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

namespace pocketmine\network\mcpe\protocol\types\camera;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\nbt\tag\CompoundTag;

final class CameraSetInstructionEase{

	/**
	 * @see CameraSetInstructionEaseType
	 */
	public function __construct(
		private int $type,
		private float $duration
	){}

	/**
	 * @see CameraSetInstructionEaseType
	 */
	public function getType() : int{ return $this->type; }

	public function getDuration() : float{ return $this->duration; }

	public static function read(ByteBufferReader $in) : self{
		$type = Byte::readUnsigned($in);
		$duration = LE::readFloat($in);
		return new self($type, $duration);
	}

	public static function fromNBT(CompoundTag $nbt) : self{
		$typeName = $nbt->getString("type");
		$type = CameraSetInstructionEaseType::fromString($typeName) ?? throw new \InvalidArgumentException("Invalid type tag");
		$duration = $nbt->getFloat("time");
		return new self($type, $duration);
	}

	public function write(ByteBufferWriter $out) : void{
		Byte::writeUnsigned($out, $this->type);
		LE::writeFloat($out, $this->duration);
	}

	public function toNBT() : CompoundTag{
		return CompoundTag::create()
			->setString("type", CameraSetInstructionEaseType::toString($this->type) ?? throw new \InvalidArgumentException("Invalid type"))
			->setFloat("time", $this->duration);
	}
}
