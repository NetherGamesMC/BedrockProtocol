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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * @see AttributeEnvironment
 */
final class AttributeValueFloat extends AttributeValue{
	public const ID = AttributeValueType::FLOAT;

	public const OPERATION_OVERRIDE = "override";
	public const OPERATION_ALPHA_BLEND = "alpha_blend";
	public const OPERATION_ADD = "add";
	public const OPERATION_SUBTRACT = "subtract";
	public const OPERATION_MULTIPLY = "multiply";
	public const OPERATION_MINIMUM = "minimum";
	public const OPERATION_MAXIMUM = "maximum";

	public function __construct(
		private float $value,
		private string $operation,
		private ?float $constraintMin = null,
		private ?float $constraintMax = null,
	){}

	public function getTypeId() : int{
		return self::ID;
	}

	public function getValue() : float{ return $this->value; }

	public function getOperation() : string{ return $this->operation; }

	public function getConstraintMin() : ?float{ return $this->constraintMin; }

	public function getConstraintMax() : ?float{ return $this->constraintMax; }

	public static function read(ByteBufferReader $in) : self{
		$value = LE::readFloat($in);
		$operation = CommonTypes::getString($in);
		$constraintMin = CommonTypes::readOptional($in, LE::readFloat(...));
		$constraintMax = CommonTypes::readOptional($in, LE::readFloat(...));

		return new self(
			$value,
			$operation,
			$constraintMin,
			$constraintMax
		);
	}

	public function write(ByteBufferWriter $out) : void{
		LE::writeFloat($out, $this->value);
		CommonTypes::putString($out, $this->operation);
		CommonTypes::writeOptional($out, $this->constraintMin, LE::writeFloat(...));
		CommonTypes::writeOptional($out, $this->constraintMax, LE::writeFloat(...));
	}
}
