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

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\BlockPosition;

class AnvilDamagePacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::ANVIL_DAMAGE_PACKET;

	private BlockPosition $blockPosition;
	/** <= ProtocolInfo::PROTOCOL_1_26_30 */
	private int $damageAmount = 0;

	/**
	 * @generate-create-func
	 */
	public static function create(BlockPosition $blockPosition, int $damageAmount) : self{
		$result = new self;
		$result->blockPosition = $blockPosition;
		$result->damageAmount = $damageAmount;
		return $result;
	}

	public function getDamageAmount() : int{
		return $this->damageAmount;
	}

	public function getBlockPosition() : BlockPosition{ return $this->blockPosition; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId <= ProtocolInfo::PROTOCOL_1_26_30){
			$this->damageAmount = Byte::readUnsigned($in);
		}
		$this->blockPosition = CommonTypes::getBlockPosition($in, $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId <= ProtocolInfo::PROTOCOL_1_26_30){
			Byte::writeUnsigned($out, $this->damageAmount);
		}
		CommonTypes::putBlockPosition($out, $this->blockPosition, $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleAnvilDamage($this);
	}
}
