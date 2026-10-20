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

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class RecipeUnlockingRequirement{

	/**
	 * @param RecipeIngredient[]|null $unlockingIngredients
	 * @phpstan-param list<RecipeIngredient>|null $unlockingIngredients
	 */
	public function __construct(
		private RecipeUnlockingContext $unlockingContext,
		private ?array $unlockingIngredients
	){
		if($unlockingContext !== RecipeUnlockingContext::NONE && $unlockingIngredients !== null){
			throw new \InvalidArgumentException("Unlocking ingredients can only be set when unlocking context is NONE");
		}
	}

	public function getUnlockingContext() : RecipeUnlockingContext{ return $this->unlockingContext; }

	/**
	 * @return RecipeIngredient[]|null
	 * @phpstan-return list<RecipeIngredient>|null
	 */
	public function getUnlockingIngredients() : ?array{ return $this->unlockingIngredients; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		//I don't know what the point of this structure is. It could easily have been a list<RecipeIngredient> instead.
		//It's basically just an optional list, which could have been done by an empty list wherever it's not needed.
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$unlockingContext = RecipeUnlockingContext::fromPacket(VarInt::readSignedInt($in));
			$unlockingIngredients = CommonTypes::readOptional($in, static fn(ByteBufferReader $in) => CommonTypes::readList($in, static fn(ByteBufferReader $in) => CommonTypes::getRecipeIngredient($in, $protocolId)));
			if($unlockingContext !== RecipeUnlockingContext::NONE && $unlockingIngredients !== null){
				//this is a runtime error, make sure the correct exception type is thrown
				throw new PacketDecodeException("Unlocking ingredients should only be set when context is CONTEXT_NONE");
			}
		}else{
			//Before 1.26.40 the context was a single byte (previously read here as a bool), and the ingredient list
			//(VarInt count) followed only when the context was 0 (NONE). The byte values are the same as the
			//RecipeUnlockingContext enum, so this maps losslessly, except that NONE always has a (possibly empty)
			//ingredient list on this wire format. Unknown non-zero values were accepted before (anything non-zero
			//meant "no ingredient list"), so they're mapped to ALWAYS_UNLOCKED instead of being rejected.
			$rawContext = Byte::readUnsigned($in);
			$unlockingContext = RecipeUnlockingContext::tryFrom($rawContext) ?? RecipeUnlockingContext::ALWAYS_UNLOCKED;
			$unlockingIngredients = null;
			if($unlockingContext === RecipeUnlockingContext::NONE){
				$unlockingIngredients = CommonTypes::readList($in, static fn(ByteBufferReader $in) => CommonTypes::getRecipeIngredient($in, $protocolId));
			}
		}

		return new self($unlockingContext, $unlockingIngredients);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			VarInt::writeSignedInt($out, $this->unlockingContext->value);
			CommonTypes::writeOptional($out, $this->unlockingIngredients, static fn(ByteBufferWriter $out, array $v) => CommonTypes::writeList($out, $v, static fn(ByteBufferWriter $out, RecipeIngredient $ingredient) => CommonTypes::putRecipeIngredient($out, $protocolId, $ingredient)));
		}else{
			//see read() for the mapping
			Byte::writeUnsigned($out, $this->unlockingContext->value);
			if($this->unlockingContext === RecipeUnlockingContext::NONE){
				CommonTypes::writeList($out, $this->unlockingIngredients ?? [], static fn(ByteBufferWriter $out, RecipeIngredient $ingredient) => CommonTypes::putRecipeIngredient($out, $protocolId, $ingredient));
			}
		}
	}
}
