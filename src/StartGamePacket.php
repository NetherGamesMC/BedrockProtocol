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

use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\BlockPaletteEntry;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\network\mcpe\protocol\types\LegacyBlockPaletteEntry;
use pocketmine\network\mcpe\protocol\types\LevelSettings;
use pocketmine\network\mcpe\protocol\types\PlayerMovementSettings;
use Ramsey\Uuid\UuidInterface;
use function count;

class StartGamePacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::START_GAME_PACKET;

	public int $actorUniqueId;
	public int $actorRuntimeId;
	public int $playerGamemode;

	public Vector3 $playerPosition;

	public float $pitch;
	public float $yaw;

	/** @phpstan-var CacheableNbt<CompoundTag>  */
	public CacheableNbt $playerActorProperties; //same as SyncActorPropertyPacket content

	public LevelSettings $levelSettings;

	public string $levelId = ""; //base64 string, usually the same as world folder name in vanilla
	public string $worldName;
	public string $premiumWorldTemplateId = "";
	public bool $isTrial = false;
	public PlayerMovementSettings $playerMovementSettings;
	public int $currentTick = 0; //only used if isTrial is true
	public int $enchantmentSeed = 0;
	public string $multiplayerCorrelationId = ""; //TODO: this should be filled with a UUID of some sort
	public bool $enableNewInventorySystem = false; //TODO
	public string $serverSoftwareVersion;
	public UuidInterface $worldTemplateId; //why is this here twice ??? mojang
	public bool $enableClientSideChunkGeneration;

	/**
	 * @var BlockPaletteEntry[]
	 * @phpstan-var list<BlockPaletteEntry>
	 */
	public array $blockPalette = [];
	/**
	 * @var LegacyBlockPaletteEntry[]
	 * @phpstan-var list<LegacyBlockPaletteEntry>
	 */
	public array $legacyBlockPalette = [];

	/**
	 * Checksum of the full block palette. This is a hash of some weird stringified version of the NBT.
	 * This is used along with the baseGameVersion to check for inconsistencies in the block palette.
	 * Fill with 0 if you don't want to bother having the client verify the palette (seems pointless anyway).
	 */
	public int $blockPaletteChecksum;

	/**
	 * @var ItemTypeEntry[]
	 * @phpstan-var list<ItemTypeEntry>
	 */
	public array $itemTable;

	/**
	 * @generate-create-func
	 * @param BlockPaletteEntry[]       $blockPalette
	 * @param LegacyBlockPaletteEntry[] $legacyBlockPalette
	 * @param ItemTypeEntry[]           $itemTable
	 * @phpstan-param CacheableNbt<CompoundTag>     $playerActorProperties
	 * @phpstan-param list<BlockPaletteEntry>       $blockPalette
	 * @phpstan-param list<LegacyBlockPaletteEntry> $legacyBlockPalette
	 * @phpstan-param list<ItemTypeEntry>           $itemTable
	 */
	public static function create(
		int $actorUniqueId,
		int $actorRuntimeId,
		int $playerGamemode,
		Vector3 $playerPosition,
		float $pitch,
		float $yaw,
		CacheableNbt $playerActorProperties,
		LevelSettings $levelSettings,
		string $levelId,
		string $worldName,
		string $premiumWorldTemplateId,
		bool $isTrial,
		PlayerMovementSettings $playerMovementSettings,
		int $currentTick,
		int $enchantmentSeed,
		string $multiplayerCorrelationId,
		bool $enableNewInventorySystem,
		string $serverSoftwareVersion,
		UuidInterface $worldTemplateId,
		bool $enableClientSideChunkGeneration,
		array $blockPalette,
		array $legacyBlockPalette,
		int $blockPaletteChecksum,
		array $itemTable,
	) : self{
		$result = new self;
		$result->actorUniqueId = $actorUniqueId;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->playerGamemode = $playerGamemode;
		$result->playerPosition = $playerPosition;
		$result->pitch = $pitch;
		$result->yaw = $yaw;
		$result->playerActorProperties = $playerActorProperties;
		$result->levelSettings = $levelSettings;
		$result->levelId = $levelId;
		$result->worldName = $worldName;
		$result->premiumWorldTemplateId = $premiumWorldTemplateId;
		$result->isTrial = $isTrial;
		$result->playerMovementSettings = $playerMovementSettings;
		$result->currentTick = $currentTick;
		$result->enchantmentSeed = $enchantmentSeed;
		$result->multiplayerCorrelationId = $multiplayerCorrelationId;
		$result->enableNewInventorySystem = $enableNewInventorySystem;
		$result->serverSoftwareVersion = $serverSoftwareVersion;
		$result->worldTemplateId = $worldTemplateId;
		$result->enableClientSideChunkGeneration = $enableClientSideChunkGeneration;
		$result->blockPalette = $blockPalette;
		$result->legacyBlockPalette = $legacyBlockPalette;
		$result->blockPaletteChecksum = $blockPaletteChecksum;
		$result->itemTable = $itemTable;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorUniqueId = $in->getActorUniqueId();
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->playerGamemode = $in->getVarInt();

		$this->playerPosition = $in->getVector3();

		$this->pitch = $in->getLFloat();
		$this->yaw = $in->getLFloat();

		$this->levelSettings = LevelSettings::read($in);

		$this->levelId = $in->getString();
		$this->worldName = $in->getString();
		$this->premiumWorldTemplateId = $in->getString();
		$this->isTrial = $in->getBool();
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_13_0){
			$this->playerMovementSettings = PlayerMovementSettings::read($in);
		}
		$this->currentTick = $in->getLLong();

		$this->enchantmentSeed = $in->getVarInt();

		$this->blockPalette = [];
		$this->legacyBlockPalette = [];
		$this->getEncodedBlockPalette($in);

		$this->itemTable = [];
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$stringId = $in->getString();
			$numericId = $in->getSignedLShort();
			if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_100){
				$isComponentBased = $in->getBool();
			}

			$this->itemTable[] = new ItemTypeEntry($stringId, $numericId, $isComponentBased ?? false);
		}

		$this->multiplayerCorrelationId = $in->getString();
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_0){
			$this->enableNewInventorySystem = $in->getBool();
		}
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_17_0){
			$this->serverSoftwareVersion = $in->getString();
		}
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_19_0){
			$this->playerActorProperties = new CacheableNbt($in->getNbtCompoundRoot());
			$this->blockPaletteChecksum = $in->getLLong();
			$this->worldTemplateId = $in->getUUID();
		}elseif($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_18_0){
			$this->blockPaletteChecksum = $in->getLLong();
		}
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_19_20){
			$this->enableClientSideChunkGeneration = $in->getBool();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorUniqueId($this->actorUniqueId);
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putVarInt($this->playerGamemode);

		$out->putVector3($this->playerPosition);

		$out->putLFloat($this->pitch);
		$out->putLFloat($this->yaw);

		$this->levelSettings->write($out);

		$out->putString($this->levelId);
		$out->putString($this->worldName);
		$out->putString($this->premiumWorldTemplateId);
		$out->putBool($this->isTrial);
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_13_0){
			$this->playerMovementSettings->write($out);
		}
		$out->putLLong($this->currentTick);

		$out->putVarInt($this->enchantmentSeed);

		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_100){
			$out->putUnsignedVarInt(count($this->blockPalette));
			foreach($this->blockPalette as $entry){
				$out->putString($entry->getName());
				$out->put($entry->getStates()->getEncodedNbt());
			}
		}else{
			$this->putEncodedBlockPalette($out);
		}

		$out->putUnsignedVarInt(count($this->itemTable));
		foreach($this->itemTable as $entry){
			$out->putString($entry->getStringId());
			$out->putLShort($entry->getNumericId());
			if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_100){
				$out->putBool($entry->isComponentBased());
			}
		}

		$out->putString($this->multiplayerCorrelationId);
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_0){
			$out->putBool($this->enableNewInventorySystem);
		}
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_17_0){
			$out->putString($this->serverSoftwareVersion);
		}
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_19_0){
			$out->put($this->playerActorProperties->getEncodedNbt());
			$out->putLLong($this->blockPaletteChecksum);
			$out->putUUID($this->worldTemplateId);
		}elseif($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_18_0){
			$out->putLLong($this->blockPaletteChecksum);
		}
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_19_20){
			$out->putBool($this->enableClientSideChunkGeneration);
		}
	}

	private function getEncodedBlockPalette(PacketSerializer $in) : void{
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_13_0){
			if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_100){
				for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
					$blockName = $in->getString();
					$state = $in->getNbtCompoundRoot();
					$this->blockPalette[] = new BlockPaletteEntry($blockName, new CacheableNbt($state));
				}
			}else{
				$blockTable = $in->getNbtRoot()->getTag();
				if(!($blockTable instanceof ListTag)){
					throw new PacketDecodeException("Expected TAG_List NBT root");
				}

				foreach($blockTable->getValue() as $tag){
					$state = $tag->getValue();
					if(!($state instanceof CompoundTag)){
						throw new PacketDecodeException("Expected TAG_Compound NBT state");
					}

					$blockName = $state->getCompoundTag("block");
					if($blockName === null) {
						throw new PacketDecodeException("Expected TAG_Compound NBT block");
					}

					$this->blockPalette[] = new BlockPaletteEntry($blockName->getString("name"), new CacheableNbt($state));
				}
			}
		}else{
			for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
				$name = $in->getString();
				$metadata = $in->getLShort();
				$id = $in->getLShort();
				$this->legacyBlockPalette[] = new LegacyBlockPaletteEntry($name, $id, $metadata);
			}
		}
	}

	private function putEncodedBlockPalette(PacketSerializer $out) : void{
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_13_0){
			$root = new ListTag();
			foreach($this->blockPalette as $entry){
				$root->push($entry->getStates()->getRoot());
			}
			$out->put((new NetworkNbtSerializer())->write(new TreeRoot($root)));
		}else{
			$out->putUnsignedVarInt(count($this->legacyBlockPalette));
			foreach($this->legacyBlockPalette as $entry){
				$out->putString($entry->getName());
				$out->putLShort($entry->getMetadata());
				$out->putLShort($entry->getId());
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleStartGame($this);
	}
}
