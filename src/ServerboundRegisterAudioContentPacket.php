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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\sound\AudioContentRegistrationEntry;

final class ServerboundRegisterAudioContentPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::SERVERBOUND_REGISTER_AUDIO_CONTENT_PACKET;

	/**
	 * @var AudioContentRegistrationEntry[]
	 * @phpstan-var list<AudioContentRegistrationEntry>
	 */
	private array $registrations;

	/**
	 * @generate-create-func
	 * @param AudioContentRegistrationEntry[] $registrations
	 * @phpstan-param list<AudioContentRegistrationEntry> $registrations
	 */
	public static function create(array $registrations) : self{
		$result = new self;
		$result->registrations = $registrations;
		return $result;
	}

	/**
	 * @return AudioContentRegistrationEntry[]
	 * @phpstan-return list<AudioContentRegistrationEntry>
	 */
	public function getRegistrations() : array{
		return $this->registrations;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->registrations = CommonTypes::readList($in, AudioContentRegistrationEntry::read(...));
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::writeList($out, $this->registrations, static fn(ByteBufferWriter $out, AudioContentRegistrationEntry $entry) => $entry->write($out));
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleServerboundRegisterAudioContent($this);
	}
}
