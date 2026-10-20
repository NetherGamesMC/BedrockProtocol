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
use pocketmine\color\Color;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class DebugMarkerData{

	public function __construct(
		private string $text,
		private Vector3 $position,
		private Color $color,
		private int $durationMillis
	){}

	public function getText() : string{ return $this->text; }

	public function getPosition() : Vector3{ return $this->position; }

	public function getColor() : Color{ return $this->color; }

	public function getDurationMillis() : int{ return $this->durationMillis; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$text = CommonTypes::getString($in);
		$position = CommonTypes::getVector3($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			$color = new Color(
				(int) (LE::readFloat($in) * 255),
				(int) (LE::readFloat($in) * 255),
				(int) (LE::readFloat($in) * 255),
				(int) (LE::readFloat($in) * 255)
			);
		}else{
			$color = CommonTypes::readColor($in);
		}
		$durationMillis = LE::readUnsignedLong($in);

		return new self(
			$text,
			$position,
			$color,
			$durationMillis
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->text);
		CommonTypes::putVector3($out, $this->position);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			LE::writeFloat($out, $this->color->getR() / 255);
			LE::writeFloat($out, $this->color->getG() / 255);
			LE::writeFloat($out, $this->color->getB() / 255);
			LE::writeFloat($out, $this->color->getA() / 255);
		}else{
			CommonTypes::writeColor($out, $this->color);
		}
		LE::writeUnsignedLong($out, $this->durationMillis);
	}
}
