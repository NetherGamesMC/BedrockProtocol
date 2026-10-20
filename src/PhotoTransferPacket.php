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
use Ramsey\Uuid\Uuid;
use function str_ends_with;
use function strlen;
use function substr;

class PhotoTransferPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::PHOTO_TRANSFER_PACKET;

	private string $photoName;
	private string $photoData;
	private string $bookId;
	private int $type;
	private int $sourceType;
	private int $ownerActorUniqueId;
	private string $newPhotoName;

	private const PHOTO_NAME_EXTENSION = '.jpeg';
	private const PHOTO_NAME_EXTENSION_LENGTH = 5;
	private const MAX_PHOTO_DATA_SIZE = 20 * 1024 * 1024; // 20 MiB in bytes

	/**
	 * @generate-create-func
	 */
	private static function internalCreate(
		string $photoName,
		string $photoData,
		string $bookId,
		int $type,
		int $sourceType,
		int $ownerActorUniqueId,
		string $newPhotoName,
	) : self{
		$result = new self;
		$result->photoName = $photoName;
		$result->photoData = $photoData;
		$result->bookId = $bookId;
		$result->type = $type;
		$result->sourceType = $sourceType;
		$result->ownerActorUniqueId = $ownerActorUniqueId;
		$result->newPhotoName = $newPhotoName;
		return $result;
	}

	public static function create(
		string $photoName,
		string $photoData,
		string $bookId,
		int $type,
		int $sourceType,
		int $ownerActorUniqueId,
		string $newPhotoName,
	) : self{
		if(!str_ends_with($photoName, self::PHOTO_NAME_EXTENSION) || !Uuid::isValid(substr($photoName, 0, -self::PHOTO_NAME_EXTENSION_LENGTH))){
			throw new \InvalidArgumentException("Invalid photo name: '$photoName'. Must be a UUID followed by " . self::PHOTO_NAME_EXTENSION);
		}
		if(strlen($photoData) > self::MAX_PHOTO_DATA_SIZE){
			throw new \InvalidArgumentException("Photo data size (" . strlen($photoData) . " bytes) exceeds maximum allowed " . self::MAX_PHOTO_DATA_SIZE);
		}

		return self::internalCreate($photoName, $photoData, $bookId, $type, $sourceType, $ownerActorUniqueId, $newPhotoName);
	}

	public function getPhotoName() : string{ return $this->photoName; }

	public function getPhotoData() : string{ return $this->photoData; }

	public function getBookId() : string{ return $this->bookId; }

	public function getType() : int{ return $this->type; }

	public function getSourceType() : int{ return $this->sourceType; }

	public function getOwnerActorUniqueId() : int{ return $this->ownerActorUniqueId; }

	public function getNewPhotoName() : string{ return $this->newPhotoName; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->photoName = CommonTypes::getString($in);
		if(!str_ends_with($this->photoName, self::PHOTO_NAME_EXTENSION) || !Uuid::isValid(substr($this->photoName, 0, -self::PHOTO_NAME_EXTENSION_LENGTH))){
			throw new PacketDecodeException("Invalid photo name: '$this->photoName'. Must be a UUID followed by " . self::PHOTO_NAME_EXTENSION);
		}

		//TODO: reading the length manually before allocating is a hacky workaround;
		//a proper restriction/weight system for packet fields would be cleaner
		$photoDataLength = VarInt::readUnsignedInt($in);
		if($photoDataLength > self::MAX_PHOTO_DATA_SIZE){
			throw new PacketDecodeException("Photo data size ($photoDataLength bytes) exceeds maximum allowed " . self::MAX_PHOTO_DATA_SIZE);
		}
		$this->photoData = $in->readByteArray($photoDataLength);

		$this->bookId = CommonTypes::getString($in);
		$this->type = Byte::readUnsigned($in);
		$this->sourceType = Byte::readUnsigned($in);
		$this->ownerActorUniqueId = LE::readSignedLong($in);
		$this->newPhotoName = CommonTypes::getString($in);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->photoName);
		CommonTypes::putString($out, $this->photoData);
		CommonTypes::putString($out, $this->bookId);
		Byte::writeUnsigned($out, $this->type);
		Byte::writeUnsigned($out, $this->sourceType);
		LE::writeSignedLong($out, $this->ownerActorUniqueId);
		CommonTypes::putString($out, $this->newPhotoName);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePhotoTransfer($this);
	}
}
