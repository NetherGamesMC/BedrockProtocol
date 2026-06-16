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
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\TransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use function count;

/**
 * This packet effectively crams multiple packets into one.
 */
class InventoryTransactionPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::INVENTORY_TRANSACTION_PACKET;

	public const TYPE_NORMAL = 0;
	public const TYPE_MISMATCH = 1;
	public const TYPE_USE_ITEM = 2;
	public const TYPE_USE_ITEM_ON_ENTITY = 3;
	public const TYPE_RELEASE_ITEM = 4;

	public int $requestId;
	/** @var InventoryTransactionChangedSlotsHack[] */
	public array $requestChangedSlots;
	public ?TransactionData $trData;

	/**
	 * @generate-create-func
	 * @param InventoryTransactionChangedSlotsHack[] $requestChangedSlots
	 */
	public static function create(int $requestId, array $requestChangedSlots, ?TransactionData $trData) : self{
		$result = new self;
		$result->requestId = $requestId;
		$result->requestChangedSlots = $requestChangedSlots;
		$result->trData = $trData;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->requestId = CommonTypes::readLegacyItemStackRequestId($in);
		$hasChangedSlots = $protocolId >= ProtocolInfo::PROTOCOL_1_26_30 ? CommonTypes::getBool($in) : $this->requestId !== 0;

		$this->requestChangedSlots = [];
		if($hasChangedSlots){
			for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
				$this->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in);
			}
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
			$transactionType = CommonTypes::readOptional($in, VarInt::readUnsignedInt(...));
		}else{
			$transactionType = VarInt::readUnsignedInt($in);
		}
		$this->trData = match($transactionType){
			null => null,
			NormalTransactionData::ID => new NormalTransactionData(),
			MismatchTransactionData::ID => new MismatchTransactionData(),
			UseItemTransactionData::ID => new UseItemTransactionData(),
			UseItemOnEntityTransactionData::ID => new UseItemOnEntityTransactionData(),
			ReleaseItemTransactionData::ID => new ReleaseItemTransactionData(),
			default => throw new PacketDecodeException("Unknown transaction type $transactionType"),
		};

		$this->trData?->decode($in, $protocolId);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::writeLegacyItemStackRequestId($out, $this->requestId);
		$hasChangedSlots = $this->requestId !== 0;

		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
			CommonTypes::putBool($out, $hasChangedSlots);
		}
		if($hasChangedSlots){
			VarInt::writeUnsignedInt($out, count($this->requestChangedSlots));
			foreach($this->requestChangedSlots as $changedSlots){
				$changedSlots->write($out);
			}
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
			CommonTypes::writeOptional($out, $this->trData?->getTypeId(), VarInt::writeUnsignedInt(...));
		}else{
			VarInt::writeUnsignedInt($out, $this->trData?->getTypeId() ?? self::TYPE_NORMAL);
		}

		$this->trData?->encode($out, $protocolId);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleInventoryTransaction($this);
	}
}
