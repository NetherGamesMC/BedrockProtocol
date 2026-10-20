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
use pocketmine\network\mcpe\protocol\types\furnace\FurnaceOptions;
use pocketmine\network\mcpe\protocol\types\furnace\FurnaceType;

final class SetPlayerFurnaceOptionsPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::SET_PLAYER_FURNACE_OPTIONS_PACKET;

	private FurnaceType $furnaceType;
	private FurnaceOptions $furnaceOptions;

	/**
	 * @generate-create-func
	 */
	public static function create(FurnaceType $furnaceType, FurnaceOptions $furnaceOptions) : self{
		$result = new self;
		$result->furnaceType = $furnaceType;
		$result->furnaceOptions = $furnaceOptions;
		return $result;
	}

	public function getFurnaceType() : FurnaceType{ return $this->furnaceType; }

	public function getFurnaceOptions() : FurnaceOptions{ return $this->furnaceOptions; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->furnaceType = FurnaceType::fromPacket(Byte::readUnsigned($in));
		$this->furnaceOptions = FurnaceOptions::read($in);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		Byte::writeUnsigned($out, $this->furnaceType->value);
		$this->furnaceOptions->write($out);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSetPlayerFurnaceOptions($this);
	}
}
