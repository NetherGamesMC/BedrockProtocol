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
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use function count;

/**
 * Not clear what this is needed for, but it is very clearly marked as deprecated, so hopefully it'll go away before I
 * have to write a proper description for it.
 * Spec name: ItemStackRequestCraftResultsDeprecatedAction
 *
 * The result format depends on the protocol: since 1.26.40, results are DeprecatedCraftingResultsEntry; older protocols
 * send ItemStack (without stack ID). Decoding produces the type matching the protocol, and encoding requires it.
 */
final class DeprecatedCraftingResultsStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING;

	/**
	 * @param DeprecatedCraftingResultsEntry[]|ItemStack[] $results
	 * @phpstan-param list<DeprecatedCraftingResultsEntry|ItemStack> $results
	 */
	public function __construct(
		private array $results,
		private int $iterations
	){}

	/**
	 * @return DeprecatedCraftingResultsEntry[]|ItemStack[]
	 * @phpstan-return list<DeprecatedCraftingResultsEntry|ItemStack>
	 */
	public function getResults() : array{ return $this->results; }

	public function getIterations() : int{ return $this->iterations; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$results = [];
		for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				$results[] = DeprecatedCraftingResultsEntry::read($in, $protocolId);
			}else{
				$results[] = CommonTypes::getItemStackWithoutStackId($in, $protocolId);
			}
		}
		$iterations = Byte::readUnsigned($in);
		return new self($results, $iterations);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		VarInt::writeUnsignedInt($out, count($this->results));
		foreach($this->results as $result){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				if(!$result instanceof DeprecatedCraftingResultsEntry){
					throw new \InvalidArgumentException("Results must be DeprecatedCraftingResultsEntry for protocol $protocolId");
				}
				$result->write($out, $protocolId);
			}else{
				if(!$result instanceof ItemStack){
					throw new \InvalidArgumentException("Results must be ItemStack for protocol $protocolId");
				}
				CommonTypes::putItemStackWithoutStackId($out, $protocolId, $result);
			}
		}
		Byte::writeUnsigned($out, $this->iterations);
	}
}
