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
use pocketmine\network\mcpe\protocol\ProtocolInfo;

/**
 * >= ProtocolInfo::PROTOCOL_1_26_40. Older protocols have no null game rule type.
 */
final class NullGameRule extends GameRule{
	use GetTypeIdFromConstTrait;

	public const ID = GameRuleType::NULL;

	public function __construct(bool $isPlayerModifiable){
		parent::__construct($isPlayerModifiable);
	}

	public function encode(ByteBufferWriter $out, int $protocolId, bool $isStartGame) : void{
		if($protocolId <= ProtocolInfo::PROTOCOL_1_26_30){
			throw new \InvalidArgumentException("Null game rules are not supported before 1.26.40");
		}
		//NOOP
	}

	public static function decode(ByteBufferReader $in, int $protocolId, bool $isPlayerModifiable) : self{
		return new self($isPlayerModifiable);
	}
}
