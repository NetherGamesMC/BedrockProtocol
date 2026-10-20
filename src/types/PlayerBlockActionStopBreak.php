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

use pmmp\encoding\ByteBufferWriter;

/**
 * Legacy (< 1.26.40) representation of a STOP_BREAK block action, which carried no block info.
 *
 * @deprecated Use {@link PlayerBlockAction::stopBreak()} instead. PlayerAuthInputPacket no longer uses this class.
 */
final class PlayerBlockActionStopBreak{

	public function toBlockAction() : PlayerBlockAction{
		return PlayerBlockAction::stopBreak();
	}

	public function getActionType() : int{
		return PlayerAction::STOP_BREAK;
	}

	public function write(ByteBufferWriter $out) : void{
		//NOOP
	}
}
