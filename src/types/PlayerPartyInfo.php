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
 * @see PartyChangedPacket
 */
final class PlayerPartyInfo{
	public function __construct(
		private string $partyId,
		private bool $partyLeader
	){}

	public function getPartyId() : string{ return $this->partyId; }

	public function isPartyLeader() : bool{ return $this->partyLeader; }

	public static function read(ByteBufferReader $in) : self{
		$partyId = CommonTypes::getString($in);
		$partyLeader = CommonTypes::getBool($in);

		return new self($partyId, $partyLeader);
	}

	public function write(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->partyId);
		CommonTypes::putBool($out, $this->partyLeader);
	}
}
