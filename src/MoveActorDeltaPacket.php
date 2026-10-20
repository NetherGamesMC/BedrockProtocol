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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class MoveActorDeltaPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::MOVE_ACTOR_DELTA_PACKET;

	/** Legacy (<= 1.26.30) wire flags. From 1.26.40, these are represented by optionals and bools. */
	public const FLAG_HAS_X = 0x01;
	public const FLAG_HAS_Y = 0x02;
	public const FLAG_HAS_Z = 0x04;
	public const FLAG_HAS_PITCH = 0x08;
	public const FLAG_HAS_YAW = 0x10;
	public const FLAG_HAS_HEAD_YAW = 0x20;
	public const FLAG_GROUND = 0x40;
	public const FLAG_TELEPORT = 0x80;
	public const FLAG_FORCE_MOVE_LOCAL_ENTITY = 0x100;

	public int $actorRuntimeId;
	public ?float $xPos = null;
	public ?float $yPos = null;
	public ?float $zPos = null;
	public ?float $pitch = null;
	public ?float $yaw = null;
	public ?float $headYaw = null;
	public bool $onGround = false;
	public bool $teleport = false; //force move in the docs
	public bool $forceMoveLocalEntity = false; //force move local entity in the docs
	/** >= ProtocolInfo::PROTOCOL_1_26_40 */
	public bool $forceCompletion = false;
	/**
	 * >= ProtocolInfo::PROTOCOL_1_26_50
	 * Expected number of ticks before the next movement update, used for position interpolation duration on the client.
	 */
	public int $ticks = 0;

	/**
	 * @generate-create-func
	 */
	public static function create(
		int $actorRuntimeId,
		?float $xPos,
		?float $yPos,
		?float $zPos,
		?float $pitch,
		?float $yaw,
		?float $headYaw,
		bool $onGround,
		bool $teleport,
		bool $forceMoveLocalEntity,
		bool $forceCompletion,
		int $ticks,
	) : self{
		$result = new self;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->xPos = $xPos;
		$result->yPos = $yPos;
		$result->zPos = $zPos;
		$result->pitch = $pitch;
		$result->yaw = $yaw;
		$result->headYaw = $headYaw;
		$result->onGround = $onGround;
		$result->teleport = $teleport;
		$result->forceMoveLocalEntity = $forceMoveLocalEntity;
		$result->forceCompletion = $forceCompletion;
		$result->ticks = $ticks;
		return $result;
	}

	/**
	 * Returns the legacy (<= 1.26.30) flags bitfield derived from this packet's fields.
	 */
	public function getLegacyFlags() : int{
		return
			($this->xPos !== null ? self::FLAG_HAS_X : 0) |
			($this->yPos !== null ? self::FLAG_HAS_Y : 0) |
			($this->zPos !== null ? self::FLAG_HAS_Z : 0) |
			($this->pitch !== null ? self::FLAG_HAS_PITCH : 0) |
			($this->yaw !== null ? self::FLAG_HAS_YAW : 0) |
			($this->headYaw !== null ? self::FLAG_HAS_HEAD_YAW : 0) |
			($this->onGround ? self::FLAG_GROUND : 0) |
			($this->teleport ? self::FLAG_TELEPORT : 0) |
			($this->forceMoveLocalEntity ? self::FLAG_FORCE_MOVE_LOCAL_ENTITY : 0);
	}

	/** @throws DataDecodeException */
	private function maybeReadCoord(ByteBufferReader $in) : ?float{
		return CommonTypes::readOptional($in, LE::readFloat(...));
	}

	/** @throws DataDecodeException */
	private function maybeReadRotation(ByteBufferReader $in) : ?float{
		return CommonTypes::readOptional($in, CommonTypes::getRotationByte(...));
	}

	/** @throws DataDecodeException */
	private static function legacyMaybeReadCoord(int $flags, int $flag, ByteBufferReader $in) : ?float{
		if(($flags & $flag) !== 0){
			return LE::readFloat($in);
		}
		return null;
	}

	/** @throws DataDecodeException */
	private static function legacyMaybeReadRotation(int $flags, int $flag, ByteBufferReader $in) : ?float{
		if(($flags & $flag) !== 0){
			return CommonTypes::getRotationByte($in);
		}
		return null;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->xPos = $this->maybeReadCoord($in);
			$this->yPos = $this->maybeReadCoord($in);
			$this->zPos = $this->maybeReadCoord($in);
			$this->pitch = $this->maybeReadRotation($in);
			$this->yaw = $this->maybeReadRotation($in);
			$this->headYaw = $this->maybeReadRotation($in);
			$this->onGround = CommonTypes::getBool($in);
			$this->teleport = CommonTypes::getBool($in);
			$this->forceMoveLocalEntity = CommonTypes::getBool($in);
			$this->forceCompletion = CommonTypes::getBool($in);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
				$this->ticks = VarInt::readUnsignedLong($in);
			}
		}else{
			$flags = LE::readUnsignedShort($in);
			$this->xPos = self::legacyMaybeReadCoord($flags, self::FLAG_HAS_X, $in);
			$this->yPos = self::legacyMaybeReadCoord($flags, self::FLAG_HAS_Y, $in);
			$this->zPos = self::legacyMaybeReadCoord($flags, self::FLAG_HAS_Z, $in);
			$this->pitch = self::legacyMaybeReadRotation($flags, self::FLAG_HAS_PITCH, $in);
			$this->yaw = self::legacyMaybeReadRotation($flags, self::FLAG_HAS_YAW, $in);
			$this->headYaw = self::legacyMaybeReadRotation($flags, self::FLAG_HAS_HEAD_YAW, $in);
			$this->onGround = ($flags & self::FLAG_GROUND) !== 0;
			$this->teleport = ($flags & self::FLAG_TELEPORT) !== 0;
			$this->forceMoveLocalEntity = ($flags & self::FLAG_FORCE_MOVE_LOCAL_ENTITY) !== 0;
			$this->forceCompletion = false;
		}
	}

	private function maybeWriteCoord(?float $val, ByteBufferWriter $out) : void{
		CommonTypes::writeOptional($out, $val, LE::writeFloat(...));
	}

	private function maybeWriteRotation(?float $val, ByteBufferWriter $out) : void{
		CommonTypes::writeOptional($out, $val, CommonTypes::putRotationByte(...));
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->maybeWriteCoord($this->xPos, $out);
			$this->maybeWriteCoord($this->yPos, $out);
			$this->maybeWriteCoord($this->zPos, $out);
			$this->maybeWriteRotation($this->pitch, $out);
			$this->maybeWriteRotation($this->yaw, $out);
			$this->maybeWriteRotation($this->headYaw, $out);
			CommonTypes::putBool($out, $this->onGround);
			CommonTypes::putBool($out, $this->teleport);
			CommonTypes::putBool($out, $this->forceMoveLocalEntity);
			CommonTypes::putBool($out, $this->forceCompletion);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
				VarInt::writeUnsignedLong($out, $this->ticks);
			}
		}else{
			LE::writeUnsignedShort($out, $this->getLegacyFlags());
			if($this->xPos !== null){
				LE::writeFloat($out, $this->xPos);
			}
			if($this->yPos !== null){
				LE::writeFloat($out, $this->yPos);
			}
			if($this->zPos !== null){
				LE::writeFloat($out, $this->zPos);
			}
			if($this->pitch !== null){
				CommonTypes::putRotationByte($out, $this->pitch);
			}
			if($this->yaw !== null){
				CommonTypes::putRotationByte($out, $this->yaw);
			}
			if($this->headYaw !== null){
				CommonTypes::putRotationByte($out, $this->headYaw);
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleMoveActorDelta($this);
	}
}
