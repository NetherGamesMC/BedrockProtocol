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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\EntityDiagnosticTimingInfo;
use pocketmine\network\mcpe\protocol\types\MemoryCategoryCounter;
use pocketmine\network\mcpe\protocol\types\SystemCategory;
use pocketmine\network\mcpe\protocol\types\SystemDiagnosticTimingInfo;
use pocketmine\network\mcpe\protocol\types\WhiskerScopeDataSummary;

class ServerboundDiagnosticsPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::SERVERBOUND_DIAGNOSTICS_PACKET;

	private float $avgFps;
	private float $avgServerSimTickTimeMS;
	private float $avgClientSimTickTimeMS;
	private float $avgBeginFrameTimeMS;
	private float $avgInputTimeMS;
	private float $avgRenderTimeMS;
	private float $avgEndFrameTimeMS;
	private float $avgRemainderTimePercent;
	private float $avgUnaccountedTimePercent;
	/**
	 * @var MemoryCategoryCounter[]
	 * @phpstan-var list<MemoryCategoryCounter>
	 */
	private array $memoryCategoryValues = [];
	/**
	 * @var EntityDiagnosticTimingInfo[]
	 * @phpstan-var list<EntityDiagnosticTimingInfo>
	 */
	private array $entityDiagnostics = [];
	/**
	 * @var SystemDiagnosticTimingInfo[]
	 * @phpstan-var list<SystemDiagnosticTimingInfo>
	 */
	private array $systemDiagnostics = [];
	/**
	 * >= ProtocolInfo::PROTOCOL_1_26_40 (optional since ProtocolInfo::PROTOCOL_1_26_60, a list for 1.26.40 - 1.26.50)
	 * @var SystemCategory[]|null
	 * @phpstan-var list<SystemCategory>|null
	 */
	private ?array $systemCategories = null;
	/**
	 * @var WhiskerScopeDataSummary[]
	 * @phpstan-var list<WhiskerScopeDataSummary>
	 */
	private array $whiskerScopes = [];

	/**
	 * @generate-create-func
	 * @param MemoryCategoryCounter[]      $memoryCategoryValues
	 * @param EntityDiagnosticTimingInfo[] $entityDiagnostics
	 * @param SystemDiagnosticTimingInfo[] $systemDiagnostics
	 * @param SystemCategory[]|null        $systemCategories
	 * @param WhiskerScopeDataSummary[]    $whiskerScopes
	 * @phpstan-param list<MemoryCategoryCounter>      $memoryCategoryValues
	 * @phpstan-param list<EntityDiagnosticTimingInfo> $entityDiagnostics
	 * @phpstan-param list<SystemDiagnosticTimingInfo> $systemDiagnostics
	 * @phpstan-param list<SystemCategory>|null        $systemCategories
	 * @phpstan-param list<WhiskerScopeDataSummary>    $whiskerScopes
	 */
	public static function create(
		float $avgFps,
		float $avgServerSimTickTimeMS,
		float $avgClientSimTickTimeMS,
		float $avgBeginFrameTimeMS,
		float $avgInputTimeMS,
		float $avgRenderTimeMS,
		float $avgEndFrameTimeMS,
		float $avgRemainderTimePercent,
		float $avgUnaccountedTimePercent,
		array $memoryCategoryValues,
		array $entityDiagnostics,
		array $systemDiagnostics,
		?array $systemCategories,
		array $whiskerScopes,
	) : self{
		$result = new self;
		$result->avgFps = $avgFps;
		$result->avgServerSimTickTimeMS = $avgServerSimTickTimeMS;
		$result->avgClientSimTickTimeMS = $avgClientSimTickTimeMS;
		$result->avgBeginFrameTimeMS = $avgBeginFrameTimeMS;
		$result->avgInputTimeMS = $avgInputTimeMS;
		$result->avgRenderTimeMS = $avgRenderTimeMS;
		$result->avgEndFrameTimeMS = $avgEndFrameTimeMS;
		$result->avgRemainderTimePercent = $avgRemainderTimePercent;
		$result->avgUnaccountedTimePercent = $avgUnaccountedTimePercent;
		$result->memoryCategoryValues = $memoryCategoryValues;
		$result->entityDiagnostics = $entityDiagnostics;
		$result->systemDiagnostics = $systemDiagnostics;
		$result->systemCategories = $systemCategories;
		$result->whiskerScopes = $whiskerScopes;
		return $result;
	}

	public function getAvgFps() : float{ return $this->avgFps; }

	public function getAvgServerSimTickTimeMS() : float{ return $this->avgServerSimTickTimeMS; }

	public function getAvgClientSimTickTimeMS() : float{ return $this->avgClientSimTickTimeMS; }

	public function getAvgBeginFrameTimeMS() : float{ return $this->avgBeginFrameTimeMS; }

	public function getAvgInputTimeMS() : float{ return $this->avgInputTimeMS; }

	public function getAvgRenderTimeMS() : float{ return $this->avgRenderTimeMS; }

	public function getAvgEndFrameTimeMS() : float{ return $this->avgEndFrameTimeMS; }

	public function getAvgRemainderTimePercent() : float{ return $this->avgRemainderTimePercent; }

	public function getAvgUnaccountedTimePercent() : float{ return $this->avgUnaccountedTimePercent; }

	/**
	 * @return MemoryCategoryCounter[]
	 * @phpstan-return list<MemoryCategoryCounter>
	 */
	public function getMemoryCategoryValues() : array{ return $this->memoryCategoryValues; }

	/**
	 * @return EntityDiagnosticTimingInfo[]
	 * @phpstan-return list<EntityDiagnosticTimingInfo>
	 */
	public function getEntityDiagnostics() : array{ return $this->entityDiagnostics; }

	/**
	 * @return SystemDiagnosticTimingInfo[]
	 * @phpstan-return list<SystemDiagnosticTimingInfo>
	 */
	public function getSystemDiagnostics() : array{ return $this->systemDiagnostics; }

	/**
	 * @return SystemCategory[]|null
	 * @phpstan-return list<SystemCategory>|null
	 */
	public function getSystemCategories() : ?array{ return $this->systemCategories; }

	/**
	 * @return WhiskerScopeDataSummary[]
	 * @phpstan-return list<WhiskerScopeDataSummary>
	 */
	public function getWhiskerScopes() : array{ return $this->whiskerScopes; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->avgFps = LE::readFloat($in);
		$this->avgServerSimTickTimeMS = LE::readFloat($in);
		$this->avgClientSimTickTimeMS = LE::readFloat($in);
		$this->avgBeginFrameTimeMS = LE::readFloat($in);
		$this->avgInputTimeMS = LE::readFloat($in);
		$this->avgRenderTimeMS = LE::readFloat($in);
		$this->avgEndFrameTimeMS = LE::readFloat($in);
		$this->avgRemainderTimePercent = LE::readFloat($in);
		$this->avgUnaccountedTimePercent = LE::readFloat($in);

		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_0){
			$this->memoryCategoryValues = CommonTypes::readList($in, MemoryCategoryCounter::read(...));

			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
				$this->entityDiagnostics = CommonTypes::readList($in, static fn(ByteBufferReader $in) => EntityDiagnosticTimingInfo::read($in, $protocolId));
				$this->systemDiagnostics = CommonTypes::readList($in, SystemDiagnosticTimingInfo::read(...));

				if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
					if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
						$this->systemCategories = CommonTypes::readOptional($in, static fn(ByteBufferReader $in) => CommonTypes::readList($in, SystemCategory::read(...)));
					}else{
						$this->systemCategories = CommonTypes::readList($in, SystemCategory::read(...));
					}
				}

				if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
					$this->whiskerScopes = CommonTypes::readList($in, WhiskerScopeDataSummary::read(...));
				}
			}
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		LE::writeFloat($out, $this->avgFps);
		LE::writeFloat($out, $this->avgServerSimTickTimeMS);
		LE::writeFloat($out, $this->avgClientSimTickTimeMS);
		LE::writeFloat($out, $this->avgBeginFrameTimeMS);
		LE::writeFloat($out, $this->avgInputTimeMS);
		LE::writeFloat($out, $this->avgRenderTimeMS);
		LE::writeFloat($out, $this->avgEndFrameTimeMS);
		LE::writeFloat($out, $this->avgRemainderTimePercent);
		LE::writeFloat($out, $this->avgUnaccountedTimePercent);

		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_0){
			CommonTypes::writeList($out, $this->memoryCategoryValues, static fn(ByteBufferWriter $out, MemoryCategoryCounter $v) => $v->write($out));

			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
				CommonTypes::writeList($out, $this->entityDiagnostics, static fn(ByteBufferWriter $out, EntityDiagnosticTimingInfo $v) => $v->write($out, $protocolId));
				CommonTypes::writeList($out, $this->systemDiagnostics, static fn(ByteBufferWriter $out, SystemDiagnosticTimingInfo $v) => $v->write($out));

				if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
					if($protocolId >= ProtocolInfo::PROTOCOL_1_26_60){
						CommonTypes::writeOptional($out, $this->systemCategories, static fn(ByteBufferWriter $out, array $categories) => CommonTypes::writeList($out, $categories, static fn(ByteBufferWriter $out, SystemCategory $v) => $v->write($out)));
					}else{
						CommonTypes::writeList($out, $this->systemCategories ?? [], static fn(ByteBufferWriter $out, SystemCategory $v) => $v->write($out));
					}
				}

				if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
					CommonTypes::writeList($out, $this->whiskerScopes, static fn(ByteBufferWriter $out, WhiskerScopeDataSummary $v) => $v->write($out));
				}
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleServerboundDiagnostics($this);
	}
}
