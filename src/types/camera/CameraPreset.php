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

namespace pocketmine\network\mcpe\protocol\types\camera;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\ControlScheme;

final class CameraPreset{
	public const AUDIO_LISTENER_TYPE_CAMERA = 0;
	public const AUDIO_LISTENER_TYPE_PLAYER = 1;

	public function __construct(
		private string $name,
		private string $parent,
		private ?float $xPosition,
		private ?float $yPosition,
		private ?float $zPosition,
		private ?float $pitch,
		private ?float $yaw,
		private ?float $rotationSpeed,
		private ?bool $snapToTarget,
		private ?Vector2 $horizontalRotationLimit,
		private ?Vector2 $verticalRotationLimit,
		private ?bool $continueTargeting,
		private ?float $blockListeningRadius,
		private ?Vector2 $viewOffset,
		private ?Vector3 $entityOffset,
		private ?float $radius,
		private ?float $yawLimitMin,
		private ?float $yawLimitMax,
		private ?int $audioListenerType,
		private ?bool $playerEffects,
		private ?bool $alignTargetAndCameraForward,
		private ?CameraPresetAimAssist $aimAssist,
		private ?ControlScheme $controlScheme,
		/** >= ProtocolInfo::PROTOCOL_1_26_50 */
		private bool $applyInheritedStartingRotation = false,
		/** >= ProtocolInfo::PROTOCOL_1_26_50 */
		private ?Vector2 $startingRotation = null,
	){}

	public function getName() : string{ return $this->name; }

	public function getParent() : string{ return $this->parent; }

	public function getXPosition() : ?float{ return $this->xPosition; }

	public function getYPosition() : ?float{ return $this->yPosition; }

	public function getZPosition() : ?float{ return $this->zPosition; }

	public function getPitch() : ?float{ return $this->pitch; }

	public function getYaw() : ?float{ return $this->yaw; }

	public function getRotationSpeed() : ?float { return $this->rotationSpeed; }

	public function getSnapToTarget() : ?bool { return $this->snapToTarget; }

	public function getHorizontalRotationLimit() : ?Vector2{ return $this->horizontalRotationLimit; }

	public function getVerticalRotationLimit() : ?Vector2{ return $this->verticalRotationLimit; }

	public function getContinueTargeting() : ?bool{ return $this->continueTargeting; }

	public function getBlockListeningRadius() : ?float{ return $this->blockListeningRadius; }

	public function getViewOffset() : ?Vector2{ return $this->viewOffset; }

	public function getEntityOffset() : ?Vector3{ return $this->entityOffset; }

	public function getRadius() : ?float{ return $this->radius; }

	public function getYawLimitMin() : ?float{ return $this->yawLimitMin; }

	public function getYawLimitMax() : ?float{ return $this->yawLimitMax; }

	public function getAudioListenerType() : ?int{ return $this->audioListenerType; }

	public function getPlayerEffects() : ?bool{ return $this->playerEffects; }

	public function getAlignTargetAndCameraForward() : ?bool{ return $this->alignTargetAndCameraForward; }

	public function getAimAssist() : ?CameraPresetAimAssist{ return $this->aimAssist; }

	public function getControlScheme() : ?ControlScheme{ return $this->controlScheme; }

	public function isApplyInheritedStartingRotation() : bool{ return $this->applyInheritedStartingRotation; }

	public function getStartingRotation() : ?Vector2{ return $this->startingRotation; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$name = CommonTypes::getString($in);
		$parent = CommonTypes::getString($in);
		$xPosition = CommonTypes::readOptional($in, LE::readFloat(...));
		$yPosition = CommonTypes::readOptional($in, LE::readFloat(...));
		$zPosition = CommonTypes::readOptional($in, LE::readFloat(...));
		$pitch = CommonTypes::readOptional($in, LE::readFloat(...));
		$yaw = CommonTypes::readOptional($in, LE::readFloat(...));
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_30){
				$rotationSpeed = CommonTypes::readOptional($in, LE::readFloat(...));
				$snapToTarget = CommonTypes::readOptional($in, CommonTypes::getBool(...));
				if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
					$horizontalRotationLimit = CommonTypes::readOptional($in, CommonTypes::getVector2(...));
					$verticalRotationLimit = CommonTypes::readOptional($in, CommonTypes::getVector2(...));
					$continueTargeting = CommonTypes::readOptional($in, CommonTypes::getBool(...));
					if($protocolId >= ProtocolInfo::PROTOCOL_1_21_50){
						$blockListeningRadius = CommonTypes::readOptional($in, LE::readFloat(...));
					}
				}
			}
			$viewOffset = CommonTypes::readOptional($in, CommonTypes::getVector2(...));
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_30){
				$entityOffset = CommonTypes::readOptional($in, CommonTypes::getVector3(...));
			}
			$radius = CommonTypes::readOptional($in, LE::readFloat(...));
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_60){
				$yawLimitMin = CommonTypes::readOptional($in, LE::readFloat(...));
				$yawLimitMax = CommonTypes::readOptional($in, LE::readFloat(...));
			}
		}
		$audioListenerType = CommonTypes::readOptional($in, Byte::readUnsigned(...));
		$playerEffects = CommonTypes::readOptional($in, CommonTypes::getBool(...));
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
			if($protocolId <= ProtocolInfo::PROTOCOL_1_21_80){
				$alignTargetAndCameraForward = CommonTypes::readOptional($in, CommonTypes::getBool(...));
			}
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_50){
				if($protocolId >= ProtocolInfo::PROTOCOL_1_21_60){
					$aimAssist = CommonTypes::readOptional($in, fn() => CameraPresetAimAssist::read($in));
					if($protocolId >= ProtocolInfo::PROTOCOL_1_21_80){
						$controlScheme = CommonTypes::readOptional($in, fn() => ControlScheme::fromPacket(Byte::readUnsigned($in)));
					}
				}else{
					$aimAssist = CommonTypes::readOptional($in, fn() => CommonTypes::getBool($in) ? new CameraPresetAimAssist(null, null, null, null) : null);
				}
			}
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			$applyInheritedStartingRotation = CommonTypes::getBool($in);
			$startingRotation = CommonTypes::readOptional($in, CommonTypes::getVector2(...));
		}

		return new self(
			$name,
			$parent,
			$xPosition,
			$yPosition,
			$zPosition,
			$pitch,
			$yaw,
			$rotationSpeed ?? null,
			$snapToTarget ?? null,
			$horizontalRotationLimit ?? null,
			$verticalRotationLimit ?? null,
			$continueTargeting ?? null,
			$blockListeningRadius ?? null,
			$viewOffset ?? null,
			$entityOffset ?? null,
			$radius ?? null,
			$yawLimitMin ?? null,
			$yawLimitMax ?? null,
			$audioListenerType,
			$playerEffects,
			$alignTargetAndCameraForward ?? null,
			$aimAssist ?? null,
			$controlScheme ?? null,
			$applyInheritedStartingRotation ?? false,
			$startingRotation ?? null,
		);
	}

	public static function fromNBT(CompoundTag $nbt) : self{
		return new self(
			$nbt->getString("identifier"),
			$nbt->getString("inherit_from"),
			$nbt->getTag("pos_x") === null ? null : $nbt->getFloat("pos_x"),
			$nbt->getTag("pos_y") === null ? null : $nbt->getFloat("pos_y"),
			$nbt->getTag("pos_z") === null ? null : $nbt->getFloat("pos_z"),
			$nbt->getTag("rot_x") === null ? null : $nbt->getFloat("rot_x"),
			$nbt->getTag("rot_y") === null ? null : $nbt->getFloat("rot_y"),
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			$nbt->getTag("audio_listener_type") === null ? null : match ($nbt->getString("audio_listener_type")){
				"camera" => self::AUDIO_LISTENER_TYPE_CAMERA,
				"player" => self::AUDIO_LISTENER_TYPE_PLAYER,
				default => throw new \InvalidArgumentException("Invalid audio listener type: " . $nbt->getString("audio_listener_type")),
			},
			$nbt->getTag("player_effects") === null ? null : $nbt->getByte("player_effects") !== 0,
			null,
			null,
			null,
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->name);
		CommonTypes::putString($out, $this->parent);
		CommonTypes::writeOptional($out, $this->xPosition, LE::writeFloat(...));
		CommonTypes::writeOptional($out, $this->yPosition, LE::writeFloat(...));
		CommonTypes::writeOptional($out, $this->zPosition, LE::writeFloat(...));
		CommonTypes::writeOptional($out, $this->pitch, LE::writeFloat(...));
		CommonTypes::writeOptional($out, $this->yaw, LE::writeFloat(...));
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_30){
				CommonTypes::writeOptional($out, $this->rotationSpeed, LE::writeFloat(...));
				CommonTypes::writeOptional($out, $this->snapToTarget, CommonTypes::putBool(...));
				if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
					CommonTypes::writeOptional($out, $this->horizontalRotationLimit, CommonTypes::putVector2(...));
					CommonTypes::writeOptional($out, $this->verticalRotationLimit, CommonTypes::putVector2(...));
					CommonTypes::writeOptional($out, $this->continueTargeting, CommonTypes::putBool(...));
					if($protocolId >= ProtocolInfo::PROTOCOL_1_21_50){
						CommonTypes::writeOptional($out, $this->blockListeningRadius, LE::writeFloat(...));
					}
				}
			}
			CommonTypes::writeOptional($out, $this->viewOffset, CommonTypes::putVector2(...));
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_30){
				CommonTypes::writeOptional($out, $this->entityOffset, CommonTypes::putVector3(...));
			}
			CommonTypes::writeOptional($out, $this->radius, LE::writeFloat(...));
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_60){
				CommonTypes::writeOptional($out, $this->yawLimitMin, LE::writeFloat(...));
				CommonTypes::writeOptional($out, $this->yawLimitMax, LE::writeFloat(...));
			}
		}
		CommonTypes::writeOptional($out, $this->audioListenerType, Byte::writeUnsigned(...));
		CommonTypes::writeOptional($out, $this->playerEffects, CommonTypes::putBool(...));
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
			if($protocolId <= ProtocolInfo::PROTOCOL_1_21_80){
				CommonTypes::writeOptional($out, $this->alignTargetAndCameraForward, CommonTypes::putBool(...));
			}
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_50){
				if($protocolId >= ProtocolInfo::PROTOCOL_1_21_60){
					CommonTypes::writeOptional($out, $this->aimAssist, fn(ByteBufferWriter $out, CameraPresetAimAssist $v) => $v->write($out));
					if($protocolId >= ProtocolInfo::PROTOCOL_1_21_80){
						CommonTypes::writeOptional($out, $this->controlScheme, fn(ByteBufferWriter $out, ControlScheme $v) => Byte::writeUnsigned($out, $v->value));
					}
				}else{
					CommonTypes::writeOptional($out, $this->aimAssist, fn(ByteBufferWriter $out, CameraPresetAimAssist $v) => $v->write($out));
				}
			}
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			CommonTypes::putBool($out, $this->applyInheritedStartingRotation);
			CommonTypes::writeOptional($out, $this->startingRotation, CommonTypes::putVector2(...));
		}
	}

	public function toNBT(int $protocolId) : CompoundTag{
		$nbt = CompoundTag::create()
			->setString("identifier", $this->name)
			->setString("inherit_from", $this->parent);

		if($this->xPosition !== null){
			$nbt->setFloat("pos_x", $this->xPosition);
		}

		if($this->yPosition !== null){
			$nbt->setFloat("pos_y", $this->yPosition);
		}

		if($this->zPosition !== null){
			$nbt->setFloat("pos_z", $this->zPosition);
		}

		if($this->pitch !== null){
			$nbt->setFloat("rot_x", $this->pitch);
		}

		if($this->yaw !== null){
			$nbt->setFloat("rot_y", $this->yaw);
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_10){
			if($this->audioListenerType !== null){
				$nbt->setString("audio_listener_type", match($this->audioListenerType){
					self::AUDIO_LISTENER_TYPE_CAMERA => "camera",
					self::AUDIO_LISTENER_TYPE_PLAYER => "player",
					default => throw new \InvalidArgumentException("Invalid audio listener type: $this->audioListenerType"),
				});
			}

			if($this->playerEffects !== null){
				$nbt->setByte("player_effects", (int) $this->playerEffects);
			}
		}

		return $nbt;
	}
}
