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

namespace pocketmine\network\mcpe\protocol\types\camera;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function is_int;

final class CameraSplineProgressKeyFrame{

	/** @see CameraSetInstructionEaseType */
	private ?string $easeType;

	public function __construct(
		private float $progress,
		private float $time,
		int|string|null $easeType,
	){
		$this->easeType = is_int($easeType) ? CameraSetInstructionEaseType::toName($easeType) : $easeType;
	}

	public function getProgress() : float{ return $this->progress; }

	public function getTime() : float{ return $this->time; }

	/**
	 * @see CameraSetInstructionEaseType
	 */
	public function getEaseType() : ?string{ return $this->easeType; }

	public static function read(ByteBufferReader $in) : self{
		$progress = LE::readFloat($in);
		$time = LE::readFloat($in);
		$easeType = CommonTypes::readOptional($in, CommonTypes::getString(...));

		return new self(
			$progress,
			$time,
			$easeType
		);
	}

	public function write(ByteBufferWriter $out) : void{
		LE::writeFloat($out, $this->progress);
		LE::writeFloat($out, $this->time);
		CommonTypes::writeOptional($out, $this->easeType, CommonTypes::putString(...));
	}
}
