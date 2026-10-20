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

/**
 * @see ServerPresenceInfoPacket&ServerJoinInformation
 */
final class PresenceInfo{
	/**
	 * @param string|null $richPresenceId  >= 1.26.30 (optional since 1.26.40; written as "" for 1.26.30 when null)
	 * @param string|null $experienceName  <= 1.26.30 only (removed in 1.26.40; required before 1.26.30)
	 * @param string|null $worldName       <= 1.26.30 only (removed in 1.26.40; required before 1.26.30)
	 */
	public function __construct(
		private ?string $richPresenceId,
		private ?string $experienceName = null,
		private ?string $worldName = null
	){}

	public function getRichPresenceId() : ?string{ return $this->richPresenceId; }

	/** <= ProtocolInfo::PROTOCOL_1_26_30 */
	public function getExperienceName() : ?string{ return $this->experienceName; }

	/** <= ProtocolInfo::PROTOCOL_1_26_30 */
	public function getWorldName() : ?string{ return $this->worldName; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$richPresenceId = CommonTypes::readOptional($in, CommonTypes::getString(...));
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
			$experienceName = CommonTypes::readOptional($in, CommonTypes::getString(...));
			$worldName = CommonTypes::readOptional($in, CommonTypes::getString(...));
			$richPresenceId = CommonTypes::getString($in);
		}else{
			$experienceName = CommonTypes::getString($in);
			$worldName = CommonTypes::getString($in);
		}

		return new self($richPresenceId ?? null, $experienceName ?? null, $worldName ?? null);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeOptional($out, $this->richPresenceId, CommonTypes::putString(...));
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
			CommonTypes::writeOptional($out, $this->experienceName, CommonTypes::putString(...));
			CommonTypes::writeOptional($out, $this->worldName, CommonTypes::putString(...));
			CommonTypes::putString($out, $this->richPresenceId ?? "");
		}else{
			CommonTypes::putString($out, $this->experienceName ?? throw new \InvalidArgumentException("experienceName must be set"));
			CommonTypes::putString($out, $this->worldName ?? throw new \InvalidArgumentException("worldName must be set"));
		}
	}
}
