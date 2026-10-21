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

enum InputMode : int{
	use PacketIntEnumTrait;

	case UNDEFINED = 0;
	case MOUSE_KEYBOARD = 1;
	case TOUCHSCREEN = 2;
	case GAME_PAD = 3;
	/**
	 * @deprecated 1.21.120
	 */
	case MOTION_CONTROLLER = 4;
}
