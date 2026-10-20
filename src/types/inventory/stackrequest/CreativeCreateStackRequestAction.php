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

namespace pocketmine\network\mcpe\protocol\types\inventory\stackrequest;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;

/**
 * Creates an item by copying it from the creative inventory. This is treated as a crafting action by vanilla.
 * Spec name: ItemStackRequestCraftCreativeAction
 */
final class CreativeCreateStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CREATIVE_CREATE;

	public function __construct(
		private int $creativeItemId,
		private int $repetitions
	){}

	public function getCreativeItemId() : int{ return $this->creativeItemId; }

	public function getRepetitions() : int{ return $this->repetitions; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$creativeItemId = CommonTypes::readCreativeItemNetId($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			$repetitions = Byte::readUnsigned($in);
		}
		return new self($creativeItemId, $repetitions ?? 0);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::writeCreativeItemNetId($out, $this->creativeItemId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			Byte::writeUnsigned($out, $this->repetitions);
		}
	}
}
