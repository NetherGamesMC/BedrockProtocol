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

use Ramsey\Uuid\UuidInterface;

final class PersonaSkinPiece{

	public function __construct(
		private string $pieceId,
		private PersonaSkinPieceType $pieceType,
		private UuidInterface $packId,
		private bool $isDefaultPiece,
		private string $productId
	){}

	public function getPieceId() : string{
		return $this->pieceId;
	}

	public function getPieceType() : PersonaSkinPieceType{
		return $this->pieceType;
	}

	public function getPackId() : UuidInterface{
		return $this->packId;
	}

	public function isDefaultPiece() : bool{
		return $this->isDefaultPiece;
	}

	public function getProductId() : string{
		return $this->productId;
	}
}
