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

namespace pocketmine\network\mcpe\protocol\types\furnace;

use pocketmine\network\mcpe\protocol\types\PacketIntEnumTrait;

/**
 * Spec name: FurnaceLeftTabIndex
 */
enum FurnaceLeftTab : int{
	use PacketIntEnumTrait;

	case NONE = 0;
	case RECIPE_FOOD = 1;
	case RECIPE_ITEMS = 2;
	case RECIPE_BLOCKS = 3;
	case RECIPE_SEARCH = 4;
	case INVENTORY = 5;
}
