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

use function array_flip;

final class ItemStackRequestActionType{

	private function __construct(){
		//NOOP
	}

	public const TAKE = 0;
	public const PLACE = 1;
	public const SWAP = 2;
	public const DROP = 3;
	public const DESTROY = 4;
	public const CRAFTING_CONSUME_INPUT = 5;
	public const CRAFTING_CREATE_SPECIFIC_RESULT = 6;
	public const LAB_TABLE_COMBINE = 7;
	public const BEACON_PAYMENT = 8;
	public const MINE_BLOCK = 9;
	public const CRAFTING_RECIPE = 10;
	public const CRAFTING_RECIPE_AUTO = 11; //recipe book?
	public const CREATIVE_CREATE = 12;
	public const CRAFTING_RECIPE_OPTIONAL = 13; //anvil/cartography table rename
	public const CRAFTING_GRINDSTONE = 14;
	public const CRAFTING_LOOM = 15;
	public const CRAFTING_RESERVED = 16;
	public const CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING = 17;
	public const CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING = 18; //no idea what this is for

	/**
	 * Fork-only: these actions no longer exist since 1.26.40 (PlaceItemInContainer_DEPRECATED and
	 * TakeItemFromContainer_DEPRECATED), so they have no current ID. The values here are internal placeholders that are
	 * never written to the wire; older protocols use the legacy IDs from {@link ItemStackRequestActionType::toLegacyId()}.
	 *
	 * @deprecated 1.26.40
	 */
	public const PLACE_INTO_BUNDLE = 1007;
	/** @deprecated 1.26.40 */
	public const TAKE_FROM_BUNDLE = 1008;

	/**
	 * Since 1.26.40, the action type is sent twice: once as a VarInt outer type (the constants above), and once as an
	 * inner type byte (the values of this map). These are the 1.26.60 values, older protocols use the PRE_1_26_60 tables.
	 */
	public const INNER_TYPES = [
		self::TAKE => 0,
		self::PLACE => 1,
		self::SWAP => 2,
		self::DROP => 3,
		self::DESTROY => 4,
		self::CRAFTING_CONSUME_INPUT => 5,
		self::CRAFTING_CREATE_SPECIFIC_RESULT => 6,
		//7 PlaceItemInContainer_DEPRECATED
		//8 TakeItemFromContainer_DEPRECATED
		self::LAB_TABLE_COMBINE => 9,
		self::BEACON_PAYMENT => 10,
		self::MINE_BLOCK => 11,
		self::CRAFTING_RECIPE => 12,
		self::CRAFTING_RECIPE_AUTO => 13, //recipe book?
		self::CREATIVE_CREATE => 14,
		self::CRAFTING_RECIPE_OPTIONAL => 15, //anvil/cartography table rename
		self::CRAFTING_GRINDSTONE => 16,
		self::CRAFTING_LOOM => 17,
		self::CRAFTING_RESERVED => 18,
		self::CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING => 19,
		self::CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING => 20, //no idea what this is for
	];

	/** Fork-only: inner type (1.26.40 - 1.26.50) and single type (before 1.26.40) IDs, before CRAFTING_RESERVED. */
	private const PRE_1_26_60_INNER_TYPES = [
		self::TAKE => 0,
		self::PLACE => 1,
		self::SWAP => 2,
		self::DROP => 3,
		self::DESTROY => 4,
		self::CRAFTING_CONSUME_INPUT => 5,
		self::CRAFTING_CREATE_SPECIFIC_RESULT => 6,
		self::LAB_TABLE_COMBINE => 9,
		self::BEACON_PAYMENT => 10,
		self::MINE_BLOCK => 11,
		self::CRAFTING_RECIPE => 12,
		self::CRAFTING_RECIPE_AUTO => 13,
		self::CREATIVE_CREATE => 14,
		self::CRAFTING_RECIPE_OPTIONAL => 15,
		self::CRAFTING_GRINDSTONE => 16,
		self::CRAFTING_LOOM => 17,
		self::CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING => 18,
		self::CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING => 19,
	];

	/** Fork-only: outer types used by 1.26.40 - 1.26.50, before CRAFTING_RESERVED. */
	private const PRE_1_26_60_OUTER_TYPES = [
		self::TAKE => 0,
		self::PLACE => 1,
		self::SWAP => 2,
		self::DROP => 3,
		self::DESTROY => 4,
		self::CRAFTING_CONSUME_INPUT => 5,
		self::CRAFTING_CREATE_SPECIFIC_RESULT => 6,
		self::LAB_TABLE_COMBINE => 7,
		self::BEACON_PAYMENT => 8,
		self::MINE_BLOCK => 9,
		self::CRAFTING_RECIPE => 10,
		self::CRAFTING_RECIPE_AUTO => 11,
		self::CREATIVE_CREATE => 12,
		self::CRAFTING_RECIPE_OPTIONAL => 13,
		self::CRAFTING_GRINDSTONE => 14,
		self::CRAFTING_LOOM => 15,
		self::CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING => 16,
		self::CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING => 17,
	];

	/**
	 * Fork-only: action types that only exist before 1.26.40, mapped to their legacy wire IDs.
	 */
	private const LEGACY_ONLY_TYPES = [
		self::PLACE_INTO_BUNDLE => 7,
		self::TAKE_FROM_BUNDLE => 8,
	];

	/**
	 * @var int[]|null
	 * @phpstan-var array<int, int>|null
	 */
	private static ?array $legacyToType = null;

	/**
	 * @var int[]|null
	 * @phpstan-var array<int, int>|null
	 */
	private static ?array $pre1_26_60OuterToType = null;

	/** Inner/single type ID used before 1.26.60, or null if the action type doesn't exist there. */
	public static function toLegacyId(int $typeId) : ?int{
		return self::PRE_1_26_60_INNER_TYPES[$typeId] ?? self::LEGACY_ONLY_TYPES[$typeId] ?? null;
	}

	/** Action type for a single type ID used before 1.26.40, or null if unknown. */
	public static function fromLegacyId(int $legacyId) : ?int{
		self::$legacyToType ??= array_flip(self::PRE_1_26_60_INNER_TYPES + self::LEGACY_ONLY_TYPES);
		return self::$legacyToType[$legacyId] ?? null;
	}

	/** Outer type ID used by 1.26.40 - 1.26.50, or null if the action type doesn't exist there. */
	public static function toPre1_26_60OuterId(int $typeId) : ?int{
		return self::PRE_1_26_60_OUTER_TYPES[$typeId] ?? null;
	}

	/** Action type for an outer type ID used by 1.26.40 - 1.26.50, or null if unknown. */
	public static function fromPre1_26_60OuterId(int $outerId) : ?int{
		self::$pre1_26_60OuterToType ??= array_flip(self::PRE_1_26_60_OUTER_TYPES);
		return self::$pre1_26_60OuterToType[$outerId] ?? null;
	}
}
