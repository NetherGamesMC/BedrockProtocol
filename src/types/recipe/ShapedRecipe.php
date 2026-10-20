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
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use Ramsey\Uuid\UuidInterface;
use function count;

final class ShapedRecipe{
	private string $blockName;

	/**
	 * @param RecipeIngredient[][] $input
	 * @param ItemStack[]          $output
	 * @phpstan-param list<list<RecipeIngredient>> $input
	 * @phpstan-param list<ItemStack> $output
	 */
	public function __construct(
		private string $recipeId,
		private array $input,
		private array $output,
		private UuidInterface $uuid,
		string $blockType, //TODO: rename this
		private int $priority,
		private bool $symmetric,
		private ?RecipeUnlockingRequirement $unlockingRequirement,
		private int $recipeNetId
	){
		$rows = count($input);
		if($rows < 1 or $rows > 3){
			throw new \InvalidArgumentException("Expected 1, 2 or 3 input rows");
		}
		$columns = null;
		foreach($input as $rowNumber => $row){
			if($columns === null){
				$columns = count($row);
			}elseif(count($row) !== $columns){
				throw new \InvalidArgumentException("Expected each row to be $columns columns, but have " . count($row) . " in row $rowNumber");
			}
		}
		$this->blockName = $blockType;
	}

	public function getRecipeId() : string{
		return $this->recipeId;
	}

	public function getWidth() : int{
		return count($this->input[0]);
	}

	public function getHeight() : int{
		return count($this->input);
	}

	/**
	 * @return RecipeIngredient[][]
	 * @phpstan-return list<list<RecipeIngredient>>
	 */
	public function getInput() : array{
		return $this->input;
	}

	/**
	 * @return ItemStack[]
	 * @phpstan-return list<ItemStack>
	 */
	public function getOutput() : array{
		return $this->output;
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

	public function isSymmetric() : bool{ return $this->symmetric; }

	public function getUnlockingRequirement() : ?RecipeUnlockingRequirement{ return $this->unlockingRequirement; }

	public function getRecipeNetId() : int{
		return $this->recipeNetId;
	}

	public static function decode(ByteBufferReader $in, int $protocolId) : self{
		$recipeId = CommonTypes::getString($in);
		$width = VarInt::readSignedInt($in);
		$height = VarInt::readSignedInt($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$count = VarInt::readUnsignedInt($in);
			if($count !== $width * $height){
				throw new PacketDecodeException("Provided ingredient count $count does not match width $width * height $height");
			}
		}
		$input = [];
		for($row = 0; $row < $height; ++$row){
			for($column = 0; $column < $width; ++$column){
				$input[$row][$column] = CommonTypes::getRecipeIngredient($in, $protocolId);
			}
		}

		$output = [];
		for($k = 0, $resultCount = VarInt::readUnsignedInt($in); $k < $resultCount; ++$k){
			$output[] = CommonTypes::getItemStackWithoutStackId($in, $protocolId);
		}
		$uuid = CommonTypes::getUUID($in);
		$block = CommonTypes::getString($in);
		$priority = VarInt::readSignedInt($in);
		$unlockingRequirement = null;
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_80){
			$symmetric = CommonTypes::getBool($in);

			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				$unlockingRequirement = CommonTypes::readOptional($in, static fn(ByteBufferReader $in) => RecipeUnlockingRequirement::read($in, $protocolId));
			}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_0){
				$unlockingRequirement = RecipeUnlockingRequirement::read($in, $protocolId);
			}
		}

		$recipeNetId = CommonTypes::readRecipeNetId($in);

		return new self($recipeId, $input, $output, $uuid, $block, $priority, $symmetric ?? true, $unlockingRequirement, $recipeNetId);
	}

	public function encode(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->recipeId);
		VarInt::writeSignedInt($out, $this->getWidth());
		VarInt::writeSignedInt($out, $this->getHeight());
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			VarInt::writeUnsignedInt($out, $this->getWidth() * $this->getHeight());
		}
		foreach($this->input as $row){
			foreach($row as $ingredient){
				CommonTypes::putRecipeIngredient($out, $protocolId, $ingredient);
			}
		}

		VarInt::writeUnsignedInt($out, count($this->output));
		foreach($this->output as $item){
			CommonTypes::putItemStackWithoutStackId($out, $protocolId, $item);
		}

		CommonTypes::putUUID($out, $this->uuid);
		CommonTypes::putString($out, $this->blockName);
		VarInt::writeSignedInt($out, $this->priority);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_80){
			CommonTypes::putBool($out, $this->symmetric);

			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				CommonTypes::writeOptional($out, $this->unlockingRequirement, static fn(ByteBufferWriter $out, RecipeUnlockingRequirement $data) => $data->write($out, $protocolId));
			}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_0){
				//the requirement isn't optional on this wire format: no requirement is written as ALWAYS_UNLOCKED, which
				//is what the old API's `new RecipeUnlockingRequirement(null)` used to encode to
				($this->unlockingRequirement ?? new RecipeUnlockingRequirement(RecipeUnlockingContext::ALWAYS_UNLOCKED, null))->write($out, $protocolId);
			}
		}

		CommonTypes::writeRecipeNetId($out, $this->recipeNetId);
	}
}
