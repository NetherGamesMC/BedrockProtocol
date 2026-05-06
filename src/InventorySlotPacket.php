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
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

class InventorySlotPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::INVENTORY_SLOT_PACKET;

	public int $windowId;
	public int $inventorySlot;
	public ?FullContainerName $containerName;
	public int $dynamicContainerSize;
	public ?ItemStackWrapper $storage;
	public ItemStackWrapper $item;

	/**
	 * @generate-create-func
	 */
	public static function create(int $windowId, int $inventorySlot, ?FullContainerName $containerName, int $dynamicContainerSize, ?ItemStackWrapper $storage, ItemStackWrapper $item) : self{
		$result = new self;
		$result->windowId = $windowId;
		$result->inventorySlot = $inventorySlot;
		$result->containerName = $containerName;
		$result->dynamicContainerSize = $dynamicContainerSize;
		$result->storage = $storage;
		$result->item = $item;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->windowId = VarInt::readUnsignedInt($in);
		$this->inventorySlot = VarInt::readUnsignedInt($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
			$this->containerName = CommonTypes::readOptional($in, fn(ByteBufferReader $in) => FullContainerName::read($in, $protocolId));
			$this->storage = CommonTypes::readOptional($in, CommonTypes::getNetworkItemStackDescriptor(...));
			$this->item = CommonTypes::getNetworkItemStackDescriptor($in);
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_30){
			$this->containerName = FullContainerName::read($in, $protocolId);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
				$this->storage = CommonTypes::getItemStackWrapper($in);
			}else{
				$this->dynamicContainerSize = VarInt::readUnsignedInt($in);
			}
			$this->item = CommonTypes::getItemStackWrapper($in);
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			$this->containerName = new FullContainerName(0, VarInt::readUnsignedInt($in));
			$this->item = CommonTypes::getItemStackWrapper($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		VarInt::writeUnsignedInt($out, $this->windowId);
		VarInt::writeUnsignedInt($out, $this->inventorySlot);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
			CommonTypes::writeOptional($out, $this->containerName, fn(ByteBufferWriter $out, FullContainerName $v) => $v->write($out, $protocolId));
			CommonTypes::writeOptional($out, $this->storage, CommonTypes::putNetworkItemStackDescriptor(...));
			CommonTypes::putNetworkItemStackDescriptor($out, $this->item);
		}else{
			if($this->containerName === null){
				throw new \InvalidArgumentException("ContainerName must be set for protocol $protocolId");
			}

			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_30){
				$this->containerName->write($out, $protocolId);
				if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
					CommonTypes::putItemStackWrapper($out, $this->storage ?? new ItemStackWrapper(0, ItemStack::null()));
				}else{
					VarInt::writeUnsignedInt($out, $this->dynamicContainerSize);
				}
				CommonTypes::putItemStackWrapper($out, $this->item);
			}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
				VarInt::writeUnsignedInt($out, $this->containerName->getDynamicId() ?? 0);
				CommonTypes::putItemStackWrapper($out, $this->item);
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleInventorySlot($this);
	}
}
