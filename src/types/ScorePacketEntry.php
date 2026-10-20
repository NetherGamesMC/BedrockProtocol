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

class ScorePacketEntry{
	/*
	 * Legacy (<= 1.26.30) per-entry type IDs. Use $action (ScorePacketEntryAction) instead; SetScorePacket converts
	 * between the two when encoding/decoding older protocols.
	 */
	/** @deprecated use ScorePacketEntryAction::CHANGE_PLAYER */
	public const TYPE_PLAYER = 1;
	/** @deprecated use ScorePacketEntryAction::CHANGE_ENTITY */
	public const TYPE_ENTITY = 2;
	/** @deprecated use ScorePacketEntryAction::CHANGE_FAKE_PLAYER */
	public const TYPE_FAKE_PLAYER = 3;

	public int $scoreboardId;
	/** @var string|null (optional for remove action) */
	public ?string $objectiveName;
	public int $score = 0;
	public ScorePacketEntryAction $action;
	/** @var int|null (if action entity or player) */
	public ?int $actorUniqueId = null;
	/** @var string|null (if action fake player) */
	public ?string $customName = null;
}
