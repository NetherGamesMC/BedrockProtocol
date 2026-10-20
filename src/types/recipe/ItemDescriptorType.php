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

use pocketmine\network\mcpe\protocol\types\PacketOrdinalEnumTrait;

enum ItemDescriptorType : string{
	use PacketOrdinalEnumTrait;

	case EMPTY = "empty";
	case STRING_ID_META = "name";
	case MOLANG = "molang";
	case TAG = "item_tag";

	/**
	 * Only used by protocols older than 1.26.40.
	 * These must stay after the cases above so their ordinals don't shift the ones used on the wire.
	 */
	case INT_ID_META = "int_id_meta";
	case COMPLEX_ALIAS = "complex_alias";

	/**
	 * Type IDs used on the wire before 1.26.40.
	 */
	private const LEGACY_IDS = [
		"empty" => 0,
		"int_id_meta" => 1,
		"molang" => 2,
		"item_tag" => 3,
		"name" => 4,
		"complex_alias" => 5,
	];

	public function toLegacyId() : int{
		return self::LEGACY_IDS[$this->value];
	}

	public static function tryFromLegacyId(int $id) : ?self{
		foreach(self::LEGACY_IDS as $value => $legacyId){
			if($legacyId === $id){
				return self::from($value);
			}
		}
		return null;
	}
}
