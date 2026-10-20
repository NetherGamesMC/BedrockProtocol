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

namespace pocketmine\network\mcpe\protocol\types\sound;

use pocketmine\network\mcpe\protocol\types\PacketIntEnumTrait;

enum SoundDataEventType : int{
	use PacketIntEnumTrait;

	case STOP = 0;
	case SET_VOLUME = 1;
	case SET_PITCH = 2;
	case FADE = 3;
	case SEEK_TO = 4;
	case PAUSE = 5;
	case RESUME = 6;
}
