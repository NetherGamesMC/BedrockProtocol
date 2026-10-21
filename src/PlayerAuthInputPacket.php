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
use function assert;
use function count;
use function min;

class PlayerAuthInputPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_AUTH_INPUT_PACKET;

	public Vector3 $position;
	private float $pitch;
	private float $yaw;
	private float $headYaw;
	private float $moveVecX;
	private float $moveVecZ;
	private BitSet $inputFlags;
	private InputMode $inputMode;
	private PlayMode $playMode;
	private InteractionMode $interactionMode;
	private ?Vector3 $vrGazeDirection = null;
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
		?Vector3 $vrGazeDirection,
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
		$result->vrGazeDirection = $vrGazeDirection;
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
		?Vector3 $vrGazeDirection,
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

		if($playMode === PlayMode::REALITY and $vrGazeDirection === null){
			//yuck, can we get a properly written packet just once? ...
			throw new \InvalidArgumentException("Gaze direction must be provided for VR play mode");
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
			$vrGazeDirection?->asVector3(),
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

	public function getVrGazeDirection() : ?Vector3{
		return $this->vrGazeDirection;
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

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->pitch = LE::readFloat($in);
		$this->yaw = LE::readFloat($in);
		$this->position = CommonTypes::getVector3($in);
		$this->moveVecX = LE::readFloat($in);
		$this->moveVecZ = LE::readFloat($in);
		$this->headYaw = LE::readFloat($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			if($protocolId <= ProtocolInfo::PROTOCOL_1_26_45){
				CommonTypes::readDummyOptional($in);
			}
			$this->inputFlags = new BitSet(PlayerAuthInputFlags::NUMBER_OF_FLAGS);
			foreach(CommonTypes::readList($in, VarInt::readSignedInt(...)) as $flag){
				if($flag < 0 || $flag >= PlayerAuthInputFlags::NUMBER_OF_FLAGS){
					throw new PacketDecodeException("Input flag $flag is out of range");
				}
				if($this->inputFlags->get($flag)){
					throw new PacketDecodeException("Duplicate input flag $flag");
				}
				$this->inputFlags->set($flag, true);
			}
		}else{
			//normalize to the current flag count, so that callers can always query any known flag
			$legacyLength = self::getLegacyInputFlagsLength($protocolId);
			$legacyFlags = BitSet::read($in, $legacyLength);
			$this->inputFlags = new BitSet(PlayerAuthInputFlags::NUMBER_OF_FLAGS);
			for($i = 0; $i < $legacyLength; ++$i){
				if($legacyFlags->get($i)){
					$this->inputFlags->set($i, true);
				}
			}
		}
		$this->inputMode = InputMode::fromPacket(VarInt::readUnsignedInt($in));
		$this->playMode = PlayMode::fromPacket(VarInt::readUnsignedInt($in));
		$this->interactionMode = InteractionMode::fromPacket($protocolId >= ProtocolInfo::PROTOCOL_1_26_40 ? VarInt::readSignedInt($in) : VarInt::readUnsignedInt($in));
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
			$this->interactRotation = CommonTypes::getVector2($in);
		}elseif($this->playMode === PlayMode::REALITY){
			$this->vrGazeDirection = CommonTypes::getVector3($in);
		}
		$this->tick = VarInt::readUnsignedLong($in);
		$this->delta = CommonTypes::getVector3($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
				$this->itemInteractionData = CommonTypes::readOptional($in, fn(ByteBufferReader $in) => ItemInteractionData::read($in, $protocolId));
				$this->itemStackRequest = CommonTypes::readOptional($in, fn(ByteBufferReader $in) => ItemStackRequest::read($in, $protocolId));

				$this->blockActions = CommonTypes::readOptional($in, fn(ByteBufferReader $in) => CommonTypes::readList($in, fn(ByteBufferReader $in) => PlayerBlockAction::read($in, $protocolId)));
				$vehicleRotation = CommonTypes::readOptional($in, CommonTypes::getVector2(...));
				$vehicleActorUniqueId = CommonTypes::readOptional($in, CommonTypes::getActorUniqueId(...));
			}else{
				$this->itemInteractionData = CommonTypes::readDoubleOptional($in, fn(ByteBufferReader $in) => ItemInteractionData::read($in, $protocolId));
				$this->itemStackRequest = CommonTypes::readDoubleOptional($in, fn(ByteBufferReader $in) => ItemStackRequest::read($in, $protocolId));

				$this->blockActions = CommonTypes::readDoubleOptional($in, fn(ByteBufferReader $in) => CommonTypes::readList($in, fn(ByteBufferReader $in) => PlayerBlockAction::read($in, $protocolId)));
				$vehicleRotation = CommonTypes::readDoubleOptional($in, CommonTypes::getVector2(...));
				$vehicleActorUniqueId = CommonTypes::readDoubleOptional($in, CommonTypes::getActorUniqueId(...));
			}
			if($vehicleRotation !== null && $vehicleActorUniqueId !== null){
				$this->vehicleInfo = new PlayerAuthInputVehicleInfo($vehicleRotation, $vehicleActorUniqueId);
			}elseif($vehicleRotation === null && $vehicleActorUniqueId === null){
				$this->vehicleInfo = null;
			}else{
				throw new PacketDecodeException("Vehicle rotation and actor unique ID must both be present or both be absent");
			}
		}else{
			if($this->inputFlags->get(PlayerAuthInputFlags::PERFORM_ITEM_INTERACTION)){
				$this->itemInteractionData = ItemInteractionData::read($in, $protocolId);
			}
			if($this->inputFlags->get(PlayerAuthInputFlags::PERFORM_ITEM_STACK_REQUEST)){
				$this->itemStackRequest = ItemStackRequest::read($in, $protocolId);
			}
			if($this->inputFlags->get(PlayerAuthInputFlags::PERFORM_BLOCK_ACTIONS)){
				$this->blockActions = [];
				$max = VarInt::readSignedInt($in);
				for($i = 0; $i < $max; ++$i){
					$this->blockActions[] = PlayerBlockAction::read($in, $protocolId);
				}
			}
			if($this->inputFlags->get(PlayerAuthInputFlags::IN_CLIENT_PREDICTED_VEHICLE) && $protocolId >= ProtocolInfo::PROTOCOL_1_20_60){
				$this->vehicleInfo = PlayerAuthInputVehicleInfo::read($in, $protocolId);
			}
		}
		$this->analogMoveVecX = LE::readFloat($in);
		$this->analogMoveVecZ = LE::readFloat($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
			$this->cameraOrientation = CommonTypes::getVector3($in);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_50){
				$this->rawMove = CommonTypes::getVector2($in);
			}
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		LE::writeFloat($out, $this->pitch);
		LE::writeFloat($out, $this->yaw);
		CommonTypes::putVector3($out, $this->position);
		LE::writeFloat($out, $this->moveVecX);
		LE::writeFloat($out, $this->moveVecZ);
		LE::writeFloat($out, $this->headYaw);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			if($protocolId <= ProtocolInfo::PROTOCOL_1_26_45){
				CommonTypes::writeDummyOptional($out);
			}
			$flagsArray = [];
			$flagCount = min($this->inputFlags->getLength(), PlayerAuthInputFlags::NUMBER_OF_FLAGS);
			for($i = 0; $i < $flagCount; ++$i){
				if($this->inputFlags->get($i)){
					$flagsArray[] = $i;
				}
			}
			CommonTypes::writeList($out, $flagsArray, VarInt::writeSignedInt(...));
		}else{
			//before 1.26.40, the presence of the optional fields below was determined by these flags
			$inputFlags = clone $this->inputFlags;
			$inputFlags->set(PlayerAuthInputFlags::PERFORM_ITEM_INTERACTION, $this->itemInteractionData !== null);
			$inputFlags->set(PlayerAuthInputFlags::PERFORM_ITEM_STACK_REQUEST, $this->itemStackRequest !== null);
			$inputFlags->set(PlayerAuthInputFlags::PERFORM_BLOCK_ACTIONS, $this->blockActions !== null);
			if($this->vehicleInfo !== null && $protocolId >= ProtocolInfo::PROTOCOL_1_20_60){
				$inputFlags->set(PlayerAuthInputFlags::IN_CLIENT_PREDICTED_VEHICLE, true);
			}
			$inputFlags->write($out, self::getLegacyInputFlagsLength($protocolId));
		}
		VarInt::writeUnsignedInt($out, $this->inputMode->value);
		VarInt::writeUnsignedInt($out, $this->playMode->value);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			VarInt::writeSignedInt($out, $this->interactionMode->value);
		}else{
			VarInt::writeUnsignedInt($out, $this->interactionMode->value);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
			CommonTypes::putVector2($out, $this->interactRotation);
		}elseif($this->playMode === PlayMode::REALITY){
			assert($this->vrGazeDirection !== null);
			CommonTypes::putVector3($out, $this->vrGazeDirection);
		}
		VarInt::writeUnsignedLong($out, $this->tick);
		CommonTypes::putVector3($out, $this->delta);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
				CommonTypes::writeOptional($out, $this->itemInteractionData, fn(ByteBufferWriter $out, ItemInteractionData $data) => $data->write($out, $protocolId));
				CommonTypes::writeOptional($out, $this->itemStackRequest, fn(ByteBufferWriter $out, ItemStackRequest $request) => $request->write($out, $protocolId));
				CommonTypes::writeOptional($out, $this->blockActions, fn(ByteBufferWriter $out, array $array) => CommonTypes::writeList($out, $array, fn(ByteBufferWriter $out, PlayerBlockAction $v) => $v->write($out, $protocolId)));
				CommonTypes::writeOptional($out, $this->vehicleInfo?->getVehicleRotation(), CommonTypes::putVector2(...));
				CommonTypes::writeOptional($out, $this->vehicleInfo?->getPredictedVehicleActorUniqueId(), CommonTypes::putActorUniqueId(...));
			}else{
				CommonTypes::writeDoubleOptional($out, $this->itemInteractionData, fn(ByteBufferWriter $out, ItemInteractionData $data) => $data->write($out, $protocolId));
				CommonTypes::writeDoubleOptional($out, $this->itemStackRequest, fn(ByteBufferWriter $out, ItemStackRequest $request) => $request->write($out, $protocolId));
				CommonTypes::writeDoubleOptional($out, $this->blockActions, fn(ByteBufferWriter $out, array $array) => CommonTypes::writeList($out, $array, fn(ByteBufferWriter $out, PlayerBlockAction $v) => $v->write($out, $protocolId)));
				CommonTypes::writeDoubleOptional($out, $this->vehicleInfo?->getVehicleRotation(), CommonTypes::putVector2(...));
				CommonTypes::writeDoubleOptional($out, $this->vehicleInfo?->getPredictedVehicleActorUniqueId(), CommonTypes::putActorUniqueId(...));
			}
		}else{
			if($this->itemInteractionData !== null){
				$this->itemInteractionData->write($out, $protocolId);
			}
			if($this->itemStackRequest !== null){
				$this->itemStackRequest->write($out, $protocolId);
			}
			if($this->blockActions !== null){
				VarInt::writeSignedInt($out, count($this->blockActions));
				foreach($this->blockActions as $blockAction){
					$blockAction->write($out, $protocolId);
				}
			}
			if($this->vehicleInfo !== null && $protocolId >= ProtocolInfo::PROTOCOL_1_20_60){
				$this->vehicleInfo->write($out, $protocolId);
			}
		}
		LE::writeFloat($out, $this->analogMoveVecX);
		LE::writeFloat($out, $this->analogMoveVecZ);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
			CommonTypes::putVector3($out, $this->cameraOrientation);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_50){
				CommonTypes::putVector2($out, $this->rawMove);
			}
		}
	}

	/**
	 * Number of bits in the input flags bitset for protocols older than 1.26.40.
	 */
	private static function getLegacyInputFlagsLength(int $protocolId) : int{
		return match(true){
			$protocolId >= ProtocolInfo::PROTOCOL_1_21_50 => 65,
			default => 64,
		};
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerAuthInput($this);
	}
}
