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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;

final class ItemInteractionData{
	/**
	 * Before 1.26.40, the changed slots were only present if the request ID was non-zero. In that case they are decoded
	 * as an array (possibly empty), otherwise as null.
	 *
	 * @param InventoryTransactionChangedSlotsHack[]|null $requestChangedSlots
	 * @phpstan-param list<InventoryTransactionChangedSlotsHack>|null $requestChangedSlots
	 */
	public function __construct(
		private int $requestId,
		private ?array $requestChangedSlots,
		private UseItemTransactionData $transactionData
	){}

	public function getRequestId() : int{
		return $this->requestId;
	}

	/**
	 * @return InventoryTransactionChangedSlotsHack[]|null
	 * @phpstan-return list<InventoryTransactionChangedSlotsHack>|null
	 */
	public function getRequestChangedSlots() : ?array{
		return $this->requestChangedSlots;
	}

	public function getTransactionData() : UseItemTransactionData{
		return $this->transactionData;
	}

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$requestId = VarInt::readSignedInt($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$requestChangedSlots = CommonTypes::readOptional($in, static fn(ByteBufferReader $in) : array => CommonTypes::readList($in, InventoryTransactionChangedSlotsHack::read(...)));
			if($protocolId < ProtocolInfo::PROTOCOL_1_26_50){
				CommonTypes::readDummyOptional($in);
				CommonTypes::readDummyOptional($in);
			}
		}else{
			$requestChangedSlots = $requestId !== 0 ? CommonTypes::readList($in, InventoryTransactionChangedSlotsHack::read(...)) : null;
		}
		$transactionData = new UseItemTransactionData();
		$transactionData->decode($in, $protocolId, authInput: true);
		return new ItemInteractionData($requestId, $requestChangedSlots, $transactionData);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		VarInt::writeSignedInt($out, $this->requestId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeOptional($out, $this->requestChangedSlots, static fn(ByteBufferWriter $out, array $list) => CommonTypes::writeList($out, $list, static fn(ByteBufferWriter $out, InventoryTransactionChangedSlotsHack $v) => $v->write($out)));
			if($protocolId < ProtocolInfo::PROTOCOL_1_26_50){
				CommonTypes::writeDummyOptional($out);
				CommonTypes::writeDummyOptional($out);
			}
		}elseif($this->requestId !== 0){
			CommonTypes::writeList($out, $this->requestChangedSlots ?? [], static fn(ByteBufferWriter $out, InventoryTransactionChangedSlotsHack $v) => $v->write($out));
		}
		$this->transactionData->encode($out, $protocolId, authInput: true);
	}
}
