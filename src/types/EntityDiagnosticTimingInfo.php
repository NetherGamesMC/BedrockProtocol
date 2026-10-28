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

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class EntityDiagnosticTimingInfo{

	public function __construct(
		private string $displayName,
		private string $entity,
		private int $timeInNS,
		private int $percentOfTotal,
		/** >= ProtocolInfo::PROTOCOL_1_26_50 (optional since ProtocolInfo::PROTOCOL_1_26_60) */
		private ?Vector3 $position = null,
		/** >= ProtocolInfo::PROTOCOL_1_26_50 (optional since ProtocolInfo::PROTOCOL_1_26_60) */
		private ?string $dimension = null,
	){}

	public function getDisplayName() : string{ return $this->displayName; }

	public function getEntity() : string{ return $this->entity; }

	public function getTimeInNS() : int{ return $this->timeInNS; }

	public function getPercentOfTotal() : int{ return $this->percentOfTotal; }

	public function getPosition() : ?Vector3{ return $this->position; }

	public function getDimension() : ?string{ return $this->dimension; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$displayName = CommonTypes::getString($in);
		$entity = CommonTypes::getString($in);
		$timeInNS = LE::readUnsignedLong($in);
		$percentOfTotal = Byte::readUnsigned($in);
		$position = null;
		$dimension = null;
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			$position = CommonTypes::readOptional($in, CommonTypes::getVector3(...));
			$dimension = CommonTypes::readOptional($in, CommonTypes::getString(...));
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			$position = CommonTypes::getVector3($in);
			$dimension = CommonTypes::getString($in);
		}

		return new self(
			$displayName,
			$entity,
			$timeInNS,
			$percentOfTotal,
			$position,
			$dimension
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->displayName);
		CommonTypes::putString($out, $this->entity);
		LE::writeUnsignedLong($out, $this->timeInNS);
		Byte::writeUnsigned($out, $this->percentOfTotal);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			CommonTypes::writeOptional($out, $this->position, CommonTypes::putVector3(...));
			CommonTypes::writeOptional($out, $this->dimension, CommonTypes::putString(...));
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			CommonTypes::putVector3($out, $this->position ?? new Vector3(0, 0, 0));
			CommonTypes::putString($out, $this->dimension ?? "");
		}
	}
}
