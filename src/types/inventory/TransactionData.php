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
	final public function decodeTransaction(ByteBufferReader $in, int $protocolId) : void{
		$actionCount = VarInt::readUnsignedInt($in);
		$this->actions = [];
		for($i = 0; $i < $actionCount; ++$i){
			$this->actions[] = (new NetworkInventoryAction())->readTransaction($in, $protocolId);
		}
		$this->decodeData($in, $protocolId);
	}

	/**
	 * @throws DataDecodeException
	 * @throws PacketDecodeException
	 */
	final public function decodeAuthInput(ByteBufferReader $in) : void{
		$actionCount = VarInt::readUnsignedInt($in);
		$this->actions = [];
		for($i = 0; $i < $actionCount; ++$i){
			$this->actions[] = (new NetworkInventoryAction())->readAuthInput($in);
		}
	}

	/**
	 * @throws DataDecodeException
	 * @throws PacketDecodeException
	 */
	abstract protected function decodeData(ByteBufferReader $in, int $protocolId) : void;

	final public function encodeTransaction(ByteBufferWriter $out, int $protocolId) : void{
		VarInt::writeUnsignedInt($out, count($this->actions));
		foreach($this->actions as $action){
			$action->writeTransaction($out, $protocolId);
		}
		$this->encodeData($out, $protocolId);
	}

	final public function encodeAuthInput(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, count($this->actions));
		foreach($this->actions as $action){
			$action->writeAuthInput($out);
		}
	}

	abstract protected function encodeData(ByteBufferWriter $out, int $protocolId) : void;
}
