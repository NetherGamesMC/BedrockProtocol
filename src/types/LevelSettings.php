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

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class LevelSettings{

	public int $seed;
	public SpawnSettings $spawnSettings;
	public int $generator = GeneratorType::OVERWORLD;
	public int $worldGamemode;
	public bool $hardcore = false;
	public int $difficulty;
	public BlockPosition $spawnPosition;
	public bool $hasAchievementsDisabled = true;
	public int $editorWorldType = EditorWorldType::NON_EDITOR;
	public bool $createdInEditorMode = false;
	public bool $exportedFromEditorMode = false;
	public int $time = -1;
	public int $eduEditionOffer = EducationEditionOffer::NONE;
	public bool $hasEduFeaturesEnabled = false;
	public string $eduProductUUID = "";
	public float $rainLevel;
	public float $lightningLevel;
	public bool $hasConfirmedPlatformLockedContent = false;
	public bool $isMultiplayerGame = true;
	public bool $hasLANBroadcast = true;
	public int $xboxLiveBroadcastMode = MultiplayerGameVisibility::PUBLIC;
	public int $platformBroadcastMode = MultiplayerGameVisibility::PUBLIC;
	public bool $commandsEnabled;
	public bool $isTexturePacksRequired = true;
	/**
	 * @var GameRule[]
	 * @phpstan-var array<string, GameRule>
	 */
	public array $gameRules = [];
	public Experiments $experiments;
	public bool $hasBonusChestEnabled = false;
	public bool $hasStartWithMapEnabled = false;
	public int $defaultPlayerPermission = PlayerPermissions::MEMBER; //TODO

	public int $serverChunkTickRadius = 4; //TODO (leave as default for now)

	public bool $hasLockedBehaviorPack = false;
	public bool $hasLockedResourcePack = false;
	public bool $isFromLockedWorldTemplate = false;
	public bool $useMsaGamertagsOnly = false;
	public bool $isFromWorldTemplate = false;
	public bool $isWorldTemplateOptionLocked = false;
	public bool $onlySpawnV1Villagers = false;
	public bool $disablePersona = false;
	public bool $disableCustomSkins = false;
	public bool $muteEmoteAnnouncements = false;
	public string $vanillaVersion = ProtocolInfo::MINECRAFT_VERSION_NETWORK;
	public int $limitedWorldWidth = 0;
	public int $limitedWorldLength = 0;
	public bool $isNewNether = true;
	public ?EducationUriResource $eduSharedUriResource = null;
	public ?bool $experimentalGameplayOverride = null;
	public int $chatRestrictionLevel = ChatRestrictionLevel::NONE;
	public bool $disablePlayerInteractions = false;

	/**
	 * @throws DataDecodeException
	 * @throws PacketDecodeException
	 */
	public static function read(ByteBufferReader $in, ServerTelemetryData &$serverTelemetryData, int $protocolId) : self{
		//TODO: in the future we'll use promoted properties + named arguments for decoding, but for now we stick with
		//this shitty way to limit BC breaks (needs more R&D)
		$result = new self;
		$result->internalRead($in, $serverTelemetryData, $protocolId);
		return $result;
	}

	/**
	 * @throws DataDecodeException
	 * @throws PacketDecodeException
	 */
	private function internalRead(ByteBufferReader $in, ServerTelemetryData &$serverTelemetryData, int $protocolId) : void{
		$this->seed = LE::readUnsignedLong($in);
		$this->spawnSettings = SpawnSettings::read($in);
		$this->generator = VarInt::readSignedInt($in);
		$this->worldGamemode = VarInt::readSignedInt($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_80){
			$this->hardcore = CommonTypes::getBool($in);
		}
		$this->difficulty = VarInt::readSignedInt($in);
		$this->spawnPosition = CommonTypes::getBlockPosition($in);
		$this->hasAchievementsDisabled = CommonTypes::getBool($in);
		$this->editorWorldType = $protocolId >= ProtocolInfo::PROTOCOL_1_20_30 ? VarInt::readSignedInt($in) : (CommonTypes::getBool($in) ? EditorWorldType::PROJECT : EditorWorldType::NON_EDITOR);
		$this->createdInEditorMode = CommonTypes::getBool($in);
		$this->exportedFromEditorMode = CommonTypes::getBool($in);
		$this->time = VarInt::readSignedInt($in);
		$this->eduEditionOffer = VarInt::readSignedInt($in);
		$this->hasEduFeaturesEnabled = CommonTypes::getBool($in);
		$this->eduProductUUID = CommonTypes::getString($in);
		$this->rainLevel = LE::readFloat($in);
		$this->lightningLevel = LE::readFloat($in);
		$this->hasConfirmedPlatformLockedContent = CommonTypes::getBool($in);
		$this->isMultiplayerGame = CommonTypes::getBool($in);
		$this->hasLANBroadcast = CommonTypes::getBool($in);
		$this->xboxLiveBroadcastMode = VarInt::readSignedInt($in);
		$this->platformBroadcastMode = VarInt::readSignedInt($in);
		$this->commandsEnabled = CommonTypes::getBool($in);
		$this->isTexturePacksRequired = CommonTypes::getBool($in);
		$this->gameRules = CommonTypes::getGameRules($in, $protocolId, true);
		$this->experiments = Experiments::read($in);
		$this->hasBonusChestEnabled = CommonTypes::getBool($in);
		$this->hasStartWithMapEnabled = CommonTypes::getBool($in);
		$this->defaultPlayerPermission = VarInt::readSignedInt($in);
		$this->serverChunkTickRadius = LE::readSignedInt($in); //doesn't make sense for this to be signed, but that's what the spec says
		$this->hasLockedBehaviorPack = CommonTypes::getBool($in);
		$this->hasLockedResourcePack = CommonTypes::getBool($in);
		$this->isFromLockedWorldTemplate = CommonTypes::getBool($in);
		$this->useMsaGamertagsOnly = CommonTypes::getBool($in);
		$this->isFromWorldTemplate = CommonTypes::getBool($in);
		$this->isWorldTemplateOptionLocked = CommonTypes::getBool($in);
		$this->onlySpawnV1Villagers = CommonTypes::getBool($in);
		$this->disablePersona = CommonTypes::getBool($in);
		$this->disableCustomSkins = CommonTypes::getBool($in);
		$this->muteEmoteAnnouncements = CommonTypes::getBool($in);
		$this->vanillaVersion = CommonTypes::getString($in);
		$this->limitedWorldWidth = LE::readSignedInt($in); //doesn't make sense for this to be signed, but that's what the spec says
		$this->limitedWorldLength = LE::readSignedInt($in); //same as above
		$this->isNewNether = CommonTypes::getBool($in);
		$this->eduSharedUriResource = EducationUriResource::read($in);
		$this->experimentalGameplayOverride = CommonTypes::readOptional($in, CommonTypes::getBool(...));
		$this->chatRestrictionLevel = Byte::readUnsigned($in);
		$this->disablePlayerInteractions = CommonTypes::getBool($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_0 && $protocolId <= ProtocolInfo::PROTOCOL_1_21_130){
			$serverId = CommonTypes::getString($in);
			$worldId = CommonTypes::getString($in);
			$scenarioId = CommonTypes::getString($in);
			$ownerId = $protocolId >= ProtocolInfo::PROTOCOL_1_21_90 ? CommonTypes::getString($in) : "";

			$serverTelemetryData = new ServerTelemetryData($serverId, $scenarioId, $worldId, $ownerId);
		}
	}

	public function write(ByteBufferWriter $out, ServerTelemetryData $serverTelemetryData, int $protocolId) : void{
		LE::writeUnsignedLong($out, $this->seed);
		$this->spawnSettings->write($out);
		VarInt::writeSignedInt($out, $this->generator);
		VarInt::writeSignedInt($out, $this->worldGamemode);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_80){
			CommonTypes::putBool($out, $this->hardcore);
		}
		VarInt::writeSignedInt($out, $this->difficulty);
		CommonTypes::putBlockPosition($out, $this->spawnPosition);
		CommonTypes::putBool($out, $this->hasAchievementsDisabled);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_30){
			VarInt::writeSignedInt($out, $this->editorWorldType);
		}else{
			CommonTypes::putBool($out, $this->editorWorldType !== EditorWorldType::NON_EDITOR);
		}
		CommonTypes::putBool($out, $this->createdInEditorMode);
		CommonTypes::putBool($out, $this->exportedFromEditorMode);
		VarInt::writeSignedInt($out, $this->time);
		VarInt::writeSignedInt($out, $this->eduEditionOffer);
		CommonTypes::putBool($out, $this->hasEduFeaturesEnabled);
		CommonTypes::putString($out, $this->eduProductUUID);
		LE::writeFloat($out, $this->rainLevel);
		LE::writeFloat($out, $this->lightningLevel);
		CommonTypes::putBool($out, $this->hasConfirmedPlatformLockedContent);
		CommonTypes::putBool($out, $this->isMultiplayerGame);
		CommonTypes::putBool($out, $this->hasLANBroadcast);
		VarInt::writeSignedInt($out, $this->xboxLiveBroadcastMode);
		VarInt::writeSignedInt($out, $this->platformBroadcastMode);
		CommonTypes::putBool($out, $this->commandsEnabled);
		CommonTypes::putBool($out, $this->isTexturePacksRequired);
		CommonTypes::putGameRules($out, $protocolId, $this->gameRules, true);
		$this->experiments->write($out);
		CommonTypes::putBool($out, $this->hasBonusChestEnabled);
		CommonTypes::putBool($out, $this->hasStartWithMapEnabled);
		VarInt::writeSignedInt($out, $this->defaultPlayerPermission);
		LE::writeSignedInt($out, $this->serverChunkTickRadius); //doesn't make sense for this to be signed, but that's what the spec says
		CommonTypes::putBool($out, $this->hasLockedBehaviorPack);
		CommonTypes::putBool($out, $this->hasLockedResourcePack);
		CommonTypes::putBool($out, $this->isFromLockedWorldTemplate);
		CommonTypes::putBool($out, $this->useMsaGamertagsOnly);
		CommonTypes::putBool($out, $this->isFromWorldTemplate);
		CommonTypes::putBool($out, $this->isWorldTemplateOptionLocked);
		CommonTypes::putBool($out, $this->onlySpawnV1Villagers);
		CommonTypes::putBool($out, $this->disablePersona);
		CommonTypes::putBool($out, $this->disableCustomSkins);
		CommonTypes::putBool($out, $this->muteEmoteAnnouncements);
		CommonTypes::putString($out, $this->vanillaVersion);
		LE::writeSignedInt($out, $this->limitedWorldWidth); //doesn't make sense for this to be signed, but that's what the spec says
		LE::writeSignedInt($out, $this->limitedWorldLength); //same as above
		CommonTypes::putBool($out, $this->isNewNether);
		($this->eduSharedUriResource ?? new EducationUriResource("", ""))->write($out);
		CommonTypes::writeOptional($out, $this->experimentalGameplayOverride, CommonTypes::putBool(...));
		Byte::writeUnsigned($out, $this->chatRestrictionLevel);
		CommonTypes::putBool($out, $this->disablePlayerInteractions);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_0 && $protocolId <= ProtocolInfo::PROTOCOL_1_21_130){
			CommonTypes::putString($out, $serverTelemetryData->getServerId());
			CommonTypes::putString($out, $serverTelemetryData->getWorldId());
			CommonTypes::putString($out, $serverTelemetryData->getScenarioId());
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_90){
				CommonTypes::putString($out, $serverTelemetryData->getOwnerId());
			}
		}
	}
}
