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
final class AttributeEnvironmentNoiseTransition extends AttributeEnvironmentPayload{
	public const ID = AttributeEnvironmentPayloadType::NOISE_TRANSITION;

	public function __construct(
		private AttributeValue $fromAttribute,
		private AttributeValue $toAttribute,
		private AttributeEnvironmentNoiseTransitionSettings $settings
	){}

	public function getTypeId() : int{
		return self::ID;
	}

	public function getFromAttribute() : AttributeValue{ return $this->fromAttribute; }

	public function getToAttribute() : AttributeValue{ return $this->toAttribute; }

	public function getSettings() : AttributeEnvironmentNoiseTransitionSettings{ return $this->settings; }

	public static function read(ByteBufferReader $in) : self{
		$fromAttribute = AttributeValue::read($in);
		$toAttribute = AttributeValue::read($in);
		$settings = AttributeEnvironmentNoiseTransitionSettings::read($in);

		return new self(
			$fromAttribute,
			$toAttribute,
			$settings
		);
	}

	public function write(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, $this->fromAttribute->getTypeId());
		$this->fromAttribute->write($out);
		VarInt::writeUnsignedInt($out, $this->toAttribute->getTypeId());
		$this->toAttribute->write($out);
		$this->settings->write($out);
	}
}
