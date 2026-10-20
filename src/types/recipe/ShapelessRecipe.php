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

namespace pocketmine\network\mcpe\protocol\types\recipe;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use Ramsey\Uuid\UuidInterface;
use function count;

final class ShapelessRecipe{
	/**
	 * @param RecipeIngredient[] $inputs
	 * @param ItemStack[]        $outputs
	 * @phpstan-param list<RecipeIngredient> $inputs
	 * @phpstan-param list<ItemStack> $outputs
	 */
	public function __construct(
		private string $recipeId,
		private array $inputs,
		private array $outputs,
		private UuidInterface $uuid,
		private string $blockName,
		private int $priority,
		private ?RecipeUnlockingRequirement $unlockingRequirement,
		private int $recipeNetId
	){}

	public function getRecipeId() : string{
		return $this->recipeId;
	}

	/**
	 * @return RecipeIngredient[]
	 * @phpstan-return list<RecipeIngredient>
	 */
	public function getInputs() : array{
		return $this->inputs;
	}

	/**
	 * @return ItemStack[]
	 * @phpstan-return list<ItemStack>
	 */
	public function getOutputs() : array{
		return $this->outputs;
	}

	public function getUuid() : UuidInterface{
		return $this->uuid;
	}

	public function getBlockName() : string{
		return $this->blockName;
	}

	public function getPriority() : int{
		return $this->priority;
	}

	public function getUnlockingRequirement() : ?RecipeUnlockingRequirement{ return $this->unlockingRequirement; }

	public function getRecipeNetId() : int{
		return $this->recipeNetId;
	}

	public static function decode(ByteBufferReader $in, int $protocolId) : self{
		$recipeId = CommonTypes::getString($in);
		$input = [];
		for($j = 0, $ingredientCount = VarInt::readUnsignedInt($in); $j < $ingredientCount; ++$j){
			$input[] = CommonTypes::getRecipeIngredient($in, $protocolId);
		}
		$output = [];
		for($k = 0, $resultCount = VarInt::readUnsignedInt($in); $k < $resultCount; ++$k){
			$output[] = CommonTypes::getItemStackWithoutStackId($in, $protocolId);
		}
		$uuid = CommonTypes::getUUID($in);
		$block = CommonTypes::getString($in);
		$priority = VarInt::readSignedInt($in);
		$unlockingRequirement = null;
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$unlockingRequirement = CommonTypes::readOptional($in, static fn(ByteBufferReader $in) => RecipeUnlockingRequirement::read($in, $protocolId));
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_0){
			$unlockingRequirement = RecipeUnlockingRequirement::read($in, $protocolId);
		}

		$recipeNetId = CommonTypes::readRecipeNetId($in);

		return new self($recipeId, $input, $output, $uuid, $block, $priority, $unlockingRequirement, $recipeNetId);
	}

	public function encode(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->recipeId);
		VarInt::writeUnsignedInt($out, count($this->inputs));
		foreach($this->inputs as $item){
			CommonTypes::putRecipeIngredient($out, $protocolId, $item);
		}

		VarInt::writeUnsignedInt($out, count($this->outputs));
		foreach($this->outputs as $item){
			CommonTypes::putItemStackWithoutStackId($out, $protocolId, $item);
		}

		CommonTypes::putUUID($out, $this->uuid);
		CommonTypes::putString($out, $this->blockName);
		VarInt::writeSignedInt($out, $this->priority);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeOptional($out, $this->unlockingRequirement, static fn(ByteBufferWriter $out, RecipeUnlockingRequirement $data) => $data->write($out, $protocolId));
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_0){
			//the requirement isn't optional on this wire format: no requirement is written as ALWAYS_UNLOCKED, which
			//is what the old API's `new RecipeUnlockingRequirement(null)` used to encode to
			($this->unlockingRequirement ?? new RecipeUnlockingRequirement(RecipeUnlockingContext::ALWAYS_UNLOCKED, null))->write($out, $protocolId);
		}

		CommonTypes::writeRecipeNetId($out, $this->recipeNetId);
	}
}
