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
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use Ramsey\Uuid\UuidInterface;
use function count;

class CraftingEventPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::CRAFTING_EVENT_PACKET;

	public int $windowId;
	public int $windowType;
	public UuidInterface $recipeUUID;
	/** @var ItemStackWrapper[] */
	public array $input = [];
	/** @var ItemStackWrapper[] */
	public array $output = [];

	/**
	 * @generate-create-func
	 * @param ItemStackWrapper[] $input
	 * @param ItemStackWrapper[] $output
	 */
	public static function create(int $windowId, int $windowType, UuidInterface $recipeUUID, array $input, array $output) : self{
		$result = new self;
		$result->windowId = $windowId;
		$result->windowType = $windowType;
		$result->recipeUUID = $recipeUUID;
		$result->input = $input;
		$result->output = $output;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->windowId = Byte::readUnsigned($in);
		$this->windowType = VarInt::readSignedInt($in);
		$this->recipeUUID = CommonTypes::getUUID($in);

		$size = VarInt::readUnsignedInt($in);
		for($i = 0; $i < $size and $i < 128; ++$i){
			$this->input[] = CommonTypes::getItemStackWrapper($in, $protocolId);
		}

		$size = VarInt::readUnsignedInt($in);
		for($i = 0; $i < $size and $i < 128; ++$i){
			$this->output[] = CommonTypes::getItemStackWrapper($in, $protocolId);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		Byte::writeUnsigned($out, $this->windowId);
		VarInt::writeSignedInt($out, $this->windowType);
		CommonTypes::putUUID($out, $this->recipeUUID);

		VarInt::writeUnsignedInt($out, count($this->input));
		foreach($this->input as $item){
			CommonTypes::putItemStackWrapper($out, $protocolId, $item);
		}

		VarInt::writeUnsignedInt($out, count($this->output));
		foreach($this->output as $item){
			CommonTypes::putItemStackWrapper($out, $protocolId, $item);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleCraftingEvent($this);
	}
}
