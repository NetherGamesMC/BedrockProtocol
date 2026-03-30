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

<<<<<<< HEAD
class ServerboundDataDrivenScreenClosedPacket extends DataPacket{
=======
class ServerboundDataDrivenScreenClosedPacket extends DataPacket implements ServerboundPacket{
>>>>>>> upstream/master
	public const NETWORK_ID = ProtocolInfo::SERVERBOUND_DATA_DRIVEN_SCREEN_CLOSED_PACKET;

	private int $formId;
	private string $closeReason;

	/**
	 * @generate-create-func
	 */
	public static function create(int $formId, string $closeReason) : self{
		$result = new self;
		$result->formId = $formId;
		$result->closeReason = $closeReason;
		return $result;
	}

<<<<<<< HEAD
	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
=======
	protected function decodePayload(ByteBufferReader $in) : void{
>>>>>>> upstream/master
		$this->formId = LE::readUnsignedInt($in);
		$this->closeReason = CommonTypes::getString($in);
	}

<<<<<<< HEAD
	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
=======
	protected function encodePayload(ByteBufferWriter $out) : void{
>>>>>>> upstream/master
		LE::writeUnsignedInt($out, $this->formId);
		CommonTypes::putString($out, $this->closeReason);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleServerboundDataDrivenScreenClosed($this);
	}
}
