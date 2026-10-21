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
use pocketmine\network\mcpe\protocol\types\ResourcePackClientResponseStatus;
use function count;

class ResourcePackClientResponsePacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACK_CLIENT_RESPONSE_PACKET;

	/** @deprecated use {@link ResourcePackClientResponseStatus::REFUSED} */
	public const STATUS_REFUSED = ResourcePackClientResponseStatus::REFUSED;
	/** @deprecated use {@link ResourcePackClientResponseStatus::SEND_PACKS} */
	public const STATUS_SEND_PACKS = ResourcePackClientResponseStatus::SEND_PACKS;
	/** @deprecated use {@link ResourcePackClientResponseStatus::HAVE_ALL_PACKS} */
	public const STATUS_HAVE_ALL_PACKS = ResourcePackClientResponseStatus::HAVE_ALL_PACKS;
	/** @deprecated use {@link ResourcePackClientResponseStatus::COMPLETED} */
	public const STATUS_COMPLETED = ResourcePackClientResponseStatus::COMPLETED;

	/**
	 * >= 1.26.40 sends the status ordinal (REFUSED = 0 ... COMPLETED = 3). Protocols <= 1.26.30 used the same statuses
	 * shifted up by one (REFUSED = 1 ... COMPLETED = 4).
	 */
	private const LEGACY_STATUS_OFFSET = 1;

	public ResourcePackClientResponseStatus $status;
	/** @var string[] */
	public array $packIds = [];

	/**
	 * @generate-create-func
	 * @param string[] $packIds
	 */
	public static function create(ResourcePackClientResponseStatus $status, array $packIds) : self{
		$result = new self;
		$result->status = $status;
		$result->packIds = $packIds;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->status = ResourcePackClientResponseStatus::fromOrdinal(VarInt::readUnsignedInt($in));
			$innerType = CommonTypes::getString($in);
			if($innerType !== $this->status->value){
				throw new PacketDecodeException("Unexpected inner type $innerType for resource pack client response status {$this->status->name}, expected {$this->status->value}");
			}

			if($this->status === ResourcePackClientResponseStatus::SEND_PACKS){
				$this->packIds = [];
				for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
					$this->packIds[] = CommonTypes::getString($in);
				}
			}
		}else{
			$this->status = ResourcePackClientResponseStatus::fromOrdinal(Byte::readUnsigned($in) - self::LEGACY_STATUS_OFFSET);
			$entryCount = LE::readUnsignedShort($in);
			$this->packIds = [];
			while($entryCount-- > 0){
				$this->packIds[] = CommonTypes::getString($in);
			}
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			VarInt::writeUnsignedInt($out, $this->status->toOrdinal());
			CommonTypes::putString($out, $this->status->value);
			if($this->status === ResourcePackClientResponseStatus::SEND_PACKS){
				VarInt::writeUnsignedInt($out, count($this->packIds));
				foreach($this->packIds as $id){
					CommonTypes::putString($out, $id);
				}
			}
		}else{
			Byte::writeUnsigned($out, $this->status->toOrdinal() + self::LEGACY_STATUS_OFFSET);
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
