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

class PotionTypeRecipe{
	public function __construct(
		private int $inputItemId,
		private int $inputItemMeta,
		private int $ingredientItemId,
		private int $ingredientItemMeta,
		private int $outputItemId,
		private int $outputItemMeta
	){}

	public function getInputItemId() : int{
		return $this->inputItemId;
	}

	public function getInputItemMeta() : int{
		return $this->inputItemMeta;
	}

	public function getIngredientItemId() : int{
		return $this->ingredientItemId;
	}

	public function getIngredientItemMeta() : int{
		return $this->ingredientItemMeta;
	}

	public function getOutputItemId() : int{
		return $this->outputItemId;
	}

	public function getOutputItemMeta() : int{
		return $this->outputItemMeta;
	}

	public static function decode(ByteBufferReader $in) : self{
		$inputId = VarInt::readSignedInt($in);
		$inputMeta = VarInt::readSignedInt($in);
		$ingredientId = VarInt::readSignedInt($in);
		$ingredientMeta = VarInt::readSignedInt($in);
		$outputId = VarInt::readSignedInt($in);
		$outputMeta = VarInt::readSignedInt($in);

		return new self(
			$inputId,
			$inputMeta,
			$ingredientId,
			$ingredientMeta,
			$outputId,
			$outputMeta
		);
	}

	public function encode(ByteBufferWriter $out) : void{
		VarInt::writeSignedInt($out, $this->inputItemId);
		VarInt::writeSignedInt($out, $this->inputItemMeta);
		VarInt::writeSignedInt($out, $this->ingredientItemId);
		VarInt::writeSignedInt($out, $this->ingredientItemMeta);
		VarInt::writeSignedInt($out, $this->outputItemId);
		VarInt::writeSignedInt($out, $this->outputItemMeta);
	}
}
