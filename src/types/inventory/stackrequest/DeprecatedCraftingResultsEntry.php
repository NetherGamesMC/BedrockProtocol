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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\recipe\MolangItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\StringIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\TagItemDescriptor;

/**
 * Seems pointless, but we have to account for it \_(ツ)_/
 * Spec name: ItemStackRequestNetworkItemInstanceDescriptor
 *
 * Only used since 1.26.40. Older protocols send plain item stacks instead, see DeprecatedCraftingResultsStackRequestAction.
 */
final class DeprecatedCraftingResultsEntry{

	public function __construct(
		private StringIdMetaItemDescriptor|TagItemDescriptor|MolangItemDescriptor|null $descriptor,
		private int $count,
		private int $blockRuntimeId,
		private string $rawExtraData
	){}

	public function getDescriptor() : StringIdMetaItemDescriptor|TagItemDescriptor|MolangItemDescriptor|null{ return $this->descriptor; }

	public function getCount() : int{ return $this->count; }

	public function getBlockRuntimeId() : int{ return $this->blockRuntimeId; }

	public function getRawExtraData() : string{ return $this->rawExtraData; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$descriptor = CommonTypes::readItemDescriptorNormal($in, $protocolId);
		$count = LE::readUnsignedShort($in);
		$blockRuntimeId = VarInt::readUnsignedInt($in);
		$rawExtraData = CommonTypes::getString($in);

		return new self($descriptor, $count, $blockRuntimeId, $rawExtraData);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::writeItemDescriptorNormal($out, $protocolId, $this->descriptor);
		LE::writeUnsignedShort($out, $this->count);
		VarInt::writeUnsignedInt($out, $this->blockRuntimeId);
		CommonTypes::putString($out, $this->rawExtraData);
	}
}
