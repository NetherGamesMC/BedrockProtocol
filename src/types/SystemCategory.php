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

final class SystemCategory{

	public function __construct(
		private string $categoryName,
		private int $systemIndex
	){}

	public function getCategoryName() : string{ return $this->categoryName; }

	public function getSystemIndex() : int{ return $this->systemIndex; }

	public static function read(ByteBufferReader $in) : self{
		$categoryName = CommonTypes::getString($in);
		$systemIndex = LE::readUnsignedLong($in);

		return new self($categoryName, $systemIndex);
	}

	public function write(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->categoryName);
		LE::writeUnsignedLong($out, $this->systemIndex);
	}
}
