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

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\color\Color;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\command\CommandOriginData;
use pocketmine\network\mcpe\protocol\types\entity\BlockPosMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\CompoundTagMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
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
use pocketmine\network\mcpe\protocol\types\inventory\HandSlot;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\NullGameRule;
use pocketmine\network\mcpe\protocol\types\recipe\ItemDescriptorType;
use pocketmine\network\mcpe\protocol\types\recipe\MolangItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use pocketmine\network\mcpe\protocol\types\recipe\StringIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\TagItemDescriptor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPieceType;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinArmSizeType;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use pocketmine\network\mcpe\protocol\types\StructureEditorData;
use pocketmine\network\mcpe\protocol\types\StructureSettings;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function count;
use function strlen;
use function strrev;
use function substr;

final class CommonTypes{

	private function __construct(){
		//NOOP
	}

	/** @throws DataDecodeException */
	public static function getString(ByteBufferReader $in) : string{
		return $in->readByteArray(VarInt::readUnsignedInt($in));
	}

	public static function putString(ByteBufferWriter $out, string $v) : void{
		VarInt::writeUnsignedInt($out, strlen($v));
		$out->writeByteArray($v);
	}

	/** @throws DataDecodeException */
	public static function getBool(ByteBufferReader $in) : bool{
		return Byte::readUnsigned($in) !== 0;
	}

	public static function putBool(ByteBufferWriter $out, bool $v) : void{
		Byte::writeUnsigned($out, $v ? 1 : 0);
	}

	/** @throws DataDecodeException */
	public static function getUUID(ByteBufferReader $in) : UuidInterface{
		//This is two little-endian longs: bytes 7-0 followed by bytes 15-8
		$p1 = strrev($in->readByteArray(8));
		$p2 = strrev($in->readByteArray(8));
		return Uuid::fromBytes($p1 . $p2);
	}

	public static function putUUID(ByteBufferWriter $out, UuidInterface $uuid) : void{
		$bytes = $uuid->getBytes();
		$out->writeByteArray(strrev(substr($bytes, 0, 8)));
		$out->writeByteArray(strrev(substr($bytes, 8, 8)));
	}

	public static function readColor(ByteBufferReader $in) : Color{
		return Color::fromARGB(LE::readUnsignedInt($in));
	}

	public static function writeColor(ByteBufferWriter $out, Color $color) : void{
		LE::writeUnsignedInt($out, $color->toARGB());
	}

	/**
	 * @throws PacketDecodeException
	 * @throws DataDecodeException
	 */
	public static function readHandSlot(ByteBufferReader $in) : HandSlot{
		return HandSlot::fromPacket(Byte::readUnsigned($in));
	}

	public static function writeHandSlot(ByteBufferWriter $out, HandSlot $hand) : void{
		Byte::writeUnsigned($out, $hand->value);
	}

	/** @throws DataDecodeException */
	public static function getSkin(ByteBufferReader $in) : SkinData{
		$skinId = self::getString($in);
		$skinResourcePatch = self::getString($in);
		$skinData = self::getSkinImage($in);
		$animations = self::readList($in, static function(ByteBufferReader $in) : SkinAnimation{
			$skinImage = self::getSkinImage($in);
			$animationType = VarInt::readUnsignedInt($in);
			$animationFrames = LE::readFloat($in);
			$expressionType = VarInt::readUnsignedInt($in);
			return new SkinAnimation($skinImage, $animationType, $animationFrames, $expressionType);
		});
		$capeData = self::getSkinImage($in);
		$geometryData = self::getString($in);
		$geometryDataVersion = self::getString($in);
		$animationData = self::getString($in);
		$capeId = self::getString($in);
		$fullSkinId = self::getString($in);
		$armSize = SkinArmSizeType::fromOrdinal(Byte::readUnsigned($in));
		$skinColor = self::readColor($in);
		$personaPieces = self::readList($in, static function(ByteBufferReader $in) : PersonaSkinPiece{
			$pieceId = self::getString($in);
			$pieceType = PersonaSkinPieceType::fromOrdinal(LE::readUnsignedInt($in));
			$packId = self::getUUID($in);
			$isDefaultPiece = self::getBool($in);
			$productId = self::getString($in);
			return new PersonaSkinPiece($pieceId, $pieceType, $packId, $isDefaultPiece, $productId);
		});
		$pieceTintColors = self::readList($in, static function(ByteBufferReader $in) : PersonaPieceTintColor{
			$pieceType = PersonaSkinPieceType::fromPacket(self::getString($in));
			$colors = [];
			for($j = 0; $j < PersonaPieceTintColor::EXPECTED_COLOR_COUNT; ++$j){
				$colors[] = self::readColor($in);
			}
			/** @phpstan-var array{Color, Color, Color, Color} $colors */
			return new PersonaPieceTintColor(
				$pieceType,
				$colors
			);
		});

		$premium = self::getBool($in);
		$persona = self::getBool($in);
		$capeOnClassic = self::getBool($in);
		$isPrimaryUser = self::getBool($in);
		$override = self::getBool($in);
		$trustedSkinFlag = self::getString($in);
		$profileHash = self::getString($in);

		return new SkinData(
			$skinId,
			$skinResourcePatch,
			$skinData,
			$animations,
			$capeData,
			$geometryData,
			$geometryDataVersion,
			$animationData,
			$capeId,
			$fullSkinId,
			$armSize,
			$skinColor,
			$personaPieces,
			$pieceTintColors,
			$trustedSkinFlag,
			$premium,
			$persona,
			$capeOnClassic,
			$isPrimaryUser,
			$override,
			$profileHash
		);
	}

	public static function putSkin(ByteBufferWriter $out, SkinData $skin) : void{
		self::putString($out, $skin->getSkinId());
		self::putString($out, $skin->getResourcePatch());
		self::putSkinImage($out, $skin->getSkinImage());
		self::writeList($out, $skin->getAnimations(), function(ByteBufferWriter $out, SkinAnimation $animation) : void{
			self::putSkinImage($out, $animation->getImage());
			VarInt::writeUnsignedInt($out, $animation->getType());
			LE::writeFloat($out, $animation->getFrames());
			VarInt::writeUnsignedInt($out, $animation->getExpressionType());
		});
		self::putSkinImage($out, $skin->getCapeImage());
		self::putString($out, $skin->getGeometryDataJson());
		self::putString($out, $skin->getGeometryDataEngineVersion());
		self::putString($out, $skin->getAnimationData());
		self::putString($out, $skin->getCapeId());
		self::putString($out, $skin->getFullSkinId());
		Byte::writeUnsigned($out, $skin->getArmSize()->toOrdinal());
		self::writeColor($out, $skin->getSkinColor());
		self::writeList($out, $skin->getPersonaPieces(), function(ByteBufferWriter $out, PersonaSkinPiece $piece) : void{
			self::putString($out, $piece->getPieceId());
			LE::writeUnsignedInt($out, $piece->getPieceType()->toOrdinal());
			self::putUUID($out, $piece->getPackId());
			self::putBool($out, $piece->isDefaultPiece());
			self::putString($out, $piece->getProductId());
		});
		self::writeList($out, $skin->getPieceTintColors(), function(ByteBufferWriter $out, PersonaPieceTintColor $tint) : void{
			self::putString($out, $tint->getPieceType()->value);
			foreach($tint->getColors() as $color){
				self::writeColor($out, $color);
			}
		});
		self::putBool($out, $skin->isPremium());
		self::putBool($out, $skin->isPersona());
		self::putBool($out, $skin->isPersonaCapeOnClassic());
		self::putBool($out, $skin->isPrimaryUser());
		self::putBool($out, $skin->isOverride());
		self::putString($out, $skin->getTrustedSkinFlag());
		self::putString($out, $skin->getProfileHash());
	}

	/** @throws DataDecodeException */
	private static function getSkinImage(ByteBufferReader $in) : SkinImage{
		$width = LE::readUnsignedInt($in);
		$height = LE::readUnsignedInt($in);
		$data = self::getString($in);
		try{
			return new SkinImage($height, $width, $data);
		}catch(\InvalidArgumentException $e){
			throw new PacketDecodeException($e->getMessage(), 0, $e);
		}
	}

	private static function putSkinImage(ByteBufferWriter $out, SkinImage $image) : void{
		LE::writeUnsignedInt($out, $image->getWidth());
		LE::writeUnsignedInt($out, $image->getHeight());
		self::putString($out, $image->getData());
	}

	/**
	 * Spec name:
	 * @throws PacketDecodeException
	 * @throws DataDecodeException
	 */
	public static function getItemStackWithoutStackId(ByteBufferReader $in) : ItemStack{
		$id = VarInt::readSignedInt($in);
		$count = LE::readUnsignedShort($in);
		$meta = VarInt::readUnsignedInt($in);

		$blockRuntimeId = VarInt::readSignedInt($in);
		$rawExtraData = self::getString($in);

		return new ItemStack($id, $meta, $count, $blockRuntimeId, $rawExtraData);
	}

	public static function putItemStackWithoutStackId(ByteBufferWriter $out, ItemStack $itemStack) : void{
		VarInt::writeSignedInt($out, $itemStack->getId());
		LE::writeUnsignedShort($out, $itemStack->getCount());
		VarInt::writeUnsignedInt($out, $itemStack->getMeta());

		VarInt::writeSignedInt($out, $itemStack->getBlockRuntimeId());
		self::putString($out, $itemStack->getRawExtraData());
	}

	public static function getItemStackWrapper(ByteBufferReader $in) : ItemStackWrapper{
		$id = LE::readSignedShort($in);
		$count = LE::readUnsignedShort($in);
		$meta = VarInt::readUnsignedInt($in);

		$hasNetId = self::getBool($in);
		$stackId = $hasNetId ? VarInt::readSignedInt($in) : 0;

		$blockRuntimeId = VarInt::readUnsignedInt($in);
		$rawExtraData = self::getString($in);

		return new ItemStackWrapper($stackId, new ItemStack($id, $meta, $count, $blockRuntimeId, $rawExtraData));
	}

	public static function putItemStackWrapper(ByteBufferWriter $out, ItemStackWrapper $itemStackWrapper) : void{
		LE::writeSignedShort($out, $itemStackWrapper->getItemStack()->getId());
		LE::writeUnsignedShort($out, $itemStackWrapper->getItemStack()->getCount());
		VarInt::writeUnsignedInt($out, $itemStackWrapper->getItemStack()->getMeta());

		self::putBool($out, $hasNetId = $itemStackWrapper->getStackId() !== 0);
		if($hasNetId){
			VarInt::writeSignedInt($out, $itemStackWrapper->getStackId());
		}

		VarInt::writeUnsignedInt($out, $itemStackWrapper->getItemStack()->getBlockRuntimeId());
		self::putString($out, $itemStackWrapper->getItemStack()->getRawExtraData());
	}

	public static function readItemDescriptorNormal(ByteBufferReader $in) : StringIdMetaItemDescriptor|TagItemDescriptor|MolangItemDescriptor|null{
		$descriptorTypeOrd = VarInt::readUnsignedInt($in);
		$innerTypeOrd = Byte::readUnsigned($in);
		if($descriptorTypeOrd !== $innerTypeOrd){
			throw new PacketDecodeException("Item descriptor type mismatch: outer type $descriptorTypeOrd, inner type $innerTypeOrd");
		}

		$descriptorType = ItemDescriptorType::fromOrdinal($descriptorTypeOrd);
		return match($descriptorType){
			ItemDescriptorType::STRING_ID_META => StringIdMetaItemDescriptor::read($in),
			ItemDescriptorType::TAG => TagItemDescriptor::readTagOnly($in),
			ItemDescriptorType::MOLANG => MolangItemDescriptor::read($in),
			ItemDescriptorType::EMPTY => null,
		};
	}

	public static function writeItemDescriptorNormal(ByteBufferWriter $out, StringIdMetaItemDescriptor|TagItemDescriptor|MolangItemDescriptor|null $descriptor) : void{
		$typeOrd = ($descriptor?->getDescriptorType() ?? ItemDescriptorType::EMPTY)->toOrdinal();
		VarInt::writeUnsignedInt($out, $typeOrd);
		Byte::writeUnsigned($out, $typeOrd);
		if($descriptor instanceof TagItemDescriptor){
			$descriptor->writeTagOnly($out);
		}else{
			$descriptor?->write($out);
		}
	}

	public static function readItemDescriptorMess(ByteBufferReader $in) : StringIdMetaItemDescriptor|TagItemDescriptor|MolangItemDescriptor|null{
		$something = Byte::readUnsigned($in);
		if($something === 0){
			$meta = VarInt::readSignedInt($in);
			if($meta !== 32767){
				throw new PacketDecodeException("Expected meta 32767 for empty item descriptor, got $meta");
			}
			return null;
		}elseif($something !== 1){
			throw new PacketDecodeException("Expected 0 or 1 for item descriptor variant, got $something");
		}
		$descriptorType = ItemDescriptorType::fromPacket(self::getString($in));

		return match($descriptorType){
			ItemDescriptorType::STRING_ID_META => StringIdMetaItemDescriptor::read($in),
			ItemDescriptorType::TAG => TagItemDescriptor::read($in),
			ItemDescriptorType::MOLANG => MolangItemDescriptor::read($in),
			ItemDescriptorType::EMPTY => null,
		};
	}

	public static function writeItemDescriptorMess(ByteBufferWriter $out, StringIdMetaItemDescriptor|TagItemDescriptor|MolangItemDescriptor|null $descriptor) : void{
		if($descriptor === null){
			Byte::writeUnsigned($out, 0);
			VarInt::writeSignedInt($out, 32767);
			return;
		}
		Byte::writeUnsigned($out, 1);
		self::putString($out, $descriptor->getDescriptorType()->value);
		$descriptor->write($out);
	}

	/** @throws DataDecodeException */
	public static function getRecipeIngredient(ByteBufferReader $in) : RecipeIngredient{
		$descriptor = self::readItemDescriptorMess($in);
		$count = VarInt::readSignedInt($in);

		return new RecipeIngredient($descriptor, $count);
	}

	public static function putRecipeIngredient(ByteBufferWriter $out, RecipeIngredient $ingredient) : void{
		self::writeItemDescriptorMess($out, $ingredient->getDescriptor());
		VarInt::writeSignedInt($out, $ingredient->getCount());
	}

	/**
	 * @throws DataDecodeException
	 * @throws PacketDecodeException
	 */
	public static function readStackRequestIngredient(ByteBufferReader $in) : RecipeIngredient{
		$descriptor = self::readItemDescriptorNormal($in);
		$count = LE::readUnsignedShort($in);

		return new RecipeIngredient($descriptor, $count);
	}

	public static function writeStackRequestIngredient(ByteBufferWriter $out, RecipeIngredient $ingredient) : void{
		self::writeItemDescriptorNormal($out, $ingredient->getDescriptor());
		LE::writeUnsignedShort($out, $ingredient->getCount());
	}

	/**
	 * Decodes entity metadata from the stream.
	 *
	 * @return MetadataProperty[]
	 * @phpstan-return array<int, MetadataProperty>
	 *
	 * @throws PacketDecodeException
	 * @throws DataDecodeException
	 */
	public static function getEntityMetadata(ByteBufferReader $in) : array{
		$count = VarInt::readUnsignedInt($in);
		$data = [];
		for($i = 0; $i < $count; ++$i){
			$key = VarInt::readUnsignedInt($in);
			if(isset($data[$key])){
				throw new PacketDecodeException("Duplicate entity metadata key $key");
			}
			$type = VarInt::readUnsignedInt($in);
			$innerType = Byte::readUnsigned($in);
			if($type !== $innerType){
				throw new PacketDecodeException("Entity metadata type mismatch: expected $type, got $innerType");
			}

			$data[$key] = self::readMetadataProperty($in, $type);
		}

		return $data;
	}

	/** @throws DataDecodeException */
	private static function readMetadataProperty(ByteBufferReader $in, int $type) : MetadataProperty{
		return match($type){
			ByteMetadataProperty::ID => ByteMetadataProperty::read($in),
			ShortMetadataProperty::ID => ShortMetadataProperty::read($in),
			IntMetadataProperty::ID => IntMetadataProperty::read($in),
			FloatMetadataProperty::ID => FloatMetadataProperty::read($in),
			StringMetadataProperty::ID => StringMetadataProperty::read($in),
			CompoundTagMetadataProperty::ID => CompoundTagMetadataProperty::read($in),
			BlockPosMetadataProperty::ID => BlockPosMetadataProperty::read($in),
			LongMetadataProperty::ID => LongMetadataProperty::read($in),
			Vec3MetadataProperty::ID => Vec3MetadataProperty::read($in),
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
	public static function putEntityMetadata(ByteBufferWriter $out, array $metadata) : void{
		VarInt::writeUnsignedInt($out, count($metadata));
		foreach($metadata as $key => $d){
			VarInt::writeUnsignedInt($out, $key);
			VarInt::writeUnsignedInt($out, $d->getTypeId());
			Byte::writeUnsigned($out, $d->getTypeId());
			$d->write($out);
		}
	}

	/** @throws DataDecodeException */
	public static function getActorUniqueId(ByteBufferReader $in) : int{
		return VarInt::readSignedLong($in);
	}

	public static function putActorUniqueId(ByteBufferWriter $out, int $eid) : void{
		VarInt::writeSignedLong($out, $eid);
	}

	/** @throws DataDecodeException */
	public static function getActorRuntimeId(ByteBufferReader $in) : int{
		return VarInt::readUnsignedLong($in);
	}

	public static function putActorRuntimeId(ByteBufferWriter $out, int $eid) : void{
		VarInt::writeUnsignedLong($out, $eid);
	}

	/**
	 * Reads a block position
	 *
	 * @throws DataDecodeException
	 */
	public static function getBlockPosition(ByteBufferReader $in) : BlockPosition{
		$x = VarInt::readSignedInt($in);
		$y = VarInt::readSignedInt($in);
		$z = VarInt::readSignedInt($in);
		return new BlockPosition($x, $y, $z);
	}

	/**
	 * Writes a block position
	 */
	public static function putBlockPosition(ByteBufferWriter $out, BlockPosition $blockPosition) : void{
		VarInt::writeSignedInt($out, $blockPosition->getX());
		VarInt::writeSignedInt($out, $blockPosition->getY());
		VarInt::writeSignedInt($out, $blockPosition->getZ());
	}

	/**
	 * Reads a floating-point Vector3 object with coordinates rounded to 4 decimal places.
	 *
	 * @throws DataDecodeException
	 */
	public static function getVector3(ByteBufferReader $in) : Vector3{
		$x = LE::readFloat($in);
		$y = LE::readFloat($in);
		$z = LE::readFloat($in);
		return new Vector3($x, $y, $z);
	}

	/**
	 * Reads a floating-point Vector2 object with coordinates rounded to 4 decimal places.
	 *
	 * @throws DataDecodeException
	 */
	public static function getVector2(ByteBufferReader $in) : Vector2{
		$x = LE::readFloat($in);
		$y = LE::readFloat($in);
		return new Vector2($x, $y);
	}

	/**
	 * Writes a floating-point Vector3 object, or 3x zero if null is given.
	 *
	 * Note: ONLY use this where it is reasonable to allow not specifying the vector.
	 * For all other purposes, use the non-nullable version.
	 *
	 * @see CommonTypes::putVector3()
	 */
	public static function putVector3Nullable(ByteBufferWriter $out, ?Vector3 $vector) : void{
		if($vector !== null){
			self::putVector3($out, $vector);
		}else{
			LE::writeFloat($out, 0.0);
			LE::writeFloat($out, 0.0);
			LE::writeFloat($out, 0.0);
		}
	}

	/**
	 * Writes a floating-point Vector3 object
	 */
	public static function putVector3(ByteBufferWriter $out, Vector3 $vector) : void{
		LE::writeFloat($out, $vector->x);
		LE::writeFloat($out, $vector->y);
		LE::writeFloat($out, $vector->z);
	}

	/**
	 * Writes a floating-point Vector2 object
	 */
	public static function putVector2(ByteBufferWriter $out, Vector2 $vector2) : void{
		LE::writeFloat($out, $vector2->x);
		LE::writeFloat($out, $vector2->y);
	}

	/** @throws DataDecodeException */
	public static function getRotationByte(ByteBufferReader $in) : float{
		return Byte::readUnsigned($in) * (360 / 256);
	}

	public static function putRotationByte(ByteBufferWriter $out, float $rotation) : void{
		Byte::writeUnsigned($out, (int) ($rotation / (360 / 256)));
	}

	/** @throws DataDecodeException */
	private static function readGameRule(ByteBufferReader $in, int $type, bool $isPlayerModifiable) : GameRule{
		return match($type){
			NullGameRule::ID => NullGameRule::decode($in, $isPlayerModifiable),
			BoolGameRule::ID => BoolGameRule::decode($in, $isPlayerModifiable),
			IntGameRule::ID => IntGameRule::decode($in, $isPlayerModifiable),
			FloatGameRule::ID => FloatGameRule::decode($in, $isPlayerModifiable),
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
	 * @throws DataDecodeException
	 */
	public static function getGameRules(ByteBufferReader $in) : array{
		$count = VarInt::readUnsignedInt($in);
		$rules = [];
		for($i = 0; $i < $count; ++$i){
			$name = self::getString($in);
			if(isset($rules[$name])){
				throw new PacketDecodeException("Duplicate gamerule $name");
			}
			$isPlayerModifiable = self::getBool($in);
			$type = VarInt::readUnsignedInt($in);
			$rules[$name] = self::readGameRule($in, $type, $isPlayerModifiable);
		}

		return $rules;
	}

	/**
	 * Writes a gamerule array
	 *
	 * @param GameRule[] $rules
	 * @phpstan-param array<string, GameRule> $rules
	 */
	public static function putGameRules(ByteBufferWriter $out, array $rules) : void{
		VarInt::writeUnsignedInt($out, count($rules));
		foreach($rules as $name => $rule){
			self::putString($out, $name);
			self::putBool($out, $rule->isPlayerModifiable());
			VarInt::writeUnsignedInt($out, $rule->getTypeId());
			$rule->encode($out);
		}
	}

	/** @throws DataDecodeException */
	public static function getEntityLink(ByteBufferReader $in) : EntityLink{
		$fromActorUniqueId = self::getActorUniqueId($in);
		$toActorUniqueId = self::getActorUniqueId($in);
		$type = Byte::readUnsigned($in);
		$immediate = self::getBool($in);
		$causedByRider = self::getBool($in);
		$vehicleAngularVelocity = LE::readFloat($in);
		return new EntityLink($fromActorUniqueId, $toActorUniqueId, $type, $immediate, $causedByRider, $vehicleAngularVelocity);
	}

	public static function putEntityLink(ByteBufferWriter $out, EntityLink $link) : void{
		self::putActorUniqueId($out, $link->fromActorUniqueId);
		self::putActorUniqueId($out, $link->toActorUniqueId);
		Byte::writeUnsigned($out, $link->type);
		self::putBool($out, $link->immediate);
		self::putBool($out, $link->causedByRider);
		LE::writeFloat($out, $link->vehicleAngularVelocity);
	}

	/** @throws DataDecodeException */
	public static function getCommandOriginData(ByteBufferReader $in) : CommandOriginData{
		$result = new CommandOriginData();

		$result->type = CommonTypes::getString($in);
		$result->uuid = self::getUUID($in);
		$result->requestId = self::getString($in);
		$result->playerActorUniqueId = LE::readSignedLong($in);

		return $result;
	}

	public static function putCommandOriginData(ByteBufferWriter $out, CommandOriginData $data) : void{
		self::putString($out, $data->type);
		self::putUUID($out, $data->uuid);
		self::putString($out, $data->requestId);
		LE::writeSignedLong($out, $data->playerActorUniqueId);
	}

	/** @throws DataDecodeException */
	public static function getStructureSettings(ByteBufferReader $in) : StructureSettings{
		$result = new StructureSettings();

		$result->paletteName = self::getString($in);

		$result->ignoreEntities = self::getBool($in);
		$result->ignoreBlocks = self::getBool($in);
		$result->allowNonTickingChunks = self::getBool($in);

		$result->dimensions = self::getBlockPosition($in);
		$result->offset = self::getBlockPosition($in);

		$result->lastTouchedByPlayerID = self::getActorUniqueId($in);
		$result->rotation = Byte::readUnsigned($in);
		$result->mirror = Byte::readUnsigned($in);
		$result->animationMode = Byte::readUnsigned($in);
		$result->animationSeconds = LE::readFloat($in);
		$result->integrityValue = LE::readFloat($in);
		$result->integritySeed = LE::readUnsignedInt($in);
		$result->pivot = self::getVector3($in);

		return $result;
	}

	public static function putStructureSettings(ByteBufferWriter $out, StructureSettings $structureSettings) : void{
		self::putString($out, $structureSettings->paletteName);

		self::putBool($out, $structureSettings->ignoreEntities);
		self::putBool($out, $structureSettings->ignoreBlocks);
		self::putBool($out, $structureSettings->allowNonTickingChunks);

		self::putBlockPosition($out, $structureSettings->dimensions);
		self::putBlockPosition($out, $structureSettings->offset);

		self::putActorUniqueId($out, $structureSettings->lastTouchedByPlayerID);
		Byte::writeUnsigned($out, $structureSettings->rotation);
		Byte::writeUnsigned($out, $structureSettings->mirror);
		Byte::writeUnsigned($out, $structureSettings->animationMode);
		LE::writeFloat($out, $structureSettings->animationSeconds);
		LE::writeFloat($out, $structureSettings->integrityValue);
		LE::writeUnsignedInt($out, $structureSettings->integritySeed);
		self::putVector3($out, $structureSettings->pivot);
	}

	/** @throws DataDecodeException */
	public static function getStructureEditorData(ByteBufferReader $in) : StructureEditorData{
		$result = new StructureEditorData();

		$result->structureName = self::getString($in);
		$result->filteredStructureName = self::readOptional($in, self::getString(...));
		$result->structureDataField = self::getString($in);

		$result->includePlayers = self::getBool($in);
		$result->showBoundingBox = self::getBool($in);

		$result->structureBlockType = VarInt::readSignedInt($in);
		$result->structureSettings = self::getStructureSettings($in);
		$result->structureRedstoneSaveMode = Byte::readUnsigned($in);

		return $result;
	}

	public static function putStructureEditorData(ByteBufferWriter $out, StructureEditorData $structureEditorData) : void{
		self::putString($out, $structureEditorData->structureName);
		self::writeOptional($out, $structureEditorData->filteredStructureName, self::putString(...));
		self::putString($out, $structureEditorData->structureDataField);

		self::putBool($out, $structureEditorData->includePlayers);
		self::putBool($out, $structureEditorData->showBoundingBox);

		VarInt::writeSignedInt($out, $structureEditorData->structureBlockType);
		self::putStructureSettings($out, $structureEditorData->structureSettings);
		Byte::writeUnsigned($out, $structureEditorData->structureRedstoneSaveMode);
	}

	/** @throws PacketDecodeException */
	public static function getNbtRoot(ByteBufferReader $in) : TreeRoot{
		$offset = $in->getOffset();
		try{
			return (new NetworkNbtSerializer())->read($in->getData(), $offset, 512);
		}catch(NbtDataException $e){
			throw PacketDecodeException::wrap($e, "Failed decoding NBT root");
		}finally{
			$in->setOffset($offset);
		}
	}

	public static function getNbtCompoundRoot(ByteBufferReader $in) : CompoundTag{
		try{
			return self::getNbtRoot($in)->mustGetCompoundTag();
		}catch(NbtDataException $e){
			throw PacketDecodeException::wrap($e, "Expected TAG_Compound NBT root");
		}
	}

	/** @throws DataDecodeException */
	public static function readRecipeNetId(ByteBufferReader $in) : int{
		return VarInt::readUnsignedInt($in);
	}

	public static function writeRecipeNetId(ByteBufferWriter $out, int $id) : void{
		VarInt::writeUnsignedInt($out, $id);
	}

	/** @throws DataDecodeException */
	public static function readCreativeItemNetId(ByteBufferReader $in) : int{
		return VarInt::readUnsignedInt($in);
	}

	public static function writeCreativeItemNetId(ByteBufferWriter $out, int $id) : void{
		VarInt::writeUnsignedInt($out, $id);
	}

	/**
	 * This is a union of ItemStackRequestId, LegacyItemStackRequestId, and ServerItemStackId, used in serverbound
	 * packets to allow the client to refer to server known items, or items which may have been modified by a previous
	 * as-yet unacknowledged request from the client.
	 *
	 * - Server itemstack ID is positive
	 * - InventoryTransaction "legacy" request ID is negative and even
	 * - ItemStackRequest request ID is negative and odd
	 * - 0 refers to an empty itemstack (air)
	 *
	 * @throws DataDecodeException
	 */
	public static function readItemStackNetIdVariant(ByteBufferReader $in) : int{
		return LE::readSignedInt($in);
	}

	/**
	 * This is a union of ItemStackRequestId, LegacyItemStackRequestId, and ServerItemStackId, used in serverbound
	 * packets to allow the client to refer to server known items, or items which may have been modified by a previous
	 * as-yet unacknowledged request from the client.
	 */
	public static function writeItemStackNetIdVariant(ByteBufferWriter $out, int $id) : void{
		LE::writeSignedInt($out, $id);
	}

	/** @throws DataDecodeException */
	public static function readItemStackRequestId(ByteBufferReader $in) : int{
		return VarInt::readSignedInt($in);
	}

	public static function writeItemStackRequestId(ByteBufferWriter $out, int $id) : void{
		VarInt::writeSignedInt($out, $id);
	}

	/** @throws DataDecodeException */
	public static function readLegacyItemStackRequestId(ByteBufferReader $in) : int{
		return VarInt::readSignedInt($in);
	}

	public static function writeLegacyItemStackRequestId(ByteBufferWriter $out, int $id) : void{
		VarInt::writeSignedInt($out, $id);
	}

	/** @throws DataDecodeException */
	public static function readServerItemStackId(ByteBufferReader $in) : int{
		return VarInt::readSignedInt($in);
	}

	public static function writeServerItemStackId(ByteBufferWriter $out, int $id) : void{
		VarInt::writeSignedInt($out, $id);
	}

	/**
	 * @phpstan-template T
	 * @phpstan-param \Closure(ByteBufferReader) : T $reader
	 * @phpstan-return T|null
	 * @throws DataDecodeException
	 */
	public static function readOptional(ByteBufferReader $in, \Closure $reader) : mixed{
		if(self::getBool($in)){
			return $reader($in);
		}
		return null;
	}

	/**
	 * @phpstan-template T
	 * @phpstan-param T|null $value
	 * @phpstan-param \Closure(ByteBufferWriter, T) : void $writer
	 */
	public static function writeOptional(ByteBufferWriter $out, mixed $value, \Closure $writer) : void{
		if($value !== null){
			self::putBool($out, true);
			$writer($out, $value);
		}else{
			self::putBool($out, false);
		}
	}

	/**
	 * @throws DataDecodeException
	 */
	public static function readDummyOptional(ByteBufferReader $in) : void{
		$dummy = Byte::readUnsigned($in);
		if($dummy !== 1){
			throw new PacketDecodeException("Dummy optional first byte should always be 1, got $dummy");
		}
	}

	public static function writeDummyOptional(ByteBufferWriter $out) : void{
		Byte::writeUnsigned($out, 1);
	}

	/**
	 * @phpstan-template T
	 * @phpstan-param \Closure(ByteBufferReader) : T $reader
	 * @phpstan-return list<T>
	 * @throws DataDecodeException
	 */
	public static function readList(ByteBufferReader $in, \Closure $reader) : array{
		$count = VarInt::readUnsignedInt($in);
		$result = [];
		for($i = 0; $i < $count; ++$i){
			$result[] = $reader($in);
		}
		return $result;
	}

	/**
	 * @phpstan-template T
	 * @phpstan-param list<T> $list
	 * @phpstan-param \Closure(ByteBufferWriter, T) : void $writer
	 */
	public static function writeList(ByteBufferWriter $out, array $list, \Closure $writer) : void{
		VarInt::writeUnsignedInt($out, count($list));
		foreach($list as $item){
			$writer($out, $item);
		}
	}
}
