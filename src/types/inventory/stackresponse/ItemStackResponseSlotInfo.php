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

namespace pocketmine\network\mcpe\protocol\types\inventory\stackresponse;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class ItemStackResponseSlotInfo{
	public function __construct(
		private int $slot,
		private int $hotbarSlot,
		private int $count,
		private ?int $itemStackId,
		private string $customName,
		private ?string $filteredCustomName,
		private int $durabilityCorrection
	){}

	public function getSlot() : int{ return $this->slot; }

	public function getHotbarSlot() : int{ return $this->hotbarSlot; }

	public function getCount() : int{ return $this->count; }

	/**
	 * Optional since 1.26.40. Older protocols always send it (null is written as 0).
	 */
	public function getItemStackId() : ?int{ return $this->itemStackId; }

	public function getCustomName() : string{ return $this->customName; }

	/**
	 * Optional since 1.26.40.
	 */
	public function getFilteredCustomName() : ?string{ return $this->filteredCustomName; }

	public function getDurabilityCorrection() : int{ return $this->durabilityCorrection; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$slot = Byte::readUnsigned($in);
		$hotbarSlot = Byte::readUnsigned($in);
		$count = Byte::readUnsigned($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$itemStackId = CommonTypes::readDoubleOptional($in, CommonTypes::readServerItemStackId(...));
		}else{
			$itemStackId = CommonTypes::readServerItemStackId($in);
		}
		$customName = CommonTypes::getString($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$filteredCustomName = CommonTypes::readOptional($in, CommonTypes::getString(...));
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_50){
			$filteredCustomName = CommonTypes::getString($in);
		}else{
			$filteredCustomName = $customName;
		}
		$durabilityCorrection = VarInt::readSignedInt($in);
		return new self($slot, $hotbarSlot, $count, $itemStackId, $customName, $filteredCustomName, $durabilityCorrection);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		Byte::writeUnsigned($out, $this->slot);
		Byte::writeUnsigned($out, $this->hotbarSlot);
		Byte::writeUnsigned($out, $this->count);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeDoubleOptional($out, $this->itemStackId, CommonTypes::writeServerItemStackId(...));
		}else{
			CommonTypes::writeServerItemStackId($out, $this->itemStackId ?? 0);
		}
		CommonTypes::putString($out, $this->customName);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeOptional($out, $this->filteredCustomName, CommonTypes::putString(...));
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_50){
			CommonTypes::putString($out, $this->filteredCustomName ?? "");
		}
		VarInt::writeSignedInt($out, $this->durabilityCorrection);
	}
}
