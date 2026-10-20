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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\resourcepacks\BehaviorPackInfoEntry;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackInfoEntry;
use Ramsey\Uuid\UuidInterface;
use function count;

class ResourcePacksInfoPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACKS_INFO_PACKET;

	/**
	 * @var ResourcePackInfoEntry[]
	 * @phpstan-var list<ResourcePackInfoEntry>
	 */
	public array $resourcePackEntries = [];
	/** @var BehaviorPackInfoEntry[] */
	public array $behaviorPackEntries = [];
	public bool $mustAccept = false; //if true, forces client to choose between accepting packs or being disconnected
	public bool $hasAddons = false;
	public bool $hasScripts = false; //if true, causes disconnect for any platform that doesn't support scripts yet
	public bool $forceServerPacks = false;
	/**
	 * @var string[]
	 * @phpstan-var array<string, string>
	 */
	public array $cdnUrls = [];
	private UuidInterface $worldTemplateId;
	private string $worldTemplateVersion;
	private bool $forceDisableVibrantVisuals;

	/**
	 * @generate-create-func
	 * @param ResourcePackInfoEntry[] $resourcePackEntries
	 * @param BehaviorPackInfoEntry[] $behaviorPackEntries
	 * @param string[]                $cdnUrls
	 * @phpstan-param list<ResourcePackInfoEntry> $resourcePackEntries
	 * @phpstan-param array<string, string>       $cdnUrls
	 */
	public static function create(
		array $resourcePackEntries,
		array $behaviorPackEntries,
		bool $mustAccept,
		bool $hasAddons,
		bool $hasScripts,
		bool $forceServerPacks,
		array $cdnUrls,
		UuidInterface $worldTemplateId,
		string $worldTemplateVersion,
		bool $forceDisableVibrantVisuals,
	) : self{
		$result = new self;
		$result->resourcePackEntries = $resourcePackEntries;
		$result->behaviorPackEntries = $behaviorPackEntries;
		$result->mustAccept = $mustAccept;
		$result->hasAddons = $hasAddons;
		$result->hasScripts = $hasScripts;
		$result->forceServerPacks = $forceServerPacks;
		$result->cdnUrls = $cdnUrls;
		$result->worldTemplateId = $worldTemplateId;
		$result->worldTemplateVersion = $worldTemplateVersion;
		$result->forceDisableVibrantVisuals = $forceDisableVibrantVisuals;
		return $result;
	}

	public function getWorldTemplateId() : UuidInterface{ return $this->worldTemplateId; }

	public function getWorldTemplateVersion() : string{ return $this->worldTemplateVersion; }

	/** @deprecated incorrect name */
	public function isForceDisablingVibrantVisuals() : bool{ return $this->forceDisableVibrantVisuals; }

	public function isForceDisableVibrantVisuals() : bool{ return $this->forceDisableVibrantVisuals; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->mustAccept = CommonTypes::getBool($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_70){
			$this->hasAddons = CommonTypes::getBool($in);
		}
		$this->hasScripts = CommonTypes::getBool($in);
		if($protocolId <= ProtocolInfo::PROTOCOL_1_21_20){
			$this->forceServerPacks = CommonTypes::getBool($in);
			$behaviorPackCount = LE::readUnsignedShort($in);
			while($behaviorPackCount-- > 0){
				$this->behaviorPackEntries[] = BehaviorPackInfoEntry::read($in, $protocolId);
			}
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_50){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_90){
				$this->forceDisableVibrantVisuals = CommonTypes::getBool($in);
			}
			$this->worldTemplateId = CommonTypes::getUUID($in);
			$this->worldTemplateVersion = CommonTypes::getString($in);
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->resourcePackEntries = CommonTypes::readList($in, static fn(ByteBufferReader $in) => ResourcePackInfoEntry::read($in, $protocolId));
		}else{
			$this->resourcePackEntries = [];
			$resourcePackCount = LE::readUnsignedShort($in);
			while($resourcePackCount-- > 0){
				$this->resourcePackEntries[] = ResourcePackInfoEntry::read($in, $protocolId);
			}
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_30 && $protocolId < ProtocolInfo::PROTOCOL_1_21_40){
			$this->cdnUrls = [];
			for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; $i++){
				$packId = CommonTypes::getString($in);
				$cdnUrl = CommonTypes::getString($in);
				$this->cdnUrls[$packId] = $cdnUrl;
			}
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putBool($out, $this->mustAccept);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_70){
			CommonTypes::putBool($out, $this->hasAddons);
		}
		CommonTypes::putBool($out, $this->hasScripts);
		if($protocolId <= ProtocolInfo::PROTOCOL_1_21_20){
			CommonTypes::putBool($out, $this->forceServerPacks);
			LE::writeUnsignedShort($out, count($this->behaviorPackEntries));
			foreach($this->behaviorPackEntries as $entry){
				$entry->write($out, $protocolId);
			}
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_50){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_90){
				CommonTypes::putBool($out, $this->forceDisableVibrantVisuals);
			}
			CommonTypes::putUUID($out, $this->worldTemplateId);
			CommonTypes::putString($out, $this->worldTemplateVersion);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeList($out, $this->resourcePackEntries, static fn(ByteBufferWriter $out, ResourcePackInfoEntry $entry) => $entry->write($out, $protocolId));
		}else{
			LE::writeUnsignedShort($out, count($this->resourcePackEntries));
			foreach($this->resourcePackEntries as $entry){
				$entry->write($out, $protocolId);
			}
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_30 && $protocolId < ProtocolInfo::PROTOCOL_1_21_40){
			VarInt::writeUnsignedInt($out, count($this->cdnUrls));
			foreach($this->cdnUrls as $packId => $cdnUrl){
				CommonTypes::putString($out, $packId);
				CommonTypes::putString($out, $cdnUrl);
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleResourcePacksInfo($this);
	}
}
