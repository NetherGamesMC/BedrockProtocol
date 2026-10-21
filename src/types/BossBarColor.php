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

enum BossBarColor : int{
	use PacketIntEnumTrait;

	case PINK = 0;
	case BLUE = 1;
	case RED = 2;
	case GREEN = 3;
	case YELLOW = 4;
	case PURPLE = 5;
	case REBECCA_PURPLE = 6;
	case WHITE = 7;
}
