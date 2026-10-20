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
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

class ResourcePackClientResponsePacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACK_CLIENT_RESPONSE_PACKET;

	/*
	 * These hold the >= 1.26.40 values. Protocols <= 1.26.30 used the same statuses shifted up by one
	 * (REFUSED = 1 ... COMPLETED = 4), see LEGACY_STATUS_OFFSET.
	 */
	public const STATUS_REFUSED = 0;
	public const STATUS_SEND_PACKS = 1;
	public const STATUS_HAVE_ALL_PACKS = 2;
	public const STATUS_COMPLETED = 3;

	private const INNER_TYPES = [
		self::STATUS_REFUSED => "cancel",
		self::STATUS_SEND_PACKS => "downloading",
		self::STATUS_HAVE_ALL_PACKS => "downloadingfinished",
		self::STATUS_COMPLETED => "resourcepackstackfinished"
	];

	/** legacy (<= 1.26.30) wire status = status + LEGACY_STATUS_OFFSET */
	private const LEGACY_STATUS_OFFSET = 1;

	public int $status;
	/** @var string[] */
	public array $packIds = [];

	/**
	 * @generate-create-func
	 * @param string[] $packIds
	 */
	public static function create(int $status, array $packIds) : self{
		$result = new self;
		$result->status = $status;
		$result->packIds = $packIds;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->status = VarInt::readUnsignedInt($in);
			$innerType = CommonTypes::getString($in);
			$expectedInnerType = self::INNER_TYPES[$this->status] ?? "unknown";
			if($innerType !== $expectedInnerType){
				throw new PacketDecodeException("Unexpected inner type $innerType for resource pack client response status $this->status, expected $expectedInnerType");
			}

			if($this->status === self::STATUS_SEND_PACKS){
				$this->packIds = [];
				for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
					$this->packIds[] = CommonTypes::getString($in);
				}
			}
		}else{
			$this->status = Byte::readUnsigned($in) - self::LEGACY_STATUS_OFFSET;
			$entryCount = LE::readUnsignedShort($in);
			$this->packIds = [];
			while($entryCount-- > 0){
				$this->packIds[] = CommonTypes::getString($in);
			}
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			VarInt::writeUnsignedInt($out, $this->status);
			if(!isset(self::INNER_TYPES[$this->status])){
				throw new \LogicException("Unknown resource pack client response status $this->status");
			}
			CommonTypes::putString($out, self::INNER_TYPES[$this->status]);
			if($this->status === self::STATUS_SEND_PACKS){
				VarInt::writeUnsignedInt($out, count($this->packIds));
				foreach($this->packIds as $id){
					CommonTypes::putString($out, $id);
				}
			}
		}else{
			Byte::writeUnsigned($out, $this->status + self::LEGACY_STATUS_OFFSET);
			LE::writeUnsignedShort($out, count($this->packIds));
			foreach($this->packIds as $id){
				CommonTypes::putString($out, $id);
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleResourcePackClientResponse($this);
	}
}
