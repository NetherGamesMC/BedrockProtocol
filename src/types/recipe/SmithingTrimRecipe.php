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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class SmithingTrimRecipe{

	public function __construct(
		private string $recipeId,
		private RecipeIngredient $template,
		private RecipeIngredient $input,
		private RecipeIngredient $addition,
		private string $blockName,
		private int $recipeNetId
	){}

	public function getRecipeId() : string{ return $this->recipeId; }

	public function getTemplate() : RecipeIngredient{ return $this->template; }

	public function getInput() : RecipeIngredient{ return $this->input; }

	public function getAddition() : RecipeIngredient{ return $this->addition; }

	public function getBlockName() : string{ return $this->blockName; }

	public function getRecipeNetId() : int{ return $this->recipeNetId; }

	public static function decode(ByteBufferReader $in, int $protocolId) : self{
		$recipeId = CommonTypes::getString($in);
		$template = CommonTypes::getRecipeIngredient($in, $protocolId);
		$input = CommonTypes::getRecipeIngredient($in, $protocolId);
		$addition = CommonTypes::getRecipeIngredient($in, $protocolId);
		$blockName = CommonTypes::getString($in);
		$recipeNetId = CommonTypes::readRecipeNetId($in);

		return new self(
			$recipeId,
			$template,
			$input,
			$addition,
			$blockName,
			$recipeNetId
		);
	}

	public function encode(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->recipeId);
		CommonTypes::putRecipeIngredient($out, $protocolId, $this->template);
		CommonTypes::putRecipeIngredient($out, $protocolId, $this->input);
		CommonTypes::putRecipeIngredient($out, $protocolId, $this->addition);
		CommonTypes::putString($out, $this->blockName);
		CommonTypes::writeRecipeNetId($out, $this->recipeNetId);
	}
}
