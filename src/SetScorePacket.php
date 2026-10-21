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
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntryAction;
use function count;

class SetScorePacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::SET_SCORE_PACKET;

	/*
	 * Legacy (<= 1.26.30) packet-level type. Since 1.26.40 every entry carries its own ScorePacketEntryAction; for
	 * older protocols the packet type is derived from the entries' actions (which therefore must not mix REMOVE with
	 * CHANGE_* actions).
	 */
	/** @deprecated use ScorePacketEntryAction on each entry */
	public const TYPE_CHANGE = 0;
	/** @deprecated use ScorePacketEntryAction::REMOVE on each entry */
	public const TYPE_REMOVE = 1;

	/**
	 * @var ScorePacketEntry[]
	 * @phpstan-var list<ScorePacketEntry>
	 */
	private array $entries = [];

	/**
	 * @generate-create-func
	 * @param ScorePacketEntry[] $entries
	 * @phpstan-param list<ScorePacketEntry> $entries
	 */
	public static function create(array $entries) : self{
		$result = new self;
		$result->entries = $entries;
		return $result;
	}

	/**
	 * @return ScorePacketEntry[]
	 * @phpstan-return list<ScorePacketEntry>
	 */
	public function getEntries() : array{ return $this->entries; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->entries = CommonTypes::readList($in, function(ByteBufferReader $in) use ($protocolId) : ScorePacketEntry{
				$action = ScorePacketEntryAction::fromOrdinal(VarInt::readUnsignedInt($in));
				$innerType = CommonTypes::getString($in);
				if($action !== ScorePacketEntryAction::fromPacket($innerType)){
					throw new PacketDecodeException("Expected inner type {$action->value} for score packet entry ordinal {$action->toOrdinal()}, got $innerType");
				}

				$entry = new ScorePacketEntry();
				$entry->action = $action;

				//same for all types
				$entry->scoreboardId = VarInt::readSignedLong($in);

				if($action === ScorePacketEntryAction::REMOVE){
					$entry->objectiveName = $protocolId === ProtocolInfo::PROTOCOL_1_26_44 ?
						CommonTypes::readDoubleOptional($in, CommonTypes::getString(...)) :
						CommonTypes::readOptional($in, CommonTypes::getString(...));
				}elseif($action === ScorePacketEntryAction::CHANGE_PLAYER || $action === ScorePacketEntryAction::CHANGE_ENTITY){
					$entry->objectiveName = CommonTypes::getString($in);
					$entry->score = LE::readSignedInt($in);
					$entry->actorUniqueId = CommonTypes::getActorUniqueId($in);
				}elseif($action === ScorePacketEntryAction::CHANGE_FAKE_PLAYER){
					$entry->objectiveName = CommonTypes::getString($in);
					$entry->score = LE::readSignedInt($in);
					$entry->customName = CommonTypes::getString($in);
				}else{ // this should never be the case
					throw new \LogicException("Unhandled decode for action: " . $action->name);
				}
				return $entry;
			});
		}else{
			$this->entries = [];
			$type = Byte::readUnsigned($in);
			for($i = 0, $i2 = VarInt::readUnsignedInt($in); $i < $i2; ++$i){
				$entry = new ScorePacketEntry();
				$entry->scoreboardId = VarInt::readSignedLong($in);
				$entry->objectiveName = CommonTypes::getString($in);
				$entry->score = LE::readSignedInt($in);
				if($type !== self::TYPE_REMOVE){
					$entryType = Byte::readUnsigned($in);
					$entry->action = self::actionFromLegacyEntryType($entryType) ?? throw new PacketDecodeException("Unknown entry type $entryType");
					if($entry->action === ScorePacketEntryAction::CHANGE_FAKE_PLAYER){
						$entry->customName = CommonTypes::getString($in);
					}else{
						$entry->actorUniqueId = CommonTypes::getActorUniqueId($in);
					}
				}else{
					$entry->action = ScorePacketEntryAction::REMOVE;
				}
				$this->entries[] = $entry;
			}
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeList($out, $this->entries, function(ByteBufferWriter $out, ScorePacketEntry $entry) use ($protocolId) : void{
				VarInt::writeUnsignedInt($out, $entry->action->toOrdinal());
				CommonTypes::putString($out, $entry->action->value);

				//same for all types
				VarInt::writeSignedLong($out, $entry->scoreboardId);

				if($entry->action === ScorePacketEntryAction::REMOVE){
					if($protocolId === ProtocolInfo::PROTOCOL_1_26_44){
						CommonTypes::writeDoubleOptional($out, $entry->objectiveName, CommonTypes::putString(...));
					}else{
						CommonTypes::writeOptional($out, $entry->objectiveName, CommonTypes::putString(...));
					}
				}elseif($entry->action === ScorePacketEntryAction::CHANGE_PLAYER || $entry->action === ScorePacketEntryAction::CHANGE_ENTITY){
					CommonTypes::putString($out, $entry->objectiveName);
					LE::writeSignedInt($out, $entry->score);
					CommonTypes::putActorUniqueId($out, $entry->actorUniqueId);
				}elseif($entry->action === ScorePacketEntryAction::CHANGE_FAKE_PLAYER){
					CommonTypes::putString($out, $entry->objectiveName);
					LE::writeSignedInt($out, $entry->score);
					CommonTypes::putString($out, $entry->customName ?? throw new \InvalidArgumentException("CustomName must be set for this entry type"));
				}else{ // this should never be the case
					throw new \LogicException("Unhandled encode for action: " . $entry->action->name);
				}
			});
		}else{
			$isRemove = null;
			foreach($this->entries as $entry){
				$entryIsRemove = $entry->action === ScorePacketEntryAction::REMOVE;
				if($isRemove === null){
					$isRemove = $entryIsRemove;
				}elseif($isRemove !== $entryIsRemove){
					throw new \InvalidArgumentException("Protocols older than 1.26.40 cannot mix REMOVE and CHANGE entries in a single SetScorePacket");
				}
			}
			Byte::writeUnsigned($out, $isRemove === true ? self::TYPE_REMOVE : self::TYPE_CHANGE);
			VarInt::writeUnsignedInt($out, count($this->entries));
			foreach($this->entries as $entry){
				VarInt::writeSignedLong($out, $entry->scoreboardId);
				if($entry->action === ScorePacketEntryAction::REMOVE){
					CommonTypes::putString($out, $entry->objectiveName ?? "");
					LE::writeSignedInt($out, $entry->score);
				}else{
					CommonTypes::putString($out, $entry->objectiveName ?? throw new \InvalidArgumentException("ObjectiveName must be set for this entry type"));
					LE::writeSignedInt($out, $entry->score);
					Byte::writeUnsigned($out, self::actionToLegacyEntryType($entry->action));
					if($entry->action === ScorePacketEntryAction::CHANGE_FAKE_PLAYER){
						CommonTypes::putString($out, $entry->customName ?? throw new \InvalidArgumentException("CustomName must be set for this entry type"));
					}else{
						CommonTypes::putActorUniqueId($out, $entry->actorUniqueId ?? throw new \InvalidArgumentException("ActorUniqueId must be set for this entry type"));
					}
				}
			}
		}
	}

	/**
	 * Maps a <= 1.26.30 entry type (ScorePacketEntry::TYPE_*) to its ScorePacketEntryAction.
	 */
	private static function actionFromLegacyEntryType(int $type) : ?ScorePacketEntryAction{
		return match($type){
			ScorePacketEntry::TYPE_PLAYER => ScorePacketEntryAction::CHANGE_PLAYER,
			ScorePacketEntry::TYPE_ENTITY => ScorePacketEntryAction::CHANGE_ENTITY,
			ScorePacketEntry::TYPE_FAKE_PLAYER => ScorePacketEntryAction::CHANGE_FAKE_PLAYER,
			default => null,
		};
	}

	/**
	 * Maps a CHANGE_* ScorePacketEntryAction to its <= 1.26.30 entry type (ScorePacketEntry::TYPE_*).
	 */
	private static function actionToLegacyEntryType(ScorePacketEntryAction $action) : int{
		return match($action){
			ScorePacketEntryAction::CHANGE_PLAYER => ScorePacketEntry::TYPE_PLAYER,
			ScorePacketEntryAction::CHANGE_ENTITY => ScorePacketEntry::TYPE_ENTITY,
			ScorePacketEntryAction::CHANGE_FAKE_PLAYER => ScorePacketEntry::TYPE_FAKE_PLAYER,
			ScorePacketEntryAction::REMOVE => throw new \InvalidArgumentException("REMOVE has no legacy entry type"),
		};
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSetScore($this);
	}
}
