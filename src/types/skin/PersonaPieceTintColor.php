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

namespace pocketmine\network\mcpe\protocol\types\skin;

use pocketmine\color\Color;
use function count;

final class PersonaPieceTintColor{

	public const EXPECTED_COLOR_COUNT = 4;

	/**
	 * @param Color[] $colors
	 * @phpstan-param array{Color, Color, Color, Color} $colors
	 */
	public function __construct(
		private PersonaSkinPieceType $pieceType,
		private array $colors
	){
		if(count($this->colors) !== self::EXPECTED_COLOR_COUNT){
			throw new \InvalidArgumentException("Colors array must contain exactly " . self::EXPECTED_COLOR_COUNT . " Color objects");
		}
	}

	public function getPieceType() : PersonaSkinPieceType{
		return $this->pieceType;
	}

	/**
	 * @return Color[]
	 * @phpstan-return array{Color, Color, Color, Color}
	 */
	public function getColors() : array{
		return $this->colors;
	}
}
