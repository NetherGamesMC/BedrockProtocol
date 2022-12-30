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
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use Ramsey\Uuid\UuidInterface;

class PlayerSkinPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_SKIN_PACKET;

	public UuidInterface $uuid;
	public string $oldSkinName = "";
	public string $newSkinName = "";
	public SkinData $skin;

	/**
	 * @generate-create-func
	 */
	public static function create(UuidInterface $uuid, string $oldSkinName, string $newSkinName, SkinData $skin) : self{
		$result = new self;
		$result->uuid = $uuid;
		$result->oldSkinName = $oldSkinName;
		$result->newSkinName = $newSkinName;
		$result->skin = $skin;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->uuid = $in->getUUID();
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_13_0){
			$this->skin = $in->getSkin();
			$this->newSkinName = $in->getString();
			$this->oldSkinName = $in->getString();
			if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_14_60){
				$this->skin->setVerified($in->getBool());
			}
		}else{
			$skinId = $in->getString();
			$this->newSkinName = $in->getString();
			$this->oldSkinName = $in->getString();
			$this->skin = $in->getSkin();
			$this->skin->setSkinId($skinId);
			$this->skin->setPremium($in->getBool());
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUUID($this->uuid);
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_13_0){
			$out->putSkin($this->skin);
			$out->putString($this->newSkinName);
			$out->putString($this->oldSkinName);
			if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_14_60){
				$out->putBool($this->skin->isVerified());
			}
		}else{
			$out->putString($this->skin->getSkinId());
			$out->putString($this->newSkinName);
			$out->putString($this->oldSkinName);
			$out->putSkin($this->skin);
			$out->putBool($this->skin->isPremium());
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerSkin($this);
	}
}
