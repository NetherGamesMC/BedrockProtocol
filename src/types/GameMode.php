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

enum GameMode : int{
	use PacketIntEnumTrait;

	case SURVIVAL = 0;
	case CREATIVE = 1;
	case ADVENTURE = 2;
	case SURVIVAL_VIEWER = 3;
	case CREATIVE_VIEWER = 4;
	case DEFAULT = 5;
	case SPECTATOR = 6;
}
