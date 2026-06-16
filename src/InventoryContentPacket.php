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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use function count;

class InventoryContentPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::INVENTORY_CONTENT_PACKET;

	public int $windowId;
	/** @var ItemStackWrapper[] */
	public array $items = [];
	public FullContainerName $containerName;
	public int $dynamicContainerSize;
	public ItemStackWrapper $storage;

	/**
	 * @generate-create-func
	 * @param ItemStackWrapper[] $items
	 */
	public static function create(int $windowId, array $items, FullContainerName $containerName, int $dynamicContainerSize, ItemStackWrapper $storage) : self{
		$result = new self;
		$result->windowId = $windowId;
		$result->items = $items;
		$result->containerName = $containerName;
		$result->dynamicContainerSize = $dynamicContainerSize;
		$result->storage = $storage;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->windowId = VarInt::readUnsignedInt($in);
		$count = VarInt::readUnsignedInt($in);
		for($i = 0; $i < $count; ++$i){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
				$this->items[] = CommonTypes::getNetworkItemStackDescriptor($in);
			}else{
				$this->items[] = CommonTypes::getItemStackWrapper($in);
			}
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_30){
			$this->containerName = FullContainerName::read($in, $protocolId);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
				$this->storage = CommonTypes::getNetworkItemStackDescriptor($in);
			}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
				$this->storage = CommonTypes::getItemStackWrapper($in);
			}else{
				$this->dynamicContainerSize = VarInt::readUnsignedInt($in);
			}
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			$this->containerName = new FullContainerName(0, VarInt::readUnsignedInt($in));
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		VarInt::writeUnsignedInt($out, $this->windowId);
		VarInt::writeUnsignedInt($out, count($this->items));
		foreach($this->items as $item){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
				CommonTypes::putNetworkItemStackDescriptor($out, $item);
			}else{
				CommonTypes::putItemStackWrapper($out, $item);
			}
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_30){
			$this->containerName->write($out, $protocolId);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
				CommonTypes::putNetworkItemStackDescriptor($out, $this->storage);
			}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
				CommonTypes::putItemStackWrapper($out, $this->storage);
			}else{
				VarInt::writeUnsignedInt($out, $this->dynamicContainerSize);
			}
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			VarInt::writeUnsignedInt($out, $this->containerName->getDynamicId() ?? 0);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleInventoryContent($this);
	}
}
