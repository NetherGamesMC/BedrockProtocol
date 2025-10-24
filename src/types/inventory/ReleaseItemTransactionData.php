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

	protected function decodeData(ByteBufferReader $in, int $protocolId) : void{
		$this->actionType = VarInt::readUnsignedInt($in);
		$this->hotbarSlot = VarInt::readSignedInt($in);
		$this->itemInHand = CommonTypes::getItemStackWrapper($in);
		$this->headPosition = CommonTypes::getVector3($in);
	}

	protected function encodeData(ByteBufferWriter $out, int $protocolId) : void{
		VarInt::writeUnsignedInt($out, $this->actionType);
		VarInt::writeSignedInt($out, $this->hotbarSlot);
		CommonTypes::putItemStackWrapper($out, $this->itemInHand);
		CommonTypes::putVector3($out, $this->headPosition);
	}

	/**
	 * @generate-create-func
	 */
	private static function initSelf(int $actionType, int $hotbarSlot, ItemStackWrapper $itemInHand, Vector3 $headPosition) : self{
		$result = new self;
		$result->actionType = $actionType;
		$result->hotbarSlot = $hotbarSlot;
		$result->itemInHand = $itemInHand;
		$result->headPosition = $headPosition;
		return $result;
	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 */
	public static function new(array $actions, int $actionType, int $hotbarSlot, ItemStackWrapper $itemInHand, Vector3 $headPosition) : self{
		$result = self::initSelf($actionType, $hotbarSlot, $itemInHand, $headPosition);
		$result->actions = $actions;
		return $result;
	}
}
