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
use pocketmine\network\mcpe\protocol\types\BlockPosition;

class RecordStartedPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::RECORD_STARTED_PACKET;

	private BlockPosition $blockPosition;
	private int $serverSoundHandle;

	/**
	 * @generate-create-func
	 */
	public static function create(BlockPosition $blockPosition, int $serverSoundHandle) : self{
		$result = new self;
		$result->blockPosition = $blockPosition;
		$result->serverSoundHandle = $serverSoundHandle;
		return $result;
	}

	public function getBlockPosition() : BlockPosition{ return $this->blockPosition; }

	public function getServerSoundHandle() : int{ return $this->serverSoundHandle; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->blockPosition = CommonTypes::getBlockPosition($in);
		$this->serverSoundHandle = LE::readUnsignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putBlockPosition($out, $this->blockPosition);
		LE::writeUnsignedLong($out, $this->serverSoundHandle);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleRecordStarted($this);
	}
}
