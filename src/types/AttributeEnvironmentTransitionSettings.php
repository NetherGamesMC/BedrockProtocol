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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\camera\CameraSetInstructionEaseType;

/**
 * @see AttributeEnvironmentTransition
 */
final class AttributeEnvironmentTransitionSettings{

	public function __construct(
		private int $totalTransitionTicks,
		private int $currentTransitionTicks,
		private CameraSetInstructionEaseType $easeType,
		private string $clockName
	){}

	public function getTotalTransitionTicks() : int{ return $this->totalTransitionTicks; }

	public function getCurrentTransitionTicks() : int{ return $this->currentTransitionTicks; }

	public function getEaseType() : CameraSetInstructionEaseType{ return $this->easeType; }

	public function getClockName() : string{ return $this->clockName; }

	public static function read(ByteBufferReader $in) : self{
		$totalTransitionTicks = VarInt::readUnsignedInt($in);
		$currentTransitionTicks = VarInt::readUnsignedInt($in);
		$easeType = CameraSetInstructionEaseType::fromOrdinal(VarInt::readSignedInt($in));
		$clockName = CommonTypes::getString($in);

		return new self(
			$totalTransitionTicks,
			$currentTransitionTicks,
			$easeType,
			$clockName
		);
	}

	public function write(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, $this->totalTransitionTicks);
		VarInt::writeUnsignedInt($out, $this->currentTransitionTicks);
		VarInt::writeSignedInt($out, $this->easeType->toOrdinal());
		CommonTypes::putString($out, $this->clockName);
	}
}
