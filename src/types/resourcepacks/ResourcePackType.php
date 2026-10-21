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

namespace pocketmine\network\mcpe\protocol\types\resourcepacks;

use pocketmine\network\mcpe\protocol\types\PacketIntEnumTrait;

enum ResourcePackType : int{
	use PacketIntEnumTrait;

	case INVALID = 0;
	case ADDON = 1;
	case CACHED = 2;
	case COPY_PROTECTED = 3;
	case BEHAVIORS = 4;
	case PERSONA_PIECE = 5;
	case RESOURCES = 6;
	case SKINS = 7;
	case WORLD_TEMPLATE = 8;
}
