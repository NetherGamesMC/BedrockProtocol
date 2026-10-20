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

namespace pocketmine\network\mcpe\protocol\types\inventory\stackresponse;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

/**
 * Spec name: ItemStackResponseInfo
 */
final class ItemStackResponse{

	public const RESULT_OK = 0;
	public const RESULT_ERROR = 1;
	//TODO: there are a ton more possible result types but we don't need them yet and they are wayyyyyy too many for me
	//to waste my time on right now...

	/**
	 * @var ItemStackResponseContainerInfo[]|null
	 * @phpstan-var list<ItemStackResponseContainerInfo>|null
	 */
	private ?array $containerInfos;

	/**
	 * Before 1.26.40, container infos are only sent (and always sent) if the result is RESULT_OK; null is written as an
	 * empty list there.
	 *
	 * @param ItemStackResponseContainerInfo[]|null $containerInfos
	 * @phpstan-param list<ItemStackResponseContainerInfo>|null $containerInfos
	 */
	public function __construct(
		private int $result,
		private int $requestId,
		?array $containerInfos = null
	){
		if($this->result !== self::RESULT_OK && $containerInfos !== null){
			if(count($containerInfos) !== 0){
				throw new \InvalidArgumentException("Container infos must be null if rejecting the request");
			}
			$containerInfos = null; //BC: older code passed an empty array
		}
		$this->containerInfos = $containerInfos;
	}

	public function getResult() : int{ return $this->result; }

	public function getRequestId() : int{ return $this->requestId; }

	/**
	 * @return ItemStackResponseContainerInfo[]|null
	 * @phpstan-return list<ItemStackResponseContainerInfo>|null
	 */
	public function getContainerInfos() : ?array{ return $this->containerInfos; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$result = Byte::readUnsigned($in);
		$requestId = CommonTypes::readItemStackRequestId($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			$containerInfos = CommonTypes::readOptional($in, static fn(ByteBufferReader $in) : array => CommonTypes::readList($in, static fn(ByteBufferReader $in) => ItemStackResponseContainerInfo::read($in, $protocolId)));
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$containerInfos = CommonTypes::readDoubleOptional($in, static fn(ByteBufferReader $in) : array => CommonTypes::readList($in, static fn(ByteBufferReader $in) => ItemStackResponseContainerInfo::read($in, $protocolId)));
		}else{
			$containerInfos = null;
			if($result === self::RESULT_OK){
				$containerInfos = CommonTypes::readList($in, static fn(ByteBufferReader $in) => ItemStackResponseContainerInfo::read($in, $protocolId));
			}
		}
		return new self($result, $requestId, $containerInfos);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		Byte::writeUnsigned($out, $this->result);
		CommonTypes::writeItemStackRequestId($out, $this->requestId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			CommonTypes::writeOptional($out, $this->containerInfos, static fn(ByteBufferWriter $out, array $list) => CommonTypes::writeList($out, $list, static fn(ByteBufferWriter $out, ItemStackResponseContainerInfo $v) => $v->write($out, $protocolId)));
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeDoubleOptional($out, $this->containerInfos, static fn(ByteBufferWriter $out, array $list) => CommonTypes::writeList($out, $list, static fn(ByteBufferWriter $out, ItemStackResponseContainerInfo $v) => $v->write($out, $protocolId)));
		}elseif($this->result === self::RESULT_OK){
			CommonTypes::writeList($out, $this->containerInfos ?? [], static fn(ByteBufferWriter $out, ItemStackResponseContainerInfo $v) => $v->write($out, $protocolId));
		}
	}
}
