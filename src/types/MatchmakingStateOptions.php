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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * @see ClientboundMatchmakingStatePacket
 */
final class MatchmakingStateOptions{
	public function __construct(
		private ?string $triggeringPlayerName,
		private ?bool $triggeredByLocalPlayer
	){}

	public function getTriggeringPlayerName() : ?string{ return $this->triggeringPlayerName; }

	public function getTriggeredByLocalPlayer() : ?bool{ return $this->triggeredByLocalPlayer; }

	public static function read(ByteBufferReader $in) : self{
		$triggeringPlayerName = CommonTypes::readOptional($in, CommonTypes::getString(...));
		$triggeredByLocalPlayer = CommonTypes::readOptional($in, CommonTypes::getBool(...));

		return new self($triggeringPlayerName, $triggeredByLocalPlayer);
	}

	public function write(ByteBufferWriter $out) : void{
		CommonTypes::writeOptional($out, $this->triggeringPlayerName, CommonTypes::putString(...));
		CommonTypes::writeOptional($out, $this->triggeredByLocalPlayer, CommonTypes::putBool(...));
	}
}
