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
namespace pocketmine\network\mcpe\protocol\types\recipe;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class MolangItemDescriptor implements ItemDescriptor{

	public function __construct(
		private string $molangExpression,
		private int $molangVersion
	){}

	public function getDescriptorType() : ItemDescriptorType{
		return ItemDescriptorType::MOLANG;
	}

	public function getMolangExpression() : string{ return $this->molangExpression; }

	public function getMolangVersion() : int{ return $this->molangVersion; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$expression = CommonTypes::getString($in);
		$version = $protocolId >= ProtocolInfo::PROTOCOL_1_26_40 ? LE::readUnsignedShort($in) : Byte::readUnsigned($in);

		return new self($expression, $version);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->molangExpression);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			LE::writeUnsignedShort($out, $this->molangVersion);
		}else{
			Byte::writeUnsigned($out, $this->molangVersion);
		}
	}
}
