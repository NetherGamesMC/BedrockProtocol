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
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\serializer\BitSet;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\network\mcpe\protocol\types\InteractionMode;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use pocketmine\network\mcpe\protocol\types\ItemInteractionData;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputVehicleInfo;
use pocketmine\network\mcpe\protocol\types\PlayerBlockAction;
use pocketmine\network\mcpe\protocol\types\PlayMode;

class PlayerAuthInputPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_AUTH_INPUT_PACKET;

	private Vector3 $position;
	private float $pitch;
	private float $yaw;
	private float $headYaw;
	private float $moveVecX;
	private float $moveVecZ;
	private BitSet $inputFlags;
	private InputMode $inputMode;
	private PlayMode $playMode;
	private InteractionMode $interactionMode;
	private Vector2 $interactRotation;
	private int $tick;
	private Vector3 $delta;
	private ?ItemInteractionData $itemInteractionData = null;
	private ?ItemStackRequest $itemStackRequest = null;
	/**
	 * @var PlayerBlockAction[]|null
	 * @phpstan-var list<PlayerBlockAction>|null
	 */
	private ?array $blockActions = null;
	private ?PlayerAuthInputVehicleInfo $vehicleInfo = null;
	private float $analogMoveVecX;
	private float $analogMoveVecZ;
	private Vector3 $cameraOrientation;
	private Vector2 $rawMove;

	/**
	 * @generate-create-func
	 * @param PlayerBlockAction[]|null $blockActions
	 * @phpstan-param list<PlayerBlockAction>|null $blockActions
	 */
	private static function internalCreate(
		Vector3 $position,
		float $pitch,
		float $yaw,
		float $headYaw,
		float $moveVecX,
		float $moveVecZ,
		BitSet $inputFlags,
		InputMode $inputMode,
		PlayMode $playMode,
		InteractionMode $interactionMode,
		Vector2 $interactRotation,
		int $tick,
		Vector3 $delta,
		?ItemInteractionData $itemInteractionData,
		?ItemStackRequest $itemStackRequest,
		?array $blockActions,
		?PlayerAuthInputVehicleInfo $vehicleInfo,
		float $analogMoveVecX,
		float $analogMoveVecZ,
		Vector3 $cameraOrientation,
		Vector2 $rawMove,
	) : self{
		$result = new self;
		$result->position = $position;
		$result->pitch = $pitch;
		$result->yaw = $yaw;
		$result->headYaw = $headYaw;
		$result->moveVecX = $moveVecX;
		$result->moveVecZ = $moveVecZ;
		$result->inputFlags = $inputFlags;
		$result->inputMode = $inputMode;
		$result->playMode = $playMode;
		$result->interactionMode = $interactionMode;
		$result->interactRotation = $interactRotation;
		$result->tick = $tick;
		$result->delta = $delta;
		$result->itemInteractionData = $itemInteractionData;
		$result->itemStackRequest = $itemStackRequest;
		$result->blockActions = $blockActions;
		$result->vehicleInfo = $vehicleInfo;
		$result->analogMoveVecX = $analogMoveVecX;
		$result->analogMoveVecZ = $analogMoveVecZ;
		$result->cameraOrientation = $cameraOrientation;
		$result->rawMove = $rawMove;
		return $result;
	}

	/**
	 * @param BitSet                   $inputFlags @see PlayerAuthInputFlags
	 * @param PlayerBlockAction[]|null $blockActions Blocks that the client has interacted with
	 * @phpstan-param list<PlayerBlockAction>|null $blockActions
	 */
	public static function create(
		Vector3 $position,
		float $pitch,
		float $yaw,
		float $headYaw,
		float $moveVecX,
		float $moveVecZ,
		BitSet $inputFlags,
		InputMode $inputMode,
		PlayMode $playMode,
		InteractionMode $interactionMode,
		Vector2 $interactRotation,
		int $tick,
		Vector3 $delta,
		?ItemInteractionData $itemInteractionData,
		?ItemStackRequest $itemStackRequest,
		?array $blockActions,
		?PlayerAuthInputVehicleInfo $vehicleInfo,
		float $analogMoveVecX,
		float $analogMoveVecZ,
		Vector3 $cameraOrientation,
		Vector2 $rawMove
	) : self{
		if($inputFlags->getLength() !== PlayerAuthInputFlags::NUMBER_OF_FLAGS){
			throw new \InvalidArgumentException("Input flags must be " . PlayerAuthInputFlags::NUMBER_OF_FLAGS . " bits long");
		}

		//TODO: are these still needed in 26.40?
		$inputFlags->set(PlayerAuthInputFlags::PERFORM_ITEM_STACK_REQUEST, $itemStackRequest !== null);
		$inputFlags->set(PlayerAuthInputFlags::PERFORM_ITEM_INTERACTION, $itemInteractionData !== null);
		$inputFlags->set(PlayerAuthInputFlags::PERFORM_BLOCK_ACTIONS, $blockActions !== null);
		$inputFlags->set(PlayerAuthInputFlags::IN_CLIENT_PREDICTED_VEHICLE, $vehicleInfo !== null);

		return self::internalCreate(
			$position,
			$pitch,
			$yaw,
			$headYaw,
			$moveVecX,
			$moveVecZ,
			$inputFlags,
			$inputMode,
			$playMode,
			$interactionMode,
			$interactRotation,
			$tick,
			$delta,
			$itemInteractionData,
			$itemStackRequest,
			$blockActions,
			$vehicleInfo,
			$analogMoveVecX,
			$analogMoveVecZ,
			$cameraOrientation,
			$rawMove
		);
	}

	public function getPosition() : Vector3{
		return $this->position;
	}

	public function getPitch() : float{
		return $this->pitch;
	}

	public function getYaw() : float{
		return $this->yaw;
	}

	public function getHeadYaw() : float{
		return $this->headYaw;
	}

	public function getMoveVecX() : float{
		return $this->moveVecX;
	}

	public function getMoveVecZ() : float{
		return $this->moveVecZ;
	}

	/**
	 * @see PlayerAuthInputFlags
	 */
	public function getInputFlags() : BitSet{
		return $this->inputFlags;
	}

	public function getInputMode() : InputMode{
		return $this->inputMode;
	}

	public function getPlayMode() : PlayMode{
		return $this->playMode;
	}

	public function getInteractionMode() : InteractionMode{
		return $this->interactionMode;
	}

	public function getInteractRotation() : Vector2{ return $this->interactRotation; }

	public function getTick() : int{
		return $this->tick;
	}

	public function getDelta() : Vector3{
		return $this->delta;
	}

	public function getItemInteractionData() : ?ItemInteractionData{
		return $this->itemInteractionData;
	}

	public function getItemStackRequest() : ?ItemStackRequest{
		return $this->itemStackRequest;
	}

	/**
	 * @return PlayerBlockAction[]|null
	 * @phpstan-return list<PlayerBlockAction>|null
	 */
	public function getBlockActions() : ?array{
		return $this->blockActions;
	}

	public function getVehicleInfo() : ?PlayerAuthInputVehicleInfo{ return $this->vehicleInfo; }

	public function getAnalogMoveVecX() : float{ return $this->analogMoveVecX; }

	public function getAnalogMoveVecZ() : float{ return $this->analogMoveVecZ; }

	public function getCameraOrientation() : Vector3{ return $this->cameraOrientation; }

	public function getRawMove() : Vector2{ return $this->rawMove; }

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->pitch = LE::readFloat($in);
		$this->yaw = LE::readFloat($in);
		$this->position = CommonTypes::getVector3($in);
		$this->moveVecX = LE::readFloat($in);
		$this->moveVecZ = LE::readFloat($in);
		$this->headYaw = LE::readFloat($in);

		$this->inputFlags = new BitSet(PlayerAuthInputFlags::NUMBER_OF_FLAGS);
		foreach(CommonTypes::readList($in, VarInt::readSignedInt(...)) as $flag){
			if($this->inputFlags->get($flag)){
				throw new PacketDecodeException("Duplicate input flag $flag");
			}
			$this->inputFlags->set($flag, true);
		}

		$this->inputMode = InputMode::fromPacket(VarInt::readUnsignedInt($in));
		$this->playMode = PlayMode::fromPacket(VarInt::readUnsignedInt($in));
		$this->interactionMode = InteractionMode::fromPacket(VarInt::readSignedInt($in));
		$this->interactRotation = CommonTypes::getVector2($in);
		$this->tick = VarInt::readUnsignedLong($in);
		$this->delta = CommonTypes::getVector3($in);
		$this->itemInteractionData = CommonTypes::readOptional($in, ItemInteractionData::read(...));
		$this->itemStackRequest = CommonTypes::readOptional($in, ItemStackRequest::read(...));

		$this->blockActions = CommonTypes::readOptional($in, static fn($in) => CommonTypes::readList($in, PlayerBlockAction::read(...)));
		$vehicleRotation = CommonTypes::readOptional($in, CommonTypes::getVector2(...));
		$vehicleActorUniqueId = CommonTypes::readOptional($in, CommonTypes::getActorUniqueId(...));
		if($vehicleRotation !== null && $vehicleActorUniqueId !== null){
			$this->vehicleInfo = new PlayerAuthInputVehicleInfo($vehicleRotation, $vehicleActorUniqueId);
		}elseif($vehicleRotation === null && $vehicleActorUniqueId === null){
			$this->vehicleInfo = null;
		}else{
			throw new PacketDecodeException("Vehicle rotation and actor unique ID must both be present or both be absent");
		}
		$this->analogMoveVecX = LE::readFloat($in);
		$this->analogMoveVecZ = LE::readFloat($in);
		$this->cameraOrientation = CommonTypes::getVector3($in);
		$this->rawMove = CommonTypes::getVector2($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		LE::writeFloat($out, $this->pitch);
		LE::writeFloat($out, $this->yaw);
		CommonTypes::putVector3($out, $this->position);
		LE::writeFloat($out, $this->moveVecX);
		LE::writeFloat($out, $this->moveVecZ);
		LE::writeFloat($out, $this->headYaw);

		$flagsArray = [];
		for($i = 0; $i < PlayerAuthInputFlags::NUMBER_OF_FLAGS; ++$i){
			if($this->inputFlags->get($i)){
				$flagsArray[] = $i;
			}
		}
		CommonTypes::writeList($out, $flagsArray, VarInt::writeSignedInt(...));

		VarInt::writeUnsignedInt($out, $this->inputMode->value);
		VarInt::writeUnsignedInt($out, $this->playMode->value);
		VarInt::writeSignedInt($out, $this->interactionMode->value);
		CommonTypes::putVector2($out, $this->interactRotation);
		VarInt::writeUnsignedLong($out, $this->tick);
		CommonTypes::putVector3($out, $this->delta);
		CommonTypes::writeOptional($out, $this->itemInteractionData, static fn(ByteBufferWriter $out, ItemInteractionData $data) => $data->write($out));
		CommonTypes::writeOptional($out, $this->itemStackRequest, static fn(ByteBufferWriter $out, ItemStackRequest $request) => $request->write($out));
		CommonTypes::writeOptional($out, $this->blockActions, static fn($out, $array) => CommonTypes::writeList($out, $array, static fn($out, $v) => $v->write($out)));
		CommonTypes::writeOptional($out, $this->vehicleInfo?->getVehicleRotation(), CommonTypes::putVector2(...));
		CommonTypes::writeOptional($out, $this->vehicleInfo?->getPredictedVehicleActorUniqueId(), CommonTypes::putActorUniqueId(...));
		LE::writeFloat($out, $this->analogMoveVecX);
		LE::writeFloat($out, $this->analogMoveVecZ);
		CommonTypes::putVector3($out, $this->cameraOrientation);
		CommonTypes::putVector2($out, $this->rawMove);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerAuthInput($this);
	}
}
