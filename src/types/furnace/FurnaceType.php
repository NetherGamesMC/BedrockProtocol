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

enum FurnaceType : int{
	use PacketIntEnumTrait;

	case NONE = 0;
	case FURNACE = 1;
	case BLAST_FURNACE = 2;
	case SMOKER = 3;
}
