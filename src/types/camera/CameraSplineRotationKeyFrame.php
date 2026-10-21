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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class CameraSplineRotationKeyFrame{

	public function __construct(
		private Vector3 $rotation,
		private float $time,
		private ?CameraSetInstructionEaseType $easeType,
	){}

	public function getRotation() : Vector3{ return $this->rotation; }

	public function getTime() : float{ return $this->time; }

	public function getEaseType() : ?CameraSetInstructionEaseType{ return $this->easeType; }

	public static function read(ByteBufferReader $in) : self{
		$rotation = CommonTypes::getVector3($in);
		$time = LE::readFloat($in);
		$easeType = CommonTypes::readOptional($in, static fn(ByteBufferReader $in) => CameraSetInstructionEaseType::fromPacket(CommonTypes::getString($in)));

		return new self(
			$rotation,
			$time,
			$easeType
		);
	}

	public function write(ByteBufferWriter $out) : void{
		CommonTypes::putVector3($out, $this->rotation);
		LE::writeFloat($out, $this->time);
		CommonTypes::writeOptional($out, $this->easeType, static fn(ByteBufferWriter $out, CameraSetInstructionEaseType $v) => CommonTypes::putString($out, $v->value));
	}
}
