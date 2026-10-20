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
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use function count;

/**
 * Tells that the current transaction crafted the specified recipe, using the recipe book. This is effectively the same
 * as the regular crafting result action.
 * Spec name: ItemStackRequestCraftRecipeAutoAction
 */
final class CraftRecipeAutoStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CRAFTING_RECIPE_AUTO;

	/** >= ProtocolInfo::PROTOCOL_1_21_20 && <= ProtocolInfo::PROTOCOL_1_26_30 */
	private int $repetitions2;

	/**
	 * @param RecipeIngredient[] $ingredients
	 * @phpstan-param list<RecipeIngredient> $ingredients
	 * @param int|null $repetitions2 only used by protocols 1.21.20 - 1.26.30 (defaults to $repetitions)
	 */
	final public function __construct(
		private int $recipeId,
		private int $repetitions,
		private array $ingredients,
		?int $repetitions2 = null
	){
		$this->repetitions2 = $repetitions2 ?? $repetitions;
	}

	public function getRecipeId() : int{ return $this->recipeId; }

	public function getRepetitions() : int{ return $this->repetitions; }

	/**
	 * Only sent by protocols 1.21.20 - 1.26.30. Equal to getRepetitions() otherwise.
	 */
	public function getRepetitions2() : int{ return $this->repetitions2; }

	/**
	 * @return RecipeIngredient[]
	 * @phpstan-return list<RecipeIngredient>
	 */
	public function getIngredients() : array{ return $this->ingredients; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$recipeId = CommonTypes::readRecipeNetId($in);
		$repetitions = Byte::readUnsigned($in);
		$repetitions2 = null;
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$ingredients = CommonTypes::readList($in, static fn(ByteBufferReader $in) => CommonTypes::readStackRequestIngredient($in, $protocolId));
		}else{
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
				$repetitions2 = Byte::readUnsigned($in); //repetitions property is sent twice, mojang...
			}
			$ingredients = [];
			for($i = 0, $count = Byte::readUnsigned($in); $i < $count; ++$i){
				$ingredients[] = CommonTypes::readStackRequestIngredient($in, $protocolId);
			}
		}
		return new self($recipeId, $repetitions, $ingredients, $repetitions2);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::writeRecipeNetId($out, $this->recipeId);
		Byte::writeUnsigned($out, $this->repetitions);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeList($out, $this->ingredients, static fn(ByteBufferWriter $out, RecipeIngredient $ingredient) => CommonTypes::writeStackRequestIngredient($out, $protocolId, $ingredient));
		}else{
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
				Byte::writeUnsigned($out, $this->repetitions2);
			}
			Byte::writeUnsigned($out, count($this->ingredients));
			foreach($this->ingredients as $ingredient){
				CommonTypes::writeStackRequestIngredient($out, $protocolId, $ingredient);
			}
		}
	}
}
