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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class IntGameRule extends GameRule{
	use GetTypeIdFromConstTrait;

	public const ID = GameRuleType::INT;

	private int $value;

	public function __construct(int $value, bool $isPlayerModifiable){
		parent::__construct($isPlayerModifiable);
		$this->value = $value;
	}

	public function getValue() : int{
		return $this->value;
	}

	public function encode(ByteBufferWriter $out, int $protocolId, bool $isStartGame) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			LE::writeUnsignedInt($out, $this->value);
		}elseif($isStartGame || $protocolId < ProtocolInfo::PROTOCOL_1_21_111){
			VarInt::writeUnsignedInt($out, $this->value);
		}else{
			LE::writeUnsignedInt($out, $this->value);
		}
	}

	public static function decode(ByteBufferReader $in, int $protocolId, bool $isPlayerModifiable, bool $isStartGame) : self{
		$value = match(true){
			$protocolId >= ProtocolInfo::PROTOCOL_1_26_40 => LE::readUnsignedInt($in),
			$isStartGame || $protocolId < ProtocolInfo::PROTOCOL_1_21_111 => VarInt::readUnsignedInt($in),
			default => LE::readUnsignedInt($in),
		};
		return new self($value, $isPlayerModifiable);
	}
}
