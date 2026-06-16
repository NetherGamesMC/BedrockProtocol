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

namespace pocketmine\network\mcpe\protocol\types\inventory;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

abstract class TransactionData{
	/** @var NetworkInventoryAction[] */
	protected array $actions = [];

	/**
	 * @return NetworkInventoryAction[]
	 */
	final public function getActions() : array{
		return $this->actions;
	}

	abstract public function getTypeId() : int;

	/**
	 * @throws DataDecodeException
	 * @throws PacketDecodeException
	 */
	final public function decode(ByteBufferReader $in, int $protocolId) : void{
		$hasValue = $protocolId <= ProtocolInfo::PROTOCOL_1_26_20 || CommonTypes::getBool($in);
		if($hasValue){
			$actionCount = VarInt::readUnsignedInt($in);
			for($i = 0; $i < $actionCount; ++$i){
				$this->actions[] = (new NetworkInventoryAction())->read($in, $protocolId);
			}
			$this->decodeData($in, $protocolId);
		}
	}

	/**
	 * @throws DataDecodeException
	 * @throws PacketDecodeException
	 */
	abstract protected function decodeData(ByteBufferReader $in, int $protocolId) : void;

	final public function encode(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
			CommonTypes::putBool($out, $hasValue = count($this->actions) > 0);
		}else{
			$hasValue = true;
		}
		if($hasValue){
			VarInt::writeUnsignedInt($out, count($this->actions));
			foreach($this->actions as $action){
				$action->write($out, $protocolId);
			}
			$this->encodeData($out, $protocolId);
		}
	}

	abstract protected function encodeData(ByteBufferWriter $out, int $protocolId) : void;
}
