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

enum AgentActionType : int{
	use PacketIntEnumTrait;

	case ATTACK = 1;
	case COLLECT = 2;
	case DESTROY = 3;
	case DETECT_REDSTONE = 4;
	case DETECT_OBSTACLE = 5;
	case DROP = 6;
	case DROP_ALL = 7;
	case INSPECT = 8;
	case INSPECT_DATA = 9;
	case INSPECT_ITEM_COUNT = 10;
	case INSPECT_ITEM_DETAIL = 11;
	case INSPECT_ITEM_SPACE = 12;
	case INTERACT = 13;
	case MOVE = 14;
	case PLACE_BLOCK = 15;
	case TILL = 16;
	case TRANSFER_ITEM_TO = 17;
	case TURN = 18;
}
