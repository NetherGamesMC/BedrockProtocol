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
use pmmp\encoding\LE;

final class MovePlayerTeleportData{

	public function __construct(
		private int $cause,
		private int $sourceActorType
	){}

	public function getCause() : int{ return $this->cause; }

	public function getSourceActorType() : int{ return $this->sourceActorType; }

	public static function read(ByteBufferReader $in) : self{
		$cause = LE::readUnsignedInt($in);
		$sourceActorType = LE::readUnsignedInt($in);
		return new self($cause, $sourceActorType);
	}

	public function write(ByteBufferWriter $out) : void{
		LE::writeUnsignedInt($out, $this->cause);
		LE::writeUnsignedInt($out, $this->sourceActorType);
	}
}
