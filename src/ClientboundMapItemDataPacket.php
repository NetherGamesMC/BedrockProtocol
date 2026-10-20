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
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\MapDecoration;
use pocketmine\network\mcpe\protocol\types\MapImage;
use pocketmine\network\mcpe\protocol\types\MapTrackedObject;
use pocketmine\utils\Binary;
use function count;

class ClientboundMapItemDataPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_MAP_ITEM_DATA_PACKET;

	/** Wire bitflags used before 1.26.40 (derived from which fields are non-null) */
	public const BITFLAG_TEXTURE_UPDATE = 0x02;
	public const BITFLAG_DECORATION_UPDATE = 0x04;
	public const BITFLAG_MAP_CREATION = 0x08;

	public int $mapId;
	/**
	 * <= ProtocolInfo::PROTOCOL_1_26_30: the decoded BITFLAG_* value. Only populated when decoding those protocols and
	 * ignored when encoding (the flags are derived from which fields are set).
	 */
	public int $type = 0;
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

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId <= ProtocolInfo::PROTOCOL_1_26_30){
			$this->decodeLegacy($in, $protocolId);
			return;
		}

		$this->mapId = CommonTypes::getActorUniqueId($in);
		$this->type = 0;
		$this->dimensionId = Byte::readUnsigned($in);
		$this->isLocked = CommonTypes::getBool($in);
		$this->origin = CommonTypes::getBlockPosition($in);

		$this->parentMapIds = CommonTypes::readOptional($in, static fn($in) => CommonTypes::readList($in, CommonTypes::getActorUniqueId(...)));

		$this->scale = CommonTypes::readOptional($in, Byte::readUnsigned(...));

		$this->trackedEntities = CommonTypes::readOptional($in, static fn($in) => CommonTypes::readList($in, static fn($in) => self::readTrackedObject($in, true)));

		$this->decorations = CommonTypes::readOptional($in, static fn($in) => CommonTypes::readList($in, static function($in) : MapDecoration{
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

		$this->colors = CommonTypes::readOptional($in, static function($in) use ($protocolId, $width, $height) : MapImage{
			if($width === null || $height === null){
				//ensure the packet can't get into an inconsistent state for re-encoding
				throw new PacketDecodeException("Expected both width and height to be present if colors are present");
			}
			return self::readColors($in, $protocolId, $width, $height);
		});
		if($this->colors === null && ($this->xOffset !== null || $this->yOffset !== null)){
			//ensure the packet can't get into an inconsistent state for re-encoding
			throw new PacketDecodeException("Expected both xOffset and yOffset to be null if colors are null");
		}
	}

	private function decodeLegacy(ByteBufferReader $in, int $protocolId) : void{
		$this->mapId = CommonTypes::getActorUniqueId($in);
		$this->type = VarInt::readUnsignedInt($in);
		$this->dimensionId = Byte::readUnsigned($in);
		$this->isLocked = CommonTypes::getBool($in);
		$this->origin = CommonTypes::getBlockPosition($in);

		$this->parentMapIds = null;
		$this->scale = null;
		$this->trackedEntities = null;
		$this->decorations = null;
		$this->xOffset = null;
		$this->yOffset = null;
		$this->colors = null;

		if(($this->type & self::BITFLAG_MAP_CREATION) !== 0){
			$this->parentMapIds = [];
			$count = VarInt::readUnsignedInt($in);
			for($i = 0; $i < $count; ++$i){
				$this->parentMapIds[] = CommonTypes::getActorUniqueId($in);
			}
		}

		if(($this->type & (self::BITFLAG_MAP_CREATION | self::BITFLAG_DECORATION_UPDATE | self::BITFLAG_TEXTURE_UPDATE)) !== 0){ //Decoration bitflag or colour bitflag
			$this->scale = Byte::readUnsigned($in);
		}

		if(($this->type & self::BITFLAG_DECORATION_UPDATE) !== 0){
			$this->trackedEntities = [];
			for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
				$this->trackedEntities[] = self::readTrackedObject($in, $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);
			}

			$this->decorations = [];
			for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
				$icon = Byte::readUnsigned($in);
				$rotation = Byte::readUnsigned($in);
				$xOffset = Byte::readUnsigned($in);
				$yOffset = Byte::readUnsigned($in);
				$label = CommonTypes::getString($in);
				$color = Color::fromRGBA(Binary::flipIntEndianness(VarInt::readUnsignedInt($in)));
				$this->decorations[] = new MapDecoration($icon, $rotation, $xOffset, $yOffset, $label, $color);
			}
		}

		if(($this->type & self::BITFLAG_TEXTURE_UPDATE) !== 0){
			$width = VarInt::readSignedInt($in);
			$height = VarInt::readSignedInt($in);
			$this->xOffset = VarInt::readSignedInt($in);
			$this->yOffset = VarInt::readSignedInt($in);

			$this->colors = self::readColors($in, $protocolId, $width, $height);
		}
	}

	private static function readTrackedObject(ByteBufferReader $in, bool $signedBlockY) : MapTrackedObject{
		$object = new MapTrackedObject();
		$object->type = LE::readUnsignedInt($in);
		if($object->type === MapTrackedObject::TYPE_BLOCK){
			$object->blockPosition = CommonTypes::getBlockPosition($in, $signedBlockY);
		}elseif($object->type === MapTrackedObject::TYPE_ENTITY){
			$object->actorUniqueId = CommonTypes::getActorUniqueId($in);
		}else{
			throw new PacketDecodeException("Unknown map object type $object->type");
		}
		return $object;
	}

	private static function writeTrackedObject(ByteBufferWriter $out, MapTrackedObject $object, bool $signedBlockY) : void{
		LE::writeUnsignedInt($out, $object->type);
		if($object->type === MapTrackedObject::TYPE_BLOCK){
			CommonTypes::putBlockPosition($out, $object->blockPosition, $signedBlockY);
		}elseif($object->type === MapTrackedObject::TYPE_ENTITY){
			CommonTypes::putActorUniqueId($out, $object->actorUniqueId);
		}else{
			throw new \InvalidArgumentException("Unknown map object type $object->type");
		}
	}

	private static function readColors(ByteBufferReader $in, int $protocolId, int $width, int $height) : MapImage{
		$count = VarInt::readUnsignedInt($in);
		if($count !== $width * $height){
			throw new PacketDecodeException("Expected colour count of " . ($height * $width) . " (height $height * width $width), got $count");
		}

		return MapImage::decode($in, $protocolId, $height, $width);
	}

	private static function writeColors(ByteBufferWriter $out, int $protocolId, MapImage $colors) : void{
		VarInt::writeUnsignedInt($out, $colors->getWidth() * $colors->getHeight()); //list count, but we handle it as a 2D array... thanks for the confusion mojang
		$colors->encode($out, $protocolId);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId <= ProtocolInfo::PROTOCOL_1_26_30){
			$this->encodeLegacy($out, $protocolId);
			return;
		}

		CommonTypes::putActorUniqueId($out, $this->mapId);
		Byte::writeUnsigned($out, $this->dimensionId);
		CommonTypes::putBool($out, $this->isLocked);
		CommonTypes::putBlockPosition($out, $this->origin);

		CommonTypes::writeOptional($out, $this->parentMapIds, static fn($out, $v) => CommonTypes::writeList($out, $v, CommonTypes::putActorUniqueId(...)));

		CommonTypes::writeOptional($out, $this->scale, Byte::writeUnsigned(...));

		CommonTypes::writeOptional($out, $this->trackedEntities, static fn($out, $v) => CommonTypes::writeList($out, $v, static fn($out, $object) => self::writeTrackedObject($out, $object, true)));

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

		CommonTypes::writeOptional($out, $colors, static fn($out, $colors) => self::writeColors($out, $protocolId, $colors));
	}

	/**
	 * Before 1.26.40 the optionals were expressed as a set of bitflags. An empty parent map ID list or empty
	 * decoration + tracked entity lists are treated like null, matching the fork's previous behaviour.
	 */
	private function encodeLegacy(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putActorUniqueId($out, $this->mapId);

		$parentMapIds = $this->parentMapIds ?? [];
		$trackedEntities = $this->trackedEntities ?? [];
		$decorations = $this->decorations ?? [];

		$type = 0;
		if(count($parentMapIds) > 0){
			$type |= self::BITFLAG_MAP_CREATION;
		}
		if(count($decorations) > 0 || count($trackedEntities) > 0){
			$type |= self::BITFLAG_DECORATION_UPDATE;
		}
		if($this->colors !== null){
			$type |= self::BITFLAG_TEXTURE_UPDATE;
		}

		VarInt::writeUnsignedInt($out, $type);
		Byte::writeUnsigned($out, $this->dimensionId);
		CommonTypes::putBool($out, $this->isLocked);
		CommonTypes::putBlockPosition($out, $this->origin);

		if(($type & self::BITFLAG_MAP_CREATION) !== 0){
			VarInt::writeUnsignedInt($out, count($parentMapIds));
			foreach($parentMapIds as $parentMapId){
				CommonTypes::putActorUniqueId($out, $parentMapId);
			}
		}

		if(($type & (self::BITFLAG_MAP_CREATION | self::BITFLAG_TEXTURE_UPDATE | self::BITFLAG_DECORATION_UPDATE)) !== 0){
			Byte::writeUnsigned($out, $this->scale ?? 0);
		}

		if(($type & self::BITFLAG_DECORATION_UPDATE) !== 0){
			VarInt::writeUnsignedInt($out, count($trackedEntities));
			foreach($trackedEntities as $object){
				self::writeTrackedObject($out, $object, $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);
			}

			VarInt::writeUnsignedInt($out, count($decorations));
			foreach($decorations as $decoration){
				Byte::writeUnsigned($out, $decoration->getIcon());
				Byte::writeUnsigned($out, $decoration->getRotation());
				Byte::writeUnsigned($out, $decoration->getXOffset());
				Byte::writeUnsigned($out, $decoration->getYOffset());
				CommonTypes::putString($out, $decoration->getLabel());
				VarInt::writeUnsignedInt($out, Binary::flipIntEndianness($decoration->getColor()->toRGBA()));
			}
		}

		if($this->colors !== null){
			VarInt::writeSignedInt($out, $this->colors->getWidth());
			VarInt::writeSignedInt($out, $this->colors->getHeight());
			VarInt::writeSignedInt($out, $this->xOffset ?? 0);
			VarInt::writeSignedInt($out, $this->yOffset ?? 0);

			self::writeColors($out, $protocolId, $this->colors);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleClientboundMapItemData($this);
	}
}
