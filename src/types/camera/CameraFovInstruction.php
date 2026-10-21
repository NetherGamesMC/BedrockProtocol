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
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class CameraFovInstruction{

	public function __construct(
		private float $fieldOfView,
		private float $easeTime,
		private CameraSetInstructionEaseType $easeType,
		private bool $clear,
	){}

	public function getFieldOfView() : float{ return $this->fieldOfView; }

	public function getEaseTime() : float{ return $this->easeTime; }

	public function getEaseType() : CameraSetInstructionEaseType{ return $this->easeType; }

	public function getClear() : bool{ return $this->clear; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$fieldOfView = LE::readFloat($in);
		$easeTime = LE::readFloat($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_10){
			$easeType = CameraSetInstructionEaseType::fromPacket(CommonTypes::getString($in));
		}else{
			$easeType = CameraSetInstructionEaseType::fromOrdinal(Byte::readUnsigned($in));
		}
		$clear = CommonTypes::getBool($in);

		return new self(
			$fieldOfView,
			$easeTime,
			$easeType,
			$clear
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		LE::writeFloat($out, $this->fieldOfView);
		LE::writeFloat($out, $this->easeTime);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_10){
			CommonTypes::putString($out, $this->easeType->value);
		}else{
			if($this->easeType === CameraSetInstructionEaseType::INVERSE_LERP){
				throw new \InvalidArgumentException("Ease type " . $this->easeType->value . " is not supported by this protocol");
			}
			Byte::writeUnsigned($out, $this->easeType->toOrdinal());
		}
		CommonTypes::putBool($out, $this->clear);
	}
}
