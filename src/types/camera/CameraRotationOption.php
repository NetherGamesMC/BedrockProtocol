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
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function is_int;

final class CameraRotationOption{

	/** @see CameraSetInstructionEaseType */
	private string $easeType;

	public function __construct(
		private Vector3 $value,
		private float $time,
		int|string $easeType,
	){
		$this->easeType = is_int($easeType) ? CameraSetInstructionEaseType::toName($easeType) : $easeType;
	}

	public function getValue() : Vector3{ return $this->value; }

	public function getTime() : float{ return $this->time; }

	/**
	 * @see CameraSetInstructionEaseType
	 */
	public function getEaseType() : string{ return $this->easeType; }

<<<<<<< HEAD
	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$value = CommonTypes::getVector3($in);
		$time = LE::readFloat($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_0){
			$ease = CommonTypes::getString($in);
		}
=======
	public static function read(ByteBufferReader $in) : self{
		$value = CommonTypes::getVector3($in);
		$time = LE::readFloat($in);
		$ease = CommonTypes::getString($in);
>>>>>>> upstream/master

		return new self(
			$value,
			$time,
<<<<<<< HEAD
			$ease ?? ""
=======
			$ease
>>>>>>> upstream/master
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putVector3($out, $this->value);
		LE::writeFloat($out, $this->time);
<<<<<<< HEAD
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_0){
			CommonTypes::putString($out, $this->easeType);
		}
=======
		CommonTypes::putString($out, $this->easeType);
>>>>>>> upstream/master
	}
}
