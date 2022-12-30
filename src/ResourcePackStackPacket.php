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

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackStackEntry;
use function count;

class ResourcePackStackPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACK_STACK_PACKET;

	/** @var ResourcePackStackEntry[] */
	public array $resourcePackStack = [];
	/** @var ResourcePackStackEntry[] */
	public array $behaviorPackStack = [];
	public bool $mustAccept = false;
	public string $baseGameVersion = ProtocolInfo::MINECRAFT_VERSION_NETWORK;
	public Experiments $experiments;

	/**
	 * @generate-create-func
	 * @param ResourcePackStackEntry[] $resourcePackStack
	 * @param ResourcePackStackEntry[] $behaviorPackStack
	 */
	public static function create(array $resourcePackStack, array $behaviorPackStack, bool $mustAccept, string $baseGameVersion, Experiments $experiments) : self{
		$result = new self;
		$result->resourcePackStack = $resourcePackStack;
		$result->behaviorPackStack = $behaviorPackStack;
		$result->mustAccept = $mustAccept;
		$result->baseGameVersion = $baseGameVersion;
		$result->experiments = $experiments;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->mustAccept = $in->getBool();
		$behaviorPackCount = $in->getUnsignedVarInt();
		while($behaviorPackCount-- > 0){
			$this->behaviorPackStack[] = ResourcePackStackEntry::read($in);
		}

		$resourcePackCount = $in->getUnsignedVarInt();
		while($resourcePackCount-- > 0){
			$this->resourcePackStack[] = ResourcePackStackEntry::read($in);
		}

		if($in->getProtocolId() < ProtocolInfo::PROTOCOL_1_16_100){
			$in->getBool(); // isExperimental
		}
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_13_0){
			$this->baseGameVersion = $in->getString();
		}
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_100){
			$this->experiments = Experiments::read($in);
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putBool($this->mustAccept);

		$out->putUnsignedVarInt(count($this->behaviorPackStack));
		foreach($this->behaviorPackStack as $entry){
			$entry->write($out);
		}

		$out->putUnsignedVarInt(count($this->resourcePackStack));
		foreach($this->resourcePackStack as $entry){
			$entry->write($out);
		}

		if($out->getProtocolId() < ProtocolInfo::PROTOCOL_1_16_100){
			$out->putBool(count($this->experiments->getExperiments()) > 0);
		}
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_13_0){
			$out->putString($this->baseGameVersion);
		}
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_100){
			$this->experiments->write($out);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleResourcePackStack($this);
	}
}
