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
 * @see ClientboundMatchmakingStatePacket
 */
enum MatchmakingState : string{
	use PacketOrdinalEnumTrait;

	case IDLE = "Idle";
	case MATCHMAKING = "Matchmaking";
	case MATCH_FOUND = "MatchFound";
	case CANCELED = "Canceled";
	case PLAYER_LEFT_PARTY = "PlayerLeftParty";
	case PLAYER_LEFT_SERVER = "PlayerLeftServer";
	case SERVER_SHUTDOWN = "ServerShutdown";
	case TIMED_OUT = "TimedOut";
	case REQUEUE_AS_PARTY = "RequeueAsParty";
}
