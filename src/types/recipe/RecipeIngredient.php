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

/**
 * The descriptor may be an IntIdMetaItemDescriptor or ComplexAliasItemDescriptor for protocols older than 1.26.40.
 */
final class RecipeIngredient{
	public function __construct(
		private ?ItemDescriptor $descriptor,
		private int $count
	){}

	public function getDescriptor() : ?ItemDescriptor{
		return $this->descriptor;
	}

	public function getCount() : int{
		return $this->count;
	}
}
