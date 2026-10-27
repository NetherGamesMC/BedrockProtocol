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

namespace pocketmine\network\mcpe\protocol\types\inventory\stackrequest;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;

/**
 * Spec name: ItemStackRequestReservedAction
 */
final class CraftReservedStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CRAFTING_RESERVED;

	public function __construct(
		private string $reservedId,
		private int $numCrafts
	){}

	public function getReservedId() : string{ return $this->reservedId; }

	public function getNumCrafts() : int{ return $this->numCrafts; }

	public static function read(ByteBufferReader $in) : self{
		$reservedId = CommonTypes::getString($in);
		$numCrafts = Byte::readUnsigned($in);
		return new self($reservedId, $numCrafts);
	}

	public function write(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->reservedId);
		Byte::writeUnsigned($out, $this->numCrafts);
	}
}
