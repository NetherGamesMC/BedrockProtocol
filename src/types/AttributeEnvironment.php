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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\camera\CameraSetInstructionEaseType;

/**
 * Since 1.26.60 this is a name and a payload. Older protocols use a flat layout, which is converted from/to the
 * payload; the clock name and the noise name of the environment are lost in that conversion.
 *
 * @see AttributeLayer&AttributesUpdateEnvironment
 */
final class AttributeEnvironment{

	public function __construct(
		private string $name,
		private AttributeEnvironmentPayload $payload
	){}

	public function getName() : string{ return $this->name; }

	public function getPayload() : AttributeEnvironmentPayload{ return $this->payload; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$name = CommonTypes::getString($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			return new self($name, AttributeEnvironmentPayload::read($in));
		}

		$fromAttribute = CommonTypes::readOptional($in, AttributeValue::read(...));
		$attribute = AttributeValue::read($in);
		$toAttribute = CommonTypes::readOptional($in, AttributeValue::read(...));
		$currentTransitionTicks = LE::readUnsignedInt($in);
		$totalTransitionTicks = LE::readUnsignedInt($in);
		$easeType = CameraSetInstructionEaseType::fromPacket(CommonTypes::getString($in));
		$localTransitionTicks = 0;
		$noiseTransition = false;
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
			$localTransitionTicks = LE::readUnsignedInt($in);
			$noiseTransition = CommonTypes::getBool($in);
		}
		$noiseAlignment = new NoiseAlignment(NoiseAlignmentType::MIN_LOCAL_TRANSITION_END, 0);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			$noiseAlignment = NoiseAlignment::read($in);
		}

		if($noiseTransition){
			$payload = new AttributeEnvironmentNoiseTransition(
				$fromAttribute ?? $attribute,
				$toAttribute ?? $attribute,
				new AttributeEnvironmentNoiseTransitionSettings(
					$totalTransitionTicks,
					$currentTransitionTicks,
					$easeType,
					"",
					$localTransitionTicks,
					"",
					$noiseAlignment
				)
			);
		}elseif($fromAttribute !== null || $toAttribute !== null){
			$payload = new AttributeEnvironmentTransition(
				$fromAttribute ?? $attribute,
				$toAttribute ?? $attribute,
				new AttributeEnvironmentTransitionSettings($totalTransitionTicks, $currentTransitionTicks, $easeType, "")
			);
		}else{
			$payload = new AttributeEnvironmentConstant($attribute);
		}

		return new self($name, $payload);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->name);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
			VarInt::writeUnsignedInt($out, $this->payload->getTypeId());
			$this->payload->write($out);
			return;
		}

		$fromAttribute = null;
		$toAttribute = null;
		$totalTransitionTicks = 0;
		$currentTransitionTicks = 0;
		$easeType = CameraSetInstructionEaseType::LINEAR;
		$localTransitionTicks = 0;
		$noiseTransition = false;
		$noiseAlignment = new NoiseAlignment(NoiseAlignmentType::MIN_LOCAL_TRANSITION_END, 0);

		$payload = $this->payload;
		if($payload instanceof AttributeEnvironmentConstant){
			$attribute = $payload->getAttribute();
		}elseif($payload instanceof AttributeEnvironmentTransition){
			$attribute = $fromAttribute = $payload->getFromAttribute();
			$toAttribute = $payload->getToAttribute();
			$settings = $payload->getSettings();
			$totalTransitionTicks = $settings->getTotalTransitionTicks();
			$currentTransitionTicks = $settings->getCurrentTransitionTicks();
			$easeType = $settings->getEaseType();
		}elseif($payload instanceof AttributeEnvironmentNoiseTransition){
			$attribute = $fromAttribute = $payload->getFromAttribute();
			$toAttribute = $payload->getToAttribute();
			$settings = $payload->getSettings();
			$totalTransitionTicks = $settings->getTotalTransitionTicks();
			$currentTransitionTicks = $settings->getCurrentTransitionTicks();
			$easeType = $settings->getEaseType();
			$localTransitionTicks = $settings->getLocalTransitionTicks();
			$noiseTransition = true;
			$noiseAlignment = $settings->getNoiseAlignment();
		}else{
			throw new \InvalidArgumentException("Unsupported attribute environment payload " . $payload::class);
		}

		CommonTypes::writeOptional($out, $fromAttribute, fn(ByteBufferWriter $out, AttributeValue $value) => $value->writeWithType($out));
		$attribute->writeWithType($out);
		CommonTypes::writeOptional($out, $toAttribute, fn(ByteBufferWriter $out, AttributeValue $value) => $value->writeWithType($out));
		LE::writeUnsignedInt($out, $currentTransitionTicks);
		LE::writeUnsignedInt($out, $totalTransitionTicks);
		CommonTypes::putString($out, $easeType->value);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
			LE::writeUnsignedInt($out, $localTransitionTicks);
			CommonTypes::putBool($out, $noiseTransition);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			$noiseAlignment->write($out);
		}
	}
}
