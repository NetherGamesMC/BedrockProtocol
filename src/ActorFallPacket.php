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

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class ActorFallPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::ACTOR_FALL_PACKET;

	private int $actorRuntimeId;
	private float $fallDistance;
	private bool $inVoid;

	/**
	 * @generate-create-func
	 */
	public static function create(int $actorRuntimeId, float $fallDistance, bool $inVoid) : self{
		$result = new self;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->fallDistance = $fallDistance;
		$result->inVoid = $inVoid;
		return $result;
	}

	public function getActorRuntimeId() : int{ return $this->actorRuntimeId; }

	public function getFallDistance() : float{ return $this->fallDistance; }

	public function isInVoid() : bool{ return $this->inVoid; }

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->fallDistance = $in->getLFloat();
		$this->inVoid = $in->getBool();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putLFloat($this->fallDistance);
		$out->putBool($this->inVoid);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleActorFall($this);
	}
}
