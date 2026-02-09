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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * @see ServerboundDataStorePacket&ClientboundDataStorePacket
 */
final class DataStoreUpdate extends DataStore{
	public const ID = DataStoreType::UPDATE;

	public function __construct(
		private string $name,
		private string $property,
		private string $path,
		private DataStoreValue $data,
		private int $updateCount,
		private int $pathUpdateCount,
	){}

	public function getTypeId() : int{
		return self::ID;
	}

	public function getName() : string{ return $this->name; }

	public function getProperty() : string{ return $this->property; }

	public function getPath() : string{ return $this->path; }

	public function getData() : DataStoreValue{ return $this->data; }

	public function getUpdateCount() : int{ return $this->updateCount; }

	public function getPathUpdateCount() : int{ return $this->pathUpdateCount; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$name = CommonTypes::getString($in);
		$property = CommonTypes::getString($in);
		$path = CommonTypes::getString($in);

		$data = match(VarInt::readUnsignedInt($in)){
			DataStoreValueType::DOUBLE => DoubleDataStoreValue::read($in),
			DataStoreValueType::BOOL => BoolDataStoreValue::read($in),
			DataStoreValueType::STRING => StringDataStoreValue::read($in),
			default => throw new PacketDecodeException("Unknown DataStoreValueType"),
		};

		$updateCount = LE::readUnsignedInt($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_0){
			$pathUpdateCount = LE::readUnsignedInt($in);
		}

		return new self(
			$name,
			$property,
			$path,
			$data,
			$updateCount,
			$pathUpdateCount ?? -1,
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->name);
		CommonTypes::putString($out, $this->property);
		CommonTypes::putString($out, $this->path);
		VarInt::writeUnsignedInt($out, $this->data->getTypeId());
		$this->data->write($out);
		LE::writeUnsignedInt($out, $this->updateCount);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_0) {
			LE::writeUnsignedInt($out, $this->pathUpdateCount);
		}
	}
}
