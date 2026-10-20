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
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\sound\SoundDataEvent;

class ClientboundUpdateSoundDataPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_UPDATE_SOUND_DATA_PACKET;

	private int $serverSoundHandle;
	/** >= ProtocolInfo::PROTOCOL_1_26_40 */
	private ?SoundDataEvent $stopEvent = null;
	/** >= ProtocolInfo::PROTOCOL_1_26_40 */
	private ?SoundDataEvent $volumeEvent = null;
	/** >= ProtocolInfo::PROTOCOL_1_26_40 */
	private ?SoundDataEvent $pitchEvent = null;
	/** >= ProtocolInfo::PROTOCOL_1_26_40 */
	private ?SoundDataEvent $fadeEvent = null;
	/** >= ProtocolInfo::PROTOCOL_1_26_40 */
	private ?SoundDataEvent $seekToEvent = null;
	/** >= ProtocolInfo::PROTOCOL_1_26_40 */
	private ?SoundDataEvent $pauseEvent = null;
	/** >= ProtocolInfo::PROTOCOL_1_26_40 */
	private ?SoundDataEvent $resumeEvent = null;
	/**
	 * <= ProtocolInfo::PROTOCOL_1_26_30
	 * The 1.26.30 layout carried a single sound event string instead of the structured events above. There is no
	 * known mapping between the two, so each is only used for the protocols that have it.
	 */
	private string $soundEvent = "";

	/**
	 * @generate-create-func
	 */
	private static function internalCreate(
		int $serverSoundHandle,
		?SoundDataEvent $stopEvent,
		?SoundDataEvent $volumeEvent,
		?SoundDataEvent $pitchEvent,
		?SoundDataEvent $fadeEvent,
		?SoundDataEvent $seekToEvent,
		?SoundDataEvent $pauseEvent,
		?SoundDataEvent $resumeEvent,
		string $soundEvent,
	) : self{
		$result = new self;
		$result->serverSoundHandle = $serverSoundHandle;
		$result->stopEvent = $stopEvent;
		$result->volumeEvent = $volumeEvent;
		$result->pitchEvent = $pitchEvent;
		$result->fadeEvent = $fadeEvent;
		$result->seekToEvent = $seekToEvent;
		$result->pauseEvent = $pauseEvent;
		$result->resumeEvent = $resumeEvent;
		$result->soundEvent = $soundEvent;
		return $result;
	}

	/**
	 * @param string $soundEvent only used for protocols <= 1.26.30
	 */
	public static function create(
		int $serverSoundHandle,
		?SoundDataEvent $stopEvent,
		?SoundDataEvent $volumeEvent,
		?SoundDataEvent $pitchEvent,
		?SoundDataEvent $fadeEvent,
		?SoundDataEvent $seekToEvent,
		?SoundDataEvent $pauseEvent,
		?SoundDataEvent $resumeEvent,
		string $soundEvent = "",
	) : self{
		return self::internalCreate($serverSoundHandle, $stopEvent, $volumeEvent, $pitchEvent, $fadeEvent, $seekToEvent, $pauseEvent, $resumeEvent, $soundEvent);
	}

	/**
	 * Creates a packet using the 1.26.30 layout (a single sound event string). Protocols >= 1.26.40 will receive no
	 * events.
	 */
	public static function createLegacy(int $serverSoundHandle, string $soundEvent) : self{
		return self::internalCreate($serverSoundHandle, null, null, null, null, null, null, null, $soundEvent);
	}

	public function getServerSoundHandle() : int{ return $this->serverSoundHandle; }

	public function getStopEvent() : ?SoundDataEvent{ return $this->stopEvent; }

	public function getVolumeEvent() : ?SoundDataEvent{ return $this->volumeEvent; }

	public function getPitchEvent() : ?SoundDataEvent{ return $this->pitchEvent; }

	public function getFadeEvent() : ?SoundDataEvent{ return $this->fadeEvent; }

	public function getSeekToEvent() : ?SoundDataEvent{ return $this->seekToEvent; }

	public function getPauseEvent() : ?SoundDataEvent{ return $this->pauseEvent; }

	public function getResumeEvent() : ?SoundDataEvent{ return $this->resumeEvent; }

	/** <= ProtocolInfo::PROTOCOL_1_26_30 */
	public function getSoundEvent() : string{ return $this->soundEvent; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->serverSoundHandle = LE::readUnsignedLong($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->stopEvent = CommonTypes::readOptional($in, SoundDataEvent::read(...));
			$this->volumeEvent = CommonTypes::readOptional($in, SoundDataEvent::read(...));
			$this->pitchEvent = CommonTypes::readOptional($in, SoundDataEvent::read(...));
			$this->fadeEvent = CommonTypes::readOptional($in, SoundDataEvent::read(...));
			$this->seekToEvent = CommonTypes::readOptional($in, SoundDataEvent::read(...));
			$this->pauseEvent = CommonTypes::readOptional($in, SoundDataEvent::read(...));
			$this->resumeEvent = CommonTypes::readOptional($in, SoundDataEvent::read(...));
		}else{
			$this->soundEvent = CommonTypes::getString($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		LE::writeUnsignedLong($out, $this->serverSoundHandle);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeOptional($out, $this->stopEvent, fn(ByteBufferWriter $out, SoundDataEvent $data) => $data->write($out));
			CommonTypes::writeOptional($out, $this->volumeEvent, fn(ByteBufferWriter $out, SoundDataEvent $data) => $data->write($out));
			CommonTypes::writeOptional($out, $this->pitchEvent, fn(ByteBufferWriter $out, SoundDataEvent $data) => $data->write($out));
			CommonTypes::writeOptional($out, $this->fadeEvent, fn(ByteBufferWriter $out, SoundDataEvent $data) => $data->write($out));
			CommonTypes::writeOptional($out, $this->seekToEvent, fn(ByteBufferWriter $out, SoundDataEvent $data) => $data->write($out));
			CommonTypes::writeOptional($out, $this->pauseEvent, fn(ByteBufferWriter $out, SoundDataEvent $data) => $data->write($out));
			CommonTypes::writeOptional($out, $this->resumeEvent, fn(ByteBufferWriter $out, SoundDataEvent $data) => $data->write($out));
		}else{
			CommonTypes::putString($out, $this->soundEvent);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleClientboundUpdateSoundData($this);
	}
}
