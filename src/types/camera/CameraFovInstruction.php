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
use function is_int;

final class CameraFovInstruction{

	/** @see CameraSetInstructionEaseType */
	private string $easeType;

	public function __construct(
		private float $fieldOfView,
		private float $easeTime,
		int|string $easeType,
		private bool $clear,
	){
		$this->easeType = is_int($easeType) ? CameraSetInstructionEaseType::toName($easeType) : $easeType;
	}

	public function getFieldOfView() : float{ return $this->fieldOfView; }

	public function getEaseTime() : float{ return $this->easeTime; }

	/**
	 * @see CameraSetInstructionEaseType
	 */
	public function getEaseType() : string{ return $this->easeType; }

	public function getClear() : bool{ return $this->clear; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$fieldOfView = LE::readFloat($in);
		$easeTime = LE::readFloat($in);
<<<<<<< HEAD
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_10){
			$easeType = CommonTypes::getString($in);
		}else{
			$easeType = CameraSetInstructionEaseType::toName(Byte::readUnsigned($in));
		}
=======
		$easeType = CommonTypes::getString($in);
>>>>>>> upstream/master
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
<<<<<<< HEAD
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_10){
			CommonTypes::putString($out, $this->easeType);
		}else{
			Byte::writeUnsigned($out, CameraSetInstructionEaseType::fromName($this->easeType));
		}
=======
		CommonTypes::putString($out, $this->easeType);
>>>>>>> upstream/master
		CommonTypes::putBool($out, $this->clear);
	}
}
