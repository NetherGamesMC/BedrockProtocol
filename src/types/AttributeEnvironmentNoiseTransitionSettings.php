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
 * @see AttributeEnvironmentNoiseTransition
 */
final class AttributeEnvironmentNoiseTransitionSettings{

	public function __construct(
		private int $totalTransitionTicks,
		private int $currentTransitionTicks,
		private CameraSetInstructionEaseType $easeType,
		private string $clockName,
		private int $localTransitionTicks,
		private string $noiseName,
		private NoiseAlignment $noiseAlignment
	){}

	public function getTotalTransitionTicks() : int{ return $this->totalTransitionTicks; }

	public function getCurrentTransitionTicks() : int{ return $this->currentTransitionTicks; }

	public function getEaseType() : CameraSetInstructionEaseType{ return $this->easeType; }

	public function getClockName() : string{ return $this->clockName; }

	public function getLocalTransitionTicks() : int{ return $this->localTransitionTicks; }

	public function getNoiseName() : string{ return $this->noiseName; }

	public function getNoiseAlignment() : NoiseAlignment{ return $this->noiseAlignment; }

	public static function read(ByteBufferReader $in) : self{
		$totalTransitionTicks = VarInt::readUnsignedInt($in);
		$currentTransitionTicks = VarInt::readUnsignedInt($in);
		$easeType = CameraSetInstructionEaseType::fromOrdinal(VarInt::readSignedInt($in));
		$clockName = CommonTypes::getString($in);
		$localTransitionTicks = VarInt::readUnsignedInt($in);
		$noiseName = CommonTypes::getString($in);
		$noiseAlignment = NoiseAlignment::read($in);

		return new self(
			$totalTransitionTicks,
			$currentTransitionTicks,
			$easeType,
			$clockName,
			$localTransitionTicks,
			$noiseName,
			$noiseAlignment
		);
	}

	public function write(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, $this->totalTransitionTicks);
		VarInt::writeUnsignedInt($out, $this->currentTransitionTicks);
		VarInt::writeSignedInt($out, $this->easeType->toOrdinal());
		CommonTypes::putString($out, $this->clockName);
		VarInt::writeUnsignedInt($out, $this->localTransitionTicks);
		CommonTypes::putString($out, $this->noiseName);
		$this->noiseAlignment->write($out);
	}
}
