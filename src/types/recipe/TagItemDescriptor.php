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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class TagItemDescriptor implements ItemDescriptor{

	//used to indicate that the item has multiple selectable variants
	private const DEFAULT_META = 32767;

	public function __construct(
		private string $tag,
		private int $meta = self::DEFAULT_META
	){}

	public function getDescriptorType() : ItemDescriptorType{
		return ItemDescriptorType::TAG;
	}

	public function getTag() : string{ return $this->tag; }

	public function getMeta() : int{ return $this->meta; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$tag = CommonTypes::getString($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$meta = VarInt::readSignedInt($in);
		}

		return new self($tag, $meta ?? self::DEFAULT_META);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->tag);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			VarInt::writeSignedInt($out, $this->meta);
		}
	}

	public static function readTagOnly(ByteBufferReader $in) : self{
		return new self(CommonTypes::getString($in));
	}

	public function writeTagOnly(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->tag);
	}
}
