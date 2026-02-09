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

final class CameraProgressOption{

	/**
	 * @see CameraSetInstructionEaseType
	 */
	public function __construct(
		private float $value,
		private float $time,
		private int $easeType,
	){}

	public function getValue() : float{ return $this->value; }

	public function getTime() : float{ return $this->time; }

	/**
	 * @see CameraSetInstructionEaseType
	 */
	public function getEaseType() : int{ return $this->easeType; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$value = LE::readFloat($in);
		$time = LE::readFloat($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_0){
			$easeType = LE::readUnsignedInt($in);
		}

		return new self(
			$value,
			$time,
			$easeType ?? -1
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		LE::writeFloat($out, $this->value);
		LE::writeFloat($out, $this->time);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_0){
			LE::writeUnsignedInt($out, $this->easeType);
		}
	}
}
