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

	/** 1.26.40 - 1.26.50: BDS binds one event member under 7 field names (Stop to Resume) */
	private const EVENT_COPIES = 7;

	private int $serverSoundHandle;
	/** >= ProtocolInfo::PROTOCOL_1_26_40 */
	private ?SoundDataEvent $event = null;
	/**
	 * <= ProtocolInfo::PROTOCOL_1_26_30
	 * The 1.26.30 layout carried a single sound event string instead of the structured event above. There is no
	 * known mapping between the two, so each is only used for the protocols that have it.
	 */
	private string $soundEvent = "";

	/**
	 * @generate-create-func
	 */
	private static function internalCreate(int $serverSoundHandle, ?SoundDataEvent $event, string $soundEvent) : self{
		$result = new self;
		$result->serverSoundHandle = $serverSoundHandle;
		$result->event = $event;
		$result->soundEvent = $soundEvent;
		return $result;
	}

	/**
	 * @param string $soundEvent only used for protocols <= 1.26.30
	 */
	public static function create(int $serverSoundHandle, SoundDataEvent $event, string $soundEvent = "") : self{
		return self::internalCreate($serverSoundHandle, $event, $soundEvent);
	}

	/**
	 * Creates a packet using the 1.26.30 layout (a single sound event string). It cannot be encoded for
	 * protocols >= 1.26.40, which require a structured event.
	 */
	public static function createLegacy(int $serverSoundHandle, string $soundEvent) : self{
		return self::internalCreate($serverSoundHandle, null, $soundEvent);
	}

	public function getServerSoundHandle() : int{ return $this->serverSoundHandle; }

	/** >= ProtocolInfo::PROTOCOL_1_26_40; null when decoded from an older protocol */
	public function getEvent() : ?SoundDataEvent{ return $this->event; }

	/** <= ProtocolInfo::PROTOCOL_1_26_30 */
	public function getSoundEvent() : string{ return $this->soundEvent; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->serverSoundHandle = LE::readUnsignedLong($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			$this->event = SoundDataEvent::read($in, $protocolId);
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			for($i = 0; $i < self::EVENT_COPIES; ++$i){
				$this->event = SoundDataEvent::read($in, $protocolId);
			}
		}else{
			$this->soundEvent = CommonTypes::getString($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		LE::writeUnsignedLong($out, $this->serverSoundHandle);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$event = $this->event ?? throw new \InvalidArgumentException("event must be set for protocols >= 1.26.40");
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
				$event->write($out, $protocolId);
			}else{
				for($i = 0; $i < self::EVENT_COPIES; ++$i){
					$event->write($out, $protocolId);
				}
			}
		}else{
			CommonTypes::putString($out, $this->soundEvent);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleClientboundUpdateSoundData($this);
	}
}
