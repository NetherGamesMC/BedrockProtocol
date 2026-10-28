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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

/**
 * @see AttributeUpdateLayers
 */
final class AttributeLayer{

	/**
	 * @param AttributeEnvironment[] $attributes
	 * @phpstan-param list<AttributeEnvironment> $attributes
	 */
	public function __construct(
		private string $name,
		/** >= ProtocolInfo::PROTOCOL_1_26_30 && <= ProtocolInfo::PROTOCOL_1_26_50 */
		private ?string $noiseName,
		private int $dimension,
		private AttributeLayerSettings $settings,
		private array $attributes,
	){}

	public function getName() : string{ return $this->name; }

	/** >= ProtocolInfo::PROTOCOL_1_26_30 && <= ProtocolInfo::PROTOCOL_1_26_50 */
	public function getNoiseName() : ?string{ return $this->noiseName; }

	public function getDimension() : int{ return $this->dimension; }

	public function getSettings() : AttributeLayerSettings{ return $this->settings; }

	/**
	 * @return AttributeEnvironment[]
	 * @phpstan-return list<AttributeEnvironment>
	 */
	public function getAttributes() : array{ return $this->attributes; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$name = CommonTypes::getString($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30 && $protocolId <= ProtocolInfo::PROTOCOL_1_26_50){
			$noiseName = CommonTypes::readOptional($in, CommonTypes::getString(...));
		}
		$dimension = VarInt::readSignedInt($in);
		$settings = AttributeLayerSettings::read($in, $protocolId);

		$attributes = [];
		for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
			$attributes[] = AttributeEnvironment::read($in, $protocolId);
		}

		return new self(
			$name,
			$noiseName ?? null,
			$dimension,
			$settings,
			$attributes,
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->name);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30 && $protocolId <= ProtocolInfo::PROTOCOL_1_26_50){
			CommonTypes::writeOptional($out, $this->noiseName, CommonTypes::putString(...));
		}
		VarInt::writeSignedInt($out, $this->dimension);
		$this->settings->write($out, $protocolId);

		VarInt::writeUnsignedInt($out, count($this->attributes));
		foreach($this->attributes as $attribute){
			$attribute->write($out, $protocolId);
		}
	}
}
