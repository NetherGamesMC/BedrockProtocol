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

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\math\Vector2;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class PlayerAuthInputVehicleInfo{

	/**
	 * @param Vector2 $vehicleRotation Not sent before 1.20.70; decoded as (0, 0) for those protocols.
	 */
	public function __construct(
		private Vector2 $vehicleRotation,
		private int $predictedVehicleActorUniqueId
	){}

	public function getVehicleRotation() : Vector2{ return $this->vehicleRotation; }

	public function getPredictedVehicleActorUniqueId() : int{ return $this->predictedVehicleActorUniqueId; }

	/**
	 * Legacy (< 1.26.40) encoding. From 1.26.40, the rotation and actor ID are separate double optionals in
	 * PlayerAuthInputPacket.
	 * @internal
	 */
	public static function read(ByteBufferReader $in, int $protocolId) : self{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_70){
			$vehicleRotationX = LE::readFloat($in);
			$vehicleRotationZ = LE::readFloat($in);
		}
		$predictedVehicleActorUniqueId = CommonTypes::getActorUniqueId($in);

		return new self(new Vector2($vehicleRotationX ?? 0.0, $vehicleRotationZ ?? 0.0), $predictedVehicleActorUniqueId);
	}

	/**
	 * Legacy (< 1.26.40) encoding. From 1.26.40, the rotation and actor ID are separate double optionals in
	 * PlayerAuthInputPacket.
	 * @internal
	 */
	public function write(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_70){
			LE::writeFloat($out, $this->vehicleRotation->getX());
			LE::writeFloat($out, $this->vehicleRotation->getY());
		}
		CommonTypes::putActorUniqueId($out, $this->predictedVehicleActorUniqueId);
	}
}
