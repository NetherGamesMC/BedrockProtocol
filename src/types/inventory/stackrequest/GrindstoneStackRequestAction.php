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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;

/**
 * Repair and/or remove enchantments from an item in a grindstone.
 */
final class GrindstoneStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CRAFTING_GRINDSTONE;

	public function __construct(
		private int $recipeId,
		private int $repairCost,
		private int $repetitions
	){}

	public function getRecipeId() : int{ return $this->recipeId; }

	/** WARNING: This may be negative */
	public function getRepairCost() : int{ return $this->repairCost; }

	public function getRepetitions() : int{ return $this->repetitions; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$recipeId = CommonTypes::readRecipeNetId($in);
		$repairCost = VarInt::readSignedInt($in); //WHY!!!!
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			$repetitions = Byte::readUnsigned($in);
		}

		return new self($recipeId, $repairCost, $repetitions ?? 0);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::writeRecipeNetId($out, $this->recipeId);
		VarInt::writeSignedInt($out, $this->repairCost);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			Byte::writeUnsigned($out, $this->repetitions);
		}
	}
}
