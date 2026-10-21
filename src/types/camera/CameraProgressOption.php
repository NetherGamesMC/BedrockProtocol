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
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class CameraProgressOption{

	public function __construct(
		private float $value,
		private float $time,
		private CameraSetInstructionEaseType $easeType,
	){}

	public function getValue() : float{ return $this->value; }

	public function getTime() : float{ return $this->time; }

	public function getEaseType() : CameraSetInstructionEaseType{ return $this->easeType; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$value = LE::readFloat($in);
		$time = LE::readFloat($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_0){
			$easeType = CameraSetInstructionEaseType::fromPacket(CommonTypes::getString($in));
		}

		return new self(
			$value,
			$time,
			$easeType ?? CameraSetInstructionEaseType::LINEAR //not sent before 1.26.0
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		LE::writeFloat($out, $this->value);
		LE::writeFloat($out, $this->time);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_0){
			CommonTypes::putString($out, $this->easeType->value);
		}
	}
}
