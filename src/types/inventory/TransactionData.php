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
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

abstract class TransactionData{

	/**
	 * @var NetworkInventoryAction[]
	 * @phpstan-var list<NetworkInventoryAction>
	 */
	protected array $actions = [];

	/**
	 * @return NetworkInventoryAction[]
	 * @phpstan-return list<NetworkInventoryAction>
	 */
	final public function getActions() : array{
		return $this->actions;
	}

	abstract public function getTypeId() : int;

	/**
	 * @param bool $authInput whether this is the transaction of a PlayerAuthInputPacket item interaction (selects the
	 *                        legacy action layout for older protocols, see NetworkInventoryAction::read())
	 *
	 * @throws DataDecodeException
	 * @throws PacketDecodeException
	 */
	final public function decode(ByteBufferReader $in, int $protocolId, bool $authInput = false) : void{
		$this->actions = CommonTypes::readList($in, static fn(ByteBufferReader $in) => (new NetworkInventoryAction())->read($in, $protocolId, $authInput));
		$this->decodeData($in, $protocolId);
	}

	/**
	 * @throws DataDecodeException
	 * @throws PacketDecodeException
	 */
	abstract protected function decodeData(ByteBufferReader $in, int $protocolId) : void;

	/**
	 * @see TransactionData::decode() for the meaning of $authInput
	 */
	final public function encode(ByteBufferWriter $out, int $protocolId, bool $authInput = false) : void{
		CommonTypes::writeList($out, $this->actions, static fn(ByteBufferWriter $out, NetworkInventoryAction $a) => $a->write($out, $protocolId, $authInput));
		$this->encodeData($out, $protocolId);
	}

	abstract protected function encodeData(ByteBufferWriter $out, int $protocolId) : void;
}
