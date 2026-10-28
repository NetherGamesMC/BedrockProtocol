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
use pmmp\encoding\VarInt;

/**
 * @see AttributeEnvironment
 */
final class AttributeEnvironmentConstant extends AttributeEnvironmentPayload{
	public const ID = AttributeEnvironmentPayloadType::CONSTANT;

	public function __construct(
		private AttributeValue $attribute
	){}

	public function getTypeId() : int{
		return self::ID;
	}

	public function getAttribute() : AttributeValue{ return $this->attribute; }

	public static function read(ByteBufferReader $in) : self{
		$attribute = AttributeValue::read($in);

		return new self($attribute);
	}

	public function write(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, $this->attribute->getTypeId());
		$this->attribute->write($out);
	}
}
