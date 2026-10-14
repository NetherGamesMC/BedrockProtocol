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

final class CameraSetInstructionEase{

	public function __construct(
		private CameraSetInstructionEaseType $type,
		private float $duration
	){}

	public function getType() : CameraSetInstructionEaseType{ return $this->type; }

	public function getDuration() : float{ return $this->duration; }

	public static function read(ByteBufferReader $in) : self{
		$type = CameraSetInstructionEaseType::fromOrdinal(Byte::readUnsigned($in));
		$duration = LE::readFloat($in);
		return new self($type, $duration);
	}

	public function write(ByteBufferWriter $out) : void{
		Byte::writeUnsigned($out, $this->type->toOrdinal());
		LE::writeFloat($out, $this->duration);
	}
}
