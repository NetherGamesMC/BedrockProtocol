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

namespace pocketmine\network\mcpe\protocol\serializer;

use pocketmine\math\Vector3;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\command\CommandOriginData;
use pocketmine\network\mcpe\protocol\types\entity\Attribute;
use pocketmine\network\mcpe\protocol\types\entity\AttributeModifier;
use pocketmine\network\mcpe\protocol\types\entity\BlockPosMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\CompoundTagMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ShortMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\Vec3MetadataProperty;
use pocketmine\network\mcpe\protocol\types\FloatGameRule;
use pocketmine\network\mcpe\protocol\types\GameRule;
use pocketmine\network\mcpe\protocol\types\IntGameRule;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\recipe\IntIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\ItemDescriptorType;
use pocketmine\network\mcpe\protocol\types\recipe\MolangItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use pocketmine\network\mcpe\protocol\types\recipe\StringIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\TagItemDescriptor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use pocketmine\network\mcpe\protocol\types\StructureEditorData;
use pocketmine\network\mcpe\protocol\types\StructureSettings;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryDataException;
use pocketmine\utils\BinaryStream;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function count;
use function strlen;
use function strrev;
use function substr;

class PacketSerializer extends BinaryStream{

	private ?int $protocolId = null;
	private int $shieldItemRuntimeId;
	private PacketSerializerContext $context;

	protected function __construct(PacketSerializerContext $context, string $buffer = "", int $offset = 0){
		parent::__construct($buffer, $offset);
		$this->context = $context;
		$this->shieldItemRuntimeId = $context->getItemDictionary()->fromStringId("minecraft:shield");
	}

	public static function encoder(PacketSerializerContext $context) : self{
		return new self($context);
	}

	public static function decoder(string $buffer, int $offset, PacketSerializerContext $context) : self{
		return new self($context, $buffer, $offset);
	}

	public function setProtocolId(?int $protocolId) : void{
		$this->protocolId = $protocolId;
	}

	public function getProtocolId() : int{
		return $this->protocolId ?? ProtocolInfo::CURRENT_PROTOCOL;
	}

	/**
	 * @throws BinaryDataException
	 */
	public function getString() : string{
		return $this->get($this->getUnsignedVarInt());
	}

	public function putString(string $v) : void{
		$this->putUnsignedVarInt(strlen($v));
		$this->put($v);
	}

	/**
	 * @throws BinaryDataException
	 */
	public function getUUID() : UuidInterface{
		//This is two little-endian longs: bytes 7-0 followed by bytes 15-8
		$p1 = strrev($this->get(8));
		$p2 = strrev($this->get(8));
		return Uuid::fromBytes($p1 . $p2);
	}

	public function putUUID(UuidInterface $uuid) : void{
		$bytes = $uuid->getBytes();
		$this->put(strrev(substr($bytes, 0, 8)));
		$this->put(strrev(substr($bytes, 8, 8)));
	}

	public function getSkin() : SkinData{
		$skinId = "";
		$skinResourcePatch = null;
		$geometryName = "";
		if($p_1_13_0 = ($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_13_0)){
			$skinId = $this->getString();
			if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_210){
				$skinPlayFabId = $this->getString();
			}
			$skinResourcePatch = $this->getString();
		}
		$skinData = $this->getSkinImage();
		$animations = [];
		if($p_1_13_0){
			$animationCount = $this->getLInt();
			for($i = 0; $i < $animationCount; ++$i){
				$skinImage = $this->getSkinImage();
				$animationType = $this->getLInt();
				$animationFrames = $this->getLFloat();
				if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_100){
					$expressionType = $this->getLInt();
				}
				$animations[] = new SkinAnimation($skinImage, $animationType, $animationFrames, $expressionType ?? 0);
			}
			$capeData = $this->getSkinImage();
		}else{
			$capeRawData = $this->getString();
			if(strlen($capeRawData) !== 0){
				$capeData = $this->getSkinImage();
			}
			$geometryName = $this->getString();
		}
		$geometryData = $this->getString();
		if(!$p_1_13_0){
			return new SkinData(
				$skinId,
				"",
				null,
				$skinData,
				capeImage: $capeData ?? new SkinImage(0, 0, ""),
				geometryData: $geometryData,
				geometryName: $geometryName,
			);
		}

		if($p_1_17_30 = ($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_17_30)){
			$geometryDataVersion = $this->getString();
		}
		$animationData = $this->getString();
		if(!$p_1_17_30){
			$premium = $this->getBool();
			$persona = $this->getBool();
			$capeOnClassic = $this->getBool();
		}
		$capeId = $this->getString();
		$fullSkinId = $this->getString();
		if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_14_60){
			$armSize = $this->getString();
			$skinColor = $this->getString();
			$personaPieceCount = $this->getLInt();
			$personaPieces = [];
			for($i = 0; $i < $personaPieceCount; ++$i){
				$pieceId = $this->getString();
				$pieceType = $this->getString();
				$packId = $this->getString();
				$isDefaultPiece = $this->getBool();
				$productId = $this->getString();
				$personaPieces[] = new PersonaSkinPiece($pieceId, $pieceType, $packId, $isDefaultPiece, $productId);
			}
			$pieceTintColorCount = $this->getLInt();
			$pieceTintColors = [];
			for($i = 0; $i < $pieceTintColorCount; ++$i){
				$pieceType = $this->getString();
				$colorCount = $this->getLInt();
				$colors = [];
				for($j = 0; $j < $colorCount; ++$j){
					$colors[] = $this->getString();
				}
				$pieceTintColors[] = new PersonaPieceTintColor(
					$pieceType,
					$colors
				);
			}
		}

		if($p_1_17_30){
			$premium = $this->getBool();
			$persona = $this->getBool();
			$capeOnClassic = $this->getBool();
			$isPrimaryUser = $this->getBool();
		}

		return new SkinData(
			$skinId,
			$skinPlayFabId ?? "",
			$skinResourcePatch,
			$skinData,
			$animations,
			$capeData ?? new SkinImage(0, 0, ""),
			$geometryData,
			$geometryDataVersion ?? ProtocolInfo::MINECRAFT_VERSION_NETWORK,
			$animationData,
			$capeId,
			$fullSkinId,
			$armSize ?? "",
			$skinColor ?? "",
			$personaPieces ?? [],
			$pieceTintColors ?? [],
			true,
			$premium,
			$persona,
			$capeOnClassic,
			$isPrimaryUser ?? true,
		);
	}

	public function putSkin(SkinData $skin) : void{
		if($p_1_13_0 = ($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_13_0)){
			$this->putString($skin->getSkinId());
			if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_210){
				$this->putString($skin->getPlayFabId());
			}
			$this->putString($skin->getResourcePatch());
		}
		$this->putSkinImage($skin->getSkinImage());
		if($p_1_13_0){
			$this->putLInt(count($skin->getAnimations()));
			foreach($skin->getAnimations() as $animation){
				$this->putSkinImage($animation->getImage());
				$this->putLInt($animation->getType());
				$this->putLFloat($animation->getFrames());
				if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_100){
					$this->putLInt($animation->getExpressionType());
				}
			}
		}
		$this->putSkinImage($skin->getCapeImage());
		if(!$p_1_13_0){
			$this->putString($skin->getGeometryName());
		}
		$this->putString($skin->getGeometryData());
		if(!$p_1_13_0){
			return;
		}
		if($p_1_17_30 = ($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_17_30)){
			$this->putString($skin->getGeometryDataEngineVersion());
		}
		$this->putString($skin->getAnimationData());
		if(!$p_1_17_30){
			$this->putBool($skin->isPremium());
			$this->putBool($skin->isPersona());
			$this->putBool($skin->isPersonaCapeOnClassic());
		}
		$this->putString($skin->getCapeId());
		$this->putString($skin->getFullSkinId());
		if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_14_60){
			$this->putString($skin->getArmSize());
			$this->putString($skin->getSkinColor());
			$this->putLInt(count($skin->getPersonaPieces()));
			foreach($skin->getPersonaPieces() as $piece){
				$this->putString($piece->getPieceId());
				$this->putString($piece->getPieceType());
				$this->putString($piece->getPackId());
				$this->putBool($piece->isDefaultPiece());
				$this->putString($piece->getProductId());
			}
			$this->putLInt(count($skin->getPieceTintColors()));
			foreach($skin->getPieceTintColors() as $tint){
				$this->putString($tint->getPieceType());
				$this->putLInt(count($tint->getColors()));
				foreach($tint->getColors() as $color){
					$this->putString($color);
				}
			}
		}
		if($p_1_17_30){
			$this->putBool($skin->isPremium());
			$this->putBool($skin->isPersona());
			$this->putBool($skin->isPersonaCapeOnClassic());
			$this->putBool($skin->isPrimaryUser());
		}
	}

	private function getSkinImage() : SkinImage{
		if ($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_13_0){
			$width = $this->getLInt();
			$height = $this->getLInt();
			$data = $this->getString();
			try{
				return new SkinImage($height, $width, $data);
			}catch(\InvalidArgumentException $e){
				throw new PacketDecodeException($e->getMessage(), 0, $e);
			}
		}else{
			$data = $this->getString();
			try{
				return SkinImage::fromLegacy($data);
			}catch(\InvalidArgumentException $e){
				throw new PacketDecodeException($e->getMessage(), 0, $e);
			}
		}
	}

	private function putSkinImage(SkinImage $image) : void{
		if ($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_13_0){
			$this->putLInt($image->getWidth());
			$this->putLInt($image->getHeight());
		}
		$this->putString($image->getData());
	}

	/**
	 * @throws PacketDecodeException
	 * @throws BinaryDataException
	 */
	public function getItemStackWithoutStackId() : ItemStack{
		return $this->getItemStack(function() : void{
			//NOOP
		});
	}

	public function putItemStackWithoutStackId(ItemStack $item) : void{
		$this->putItemStack($item, function() : void{
			//NOOP
		});
	}

	/**
	 * @phpstan-param \Closure(PacketSerializer) : void $readExtraCrapInTheMiddle
	 *
	 * @throws PacketDecodeException
	 * @throws BinaryDataException
	 */
	public function getItemStack(\Closure $readExtraCrapInTheMiddle) : ItemStack{
		$id = $this->getVarInt();
		if($id === 0){
			return ItemStack::null();
		}

		if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_220){
			$count = $this->getLShort();
			$meta = $this->getUnsignedVarInt();

			$readExtraCrapInTheMiddle($this);

			$blockRuntimeId = $this->getVarInt();
			$extraData = self::decoder($this->getString(), 0, $this->context);
		}else{
			$auxValue = $this->getVarInt();
			$count = $auxValue & 0xff;
			$meta = $auxValue >> 8;

			$blockRuntimeId = 0; // TODO: somehow get a runtime id?
			$extraData = $this;
		}

		$stack = self::readExtraItemStackData($extraData, $id, $meta, $count, $blockRuntimeId);

		if($extraData !== $this) {
			if(!$extraData->feof()){
				throw new PacketDecodeException("Unexpected trailing extradata for network item $id");
			}
		}

		return $stack;
	}

	private static function readExtraItemStackData(PacketSerializer $serializer, int $id, int $meta, int $count, int $blockRuntimeId) : ItemStack{
		if($serializer->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_220) {
			$getListCount = \Closure::fromCallable([$serializer, "getLInt"]);
			$getString = \Closure::fromCallable(function() use ($serializer) : string{
				return $serializer->get($serializer->getLShort());
			});
			$getNBT = \Closure::fromCallable(function() use ($serializer) : CompoundTag{
				$offset = $serializer->getOffset();
				try{
					return (new LittleEndianNbtSerializer())->read($serializer->getBuffer(), $offset, 512)->mustGetCompoundTag();
				}catch(NbtDataException $e){
					throw PacketDecodeException::wrap($e, "Failed decoding NBT root");
				}finally{
					$serializer->setOffset($offset);
				}
			});
			$getBlockingTick = \Closure::fromCallable([$serializer, "getLLong"]);
		}else{
			$getListCount = \Closure::fromCallable([$serializer, "getVarInt"]);
			$getString = \Closure::fromCallable([$serializer, "getString"]);
			$getNBT = \Closure::fromCallable([$serializer, "getNbtCompoundRoot"]);
			$getBlockingTick = \Closure::fromCallable([$serializer, "getVarLong"]);
		}

		$nbtLen = $serializer->getLShort();

		/** @var CompoundTag|null $compound */
		$compound = null;
		if($nbtLen === 0xffff){
			$nbtDataVersion = $serializer->getByte();
			if($nbtDataVersion !== 1){
				throw new PacketDecodeException("Unexpected NBT data version $nbtDataVersion");
			}
			$compound = $getNBT();
		}elseif($nbtLen !== 0){
			throw new PacketDecodeException("Unexpected fake NBT length $nbtLen");
		}

		$canPlaceOn = [];
		for($i = 0, $canPlaceOnCount = $getListCount(); $i < $canPlaceOnCount; ++$i){
			$canPlaceOn[] = $getString();
		}
		$canDestroy = [];
		for($i = 0, $canDestroyCount = $getListCount(); $i < $canDestroyCount; ++$i){
			$canDestroy[] = $getString();
		}

		$shieldBlockingTick = null;
		if($id === $serializer->shieldItemRuntimeId){
			$shieldBlockingTick = $getBlockingTick();
		}

		return new ItemStack($id, $meta, $count, $blockRuntimeId, $compound, $canPlaceOn, $canDestroy, $shieldBlockingTick);
	}

	/**
	 * @phpstan-param \Closure(PacketSerializer) : void $writeExtraCrapInTheMiddle
	 */
	public function putItemStack(ItemStack $item, \Closure $writeExtraCrapInTheMiddle) : void{
		if($item->getId() === 0){
			$this->putVarInt(0);

			return;
		}

		$this->putVarInt($item->getId());

		if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_220){
			$this->putLShort($item->getCount());
			$this->putUnsignedVarInt($item->getMeta());

			$writeExtraCrapInTheMiddle($this);

			$this->putVarInt($item->getBlockRuntimeId());

			$extraData = PacketSerializer::encoder($this->context);
			self::putExtraItemStackData($extraData, $item);
			$this->putString($extraData->getBuffer());
			return;
		}

		$auxValue = (($item->getMeta() & 0x7fff) << 8) | $item->getCount();
		$this->putVarInt($auxValue);

		self::putExtraItemStackData($this, $item);
	}

	private static function putExtraItemStackData(PacketSerializer $serializer, ItemStack $item) : void{
		if($serializer->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_220) {
			$putListCount = \Closure::fromCallable([$serializer, "putLInt"]);
			$putString = \Closure::fromCallable(function(string $str) use ($serializer) : void{
				$serializer->putLShort(strlen($str));
				$serializer->put($str);
			});
			$putBlockingTick = \Closure::fromCallable([$serializer, "putLLong"]);
			$nbtSerializerClass = LittleEndianNbtSerializer::class;
		}else{
			$putListCount = \Closure::fromCallable([$serializer, "putVarInt"]);
			$putString = \Closure::fromCallable([$serializer, "putString"]);
			$putBlockingTick = \Closure::fromCallable([$serializer, "putVarLong"]);
			$nbtSerializerClass = NetworkNbtSerializer::class;
		}

		$nbt = $item->getNbt();
		if($nbt !== null){
			$serializer->putLShort(0xffff);
			$serializer->putByte(1); //TODO: NBT data version (?)
			$serializer->put((new $nbtSerializerClass())->write(new TreeRoot($nbt)));
		}else{
			$serializer->putLShort(0);
		}

		$putListCount(count($item->getCanPlaceOn()));
		foreach($item->getCanPlaceOn() as $entry){
			$putString($entry);
		}
		$putListCount(count($item->getCanDestroy()));
		foreach($item->getCanDestroy() as $entry){
			$putString($entry);
		}

		$blockingTick = $item->getShieldBlockingTick();
		if($item->getId() === $serializer->shieldItemRuntimeId){
			$putBlockingTick($blockingTick ?? 0);
		}
	}

	public function getRecipeIngredient() : RecipeIngredient{
		if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_19_30){
			$descriptorType = $this->getByte();
			$descriptor = match($descriptorType){
				ItemDescriptorType::INT_ID_META => IntIdMetaItemDescriptor::read($this),
				ItemDescriptorType::STRING_ID_META => StringIdMetaItemDescriptor::read($this),
				ItemDescriptorType::TAG => TagItemDescriptor::read($this),
				ItemDescriptorType::MOLANG => MolangItemDescriptor::read($this),
				default => null
			};
			$count = $this->getVarInt();
		}else{
			$descriptor = IntIdMetaItemDescriptor::read($this);
			$count = $descriptor->getId() === 0 ? 0 : $this->getVarInt();
		}

		return new RecipeIngredient($descriptor, $count);
	}

	public function putRecipeIngredient(RecipeIngredient $ingredient) : void{
		$type = $ingredient->getDescriptor();

		if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_19_30){
			$this->putByte($type?->getTypeId() ?? 0);
			$type?->write($this);

			$this->putVarInt($ingredient->getCount());
		}elseif($type instanceof IntIdMetaItemDescriptor){
			$type->write($this);
			if($type->getId() !== 0){
				$this->putVarInt($ingredient->getCount());
			}
		}else{
			$this->putVarInt(0);
		}
	}

	/**
	 * Decodes entity metadata from the stream.
	 *
	 * @return MetadataProperty[]
	 * @phpstan-return array<int, MetadataProperty>
	 *
	 * @throws PacketDecodeException
	 * @throws BinaryDataException
	 */
	public function getEntityMetadata() : array{
		$count = $this->getUnsignedVarInt();
		$data = [];
		for($i = 0; $i < $count; ++$i){
			$key = $this->getUnsignedVarInt();
			$type = $this->getUnsignedVarInt();

			$data[$key] = $this->readMetadataProperty($type);
		}

		return $data;
	}

	private function readMetadataProperty(int $type) : MetadataProperty{
		return match($type){
			ByteMetadataProperty::ID => ByteMetadataProperty::read($this),
			ShortMetadataProperty::ID => ShortMetadataProperty::read($this),
			IntMetadataProperty::ID => IntMetadataProperty::read($this),
			FloatMetadataProperty::ID => FloatMetadataProperty::read($this),
			StringMetadataProperty::ID => StringMetadataProperty::read($this),
			CompoundTagMetadataProperty::ID => CompoundTagMetadataProperty::read($this),
			BlockPosMetadataProperty::ID => BlockPosMetadataProperty::read($this),
			LongMetadataProperty::ID => LongMetadataProperty::read($this),
			Vec3MetadataProperty::ID => Vec3MetadataProperty::read($this),
			default => throw new PacketDecodeException("Unknown entity metadata type " . $type),
		};
	}

	/**
	 * Writes entity metadata to the packet buffer.
	 *
	 * @param MetadataProperty[] $metadata
	 *
	 * @phpstan-param array<int, MetadataProperty> $metadata
	 */
	public function putEntityMetadata(array $metadata) : void{
		$data = EntityMetadataProperties::encode($metadata, $this->getProtocolId());
		$data = EntityMetadataFlags::encode($data, $this->getProtocolId());

		$this->putUnsignedVarInt(count($data));
		foreach($data as $key => $d){
			$this->putUnsignedVarInt($key);
			$this->putUnsignedVarInt($d->getTypeId());
			$d->write($this);
		}
	}

	/**
	 * Reads a list of Attributes from the stream.
	 * @return Attribute[]
	 *
	 * @throws BinaryDataException
	 */
	public function getAttributeList() : array{
		$list = [];
		$count = $this->getUnsignedVarInt();

		for($i = 0; $i < $count; ++$i){
			$min = $this->getLFloat();
			$max = $this->getLFloat();
			$current = $this->getLFloat();
			$default = $this->getLFloat();
			$id = $this->getString();

			$modifiers = [];
			if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_19_20){
				for($j = 0, $modifierCount = $this->getUnsignedVarInt(); $j < $modifierCount; $j++){
					$modifiers[] = AttributeModifier::read($this);
				}
			}

			$list[] = new Attribute($id, $min, $max, $current, $default, $modifiers);
		}

		return $list;
	}

	/**
	 * Writes a list of Attributes to the packet buffer using the standard format.
	 */
	public function putAttributeList(Attribute ...$attributes) : void{
		$this->putUnsignedVarInt(count($attributes));
		foreach($attributes as $attribute){
			$this->putLFloat($attribute->getMin());
			$this->putLFloat($attribute->getMax());
			$this->putLFloat($attribute->getCurrent());
			$this->putLFloat($attribute->getDefault());
			$this->putString($attribute->getId());

			if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_19_20){
				$this->putUnsignedVarInt(count($attribute->getModifiers()));
				foreach($attribute->getModifiers() as $modifier){
					$modifier->write($this);
				}
			}
		}
	}

	/**
	 * @throws BinaryDataException
	 */
	final public function getActorUniqueId() : int{
		return $this->getVarLong();
	}

	public function putActorUniqueId(int $eid) : void{
		$this->putVarLong($eid);
	}

	/**
	 * @throws BinaryDataException
	 */
	final public function getActorRuntimeId() : int{
		return $this->getUnsignedVarLong();
	}

	public function putActorRuntimeId(int $eid) : void{
		$this->putUnsignedVarLong($eid);
	}

	/**
	 * Reads a block position with unsigned Y coordinate.
	 *
	 * @throws BinaryDataException
	 */
	public function getBlockPosition() : BlockPosition{
		$x = $this->getVarInt();
		$y = Binary::signInt($this->getUnsignedVarInt()); //Y coordinate may be signed, but it's written unsigned :<
		$z = $this->getVarInt();
		return new BlockPosition($x, $y, $z);
	}

	/**
	 * Writes a block position with unsigned Y coordinate.
	 */
	public function putBlockPosition(BlockPosition $blockPosition) : void{
		$this->putVarInt($blockPosition->getX());
		$this->putUnsignedVarInt(Binary::unsignInt($blockPosition->getY())); //Y coordinate may be signed, but it's written unsigned :<
		$this->putVarInt($blockPosition->getZ());
	}

	/**
	 * Reads a block position with a signed Y coordinate.
	 *
	 * @throws BinaryDataException
	 */
	public function getSignedBlockPosition() : BlockPosition{
		$x = $this->getVarInt();
		$y = $this->getVarInt();
		$z = $this->getVarInt();
		return new BlockPosition($x, $y, $z);
	}

	/**
	 * Writes a block position with a signed Y coordinate.
	 */
	public function putSignedBlockPosition(BlockPosition $blockPosition) : void{
		$this->putVarInt($blockPosition->getX());
		$this->putVarInt($blockPosition->getY());
		$this->putVarInt($blockPosition->getZ());
	}

	/**
	 * Reads a floating-point Vector3 object with coordinates rounded to 4 decimal places.
	 *
	 * @throws BinaryDataException
	 */
	public function getVector3() : Vector3{
		$x = $this->getLFloat();
		$y = $this->getLFloat();
		$z = $this->getLFloat();
		return new Vector3($x, $y, $z);
	}

	/**
	 * Writes a floating-point Vector3 object, or 3x zero if null is given.
	 *
	 * Note: ONLY use this where it is reasonable to allow not specifying the vector.
	 * For all other purposes, use the non-nullable version.
	 *
	 * @see PacketSerializer::putVector3()
	 */
	public function putVector3Nullable(?Vector3 $vector) : void{
		if($vector !== null){
			$this->putVector3($vector);
		}else{
			$this->putLFloat(0.0);
			$this->putLFloat(0.0);
			$this->putLFloat(0.0);
		}
	}

	/**
	 * Writes a floating-point Vector3 object
	 */
	public function putVector3(Vector3 $vector) : void{
		$this->putLFloat($vector->x);
		$this->putLFloat($vector->y);
		$this->putLFloat($vector->z);
	}

	/**
	 * @throws BinaryDataException
	 */
	public function getRotationByte() : float{
		return ($this->getByte() * (360 / 256));
	}

	public function putRotationByte(float $rotation) : void{
		$this->putByte((int) ($rotation / (360 / 256)));
	}

	private function readGameRule(int $type, bool $isPlayerModifiable) : GameRule{
		return match($type){
			BoolGameRule::ID => BoolGameRule::decode($this, $isPlayerModifiable),
			IntGameRule::ID => IntGameRule::decode($this, $isPlayerModifiable),
			FloatGameRule::ID => FloatGameRule::decode($this, $isPlayerModifiable),
			default => throw new PacketDecodeException("Unknown gamerule type $type"),
		};
	}

	/**
	 * Reads gamerules
	 *
	 * @return GameRule[] game rule name => value
	 * @phpstan-return array<string, GameRule>
	 *
	 * @throws PacketDecodeException
	 * @throws BinaryDataException
	 */
	public function getGameRules() : array{
		$count = $this->getUnsignedVarInt();
		$rules = [];
		for($i = 0; $i < $count; ++$i){
			$name = $this->getString();
			if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_17_0){
				$isPlayerModifiable = $this->getBool();
			}else{
				$isPlayerModifiable = false;
			}
			$type = $this->getUnsignedVarInt();
			$rules[$name] = $this->readGameRule($type, $isPlayerModifiable);
		}

		return $rules;
	}

	/**
	 * Writes a gamerule array
	 *
	 * @param GameRule[] $rules
	 * @phpstan-param array<string, GameRule> $rules
	 */
	public function putGameRules(array $rules) : void{
		$this->putUnsignedVarInt(count($rules));
		foreach($rules as $name => $rule){
			$this->putString($name);
			if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_17_0){
				$this->putBool($rule->isPlayerModifiable());
			}
			$this->putUnsignedVarInt($rule->getTypeId());
			$rule->encode($this);
		}
	}

	/**
	 * @throws BinaryDataException
	 */
	public function getEntityLink() : EntityLink{
		$fromActorUniqueId = $this->getActorUniqueId();
		$toActorUniqueId = $this->getActorUniqueId();
		$type = $this->getByte();
		$immediate = $this->getBool();
		if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_0){
			$causedByRider = $this->getBool();
		}
		return new EntityLink($fromActorUniqueId, $toActorUniqueId, $type, $immediate, $causedByRider ?? false);
	}

	public function putEntityLink(EntityLink $link) : void{
		$this->putActorUniqueId($link->fromActorUniqueId);
		$this->putActorUniqueId($link->toActorUniqueId);
		$this->putByte($link->type);
		$this->putBool($link->immediate);
		if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_0){
			$this->putBool($link->causedByRider);
		}
	}

	/**
	 * @throws BinaryDataException
	 */
	public function getCommandOriginData() : CommandOriginData{
		$result = new CommandOriginData();

		$result->type = $this->getUnsignedVarInt();
		$result->uuid = $this->getUUID();
		$result->requestId = $this->getString();

		if($result->type === CommandOriginData::ORIGIN_DEV_CONSOLE or $result->type === CommandOriginData::ORIGIN_TEST){
			$result->playerActorUniqueId = $this->getVarLong();
		}

		return $result;
	}

	public function putCommandOriginData(CommandOriginData $data) : void{
		$this->putUnsignedVarInt($data->type);
		$this->putUUID($data->uuid);
		$this->putString($data->requestId);

		if($data->type === CommandOriginData::ORIGIN_DEV_CONSOLE or $data->type === CommandOriginData::ORIGIN_TEST){
			$this->putVarLong($data->playerActorUniqueId);
		}
	}

	public function getStructureSettings(int $protocolId) : StructureSettings{
		$result = new StructureSettings();

		$result->paletteName = $this->getString();

		$result->ignoreEntities = $this->getBool();
		$result->ignoreBlocks = $this->getBool();
		if($protocolId >= ProtocolInfo::PROTOCOL_1_19_50){
			$result->allowNonTickingChunks = $this->getBool();
		}else{
			$result->allowNonTickingChunks = false;
		}

		$result->dimensions = $this->getBlockPosition();
		$result->offset = $this->getBlockPosition();

		$result->lastTouchedByPlayerID = $this->getActorUniqueId();
		$result->rotation = $this->getByte();
		$result->mirror = $this->getByte();
		if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_17_0){
			$result->animationMode = $this->getByte();
			$result->animationSeconds = $this->getLFloat();
		}
		$result->integrityValue = $this->getLFloat();
		$result->integritySeed = $this->getLInt();
		if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_13_0){
			$result->pivot = $this->getVector3();
		}

		return $result;
	}

	public function putStructureSettings(StructureSettings $structureSettings, int $protocolId) : void{
		$this->putString($structureSettings->paletteName);

		$this->putBool($structureSettings->ignoreEntities);
		$this->putBool($structureSettings->ignoreBlocks);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_19_50){
			$this->putBool($structureSettings->allowNonTickingChunks);
		}

		$this->putBlockPosition($structureSettings->dimensions);
		$this->putBlockPosition($structureSettings->offset);

		$this->putActorUniqueId($structureSettings->lastTouchedByPlayerID);
		$this->putByte($structureSettings->rotation);
		$this->putByte($structureSettings->mirror);
		if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_17_0){
			$this->putByte($structureSettings->animationMode);
			$this->putLFloat($structureSettings->animationSeconds);
		}
		$this->putLFloat($structureSettings->integrityValue);
		$this->putLInt($structureSettings->integritySeed);
		if($this->getProtocolId() >= ProtocolInfo::PROTOCOL_1_13_0){
			$this->putVector3($structureSettings->pivot);
		}
	}

	public function getStructureEditorData(int $protocolId) : StructureEditorData{
		$result = new StructureEditorData();

		$result->structureName = $this->getString();
		$result->structureDataField = $this->getString();

		$result->includePlayers = $this->getBool();
		$result->showBoundingBox = $this->getBool();

		$result->structureBlockType = $this->getVarInt();
		$result->structureSettings = $this->getStructureSettings($protocolId);
		$result->structureRedstoneSaveMove = $this->getVarInt();

		return $result;
	}

	public function putStructureEditorData(StructureEditorData $structureEditorData, int $protocolId) : void{
		$this->putString($structureEditorData->structureName);
		$this->putString($structureEditorData->structureDataField);

		$this->putBool($structureEditorData->includePlayers);
		$this->putBool($structureEditorData->showBoundingBox);

		$this->putVarInt($structureEditorData->structureBlockType);
		$this->putStructureSettings($structureEditorData->structureSettings, $protocolId);
		$this->putVarInt($structureEditorData->structureRedstoneSaveMove);
	}

	public function getNbtRoot() : TreeRoot{
		$offset = $this->getOffset();
		try{
			return (new NetworkNbtSerializer())->read($this->getBuffer(), $offset, 512);
		}catch(NbtDataException $e){
			throw PacketDecodeException::wrap($e, "Failed decoding NBT root");
		}finally{
			$this->setOffset($offset);
		}
	}

	public function getNbtCompoundRoot() : CompoundTag{
		try{
			return $this->getNbtRoot()->mustGetCompoundTag();
		}catch(NbtDataException $e){
			throw PacketDecodeException::wrap($e, "Expected TAG_Compound NBT root");
		}
	}

	public function readGenericTypeNetworkId() : int{
		return $this->getVarInt();
	}

	public function writeGenericTypeNetworkId(int $id) : void{
		$this->putVarInt($id);
	}

	/**
	 * @phpstan-template T
	 * @phpstan-param \Closure() : T $reader
	 * @phpstan-return T|null
	 */
	public function readOptional(\Closure $reader) : mixed{
		if($this->getBool()){
			return $reader();
		}
		return null;
	}

	/**
	 * @phpstan-template T
	 * @phpstan-param T|null $value
	 * @phpstan-param \Closure(T) : void $writer
	 */
	public function writeOptional(mixed $value, \Closure $writer) : void{
		if($value !== null){
			$this->putBool(true);
			$writer($value);
		}else{
			$this->putBool(false);
		}
	}
}
