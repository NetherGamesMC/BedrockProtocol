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

namespace pocketmine\network\mcpe\protocol\types\inventory;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;

class ReleaseItemTransactionData extends TransactionData{
	use GetTypeIdFromConstTrait;

	public const ID = InventoryTransactionPacket::TYPE_RELEASE_ITEM;

	public const ACTION_RELEASE = 0; //bow shoot
	public const ACTION_CONSUME = 1; //eat food, drink potion

	private int $actionType;
	private int $hotbarSlot;
	private ItemStackWrapper $itemInHand;
	private Vector3 $headPosition;
	private HandSlot $hand = HandSlot::MAINHAND;

	public function getActionType() : int{
		return $this->actionType;
	}

	public function getHotbarSlot() : int{
		return $this->hotbarSlot;
	}

	public function getItemInHand() : ItemStackWrapper{
		return $this->itemInHand;
	}

	public function getHeadPosition() : Vector3{
		return $this->headPosition;
	}

	public function getHand() : HandSlot{ return $this->hand; }

	protected function decodeData(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
			$this->actionType = VarInt::readSignedInt($in);
		}else{
			$this->actionType = VarInt::readUnsignedInt($in);
		}
		$this->hotbarSlot = VarInt::readSignedInt($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
			$this->itemInHand = CommonTypes::getNetworkItemStackDescriptor($in, $protocolId);
		}else{
			$this->itemInHand = CommonTypes::getItemStackWrapper($in, $protocolId);
		}
		$this->headPosition = CommonTypes::getVector3($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			$this->hand = CommonTypes::readHandSlot($in);
		}
	}

	protected function encodeData(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
			VarInt::writeSignedInt($out, $this->actionType);
		}else{
			VarInt::writeUnsignedInt($out, $this->actionType);
		}
		VarInt::writeSignedInt($out, $this->hotbarSlot);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
			CommonTypes::putNetworkItemStackDescriptor($out, $protocolId, $this->itemInHand);
		}else{
			CommonTypes::putItemStackWrapper($out, $protocolId, $this->itemInHand);
		}
		CommonTypes::putVector3($out, $this->headPosition);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			CommonTypes::writeHandSlot($out, $this->hand);
		}
	}

	/**
	 * @generate-create-func
	 */
	private static function initSelf(int $actionType, int $hotbarSlot, ItemStackWrapper $itemInHand, Vector3 $headPosition, HandSlot $hand) : self{
		$result = new self;
		$result->actionType = $actionType;
		$result->hotbarSlot = $hotbarSlot;
		$result->itemInHand = $itemInHand;
		$result->headPosition = $headPosition;
		$result->hand = $hand;
		return $result;
	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 * @phpstan-param list<NetworkInventoryAction> $actions
	 */
	public static function new(array $actions, int $actionType, int $hotbarSlot, ItemStackWrapper $itemInHand, Vector3 $headPosition, HandSlot $hand) : self{
		$result = self::initSelf($actionType, $hotbarSlot, $itemInHand, $headPosition, $hand);
		$result->actions = $actions;
		return $result;
	}
}
