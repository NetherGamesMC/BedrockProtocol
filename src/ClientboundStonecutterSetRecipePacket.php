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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class ClientboundStonecutterSetRecipePacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_STONECUTTER_SET_RECIPE_PACKET;

	private int $playerActorUniqueId;
	private int $containerId;
	private int $recipeIndex;

	/**
	 * @generate-create-func
	 */
	public static function create(int $playerActorUniqueId, int $containerId, int $recipeIndex) : self{
		$result = new self;
		$result->playerActorUniqueId = $playerActorUniqueId;
		$result->containerId = $containerId;
		$result->recipeIndex = $recipeIndex;
		return $result;
	}

	public function getPlayerActorUniqueId() : int{ return $this->playerActorUniqueId; }

	public function getContainerId() : int{ return $this->containerId; }

	public function getRecipeIndex() : int{ return $this->recipeIndex; }

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->playerActorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->containerId = Byte::readUnsigned($in);
		$this->recipeIndex = VarInt::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorUniqueId($out, $this->playerActorUniqueId);
		Byte::writeUnsigned($out, $this->containerId);
		VarInt::writeSignedInt($out, $this->recipeIndex);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleClientboundStonecutterSetRecipe($this);
	}
}
