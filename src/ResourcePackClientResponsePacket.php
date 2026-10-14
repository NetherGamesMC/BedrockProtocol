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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\ResourcePackClientResponseStatus;
use function count;

class ResourcePackClientResponsePacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACK_CLIENT_RESPONSE_PACKET;

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

	protected function decodePayload(ByteBufferReader $in) : void{
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
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, $this->status->toOrdinal());
		CommonTypes::putString($out, $this->status->value);
		if($this->status === ResourcePackClientResponseStatus::SEND_PACKS){
			VarInt::writeUnsignedInt($out, count($this->packIds));
			foreach($this->packIds as $id){
				CommonTypes::putString($out, $id);
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleResourcePackClientResponse($this);
	}
}
