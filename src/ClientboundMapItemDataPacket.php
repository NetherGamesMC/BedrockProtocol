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
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\MapDecoration;
use pocketmine\network\mcpe\protocol\types\MapImage;
use pocketmine\network\mcpe\protocol\types\MapTrackedObject;

class ClientboundMapItemDataPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_MAP_ITEM_DATA_PACKET;

	public int $mapId;
	public int $dimensionId = DimensionIds::OVERWORLD;
	public bool $isLocked = false;
	public BlockPosition $origin;

	/**
	 * @var int[]
	 * @phpstan-var list<int>
	 */
	public ?array $parentMapIds = null;
	public ?int $scale = null;

	/**
	 * @var MapTrackedObject[]
	 * @phpstan-var list<MapTrackedObject>
	 */
	public ?array $trackedEntities = null;
	/**
	 * @var MapDecoration[]
	 * @phpstan-var list<MapDecoration>
	 */
	public ?array $decorations = null;

	public ?int $xOffset = null;
	public ?int $yOffset = null;
	public ?MapImage $colors = null;

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->mapId = CommonTypes::getActorUniqueId($in);
		$this->dimensionId = Byte::readUnsigned($in);
		$this->isLocked = CommonTypes::getBool($in);
		$this->origin = CommonTypes::getBlockPosition($in);

		$this->parentMapIds = CommonTypes::readOptional($in, static fn($in) => CommonTypes::readList($in, CommonTypes::getActorUniqueId(...)));

		$this->scale = CommonTypes::readOptional($in, Byte::readUnsigned(...));

		$this->trackedEntities = CommonTypes::readOptional($in, static fn($in) => CommonTypes::readList($in, static function($in){
			$object = new MapTrackedObject();
			$object->type = LE::readUnsignedInt($in);
			$object->actorUniqueId = CommonTypes::readOptional($in, CommonTypes::getActorUniqueId(...));
			$object->blockPosition = CommonTypes::readOptional($in, CommonTypes::getBlockPosition(...));
			return $object;
		}));

		$this->decorations = CommonTypes::readOptional($in, static fn($in) => CommonTypes::readList($in, static function($in){
			$icon = Byte::readUnsigned($in);
			$rotation = Byte::readUnsigned($in);
			$xOffset = Byte::readUnsigned($in);
			$yOffset = Byte::readUnsigned($in);
			$label = CommonTypes::getString($in);
			$color = CommonTypes::readColor($in);
			return new MapDecoration($icon, $rotation, $xOffset, $yOffset, $label, $color);
		}));

		$width = CommonTypes::readOptional($in, VarInt::readSignedInt(...));
		$height = CommonTypes::readOptional($in, VarInt::readSignedInt(...));
		$this->xOffset = CommonTypes::readOptional($in, VarInt::readSignedInt(...));
		$this->yOffset = CommonTypes::readOptional($in, VarInt::readSignedInt(...));

		$this->colors = CommonTypes::readOptional($in, static function($in) use ($width, $height){
			if($width === null || $height === null){
				//ensure the packet can't get into an inconsistent state for re-encoding
				throw new PacketDecodeException("Expected both width and height to be present if colors are present");
			}
			$count = VarInt::readUnsignedInt($in);
			if($count !== $width * $height){
				throw new PacketDecodeException("Expected colour count of " . ($height * $width) . " (height $height * width $width), got $count");
			}

			return MapImage::decode($in, $height, $width);
		});
		if($this->colors === null && ($this->xOffset !== null || $this->yOffset !== null)){
			//ensure the packet can't get into an inconsistent state for re-encoding
			throw new PacketDecodeException("Expected both xOffset and yOffset to be null if colors are null");
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorUniqueId($out, $this->mapId);
		Byte::writeUnsigned($out, $this->dimensionId);
		CommonTypes::putBool($out, $this->isLocked);
		CommonTypes::putBlockPosition($out, $this->origin);

		CommonTypes::writeOptional($out, $this->parentMapIds, static fn($out, $v) => CommonTypes::writeList($out, $v, CommonTypes::putActorUniqueId(...)));

		CommonTypes::writeOptional($out, $this->scale, Byte::writeUnsigned(...));

		CommonTypes::writeOptional($out, $this->trackedEntities, static fn($out, $v) => CommonTypes::writeList($out, $v, static function($out, $object) : void{
			LE::writeUnsignedInt($out, $object->type);
			CommonTypes::writeOptional($out, $object->actorUniqueId, CommonTypes::putActorUniqueId(...));
			CommonTypes::writeOptional($out, $object->blockPosition, CommonTypes::putBlockPosition(...));
		}));

		CommonTypes::writeOptional($out, $this->decorations, static fn($out, $v) => CommonTypes::writeList($out, $v, static function($out, $decoration) : void{
			Byte::writeUnsigned($out, $decoration->getIcon());
			Byte::writeUnsigned($out, $decoration->getRotation());
			Byte::writeUnsigned($out, $decoration->getXOffset());
			Byte::writeUnsigned($out, $decoration->getYOffset());
			CommonTypes::putString($out, $decoration->getLabel());
			CommonTypes::writeColor($out, $decoration->getColor());
		}));

		//TODO: this is icky but it's better than requiring callers to specify height and width separately from colors
		$colors = $this->colors;
		CommonTypes::writeOptional($out, $colors?->getWidth(), VarInt::writeSignedInt(...));
		CommonTypes::writeOptional($out, $colors?->getHeight(), VarInt::writeSignedInt(...));
		CommonTypes::writeOptional($out, $this->xOffset, VarInt::writeSignedInt(...));
		CommonTypes::writeOptional($out, $this->yOffset, VarInt::writeSignedInt(...));

		CommonTypes::writeOptional($out, $colors, static function($out, $colors) : void{
			VarInt::writeUnsignedInt($out, $colors->getWidth() * $colors->getHeight()); //list count, but we handle it as a 2D array... thanks for the confusion mojang
			$colors->encode($out);
		});
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleClientboundMapItemData($this);
	}
}
