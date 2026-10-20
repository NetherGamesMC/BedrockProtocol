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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * Only used by protocols older than 1.26.40.
 */
final class ComplexAliasItemDescriptor implements ItemDescriptor{

	public function __construct(
		private string $alias
	){}

	public function getDescriptorType() : ItemDescriptorType{
		return ItemDescriptorType::COMPLEX_ALIAS;
	}

	public function getAlias() : string{ return $this->alias; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$alias = CommonTypes::getString($in);

		return new self($alias);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->alias);
	}
}
