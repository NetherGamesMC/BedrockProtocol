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

use pmmp\encoding\ByteBufferWriter;

/**
 * Fork-only legacy base class (removed upstream in 1.26.40): recipes which are sent with a type ID in the single
 * type-tagged recipe list used before 1.26.40. Only FurnaceRecipe still uses it, since it needs the type ID to
 * distinguish ENTRY_FURNACE from ENTRY_FURNACE_DATA.
 */
abstract class RecipeWithTypeId{
	protected function __construct(
		private int $typeId
	){}

	final public function getTypeId() : int{
		return $this->typeId;
	}

	abstract public function encode(ByteBufferWriter $out, int $protocolId) : void;
}
