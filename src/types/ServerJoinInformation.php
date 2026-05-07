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
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class ServerJoinInformation{

	public function __construct(
		private ?GatheringJoinInfo $gatheringJoinInfo,
		private ?StoreEntryPointInfo $storeEntryPointInfo,
		private ?PresenceInfo $presenceInfo,
	){}

	public function getGatheringJoinInfo() : ?GatheringJoinInfo{ return $this->gatheringJoinInfo; }

	public function getStoreEntryPointInfo() : ?StoreEntryPointInfo{ return $this->storeEntryPointInfo; }

	public function getPresenceInfo() : ?PresenceInfo{ return $this->presenceInfo; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$storeEntryPointInfo = null;

		$gatheringJoinInfo = CommonTypes::readOptional($in, function(ByteBufferReader $in) use ($protocolId, &$storeEntryPointInfo) : GatheringJoinInfo{
			$gatheringJoinInfo = GatheringJoinInfo::read($in, $protocolId);
			if($protocolId <= ProtocolInfo::PROTOCOL_1_26_0){
				$storeId = CommonTypes::getString($in);
				$storeEntryPointInfo = new StoreEntryPointInfo($storeId, "");
			}

			return $gatheringJoinInfo;
		});

		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_10){
			$storeEntryPointInfo = CommonTypes::readOptional($in, StoreEntryPointInfo::read(...));
			$presenceInfo = CommonTypes::readOptional($in, PresenceInfo::read(...));
		}

		return new self(
			$gatheringJoinInfo,
			$storeEntryPointInfo,
			$presenceInfo ?? null,
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::writeOptional($out, $this->gatheringJoinInfo, function(ByteBufferWriter $out, GatheringJoinInfo $info) use ($protocolId) : void{
			$info->write($out, $protocolId);
			if($protocolId <= ProtocolInfo::PROTOCOL_1_26_0){
				CommonTypes::putString($out, $this->storeEntryPointInfo?->getId() ?? "");
			}
		});

		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_10){
			CommonTypes::writeOptional($out, $this->storeEntryPointInfo, fn(ByteBufferWriter $out, StoreEntryPointInfo $info) => $info->write($out));
			CommonTypes::writeOptional($out, $this->presenceInfo, fn(ByteBufferWriter $out, PresenceInfo $info) => $info->write($out));
		}
	}
}
