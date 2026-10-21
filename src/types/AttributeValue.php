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
use pocketmine\network\mcpe\protocol\PacketDecodeException;

/**
 * @see AttributeEnvironment
 */
abstract class AttributeValue{

	abstract public function getTypeId() : int;

	/**
	 * Writes the value payload only, without the type ID. Use {@link AttributeValue::writeWithType()} to write a value
	 * that can be decoded by {@link AttributeValue::read()}.
	 */
	abstract public function write(ByteBufferWriter $out) : void;

	/**
	 * Writes the type ID followed by the value payload. This is the counterpart of {@link AttributeValue::read()}.
	 */
	final public function writeWithType(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, $this->getTypeId());
		$this->write($out);
	}

	/**
	 * Reads the type ID followed by the value payload.
	 */
	public static function read(ByteBufferReader $in) : self{
		return match(VarInt::readUnsignedInt($in)){
			AttributeValueBool::ID => AttributeValueBool::read($in),
			AttributeValueFloat::ID => AttributeValueFloat::read($in),
			AttributeValueColor::ID => AttributeValueColor::read($in),
			default => throw new PacketDecodeException("Unknown AttributeValue type"),
		};
	}
}
