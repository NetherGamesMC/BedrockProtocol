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

class MapTrackedObject{
	public const TYPE_ENTITY = 0;
	public const TYPE_BLOCK = 1;
	public const TYPE_OTHER = 2;

	public int $type;

	/** Only set if is TYPE_ENTITY */
	public ?int $actorUniqueId = null;

	/** Only set if is TYPE_BLOCK */
	public ?BlockPosition $blockPosition = null;
}
