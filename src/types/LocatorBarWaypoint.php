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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\color\Color;
use pocketmine\math\Vector2;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * @see LocatorBarWaypointPayload
 */
final class LocatorBarWaypoint{
	public function __construct(
		private int $updateFlag,
		private ?bool $visible,
		private ?WorldPosition $worldPosition,
		private ?int $textureId,
		private ?string $texturePath,
		private ?Vector2 $iconSize,
		private ?Color $color,
		private ?bool $clientPositionAuthority,
		private ?int $actorUniqueId,
	){}

	public function getUpdateFlag() : int{ return $this->updateFlag; }

	public function getVisible() : ?bool{ return $this->visible; }

	public function getWorldPosition() : ?WorldPosition{ return $this->worldPosition; }

	public function getTextureId() : ?int{ return $this->textureId; }

	public function getTexturePath() : ?string{ return $this->texturePath; }

	public function getIconSize() : ?Vector2{ return $this->iconSize; }

	public function getColor() : ?Color{ return $this->color; }

	public function getClientPositionAuthority() : ?bool{ return $this->clientPositionAuthority; }

	public function getActorUniqueId() : ?int{ return $this->actorUniqueId; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$updateFlag = LE::readUnsignedInt($in);
		$visible = CommonTypes::readOptional($in, CommonTypes::getBool(...));
		$worldPosition = CommonTypes::readOptional($in, WorldPosition::read(...));
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
			$texturePath = CommonTypes::readOptional($in, CommonTypes::getString(...));
			$iconSize = CommonTypes::readOptional($in, CommonTypes::getVector2(...));
		}else{
			$textureId = CommonTypes::readOptional($in, LE::readUnsignedInt(...));
		}
		$color = CommonTypes::readOptional($in, fn() => Color::fromARGB(LE::readUnsignedInt($in)));
		$clientPositionAuthority = CommonTypes::readOptional($in, CommonTypes::getBool(...));
		$actorUniqueId = CommonTypes::readOptional($in, CommonTypes::getActorUniqueId(...));

		return new self(
			$updateFlag,
			$visible,
			$worldPosition,
			$textureId ?? null,
			$texturePath ?? null,
			$iconSize ?? null,
			$color,
			$clientPositionAuthority,
			$actorUniqueId,
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		LE::writeUnsignedInt($out, $this->updateFlag);
		CommonTypes::writeOptional($out, $this->visible, CommonTypes::putBool(...));
		CommonTypes::writeOptional($out, $this->worldPosition, fn(ByteBufferWriter $out, WorldPosition $v) => $v->write($out));
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
			CommonTypes::writeOptional($out, $this->texturePath, CommonTypes::putString(...));
			CommonTypes::writeOptional($out, $this->iconSize, CommonTypes::putVector2(...));
		}else{
			CommonTypes::writeOptional($out, $this->textureId, LE::writeUnsignedInt(...));
		}
		CommonTypes::writeOptional($out, $this->color, fn(ByteBufferWriter $out, Color $v) => LE::writeUnsignedInt($out, $v->toARGB()));
		CommonTypes::writeOptional($out, $this->clientPositionAuthority, CommonTypes::putBool(...));
		CommonTypes::writeOptional($out, $this->actorUniqueId, CommonTypes::putActorUniqueId(...));
	}
}
