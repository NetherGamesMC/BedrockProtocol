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

class PotionContainerChangeRecipe{
	public function __construct(
		private int $inputItemId,
		private int $ingredientItemId,
		private int $outputItemId
	){}

	public function getInputItemId() : int{
		return $this->inputItemId;
	}

	public function getIngredientItemId() : int{
		return $this->ingredientItemId;
	}

	public function getOutputItemId() : int{
		return $this->outputItemId;
	}

	public static function decode(ByteBufferReader $in) : self{
		$input = VarInt::readSignedInt($in);
		$ingredient = VarInt::readSignedInt($in);
		$output = VarInt::readSignedInt($in);

		return new self($input, $ingredient, $output);
	}

	public function encode(ByteBufferWriter $out) : void{
		VarInt::writeSignedInt($out, $this->inputItemId);
		VarInt::writeSignedInt($out, $this->ingredientItemId);
		VarInt::writeSignedInt($out, $this->outputItemId);
	}
}
