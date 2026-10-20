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

namespace pocketmine\network\mcpe\protocol\types\inventory;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class CreativeGroupEntry{
	public function __construct(
		private int $categoryId,
		private string $categoryName,
		private ItemStack $icon
	){}

	public function getCategoryId() : int{ return $this->categoryId; }

	public function getCategoryName() : string{ return $this->categoryName; }

	public function getIcon() : ItemStack{ return $this->icon; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$categoryId = $protocolId >= ProtocolInfo::PROTOCOL_1_26_40 ? Byte::readUnsigned($in) : LE::readSignedInt($in);
		$categoryName = CommonTypes::getString($in);
		$icon = CommonTypes::getItemStackWithoutStackId($in, $protocolId);
		return new self($categoryId, $categoryName, $icon);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			Byte::writeUnsigned($out, $this->categoryId);
		}else{
			LE::writeSignedInt($out, $this->categoryId);
		}
		CommonTypes::putString($out, $this->categoryName);
		CommonTypes::putItemStackWithoutStackId($out, $protocolId, $this->icon);
	}
}
