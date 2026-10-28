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
use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use Ramsey\Uuid\UuidInterface;
use function count;

class PlayerListPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_LIST_PACKET;

	public const TYPE_REMOVE = 0;
	public const TYPE_ADD = 1;

	private const INNER_TYPES = [
		self::TYPE_ADD => 0,
		self::TYPE_REMOVE => 1,
	];

	/**
	 * <= ProtocolInfo::PROTOCOL_1_26_30: a single type byte for the whole packet, using these (different) values
	 */
	private const LEGACY_TYPE_ADD = 0;
	private const LEGACY_TYPE_REMOVE = 1;

	/** @var PlayerListEntry[] */
	private array $entries = [];

	/**
	 * @generate-create-func
	 * @param PlayerListEntry[] $entries
	 */
	public static function create(array $entries) : self{
		$result = new self;
		$result->entries = $entries;
		return $result;
	}

	/**
	 * @return PlayerListEntry[]
	 */
	public function getEntries() : array{ return $this->entries; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$count = VarInt::readUnsignedInt($in);
			for($i = 0; $i < $count; ++$i){
				$type = VarInt::readUnsignedInt($in);
				$innerType = Byte::readUnsigned($in);
				$expectedInnerType = self::INNER_TYPES[$type] ?? "unknown";
				if($innerType !== $expectedInnerType){
					throw new PacketDecodeException("Unexpected inner type $innerType for player list entry type $type, expected $expectedInnerType");
				}
				if($type === self::TYPE_REMOVE){
					$this->entries[] = PlayerListEntry::createRemovalEntry(CommonTypes::getUUID($in));
				}elseif($type === self::TYPE_ADD){
					$this->entries[] = self::readAdditionEntry($in, $protocolId);
				}else{
					throw new PacketDecodeException("Unknown player list entry type $type");
				}
			}
		}else{
			$type = Byte::readUnsigned($in);
			$count = VarInt::readUnsignedInt($in);
			if($type === self::LEGACY_TYPE_ADD){
				$entries = [];
				for($i = 0; $i < $count; ++$i){
					$entries[] = self::readAdditionEntry($in, $protocolId);
				}
				foreach($entries as $entry){
					$entry->skinData->setTrustedSkinFlag(CommonTypes::getBool($in) ? SkinData::TRUSTED_SKIN_TRUE : SkinData::TRUSTED_SKIN_FALSE);
					$this->entries[] = $entry;
				}
			}elseif($type === self::LEGACY_TYPE_REMOVE){
				for($i = 0; $i < $count; ++$i){
					$this->entries[] = PlayerListEntry::createRemovalEntry(CommonTypes::getUUID($in));
				}
			}else{
				throw new PacketDecodeException("Unknown player list type $type");
			}
		}
	}

	private static function readAdditionEntry(ByteBufferReader $in, int $protocolId) : PlayerListEntry{
		$entry = new PlayerListEntry();
		$entry->type = self::TYPE_ADD;
		$entry->uuid = CommonTypes::getUUID($in);
		$entry->actorUniqueId = CommonTypes::getActorUniqueId($in);
		$entry->username = CommonTypes::getString($in);
		$entry->xboxUserId = CommonTypes::getString($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			$entry->playFabId = CommonTypes::getString($in);
		}
		$entry->platformChatId = CommonTypes::getString($in);
		$entry->buildPlatform = LE::readSignedInt($in);
		$entry->skinData = CommonTypes::getSkin($in, $protocolId);
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_60){
			//before 1.26.60 the PlayFab ID was part of the skin data
			$entry->playFabId = $entry->skinData->getPlayFabId();
		}
		$entry->isTeacher = CommonTypes::getBool($in);
		$entry->isHost = CommonTypes::getBool($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_60){
			$entry->isSubClient = CommonTypes::getBool($in);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_80){
				$entry->color = CommonTypes::readColor($in);
			}
		}

		return $entry;
	}

	/**
	 * Returns the UUID to remove if the given entry is a removal entry, or null if it is an addition entry.
	 */
	private static function getRemovalUuid(PlayerListEntry $entry) : ?UuidInterface{
		return match($entry->type){
			self::TYPE_ADD => null,
			self::TYPE_REMOVE => $entry->uuid,
			default => throw new \InvalidArgumentException("Unknown player list entry type $entry->type"),
		};
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			VarInt::writeUnsignedInt($out, count($this->entries));
			foreach($this->entries as $entry){
				$removalUuid = self::getRemovalUuid($entry);
				$type = $removalUuid !== null ? self::TYPE_REMOVE : self::TYPE_ADD;
				VarInt::writeUnsignedInt($out, $type);
				Byte::writeUnsigned($out, self::INNER_TYPES[$type]);
				if($removalUuid !== null){
					CommonTypes::putUUID($out, $removalUuid);
				}else{
					self::writeAdditionEntry($out, $protocolId, $entry);
				}
			}
		}else{
			$addEntries = [];
			$removalUuids = [];
			foreach($this->entries as $entry){
				$removalUuid = self::getRemovalUuid($entry);
				if($removalUuid !== null){
					$removalUuids[] = $removalUuid;
				}else{
					$addEntries[] = $entry;
				}
			}
			if(count($addEntries) > 0 && count($removalUuids) > 0){
				throw new \InvalidArgumentException("Cannot mix added and removed player list entries in one packet for protocols older than 1.26.40");
			}

			if(count($removalUuids) > 0){
				Byte::writeUnsigned($out, self::LEGACY_TYPE_REMOVE);
				VarInt::writeUnsignedInt($out, count($removalUuids));
				foreach($removalUuids as $uuid){
					CommonTypes::putUUID($out, $uuid);
				}
			}else{
				Byte::writeUnsigned($out, self::LEGACY_TYPE_ADD);
				VarInt::writeUnsignedInt($out, count($addEntries));
				foreach($addEntries as $entry){
					self::writeAdditionEntry($out, $protocolId, $entry);
				}
				foreach($addEntries as $entry){
					CommonTypes::putBool($out, $entry->skinData->getTrustedSkinFlag() !== SkinData::TRUSTED_SKIN_FALSE);
				}
			}
		}
	}

	private static function writeAdditionEntry(ByteBufferWriter $out, int $protocolId, PlayerListEntry $entry) : void{
		CommonTypes::putUUID($out, $entry->uuid);
		CommonTypes::putActorUniqueId($out, $entry->actorUniqueId);
		CommonTypes::putString($out, $entry->username);
		CommonTypes::putString($out, $entry->xboxUserId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			CommonTypes::putString($out, $entry->playFabId !== "" ? $entry->playFabId : $entry->skinData->getPlayFabId());
		}
		CommonTypes::putString($out, $entry->platformChatId);
		LE::writeSignedInt($out, $entry->buildPlatform);
		CommonTypes::putSkin($out, $protocolId, $entry->skinData);
		CommonTypes::putBool($out, $entry->isTeacher);
		CommonTypes::putBool($out, $entry->isHost);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_60){
			CommonTypes::putBool($out, $entry->isSubClient);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_80){
				CommonTypes::writeColor($out, $entry->color ?? new Color(255, 255, 255));
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerList($this);
	}
}
