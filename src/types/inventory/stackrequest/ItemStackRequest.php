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
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class ItemStackRequest{
	/**
	 * @param ItemStackRequestAction[] $actions
	 * @param string[]                 $filterStrings
	 * @phpstan-param list<ItemStackRequestAction> $actions
	 * @phpstan-param list<string> $filterStrings
	 */
	public function __construct(
		private int $requestId,
		private array $actions,
		private array $filterStrings,
		private int $filterStringCause
	){}

	public function getRequestId() : int{ return $this->requestId; }

	/**
	 * @return ItemStackRequestAction[]
	 * @phpstan-return list<ItemStackRequestAction>
	 */
	public function getActions() : array{ return $this->actions; }

	/**
	 * @return string[]
	 * @phpstan-return list<string>
	 */
	public function getFilterStrings() : array{ return $this->filterStrings; }

	public function getFilterStringCause() : int{ return $this->filterStringCause; }

	/**
	 * @throws DataDecodeException
	 * @throws PacketDecodeException
	 */
	private static function readAction(ByteBufferReader $in, int $protocolId, int $typeId) : ItemStackRequestAction{
		return match($typeId){
			TakeStackRequestAction::ID => TakeStackRequestAction::read($in, $protocolId),
			PlaceStackRequestAction::ID => PlaceStackRequestAction::read($in, $protocolId),
			SwapStackRequestAction::ID => SwapStackRequestAction::read($in, $protocolId),
			DropStackRequestAction::ID => DropStackRequestAction::read($in, $protocolId),
			DestroyStackRequestAction::ID => DestroyStackRequestAction::read($in, $protocolId),
			CraftingConsumeInputStackRequestAction::ID => CraftingConsumeInputStackRequestAction::read($in, $protocolId),
			CraftingCreateSpecificResultStackRequestAction::ID => CraftingCreateSpecificResultStackRequestAction::read($in, $protocolId),
			PlaceIntoBundleStackRequestAction::ID => PlaceIntoBundleStackRequestAction::read($in, $protocolId),
			TakeFromBundleStackRequestAction::ID => TakeFromBundleStackRequestAction::read($in, $protocolId),
			LabTableCombineStackRequestAction::ID => LabTableCombineStackRequestAction::read($in, $protocolId),
			BeaconPaymentStackRequestAction::ID => BeaconPaymentStackRequestAction::read($in, $protocolId),
			MineBlockStackRequestAction::ID => MineBlockStackRequestAction::read($in, $protocolId),
			CraftRecipeStackRequestAction::ID => CraftRecipeStackRequestAction::read($in, $protocolId),
			CraftRecipeAutoStackRequestAction::ID => CraftRecipeAutoStackRequestAction::read($in, $protocolId),
			CreativeCreateStackRequestAction::ID => CreativeCreateStackRequestAction::read($in, $protocolId),
			CraftRecipeOptionalStackRequestAction::ID => CraftRecipeOptionalStackRequestAction::read($in, $protocolId),
			GrindstoneStackRequestAction::ID => GrindstoneStackRequestAction::read($in, $protocolId),
			LoomStackRequestAction::ID => LoomStackRequestAction::read($in, $protocolId),
			DeprecatedCraftingNonImplementedStackRequestAction::ID => DeprecatedCraftingNonImplementedStackRequestAction::read($in, $protocolId),
			DeprecatedCraftingResultsStackRequestAction::ID => DeprecatedCraftingResultsStackRequestAction::read($in, $protocolId),
			default => throw new PacketDecodeException("Unhandled item stack request action type $typeId"),
		};
	}

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$requestId = CommonTypes::readItemStackRequestId($in);
		$actions = CommonTypes::readList($in, static function(ByteBufferReader $in) use ($protocolId) : ItemStackRequestAction{
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				$typeId = VarInt::readUnsignedInt($in);
				$innerTypeId = Byte::readUnsigned($in);
				$expectedInnerType = ItemStackRequestActionType::INNER_TYPES[$typeId] ?? "unknown";
				if($expectedInnerType !== $innerTypeId){
					throw new PacketDecodeException("ItemStackRequestAction type mismatch: outer type $typeId, expected inner type $expectedInnerType, actual inner type $innerTypeId");
				}
			}else{
				$legacyTypeId = Byte::readUnsigned($in);
				$typeId = ItemStackRequestActionType::fromLegacyId($legacyTypeId) ?? throw new PacketDecodeException("Unhandled item stack request action type $legacyTypeId");
			}
			return self::readAction($in, $protocolId, $typeId);
		});
		$filterStrings = CommonTypes::readList($in, CommonTypes::getString(...));
		$filterStringCause = LE::readSignedInt($in);
		return new self($requestId, $actions, $filterStrings, $filterStringCause);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::writeItemStackRequestId($out, $this->requestId);
		CommonTypes::writeList($out, $this->actions, static function(ByteBufferWriter $out, ItemStackRequestAction $action) use ($protocolId) : void{
			$typeId = $action->getTypeId();
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				$innerTypeId = ItemStackRequestActionType::INNER_TYPES[$typeId] ?? throw new \InvalidArgumentException("Item stack request action type $typeId is not supported by protocol $protocolId");
				VarInt::writeUnsignedInt($out, $typeId);
				Byte::writeUnsigned($out, $innerTypeId);
			}else{
				Byte::writeUnsigned($out, ItemStackRequestActionType::toLegacyId($typeId) ?? throw new \InvalidArgumentException("Item stack request action type $typeId is not supported by protocol $protocolId"));
			}
			$action->write($out, $protocolId);
		});
		CommonTypes::writeList($out, $this->filterStrings, CommonTypes::putString(...));
		LE::writeSignedInt($out, $this->filterStringCause);
	}
}
