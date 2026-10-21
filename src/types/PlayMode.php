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

/**
 * Enum used by PlayerAuthInputPacket. Most of these names don't make any sense, but that isn't surprising.
 */
enum PlayMode : int{
	use PacketIntEnumTrait;

	case NORMAL = 0;
	case TEASER = 1;
	case SCREEN = 2;
	case VIEWER = 3;
	/**
	 * @deprecated 1.21.120
	 */
	case REALITY = 4;
	/**
	 * @deprecated 1.21.120
	 */
	case PLACEMENT = 5;
	/**
	 * @deprecated 1.21.120
	 */
	case LIVING_ROOM = 6;
	case EXIT_LEVEL = 7;
	/**
	 * @deprecated 1.21.120
	 */
	case EXIT_LEVEL_LIVING_ROOM = 8;

	/**
	 * Fork alias kept for BC with the former int constant name.
	 * @deprecated 1.21.120
	 */
	public const VR = self::REALITY;
}
