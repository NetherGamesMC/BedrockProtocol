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

namespace pocketmine\network\mcpe\protocol;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\types\inventory\CursorItemDragState;

final class ServerboundCursorItemDragPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::SERVERBOUND_CURSOR_ITEM_DRAG_PACKET;

	private CursorItemDragState $state;

	/**
	 * @generate-create-func
	 */
	public static function create(CursorItemDragState $state) : self{
		$result = new self;
		$result->state = $state;
		return $result;
	}

	public function getState() : CursorItemDragState{
		return $this->state;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->state = CursorItemDragState::fromPacket(Byte::readUnsigned($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		Byte::writeUnsigned($out, $this->state->value);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleServerboundCursorItemDrag($this);
	}
}
